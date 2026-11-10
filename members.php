<?php
// members.php - lists and searches members; the secretary can add or edit.
require_once 'auth.php';
require_login();

$page_title = 'Members';
$active = 'members';

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
                        </div>
                    <?php endif; ?>
                    <div class="list-sub">
                        <?php echo h(t('Total saved')); ?>:
                        <strong><?php echo money($member['total_saved']); ?> RWF</strong>
                    </div>
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
<?php require 'footer.php'; ?>
