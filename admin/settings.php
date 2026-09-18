<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/email.php';

requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();
$defaults = [
    'site_name' => 'Gadget 50',
    'site_url' => '',
    'site_logo' => '',
    'site_favicon' => '',
    'site_description' => '',
    'site_email' => '',
    'contact_phone' => '',
    'default_language' => 'en',
    'default_timezone' => 'UTC',
    'maintenance_mode' => '0',
    'maintenance_message' => '',
    'header_visible' => '1',
    'footer_visible' => '1',
    'footer_name' => '',
    'footer_description' => '',
    'footer_copyright' => '© ' . date('Y') . ' Gadget 50',
    'footer_logo' => '',
    'footer_links' => '',
    'footer_contact' => '',
    'footer_social_links' => '',
    'meta_title' => '',
    'meta_description' => '',
    'meta_keywords' => '',
    'og_image' => '',
    'robots_meta' => 'index,follow',
    'canonical_url' => '',
    'google_analytics_id' => '',
    'google_search_console_verification' => '',
    'registration_enabled' => '1',
    'email_required' => '0',
    'email_service_enabled' => '0',
    'email_verification_enabled' => '0',
    'password_reset_email_enabled' => '0',
    'system_email_notifications_enabled' => '0',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $keys = [
        'site_name','site_url','site_logo','site_favicon','site_description','site_email','contact_phone','default_language','default_timezone','maintenance_mode','maintenance_message',
        'header_visible','footer_visible','footer_name','footer_description','footer_copyright','footer_logo','footer_links','footer_contact','footer_social_links',
        'meta_title','meta_description','meta_keywords','og_image','robots_meta','canonical_url','google_analytics_id','google_search_console_verification','registration_enabled',
        'email_service_enabled','email_required','email_verification_enabled','password_reset_email_enabled','system_email_notifications_enabled',
        'email_provider','smtp_host','smtp_port','smtp_encryption','smtp_username','smtp_password','smtp_from_email','smtp_from_name','admin_alert_email'
    ];

    foreach ($keys as $key) {
        $value = trim((string) ($_POST[$key] ?? ($defaults[$key] ?? '')));
        if (in_array($key, ['maintenance_mode','header_visible','footer_visible','registration_enabled','email_service_enabled','email_required','email_verification_enabled','password_reset_email_enabled','system_email_notifications_enabled'], true)) {
            $value = isset($_POST[$key]) ? '1' : '0';
        }
        if ($key === 'smtp_password' && $value === '') {
            $value = getSetting('smtp_password', '');
        }
        setSetting($pdo, $key, $value);
    }

    if ((string) ($_POST['email_service_enabled'] ?? '0') === '1') {
        setFlash('success', 'Email service enabled. SMTP settings are now active for the website.');
    } else {
        setFlash('success', 'Email service disabled. The site continues without email features.');
    }

    redirect('settings.php');
}

$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
    $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
}

$setting = static function (string $key, string $default = '') use ($settings, $defaults): string {
    return $settings[$key] ?? ($defaults[$key] ?? $default);
};

$flash = getFlash();
$siteName = $setting('site_name', 'Gadget 50');
$emailEnabled = $setting('email_service_enabled', '0') === '1';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website settings | <?= e($siteName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">
<div class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="eyebrow">ADMIN CONTROL</span>
            <h1>Website settings</h1>
        </div>
        <a class="btn btn-outline-secondary" href="index.php">Back to admin</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="content-panel mb-4">
            <h2>General settings</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Website name</label><input class="form-control" name="site_name" value="<?= e($setting('site_name', 'Gadget 50')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Website URL</label><input class="form-control" name="site_url" value="<?= e($setting('site_url', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Website logo</label><input class="form-control" name="site_logo" value="<?= e($setting('site_logo', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Website favicon</label><input class="form-control" name="site_favicon" value="<?= e($setting('site_favicon', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Website email</label><input class="form-control" type="email" name="site_email" value="<?= e($setting('site_email', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Contact phone</label><input class="form-control" name="contact_phone" value="<?= e($setting('contact_phone', '')) ?>"></div>
                <div class="col-12"><label class="form-label">Website description</label><textarea class="form-control" name="site_description" rows="2"><?= e($setting('site_description', '')) ?></textarea></div>
                <div class="col-md-4"><label class="form-label">Default language</label><input class="form-control" name="default_language" value="<?= e($setting('default_language', 'en')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Timezone</label><input class="form-control" name="default_timezone" value="<?= e($setting('default_timezone', 'UTC')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Maintenance message</label><input class="form-control" name="maintenance_message" value="<?= e($setting('maintenance_message', '')) ?>"></div>
                <div class="col-md-6 form-check ms-2"><input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" <?= $setting('maintenance_mode', '0') === '1' ? 'checked' : '' ?>><label class="form-check-label">Maintenance mode</label></div>
                <div class="col-md-6 form-check"><input class="form-check-input" type="checkbox" name="registration_enabled" value="1" <?= $setting('registration_enabled', '1') === '1' ? 'checked' : '' ?>><label class="form-check-label">Allow public registration</label></div>
            </div>
        </div>

        <div class="content-panel mb-4">
            <h2>Header & footer</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Header visible</label><div class="form-check"><input class="form-check-input" type="checkbox" name="header_visible" value="1" <?= $setting('header_visible', '1') === '1' ? 'checked' : '' ?>></div></div>
                <div class="col-md-6"><label class="form-label">Footer visible</label><div class="form-check"><input class="form-check-input" type="checkbox" name="footer_visible" value="1" <?= $setting('footer_visible', '1') === '1' ? 'checked' : '' ?>></div></div>
                <div class="col-md-6"><label class="form-label">Footer website name</label><input class="form-control" name="footer_name" value="<?= e($setting('footer_name', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Footer logo</label><input class="form-control" name="footer_logo" value="<?= e($setting('footer_logo', '')) ?>"></div>
                <div class="col-12"><label class="form-label">Footer description</label><textarea class="form-control" name="footer_description" rows="2"><?= e($setting('footer_description', '')) ?></textarea></div>
                <div class="col-12"><label class="form-label">Footer copyright</label><input class="form-control" name="footer_copyright" value="<?= e($setting('footer_copyright', '© ' . date('Y') . ' Gadget 50')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Footer links</label><textarea class="form-control" name="footer_links" rows="2"><?= e($setting('footer_links', '')) ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Footer contact</label><textarea class="form-control" name="footer_contact" rows="2"><?= e($setting('footer_contact', '')) ?></textarea></div>
                <div class="col-12"><label class="form-label">Footer social links</label><textarea class="form-control" name="footer_social_links" rows="2"><?= e($setting('footer_social_links', '')) ?></textarea></div>
            </div>
        </div>

        <div class="content-panel mb-4">
            <h2>SEO</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Meta title</label><input class="form-control" name="meta_title" value="<?= e($setting('meta_title', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Meta keywords</label><input class="form-control" name="meta_keywords" value="<?= e($setting('meta_keywords', '')) ?>"></div>
                <div class="col-12"><label class="form-label">Meta description</label><textarea class="form-control" name="meta_description" rows="2"><?= e($setting('meta_description', '')) ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Open Graph image</label><input class="form-control" name="og_image" value="<?= e($setting('og_image', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Canonical URL</label><input class="form-control" name="canonical_url" value="<?= e($setting('canonical_url', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Google Analytics ID</label><input class="form-control" name="google_analytics_id" value="<?= e($setting('google_analytics_id', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Search Console verification</label><input class="form-control" name="google_search_console_verification" value="<?= e($setting('google_search_console_verification', '')) ?>"></div>
            </div>
        </div>

        <div class="content-panel mb-4">
            <h2>Email & SMTP</h2>
            <div class="row g-3">
                <div class="col-md-6 form-check ms-2"><input class="form-check-input" type="checkbox" name="email_service_enabled" value="1" <?= $setting('email_service_enabled', '0') === '1' ? 'checked' : '' ?>><label class="form-check-label">Email service ON</label></div>
                <div class="col-md-6 form-check"><input class="form-check-input" type="checkbox" name="email_required" value="1" <?= $setting('email_required', '0') === '1' ? 'checked' : '' ?>><label class="form-check-label">Require email address</label></div>
                <div class="col-md-6 form-check ms-2"><input class="form-check-input" type="checkbox" name="email_verification_enabled" value="1" <?= $setting('email_verification_enabled', '0') === '1' ? 'checked' : '' ?>><label class="form-check-label">Enable email verification</label></div>
                <div class="col-md-6 form-check"><input class="form-check-input" type="checkbox" name="password_reset_email_enabled" value="1" <?= $setting('password_reset_email_enabled', '0') === '1' ? 'checked' : '' ?>><label class="form-check-label">Enable password reset by email</label></div>
                <div class="col-md-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="system_email_notifications_enabled" value="1" <?= $setting('system_email_notifications_enabled', '0') === '1' ? 'checked' : '' ?>><label class="form-check-label">Enable system email notifications</label></div>
                <div class="col-md-4"><label class="form-label">Email provider</label><input class="form-control" name="email_provider" value="<?= e($setting('email_provider', '')) ?>"></div>
                <div class="col-md-8"><label class="form-label">SMTP host</label><input class="form-control" name="smtp_host" value="<?= e($setting('smtp_host', '')) ?>"></div>
                <div class="col-md-3"><label class="form-label">SMTP port</label><input class="form-control" name="smtp_port" value="<?= e($setting('smtp_port', '587')) ?>"></div>
                <div class="col-md-3"><label class="form-label">SMTP encryption</label><input class="form-control" name="smtp_encryption" value="<?= e($setting('smtp_encryption', 'tls')) ?>"></div>
                <div class="col-md-6"><label class="form-label">SMTP username</label><input class="form-control" name="smtp_username" value="<?= e($setting('smtp_username', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">SMTP password</label><input class="form-control" type="password" name="smtp_password" value="" placeholder="Leave blank to keep existing password"></div>
                <div class="col-md-6"><label class="form-label">From email</label><input class="form-control" name="smtp_from_email" value="<?= e($setting('smtp_from_email', '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">From name</label><input class="form-control" name="smtp_from_name" value="<?= e($setting('smtp_from_name', 'Gadget 50')) ?>"></div>
                <div class="col-md-12"><label class="form-label">Admin alert email</label><input class="form-control" name="admin_alert_email" value="<?= e($setting('admin_alert_email', '')) ?>"></div>
            </div>
        </div>

        <div class="content-panel mb-4">
            <h2>Security & media</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Allowed file types</label><input class="form-control" name="allowed_file_types" value="<?= e($setting('allowed_file_types', 'jpg,jpeg,png,gif,webp')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Max upload size</label><input class="form-control" name="max_upload_size" value="<?= e($setting('max_upload_size', '5242880')) ?>"></div>
                <div class="col-md-12"><label class="form-label">Site robots</label><input class="form-control" name="robots_meta" value="<?= e($setting('robots_meta', 'index,follow')) ?>"></div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Save settings</button>
    </form>
</div>
</body>
</html>
