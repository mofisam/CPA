<?php
require_once '../../config/environment.php';
require_once '../../includes/core/Database.php';
require_once '../../includes/core/Functions.php';
require_once '../../includes/core/Auth.php';

use includes\core\Auth;
use includes\core\Database;

// Check admin authentication
Auth::requireAdmin();

$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        foreach ($_POST['settings'] as $key => $value) {
            $db->update('site_settings', 
                ['setting_value' => trim($value)], 
                'setting_key = ?', 
                [$key]
            );
        }
        
        $_SESSION['flash_message'] = [
            'text' => 'Settings updated successfully!',
            'type' => 'success'
        ];
        header('Location: settings.php');
        exit();
    }
}

// Get all settings grouped by category
$settings = $db->fetchAll(
    "SELECT * FROM site_settings ORDER BY setting_group, sort_order"
);

// Group settings by category
$settingsByGroup = [];
foreach ($settings as $setting) {
    $group = $setting['setting_group'];
    if (!isset($settingsByGroup[$group])) {
        $settingsByGroup[$group] = [];
    }
    $settingsByGroup[$group][] = $setting;
}

// Define group labels
$groupLabels = [
    'social_media' => 'Social Media',
    'contact' => 'Contact Information',
    'site' => 'Site Configuration',
    'features' => 'Site Features'
];

$pageTitle = "Site Settings";
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Site Settings</h1>
                <button type="submit" form="settingsForm" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save All Settings
                </button>
            </div>

            <form method="POST" action="" id="settingsForm">
                <?php foreach ($settingsByGroup as $group => $groupSettings): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <?php echo isset($groupLabels[$group]) ? $groupLabels[$group] : ucfirst(str_replace('_', ' ', $group)); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($groupSettings as $setting): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="setting_<?php echo $setting['id']; ?>" class="form-label">
                                            <?php echo htmlspecialchars($setting['display_name']); ?>
                                            <?php if ($setting['description']): ?>
                                                <small class="text-muted d-block"><?php echo htmlspecialchars($setting['description']); ?></small>
                                            <?php endif; ?>
                                        </label>
                                        
                                        <?php if ($setting['setting_type'] === 'textarea'): ?>
                                            <textarea class="form-control" 
                                                      id="setting_<?php echo $setting['id']; ?>" 
                                                      name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                      rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                        <?php elseif ($setting['setting_type'] === 'boolean'): ?>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       role="switch" 
                                                       id="setting_<?php echo $setting['id']; ?>" 
                                                       name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                       value="1" 
                                                       <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="setting_<?php echo $setting['id']; ?>">
                                                    <?php echo $setting['setting_value'] == '1' ? 'Enabled' : 'Disabled'; ?>
                                                </label>
                                            </div>
                                        <?php elseif ($setting['setting_type'] === 'email'): ?>
                                            <input type="email" 
                                                   class="form-control" 
                                                   id="setting_<?php echo $setting['id']; ?>" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        <?php elseif ($setting['setting_type'] === 'url'): ?>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-link"></i>
                                                </span>
                                                <input type="url" 
                                                       class="form-control" 
                                                       id="setting_<?php echo $setting['id']; ?>" 
                                                       name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                                       placeholder="https://example.com">
                                            </div>
                                        <?php elseif ($setting['setting_type'] === 'number'): ?>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="setting_<?php echo $setting['id']; ?>" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        <?php else: ?>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="setting_<?php echo $setting['id']; ?>" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        <?php endif; ?>
                                        
                                        <small class="text-muted d-block mt-1">
                                            Key: <code><?php echo htmlspecialchars($setting['setting_key']); ?></code>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <input type="hidden" name="update_settings" value="1">
            </form>

            <!-- Current Site Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Current Site Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th>PHP Version</th>
                                        <td><?php echo phpversion(); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Database</th>
                                        <td>MySQL</td>
                                    </tr>
                                    <tr>
                                        <th>Server Time</th>
                                        <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Timezone</th>
                                        <td><?php echo date_default_timezone_get(); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th>Site URL</th>
                                        <td><?php echo SITE_URL; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Debug Mode</th>
                                        <td>
                                            <span class="badge bg-<?php echo DEBUG_MODE ? 'warning' : 'success'; ?>">
                                                <?php echo DEBUG_MODE ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Admin Email</th>
                                        <td><?php echo ADMIN_EMAIL; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Settings Count</th>
                                        <td><?php echo count($settings); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const settingsForm = document.getElementById('settingsForm');
    
    // Form validation
    settingsForm.addEventListener('submit', function(e) {
        // Validate email fields
        const emailFields = settingsForm.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !field.value.includes('@')) {
                e.preventDefault();
                alert('Please enter valid email addresses in email fields.');
                field.focus();
                return false;
            }
        });
        
        // Validate URL fields
        const urlFields = settingsForm.querySelectorAll('input[type="url"]');
        urlFields.forEach(field => {
            if (field.value && !field.value.startsWith('http')) {
                e.preventDefault();
                alert('Please enter valid URLs starting with http:// or https://');
                field.focus();
                return false;
            }
        });
        
        if (!confirm('Are you sure you want to save all settings?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Toggle switch labels
    const switches = settingsForm.querySelectorAll('.form-switch input[type="checkbox"]');
    switches.forEach(switchEl => {
        const label = switchEl.parentElement.querySelector('.form-check-label');
        switchEl.addEventListener('change', function() {
            label.textContent = this.checked ? 'Enabled' : 'Disabled';
        });
    });
    
    // Add social media icon previews
    const socialMediaFields = {
        'facebook_url': { icon: 'facebook', color: '#1877f2' },
        'twitter_url': { icon: 'twitter', color: '#1da1f2' },
        'instagram_url': { icon: 'instagram', color: '#e4405f' },
        'linkedin_url': { icon: 'linkedin', color: '#0a66c2' },
        'youtube_url': { icon: 'youtube', color: '#ff0000' }
    };
    
    Object.keys(socialMediaFields).forEach(key => {
        const input = document.querySelector(`input[name="settings[${key}]"]`);
        if (input) {
            const socialInfo = socialMediaFields[key];
            
            // Create preview container
            const preview = document.createElement('div');
            preview.className = 'mt-2';
            preview.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="social-icon-preview me-2" 
                         style="width: 30px; height: 30px; background-color: ${socialInfo.color}; 
                                border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fab fa-${socialInfo.icon} text-white"></i>
                    </div>
                    <small class="text-muted">
                        ${socialInfo.icon.charAt(0).toUpperCase() + socialInfo.icon.slice(1)} link
                    </small>
                </div>
            `;
            
            // Insert after input
            input.parentNode.appendChild(preview);
            
            // Update preview on input
            input.addEventListener('input', function() {
                const previewText = preview.querySelector('small');
                if (this.value) {
                    previewText.innerHTML = `
                        <a href="${this.value}" target="_blank" class="text-decoration-none">
                            ${socialInfo.icon.charAt(0).toUpperCase() + socialInfo.icon.slice(1)}: 
                            <span class="text-primary">${this.value.replace(/^https?:\/\//, '')}</span>
                        </a>
                    `;
                } else {
                    previewText.textContent = `${socialInfo.icon.charAt(0).toUpperCase() + socialInfo.icon.slice(1)} link`;
                }
            });
            
            // Trigger initial update
            input.dispatchEvent(new Event('input'));
        }
    });
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>