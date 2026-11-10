<?php
// contributions.php - monthly payments and members who have not paid.
require_once 'auth.php';
require_login();

$page_title = 'Contributions';
$active = 'contributions';

// Read the month (YYYY-MM); default to the current month.
$month = isset($_GET['month']) ? trim($_GET['month']) : date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month) || $month > date('Y-m')) {
    $month = date('Y-m');
}

$start = $month . '-01';
if (!valid_date($start)) {
    $month = date('Y-m');
    $start = $month . '-01';
}

$end = date('Y-m-t', strtotime($start));
$month_label = app_date('F Y', strtotime($start));

// Escape the date values before using them in SQL.
$start_esc = mysqli_real_escape_string($conn, $start);
$end_esc = mysqli_real_escape_string($conn, $end);

// Payments recorded in this month.
$result = mysqli_query($conn, "SELECT c.id, c.member_id, c.amount, c.pay_date, m.name,
                                      m.member_type, f.id AS family_id, f.name AS family_name
                               FROM contributions c
                               JOIN members m ON m.id = c.member_id
                               LEFT JOIN families f ON f.id = m.family_id
                               WHERE c.pay_date BETWEEN '$start_esc' AND '$end_esc'
                               ORDER BY c.pay_date DESC, c.id DESC");
$payments = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

// Add up the month and count the members who paid at least once.
$month_total = 0;
$paid_ids = array();
foreach ($payments as $payment) {
    $month_total += (float) $payment['amount'];
    $paid_ids[(int) $payment['member_id']] = true;
}
$paid_count = count($paid_ids);

// Members who had joined by the end of this month but did not pay.
$result = mysqli_query($conn, "SELECT m.id, m.name, m.phone, m.member_type,
                                      f.id AS family_id, f.name AS family_name
                               FROM members m
                               LEFT JOIN families f ON f.id = m.family_id
                               WHERE m.join_date <= '$end_esc'
                               AND NOT EXISTS (
                                   SELECT 1 FROM contributions c
                                   WHERE c.member_id = m.id
                                   AND c.pay_date BETWEEN '$start_esc' AND '$end_esc'
                               )
                               ORDER BY m.name ASC");
$unpaid = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
$unpaid_count = count($unpaid);

// Default date when the secretary clicks "Record" next to an unpaid member.
$default_pay_date = ($month === date('Y-m')) ? date('Y-m-d') : $end;

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Contributions')); ?></h1>
        <p class="muted"><?php echo h(t('Payments received in %s.', $month_label)); ?></p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-small" href="contribution_add.php"><?php echo h(t('+ Record')); ?></a>
    <?php endif; ?>
</div>

<div class="card">
    <form class="filter-form" method="get" action="contributions.php">
        <div class="form-group">
            <label for="month"><?php echo h(t('Choose month')); ?></label>
            <input type="month" id="month" name="month" max="<?php echo date('Y-m'); ?>"
                   value="<?php echo h($month); ?>">
        </div>
        <button class="btn btn-secondary" type="submit"><?php echo h(t('View')); ?></button>
        <?php if ($month !== date('Y-m')): ?>
            <a class="btn btn-secondary" href="contributions.php"><?php echo h(t('This month')); ?></a>
        <?php endif; ?>
    </form>
</div>

<div class="stats">
    <div class="stat">
        <div class="number"><?php echo money($month_total); ?></div>
        <div class="label"><?php echo h(t('RWF collected')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $paid_count; ?></div>
        <div class="label"><?php echo h(t('Members who paid')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $unpaid_count; ?></div>
        <div class="label"><?php echo h(t('Not paid yet')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo money($unpaid_count > 0 ? $month_total / max(1, $paid_count) : 0); ?></div>
        <div class="label"><?php echo h(t('Average per payer (RWF)')); ?></div>
    </div>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('Payments received')); ?></h2></div>
    <?php if (count($payments) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No contributions recorded for %s.', $month_label)); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn btn-small" href="contribution_add.php"><?php echo h(t('Record the first payment')); ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($payments as $payment): ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(initials($payment['name'])); ?></div>
                    <div class="list-main">
                        <div class="list-title">
                            <?php echo h($payment['name']); ?>
                            <?php if ($payment['member_type'] === 'child'): ?>
                                <span class="badge neutral"><?php echo h(t('Child')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="list-sub">
                            <?php echo h(app_date('j M Y', strtotime($payment['pay_date']))); ?>
                            <?php if ($payment['family_name']): ?>
                                &middot; <?php echo h($payment['family_name']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="list-side"><?php echo money($payment['amount']); ?><small>RWF</small></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('Not paid yet')); ?></h2></div>
    <?php if ($paid_count + $unpaid_count === 0): ?>
        <p class="muted"><?php echo h(t('No members had joined by %s.', $month_label)); ?></p>
    <?php elseif ($unpaid_count === 0): ?>
        <div class="alert success" style="margin-bottom:0;">
            <?php echo h(t('Everyone has paid for %s.', $month_label)); ?>
        </div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($unpaid as $person): ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(initials($person['name'])); ?></div>
                    <div class="list-main">
                        <div class="list-title">
                            <?php echo h($person['name']); ?>
                            <?php if ($person['member_type'] === 'child'): ?>
                                <span class="badge neutral"><?php echo h(t('Child')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="list-sub">
                            <?php echo $person['phone'] ? h($person['phone']) : h(t('No phone')); ?>
                            <?php if ($person['family_name']): ?>
                                &middot; <?php echo h($person['family_name']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (is_admin()): ?>
                        <div class="list-side">
                            <a class="btn btn-secondary btn-small"
                               href="contribution_add.php?member_id=<?php echo (int) $person['id']; ?>&amp;pay_date=<?php echo h($default_pay_date); ?>"><?php echo h(t('Record')); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
