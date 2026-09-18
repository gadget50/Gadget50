<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$emailServiceEnabled = emailServiceAvailable();
$emailRequired = $emailServiceEnabled && getSetting('email_required', '0') === '1';
$emailVerificationEnabled = $emailServiceEnabled && getSetting('email_verification_enabled', '0') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $validEmail = $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    if (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username) || !$validEmail || ($emailRequired && $email === '') || strlen($password) < 12) {
        setFlash('danger', $emailRequired
            ? 'Enter a valid email address and a password of at least 12 characters.'
            : 'Use a valid username, an optional valid email address, and a password of at least 12 characters.');
        redirect('register.php');
    }

    $pdo = Database::getInstance();
    $sql = $email === ''
        ? 'SELECT id FROM users WHERE username = :username LIMIT 1'
        : 'SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1';
    $check = $pdo->prepare($sql);
    $params = [':username' => $username];
    if ($email !== '') {
        $params[':email'] = $email;
    }
    $check->execute($params);
    if ($check->fetch()) {
        setFlash('danger', 'Username or email is already in use.');
        redirect('register.php');
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, email_verified, password_hash, role, status, created_at)
             VALUES (:username, :email, :verified, :password_hash, :role, :status, NOW())'
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email === '' ? null : $email,
            ':verified' => $emailVerificationEnabled && $email !== '' ? 0 : 1,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => 'member',
            ':status' => 'active',
        ]);
        $user = ['id' => (int) $pdo->lastInsertId(), 'username' => $username, 'email' => $email];
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Registration failed: ' . $e->getMessage());
        setFlash('danger', 'Registration could not be completed. Please try again.');
        redirect('register.php');
    }

    if ($emailVerificationEnabled && $email !== '') {
        $sent = sendUserEmailVerification($user);
        if (getSetting('system_email_notifications_enabled', '0') === '1') {
            sendAdminNewUserEmail($user);
        }
        setFlash($sent ? 'success' : 'danger', $sent
            ? 'Registration successful. Please verify your email before logging in.'
            : 'Account created, but the verification email could not be sent.');
    } else {
        setFlash('success', 'Registration successful. You can log in now.');
    }
    redirect('login.php');
}

$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="container py-5"><div class="row justify-content-center"><div class="col-md-7 col-lg-5"><div class="content-panel">
<h1>Register</h1>
<?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
<p class="text-muted">Email is <?= $emailRequired ? 'required because the administrator enabled it.' : 'optional while Email Service is disabled or optional.' ?></p>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<label class="form-label">Username</label><input class="form-control mb-3" name="username" required>
<label class="form-label">Email<?= $emailRequired ? '' : ' (optional)' ?></label><input class="form-control mb-3" type="email" name="email" <?= $emailRequired ? 'required' : '' ?>>
<label class="form-label">Password</label><input class="form-control mb-3" type="password" name="password" minlength="12" required>
<button class="btn btn-primary w-100" type="submit">Create account</button></form>
<p class="mt-3 mb-0"><a href="login.php">Already have an account?</a></p>
</div></div></div></main>
</body></html>
