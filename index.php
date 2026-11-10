<?php
// index.php - mobile dashboard shown after login.
require_once 'auth.php';
require_login();

$page_title = 'Home';
$active = 'home';

$settings = get_setting();
$group_name = $settings['group_name'];
$monthly_contribution = (float) $settings['contribution_amount'];
$loan_interest = (float) $settings['loan_interest_percent'];

$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

// Group totals.
$total_saved = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM contributions");
$month_saved = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM contributions
                                 WHERE pay_date BETWEEN '$month_start' AND '$month_end'");
$total_members = (int) db_value("SELECT COUNT(*) FROM members");
$active_members = (int) db_value("SELECT COUNT(*) FROM members WHERE status = 'active'");

// Families and their members' savings.
$family_count = (int) db_value("SELECT COUNT(*) FROM families");
$family_member_count = (int) db_value("SELECT COUNT(*) FROM members WHERE family_id IS NOT NULL");
$family_saved = (float) db_value("SELECT COALESCE(SUM(c.amount), 0)
                                  FROM contributions c
                                  JOIN members m ON m.id = c.member_id
                                  WHERE m.family_id IS NOT NULL");

// Loans still to be repaid.
$loan_due = (float) db_value("SELECT COALESCE(SUM(ROUND(amount * (1 + interest_percent / 100))), 0) FROM loans");
$loan_repaid = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM repayments");
$loans_out = max(0, $loan_due - $loan_repaid);
$active_loans = (int) db_value("SELECT COUNT(*) FROM loans WHERE status <> 'paid'");

// Penalties that are still open.
$unpaid_penalties = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM penalties WHERE status = 'unpaid'");
$penalty_count = (int) db_value("SELECT COUNT(*) FROM penalties WHERE status = 'unpaid'");

// The logged-in member's own savings (admins have no member record).
$my_id = current_member_id();
$my_saved = $my_id > 0
    ? (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE member_id = $my_id")
    : 0.0;

// The next meeting that is today or later.
$result = mysqli_query($conn, "SELECT meeting_date, place FROM meetings
                               WHERE meeting_date >= CURDATE()
                               ORDER BY meeting_date ASC, id ASC LIMIT 1");
$next_meeting = $result ? mysqli_fetch_assoc($result) : null;

// Latest contributions: the member's own, or the whole group for the secretary.
$recent_sql = $my_id > 0
    ? "SELECT c.amount, c.pay_date, m.name
       FROM contributions c JOIN members m ON m.id = c.member_id
       WHERE c.member_id = $my_id
       ORDER BY c.pay_date DESC, c.id DESC LIMIT 5"
    : "SELECT c.amount, c.pay_date, m.name
       FROM contributions c JOIN members m ON m.id = c.member_id
       ORDER BY c.pay_date DESC, c.id DESC LIMIT 5";
$result = mysqli_query($conn, $recent_sql);
$recent = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Hello, %s', $_SESSION['username'])); ?></h1>
        <p class="muted"><?php echo h(app_date('l, j F Y')); ?></p>
    </div>
    <span class="badge <?php echo is_admin() ? '' : 'neutral'; ?>">
        <?php echo h(t(is_admin() ? 'Secretary' : 'Member')); ?>
    </span>
</div>

<div class="hero">
    <div class="label"><?php echo h(t($my_id > 0 ? 'Your total savings' : 'Group savings')); ?></div>
    <div class="amount">
        <?php echo money($my_id > 0 ? $my_saved : $total_saved); ?> <small>RWF</small>
    </div>
    <div class="hero-row">
        <span><?php echo h(t('Collected this month')); ?> <strong><?php echo money($month_saved); ?> RWF</strong></span>
        <span><?php echo h(tn('%d active member', '%d active members', $active_members)); ?></span>
    </div>
</div>

<?php if (is_admin()): ?>
    <div class="quick-grid">
        <a class="quick" href="contribution_add.php">
            <span class="ico"><?php echo nav_icon('money'); ?></span><?php echo h(t('Contribution')); ?>
        </a>
        <a class="quick" href="register.php">
            <span class="ico"><?php echo nav_icon('members'); ?></span><?php echo h(t('Register')); ?>
        </a>
        <a class="quick" href="loan_add.php">
            <span class="ico"><?php echo nav_icon('loans'); ?></span><?php echo h(t('New loan')); ?>
        </a>
        <a class="quick" href="meeting_add.php">
            <span class="ico"><?php echo nav_icon('calendar'); ?></span><?php echo h(t('Meeting')); ?>
        </a>
    </div>
<?php else: ?>
    <div class="quick-grid">
        <a class="quick" href="balances.php">
            <span class="ico"><?php echo nav_icon('balance'); ?></span><?php echo h(t('Balances')); ?>
        </a>
        <a class="quick" href="contributions.php">
            <span class="ico"><?php echo nav_icon('money'); ?></span><?php echo h(t('Payments')); ?>
        </a>
        <a class="quick" href="loans.php">
            <span class="ico"><?php echo nav_icon('loans'); ?></span><?php echo h(t('Loans')); ?>
        </a>
        <a class="quick" href="meetings.php">
            <span class="ico"><?php echo nav_icon('calendar'); ?></span><?php echo h(t('Meetings')); ?>
        </a>
    </div>
<?php endif; ?>

<?php if (!is_admin()): ?>
    <div class="card">
        <div class="section-head">
            <h2><?php echo h(t('Group report')); ?></h2>
            <a class="btn btn-secondary btn-small" href="group_report.php"><?php echo h(t('Open report')); ?></a>
        </div>
        <div class="key-value">
            <div><span><?php echo h(t('Group total saved')); ?></span><span><?php echo money($total_saved); ?> RWF</span></div>
            <div><span><?php echo h(t('Collected this month')); ?></span><span><?php echo money($month_saved); ?> RWF</span></div>
            <div><span><?php echo h(t('Active members')); ?></span><span><?php echo $active_members; ?></span></div>
        </div>
    </div>
<?php endif; ?>

<div class="stats">
    <div class="stat">
        <div class="number"><?php echo money($loans_out); ?></div>
        <div class="label"><?php echo h(t('Loans outstanding (RWF)')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo money($unpaid_penalties); ?></div>
        <div class="label"><?php echo h(t('Unpaid penalties (RWF)')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $active_loans; ?></div>
        <div class="label"><?php echo h(t('Open loans')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $total_members; ?></div>
        <div class="label"><?php echo h(t('Members in total')); ?></div>
    </div>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Families')); ?></h2>
        <a class="btn btn-secondary btn-small" href="families.php"><?php echo h(t('See all')); ?></a>
    </div>
    <div class="key-value">
        <div><span><?php echo h(t('Registered families')); ?></span><span><?php echo $family_count; ?></span></div>
        <div><span><?php echo h(t('Family members')); ?></span><span><?php echo $family_member_count; ?></span></div>
        <div><span><?php echo h(t('Family savings')); ?></span><span><?php echo money($family_saved); ?> RWF</span></div>
    </div>
    <?php if (is_admin()): ?>
        <div class="form-actions">
            <a class="btn btn-small" href="family_add.php"><?php echo h(t('Register family')); ?></a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Next meeting')); ?></h2>
        <a class="btn btn-secondary btn-small" href="meetings.php"><?php echo h(t('All meetings')); ?></a>
    </div>
    <?php if ($next_meeting): ?>
        <div class="list-row">
            <div class="avatar-sm"><?php echo h(strtoupper(date('j', strtotime($next_meeting['meeting_date'])))); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(app_date('l, j F Y', strtotime($next_meeting['meeting_date']))); ?></div>
                <div class="list-sub"><?php echo h($next_meeting['place']); ?></div>
            </div>
            <span class="badge"><?php echo h(t('Upcoming')); ?></span>
        </div>
    <?php else: ?>
        <p class="muted"><?php echo h(t('No meeting has been scheduled yet.')); ?></p>
        <?php if (is_admin()): ?>
            <a class="btn btn-secondary btn-small" href="meeting_add.php"><?php echo h(t('Schedule a meeting')); ?></a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t($my_id > 0 ? 'Your recent payments' : 'Recent contributions')); ?></h2>
        <a class="btn btn-secondary btn-small" href="contributions.php"><?php echo h(t('See all')); ?></a>
    </div>
    <?php if (count($recent) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No contributions recorded yet.')); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn btn-small" href="contribution_add.php"><?php echo h(t('Record one')); ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($recent as $item): ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(initials($item['name'])); ?></div>
                    <div class="list-main">
                        <div class="list-title"><?php echo h($item['name']); ?></div>
                        <div class="list-sub"><?php echo h(app_date('j M Y', strtotime($item['pay_date']))); ?></div>
                    </div>
                    <div class="list-side"><?php echo money($item['amount']); ?><small>RWF</small></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('Group settings')); ?></h2></div>
    <div class="key-value">
        <div><span><?php echo h(t('Monthly contribution')); ?></span><span><?php echo $monthly_contribution > 0 ? money($monthly_contribution) . ' RWF' : h(t('Not set')); ?></span></div>
        <div><span><?php echo h(t('Loan interest')); ?></span><span><?php echo number_format($loan_interest, 2); ?>%</span></div>
    </div>
    <?php if (is_admin()): ?>
        <div class="form-actions">
            <a class="btn btn-secondary btn-small" href="settings.php"><?php echo h(t('Edit settings')); ?></a>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
