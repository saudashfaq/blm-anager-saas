<?php
require_once __DIR__ . '/../middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../generalfunctions/general_functions.php';
require_once __DIR__ . '/../users/company_helper.php';
require_once __DIR__ . '/../subscriptions/classes/SubscriptionLimitChecker.php';

header('Content-Type: application/json');
header('X-CSRF-TOKEN: ' . $_SESSION['csrf_token']);

$response = ['success' => false, 'message' => ''];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $backlinkUtils = new BacklinkUtils($pdo);
    $company_id = get_current_company_id();

    if ($method === 'POST') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }

        // Handle different actions
        $action = $_POST['action'] ?? '';

        if ($action === 'delete' || $action === 'bulk_delete') {
            // Handle delete operations
            $ids = [];
            if ($action === 'delete') {
                // Single delete
                if (!isset($_POST['id'])) {
                    throw new Exception('No backlink ID provided');
                }
                $ids = [(int)$_POST['id']];
            } else {
                // Bulk delete
                if (!isset($_POST['ids'])) {
                    throw new Exception('No backlinks selected for deletion');
                }
                $ids = array_map('intval', json_decode($_POST['ids'], true));
            }

            if (empty($ids)) {
                throw new Exception('No valid backlinks selected for deletion');
            }

            // Start a transaction
            $pdo->beginTransaction();

            // Fetch the base domains and campaign IDs of the backlinks to be deleted
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("
                SELECT b.id, b.campaign_id, b.base_domain 
                FROM backlinks b
                JOIN campaigns c ON b.campaign_id = c.id
                WHERE b.id IN ($placeholders) AND c.company_id = ?
            ");
            $params = array_merge($ids, [$company_id]);
            $stmt->execute($params);
            $backlinksToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($backlinksToDelete)) {
                throw new Exception('No backlinks found or permission denied');
            }

            // Delete the backlinks
            $stmt = $pdo->prepare("
                DELETE b FROM backlinks b
                JOIN campaigns c ON b.campaign_id = c.id
                WHERE b.id IN ($placeholders) AND c.company_id = ?
            ");
            $stmt->execute($params);

            // Update duplicate status for each deleted backlink's base domain
            foreach ($backlinksToDelete as $backlink) {
                $backlinkUtils->updateDuplicateStatusAfterDelete($backlink['campaign_id'], $backlink['base_domain']);
            }

            // Commit the transaction
            $pdo->commit();

            $response['success'] = true;
            $response['message'] = count($ids) > 1 ? 'Backlinks deleted successfully' : 'Backlink deleted successfully';
        } else {
            // Handle add backlink
            // Check subscription limits first
            if (!isset($_SESSION['subscription'])) {
                throw new Exception('No active subscription found. Please subscribe to a plan to create backlinks.');
            }

            $limitChecker = new SubscriptionLimitChecker($pdo, $company_id, $_SESSION['subscription']);

            $requiredFields = ['campaign_id', 'backlink_url'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $campaignId = (int)$_POST['campaign_id'];

            // Check if user can create a new backlink based on subscription limits
            try {
                $limitChecker->canCreateBacklink($campaignId);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }

            $backlinkUrl = trim($_POST['backlink_url']);
            $targetUrl = trim($_POST['target_url'] ?? '');
            $anchorText = trim($_POST['anchor_text'] ?? '');

            // Validate URL
            if (!filter_var($backlinkUrl, FILTER_VALIDATE_URL)) {
                $response['errors'] = ['backlink_url' => ['Invalid URL format']];
                throw new Exception('Invalid backlink URL');
            }

            // Check if the campaign exists and belongs to the company
            $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND company_id = ?");
            $stmt->execute([$campaignId, $company_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Campaign not found');
            }

            // Start a transaction
            $pdo->beginTransaction();

            // Insert the backlink using BacklinkUtils
            $backlinkUtils->insertBacklink(
                $campaignId,
                $backlinkUrl,
                $targetUrl,
                $anchorText,
                $_SESSION['user_id']
            );

            // Commit the transaction
            $pdo->commit();

            $response['success'] = true;
            $response['message'] = 'Backlink added successfully';
        }
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
