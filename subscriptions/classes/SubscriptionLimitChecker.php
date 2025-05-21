<?php

require_once __DIR__ . '/../../config/subscription_plans.php';

class SubscriptionLimitChecker
{
    private $db;
    private $companyId;
    private $subscription;

    public function __construct($db, $companyId, $subscription)
    {
        $this->db = $db;
        $this->companyId = $companyId;
        $this->subscription = $subscription;
    }

    /**
     * Check if company can create a new campaign
     * @throws Exception if limit is reached
     */
    public function canCreateCampaign()
    {
        $currentCount = $this->getCurrentCampaignCount();
        $maxCampaigns = $this->subscription['limits']['max_campaigns'];

        if ($maxCampaigns !== -1 && $currentCount >= $maxCampaigns) {
            throw new Exception(
                "Campaign limit reached. Your current plan ({$this->subscription['plan_name']}) allows a maximum of {$maxCampaigns} campaigns."
            );
        }
    }

    /**
     * Check if a new backlink can be created for a campaign
     * @param int $campaignId
     * @throws Exception if limit is reached
     */
    public function canCreateBacklink($campaignId)
    {
        // Check campaign-specific limit
        $campaignCount = $this->getCampaignBacklinksCount($campaignId);
        $maxPerCampaign = $this->subscription['limits']['max_backlinks_per_campaign'];

        if ($maxPerCampaign !== -1 && $campaignCount >= $maxPerCampaign) {
            throw new Exception(
                "Campaign backlink limit reached. Your current plan allows a maximum of {$maxPerCampaign} backlinks per campaign."
            );
        }

        // Check total backlinks limit
        $totalCount = $this->getTotalBacklinksCount();
        $maxTotal = $this->subscription['limits']['max_total_backlinks'];

        if ($maxTotal !== -1 && $totalCount >= $maxTotal) {
            throw new Exception(
                "Total backlink limit reached. Your current plan allows a maximum of {$maxTotal} total backlinks."
            );
        }
    }

    /**
     * Get current number of campaigns for the company
     */
    private function getCurrentCampaignCount(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM campaigns WHERE company_id = ?");
        $stmt->execute([$this->companyId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get current number of backlinks for a specific campaign
     */
    public function getCampaignBacklinksCount($campaignId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM backlinks b 
            JOIN campaigns c ON b.campaign_id = c.id 
            WHERE c.id = ? AND c.company_id = ?
        ");
        $stmt->execute([$campaignId, $this->companyId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get total number of backlinks across all campaigns for the company
     */
    public function getTotalBacklinksCount(): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM backlinks b 
            JOIN campaigns c ON b.campaign_id = c.id 
            WHERE c.company_id = ?
        ");
        $stmt->execute([$this->companyId]);
        return (int)$stmt->fetchColumn();
    }
}
