<?php
// add_member.php - secretary-only form to add a member.
require_once 'auth.php';
require_admin();

$page_title = 'Add member';
$active = 'members';

$errors = array();
$name = '';
$phone = '';
$join_date = date('Y-m-d');
$status = 'active';

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

    // Phone numbers identify members, so do not allow duplicates.
    if (count($errors) === 0) {
        $check = mysqli_query($conn, "SELECT id FROM members WHERE phone = '$phone_esc' LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $errors[] = t('Another member already uses this phone number.');
        }
    }

    // Save when everything is valid.
    if (count($errors) === 0) {
        $sql = "INSERT INTO members (name, phone, join_date, status)
                VALUES ('$name_esc', '$phone_esc', '$date_esc', '$status_esc')";

        if (mysqli_query($conn, $sql)) {
            set_flash('success', t('Member "%s" was added.', $name));
            header('Location: members.php');
            exit;
        }

        $errors[] = t('Could not save the member: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Add member')); ?></h1>
        <p class="muted"><?php echo h(t('Only the secretary can add members.')); ?></p>
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

    <form method="post" action="add_member.php">
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
            <button class="btn" type="submit"><?php echo h(t('Save member')); ?></button>
            <a class="btn btn-secondary" href="members.php"><?php echo h(t('Cancel')); ?></a>
        </div>
    </form>

    <p class="hint" style="margin-top:16px;">
        <?php echo h(t('After saving, use the Create login button on the members list to give this member a view-only account.')); ?>
    </p>
    <p class="hint">
        <?php echo h(t('To save for a child, register a family and add the child there.')); ?>
        <a href="families.php"><?php echo h(t('Families')); ?></a>
    </p>
</div>
<?php require 'footer.php'; ?>
