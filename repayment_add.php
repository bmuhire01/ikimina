<?php
// repayment_add.php - secretary records money paid back against a loan.
require_once 'auth.php';
require_admin();

$page_title = 'Record repayment';
$active = 'loans';

$loan_id = isset($_GET['loan_id']) ? (int) $_GET['loan_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_id = isset($_POST['loan_id']) ? (int) $_POST['loan_id'] : 0;
}

if ($loan_id < 1) {
    set_flash('error', t('Loan not found.'));
    header('Location: loans.php');
    exit;
}

// Load the loan and everything already repaid against it.
$result = mysqli_query($conn, "SELECT l.id, l.member_id, l.amount, l.interest_percent,
                                      l.loan_date, l.due_date, l.status, m.name,
                                      COALESCE((SELECT SUM(r.amount) FROM repayments r
                                                WHERE r.loan_id = l.id), 0) AS paid
                               FROM loans l
                               JOIN members m ON m.id = l.member_id
                               WHERE l.id = $loan_id LIMIT 1");
$loan = $result ? mysqli_fetch_assoc($result) : null;

if (!$loan) {
    set_flash('error', t('Loan not found.'));
    header('Location: loans.php');
    exit;
}

$total_due = loan_total_due($loan['amount'], $loan['interest_percent']);
$outstanding = max(0, $total_due - (float) $loan['paid']);
$closed = $outstanding <= 0.005;

$errors = array();
$amount = '';
$pay_date = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = trim($_POST['amount'] ?? '');
    $pay_date = trim($_POST['pay_date'] ?? '');

    $amount_value = 0;
    if ($amount === '' || !is_numeric($amount)) {
        $errors[] = t('Please enter a valid repayment amount.');
    } else {
        $amount_value = (float) $amount;
        if ($amount_value <= 0) {
            $errors[] = t('Repayment must be more than zero.');
        } elseif (floor($amount_value) != $amount_value) {
            $errors[] = t('Repayment must be a whole number of RWF.');
        } elseif ($amount_value > $outstanding + 0.005) {
            $errors[] = t('Repayment cannot be more than the outstanding %s RWF.', money($outstanding));
        }
    }

    if (!valid_date($pay_date)) {
        $errors[] = t('Please choose a valid payment date.');
    } elseif ($pay_date > date('Y-m-d')) {
        $errors[] = t('Payment date cannot be in the future.');
    }

    if ($closed) {
        $errors[] = t('This loan has already been repaid in full.');
    }

    if (count($errors) === 0) {
        $amount_sql = number_format($amount_value, 2, '.', '');
        $date_esc = mysqli_real_escape_string($conn, $pay_date);

        $sql = "INSERT INTO repayments (loan_id, amount, pay_date)
                VALUES ($loan_id, '$amount_sql', '$date_esc')";

        if (mysqli_query($conn, $sql)) {
            // Update the stored status so lists stay accurate.
            $new_paid = (float) $loan['paid'] + $amount_value;
            if ($new_paid + 0.005 >= $total_due) {
                $status = 'paid';
            } elseif ($loan['due_date'] < date('Y-m-d')) {
                $status = 'overdue';
            } else {
                $status = 'active';
            }
            mysqli_query($conn, "UPDATE loans SET status = '$status' WHERE id = $loan_id LIMIT 1");

            set_flash('success', t(
                'Recorded %s RWF repayment from %s.',
                money($amount_value),
                $loan['name']
            ));
            header('Location: loans.php');
            exit;
        }

        $errors[] = t('Could not save the repayment: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Record repayment')); ?></h1>
        <p class="muted"><?php echo h(t('Money paid back by %s', $loan['name'])); ?></p>
    </div>
    <a class="btn btn-secondary btn-small" href="loans.php"><?php echo h(t('Back')); ?></a>
</div>

<div class="card">
    <div class="key-value">
        <div><span><?php echo h(t('Loan amount')); ?></span><span><?php echo money($loan['amount']); ?> RWF</span></div>
        <div><span><?php echo h(t('Interest (one-time)')); ?></span><span><?php echo number_format((float) $loan['interest_percent'], 2); ?>%</span></div>
        <div><span><?php echo h(t('Total to repay')); ?></span><span><?php echo money($total_due); ?> RWF</span></div>
        <div><span><?php echo h(t('Already paid')); ?></span><span><?php echo money($loan['paid']); ?> RWF</span></div>
        <div><span><?php echo h(t('Outstanding')); ?></span><span><?php echo money($outstanding); ?> RWF</span></div>
        <div><span><?php echo h(t('Due date')); ?></span><span><?php echo h(app_date('j M Y', strtotime($loan['due_date']))); ?></span></div>
    </div>
</div>

<?php if ($closed): ?>
    <div class="card">
        <div class="alert success" style="margin-bottom:0;"><?php echo h(t('This loan has been repaid in full.')); ?></div>
        <div class="form-actions">
            <a class="btn" href="loans.php"><?php echo h(t('Back to loans')); ?></a>
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

        <form method="post" action="repayment_add.php?loan_id=<?php echo $loan_id; ?>">
            <input type="hidden" name="loan_id" value="<?php echo $loan_id; ?>">

            <div class="form-group">
                <label for="amount"><?php echo h(t('Amount paid (RWF)')); ?></label>
                <input type="number" id="amount" name="amount" min="1" step="1" required
                       inputmode="numeric" value="<?php echo h($amount); ?>">
                <div class="hint"><?php echo h(t('Up to %s RWF.', money($outstanding))); ?></div>
            </div>

            <div class="form-group">
                <label for="pay_date"><?php echo h(t('Payment date')); ?></label>
                <input type="date" id="pay_date" name="pay_date" required
                       max="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo h($pay_date); ?>">
            </div>

            <div class="form-actions">
                <button class="btn" type="submit"><?php echo h(t('Save repayment')); ?></button>
                <a class="btn btn-secondary" href="loans.php"><?php echo h(t('Cancel')); ?></a>
            </div>
        </form>
    </div>
<?php endif; ?>
<?php require 'footer.php'; ?>
