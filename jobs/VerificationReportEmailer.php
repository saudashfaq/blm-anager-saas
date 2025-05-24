<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../emails/send_email.php';

class VerificationReportEmailer
{
    private $pdo;
    private $mailService;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->mailService = new MailService();
    }

    public function run(): void
    {
        try {
            $campaigns = $this->findVerifiedCampaigns();

            if (empty($campaigns)) {
                echo "No campaigns found that need verification report emails.\n";
                return;
            }

            foreach ($campaigns as $campaign) {
                $this->sendVerificationReport($campaign);
            }

            echo "Verification report emails job completed.\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            error_log("Verification report emails job failed: " . $e->getMessage());
        }
    }

    private function findVerifiedCampaigns(): array
    {
        // Find campaigns that:
        // 1. Are active
        // 2. Belong to active companies
        // 3. Have all backlinks verified today
        // 4. Haven't had a report email sent today
        $query = "
            WITH CampaignVerificationStatus AS (
                SELECT 
                    c.id as campaign_id,
                    c.name as campaign_name,
                    c.company_id,
                    comp.name as company_name,
                    u.email as admin_email,
                    COUNT(b.id) as total_backlinks,
                    SUM(CASE WHEN EXISTS (
                        SELECT 1 FROM verification_logs vl 
                        WHERE vl.backlink_id = b.id 
                        AND DATE(vl.created_at) = CURDATE()
                    ) THEN 1 ELSE 0 END) as verified_today,
                    SUM(CASE WHEN vl.status = 'alive' AND DATE(vl.created_at) = CURDATE() THEN 1 ELSE 0 END) as alive_count,
                    SUM(CASE WHEN vl.status = 'dead' AND DATE(vl.created_at) = CURDATE() THEN 1 ELSE 0 END) as dead_count
                FROM campaigns c
                JOIN companies comp ON c.company_id = comp.id
                JOIN users u ON comp.id = u.company_id AND u.role = 'admin'
                JOIN backlinks b ON c.id = b.campaign_id
                LEFT JOIN verification_logs vl ON b.id = vl.backlink_id AND DATE(vl.created_at) = CURDATE()
                WHERE 
                    c.status = 'enabled'
                    AND comp.status = 'active'
                    AND u.status = 'active'
                    AND NOT EXISTS (
                        SELECT 1 FROM verification_report_logs vrl 
                        WHERE vrl.campaign_id = c.id 
                        AND DATE(vrl.created_at) = CURDATE()
                    )
                GROUP BY c.id, c.name, c.company_id, comp.name, u.email
            )
            SELECT * FROM CampaignVerificationStatus
            WHERE total_backlinks > 0 
            AND total_backlinks = verified_today;
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sendVerificationReport(array $campaign): void
    {
        try {
            // Generate the report email body
            $emailBody = "
                <h2>Backlink Verification Report</h2>
                <p>Dear {$campaign['company_name']} Admin,</p>
                <p>The backlink verification process has been completed for your campaign: <strong>{$campaign['campaign_name']}</strong></p>
                
                <h3>Verification Results:</h3>
                <ul>
                    <li>Total Backlinks Verified: {$campaign['total_backlinks']}</li>
                    <li>Active Backlinks: {$campaign['alive_count']}</li>
                    <li>Dead Backlinks: {$campaign['dead_count']}</li>
                </ul>
                
                <p>You can view the detailed results here:</p>
                <p><a href='" . BASE_URL . "campaigns/backlink_management.php?campaign_id={$campaign['campaign_id']}'>View Backlinks List</a></p>
                
                <p>Best regards,<br>The Backlink Manager Team</p>
            ";

            // Send the email
            $this->mailService->send(
                $campaign['admin_email'],
                "Backlink Verification Report - {$campaign['campaign_name']}",
                $emailBody,
                true // Send as HTML
            );

            // Log that we sent the report
            $this->logReportSent($campaign['campaign_id']);

            echo "Sent verification report email for campaign {$campaign['campaign_name']} (ID: {$campaign['campaign_id']}).\n";
        } catch (Exception $e) {
            error_log("Failed to send verification report email for campaign {$campaign['campaign_id']}: " . $e->getMessage());
            echo "Error sending report for campaign {$campaign['campaign_id']}: " . $e->getMessage() . "\n";
        }
    }

    private function logReportSent(int $campaignId): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO verification_report_logs (campaign_id, created_at) VALUES (?, NOW())"
        );
        $stmt->execute([$campaignId]);
    }
}

// Execute the job
$emailer = new VerificationReportEmailer($pdo);
$emailer->run();
