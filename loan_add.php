<?php
// loan_add.php - secretary records a new loan for a member.
require_once 'auth.php';
require_admin();

$page_title = 'New loan';
$active = 'loans';

$settings = get_setting();
$default_interest = (float) $settings['loan_interest_percent'];

$errors = array();
$member_id = 0;
$amount = '';
$interest = $default_interest > 0 ? number_format($default_interest, 2, '.', '') : '0';
$loan_date = date('Y-m-d');
$due_date = date('Y-m-d', strtotime('+30 days'));

// Active members can receive a loan.
$result = mysqli_query($conn, "SELECT id, name FROM members
                               WHERE status = 'active' ORDER BY name ASC");
$members = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = isset($_POST['member_id']) ? (int) $_POST['member_id'] : 0;
    $amount = trim($_POST['amount'] ?? '');
    $interest = trim($_POST['interest_percent'] ?? '0');
    $loan_date = trim($_POST['loan_date'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');

    // The chosen member must exist and be active.
    $selected = null;
    foreach ($members as $member) {
        if ((int) $member['id'] === $member_id) {
            $selected = $member;
            break;
        }
    }

    if (!$selected) {
        $errors[] = t('Please choose an active member.');
    }

    // Principal: whole RWF, more than zero.
    $amount_value = 0;
    if ($amount === '' || !is_numeric($amount)) {
        $errors[] = t('Please enter a valid loan amount.');
    } else {
        $amount_value = (float) $amount;
        if ($amount_value <= 0) {
            $errors[] = t('Loan amount must be more than zero.');
        } elseif (floor($amount_value) != $amount_value) {
            $errors[] = t('Loan amount must be a whole number of RWF.');
        } elseif ($amount_value > 99999999) {
            $errors[] = t('Loan amount is too large.');
        }
    }

    // Interest between 0 and 100 percent.
    $interest_value = 0;
    if ($interest === '' || !is_numeric($interest)) {
        $errors[] = t('Please enter a valid interest rate.');
    } else {
        $interest_value = (float) $interest;
        if ($interest_value < 0 || $interest_value > 100) {
            $errors[] = t('Interest must be between 0 and 100 percent.');
        }
    }

    if (!valid_date($loan_date)) {
        $errors[] = t('Please choose a valid loan date.');
    }

    if (!valid_date($due_date)) {
        $errors[] = t('Please choose a valid due date.');
    } elseif (valid_date($loan_date) && $due_date < $loan_date) {
        $errors[] = t('The due date cannot be before the loan date.');
    }

    // Save when everything is valid.
    if (count($errors) === 0) {
        $amount_sql = number_format($amount_value, 2, '.', '');
        $interest_sql = number_format($interest_value, 2, '.', '');
        $loan_date_esc = mysqli_real_escape_string($conn, $loan_date);
        $due_date_esc = mysqli_real_escape_string($conn, $due_date);

        $sql = "INSERT INTO loans (member_id, amount, interest_percent, loan_date, due_date, status)
                VALUES ($member_id, '$amount_sql', '$interest_sql', '$loan_date_esc', '$due_date_esc', 'active')";

        if (mysqli_query($conn, $sql)) {
            $total_due = loan_total_due($amount_value, $interest_value);
            set_flash('success', t(
                'Loan of %s RWF recorded for %s. Total to repay: %s RWF.',
                money($amount_value),
                $selected['name'],
                money($total_due)
            ));
            header('Location: loans.php');
            exit;
        }

        $errors[] = t('Could not save the loan: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('New loan')); ?></h1>
        <p class="muted"><?php echo h(t('Only the secretary can give a loan')); ?></p>
    </div>
    <a class="btn btn-secondary btn-small" href="loans.php"><?php echo h(t('Back')); ?></a>
</div>

<?php if (count($members) === 0): ?>
    <div class="card">
        <div class="empty">
            <p><?php echo h(t('You need an active member before giving a loan.')); ?></p>
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

        <form method="post" action="loan_add.php">
            <div class="form-group">
                <label for="member_id"><?php echo h(t('Member')); ?></label>
                <select id="member_id" name="member_id" required>
                    <option value=""><?php echo h(t('-- Choose a member --')); ?></option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?php echo (int) $member['id']; ?>"
                            <?php echo $member_id === (int) $member['id'] ? 'selected' : ''; ?>>
                            <?php echo h($member['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="amount"><?php echo h(t('Loan amount (RWF)')); ?></label>
                <input type="number" id="amount" name="amount" min="1" step="1" required
                       inputmode="numeric" value="<?php echo h($amount); ?>">
                <div class="hint"><?php echo h(t('Whole francs only, for example 50000.')); ?></div>
            </div>

            <div class="form-group">
                <label for="interest_percent"><?php echo h(t('Interest (%)')); ?></label>
                <input type="number" id="interest_percent" name="interest_percent"
                       min="0" max="100" step="0.01" required inputmode="decimal"
                       value="<?php echo h($interest); ?>">
                <div class="hint"><?php echo h(t('The total to repay is the amount plus this interest.')); ?></div>
            </div>

            <div class="form-group">
                <label for="loan_date"><?php echo h(t('Loan date')); ?></label>
                <input type="date" id="loan_date" name="loan_date" required
                       value="<?php echo h($loan_date); ?>">
            </div>

            <div class="form-group">
                <label for="due_date"><?php echo h(t('Due date')); ?></label>
                <input type="date" id="due_date" name="due_date" required
                       value="<?php echo h($due_date); ?>">
            </div>

            <div class="form-actions">
                <button class="btn" type="submit"><?php echo h(t('Save loan')); ?></button>
                <a class="btn btn-secondary" href="loans.php"><?php echo h(t('Cancel')); ?></a>
            </div>
        </form>
    </div>
<?php endif; ?>
<?php require 'footer.php'; ?>
