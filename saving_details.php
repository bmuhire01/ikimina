<?php
// saving_details.php - month-by-month contributions for one family or person.
require_once 'auth.php';
require_login();

$page_title = 'Contribution details';
$active = 'members';

$type = isset($_GET['type']) && $_GET['type'] === 'family' ? 'family' : 'member';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
if ($year < 1900 || $year > 2100) {
    $year = (int) date('Y');
}

$profile = null;
$member_count = 0;

if ($type === 'family') {
    $result = mysqli_query($conn, "SELECT f.id, f.name, f.guardian_name, f.phone, f.join_date,
                                          COALESCE(MIN(m.join_date), f.join_date) AS savings_start,
                                          COUNT(DISTINCT m.id) AS member_count,
                                          COALESCE(SUM(c.amount), 0) AS total_saved
                                   FROM families f
                                   LEFT JOIN members m ON m.family_id = f.id
                                   LEFT JOIN contributions c ON c.member_id = m.id
                                   WHERE f.id = $id
                                   GROUP BY f.id, f.name, f.guardian_name, f.phone, f.join_date
                                   LIMIT 1");
    $profile = $result ? mysqli_fetch_assoc($result) : null;
    $member_count = $profile ? (int) $profile['member_count'] : 0;
} else {
    $result = mysqli_query($conn, "SELECT m.id, m.name, m.phone, m.join_date, m.member_type,
                                          f.name AS family_name,
                                          COALESCE(SUM(c.amount), 0) AS total_saved
                                   FROM members m
                                   LEFT JOIN families f ON f.id = m.family_id
                                   LEFT JOIN contributions c ON c.member_id = m.id
                                   WHERE m.id = $id
                                   GROUP BY m.id, m.name, m.phone, m.join_date, m.member_type, f.name
                                   LIMIT 1");
    $profile = $result ? mysqli_fetch_assoc($result) : null;
}

if (!$profile) {
    set_flash('error', t('Savings details not found.'));
    header('Location: members.php');
    exit;
}

$year_start = sprintf('%04d-01-01', $year);
$year_end = sprintf('%04d-12-31', $year);

// Total paid in each month of the chosen year.
if ($type === 'family') {
    $result = mysqli_query($conn, "SELECT MONTH(c.pay_date) AS month_no,
                                          COALESCE(SUM(c.amount), 0) AS paid
                                   FROM contributions c
                                   JOIN members m ON m.id = c.member_id
                                   WHERE m.family_id = $id
                                   AND c.pay_date BETWEEN '$year_start' AND '$year_end'
                                   GROUP BY MONTH(c.pay_date)");
} else {
    $result = mysqli_query($conn, "SELECT MONTH(c.pay_date) AS month_no,
                                          COALESCE(SUM(c.amount), 0) AS paid
                                   FROM contributions c
                                   WHERE c.member_id = $id
                                   AND c.pay_date BETWEEN '$year_start' AND '$year_end'
                                   GROUP BY MONTH(c.pay_date)");
}

$monthly = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $monthly[(int) $row['month_no']] = (float) $row['paid'];
    }
}

// Years that can be viewed: the join year, contribution years, current year and chosen year.
$years = array((int) date('Y'), (int) date('Y', strtotime($profile['join_date'])), $year);
if ($type === 'family') {
    $result = mysqli_query($conn, "SELECT DISTINCT YEAR(c.pay_date) AS y
                                   FROM contributions c
                                   JOIN members m ON m.id = c.member_id
                                   WHERE m.family_id = $id");
} else {
    $result = mysqli_query($conn, "SELECT DISTINCT YEAR(c.pay_date) AS y
                                   FROM contributions c
                                   WHERE c.member_id = $id");
}
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $years[] = (int) $row['y'];
    }
}
$years = array_values(array_unique($years));
$years = array_values(array_filter($years, function ($y) {
    return $y >= 1900 && $y <= 2100;
}));
rsort($years);

$no_people = $type === 'family' && $member_count === 0;
$join_date = $profile['join_date'];
$savings_start = $type === 'family' ? $profile['savings_start'] : $profile['join_date'];
$current_month_start = date('Y-m-01');

$year_total = 0;
$paid_months_count = 0;
$unpaid_months_count = 0;
$months = array();

for ($month_no = 1; $month_no <= 12; $month_no++) {
    $month_start = sprintf('%04d-%02d-01', $year, $month_no);
    $month_end = date('Y-m-t', strtotime($month_start));
    $paid = isset($monthly[$month_no]) ? $monthly[$month_no] : 0;

    if ($no_people) {
        $state = 'empty';
    } elseif ($month_end < $savings_start) {
        $state = 'not_joined';
    } elseif ($month_start > $current_month_start) {
        $state = 'future';
    } elseif ($paid > 0) {
        $state = 'paid';
    } else {
        $state = 'unpaid';
    }

    if ($state === 'paid') {
        $year_total += $paid;
        $paid_months_count++;
    } elseif ($state === 'unpaid') {
        $unpaid_months_count++;
    }

    $months[] = array(
        'label' => app_date('F', strtotime($month_start)),
        'paid' => $paid,
        'state' => $state
    );
}

$type_label = $type === 'family' ? t('Family') : t('Individual');

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Contribution details')); ?></h1>
        <p class="muted">
            <?php echo h($type_label); ?> &middot; <?php echo h($profile['name']); ?>
            <?php if ($type === 'family'): ?>
                &middot; <?php echo h(t('Parent: %s', $profile['guardian_name'])); ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="row-actions" style="margin-top:0;">
        <?php if (is_admin()): ?>
            <?php if ($type === 'family'): ?>
                <a class="btn btn-small" href="family_view.php?id=<?php echo (int) $id; ?>"><?php echo h(t('Family page')); ?></a>
            <?php else: ?>
                <a class="btn btn-small" href="edit_member.php?id=<?php echo (int) $id; ?>"><?php echo h(t('Edit person')); ?></a>
            <?php endif; ?>
        <?php endif; ?>
        <a class="btn btn-secondary btn-small" href="members.php"><?php echo h(t('Back to members')); ?></a>
    </div>
</div>

<div class="hero">
    <div class="label"><?php echo h(t('Total saved')); ?></div>
    <div class="amount"><?php echo money($profile['total_saved']); ?> <small>RWF</small></div>
    <div class="hero-row">
        <?php if ($type === 'family'): ?>
            <span><?php echo h(tn('%d person', '%d people', $member_count)); ?></span>
        <?php else: ?>
            <span><?php echo h($profile['phone'] ? $profile['phone'] : t('No phone')); ?></span>
        <?php endif; ?>
        <span><?php echo h(t('Joined %s', app_date('j M Y', strtotime($join_date)))); ?></span>
    </div>
</div>

<div class="stats">
    <div class="stat">
        <div class="number"><?php echo money($year_total); ?></div>
        <div class="label"><?php echo h(t('Paid in %d (RWF)', $year)); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $paid_months_count; ?></div>
        <div class="label"><?php echo h($paid_months_count === 1 ? t('Month paid') : t('Months paid')); ?></div>
    </div>
    <div class="stat">
        <div class="number"><?php echo $unpaid_months_count; ?></div>
        <div class="label"><?php echo h($unpaid_months_count === 1 ? t('Month not paid') : t('Months not paid')); ?></div>
    </div>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Months in %d', $year)); ?></h2>
        <form class="year-form" method="get" action="saving_details.php">
            <input type="hidden" name="type" value="<?php echo h($type); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
            <select name="year" aria-label="<?php echo h(t('Year')); ?>" onchange="this.form.submit()">
                <?php foreach ($years as $option_year): ?>
                    <option value="<?php echo (int) $option_year; ?>" <?php echo $option_year === $year ? 'selected' : ''; ?>>
                        <?php echo (int) $option_year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($no_people): ?>
        <div class="alert error"><?php echo h(t('This family has no people yet.')); ?></div>
    <?php endif; ?>

    <p class="hint">
        <?php echo h($type === 'family'
            ? t('Total paid by the family each month.')
            : t('Total paid by this person each month.')); ?>
    </p>
    <p class="hint"><?php echo h(t('Red months have no payment.')); ?></p>

    <div class="table-wrap">
        <table class="data-table month-table">
            <thead>
                <tr>
                    <th><?php echo h(t('Month')); ?></th>
                    <th class="num"><?php echo h(t('Paid (RWF)')); ?></th>
                    <th><?php echo h(t('Status')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($months as $month): ?>
                    <tr class="month-<?php echo h($month['state']); ?>">
                        <td class="name"><?php echo h($month['label']); ?></td>
                        <td class="num">
                            <?php echo $month['paid'] > 0 ? money($month['paid']) : '&mdash;'; ?>
                        </td>
                        <td>
                            <?php if ($month['state'] === 'paid'): ?>
                                <span class="badge"><?php echo h(t('Paid')); ?></span>
                            <?php elseif ($month['state'] === 'unpaid'): ?>
                                <span class="badge danger"><?php echo h(t('Not paid')); ?></span>
                            <?php elseif ($month['state'] === 'future'): ?>
                                <span class="badge neutral"><?php echo h(t('Not due yet')); ?></span>
                            <?php elseif ($month['state'] === 'empty'): ?>
                                <span class="badge neutral"><?php echo h(t('No people yet')); ?></span>
                            <?php else: ?>
                                <span class="badge neutral"><?php echo h(t('Not yet joined')); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require 'footer.php'; ?>
