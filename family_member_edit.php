<?php
// family_member_edit.php - secretary-only form to update a family member.
require_once 'auth.php';
require_admin();

$page_title = 'Edit family member';
$active = 'families';

// Read the member id from the link or the submitted form.
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
}

if ($id < 1) {
    set_flash('error', t('Family member not found.'));
    header('Location: families.php');
    exit;
}

// Load the member and the family name.
$result = mysqli_query($conn, "SELECT m.id, m.name, m.phone, m.join_date, m.date_of_birth,
                                      m.member_type, m.status, m.family_id,
                                      f.name AS family_name
                               FROM members m
                               LEFT JOIN families f ON f.id = m.family_id
                               WHERE m.id = $id
                               LIMIT 1");
$member = $result ? mysqli_fetch_assoc($result) : null;

if (!$member) {
    set_flash('error', t('Family member not found.'));
    header('Location: families.php');
    exit;
}

// Adult members without a family use the normal member form.
if ((int) $member['family_id'] < 1) {
    set_flash('error', t('Use the member form to edit this person.'));
    header('Location: edit_member.php?id=' . $id);
    exit;
}

$family_id = (int) $member['family_id'];
$errors = array();
$name = $member['name'];
$member_type = $member['member_type'];
$phone = $member['phone'] === null ? '' : $member['phone'];
$date_of_birth = $member['date_of_birth'] === null ? '' : $member['date_of_birth'];
$join_date = $member['join_date'];
$status = $member['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $member_type = $_POST['member_type'] ?? 'child';
    $phone = trim($_POST['phone'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $join_date = trim($_POST['join_date'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($name === '') {
        $errors[] = t('Name is required.');
    } elseif (mb_strlen($name) > 100) {
        $errors[] = t('Name must be 100 characters or fewer.');
    }

    if ($member_type !== 'adult' && $member_type !== 'child') {
        $errors[] = t('Please choose a valid member type.');
    }

    if ($member_type === 'adult' && $phone === '') {
        $errors[] = t('Phone number is required for an adult.');
    } elseif ($phone !== '' && !valid_phone($phone)) {
        $errors[] = t('Phone must be 7-20 digits (example: 0788123456).');
    }

    if ($date_of_birth !== '') {
        if (!valid_date($date_of_birth)) {
            $errors[] = t('Please choose a valid date of birth.');
        } elseif ($date_of_birth > date('Y-m-d')) {
            $errors[] = t('Date of birth cannot be in the future.');
        }
    }

    if (!valid_date($join_date)) {
        $errors[] = t('Please choose a valid join date.');
    }

    if ($status !== 'active' && $status !== 'inactive') {
        $errors[] = t('Please choose a valid status.');
    }

    // Update when everything is valid.
    if (count($errors) === 0) {
        $name_esc = mysqli_real_escape_string($conn, $name);
        $type_esc = mysqli_real_escape_string($conn, $member_type);
        $date_esc = mysqli_real_escape_string($conn, $join_date);
        $status_esc = mysqli_real_escape_string($conn, $status);
        $phone_sql = $phone === ''
            ? 'NULL'
            : "'" . mysqli_real_escape_string($conn, $phone) . "'";
        $birth_sql = $date_of_birth === ''
            ? 'NULL'
            : "'" . mysqli_real_escape_string($conn, $date_of_birth) . "'";

        $sql = "UPDATE members
                SET name = '$name_esc',
                    phone = $phone_sql,
                    member_type = '$type_esc',
                    date_of_birth = $birth_sql,
                    join_date = '$date_esc',
                    status = '$status_esc'
                WHERE id = $id AND family_id = $family_id LIMIT 1";

        if (mysqli_query($conn, $sql)) {
            set_flash('success', t('Family member "%s" was updated.', $name));
            header('Location: family_view.php?id=' . $family_id);
            exit;
        }

        $errors[] = t('Could not update the family member: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Edit family member')); ?></h1>
        <p class="muted">
            <?php echo h(t('Update the details for %s.', $member['name'])); ?>
            &middot; <?php echo h($member['family_name']); ?>
        </p>
    </div>
    <a class="btn btn-secondary" href="family_view.php?id=<?php echo $family_id; ?>"><?php echo h(t('Back to family')); ?></a>
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

    <form method="post" action="family_member_edit.php?id=<?php echo $id; ?>">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="form-group">
            <label for="name"><?php echo h(t('Full name')); ?></label>
            <input type="text" id="name" name="name" maxlength="100" required
                   value="<?php echo h($name); ?>">
        </div>

        <div class="form-group">
            <label for="member_type"><?php echo h(t('Member type')); ?></label>
            <select id="member_type" name="member_type">
                <option value="child" <?php echo $member_type === 'child' ? 'selected' : ''; ?>><?php echo h(t('Child')); ?></option>
                <option value="adult" <?php echo $member_type === 'adult' ? 'selected' : ''; ?>><?php echo h(t('Adult')); ?></option>
            </select>
        </div>

        <div class="form-group">
            <label for="phone"><?php echo h(t('Phone number')); ?></label>
            <input type="text" id="phone" name="phone" maxlength="20"
                   value="<?php echo h($phone); ?>">
            <div class="hint"><?php echo h(t('Adults need a phone number. It is optional for children.')); ?></div>
        </div>

        <div class="form-group">
            <label for="date_of_birth"><?php echo h(t('Date of birth (optional)')); ?></label>
            <input type="date" id="date_of_birth" name="date_of_birth"
                   max="<?php echo date('Y-m-d'); ?>" value="<?php echo h($date_of_birth); ?>">
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
        </div>

        <div class="form-actions">
            <button class="btn" type="submit"><?php echo h(t('Save changes')); ?></button>
            <a class="btn btn-secondary" href="family_view.php?id=<?php echo $family_id; ?>"><?php echo h(t('Cancel')); ?></a>
        </div>
    </form>
</div>
<?php require 'footer.php'; ?>
