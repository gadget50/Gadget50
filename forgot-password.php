<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (!emailServiceAvailable() || getSetting('password_reset_email_enabled', '0') !== '1') {
        setFlash('danger', 'Password reset by email is currently disabled.');
        redirect('forgot-password.php');
    }

    $identity = trim((string) ($_POST['identity'] ?? ''));
    $user = null;

    if ($identity !== '') {
        $stmt = Database::getInstance()->prepare('SELECT * FROM users WHERE username = :identity OR email = :email LIMIT 1');
        $stmt->execute([':identity' => $identity, ':email' => $identity]);
        $user = $stmt->fetch();
    }

    if ($user) {
        sendPasswordResetEmail($user);
    }

    setFlash('success', 'If that account exists, a password reset email has been sent.');
    redirect('forgot-password.php');
}

$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="content-panel">
                <h1>Forgot Password</h1>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <label class="form-label">Username or email</label>
                    <input class="form-control mb-3" name="identity" required>
                    <button class="btn btn-primary w-100" type="submit">Send reset link</button>
                </form>
                <p class="mt-3 mb-0"><a href="login.php">Back to login</a></p>
            </div>
        </div>
    </div>
</main>
</body>
</html>
