<?php
// attendance.php - mark who came to a meeting (secretary) or view the list.
require_once 'auth.php';
require_login();

$page_title = 'Attendance';
$active = 'meetings';

$meeting_id = isset($_GET['meeting_id']) ? (int) $_GET['meeting_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meeting_id = isset($_POST['meeting_id']) ? (int) $_POST['meeting_id'] : 0;
}

if ($meeting_id < 1) {
    set_flash('error', t('Meeting not found.'));
    header('Location: meetings.php');
    exit;
}

// Load the meeting.
$result = mysqli_query($conn, "SELECT id, meeting_date, place, notes
                               FROM meetings WHERE id = $meeting_id LIMIT 1");
$meeting = $result ? mysqli_fetch_assoc($result) : null;

if (!$meeting) {
    set_flash('error', t('Meeting not found.'));
    header('Location: meetings.php');
    exit;
}

// All members, with their current attendance for this meeting.
$result = mysqli_query($conn, "SELECT m.id, m.name, m.phone, m.status,
                                      a.present
                               FROM members m
                               LEFT JOIN attendance a
                                      ON a.member_id = m.id AND a.meeting_id = $meeting_id
                               ORDER BY m.name ASC");
$members = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

// Saving is only for the secretary.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();

    $present_ids = isset($_POST['present']) && is_array($_POST['present'])
        ? array_map('intval', $_POST['present'])
        : array();
    $present_set = array_fill_keys($present_ids, true);

    // Replace this meeting's attendance with the submitted list.
    mysqli_query($conn, "DELETE FROM attendance WHERE meeting_id = $meeting_id");

    foreach ($members as $member) {
        if (isset($present_set[(int) $member['id']])) {
            $member_id = (int) $member['id'];
            mysqli_query($conn, "INSERT INTO attendance (meeting_id, member_id, present)
                                 VALUES ($meeting_id, $member_id, 1)");
        }
    }

    // Keep the local copy in step with what was saved.
    foreach ($members as $index => $member) {
        $members[$index]['present'] = isset($present_set[(int) $member['id']]) ? 1 : 0;
    }

    set_flash('success', t('Attendance saved.'));
    header('Location: attendance.php?meeting_id=' . $meeting_id);
    exit;
}

$present_count = 0;
foreach ($members as $member) {
    if ((int) $member['present'] === 1) {
        $present_count++;
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Attendance')); ?></h1>
        <p class="muted">
            <?php echo h(app_date('D, j M Y', strtotime($meeting['meeting_date']))); ?>
            &middot; <?php echo h($meeting['place']); ?>
        </p>
    </div>
    <a class="btn btn-secondary btn-small" href="meetings.php"><?php echo h(t('Back')); ?></a>
</div>

<div class="card">
    <div class="section-head">
        <h2><?php echo h(t('%d of %d present', $present_count, count($members))); ?></h2>
        <?php if (is_admin()): ?>
            <button type="button" class="btn btn-secondary btn-small" id="select-all"><?php echo h(t('Select all')); ?></button>
        <?php endif; ?>
    </div>

    <?php if (count($members) === 0): ?>
        <div class="empty">
            <p><?php echo h(t('There are no members to mark yet.')); ?></p>
            <a class="btn" href="add_member.php"><?php echo h(t('Add member')); ?></a>
        </div>
    <?php else: ?>
        <form method="post" action="attendance.php?meeting_id=<?php echo $meeting_id; ?>">
            <input type="hidden" name="meeting_id" value="<?php echo $meeting_id; ?>">

            <div class="list">
                <?php foreach ($members as $member): ?>
                    <label class="list-row check-row">
                        <input type="checkbox" name="present[]" value="<?php echo (int) $member['id']; ?>"
                               <?php echo (int) $member['present'] === 1 ? 'checked' : ''; ?>
                               <?php echo is_admin() ? '' : 'disabled'; ?>>
                        <div class="avatar-sm"><?php echo h(initials($member['name'])); ?></div>
                        <div class="list-main">
                            <div class="list-title">
                                <?php echo h($member['name']); ?>
                                <?php if ($member['status'] !== 'active'): ?>
                                    <span class="badge danger"><?php echo h(t('Inactive')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="list-sub"><?php echo h($member['phone']); ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php if (is_admin()): ?>
                <div class="form-actions">
                    <button class="btn" type="submit"><?php echo h(t('Save attendance')); ?></button>
                    <a class="btn btn-secondary" href="meetings.php"><?php echo h(t('Cancel')); ?></a>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

<?php if (is_admin()): ?>
<script>
document.getElementById('select-all').addEventListener('click', function () {
    var boxes = document.querySelectorAll('input[name="present[]"]');
    var allChecked = boxes.length > 0;
    boxes.forEach(function (box) { if (!box.checked) { allChecked = false; } });
    boxes.forEach(function (box) { box.checked = !allChecked; });
    this.textContent = allChecked
        ? <?php echo json_encode(t('Select all')); ?>
        : <?php echo json_encode(t('Clear all')); ?>;
});
</script>
<?php endif; ?>
<?php require 'footer.php'; ?>
