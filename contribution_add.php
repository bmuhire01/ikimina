<?php
// contribution_add.php - the secretary records one contribution payment.
require_once 'auth.php';
require_admin();

$page_title = 'Record contribution';
$active = 'contributions';

$errors = array();

// Load members for the dropdown, keeping children under their family.
$result = mysqli_query($conn, "SELECT m.id, m.name, m.member_type, m.family_id,
                                      f.name AS family_name
                               FROM members m
                               LEFT JOIN families f ON f.id = m.family_id
                               ORDER BY (m.member_type = 'child'), f.name ASC, m.name ASC");
$members = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

$individuals = array();
$family_groups = array();
foreach ($members as $member) {
    if ((int) $member['family_id'] > 0) {
        $family_id = (int) $member['family_id'];
        if (!isset($family_groups[$family_id])) {
            $family_groups[$family_id] = array(
                'name' => $member['family_name'] ? $member['family_name'] : t('Family'),
                'members' => array()
            );
        }
        $family_groups[$family_id]['members'][] = $member;
    } else {
        $individuals[] = $member;
    }
}

// Defaults, possibly filled from the "Record" link on the contributions page.
$member_id = isset($_GET['member_id']) ? (int) $_GET['member_id'] : 0;
$amount = '';
$pay_date = date('Y-m-d');

if (isset($_GET['pay_date']) && valid_date($_GET['pay_date'])) {
    $pay_date = $_GET['pay_date'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = isset($_POST['member_id']) ? (int) $_POST['member_id'] : 0;
    $amount = trim($_POST['amount'] ?? '');
    $pay_date = trim($_POST['pay_date'] ?? '');

    // Make sure the chosen member exists.
    $selected = null;
    if ($member_id > 0) {
        foreach ($members as $member) {
            if ((int) $member['id'] === $member_id) {
                $selected = $member;
                break;
            }
        }
    }

    if (!$selected) {
        $errors[] = t('Please choose a member.');
    }

    // RWF amounts are whole numbers.
    if ($amount === '' || !is_numeric($amount)) {
        $errors[] = t('Please enter a valid amount.');
    } else {
        $amount_value = (float) $amount;

        if ($amount_value <= 0) {
            $errors[] = t('Amount must be more than zero.');
        } elseif (floor($amount_value) != $amount_value) {
            $errors[] = t('Amount must be a whole number of RWF.');
        } elseif ($amount_value > 99999999) {
            $errors[] = t('Amount is too large.');
        }
    }

    if (!valid_date($pay_date)) {
        $errors[] = t('Please choose a valid payment date.');
    } elseif ($pay_date > date('Y-m-d')) {
        $errors[] = t('Payment date cannot be in the future.');
    }

    // Save when everything is valid.
    if (count($errors) === 0) {
        $amount_sql = number_format($amount_value, 2, '.', '');
        $date_esc = mysqli_real_escape_string($conn, $pay_date);

        $sql = "INSERT INTO contributions (member_id, amount, pay_date)
                VALUES ($member_id, '$amount_sql', '$date_esc')";

        if (mysqli_query($conn, $sql)) {
            set_flash('success', t('Recorded %s RWF for %s.', number_format($amount_value, 0), $selected['name']));
            header('Location: contributions.php?month=' . substr($pay_date, 0, 7));
            exit;
        }

        $errors[] = t('Could not save the contribution: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Record contribution')); ?></h1>
        <p class="muted"><?php echo h(t('Only the secretary can record payments.')); ?></p>
    </div>
    <a class="btn btn-secondary" href="contributions.php"><?php echo h(t('Back to contributions')); ?></a>
</div>

<?php if (count($members) === 0): ?>
    <div class="card">
        <div class="empty">
            <p><?php echo h(t('You need at least one member before recording a contribution.')); ?></p>
            <a class="btn" href="add_member.php"><?php echo h(t('Add member')); ?></a>
        </div>
    </div>
<?php else: ?>
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

        <form method="post" action="contribution_add.php">
            <div class="form-group">
                <label for="member_id"><?php echo h(t('Member')); ?></label>
                <select id="member_id" name="member_id" required>
                    <option value=""><?php echo h(t('-- Choose a member --')); ?></option>
                    <?php if (count($individuals) > 0): ?>
                        <optgroup label="<?php echo h(t('Members')); ?>">
                            <?php foreach ($individuals as $member): ?>
                                <option value="<?php echo (int) $member['id']; ?>"
                                    <?php echo $member_id === (int) $member['id'] ? 'selected' : ''; ?>>
                                    <?php echo h($member['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php foreach ($family_groups as $group): ?>
                        <optgroup label="<?php echo h(t('Family') . ': ' . $group['name']); ?>">
                            <?php foreach ($group['members'] as $member): ?>
                                <option value="<?php echo (int) $member['id']; ?>"
                                    <?php echo $member_id === (int) $member['id'] ? 'selected' : ''; ?>>
                                    <?php echo h($member['name'] . ' (' . t($member['member_type'] === 'child' ? 'Child' : 'Adult') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <div class="hint"><?php echo h(t('Family members are grouped under their family.')); ?></div>
            </div>

            <div class="form-group">
                <label for="amount"><?php echo h(t('Amount (RWF)')); ?></label>
                <input type="number" id="amount" name="amount" min="1" step="1" required
                       value="<?php echo h($amount); ?>">
                <div class="hint"><?php echo h(t('Whole francs only, for example 5000.')); ?></div>
            </div>

            <div class="form-group">
                <label for="pay_date"><?php echo h(t('Payment date')); ?></label>
                <input type="date" id="pay_date" name="pay_date" required
                       max="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo h($pay_date); ?>">
            </div>

            <div class="form-actions">
                <button class="btn" type="submit"><?php echo h(t('Save contribution')); ?></button>
                <a class="btn btn-secondary" href="contributions.php"><?php echo h(t('Cancel')); ?></a>
            </div>
        </form>
    </div>
<?php endif; ?>
<?php require 'footer.php'; ?>
