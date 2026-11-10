<?php
// balances.php - total saved by each member across all contributions.
require_once 'auth.php';
require_login();

$page_title = 'Balances';
$active = 'balances';

// Sum every contribution for each member.
$result = mysqli_query($conn, "SELECT m.id, m.name, m.phone, m.join_date, m.member_type,
                                      f.id AS family_id, f.name AS family_name,
                                      COALESCE(SUM(c.amount), 0) AS total_saved,
                                      COUNT(c.id) AS payment_count,
                                      MAX(c.pay_date) AS last_payment
                               FROM members m
                               LEFT JOIN families f ON f.id = m.family_id
                               LEFT JOIN contributions c ON c.member_id = m.id
                               GROUP BY m.id, m.name, m.phone, m.join_date, m.member_type, f.id, f.name
                               ORDER BY total_saved DESC, m.name ASC");
$members = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
$member_count = count($members);

// Group total = sum of every member's balance, with a separate children total.
$group_total = 0;
$children_total = 0;
$children_count = 0;
foreach ($members as $member) {
    $group_total += (float) $member['total_saved'];
    if ($member['member_type'] === 'child') {
        $children_total += (float) $member['total_saved'];
        $children_count++;
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Balances')); ?></h1>
        <p class="muted"><?php echo h(t('Total saved by each member')); ?></p>
    </div>
</div>

<div class="hero">
    <div class="label"><?php echo h(t('Group total saved')); ?></div>
    <div class="amount"><?php echo money($group_total); ?> <small>RWF</small></div>
    <div class="hero-row">
        <span><?php echo h(tn('%d member in the group', '%d members in the group', $member_count)); ?></span>
        <span><?php echo h(t('Children\'s savings')); ?> <strong><?php echo money($children_total); ?> RWF</strong></span>
    </div>
</div>

<div class="card">
<?php if ($member_count === 0): ?>
    <div class="empty">
        <p><?php echo h(t('No members yet.')); ?></p>
        <?php if (is_admin()): ?>
            <a class="btn" href="add_member.php"><?php echo h(t('Add the first member')); ?></a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="list">
        <?php foreach ($members as $member): ?>
            <div class="list-row">
                <div class="avatar-sm"><?php echo h(initials($member['name'])); ?></div>
                <div class="list-main">
                    <div class="list-title">
                        <?php echo h($member['name']); ?>
                        <?php if ($member['member_type'] === 'child'): ?>
                            <span class="badge neutral"><?php echo h(t('Child')); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="list-sub"><?php echo $member['phone'] ? h($member['phone']) : h(t('No phone')); ?></div>
                    <?php if ($member['family_id'] && $member['family_name']): ?>
                        <div class="list-sub">
                            <?php echo h(t('Family')); ?>:
                            <a href="family_view.php?id=<?php echo (int) $member['family_id']; ?>"><?php echo h($member['family_name']); ?></a>
                        </div>
                    <?php endif; ?>
                    <div class="list-sub">
                        <?php echo h(tn('%d payment', '%d payments', (int) $member['payment_count'])); ?>
                        <?php if ($member['last_payment']): ?>
                            &middot; <?php echo h(t('last %s', app_date('j M Y', strtotime($member['last_payment'])))); ?>
                        <?php else: ?>
                            &middot; <?php echo h(t('no payments yet')); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="list-side"><?php echo money($member['total_saved']); ?><small>RWF</small></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
<?php require 'footer.php'; ?>
