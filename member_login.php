<?php
// member_login.php - secretary creates or updates a member's login account.
require_once 'auth.php';
require_admin();

$page_title = 'Member login';
$active = 'members';

$member_id = isset($_GET['member_id']) ? (int) $_GET['member_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = isset($_POST['member_id']) ? (int) $_POST['member_id'] : 0;
}

if ($member_id < 1) {
    set_flash('error', t('Member not found.'));
    header('Location: members.php');
    exit;
}

// Load the member.
$result = mysqli_query($conn, "SELECT id, name, status, member_type FROM members WHERE id = $member_id LIMIT 1");
$member = $result ? mysqli_fetch_assoc($result) : null;

if (!$member) {
    set_flash('error', t('Member not found.'));
    header('Location: members.php');
    exit;
}

// Children do not get login accounts.
if ($member['member_type'] === 'child') {
    set_flash('error', t('Children do not have login accounts.'));
    header('Location: members.php');
    exit;
}

// Check for an existing member login.
$result = mysqli_query($conn, "SELECT id, username
                               FROM users
                               WHERE member_id = $member_id AND role = 'member'
                               ORDER BY id LIMIT 1");
$login = $result ? mysqli_fetch_assoc($result) : null;
$login_id = $login ? (int) $login['id'] : 0;

$errors = array();
$username = $login ? $login['username'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Check the username.
    if ($username === '') {
        $errors[] = t('Username is required.');
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors[] = t('Username must be 3-50 letters, numbers or underscores.');
    } else {
        $username_esc = mysqli_real_escape_string($conn, $username);
        $check = mysqli_query($conn, "SELECT id FROM users
                                      WHERE username = '$username_esc' AND id != $login_id
                                      LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $errors[] = t('That username is already used by another account.');
        }
    }

    // A new login needs a password; an existing login may keep its password.
    if ($login === null && $password === '') {
        $errors[] = t('A password is required for a new login.');
    }

    if ($password !== '') {
        if (strlen($password) < 6) {
            $errors[] = t('Password must be at least 6 characters.');
        }
        if ($password !== $confirm) {
            $errors[] = t('The two passwords do not match.');
        }
    } elseif ($confirm !== '') {
        $errors[] = t('Please enter the new password in both fields.');
    }

    if (count($errors) === 0) {
        $username_esc = mysqli_real_escape_string($conn, $username);

        if ($login) {
            // Update the existing login. Keep the old hash if no new password was typed.
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users
                        SET username = '$username_esc', password = '$hash'
                        WHERE id = $login_id LIMIT 1";
            } else {
                $sql = "UPDATE users
                        SET username = '$username_esc'
                        WHERE id = $login_id LIMIT 1";
            }
            $message = t('Login updated for %s.', $member['name']);
        } else {
            // New member accounts always get the "member" role.
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, role, member_id)
                    VALUES ('$username_esc', '$hash', 'member', $member_id)";
            $message = t('Login created for %s.', $member['name']);
        }

        if (mysqli_query($conn, $sql)) {
            set_flash('success', $message);
            header('Location: members.php');
            exit;
        }

        $errors[] = t('Could not save the login: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t($login ? 'Manage login' : 'Create login')); ?></h1>
        <p class="muted">
            <?php echo h(t('For %s', $member['name'])); ?>
            &middot; <span class="badge <?php echo $member['status'] === 'active' ? '' : 'inactive'; ?>">
                <?php echo h(t($member['status'] === 'active' ? 'Active' : 'Inactive')); ?>
            </span>
        </p>
    </div>
    <a class="btn btn-secondary" href="members.php"><?php echo h(t('Back to members')); ?></a>
</div>

<?php if ($member['status'] === 'inactive'): ?>
    <div class="alert error">
        <?php echo h(t('This member is inactive. The login will not work until the member is set back to active.')); ?>
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

    <form method="post" action="member_login.php?member_id=<?php echo $member_id; ?>">
        <input type="hidden" name="member_id" value="<?php echo $member_id; ?>">

        <div class="form-group">
            <label for="username"><?php echo h(t('Username')); ?></label>
            <input type="text" id="username" name="username" maxlength="50" required
                   autocomplete="off" value="<?php echo h($username); ?>">
            <div class="hint"><?php echo h(t('3-50 letters, numbers or underscores.')); ?></div>
        </div>

        <div class="form-group">
            <label for="password"><?php echo h(t($login ? 'New password' : 'Password')); ?></label>
            <input type="password" id="password" name="password"
                   autocomplete="new-password" <?php echo $login ? '' : 'required'; ?>>
            <div class="hint">
                <?php echo h(t($login
                    ? 'Leave both password fields blank to keep the current password.'
                    : 'At least 6 characters.')); ?>
            </div>
        </div>

        <div class="form-group">
            <label for="confirm_password"><?php echo h(t('Confirm password')); ?></label>
            <input type="password" id="confirm_password" name="confirm_password"
                   autocomplete="new-password" <?php echo $login ? '' : 'required'; ?>>
        </div>

        <div class="hint" style="margin-bottom:14px;">
            <?php echo h(t('This account will have the member role and can only view records.')); ?>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit"><?php echo h(t($login ? 'Save login' : 'Create login')); ?></button>
            <a class="btn btn-secondary" href="members.php"><?php echo h(t('Cancel')); ?></a>
            <a class="btn btn-secondary" href="edit_member.php?id=<?php echo $member_id; ?>"><?php echo h(t('Edit member')); ?></a>
        </div>
    </form>
</div>
<?php require 'footer.php'; ?>
