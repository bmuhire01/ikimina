<?php
// register.php - choose between registering one person or a whole family.
require_once 'auth.php';
require_admin();

$page_title = 'Register';
$active = 'members';

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Register')); ?></h1>
        <p class="muted"><?php echo h(t('Choose how to register')); ?></p>
    </div>
    <a class="btn btn-secondary" href="members.php"><?php echo h(t('Back to members')); ?></a>
</div>

<div class="card">
    <div class="list">
        <a class="list-row" href="add_member.php">
            <div class="avatar-sm"><?php echo nav_icon('members'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Individual person')); ?></div>
                <div class="list-sub"><?php echo h(t('Register one person with their own phone number.')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
        <a class="list-row" href="family_add.php">
            <div class="avatar-sm"><?php echo nav_icon('family'); ?></div>
            <div class="list-main">
                <div class="list-title"><?php echo h(t('Family')); ?></div>
                <div class="list-sub"><?php echo h(t('Register a family, then add the members who save together.')); ?></div>
            </div>
            <span class="chevron">&rsaquo;</span>
        </a>
    </div>
</div>

<div class="card">
    <p class="hint" style="margin:0;">
        <?php echo h(t('An individual person saves alone. A family groups a parent or guardian and the people who save together.')); ?>
    </p>
</div>
<?php require 'footer.php'; ?>
