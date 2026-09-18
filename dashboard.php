<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';
requireLogin('login.php');

$user = currentUser();
if (!$user) {
    redirect('login.php');
}

$pdo = Database::getInstance();
$siteName = getSetting('site_name', defined('APP_NAME') ? APP_NAME : 'Gadget 50');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $enable = (string) ($_POST['enabled'] ?? '') === '1';

    if ($enable && (!emailServiceAvailable() || empty($user['email']))) {
        setFlash('danger', 'Two-factor authentication requires an active email service and an email address.');
        redirect('dashboard.php');
    }
    if ($enable && (int) ($user['email_verified'] ?? 0) !== 1) {
        setFlash('danger', 'Verify your email address before enabling two-factor authentication.');
        redirect('dashboard.php');
    }

    $stmt = $pdo->prepare('UPDATE users SET two_factor_enabled = :enabled WHERE id = :id');
    $stmt->execute([':enabled' => $enable ? 1 : 0, ':id' => (int) $user['id']]);
    setFlash('success', $enable ? 'Two-factor authentication enabled.' : 'Two-factor authentication disabled.');
    redirect('dashboard.php');
}

$stmt = $pdo->prepare(
    'SELECT n.id, n.title, n.status, n.is_anonymous, n.image, n.created_at, c.name AS category_name
     FROM news n LEFT JOIN categories c ON c.id = n.category_id
     WHERE n.author_id = :author ORDER BY n.created_at DESC'
);
$stmt->execute([':author' => (int) $user['id']]);
$mine = $stmt->fetchAll();
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My newsroom | <?= e($siteName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
<div class="container py-4 py-lg-5">
<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
<div><span class="eyebrow">MY NEWSROOM</span><h1><?= e($siteName) ?></h1><p class="text-muted mb-0">Welcome, <?= e((string) ($user['username'] ?? 'User')) ?>.</p></div>
<div class="d-flex gap-2"><a class="btn btn-primary" href="submit.php">Write a story</a><a class="btn btn-outline-secondary" href="index.php">Public site</a></div>
</div>
<?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-8"><div class="content-panel"><div class="panel-heading"><h2>My stories</h2></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Story</th><th>Category</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php if (!$mine): ?><tr><td colspan="4" class="text-center text-muted py-4">You have not submitted a story yet.</td></tr><?php else: foreach ($mine as $item): ?><tr><td><?= e((string) ($item['title'] ?? 'Untitled')) ?></td><td><?= e((string) ($item['category_name'] ?? 'General')) ?></td><td><span class="status status-<?= e((string) ($item['status'] ?? 'draft')) ?>"><?= e(ucfirst((string) ($item['status'] ?? 'draft'))) ?></span></td><td><?= e(date('M d, Y', strtotime((string) ($item['created_at'] ?? 'now')))) ?></td></tr><?php endforeach; endif; ?>
</tbody></table></div></div></div><div class="col-lg-4"><div class="sidebar-box"><h3>Account security</h3><p class="text-muted mb-2">Email: <?= e((string) ($user['email'] ?? 'Not provided')) ?></p><p class="mb-3">Email verification: <strong><?= !empty($user['email']) && (int) ($user['email_verified'] ?? 0) === 1 ? 'Verified' : 'Not required / pending' ?></strong></p><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="enabled" value="<?= (int) ($user['two_factor_enabled'] ?? 0) === 1 ? '0' : '1' ?>"><button class="btn btn-outline-primary" type="submit"><?= (int) ($user['two_factor_enabled'] ?? 0) === 1 ? 'Disable 2FA' : 'Enable 2FA' ?></button></form></div></div></div>
</div></body></html>
