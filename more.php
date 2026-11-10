<?php
// more.php - account details and links to the extra pages.
require_once 'auth.php';
require_login();

$page_title = 'More';
$active = 'more';

$settings = get_setting();

// The member record behind a member login (admins have none).
$my_id = current_member_id();
$my_name = '';
if ($my_id > 0) {
    $my_name = (string) db_value("SELECT name FROM members WHERE id = $my_id LIMIT 1", '');
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('More')); ?></h1>
        <p class="muted"><?php echo h(t('Your account and group tools')); ?></p>
    </div>
</div>

<div class="card">
    <div class="list-row">
        <div class="avatar-sm" style="width:54px;height:54px;font-size:18px;">
            <?php echo h(initials($_SESSION['username'])); ?>
        </div>
        <div class="list-main">
            <div class="list-title">
                <?php echo h($_SESSION['username']); ?>
                <span class="badge <?php echo is_admin() ? '' : 'neutral'; ?>">
                    <?php echo h(t(is_admin() ? 'Secretary' : 'Member')); ?>
                </span>
            </div>
            <div class="list-sub">
                <?php echo $my_name !== '' ? h($my_name) : h(t(is_admin() ? 'Group administrator' : 'Member')); ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('Money')); ?></h2></div>
    <div class="list">
        <a class="list-row" href="balances.php">
            <div class="avatar-sm"><?php echo nav_icon('balance'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Balances')); ?></div>
                <div class="list-sub"><?php echo h(t('How much each member has saved')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
        <a class="list-row" href="contributions.php">
            <div class="avatar-sm"><?php echo nav_icon('money'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Contributions')); ?></div>
                <div class="list-sub"><?php echo h(t('Monthly payments and who has not paid')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
        <a class="list-row" href="loans.php">
            <div class="avatar-sm"><?php echo nav_icon('loans'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Loans')); ?></div>
                <div class="list-sub"><?php echo h(t('Money lent and repayments')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
        <a class="list-row" href="penalties.php">
            <div class="avatar-sm"><?php echo nav_icon('penalty'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Penalties')); ?></div>
                <div class="list-sub"><?php echo h(t('Fines charged to members')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('Group')); ?></h2></div>
    <div class="list">
        <a class="list-row" href="group_report.php">
            <div class="avatar-sm"><?php echo nav_icon('balance'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Group report')); ?></div>
                <div class="list-sub"><?php echo h(t('Overview of the whole group\'s savings')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
        <a class="list-row" href="members.php">
            <div class="avatar-sm"><?php echo nav_icon('members'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Members')); ?></div>
                <div class="list-sub"><?php echo h(t('People in the savings group')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
        <a class="list-row" href="families.php">
            <div class="avatar-sm"><?php echo nav_icon('family'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Families')); ?></div>
                <div class="list-sub"><?php echo h(t('Register families and their members')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
        <a class="list-row" href="meetings.php">
            <div class="avatar-sm"><?php echo nav_icon('calendar'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Meetings')); ?></div>
                <div class="list-sub"><?php echo h(t('Schedule and attendance')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
        <?php if (is_admin()): ?>
            <a class="list-row" href="settings.php">
                <div class="avatar-sm"><?php echo nav_icon('settings'); ?></div>
                <div class="list-main">
                    <div class="list-title"><?php echo h(t('Group settings')); ?></div>
                    <div class="list-sub"><?php echo h(t('Group name, contribution and interest')); ?></div>
                </div>
                <span class="chevron">&rsaquo;</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('Account')); ?></h2></div>
    <div class="list">
        <a class="list-row" href="change_password.php">
            <div class="avatar-sm"><?php echo nav_icon('settings'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Change password')); ?></div>
                <div class="list-sub"><?php echo h(t('Keep your account safe')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
        <a class="list-row" href="logout.php">
            <div class="avatar-sm" style="background:#fee2e2;color:#b91c1c;"><?php echo nav_icon('logout'); ?></div>
            <div class="list-main">
                <div class="list-title" style="color:#b91c1c;"><?php echo h(t('Log out')); ?></div>
                <div class="list-sub"><?php echo h(t('End this session')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('Group information')); ?></h2></div>
    <div class="key-value">
        <div><span><?php echo h(t('Group')); ?></span><span><?php echo h($settings['group_name']); ?></span></div>
        <div><span><?php echo h(t('Monthly contribution')); ?></span><span><?php echo (float) $settings['contribution_amount'] > 0 ? money($settings['contribution_amount']) . ' RWF' : h(t('Not set')); ?></span></div>
        <div><span><?php echo h(t('Default loan interest')); ?></span><span><?php echo number_format((float) $settings['loan_interest_percent'], 2); ?>%</span></div>
    </div>
    <p class="hint" style="margin-top:14px;">
        <?php echo h(t('Ikimina works best on a phone. Add it to your home screen from the browser menu.')); ?>
    </p>
</div>
<?php require 'footer.php'; ?>
