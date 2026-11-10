<?php
// family_edit.php - secretary-only form to update a family.
require_once 'auth.php';
require_admin();

$page_title = 'Edit family';
$active = 'families';

// Read the family id from the link or the submitted form.
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
}

if ($id < 1) {
    set_flash('error', t('Family not found.'));
    header('Location: families.php');
    exit;
}

// Load the family from the database.
$result = mysqli_query($conn, "SELECT id, name, guardian_name, phone, join_date
                               FROM families
                               WHERE id = $id
                               LIMIT 1");
$family = $result ? mysqli_fetch_assoc($result) : null;

if (!$family) {
    set_flash('error', t('Family not found.'));
    header('Location: families.php');
    exit;
}

$errors = array();
$name = $family['name'];
$guardian_name = $family['guardian_name'];
$phone = $family['phone'] === null ? '' : $family['phone'];
$join_date = $family['join_date'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $join_date = trim($_POST['join_date'] ?? '');

    if ($name === '') {
        $errors[] = t('Family name is required.');
    } elseif (mb_strlen($name) > 100) {
        $errors[] = t('Family name must be 100 characters or fewer.');
    }

    if ($guardian_name === '') {
        $errors[] = t('Parent or guardian name is required.');
    } elseif (mb_strlen($guardian_name) > 100) {
        $errors[] = t('Parent or guardian name must be 100 characters or fewer.');
    }

    if ($phone !== '' && !valid_phone($phone)) {
        $errors[] = t('Phone must be 7-20 digits (example: 0788123456).');
    }

    if (!valid_date($join_date)) {
        $errors[] = t('Please choose a valid registration date.');
    }

    // Update when everything is valid.
    if (count($errors) === 0) {
        $name_esc = mysqli_real_escape_string($conn, $name);
        $guardian_esc = mysqli_real_escape_string($conn, $guardian_name);
        $date_esc = mysqli_real_escape_string($conn, $join_date);
        $phone_sql = $phone === ''
            ? 'NULL'
            : "'" . mysqli_real_escape_string($conn, $phone) . "'";

        $sql = "UPDATE families
                SET name = '$name_esc',
                    guardian_name = '$guardian_esc',
                    phone = $phone_sql,
                    join_date = '$date_esc'
                WHERE id = $id LIMIT 1";

        if (mysqli_query($conn, $sql)) {
            set_flash('success', t('Family "%s" was updated.', $name));
            header('Location: family_view.php?id=' . $id);
            exit;
        }

        $errors[] = t('Could not update the family: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Edit family')); ?></h1>
        <p class="muted"><?php echo h(t('Update the details for %s.', $family['name'])); ?></p>
    </div>
    <a class="btn btn-secondary" href="family_view.php?id=<?php echo $id; ?>"><?php echo h(t('Back to family')); ?></a>
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

    <form method="post" action="family_edit.php?id=<?php echo $id; ?>">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="form-group">
            <label for="name"><?php echo h(t('Family name')); ?></label>
            <input type="text" id="name" name="name" maxlength="100" required
                   value="<?php echo h($name); ?>">
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
        </div>

        <div class="form-group">
            <label for="join_date"><?php echo h(t('Registration date')); ?></label>
            <input type="date" id="join_date" name="join_date" required
                   value="<?php echo h($join_date); ?>">
        </div>

        <div class="form-actions">
            <button class="btn" type="submit"><?php echo h(t('Save changes')); ?></button>
            <a class="btn btn-secondary" href="family_view.php?id=<?php echo $id; ?>"><?php echo h(t('Cancel')); ?></a>
        </div>
    </form>
</div>
<?php require 'footer.php'; ?>
