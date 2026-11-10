<?php
// meeting_add.php - secretary schedules a group meeting.
require_once 'auth.php';
require_admin();

$page_title = 'New meeting';
$active = 'meetings';

$errors = array();
$meeting_date = date('Y-m-d');
$place = '';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meeting_date = trim($_POST['meeting_date'] ?? '');
    $place = trim($_POST['place'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!valid_date($meeting_date)) {
        $errors[] = t('Please choose a valid meeting date.');
    }

    if ($place === '') {
        $errors[] = t('Please write where the meeting will be held.');
    } elseif (mb_strlen($place) > 150) {
        $errors[] = t('The place must be 150 characters or fewer.');
    }

    if (count($errors) === 0) {
        $date_esc = mysqli_real_escape_string($conn, $meeting_date);
        $place_esc = mysqli_real_escape_string($conn, $place);
        $notes_esc = mysqli_real_escape_string($conn, $notes);

        $sql = "INSERT INTO meetings (meeting_date, place, notes)
                VALUES ('$date_esc', '$place_esc', '$notes_esc')";

        if (mysqli_query($conn, $sql)) {
            $new_id = (int) mysqli_insert_id($conn);
            set_flash('success', t('Meeting created. Now mark who attended.'));
            header('Location: attendance.php?meeting_id=' . $new_id);
            exit;
        }

        $errors[] = t('Could not save the meeting: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('New meeting')); ?></h1>
        <p class="muted"><?php echo h(t('Only the secretary can schedule meetings')); ?></p>
    </div>
    <a class="btn btn-secondary btn-small" href="meetings.php"><?php echo h(t('Back')); ?></a>
</div>

<div class="card form-card">
    <?php if (count($errors) > 0): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="meeting_add.php">
        <div class="form-group">
            <label for="meeting_date"><?php echo h(t('Meeting date')); ?></label>
            <input type="date" id="meeting_date" name="meeting_date" required
                   value="<?php echo h($meeting_date); ?>">
        </div>

        <div class="form-group">
            <label for="place"><?php echo h(t('Place')); ?></label>
            <input type="text" id="place" name="place" maxlength="150" required
                   value="<?php echo h($place); ?>" placeholder="<?php echo h(t('e.g. Village hall')); ?>">
        </div>

        <div class="form-group">
            <label for="notes"><?php echo h(t('Notes (optional)')); ?></label>
            <textarea id="notes" name="notes" rows="3"
                      placeholder="<?php echo h(t('What will the group discuss?')); ?>"><?php echo h($notes); ?></textarea>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit"><?php echo h(t('Save meeting')); ?></button>
            <a class="btn btn-secondary" href="meetings.php"><?php echo h(t('Cancel')); ?></a>
        </div>
    </form>
</div>
<?php require 'footer.php'; ?>
