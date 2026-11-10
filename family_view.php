<?php
// family_view.php - one family and the members who save with it.
require_once 'auth.php';
require_login();

$page_title = 'Family';
$active = 'families';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id < 1) {
    set_flash('error', t('Family not found.'));
    header('Location: families.php');
    exit;
}

// Load the family.
$result = mysqli_query($conn, "SELECT id, name, guardian_name, phone, join_date
                               FROM families
                               WHERE id = $id
                               LIMIT 1");
$family = $result ? mysqli_fetch_assoc($result) : null;

if (!$family) {
    set_flash('error', t('Family not found.'));
    header('Location: families.php');
    exit;
}

// Family members with their savings.
$result = mysqli_query($conn, "SELECT m.id, m.name, m.phone, m.join_date, m.date_of_birth,
                                      m.member_type, m.status,
                                      COALESCE(SUM(c.amount), 0) AS total_saved,
                                      COUNT(c.id) AS payment_count,
                                      MAX(c.pay_date) AS last_payment
                               FROM members m
                               LEFT JOIN contributions c ON c.member_id = m.id
                               WHERE m.family_id = $id
                               GROUP BY m.id, m.name, m.phone, m.join_date, m.date_of_birth, m.member_type, m.status
                               ORDER BY (m.member_type = 'child'), m.name ASC");
$members = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
$member_count = count($members);

$family_saved = 0;
$payment_count = 0;
foreach ($members as $member) {
    $family_saved += (float) $member['total_saved'];
    $payment_count += (int) $member['payment_count'];
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h($family['name']); ?></h1>
        <p class="muted">
            <?php echo h(t('Parent or guardian')); ?>: <?php echo h($family['guardian_name']); ?>
            &middot; <?php echo h(tn('%d family member', '%d family members', $member_count)); ?>
        </p>
    </div>
    <div class="row-actions" style="margin-top:0;">
        <?php if (is_admin()): ?>
            <a class="btn btn-secondary btn-small" href="family_edit.php?id=<?php echo $id; ?>"><?php echo h(t('Edit family')); ?></a>
        <?php endif; ?>
        <a class="btn btn-secondary btn-small" href="families.php"><?php echo h(t('Back to families')); ?></a>
    </div>
</div>

<div class="hero">
    <div class="label"><?php echo h(t('Family savings')); ?></div>
    <div class="amount"><?php echo money($family_saved); ?> <small>RWF</small></div>
    <div class="hero-row">
        <span><?php echo h(tn('%d family member', '%d family members', $member_count)); ?></span>
        <span><?php echo h(tn('%d payment', '%d payments', $payment_count)); ?></span>
    </div>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('Family information')); ?></h2></div>
    <div class="key-value">
        <div><span><?php echo h(t('Family name')); ?></span><span><?php echo h($family['name']); ?></span></div>
        <div><span><?php echo h(t('Parent or guardian')); ?></span><span><?php echo h($family['guardian_name']); ?></span></div>
        <div><span><?php echo h(t('Phone')); ?></span><span><?php echo $family['phone'] ? h($family['phone']) : h(t('No phone')); ?></span></div>
        <div><span><?php echo h(t('Registration date')); ?></span><span><?php echo h(app_date('j M Y', strtotime($family['join_date']))); ?></span></div>
    </div>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('Family members')); ?></h2>
        <?php if (is_admin()): ?>
            <a class="btn btn-small" href="family_member_add.php?family_id=<?php echo $id; ?>"><?php echo h(t('+ Add member')); ?></a>
        <?php endif; ?>
    </div>

    <?php if ($member_count === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No members in this family yet.')); ?></p>
            <p class="hint"><?php echo h(t('Add the members who save with this family.')); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn" href="family_member_add.php?family_id=<?php echo $id; ?>"><?php echo h(t('Add the first family member')); ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($members as $member): ?>
                <?php $age = age_from_date($member['date_of_birth']); ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(initials($member['name'])); ?></div>
                    <div class="list-main">
                        <div class="list-title">
                            <?php echo h($member['name']); ?>
                            <span class="badge <?php echo $member['status'] === 'active' ? '' : 'danger'; ?>">
                                <?php echo h(t($member['status'] === 'active' ? 'Active' : 'Inactive')); ?>
                            </span>
                            <span class="badge neutral">
                                <?php echo h(t($member['member_type'] === 'child' ? 'Child' : 'Adult')); ?>
                            </span>
                        </div>
                        <div class="list-sub">
                            <?php
                            if ($member['member_type'] === 'child') {
                                echo $age !== null ? h(t('%d years old', $age)) : h(t('No date of birth'));
                            } else {
                                echo $member['phone'] ? h($member['phone']) : h(t('No phone'));
                            }
                            ?>
                            &middot; <?php echo h(t('joined %s', app_date('j M Y', strtotime($member['join_date'])))); ?>
                        </div>
                        <div class="list-sub">
                            <?php echo h(tn('%d payment', '%d payments', (int) $member['payment_count'])); ?>
                            <?php if ($member['last_payment']): ?>
                                &middot; <?php echo h(t('last %s', app_date('j M Y', strtotime($member['last_payment'])))); ?>
                            <?php else: ?>
                                &middot; <?php echo h(t('no payments yet')); ?>
                            <?php endif; ?>
                        </div>
                        <?php if (is_admin()): ?>
                            <div class="row-actions">
                                <a class="btn btn-small"
                                   href="contribution_add.php?member_id=<?php echo (int) $member['id']; ?>"><?php echo h(t('Record saving')); ?></a>
                                <a class="btn btn-secondary btn-small"
                                   href="family_member_edit.php?id=<?php echo (int) $member['id']; ?>"><?php echo h(t('Edit family member')); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="list-side"><?php echo money($member['total_saved']); ?><small>RWF</small></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
