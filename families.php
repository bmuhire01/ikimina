<?php
// families.php - lists families and the members who save together.
require_once 'auth.php';
require_login();

$page_title = 'Families';
$active = 'families';

// Search text from the URL.
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q_esc = mysqli_real_escape_string($conn, $q);

$where = '';
if ($q !== '') {
    $where = "WHERE f.name LIKE '%$q_esc%'
              OR f.guardian_name LIKE '%$q_esc%'
              OR f.phone LIKE '%$q_esc%'
              OR EXISTS (
                  SELECT 1 FROM members cm
                  WHERE cm.family_id = f.id
                  AND cm.name LIKE '%$q_esc%'
              )";
}

// Each family with its members and their savings.
$sql = "SELECT f.id, f.name, f.guardian_name, f.phone, f.join_date,
               COUNT(DISTINCT m.id) AS member_count,
               COUNT(c.id) AS payment_count,
               COALESCE(SUM(c.amount), 0) AS total_saved
        FROM families f
        LEFT JOIN members m ON m.family_id = f.id
        LEFT JOIN contributions c ON c.member_id = m.id
        $where
        GROUP BY f.id, f.name, f.guardian_name, f.phone, f.join_date
        ORDER BY f.name ASC";
$result = mysqli_query($conn, $sql);
$families = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
$family_count = count($families);

// Add up the family members and savings in the result.
$member_total = 0;
$saved_total = 0;
foreach ($families as $family) {
    $member_total += (int) $family['member_count'];
    $saved_total += (float) $family['total_saved'];
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Families')); ?></h1>
        <p class="muted">
            <?php
            if ($q !== '') {
                echo h(tn('%d family found', '%d families found', $family_count));
            } else {
                echo h(tn('%d family', '%d families', $family_count));
            }
            ?>
            &middot; <?php echo h(tn('%d member', '%d members', $member_total)); ?>
        </p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-small" href="family_add.php"><?php echo h(t('Register family')); ?></a>
    <?php endif; ?>
</div>

<div class="hero">
    <div class="label"><?php echo h(t('Family savings')); ?></div>
    <div class="amount"><?php echo money($saved_total); ?> <small>RWF</small></div>
    <div class="hero-row">
        <span><?php echo h(tn('%d family', '%d families', $family_count)); ?></span>
        <span><?php echo h(tn('%d family member', '%d family members', $member_total)); ?></span>
    </div>
</div>

<div class="card">
    <form class="filter-form" method="get" action="families.php">
        <div class="form-group">
            <label for="q"><?php echo h(t('Search by family, guardian or member')); ?></label>
            <input type="search" id="q" name="q" value="<?php echo h($q); ?>"
                   placeholder="<?php echo h(t('e.g. Uwimana or 0790')); ?>" autocomplete="off">
        </div>
        <button class="btn btn-secondary" type="submit"><?php echo h(t('Search')); ?></button>
        <?php if ($q !== ''): ?>
            <a class="btn btn-secondary" href="families.php"><?php echo h(t('Clear')); ?></a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
<?php if ($family_count === 0): ?>
    <div class="empty">
        <?php if ($q !== ''): ?>
            <p><?php echo h(t('No families match "%s".', $q)); ?></p>
            <a class="btn btn-secondary btn-small" href="families.php"><?php echo h(t('Clear search')); ?></a>
        <?php else: ?>
            <p><?php echo h(t('No families yet.')); ?></p>
            <p class="hint"><?php echo h(t('Register a family, then add their family members.')); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn" href="family_add.php"><?php echo h(t('Register the first family')); ?></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="list">
        <?php foreach ($families as $family): ?>
            <a class="list-row" href="family_view.php?id=<?php echo (int) $family['id']; ?>">
                <div class="avatar-sm"><?php echo h(initials($family['name'])); ?></div>
                <div class="list-main">
                    <div class="list-title"><?php echo h($family['name']); ?></div>
                    <div class="list-sub">
                        <?php echo h($family['guardian_name']); ?>
                        &middot; <?php echo $family['phone'] ? h($family['phone']) : h(t('No phone')); ?>
                    </div>
                    <div class="list-sub">
                        <?php echo h(tn('%d member', '%d members', (int) $family['member_count'])); ?>
                        &middot; <?php echo h(tn('%d payment', '%d payments', (int) $family['payment_count'])); ?>
                    </div>
                </div>
                <div class="list-side"><?php echo money($family['total_saved']); ?><small>RWF</small></div>
                <span class="chevron">&rsaquo;</span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
<?php require 'footer.php'; ?>
