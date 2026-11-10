<?php
// edit_member.php - secretary-only form to update a member.
require_once 'auth.php';
require_admin();

$page_title = 'Edit member';
$active = 'members';

// Read the member id from the link or the submitted form.
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
}

if ($id < 1) {
    set_flash('error', t('Member not found.'));
    header('Location: members.php');
    exit;
}

// Load the member from the database.
$result = mysqli_query($conn, "SELECT id, name, phone, join_date, status, member_type
                               FROM members
                               WHERE id = $id
                               LIMIT 1");
$member = $result ? mysqli_fetch_assoc($result) : null;

if (!$member) {
    set_flash('error', t('Member not found.'));
    header('Location: members.php');
    exit;
}

// Children are edited from their family page.
if ($member['member_type'] === 'child') {
    header('Location: family_member_edit.php?id=' . $id);
    exit;
}

// Check whether this member already has a login.
$result = mysqli_query($conn, "SELECT username
                               FROM users
                               WHERE member_id = $id AND role = 'member'
                               ORDER BY id LIMIT 1");
$login = $result ? mysqli_fetch_assoc($result) : null;

$errors = array();
$name = $member['name'];
$phone = $member['phone'];
$join_date = $member['join_date'];
$status = $member['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $join_date = trim($_POST['join_date'] ?? '');
    $status = $_POST['status'] ?? 'active';

    // Check the values first.
    if ($name === '') {
        $errors[] = t('Name is required.');
    } elseif (mb_strlen($name) > 100) {
        $errors[] = t('Name must be 100 characters or fewer.');
    }

    if ($phone === '') {
        $errors[] = t('Phone number is required.');
    } elseif (!valid_phone($phone)) {
        $errors[] = t('Phone must be 7-20 digits (example: 0788123456).');
    }

    if (!valid_date($join_date)) {
        $errors[] = t('Please choose a valid join date.');
    }

    if ($status !== 'active' && $status !== 'inactive') {
        $errors[] = t('Please choose a valid status.');
    }

    // Escape the values before using them in SQL.
    $name_esc = mysqli_real_escape_string($conn, $name);
    $phone_esc = mysqli_real_escape_string($conn, $phone);
    $date_esc = mysqli_real_escape_string($conn, $join_date);
    $status_esc = mysqli_real_escape_string($conn, $status);

    // The phone may stay the same, but not belong to another member.
    if (count($errors) === 0) {
        $check = mysqli_query($conn, "SELECT id FROM members
                                      WHERE phone = '$phone_esc' AND id != $id
                                      LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $errors[] = t('Another member already uses this phone number.');
        }
    }

    // Update when everything is valid.
    if (count($errors) === 0) {
        $sql = "UPDATE members
                SET name = '$name_esc',
                    phone = '$phone_esc',
                    join_date = '$date_esc',
                    status = '$status_esc'
                WHERE id = $id LIMIT 1";

        if (mysqli_query($conn, $sql)) {
            set_flash('success', t('Member "%s" was updated.', $name));
            header('Location: members.php');
            exit;
        }

        $errors[] = t('Could not update the member: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Edit member')); ?></h1>
        <p class="muted"><?php echo h(t('Update the details for %s.', $member['name'])); ?></p>
    </div>
    <a class="btn btn-secondary" href="members.php"><?php echo h(t('Back to members')); ?></a>
</div>

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

    <form method="post" action="edit_member.php?id=<?php echo $id; ?>">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="form-group">
            <label for="name"><?php echo h(t('Full name')); ?></label>
            <input type="text" id="name" name="name" maxlength="100" required
                   value="<?php echo h($name); ?>">
        </div>

        <div class="form-group">
            <label for="phone"><?php echo h(t('Phone number')); ?></label>
            <input type="text" id="phone" name="phone" maxlength="20" required
                   value="<?php echo h($phone); ?>">
            <div class="hint"><?php echo h(t('Example: 0788123456')); ?></div>
        </div>

        <div class="form-group">
            <label for="join_date"><?php echo h(t('Join date')); ?></label>
            <input type="date" id="join_date" name="join_date" required
                   value="<?php echo h($join_date); ?>">
        </div>

        <div class="form-group">
            <label for="status"><?php echo h(t('Status')); ?></label>
            <select id="status" name="status">
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>><?php echo h(t('Active')); ?></option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>><?php echo h(t('Inactive')); ?></option>
            </select>
            <div class="hint"><?php echo h(t('Inactive members cannot log in.')); ?></div>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit"><?php echo h(t('Save changes')); ?></button>
            <a class="btn btn-secondary" href="members.php"><?php echo h(t('Cancel')); ?></a>
        </div>
    </form>
</div>

<div class="card">
    <h2><?php echo h(t('Member login')); ?></h2>
    <?php if ($login): ?>
        <p class="muted">
            <?php echo h(t('Username')); ?>: <strong>@<?php echo h($login['username']); ?></strong>
            <?php if ($status === 'inactive'): ?>
                &middot; <?php echo h(t('this member must be active before the login will work.')); ?>
            <?php endif; ?>
        </p>
        <a class="btn btn-secondary" href="member_login.php?member_id=<?php echo $id; ?>"><?php echo h(t('Manage login')); ?></a>
    <?php else: ?>
        <p class="muted"><?php echo h(t('This member does not have a login yet.')); ?></p>
        <a class="btn" href="member_login.php?member_id=<?php echo $id; ?>"><?php echo h(t('Create login')); ?></a>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
