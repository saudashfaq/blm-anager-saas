<?php
// campaign_management.php
require_once __DIR__ . '/../middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/validationHelper.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../users/company_helper.php';
require_once __DIR__ . '/../subscriptions/classes/SubscriptionLimitChecker.php';

// Check if user is logged in and has access
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'user') {
    header("Location:" . BASE_URL . "../index.php");
    exit;
}

$company_id = get_current_company_id();

// Add at the beginning after includes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Handle get single campaign request
    if (isset($_POST['action']) && $_POST['action'] === 'get') {
        try {
            $campaign_id = intval($_POST['campaign_id']);

            // Check ownership if not admin
            if ($_SESSION['role'] !== 'admin') {
                $ownership = checkCampaignOwnership($campaign_id, $_SESSION['user_id'], $company_id);
                if (!$ownership) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'You do not have permission to access this campaign'
                    ]);
                    exit;
                }
            }

            // Fetch campaign data
            $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND company_id = ?");
            $stmt->execute([$campaign_id, $company_id]);
            $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($campaign) {
                echo json_encode([
                    'success' => true,
                    'campaign' => $campaign
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Campaign not found'
                ]);
            }
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching campaign: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    //Handle create new campaign request
    if (isset($_POST['action']) && $_POST['action'] === 'create_campaign') {
        try {
            // Check subscription limits first
            if (!isset($_SESSION['subscription'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No active subscription found. Please subscribe to a plan to create campaigns.'
                ]);
                exit;
            }

            $limitChecker = new SubscriptionLimitChecker($pdo, $company_id, $_SESSION['subscription']);

            try {
                $limitChecker->canCreateCampaign();
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }

            $validator = new ValidationHelper($_POST);
            $validator
                ->required('campaign_name', 'Campaign name is required')
                ->minLength('campaign_name', 3)
                ->maxLength('campaign_name', 255)
                ->required('base_url')
                ->baseUrl('base_url')
                ->minLength('base_url', 5)
                ->maxLength('base_url', 255)
                ->required('verification_frequency')
                ->in('verification_frequency', array_keys($campaign_frequency));

            if (!$validator->passes()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Campaign was not created.',
                    'errors' => $validator->getErrors(),
                ]);
                exit;
            }
            $campaign_name = trim($_POST['campaign_name']);
            $base_url = $validator->sanitize('base_url');
            $verification_frequency = trim($_POST['verification_frequency']);
            $user_id = $_SESSION['user_id'];


            //check if campaign is already available with the same base URL
            $stmt = $pdo->prepare("SELECT EXISTS(SELECT 1 FROM campaigns WHERE base_url = ? AND company_id = ?) as campaign_exists");
            $stmt->execute([$base_url, $company_id]);
            $exists = $stmt->fetchColumn();
            if ($exists) {
                echo json_encode([
                    'success' => false,
                    'message' => 'You already have a campaign with the provided Base URL.',

                ]);
                exit;
            }



            $stmt = $pdo->prepare("INSERT INTO campaigns (company_id, user_id, `name`, base_url, verification_frequency, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$company_id, $user_id, $campaign_name, $base_url, $verification_frequency]);

            // Fetch the newly created campaign
            $campaign_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND company_id = ?");
            $stmt->execute([$campaign_id, $company_id]);
            $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Campaign created successfully',
                'campaign' => $campaign
            ]);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error creating campaign: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Handle update campaign functionality
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        try {
            $validator = new ValidationHelper($_POST);
            $validator
                ->required('campaign_name', 'Campaign name is required')
                ->minLength('campaign_name', 4)
                ->maxLength('campaign_name', 255)
                ->required('verification_frequency')
                ->in('verification_frequency', array_keys($campaign_frequency));

            if (!$validator->passes()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Campaign was not updated.',
                    'errors' => $validator->getErrors(),
                ]);
                exit;
            }

            $campaign_id = intval($_POST['campaign_id']);
            $campaign_name = trim($_POST['campaign_name']);
            $verification_frequency = trim($_POST['verification_frequency']);

            // Debug output
            /*
            echo json_encode([
                'debug' => true,
                'post_data' => $_POST,
                'campaign_id' => $campaign_id,
                'campaign_name' => $campaign_name
            ]);
            exit;
            */

            // Check if campaign exists and user has permission
            if ($_SESSION['role'] === 'admin' || checkCampaignOwnership($campaign_id, $_SESSION['user_id'], $company_id)) {
                // Validate inputs
                if (empty($campaign_name) || empty($verification_frequency)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Campaign name and verification frequency are required'
                    ]);
                    exit;
                }

                require_once __DIR__ . '/../config/constants.php';

                $status = $campaign_statuses['enabled']['value'];

                if (isset($_POST['status'])) {
                    $status = in_array($_POST['status'], array_keys($campaign_statuses)) ? $_POST['status'] : $status;
                }

                // Update query without base_url
                $stmt = $pdo->prepare("UPDATE campaigns SET `name` = ?, verification_frequency = ?, `status` = ?, updated_at = NOW() WHERE id = ? AND company_id = ?");
                $stmt->execute([$campaign_name, $verification_frequency, $status, $campaign_id, $company_id]);

                // Fetch updated campaign data
                $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND company_id = ?");
                $stmt->execute([$campaign_id, $company_id]);
                $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($campaign) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Campaign updated successfully',
                        'campaign' => $campaign
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Campaign not found after update'
                    ]);
                }
                exit;
            } else {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You are not authorized to access this action.'
                ]);
                exit;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating campaign: ' . $e->getMessage()
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating campaign: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    //handle delete campaign request
    if (isset($_POST['action']) && $_POST['action'] === 'delete_campaign') {
        try {
            $campaign_id = intval($_POST['campaign_id']);

            // Check ownership
            if ($_SESSION['role'] !== 'admin') {

                //checkOwnership use function

                $ownership = checkCampaignOwnership($campaign_id, $_SESSION['user_id'], $company_id);

                if (empty($ownership)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'You do not have permission to delete this campaign'
                    ]);
                    exit;
                }
            }

            // Delete the campaign
            $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ? AND company_id = ?");
            $stmt->execute([$campaign_id, $company_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Campaign deleted successfully'
            ]);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting campaign: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

// Function to check campaign ownership
function checkCampaignOwnership($campaign_id, $user_id, $company_id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND user_id = ? AND company_id = ?");
        $stmt->execute([$campaign_id, $user_id, $company_id]);
        return $stmt->fetch() ? true : false;
    } catch (PDOException $e) {
        return false;
    }
}
