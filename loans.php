<?php
// loans.php - money lent to members and how much is still owed.
require_once 'auth.php';
require_login();

$page_title = 'Loans';
$active = 'loans';

$result = mysqli_query($conn, "SELECT l.id, l.member_id, l.amount, l.interest_percent,
                                      l.loan_date, l.due_date, l.status,
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
$active_count = 0;
$overdue_count = 0;

// Work out each loan's real status from its repayments.
foreach ($loans as $index => $loan) {
    $due = loan_total_due($loan['amount'], $loan['interest_percent']);
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
        $overdue_count++;
        $active_count++;
    } else {
        $loans[$index]['state'] = 'active';
        $active_count++;
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Loans')); ?></h1>
        <p class="muted">
            <?php echo h(tn('%d loan', '%d loans', count($loans))); ?>
            &middot; <?php echo h(t('%d still open', $active_count)); ?>
        </p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-small" href="loan_add.php">+ <?php echo h(t('New loan')); ?></a>
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
        <div class="number"><?php echo count($loans); ?></div>
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
    <div class="section-head"><h2><?php echo h(t('All loans')); ?></h2></div>
    <?php if (count($loans) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No loans have been given yet.')); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn" href="loan_add.php"><?php echo h(t('Give the first loan')); ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($loans as $loan): ?>
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
