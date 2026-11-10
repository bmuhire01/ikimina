<?php
// family_add.php - secretary-only form to register a family.
require_once 'auth.php';
require_admin();

$page_title = 'Register family';
$active = 'families';

$errors = array();
$name = '';
$guardian_name = '';
$phone = '';
$join_date = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $join_date = trim($_POST['join_date'] ?? '');

    // Check the family name.
    if ($name === '') {
        $errors[] = t('Family name is required.');
    } elseif (mb_strlen($name) > 100) {
        $errors[] = t('Family name must be 100 characters or fewer.');
    }

    // Check the parent or guardian name.
    if ($guardian_name === '') {
        $errors[] = t('Parent or guardian name is required.');
    } elseif (mb_strlen($guardian_name) > 100) {
        $errors[] = t('Parent or guardian name must be 100 characters or fewer.');
    }

    // The phone number is optional, but must be valid when given.
    if ($phone !== '' && !valid_phone($phone)) {
        $errors[] = t('Phone must be 7-20 digits (example: 0788123456).');
    }

    if (!valid_date($join_date)) {
        $errors[] = t('Please choose a valid registration date.');
    }

    // Save when everything is valid.
    if (count($errors) === 0) {
        $name_esc = mysqli_real_escape_string($conn, $name);
        $guardian_esc = mysqli_real_escape_string($conn, $guardian_name);
        $date_esc = mysqli_real_escape_string($conn, $join_date);
        $phone_sql = $phone === ''
            ? 'NULL'
            : "'" . mysqli_real_escape_string($conn, $phone) . "'";

        $sql = "INSERT INTO families (name, guardian_name, phone, join_date)
                VALUES ('$name_esc', '$guardian_esc', $phone_sql, '$date_esc')";

        if (mysqli_query($conn, $sql)) {
            $family_id = (int) mysqli_insert_id($conn);
            set_flash('success', t('Family "%s" was registered.', $name));
            header('Location: family_view.php?id=' . $family_id);
            exit;
        }

        $errors[] = t('Could not save the family: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Register family')); ?></h1>
        <p class="muted"><?php echo h(t('Only the secretary can register families.')); ?></p>
    </div>
    <a class="btn btn-secondary" href="families.php"><?php echo h(t('Back to families')); ?></a>
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

    <form method="post" action="family_add.php">
        <div class="form-group">
            <label for="name"><?php echo h(t('Family name')); ?></label>
            <input type="text" id="name" name="name" maxlength="100" required
                   value="<?php echo h($name); ?>">
            <div class="hint"><?php echo h(t('Example: Uwimana family')); ?></div>
        </div>

        <div class="form-group">
            <label for="guardian_name"><?php echo h(t('Parent or guardian')); ?></label>
            <input type="text" id="guardian_name" name="guardian_name" maxlength="100" required
                   value="<?php echo h($guardian_name); ?>">
        </div>

        <div class="form-group">
            <label for="phone"><?php echo h(t('Parent or guardian phone (optional)')); ?></label>
            <input type="text" id="phone" name="phone" maxlength="20"
                   value="<?php echo h($phone); ?>">
            <div class="hint"><?php echo h(t('Phone number is optional.')); ?></div>
        </div>

        <div class="form-group">
            <label for="join_date"><?php echo h(t('Registration date')); ?></label>
            <input type="date" id="join_date" name="join_date" required
                   value="<?php echo h($join_date); ?>">
        </div>

        <div class="form-actions">
            <button class="btn" type="submit"><?php echo h(t('Save family')); ?></button>
            <a class="btn btn-secondary" href="families.php"><?php echo h(t('Cancel')); ?></a>
        </div>
    </form>

    <p class="hint" style="margin-top:16px;">
        <?php echo h(t('After saving, add the family members who save together.')); ?>
    </p>
</div>
<?php require 'footer.php'; ?>
