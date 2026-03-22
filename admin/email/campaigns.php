<?php
require_once '../../config/environment.php';
require_once '../../includes/core/Database.php';
require_once '../../includes/core/Functions.php';
require_once '../../includes/core/Auth.php';
require_once '../../includes/core/EmailTemplate.php';

use includes\core\Auth;

// Check admin authentication
Auth::requireAdmin();

$db = Database::getInstance();
$emailTemplate = new EmailTemplate();

// Get campaigns with details
$campaigns = $db->fetchAll("
    SELECT c.*, 
           t.name as template_name,
           COUNT(l.id) as total_sent_actual,
           SUM(CASE WHEN l.status = 'sent' THEN 1 ELSE 0 END) as delivered,
           SUM(CASE WHEN l.status = 'opened' THEN 1 ELSE 0 END) as opened,
           SUM(CASE WHEN l.status = 'clicked' THEN 1 ELSE 0 END) as clicked
    FROM email_campaigns c
    LEFT JOIN email_templates t ON c.template_id = t.id
    LEFT JOIN email_logs l ON c.id = l.campaign_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT 50
");

// Get statistics
$stats = [
    'total_campaigns' => $db->fetchOne("SELECT COUNT(*) as count FROM email_campaigns")['count'],
    'total_emails_sent' => $db->fetchOne("SELECT COUNT(*) as count FROM email_logs WHERE status IN ('sent', 'opened', 'clicked')")['count'],
    'open_rate' => $db->fetchOne("SELECT ROUND((COUNT(CASE WHEN status = 'opened' THEN 1 END) / COUNT(*)) * 100, 2) as rate FROM email_logs")['rate'],
    'click_rate' => $db->fetchOne("SELECT ROUND((COUNT(CASE WHEN status = 'clicked' THEN 1 END) / COUNT(*)) * 100, 2) as rate FROM email_logs")['rate']
];

$pageTitle = "Email Campaigns";
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-chart-line me-2"></i> Email Campaigns</h1>
                <a href="send.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> New Campaign
                </a>
            </div>

            <!-- Stats Overview -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                        Total Campaigns
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['total_campaigns']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-envelope-open-text fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                        Emails Sent
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($stats['total_emails_sent']); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-paper-plane fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                        Open Rate
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['open_rate'] ?? '0'; ?>%</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-envelope-open fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                        Click Rate
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['click_rate'] ?? '0'; ?>%</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-mouse-pointer fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaigns Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Campaign History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($campaigns)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <h5>No campaigns yet</h5>
                            <p class="text-muted">Create your first email campaign to start communicating with your audience.</p>
                            <a href="send.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Create Campaign
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Recipients</th>
                                        <th>Template</th>
                                        <th>Status</th>
                                        <th>Statistics</th>
                                        <th>Sent</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($campaign['campaign_name']); ?></strong>
                                            <div class="text-muted small">
                                                <?php echo ucfirst(str_replace('_', ' ', $campaign['recipients_type'])); ?>
                                            </div>
                                        </td>
                                        <td><?php echo number_format($campaign['total_sent_actual']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['template_name'] ?: 'Custom Email'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $campaign['status'] == 'completed' ? 'success' : ($campaign['status'] == 'sending' ? 'info' : 'secondary');
                                            ?>">
                                                <?php echo ucfirst($campaign['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div>Delivered: <?php echo number_format($campaign['delivered']); ?></div>
                                                <div>Opened: <?php echo number_format($campaign['opened']); ?> 
                                                    (<?php echo $campaign['delivered'] > 0 ? round(($campaign['opened'] / $campaign['delivered']) * 100) : 0; ?>%)
                                                </div>
                                                <div>Clicked: <?php echo number_format($campaign['clicked']); ?>
                                                    (<?php echo $campaign['delivered'] > 0 ? round(($campaign['clicked'] / $campaign['delivered']) * 100) : 0; ?>%)
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($campaign['sent_at'] ?? $campaign['created_at'])); ?><br>
                                                <?php echo date('g:i A', strtotime($campaign['sent_at'] ?? $campaign['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-campaign" 
                                                    data-id="<?php echo $campaign['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($campaign['campaign_name']); ?>">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                            <button class="btn btn-sm btn-secondary view-logs" 
                                                    data-id="<?php echo $campaign['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($campaign['campaign_name']); ?>">
                                                <i class="fas fa-list"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Campaign Details Modal -->
<div class="modal fade" id="campaignModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Campaign Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="campaign_details">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading campaign data...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View campaign details
    document.querySelectorAll('.view-campaign').forEach(btn => {
        btn.addEventListener('click', function() {
            const campaignId = this.dataset.id;
            const campaignName = this.dataset.name;
            
            document.querySelector('#campaignModal .modal-title').textContent = campaignName + ' - Campaign Details';
            
            fetch('campaign_details.php?id=' + campaignId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('campaign_details').innerHTML = html;
                });
            
            new bootstrap.Modal(document.getElementById('campaignModal')).show();
        });
    });
    
    // View email logs
    document.querySelectorAll('.view-logs').forEach(btn => {
        btn.addEventListener('click', function() {
            const campaignId = this.dataset.id;
            const campaignName = this.dataset.name;
            
            document.querySelector('#campaignModal .modal-title').textContent = campaignName + ' - Email Logs';
            
            fetch('campaign_logs.php?id=' + campaignId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('campaign_details').innerHTML = html;
                });
            
            new bootstrap.Modal(document.getElementById('campaignModal')).show();
        });
    });
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>