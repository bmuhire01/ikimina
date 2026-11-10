<?php
// change_password.php - lets the logged-in user change their own password.
require_once 'auth.php';
require_login();

$page_title = 'Change password';
$active = 'password';

$user_id = (int) $_SESSION['user_id'];
$errors = array();

// Load the logged-in user so we can check the current password.
$result = mysqli_query($conn, "SELECT username, password, role FROM users WHERE id = $user_id LIMIT 1");
$user = $result ? mysqli_fetch_assoc($result) : null;

if (!$user) {
    set_flash('error', t('Your account could not be found. Please log in again.'));
    header('Location: logout.php');
    exit;
}

// Warn while the well-known default password is still in use.
$using_default = password_verify('admin123', $user['password']);

// Handle the form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // The current password must match the stored hash.
    if ($current === '') {
        $errors[] = t('Please enter your current password.');
    } elseif (!password_verify($current, $user['password'])) {
        $errors[] = t('Your current password is not correct.');
    }

    // Check the new password.
    if ($password === '') {
        $errors[] = t('Please enter a new password.');
    } elseif (strlen($password) < 6) {
        $errors[] = t('Password must be at least 6 characters.');
    }

    if ($password !== $confirm) {
        $errors[] = t('The two new passwords do not match.');
    }

    // Reusing the same password would not improve anything.
    if (count($errors) === 0 && password_verify($password, $user['password'])) {
        $errors[] = t('The new password must be different from the current password.');
    }

    // Save the new hash when everything is valid.
    if (count($errors) === 0) {
        $hash = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_DEFAULT));
        $sql = "UPDATE users SET password = '$hash' WHERE id = $user_id LIMIT 1";

        if (mysqli_query($conn, $sql)) {
            set_flash('success', t('Your password was changed.'));
            header('Location: index.php');
            exit;
        }

        $errors[] = t('Could not save the new password: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Change password')); ?></h1>
        <p class="muted">
            <?php echo h(t('Signed in as %s', $user['username'])); ?>
            &middot; <?php echo h(t($user['role'] === 'admin' ? 'Secretary (admin)' : 'Member')); ?>
        </p>
    </div>
    <a class="btn btn-secondary" href="index.php"><?php echo h(t('Back to dashboard')); ?></a>
</div>

<?php if ($using_default): ?>
    <div class="alert error">
        <?php echo h(t('This account still uses the default password admin123. Choose a new password now so nobody else can sign in as you.')); ?>
    </div>
<?php endif; ?>

<div class="card form-card">
    <?php if (count($errors) > 0): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="change_password.php">
        <div class="form-group">
            <label for="current_password"><?php echo h(t('Current password')); ?></label>
            <input type="password" id="current_password" name="current_password" required
                   autocomplete="current-password">
        </div>

        <div class="form-group">
            <label for="password"><?php echo h(t('New password')); ?></label>
            <input type="password" id="password" name="password" required
                   autocomplete="new-password">
            <div class="hint"><?php echo h(t('At least 6 characters.')); ?></div>
        </div>

        <div class="form-group">
            <label for="confirm_password"><?php echo h(t('Confirm new password')); ?></label>
            <input type="password" id="confirm_password" name="confirm_password" required
                   autocomplete="new-password">
        </div>

        <div class="form-actions">
            <button class="btn" type="submit"><?php echo h(t('Change password')); ?></button>
            <a class="btn btn-secondary" href="index.php"><?php echo h(t('Cancel')); ?></a>
        </div>
    </form>
</div>
<?php require 'footer.php'; ?>
