<?php
// members.php - simple list of families and individuals with their savings.
require_once 'auth.php';
require_login();

$page_title = 'Members';
$active = 'members';

// Count members and children for the summary.
$result = mysqli_query($conn, "SELECT status, member_type FROM members");
$members = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
$member_count = count($members);

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
foreach ($families as $family) {
    $family_total += (float) $family['total_saved'];
}

// Individual members have no family.
$result = mysqli_query($conn, "SELECT m.id, m.name, m.phone, m.member_type,
                                      COALESCE(SUM(c.amount), 0) AS total_saved
                               FROM members m
                               LEFT JOIN contributions c ON c.member_id = m.id
                               WHERE m.family_id IS NULL
                               GROUP BY m.id, m.name, m.phone, m.member_type
                               ORDER BY total_saved DESC, m.name ASC");
$individuals = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

$individual_total = 0;
foreach ($individuals as $individual) {
    $individual_total += (float) $individual['total_saved'];
}

$group_total = $family_total + $individual_total;

// Build one simple list: family or person, total saved, and a details button.
$saving_entities = array();
foreach ($families as $family) {
    $people = (int) $family['member_count'];
    $meta = t('Parent: %s', $family['guardian_name']);
    $meta .= ' · ' . ($people > 0
        ? tn('%d person', '%d people', $people)
        : t('No people yet'));

    $saving_entities[] = array(
        'type' => 'family',
        'id' => (int) $family['id'],
        'name' => $family['name'],
        'meta' => $meta,
        'total_saved' => (float) $family['total_saved'],
        'is_child' => false
    );
}
foreach ($individuals as $individual) {
    $saving_entities[] = array(
        'type' => 'member',
        'id' => (int) $individual['id'],
        'name' => $individual['name'],
        'meta' => $individual['phone'] ? $individual['phone'] : t('No phone'),
        'total_saved' => (float) $individual['total_saved'],
        'is_child' => $individual['member_type'] === 'child'
    );
}
usort($saving_entities, function ($a, $b) {
    if ($a['total_saved'] == $b['total_saved']) {
        return strcasecmp($a['name'], $b['name']);
    }
    return $a['total_saved'] < $b['total_saved'] ? 1 : -1;
});

// Loans issued, including who received each loan.
$result = mysqli_query($conn, "SELECT l.id, l.member_id, l.group_id, l.amount, l.interest_percent,
                                      l.interest_amount, l.loan_date, l.due_date, l.status,
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

foreach ($loans as $index => $loan) {
    $total_due = loan_row_due($loan);
    $outstanding = max(0, $total_due - (float) $loan['paid']);

    $loans[$index]['total_due'] = $total_due;
    $loans[$index]['outstanding'] = $outstanding;
    $loan_principal_total += (float) $loan['amount'];
    $loan_outstanding_total += $outstanding;

    if ($outstanding <= 0.005) {
        $loans[$index]['state'] = 'paid';
    } else {
        $loans[$index]['state'] = $loan['due_date'] < date('Y-m-d') ? 'overdue' : 'active';
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Members')); ?></h1>
        <p class="muted">
            <?php echo h(tn('%d member in the group', '%d members in the group', $member_count)); ?>
            &middot; <?php echo h(tn('%d active member', '%d active members', $active_count)); ?>
            &middot; <?php echo h(tn('%d child', '%d children', $child_count)); ?>
        </p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-small" href="register.php"><?php echo h(t('Add member')); ?></a>
    <?php endif; ?>
</div>

<div class="hero">
    <div class="label"><?php echo h(t('Group total saved')); ?></div>
    <div class="amount"><?php echo money($group_total); ?> <small>RWF</small></div>
    <div class="hero-row">
        <span><?php echo h(tn('%d member in the group', '%d members in the group', $member_count)); ?></span>
        <span><?php echo h(tn('%d family', '%d families', count($families))); ?></span>
        <span><?php echo h(tn('%d individual', '%d individuals', count($individuals))); ?></span>
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
        <div class="label"><?php echo h(t('Money lent out (RWF)')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo money($loan_outstanding_total); ?></div>
        <div class="label"><?php echo h(t('Still to be repaid (RWF)')); ?></div>
    </div>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Savings')); ?></h2>
        <span class="badge neutral"><?php echo count($saving_entities); ?></span>
    </div>

    <?php if (count($saving_entities) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No members yet.')); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn" href="register.php"><?php echo h(t('Add the first member')); ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="table-hint"><?php echo h(t('Choose a family or person to see each month.')); ?></p>

        <div class="table-wrap">
            <table class="data-table savings-table">
                <thead>
                    <tr>
                        <th class="wrap"><?php echo h(t('Family or person')); ?></th>
                        <th class="num"><?php echo h(t('Total saved')); ?> (RWF)</th>
                        <th><?php echo h(t('Details')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saving_entities as $entity): ?>
                        <tr>
                            <td class="name wrap">
                                <?php echo h($entity['name']); ?>
                                <span class="badge neutral">
                                    <?php echo h(t($entity['type'] === 'family' ? 'Family' : 'Individual')); ?>
                                </span>
                                <?php if ($entity['is_child']): ?>
                                    <span class="badge neutral"><?php echo h(t('Child')); ?></span>
                                <?php endif; ?>
                                <span class="sub"><?php echo h($entity['meta']); ?></span>
                            </td>
                            <td class="num"><strong><?php echo money($entity['total_saved']); ?></strong> RWF</td>
                            <td>
                                <a class="btn btn-secondary btn-small"
                                   href="saving_details.php?type=<?php echo h($entity['type']); ?>&amp;id=<?php echo (int) $entity['id']; ?>">
                                    <?php echo h(t('View contributions')); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Loans given')); ?></h2>
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
        <p class="table-hint"><?php echo h(t('Swipe left to see all columns.')); ?></p>

        <div class="table-wrap">
            <table class="data-table loans-table">
                <thead>
                    <tr>
                        <th><?php echo h(t('Borrower')); ?></th>
                        <th class="wrap"><?php echo h(t('Family or person')); ?></th>
                        <th class="num"><?php echo h(t('Borrowed (RWF)')); ?></th>
                        <th><?php echo h(t('Interest (paid once)')); ?></th>
                        <th class="num"><?php echo h(t('Total to pay (RWF)')); ?></th>
                        <th class="num"><?php echo h(t('Already paid (RWF)')); ?></th>
                        <th class="num"><?php echo h(t('Still to pay (RWF)')); ?></th>
                        <th><?php echo h(t('Loan status')); ?></th>
                        <th><?php echo h(t('Date given')); ?></th>
                        <th><?php echo h(t('Due date')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td class="name">
                                <?php echo h($loan['name']); ?>
                                <?php if ($loan['group_id']): ?>
                                    <span class="badge neutral"><?php echo h(t('Group')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="wrap">
                                <?php if ($loan['family_name']): ?>
                                    <?php echo h($loan['family_name']); ?>
                                    <span class="sub"><?php echo h(t('Family')); ?></span>
                                <?php else: ?>
                                    <?php echo h(t('Individual')); ?>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?php echo money($loan['amount']); ?></td>
                            <td><?php echo number_format((float) $loan['interest_percent'], 2); ?>%</td>
                            <td class="num"><?php echo money($loan['total_due']); ?></td>
                            <td class="num"><?php echo money($loan['paid']); ?></td>
                            <td class="num"><strong><?php echo money($loan['outstanding']); ?></strong></td>
                            <td>
                                <?php if ($loan['state'] === 'paid'): ?>
                                    <span class="badge"><?php echo h(t('Paid')); ?></span>
                                <?php elseif ($loan['state'] === 'overdue'): ?>
                                    <span class="badge danger"><?php echo h(t('Overdue')); ?></span>
                                <?php else: ?>
                                    <span class="badge warn"><?php echo h(t('Open')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo h(app_date('j M Y', strtotime($loan['loan_date']))); ?></td>
                            <td><?php echo h(app_date('j M Y', strtotime($loan['due_date']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
