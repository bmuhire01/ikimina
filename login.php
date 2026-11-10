<?php
// login.php - logs the secretary (admin) or a member into the app.
require_once 'auth.php';

// Already logged in? Go straight to the dashboard.
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$settings = get_setting();
$group_name = $settings['group_name'];
$error = '';
$username = '';

// Handle the login form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // checked with password_verify(), not SQL

    // Escape the username before using it in the query.
    $username_esc = mysqli_real_escape_string($conn, $username);

    // Look for the user and the status of their member record.
    $result = mysqli_query($conn, "SELECT u.id, u.username, u.password, u.role, u.member_id,
                                          m.status AS member_status,
                                          m.member_type AS member_type
                                   FROM users u
                                   LEFT JOIN members m ON m.id = u.member_id
                                   WHERE u.username = '$username_esc'
                                   LIMIT 1");
    $user = $result ? mysqli_fetch_assoc($result) : null;

    // Check the password against the stored hash.
    if ($user && password_verify($password, $user['password'])) {
        // Inactive members and children cannot log in.
        if ($user['role'] === 'member' && $user['member_status'] !== 'active') {
            $error = t('Your account is inactive. Please contact the secretary.');
        } elseif ($user['role'] === 'member' && $user['member_type'] === 'child') {
            $error = t('Children do not have login accounts.');
        } else {
            session_regenerate_id(true); // new session id after login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['member_id'] = $user['member_id'] !== null ? (int) $user['member_id'] : 0;

            header('Location: index.php');
            exit;
        }
    } else {
        // Same message for a wrong username or a wrong password.
        $error = t('Wrong username or password.');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo h(current_lang()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#14532d">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Ikimina">
    <title><?php echo h(t('Login - %s', $group_name)); ?></title>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href="icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-lang">
        <a href="?lang=en" class="<?php echo current_lang() === 'en' ? 'active' : ''; ?>"
           <?php echo current_lang() === 'en' ? 'aria-current="true"' : ''; ?>
           title="<?php echo h(t('English')); ?>">EN</a>
        <a href="?lang=rw" class="<?php echo current_lang() === 'rw' ? 'active' : ''; ?>"
           <?php echo current_lang() === 'rw' ? 'aria-current="true"' : ''; ?>
           title="<?php echo h(t('Kinyarwanda')); ?>">RW</a>
    </div>

    <div class="card auth-card">
        <div class="auth-brand">
            <div class="auth-logo">IK</div>
            <h1>Ikimina</h1>
            <p><?php echo h($group_name); ?></p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username"><?php echo h(t('Username')); ?></label>
                <input type="text" id="username" name="username" required autofocus
                       autocomplete="username" value="<?php echo h($username); ?>">
            </div>

            <div class="form-group">
                <label for="password"><?php echo h(t('Password')); ?></label>
                <input type="password" id="password" name="password" required
                       autocomplete="current-password">
            </div>

            <button class="btn btn-block" type="submit"><?php echo h(t('Login')); ?></button>
        </form>

        <p class="auth-footer">
            <?php echo h($group_name); ?> &mdash; <?php echo h(t('Contact the secretary if you need an account.')); ?>
        </p>
    </div>
</body>
</html>
