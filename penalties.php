<?php
// penalties.php - fines charged to members, and marking them paid.
require_once 'auth.php';
require_login();

$page_title = 'Penalties';
$active = 'penalties';

// The secretary can flip a penalty between paid and unpaid.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $action = $_POST['action'] ?? '';

    if ($id > 0 && ($action === 'pay' || $action === 'unpay')) {
        $new_status = $action === 'pay' ? 'paid' : 'unpaid';
        mysqli_query($conn, "UPDATE penalties SET status = '$new_status' WHERE id = $id LIMIT 1");
        set_flash('success', $action === 'pay' ? t('Penalty marked as paid.') : t('Penalty marked as unpaid.'));
    }

    header('Location: penalties.php');
    exit;
}

$result = mysqli_query($conn, "SELECT p.id, p.reason, p.amount, p.penalty_date, p.status,
                                      m.name, m.phone
                               FROM penalties p
                               JOIN members m ON m.id = p.member_id
                               ORDER BY (p.status = 'unpaid') DESC, p.penalty_date DESC, p.id DESC");
$penalties = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

$unpaid_total = 0;
$unpaid_count = 0;
$paid_total = 0;

foreach ($penalties as $penalty) {
    if ($penalty['status'] === 'unpaid') {
        $unpaid_total += (float) $penalty['amount'];
        $unpaid_count++;
    } else {
        $paid_total += (float) $penalty['amount'];
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Penalties')); ?></h1>
        <p class="muted">
            <?php echo h(tn('%d record', '%d records', count($penalties))); ?>
            &middot; <?php echo h(t('%d unpaid', $unpaid_count)); ?>
        </p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-small" href="penalty_add.php"><?php echo h(t('+ Add')); ?></a>
    <?php endif; ?>
</div>

<div class="hero">
    <div class="label"><?php echo h(t('Unpaid penalties')); ?></div>
    <div class="amount"><?php echo money($unpaid_total); ?> <small>RWF</small></div>
    <div class="hero-row">
        <span><?php echo h(t('%d still to pay', $unpaid_count)); ?></span>
        <span><?php echo h(t('Collected')); ?> <strong><?php echo money($paid_total); ?> RWF</strong></span>
    </div>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('All penalties')); ?></h2></div>
    <?php if (count($penalties) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No penalties have been charged.')); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn" href="penalty_add.php"><?php echo h(t('Add a penalty')); ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($penalties as $penalty): ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(initials($penalty['name'])); ?></div>
                    <div class="list-main">
                        <div class="list-title">
                            <?php echo h($penalty['name']); ?>
                            <span class="badge <?php echo $penalty['status'] === 'paid' ? '' : 'danger'; ?>">
                                <?php echo h(t($penalty['status'] === 'paid' ? 'Paid' : 'Unpaid')); ?>
                            </span>
                        </div>
                        <div class="list-sub"><?php echo h($penalty['reason']); ?></div>
                        <div class="list-sub"><?php echo h(app_date('j M Y', strtotime($penalty['penalty_date']))); ?></div>
                        <?php if (is_admin()): ?>
                            <div class="row-actions">
                                <form method="post" action="penalties.php">
                                    <input type="hidden" name="id" value="<?php echo (int) $penalty['id']; ?>">
                                    <input type="hidden" name="action"
                                           value="<?php echo $penalty['status'] === 'paid' ? 'unpay' : 'pay'; ?>">
                                    <button class="btn btn-secondary btn-small" type="submit">
                                        <?php echo h(t($penalty['status'] === 'paid' ? 'Mark unpaid' : 'Mark paid')); ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="list-side"><?php echo money($penalty['amount']); ?><small>RWF</small></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
