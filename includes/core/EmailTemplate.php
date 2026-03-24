<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

use includes\core\Auth;
use includes\core\Database;
class EmailTemplate {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all templates
     */
    public function getAllTemplates($category = null) {
        $sql = "SELECT * FROM email_templates";
        $params = [];
        
        if ($category && $category !== 'all') {
            $sql .= " WHERE category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get template by ID
     */
    public function getTemplate($id) {
        return $this->db->fetchOne("SELECT * FROM email_templates WHERE id = ?", [$id]);
    }
    
    /**
     * Create template
     */
    public function createTemplate($data) {
        $templateData = [
            'name' => $data['name'],
            'subject' => $data['subject'],
            'content' => $data['content'],
            'variables' => $data['variables'] ?? null,
            'category' => $data['category'],
            'created_by' => $_SESSION['admin_id']
        ];
        
        return $this->db->insert('email_templates', $templateData);
    }
    
    /**
     * Update template
     */
    public function updateTemplate($id, $data) {
        $templateData = [
            'name' => $data['name'],
            'subject' => $data['subject'],
            'content' => $data['content'],
            'variables' => $data['variables'] ?? null,
            'category' => $data['category']
        ];
        
        return $this->db->update('email_templates', $templateData, 'id = ?', [$id]);
    }
    
    /**
     * Delete template
     */
    public function deleteTemplate($id) {
        return $this->db->delete('email_templates', 'id = ?', [$id]);
    }
    
    /**
     * Process template with variables
     */
    public function processTemplate($template, $data) {
        $content = $template['content'];
        $subject = $template['subject'];
        
        // Replace variables in content
        if (isset($data['recipient_name'])) {
            $content = str_replace('{recipient_name}', $data['recipient_name'], $content);
            $subject = str_replace('{recipient_name}', $data['recipient_name'], $subject);
        }
        
        if (isset($data['recipient_email'])) {
            $content = str_replace('{recipient_email}', $data['recipient_email'], $content);
        }
        
        // Add unsubscribe link
        if (isset($data['unsubscribe_token'])) {
            $unsubscribeLink = SITE_URL . 'unsubscribe.php?token=' . $data['unsubscribe_token'];
            $content = str_replace('{unsubscribe_link}', $unsubscribeLink, $content);
        } else {
            $content = str_replace('{unsubscribe_link}', '#', $content);
        }
        
        // Add dynamic content
        if (isset($data['dynamic_content'])) {
            $content = str_replace('{dynamic_content}', $data['dynamic_content'], $content);
        }
        
        // Add date
        $content = str_replace('{date}', date('F j, Y'), $content);
        $content = str_replace('{site_name}', SITE_NAME, $content);
        $content = str_replace('{site_url}', SITE_URL, $content);
        
        return [
            'subject' => $subject,
            'content' => $content
        ];
    }
    
    /**
     * Get recipients based on type and filter
     */
    public function getRecipients($type, $filter = null) {
        $recipients = [];
        
        switch ($type) {
            case 'subscribers':
                $sql = "SELECT email, full_name, unsubscribe_token FROM subscribers WHERE is_active = 1";
                $recipients = $this->db->fetchAll($sql);
                break;
                
            case 'course_interests':
                $sql = "SELECT ci.email, ci.full_name, ci.interests, t.title as course_title 
                        FROM course_interests ci 
                        LEFT JOIN trainings t ON ci.training_id = t.id 
                        WHERE ci.status NOT IN ('not_interested')";
                
                if ($filter && isset($filter['course_id'])) {
                    $sql .= " AND ci.training_id = ?";
                    $recipients = $this->db->fetchAll($sql, [$filter['course_id']]);
                } else {
                    $recipients = $this->db->fetchAll($sql);
                }
                break;
                
            case 'specific_course':
                if ($filter && isset($filter['course_id'])) {
                    $sql = "SELECT ci.email, ci.full_name, ci.interests, t.title as course_title 
                            FROM course_interests ci 
                            LEFT JOIN trainings t ON ci.training_id = t.id 
                            WHERE ci.training_id = ? AND ci.status NOT IN ('not_interested')";
                    $recipients = $this->db->fetchAll($sql, [$filter['course_id']]);
                }
                break;
                
            case 'manual':
                if ($filter && isset($filter['emails']) && is_array($filter['emails'])) {
                    foreach ($filter['emails'] as $email) {
                        $recipients[] = [
                            'email' => $email,
                            'full_name' => '',
                            'unsubscribe_token' => null
                        ];
                    }
                }
                break;
        }
        
        // Add unsubscribe token if not present
        foreach ($recipients as &$recipient) {
            if (empty($recipient['unsubscribe_token'])) {
                $recipient['unsubscribe_token'] = $this->generateUnsubscribeToken($recipient['email']);
            }
        }
        
        return $recipients;
    }
    
    /**
     * Generate unsubscribe token
     */
    private function generateUnsubscribeToken($email) {
        return hash('sha256', $email . SECRET_KEY . time());
    }
    
    /**
     * Log sent email
     */
    public function logEmail($campaignId, $recipient, $subject, $content, $status = 'sent', $error = null) {
        $logData = [
            'campaign_id' => $campaignId,
            'recipient_email' => $recipient['email'],
            'recipient_name' => $recipient['full_name'] ?? '',
            'subject' => $subject,
            'content' => $content,
            'status' => $status,
            'error_message' => $error,
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('email_logs', $logData);
    }
    
    /**
     * Create campaign
     */
    public function createCampaign($data) {
        $campaignData = [
            'template_id' => $data['template_id'] ?? null,
            'campaign_name' => $data['campaign_name'],
            'recipients_type' => $data['recipients_type'],
            'recipients_filter' => $data['recipients_filter'] ?? null,
            'created_by' => $_SESSION['admin_id'],
            'status' => 'draft'
        ];
        
        return $this->db->insert('email_campaigns', $campaignData);
    }
    
    /**
     * Update campaign status
     */
    public function updateCampaignStatus($campaignId, $status, $sentCount = null) {
        $updateData = ['status' => $status];
        if ($sentCount !== null) {
            $updateData['total_sent'] = $sentCount;
        }
        if ($status === 'completed' || $status === 'sent') {
            $updateData['sent_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('email_campaigns', $updateData, 'id = ?', [$campaignId]);
    }
}
?>