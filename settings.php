<?php
// settings.php - secretary edits the group name and default amounts.
require_once 'auth.php';
require_admin();

$page_title = 'Group settings';
$active = 'settings';

$settings = get_setting();
$errors = array();

$group_name = $settings['group_name'];
$contribution_amount = (float) $settings['contribution_amount'];
$loan_interest = (float) $settings['loan_interest_percent'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name'] ?? '');
    $contribution_amount = trim($_POST['contribution_amount'] ?? '0');
    $loan_interest = trim($_POST['loan_interest_percent'] ?? '0');

    if ($group_name === '') {
        $errors[] = t('Group name is required.');
    } elseif (mb_strlen($group_name) > 150) {
        $errors[] = t('Group name must be 150 characters or fewer.');
    }

    // Monthly contribution: whole RWF, zero allowed when not decided yet.
    $contribution_value = 0;
    if ($contribution_amount === '' || !is_numeric($contribution_amount)) {
        $errors[] = t('Please enter a valid monthly contribution.');
    } else {
        $contribution_value = (float) $contribution_amount;
        if ($contribution_value < 0) {
            $errors[] = t('Monthly contribution cannot be negative.');
        } elseif (floor($contribution_value) != $contribution_value) {
            $errors[] = t('Monthly contribution must be a whole number of RWF.');
        } elseif ($contribution_value > 99999999) {
            $errors[] = t('Monthly contribution is too large.');
        }
    }

    // Interest between 0 and 100 percent.
    $interest_value = 0;
    if ($loan_interest === '' || !is_numeric($loan_interest)) {
        $errors[] = t('Please enter a valid interest rate.');
    } else {
        $interest_value = (float) $loan_interest;
        if ($interest_value < 0 || $interest_value > 100) {
            $errors[] = t('Interest must be between 0 and 100 percent.');
        }
    }

    if (count($errors) === 0) {
        $name_esc = mysqli_real_escape_string($conn, $group_name);
        $contribution_sql = number_format($contribution_value, 2, '.', '');
        $interest_sql = number_format($interest_value, 2, '.', '');

        $sql = "INSERT INTO settings (id, group_name, contribution_amount, loan_interest_percent)
                VALUES (1, '$name_esc', '$contribution_sql', '$interest_sql')
                ON DUPLICATE KEY UPDATE
                    group_name = VALUES(group_name),
                    contribution_amount = VALUES(contribution_amount),
                    loan_interest_percent = VALUES(loan_interest_percent)";

        if (mysqli_query($conn, $sql)) {
            set_flash('success', t('Group settings saved.'));
            header('Location: settings.php');
            exit;
        }

        $errors[] = t('Could not save the settings: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Group settings')); ?></h1>
        <p class="muted"><?php echo h(t('Only the secretary can change these')); ?></p>
    </div>
    <a class="btn btn-secondary btn-small" href="more.php"><?php echo h(t('Back')); ?></a>
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

    <form method="post" action="settings.php">
        <div class="form-group">
            <label for="group_name"><?php echo h(t('Group name')); ?></label>
            <input type="text" id="group_name" name="group_name" maxlength="150" required
                   value="<?php echo h($group_name); ?>">
        </div>

        <div class="form-group">
            <label for="contribution_amount"><?php echo h(t('Monthly contribution (RWF)')); ?></label>
            <input type="number" id="contribution_amount" name="contribution_amount"
                   min="0" step="1" inputmode="numeric"
                   value="<?php echo h($contribution_amount); ?>">
            <div class="hint"><?php echo h(t('Use 0 if the group has not fixed an amount yet.')); ?></div>
        </div>

        <div class="form-group">
            <label for="loan_interest_percent"><?php echo h(t('Default loan interest (%)')); ?></label>
            <input type="number" id="loan_interest_percent" name="loan_interest_percent"
                   min="0" max="100" step="0.01" inputmode="decimal"
                   value="<?php echo h($loan_interest); ?>">
            <div class="hint"><?php echo h(t('Used as the default when a new loan is created.')); ?></div>
            <div class="hint"><?php echo h(t('This interest is charged once when the loan is repaid, not per month.')); ?></div>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit"><?php echo h(t('Save settings')); ?></button>
            <a class="btn btn-secondary" href="more.php"><?php echo h(t('Cancel')); ?></a>
        </div>
    </form>
</div>
<?php require 'footer.php'; ?>
