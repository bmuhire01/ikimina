<?php
// loans.php - money lent to members and how much is still owed.
require_once 'auth.php';
require_login();

$page_title = 'Loans';
$active = 'loans';

$result = mysqli_query($conn, "SELECT l.id, l.member_id, l.group_id, l.amount, l.interest_percent,
                                      l.interest_amount, l.loan_date, l.due_date, l.status,
                                      m.name, m.phone,
                                      COALESCE((SELECT SUM(r.amount) FROM repayments r
                                                WHERE r.loan_id = l.id), 0) AS paid
                               FROM loans l
                               JOIN members m ON m.id = l.member_id
                               ORDER BY l.loan_date DESC, l.id DESC");
$loans = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

$total_issued = 0;
$total_out = 0;
$total_repaid = 0;

// Work out each loan's real status from its repayments.
foreach ($loans as $index => $loan) {
    $due = loan_row_due($loan);
    $out = max(0, $due - (float) $loan['paid']);
    $loans[$index]['total_due'] = $due;
    $loans[$index]['outstanding'] = $out;

    $total_issued += (float) $loan['amount'];
    $total_out += $out;
    $total_repaid += (float) $loan['paid'];

    if ($out <= 0.005) {
        $loans[$index]['state'] = 'paid';
    } elseif ($loan['due_date'] < date('Y-m-d')) {
        $loans[$index]['state'] = 'overdue';
    } else {
        $loans[$index]['state'] = 'active';
    }
}

// Group the shared loans so each group is shown as one loan.
$groups = array();
$single_loans = array();
foreach ($loans as $loan) {
    if ($loan['group_id']) {
        $groups[$loan['group_id']][] = $loan;
    } else {
        $single_loans[] = $loan;
    }
}

// Count each loan once, so a shared loan counts as one loan.
$active_count = 0;
$overdue_count = 0;
$loan_count = count($single_loans) + count($groups);

foreach ($single_loans as $loan) {
    if ($loan['state'] === 'paid') {
        continue;
    }
    $active_count++;
    if ($loan['state'] === 'overdue') {
        $overdue_count++;
    }
}

foreach ($groups as $parts) {
    $group_state = 'paid';
    foreach ($parts as $part) {
        if ($part['state'] !== 'paid') {
            $group_state = $part['state'];
            break;
        }
    }
    if ($group_state === 'paid') {
        continue;
    }
    $active_count++;
    if ($group_state === 'overdue') {
        $overdue_count++;
    }
}

// The name of each shared loan.
$group_names = array();
if (count($groups) > 0) {
    $ids = array_keys($groups);
    $id_list = implode(',', array_map('intval', $ids));
    $result = mysqli_query($conn, "SELECT id, name FROM loan_groups WHERE id IN ($id_list)");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $group_names[(int) $row['id']] = $row['name'];
        }
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Loans')); ?></h1>
        <p class="muted">
            <?php echo h(tn('%d loan', '%d loans', $loan_count)); ?>
            &middot; <?php echo h(t('%d still open', $active_count)); ?>
        </p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-small" href="loan_add.php">+ <?php echo h(t('New loan')); ?></a>
        <a class="btn btn-secondary btn-small" href="group_loan_add.php"><?php echo h(t('Group loan')); ?></a>
    <?php endif; ?>
</div>

<div class="hero">
    <div class="label"><?php echo h(t('Still to be repaid')); ?></div>
    <div class="amount"><?php echo money($total_out); ?> <small>RWF</small></div>
    <div class="hero-row">
        <span><?php echo h(t('Principal issued')); ?> <strong><?php echo money($total_issued); ?> RWF</strong></span>
        <span><?php echo h(t('Overdue')); ?> <strong><?php echo $overdue_count; ?></strong></span>
    </div>
</div>

<div class="stats">
    <div class="stat">
        <div class="number"><?php echo $loan_count; ?></div>
        <div class="label"><?php echo h(t('Loans in total')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $active_count; ?></div>
        <div class="label"><?php echo h(t('Open loans')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $overdue_count; ?></div>
        <div class="label"><?php echo h(t('Overdue')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo money($total_repaid); ?></div>
        <div class="label"><?php echo h(t('Repaid (RWF)')); ?></div>
    </div>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('All loans')); ?></h2>
        <span class="badge neutral"><?php echo h(t('One-time interest')); ?></span>
    </div>
    <p class="hint"><?php echo h(t('Interest is charged once, not monthly.')); ?></p>
    <?php if (count($loans) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No loans have been given yet.')); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn" href="loan_add.php"><?php echo h(t('Give the first loan')); ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($groups as $group_id => $parts): ?>
                <?php
                $group_due = 0;
                $group_paid = 0;
                $group_amount = 0;
                $group_out = 0;
                $group_state = 'paid';
                $group_interest = (float) $parts[0]['interest_percent'];
                $group_loan_date = $parts[0]['loan_date'];
                $group_due_date = $parts[0]['due_date'];
                foreach ($parts as $part) {
                    $group_due += (float) $part['total_due'];
                    $group_paid += (float) $part['paid'];
                    $group_amount += (float) $part['amount'];
                    $group_out += (float) $part['outstanding'];
                    if ($part['state'] !== 'paid') {
                        $group_state = $part['state'];
                    }
                }
                $group_name = isset($group_names[$group_id]) ? $group_names[$group_id] : t('Group loan');
                ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(initials($group_name)); ?></div>
                    <div class="list-main">
                        <div class="list-title">
                            <?php echo h($group_name); ?>
                            <span class="badge neutral"><?php echo h(tn('%d people', '%d people', count($parts))); ?></span>
                            <?php if ($group_state === 'paid'): ?>
                                <span class="badge"><?php echo h(t('Paid')); ?></span>
                            <?php elseif ($group_state === 'overdue'): ?>
                                <span class="badge danger"><?php echo h(t('Overdue')); ?></span>
                            <?php else: ?>
                                <span class="badge warn"><?php echo h(t('Active')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="list-sub">
                            <?php echo h(t('Issued %s', app_date('j M Y', strtotime($group_loan_date)))); ?>
                            &middot; <?php echo h(t('due %s', app_date('j M Y', strtotime($group_due_date)))); ?>
                            &middot; <?php echo number_format($group_interest, 2); ?>%
                        </div>
                        <div class="list-sub">
                            <?php echo h(t('Total %s RWF · each person pays their own share', money($group_amount))); ?>
                        </div>
                        <div class="list-sub">
                            <?php echo h(t('Paid %s of %s RWF', money($group_paid), money($group_due))); ?>
                        </div>
                        <div class="share-list">
                            <?php foreach ($parts as $part): ?>
                                <div class="share-row">
                                    <span class="share-name"><?php echo h($part['name']); ?></span>
                                    <span class="share-due">
                                        <?php echo h(t('%s of %s RWF', money($part['paid']), money($part['total_due']))); ?>
                                    </span>
                                    <?php if (is_admin() && $part['state'] !== 'paid'): ?>
                                        <a class="btn btn-secondary btn-small"
                                           href="repayment_add.php?loan_id=<?php echo (int) $part['id']; ?>"><?php echo h(t('Record repayment')); ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="list-side">
                        <?php echo money($group_out); ?><small><?php echo h(t('outstanding')); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ($single_loans as $loan): ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(initials($loan['name'])); ?></div>
                    <div class="list-main">
                        <div class="list-title">
                            <?php echo h($loan['name']); ?>
                            <?php if ($loan['state'] === 'paid'): ?>
                                <span class="badge"><?php echo h(t('Paid')); ?></span>
                            <?php elseif ($loan['state'] === 'overdue'): ?>
                                <span class="badge danger"><?php echo h(t('Overdue')); ?></span>
                            <?php else: ?>
                                <span class="badge warn"><?php echo h(t('Active')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="list-sub">
                            <?php echo h(t('Issued %s', app_date('j M Y', strtotime($loan['loan_date'])))); ?>
                            &middot; <?php echo h(t('due %s', app_date('j M Y', strtotime($loan['due_date'])))); ?>
                            &middot; <?php echo number_format((float) $loan['interest_percent'], 2); ?>%
                        </div>
                        <div class="list-sub">
                            <?php echo h(t('Paid %s of %s RWF', money($loan['paid']), money($loan['total_due']))); ?>
                        </div>
                        <?php if (is_admin() && $loan['state'] !== 'paid'): ?>
                            <div class="row-actions">
                                <a class="btn btn-secondary btn-small"
                                   href="repayment_add.php?loan_id=<?php echo (int) $loan['id']; ?>"><?php echo h(t('Record repayment')); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="list-side">
                        <?php echo money($loan['outstanding']); ?><small><?php echo h(t('outstanding')); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
