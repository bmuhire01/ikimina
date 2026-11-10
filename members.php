<?php
// members.php - lists and searches members; the secretary can add or edit.
require_once 'auth.php';
require_login();

$page_title = 'Members';
$active = 'members';

$settings = get_setting();

// Search text from the URL.
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q_esc = mysqli_real_escape_string($conn, $q);

$where = '';
if ($q !== '') {
    $where = "WHERE m.name LIKE '%$q_esc%' OR m.phone LIKE '%$q_esc%' OR f.name LIKE '%$q_esc%'";
}

// Each member plus their family and the username of their login (if one exists).
$sql = "SELECT m.id, m.name, m.phone, m.join_date, m.status, m.member_type,
               f.id AS family_id, f.name AS family_name,
               (SELECT COALESCE(SUM(c.amount), 0) FROM contributions c
                WHERE c.member_id = m.id) AS total_saved,
               (SELECT u.username FROM users u
                WHERE u.member_id = m.id
                ORDER BY u.id LIMIT 1) AS login_username
        FROM members m
        LEFT JOIN families f ON f.id = m.family_id
        $where
        ORDER BY (m.member_type = 'child'), f.name ASC, m.name ASC";
$result = mysqli_query($conn, $sql);
$members = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
$member_count = count($members);

// Count active members and children in the result.
$active_count = 0;
$child_count = 0;
foreach ($members as $member) {
    if ($member['status'] === 'active') {
        $active_count++;
    }
    if ($member['member_type'] === 'child') {
        $child_count++;
    }
}

// Family totals: relatives save together, so show one total per family.
$result = mysqli_query($conn, "SELECT f.id, f.name, f.guardian_name,
                                      COUNT(DISTINCT m.id) AS member_count,
                                      COALESCE(SUM(c.amount), 0) AS total_saved
                               FROM families f
                               LEFT JOIN members m ON m.family_id = f.id
                               LEFT JOIN contributions c ON c.member_id = m.id
                               GROUP BY f.id, f.name, f.guardian_name
                               ORDER BY total_saved DESC, f.name ASC");
$families = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

$family_total = 0;
$family_totals_by_id = array();
foreach ($families as $family) {
    $family_total += (float) $family['total_saved'];
    $family_totals_by_id[(int) $family['id']] = (float) $family['total_saved'];
}

// Individual members have no family; their own total is the family-level total.
$result = mysqli_query($conn, "SELECT m.id, m.name, m.phone, m.member_type,
                                      COALESCE(SUM(c.amount), 0) AS total_saved
                               FROM members m
                               LEFT JOIN contributions c ON c.member_id = m.id
                               WHERE m.family_id IS NULL
                               GROUP BY m.id, m.name, m.phone, m.member_type
                               ORDER BY total_saved DESC, m.name ASC");
$individuals = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

$individual_total = 0;
$individual_totals_by_id = array();
foreach ($individuals as $individual) {
    $individual_total += (float) $individual['total_saved'];
    $individual_totals_by_id[(int) $individual['id']] = (float) $individual['total_saved'];
}

$group_total = $family_total + $individual_total;

// Loans issued, including who received each loan.
$result = mysqli_query($conn, "SELECT l.id, l.member_id, l.amount, l.interest_percent,
                                      l.loan_date, l.due_date, l.status,
                                      m.name, m.member_type,
                                      f.id AS family_id, f.name AS family_name,
                                      COALESCE((SELECT SUM(r.amount) FROM repayments r
                                                WHERE r.loan_id = l.id), 0) AS paid
                               FROM loans l
                               JOIN members m ON m.id = l.member_id
                               LEFT JOIN families f ON f.id = m.family_id
                               ORDER BY l.loan_date DESC, l.id DESC");
$loans = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

$loan_principal_total = 0;
$loan_outstanding_total = 0;
$loan_open_count = 0;
$member_open_loans = array();

foreach ($loans as $index => $loan) {
    $total_due = loan_total_due($loan['amount'], $loan['interest_percent']);
    $outstanding = max(0, $total_due - (float) $loan['paid']);

    $loans[$index]['total_due'] = $total_due;
    $loans[$index]['outstanding'] = $outstanding;
    $loan_principal_total += (float) $loan['amount'];
    $loan_outstanding_total += $outstanding;

    if ($outstanding <= 0.005) {
        $loans[$index]['state'] = 'paid';
        continue;
    }

    $loan_open_count++;
    $loans[$index]['state'] = $loan['due_date'] < date('Y-m-d') ? 'overdue' : 'active';

    $member_key = (int) $loan['member_id'];
    if (!isset($member_open_loans[$member_key])) {
        $member_open_loans[$member_key] = array(
            'amount' => 0,
            'outstanding' => 0,
            'count' => 0
        );
    }
    $member_open_loans[$member_key]['amount'] += (float) $loan['amount'];
    $member_open_loans[$member_key]['outstanding'] += $outstanding;
    $member_open_loans[$member_key]['count']++;
}

// Work out missed months from the member's join month up to last month.
$paid_months = array();
$result = mysqli_query($conn, "SELECT member_id, DATE_FORMAT(pay_date, '%Y-%m') AS month
                               FROM contributions
                               GROUP BY member_id, DATE_FORMAT(pay_date, '%Y-%m')");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $paid_months[(int) $row['member_id']][$row['month']] = true;
    }
}

$current_month = date('Y-m');
$current_month_end = date('Y-m-t');
$previous_month = date('Y-m', strtotime('first day of last month'));

foreach ($members as $index => $member) {
    $member_key = (int) $member['id'];
    $join_month = date('Y-m', strtotime($member['join_date']));
    $cursor = strtotime($join_month . '-01');
    $last_month = strtotime($previous_month . '-01');
    $missed = array();

    while ($cursor !== false && $cursor <= $last_month) {
        $month_key = date('Y-m', $cursor);
        if (!isset($paid_months[$member_key][$month_key])) {
            $missed[] = $month_key;
        }
        $cursor = strtotime('+1 month', $cursor);
    }

    $members[$index]['missed_months'] = $missed;
    $members[$index]['current_month_paid'] = isset($paid_months[$member_key][$current_month]);
    $members[$index]['current_month_expected'] = $member['join_date'] <= $current_month_end;
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Members')); ?></h1>
        <p class="muted">
            <?php
            if ($q !== '') {
                echo h(tn('%d member found', '%d members found', $member_count));
            } else {
                echo h(tn('%d member in the group', '%d members in the group', $member_count));
            }
            ?>
            &middot; <?php echo h(tn('%d active member', '%d active members', $active_count)); ?>
            &middot; <?php echo h(tn('%d child', '%d children', $child_count)); ?>
        </p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-small" href="register.php"><?php echo h(t('Register')); ?></a>
    <?php endif; ?>
</div>

<div class="hero">
    <div class="label"><?php echo h(t('Group total saved')); ?></div>
    <div class="amount"><?php echo money($group_total); ?> <small>RWF</small></div>
    <div class="hero-row">
        <span><?php echo h(tn('%d member in the group', '%d members in the group', $member_count)); ?></span>
        <span><?php echo count($families); ?> <?php echo h(t('Families')); ?></span>
        <span><?php echo count($individuals); ?> <?php echo h(t('Individual members')); ?></span>
    </div>
</div>

<div class="stats">
    <div class="stat">
        <div class="number"><?php echo money($family_total); ?></div>
        <div class="label"><?php echo h(t('Family savings (RWF)')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo money($individual_total); ?></div>
        <div class="label"><?php echo h(t('Individual savings (RWF)')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo money($loan_principal_total); ?></div>
        <div class="label"><?php echo h(t('Loans issued (RWF)')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo money($loan_outstanding_total); ?></div>
        <div class="label"><?php echo h(t('Loans outstanding (RWF)')); ?></div>
    </div>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Savings by family or individual')); ?></h2>
        <a class="btn btn-secondary btn-small" href="balances.php"><?php echo h(t('Balances')); ?></a>
    </div>

    <?php if (count($families) === 0 && count($individuals) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No members yet.')); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn" href="register.php"><?php echo h(t('Register the first member')); ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php if (count($families) > 0): ?>
            <div class="section-head" style="margin-top:14px;">
                <h2><?php echo h(t('Families')); ?></h2>
                <span class="badge neutral"><?php echo count($families); ?></span>
            </div>
            <div class="list">
                <?php foreach ($families as $family): ?>
                    <a class="list-row" href="family_view.php?id=<?php echo (int) $family['id']; ?>">
                        <div class="avatar-sm"><?php echo h(initials($family['name'])); ?></div>
                        <div class="list-main">
                            <div class="list-title">
                                <?php echo h($family['name']); ?>
                                <span class="badge neutral"><?php echo h(tn('%d member', '%d members', (int) $family['member_count'])); ?></span>
                            </div>
                            <div class="list-sub">
                                <?php echo h(t('Parent or guardian')); ?>: <?php echo h($family['guardian_name']); ?>
                            </div>
                        </div>
                        <div class="list-side"><?php echo money($family['total_saved']); ?><small>RWF</small></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (count($individuals) > 0): ?>
            <div class="section-head" style="margin-top:18px;">
                <h2><?php echo h(t('Individual members')); ?></h2>
                <span class="badge neutral"><?php echo count($individuals); ?></span>
            </div>
            <div class="list">
                <?php foreach ($individuals as $individual): ?>
                    <div class="list-row">
                        <div class="avatar-sm"><?php echo h(initials($individual['name'])); ?></div>
                        <div class="list-main">
                            <div class="list-title">
                                <?php echo h($individual['name']); ?>
                                <?php if ($individual['member_type'] === 'child'): ?>
                                    <span class="badge neutral"><?php echo h(t('Child')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="list-sub">
                                <?php echo $individual['phone'] ? h($individual['phone']) : h(t('No phone')); ?>
                            </div>
                        </div>
                        <div class="list-side"><?php echo money($individual['total_saved']); ?><small>RWF</small></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="card">
    <form class="filter-form" method="get" action="members.php">
        <div class="form-group">
            <label for="q"><?php echo h(t('Search by name or phone')); ?></label>
            <input type="search" id="q" name="q" value="<?php echo h($q); ?>"
                   placeholder="<?php echo h(t('e.g. Jackson or 0790')); ?>" autocomplete="off">
        </div>
        <button class="btn btn-secondary" type="submit"><?php echo h(t('Search')); ?></button>
        <?php if ($q !== ''): ?>
            <a class="btn btn-secondary" href="members.php"><?php echo h(t('Clear')); ?></a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
<?php if ($member_count === 0): ?>
    <div class="empty">
        <?php if ($q !== ''): ?>
            <p><?php echo h(t('No members match "%s".', $q)); ?></p>
            <a class="btn btn-secondary btn-small" href="members.php"><?php echo h(t('Clear search')); ?></a>
        <?php else: ?>
            <p><?php echo h(t('No members yet.')); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn" href="register.php"><?php echo h(t('Register the first member')); ?></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="list">
        <?php foreach ($members as $member): ?>
            <?php
            $member_key = (int) $member['id'];
            $member_family_total = $member['family_id'] && isset($family_totals_by_id[(int) $member['family_id']])
                ? $family_totals_by_id[(int) $member['family_id']]
                : 0;
            $member_individual_total = isset($individual_totals_by_id[$member_key])
                ? $individual_totals_by_id[$member_key]
                : 0;
            ?>
            <div class="list-row">
                <div class="avatar-sm"><?php echo h(initials($member['name'])); ?></div>
                <div class="list-main">
                    <div class="list-title">
                        <?php echo h($member['name']); ?>
                        <span class="badge <?php echo $member['status'] === 'active' ? '' : 'danger'; ?>">
                            <?php echo h(t($member['status'] === 'active' ? 'Active' : 'Inactive')); ?>
                        </span>
                        <?php if ($member['member_type'] === 'child'): ?>
                            <span class="badge neutral"><?php echo h(t('Child')); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="list-sub">
                        <?php echo $member['phone'] ? h($member['phone']) : h(t('No phone')); ?>
                        &middot; <?php echo h(t('joined %s', app_date('j M Y', strtotime($member['join_date'])))); ?>
                    </div>

                    <?php if ($member['family_id'] && $member['family_name']): ?>
                        <div class="list-sub">
                            <?php echo h(t('Family')); ?>:
                            <a href="family_view.php?id=<?php echo (int) $member['family_id']; ?>"><?php echo h($member['family_name']); ?></a>
                            &middot; <?php echo h(t('Family total')); ?>:
                            <strong><?php echo money($member_family_total); ?> RWF</strong>
                        </div>
                    <?php else: ?>
                        <div class="list-sub">
                            <?php echo h(t('Individual member')); ?>
                            &middot; <?php echo h(t('Total saved')); ?>:
                            <strong><?php echo money($member_individual_total); ?> RWF</strong>
                        </div>
                    <?php endif; ?>

                    <div class="list-sub">
                        <?php if (count($member['missed_months']) > 0): ?>
                            <?php echo h(t('Missed months (%d):', count($member['missed_months']))); ?>
                            <?php
                            $missed_labels = array();
                            foreach ($member['missed_months'] as $missed_month) {
                                $missed_labels[] = app_date('M Y', strtotime($missed_month . '-01'));
                            }
                            echo h(implode(', ', $missed_labels));
                            ?>
                        <?php else: ?>
                            <?php echo h(t('No missed months')); ?>
                        <?php endif; ?>
                        <?php if ($member['current_month_expected'] && !$member['current_month_paid']): ?>
                            &middot; <span class="badge warn"><?php echo h(t('This month not paid yet')); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($member_open_loans[$member_key])): ?>
                        <?php $open_loan = $member_open_loans[$member_key]; ?>
                        <div class="list-sub">
                            <?php echo h(t('Loan issued')); ?>:
                            <strong><?php echo money($open_loan['amount']); ?> RWF</strong>
                            &middot; <?php echo h(t('outstanding')); ?>:
                            <strong><?php echo money($open_loan['outstanding']); ?> RWF</strong>
                            &middot; <?php echo h(t('Open loans')); ?>: <?php echo (int) $open_loan['count']; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (is_admin() && $member['member_type'] !== 'child'): ?>
                        <div class="list-sub">
                            <?php if ($member['login_username']): ?>
                                <?php echo h(t('Login:')); ?> @<?php echo h($member['login_username']); ?>
                            <?php else: ?>
                                <?php echo h(t('No login account')); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (is_admin()): ?>
                        <div class="row-actions">
                            <?php if ($member['family_id']): ?>
                                <a class="btn btn-secondary btn-small"
                                   href="family_member_edit.php?id=<?php echo (int) $member['id']; ?>"><?php echo h(t('Edit')); ?></a>
                                <a class="btn btn-secondary btn-small"
                                   href="family_view.php?id=<?php echo (int) $member['family_id']; ?>"><?php echo h(t('View family')); ?></a>
                            <?php else: ?>
                                <a class="btn btn-secondary btn-small"
                                   href="edit_member.php?id=<?php echo (int) $member['id']; ?>"><?php echo h(t('Edit')); ?></a>
                            <?php endif; ?>
                            <?php if ($member['member_type'] !== 'child'): ?>
                                <a class="btn btn-secondary btn-small"
                                   href="member_login.php?member_id=<?php echo (int) $member['id']; ?>">
                                    <?php echo h(t($member['login_username'] ? 'Manage login' : 'Create login')); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Loans issued')); ?></h2>
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
            <?php foreach ($loans as $loan): ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(initials($loan['name'])); ?></div>
                    <div class="list-main">
                        <div class="list-title">
                            <?php echo h(t('Issued to: %s', $loan['name'])); ?>
                            <?php if ($loan['state'] === 'paid'): ?>
                                <span class="badge"><?php echo h(t('Paid')); ?></span>
                            <?php elseif ($loan['state'] === 'overdue'): ?>
                                <span class="badge danger"><?php echo h(t('Overdue')); ?></span>
                            <?php else: ?>
                                <span class="badge warn"><?php echo h(t('Active')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="list-sub">
                            <?php if ($loan['family_name']): ?>
                                <?php echo h(t('Family')); ?>: <?php echo h($loan['family_name']); ?>
                            <?php else: ?>
                                <?php echo h(t('Individual member')); ?>
                            <?php endif; ?>
                            &middot; <?php echo h(t('Issued %s', app_date('j M Y', strtotime($loan['loan_date'])))); ?>
                            &middot; <?php echo h(t('due %s', app_date('j M Y', strtotime($loan['due_date'])))); ?>
                        </div>
                        <div class="list-sub">
                            <?php echo h(t('Loan amount')); ?>:
                            <strong><?php echo money($loan['amount']); ?> RWF</strong>
                            &middot; <?php echo number_format((float) $loan['interest_percent'], 2); ?>%
                            <?php echo h(t('interest, charged once')); ?>
                            &middot; <?php echo h(t('Total to repay')); ?>:
                            <strong><?php echo money($loan['total_due']); ?> RWF</strong>
                        </div>
                        <div class="list-sub">
                            <?php echo h(t('Paid %s of %s RWF', money($loan['paid']), money($loan['total_due']))); ?>
                        </div>
                    </div>
                    <div class="list-side"><?php echo money($loan['outstanding']); ?><small><?php echo h(t('outstanding')); ?></small></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
