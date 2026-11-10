<?php
// penalty_add.php - secretary charges a fine to a member.
require_once 'auth.php';
require_admin();

$page_title = 'Add penalty';
$active = 'penalties';

$errors = array();
$member_id = 0;
$reason = '';
$amount = '';
$penalty_date = date('Y-m-d');

$result = mysqli_query($conn, "SELECT id, name FROM members ORDER BY name ASC");
$members = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = isset($_POST['member_id']) ? (int) $_POST['member_id'] : 0;
    $reason = trim($_POST['reason'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $penalty_date = trim($_POST['penalty_date'] ?? '');

    // The chosen member must exist.
    $selected = null;
    foreach ($members as $member) {
        if ((int) $member['id'] === $member_id) {
            $selected = $member;
            break;
        }
    }

    if (!$selected) {
        $errors[] = t('Please choose a member.');
    }

    if ($reason === '') {
        $errors[] = t('Please write the reason for the penalty.');
    } elseif (mb_strlen($reason) > 255) {
        $errors[] = t('The reason must be 255 characters or fewer.');
    }

    $amount_value = 0;
    if ($amount === '' || !is_numeric($amount)) {
        $errors[] = t('Please enter a valid amount.');
    } else {
        $amount_value = (float) $amount;
        if ($amount_value <= 0) {
            $errors[] = t('Amount must be more than zero.');
        } elseif (floor($amount_value) != $amount_value) {
            $errors[] = t('Amount must be a whole number of RWF.');
        } elseif ($amount_value > 99999999) {
            $errors[] = t('Amount is too large.');
        }
    }

    if (!valid_date($penalty_date)) {
        $errors[] = t('Please choose a valid penalty date.');
    } elseif ($penalty_date > date('Y-m-d')) {
        $errors[] = t('Penalty date cannot be in the future.');
    }

    if (count($errors) === 0) {
        $amount_sql = number_format($amount_value, 2, '.', '');
        $reason_esc = mysqli_real_escape_string($conn, $reason);
        $date_esc = mysqli_real_escape_string($conn, $penalty_date);

        $sql = "INSERT INTO penalties (member_id, reason, amount, penalty_date, status)
                VALUES ($member_id, '$reason_esc', '$amount_sql', '$date_esc', 'unpaid')";

        if (mysqli_query($conn, $sql)) {
            set_flash('success', t('Penalty of %s RWF added for %s.', money($amount_value), $selected['name']));
            header('Location: penalties.php');
            exit;
        }

        $errors[] = t('Could not save the penalty: %s', mysqli_error($conn));
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('Add penalty')); ?></h1>
        <p class="muted"><?php echo h(t('Only the secretary can charge a fine')); ?></p>
    </div>
    <a class="btn btn-secondary btn-small" href="penalties.php"><?php echo h(t('Back')); ?></a>
</div>

<?php if (count($members) === 0): ?>
    <div class="card">
        <div class="empty">
            <p><?php echo h(t('You need at least one member before adding a penalty.')); ?></p>
            <a class="btn" href="add_member.php"><?php echo h(t('Add member')); ?></a>
        </div>
    </div>
<?php else: ?>
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

        <form method="post" action="penalty_add.php">
            <div class="form-group">
                <label for="member_id"><?php echo h(t('Member')); ?></label>
                <select id="member_id" name="member_id" required>
                    <option value=""><?php echo h(t('-- Choose a member --')); ?></option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?php echo (int) $member['id']; ?>"
                            <?php echo $member_id === (int) $member['id'] ? 'selected' : ''; ?>>
                            <?php echo h($member['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="reason"><?php echo h(t('Reason')); ?></label>
                <input type="text" id="reason" name="reason" maxlength="255" required
                       value="<?php echo h($reason); ?>" placeholder="<?php echo h(t('e.g. Late for the meeting')); ?>">
            </div>

            <div class="form-group">
                <label for="amount"><?php echo h(t('Amount (RWF)')); ?></label>
                <input type="number" id="amount" name="amount" min="1" step="1" required
                       inputmode="numeric" value="<?php echo h($amount); ?>">
                <div class="hint"><?php echo h(t('Whole francs only, for example 1000.')); ?></div>
            </div>

            <div class="form-group">
                <label for="penalty_date"><?php echo h(t('Date')); ?></label>
                <input type="date" id="penalty_date" name="penalty_date" required
                       max="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo h($penalty_date); ?>">
            </div>

            <div class="form-actions">
                <button class="btn" type="submit"><?php echo h(t('Save penalty')); ?></button>
                <a class="btn btn-secondary" href="penalties.php"><?php echo h(t('Cancel')); ?></a>
            </div>
        </form>
    </div>
<?php endif; ?>
<?php require 'footer.php'; ?>
