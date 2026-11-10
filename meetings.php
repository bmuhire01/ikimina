<?php
// meetings.php - group meetings and attendance counts.
require_once 'auth.php';
require_login();

$page_title = 'Meetings';
$active = 'meetings';

$result = mysqli_query($conn, "SELECT mt.id, mt.meeting_date, mt.place, mt.notes,
                                      (SELECT COUNT(*) FROM attendance a
                                       WHERE a.meeting_id = mt.id AND a.present = 1) AS present_count,
                                      (SELECT COUNT(*) FROM members) AS member_count
                               FROM meetings mt
                               ORDER BY mt.meeting_date DESC, mt.id DESC");
$meetings = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

$today = date('Y-m-d');
$upcoming_count = 0;
foreach ($meetings as $meeting) {
    if ($meeting['meeting_date'] >= $today) {
        $upcoming_count++;
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Meetings')); ?></h1>
        <p class="muted">
            <?php echo h(tn('%d meeting', '%d meetings', count($meetings))); ?>
            &middot; <?php echo h(t('%d upcoming', $upcoming_count)); ?>
        </p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-small" href="meeting_add.php">+ <?php echo h(t('New meeting')); ?></a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="section-head"><h2><?php echo h(t('All meetings')); ?></h2></div>
    <?php if (count($meetings) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('No meetings have been scheduled yet.')); ?></p>
            <?php if (is_admin()): ?>
                <a class="btn" href="meeting_add.php"><?php echo h(t('Schedule the first meeting')); ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($meetings as $meeting): ?>
                <?php $is_upcoming = $meeting['meeting_date'] >= $today; ?>
                <div class="list-row">
                    <div class="avatar-sm"><?php echo h(date('j', strtotime($meeting['meeting_date']))); ?></div>
                    <div class="list-main">
                        <div class="list-title">
                            <?php echo h(app_date('D, j M Y', strtotime($meeting['meeting_date']))); ?>
                            <span class="badge <?php echo $is_upcoming ? 'warn' : 'neutral'; ?>">
                                <?php echo h(t($is_upcoming ? 'Upcoming' : 'Past')); ?>
                            </span>
                        </div>
                        <div class="list-sub"><?php echo h($meeting['place']); ?></div>
                        <div class="list-sub">
                            <?php echo h(t('%d of %d members present', (int) $meeting['present_count'], (int) $meeting['member_count'])); ?>
                        </div>
                        <div class="row-actions">
                            <a class="btn btn-secondary btn-small"
                               href="attendance.php?meeting_id=<?php echo (int) $meeting['id']; ?>">
                                <?php echo h(t(is_admin() ? 'Mark attendance' : 'View attendance')); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
