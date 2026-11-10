<?php
// group_report.php - a read-only overview of the whole savings group.
require_once 'auth.php';
require_login();

$page_title = 'Group report';
$active = 'report';

$settings = get_setting();
$monthly_contribution = (float) $settings['contribution_amount'];
$loan_interest = (float) $settings['loan_interest_percent'];

$today = date('Y-m-d');
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$month_label = app_date('F Y', strtotime($month_start));

// Savings totals for the whole group.
$total_saved = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM contributions");
$month_saved = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM contributions
                                 WHERE pay_date BETWEEN '$month_start' AND '$month_end'");

// Member, family and child totals.
$total_members = (int) db_value("SELECT COUNT(*) FROM members");
$active_members = (int) db_value("SELECT COUNT(*) FROM members WHERE status = 'active'");
$family_count = (int) db_value("SELECT COUNT(*) FROM families");
$family_member_count = (int) db_value("SELECT COUNT(*) FROM members WHERE family_id IS NOT NULL");
$child_count = (int) db_value("SELECT COUNT(*) FROM members WHERE member_type = 'child'");
$family_saved = (float) db_value("SELECT COALESCE(SUM(c.amount), 0)
                                  FROM contributions c
                                  JOIN members m ON m.id = c.member_id
                                  WHERE m.family_id IS NOT NULL");

// How many people paid this month and how many have not.
$paid_count = (int) db_value("SELECT COUNT(DISTINCT member_id) FROM contributions
                              WHERE pay_date BETWEEN '$month_start' AND '$month_end'");
$unpaid_count = (int) db_value("SELECT COUNT(*) FROM members m
                                WHERE m.join_date <= '$month_end'
                                AND NOT EXISTS (
                                    SELECT 1 FROM contributions c
                                    WHERE c.member_id = m.id
                                    AND c.pay_date BETWEEN '$month_start' AND '$month_end'
                                )");
$expected_payers = $paid_count + $unpaid_count;
$collection_rate = $expected_payers > 0 ? round(($paid_count / $expected_payers) * 100) : 0;

// The logged-in member's own savings (the secretary has no member record).
$my_id = current_member_id();
$my_saved = $my_id > 0
    ? (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE member_id = $my_id")
    : 0.0;

// Savings for the last six months.
$monthly = array();
$max_month = 0;
for ($i = 5; $i >= 0; $i--) {
    $start = date('Y-m-01', strtotime("-$i months", strtotime($month_start)));
    $end = date('Y-m-t', strtotime($start));
    $amount = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM contributions
                                WHERE pay_date BETWEEN '$start' AND '$end'");
    if ($amount > $max_month) {
        $max_month = $amount;
    }
    $monthly[] = array(
        'label' => app_date('M Y', strtotime($start)),
        'amount' => $amount
    );
}

// Families ranked by savings.
$result = mysqli_query($conn, "SELECT f.id, f.name,
                                      COUNT(DISTINCT m.id) AS member_count,
                                      COALESCE(SUM(c.amount), 0) AS total_saved
                               FROM families f
                               LEFT JOIN members m ON m.family_id = f.id
                               LEFT JOIN contributions c ON c.member_id = m.id
                               GROUP BY f.id, f.name
                               ORDER BY total_saved DESC, f.name ASC
                               LIMIT 5");
$top_families = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

// Members ranked by savings.
$result = mysqli_query($conn, "SELECT m.id, m.name, m.member_type,
                                      f.name AS family_name,
                                      COALESCE(SUM(c.amount), 0) AS total_saved
                               FROM members m
                               LEFT JOIN families f ON f.id = m.family_id
                               LEFT JOIN contributions c ON c.member_id = m.id
                               GROUP BY m.id, m.name, m.member_type, f.name
                               ORDER BY total_saved DESC, m.name ASC
                               LIMIT 5");
$top_savers = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

// Loans and penalties.
$loan_principal = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM loans");
$loan_due = (float) db_value("SELECT COALESCE(SUM(ROUND(amount * (1 + interest_percent / 100))), 0) FROM loans");
$loan_repaid = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM repayments");
$loans_out = max(0, $loan_due - $loan_repaid);
$open_loans = (int) db_value("SELECT COUNT(*) FROM loans WHERE status <> 'paid'");
$overdue_loans = (int) db_value("SELECT COUNT(*) FROM loans
                                 WHERE status <> 'paid' AND due_date < '$today'");
$unpaid_penalties = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM penalties WHERE status = 'unpaid'");
$unpaid_penalty_count = (int) db_value("SELECT COUNT(*) FROM penalties WHERE status = 'unpaid'");

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Group report')); ?></h1>
        <p class="muted"><?php echo h(t('An overview of the whole savings group.')); ?></p>
    </div>
    <span class="badge neutral"><?php echo h(app_date('j M Y')); ?></span>
</div>

<div class="hero">
    <div class="label"><?php echo h(t('Group total saved')); ?></div>
    <div class="amount"><?php echo money($total_saved); ?> <small>RWF</small></div>
    <div class="hero-row">
        <span><?php echo h(t('Collected this month')); ?> <strong><?php echo money($month_saved); ?> RWF</strong></span>
        <span><?php echo h(tn('%d active member', '%d active members', $active_members)); ?></span>
        <?php if ($my_id > 0): ?>
            <span><?php echo h(t('Your savings')); ?> <strong><?php echo money($my_saved); ?> RWF</strong></span>
        <?php endif; ?>
    </div>
</div>

<div class="stats">
    <div class="stat">
        <div class="number"><?php echo $total_members; ?></div>
        <div class="label"><?php echo h(t('Members in total')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $family_count; ?></div>
        <div class="label"><?php echo h(t('Registered families')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $family_member_count; ?></div>
        <div class="label"><?php echo h(t('Family members')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $child_count; ?></div>
        <div class="label"><?php echo h(t('Children in families')); ?></div>
    </div>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('This month')); ?></h2>
        <a class="btn btn-secondary btn-small" href="contributions.php?month=<?php echo h(date('Y-m')); ?>"><?php echo h(t('View month')); ?></a>
    </div>
    <div class="key-value">
        <div><span><?php echo h(t('Collected in %s', $month_label)); ?></span><span><?php echo money($month_saved); ?> RWF</span></div>
        <div><span><?php echo h(t('Members who paid')); ?></span><span><?php echo $paid_count; ?></span></div>
        <div><span><?php echo h(t('Not paid yet')); ?></span><span><?php echo $unpaid_count; ?></span></div>
        <div><span><?php echo h(t('Collection rate')); ?></span><span><?php echo $collection_rate; ?>%</span></div>
    </div>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Savings trend')); ?></h2>
        <span class="badge neutral"><?php echo h(t('Last 6 months')); ?></span>
    </div>
    <div class="report-bars">
        <?php foreach ($monthly as $month): ?>
            <?php
            $width = $max_month > 0 ? round(((float) $month['amount'] / $max_month) * 100) : 0;
            $bar_style = 'width:' . $width . '%;';
            if ((float) $month['amount'] <= 0) {
                $bar_style .= 'min-width:0;background:#e5e7eb;';
            }
            ?>
            <div class="report-bar">
                <div class="report-bar-head">
                    <span><?php echo h($month['label']); ?></span>
                    <span><?php echo money($month['amount']); ?> RWF</span>
                </div>
                <div class="report-bar-track">
                    <div class="report-bar-fill" style="<?php echo h($bar_style); ?>"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Families')); ?></h2>
        <a class="btn btn-secondary btn-small" href="families.php"><?php echo h(t('See all')); ?></a>
    </div>
    <?php if (count($top_families) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No families yet.')); ?></p>
        </div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($top_families as $family): ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(initials($family['name'])); ?></div>
                    <div class="list-main">
                        <div class="list-title"><?php echo h($family['name']); ?></div>
                        <div class="list-sub"><?php echo h(tn('%d member', '%d members', (int) $family['member_count'])); ?></div>
                    </div>
                    <div class="list-side"><?php echo money($family['total_saved']); ?><small>RWF</small></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="key-value" style="margin-top:14px;">
            <div><span><?php echo h(t('Family savings')); ?></span><span><?php echo money($family_saved); ?> RWF</span></div>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Top savers')); ?></h2>
        <a class="btn btn-secondary btn-small" href="balances.php"><?php echo h(t('See all')); ?></a>
    </div>
    <?php if (count($top_savers) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No members yet.')); ?></p>
        </div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($top_savers as $saver): ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(initials($saver['name'])); ?></div>
                    <div class="list-main">
                        <div class="list-title">
                            <?php echo h($saver['name']); ?>
                            <?php if ($saver['member_type'] === 'child'): ?>
                                <span class="badge neutral"><?php echo h(t('Child')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="list-sub">
                            <?php echo $saver['family_name'] ? h($saver['family_name']) : h(t('Individual member')); ?>
                        </div>
                    </div>
                    <div class="list-side"><?php echo money($saver['total_saved']); ?><small>RWF</small></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Loans and penalties')); ?></h2>
    </div>
    <div class="key-value">
        <div><span><?php echo h(t('Principal issued')); ?></span><span><?php echo money($loan_principal); ?> RWF</span></div>
        <div><span><?php echo h(t('Repaid (RWF)')); ?></span><span><?php echo money($loan_repaid); ?></span></div>
        <div><span><?php echo h(t('Loans outstanding (RWF)')); ?></span><span><?php echo money($loans_out); ?></span></div>
        <div><span><?php echo h(t('Open loans')); ?></span><span><?php echo $open_loans; ?></span></div>
        <div><span><?php echo h(t('Overdue')); ?></span><span><?php echo $overdue_loans; ?></span></div>
        <div><span><?php echo h(t('Unpaid penalties (RWF)')); ?></span><span><?php echo money($unpaid_penalties); ?></span></div>
        <div><span><?php echo h(tn('%d unpaid penalty', '%d unpaid penalties', $unpaid_penalty_count)); ?></span><span><?php echo $unpaid_penalty_count; ?></span></div>
    </div>
    <div class="form-actions">
        <a class="btn btn-secondary btn-small" href="loans.php"><?php echo h(t('All loans')); ?></a>
        <a class="btn btn-secondary btn-small" href="penalties.php"><?php echo h(t('All penalties')); ?></a>
    </div>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('Group settings')); ?></h2></div>
    <div class="key-value">
        <div><span><?php echo h(t('Monthly contribution')); ?></span><span><?php echo $monthly_contribution > 0 ? money($monthly_contribution) . ' RWF' : h(t('Not set')); ?></span></div>
        <div><span><?php echo h(t('Loan interest')); ?></span><span><?php echo number_format($loan_interest, 2); ?>%</span></div>
    </div>
</div>
<?php require 'footer.php'; ?>
