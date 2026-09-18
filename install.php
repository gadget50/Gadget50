<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (is_file(__DIR__ . '/install.lock')) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';

$status = '';
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $dbHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $dbName = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPass = (string) ($_POST['db_password'] ?? '');
    $adminUsername = trim((string) ($_POST['admin_username'] ?? ''));
    $adminEmail = strtolower(trim((string) ($_POST['admin_email'] ?? '')));
    $adminPassword = (string) ($_POST['admin_password'] ?? '');

    $validUsername = preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $adminUsername) === 1;
    if ($dbHost === '' || $dbName === '' || $dbUser === '' || !$validUsername || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 8) {
        $status = 'Complete all fields, use a valid username/email, and choose a password of at least 8 characters.';
    } else {
        try {
            $pdo = new PDO(
                'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                ]
            );

            $schemaPath = __DIR__ . '/database/schema.sql';
            $schema = file_get_contents($schemaPath);
            if ($schema === false) {
                throw new RuntimeException('Unable to load the schema file.');
            }

            $statements = preg_split('/;\s*(?=\s*(?:CREATE|ALTER|INSERT|UPDATE|DELETE|DROP|TRUNCATE|SET|BEGIN|COMMIT))/', $schema);
            if ($statements === false) {
                $statements = [$schema];
            }

            foreach ($statements as $index => $statement) {
                $statement = trim((string) $statement);
                if ($statement === '') {
                    continue;
                }
                if (preg_match('/^--/', $statement) === 1) {
                    continue;
                }
                try {
                    $pdo->exec($statement . ';');
                } catch (Throwable $e) {
                    throw new RuntimeException('Schema statement ' . ($index + 1) . ' failed: ' . $e->getMessage(), 0, $e);
                }
            }

            $stmt = $pdo->prepare(
                'SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1'
            );
            $stmt->execute([
                ':username' => $adminUsername,
                ':email' => $adminEmail,
            ]);
            if ($stmt->fetch() === false) {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, email, email_verified, password_hash, role, status, created_at)
                     VALUES (:username, :email, 1, :password_hash, :role, :status, NOW())'
                );
                $stmt->execute([
                    ':username' => $adminUsername,
                    ':email' => $adminEmail,
                    ':password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
                    ':role' => 'super_admin',
                    ':status' => 'active',
                ]);
            }

            $config = "<?php\ndeclare(strict_types=1);\n\ndefine('DB_HOST', " . var_export($dbHost, true) . ");\ndefine('DB_NAME', " . var_export($dbName, true) . ");\ndefine('DB_USER', " . var_export($dbUser, true) . ");\ndefine('DB_PASS', " . var_export($dbPass, true) . ");\ndefine('APP_NAME', 'Gadget 50');\ndefine('APP_DEBUG', false);\n";
            $configPath = __DIR__ . '/config.php';
            $lockPath = __DIR__ . '/install.lock';
            $tempConfig = tempnam(__DIR__, 'gadget-config-');
            if ($tempConfig === false || file_put_contents($tempConfig, $config, LOCK_EX) === false || !rename($tempConfig, $configPath)) {
                if ($tempConfig !== false && is_file($tempConfig)) {
                    @unlink($tempConfig);
                }
                throw new RuntimeException('Could not write config.php. Check file permissions.');
            }
            if (file_put_contents($lockPath, date('c'), LOCK_EX) === false) {
                throw new RuntimeException('Could not create install.lock. Check file permissions.');
            }

            header('Location: index.php?installed=1');
            exit;
        } catch (Throwable $e) {
            error_log('Installation failed: ' . $e->getMessage());
            $status = 'Installation failed. Verify the database host, database name, username, password, and hosting database permissions. Check the server error log for details.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gadget 50 Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white"><h2 class="mb-0">Gadget 50 Installation Wizard</h2></div>
                <div class="card-body p-4">
                    <?php if ($status !== ''): ?>
                        <div class="alert alert-danger"><?= e($status) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h4>Database Configuration</h4>
                                <label class="form-label">MySQL Host</label>
                                <input class="form-control mb-3" name="db_host" value="<?= e((string) ($old['db_host'] ?? 'localhost')) ?>" required>
                                <label class="form-label">Database Name</label>
                                <input class="form-control mb-3" name="db_name" value="<?= e((string) ($old['db_name'] ?? '')) ?>" required>
                                <label class="form-label">Database User</label>
                                <input class="form-control mb-3" name="db_user" value="<?= e((string) ($old['db_user'] ?? '')) ?>" required>
                                <label class="form-label">Database Password</label>
                                <input class="form-control" type="password" name="db_password">
                            </div>
                            <div class="col-md-6">
                                <h4>Super Admin Account</h4>
                                <label class="form-label">Username</label>
                                <input class="form-control mb-3" name="admin_username" value="<?= e((string) ($old['admin_username'] ?? '')) ?>" required>
                                <label class="form-label">Email</label>
                                <input class="form-control mb-3" type="email" name="admin_email" value="<?= e((string) ($old['admin_email'] ?? '')) ?>" required>
                                <label class="form-label">Password</label>
                                <input class="form-control" type="password" name="admin_password" minlength="8" required>
                            </div>
                        </div>
                        <button class="btn btn-primary mt-4" type="submit">Install Gadget 50</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
