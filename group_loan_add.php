<?php
// group_loan_add.php - secretary records a loan that several people share together.
require_once 'auth.php';
require_admin();

$page_title = 'New group loan';
$active = 'loans';

$settings = get_setting();
$default_interest = (float) $settings['loan_interest_percent'];

$errors = array();
$name = '';
$amount = '';
$interest = $default_interest > 0 ? number_format($default_interest, 2, '.', '') : '0';
$loan_date = date('Y-m-d');
$due_date = date('Y-m-d', strtotime('+30 days'));
$selected = array();

// Adults can take part in a group loan.
$result = mysqli_query($conn, "SELECT id, name, phone, member_type FROM members
                               WHERE status = 'active' AND member_type = 'adult'
                               ORDER BY name ASC");
$members = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $interest = trim($_POST['interest_percent'] ?? '0');
    $loan_date = trim($_POST['loan_date'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $picked = isset($_POST['member_ids']) && is_array($_POST['member_ids'])
        ? array_map('intval', $_POST['member_ids'])
        : array();

    // Only members who are on the list can join the loan.
    foreach ($members as $member) {
        if (in_array((int) $member['id'], $picked, true)) {
            $selected[(int) $member['id']] = $member;
        }
    }

    if (count($selected) < 2) {
        $errors[] = t('Please choose at least two people for a group loan.');
    }

    // Principal: whole RWF, more than zero.
    $amount_value = 0;
    if ($amount === '' || !is_numeric($amount)) {
        $errors[] = t('Please enter a valid loan amount.');
    } else {
        $amount_value = (float) $amount;
        if ($amount_value <= 0) {
            $errors[] = t('Loan amount must be more than zero.');
        } elseif (floor($amount_value) != $amount_value) {
            $errors[] = t('Loan amount must be a whole number of RWF.');
        } elseif ($amount_value > 99999999) {
            $errors[] = t('Loan amount is too large.');
        }
    }

    // Interest between 0 and 100 percent.
    $interest_value = 0;
    if ($interest === '' || !is_numeric($interest)) {
        $errors[] = t('Please enter a valid interest rate.');
    } else {
        $interest_value = (float) $interest;
        if ($interest_value < 0 || $interest_value > 100) {
            $errors[] = t('Interest must be between 0 and 100 percent.');
        }
    }

    if (!valid_date($loan_date)) {
        $errors[] = t('Please choose a valid loan date.');
    }

    if (!valid_date($due_date)) {
        $errors[] = t('Please choose a valid due date.');
    } elseif (valid_date($loan_date) && $due_date < $loan_date) {
        $errors[] = t('The due date cannot be before the loan date.');
    }

    // Save when everything is valid.
    if (count($errors) === 0) {
        $amount_sql = number_format($amount_value, 2, '.', '');
        $interest_sql = number_format($interest_value, 2, '.', '');
        $loan_date_esc = mysqli_real_escape_string($conn, $loan_date);
        $due_date_esc = mysqli_real_escape_string($conn, $due_date);

        // The whole group is repaid together, so it gets one shared group.
        $group_name = $name !== '' ? $name : t('Group loan of %d people', count($selected));
        $group_name_esc = mysqli_real_escape_string($conn, $group_name);

        $sql = "INSERT INTO loan_groups (name) VALUES ('$group_name_esc')";
        if (!mysqli_query($conn, $sql)) {
            $errors[] = t('Could not save the group loan: %s', mysqli_error($conn));
        } else {
            $group_id = (int) mysqli_insert_id($conn);

            // The amount and the interest are split equally between the people.
            $shares = split_evenly($amount_value, count($selected));
            $total_due = loan_total_due($amount_value, $interest_value);
            $interest_total = max(0, $total_due - (int) round($amount_value));
            $interest_shares = split_evenly($interest_total, count($selected));

            $people = array_values($selected);
            $saved = true;
            foreach ($people as $index => $person) {
                $part_interest = number_format($interest_shares[$index], 2, '.', '');
                $sql = "INSERT INTO loans (member_id, group_id, amount, interest_percent, interest_amount,
                                         loan_date, due_date, status)
                        VALUES ({$person['id']}, $group_id, '{$shares[$index]}', '$interest_sql', '$part_interest',
                                '$loan_date_esc', '$due_date_esc', 'active')";
                if (!mysqli_query($conn, $sql)) {
                    $saved = false;
                    break;
                }
            }

            if (!$saved) {
                $errors[] = t('Could not save the group loan: %s', mysqli_error($conn));
            } else {
                set_flash('success', t(
                    'Group loan of %s RWF saved for %d people. Each person pays %s RWF of the total.',
                    money($amount_value),
                    count($people),
                    money($shares[0])
                ));
                header('Location: loans.php');
                exit;
            }
        }
    }
}

require 'header.php';
?>
<div class="page-head">
    <div>
        <h1><?php echo h(t('New group loan')); ?></h1>
        <p class="muted"><?php echo h(t('Several people borrow together and each pays their own share')); ?></p>
    </div>
    <a class="btn btn-secondary btn-small" href="loans.php"><?php echo h(t('Back')); ?></a>
</div>

<?php if (count($members) < 2): ?>
    <div class="card">
        <div class="empty">
            <p><?php echo h(t('You need at least two active adults before giving a group loan.')); ?></p>
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

        <form method="post" action="group_loan_add.php">
            <div class="form-group">
                <label for="name"><?php echo h(t('Loan name (optional)')); ?></label>
                <input type="text" id="name" name="name" maxlength="150"
                       placeholder="<?php echo h(t('For example, Farm inputs')); ?>"
                       value="<?php echo h($name); ?>">
                <div class="hint"><?php echo h(t('Leave it empty to call it a group loan.')); ?></div>
            </div>

            <div class="form-group">
                <label><?php echo h(t('People who share this loan')); ?></label>
                <div class="hint" style="margin-top:0;margin-bottom:8px;">
                    <?php echo h(t('Choose two or more people. The amount is split equally between them.')); ?>
                </div>
                <div class="list">
                    <?php foreach ($members as $member): ?>
                        <label class="list-row check-row">
                            <input type="checkbox" name="member_ids[]" value="<?php echo (int) $member['id']; ?>"
                                   <?php echo isset($selected[(int) $member['id']]) ? 'checked' : ''; ?>>
                            <div class="avatar-sm"><?php echo h(initials($member['name'])); ?></div>
                            <div class="list-main">
                                <div class="list-title"><?php echo h($member['name']); ?></div>
                                <div class="list-sub"><?php echo h($member['phone'] ? $member['phone'] : t('No phone')); ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="amount"><?php echo h(t('Total loan amount (RWF)')); ?></label>
                <input type="number" id="amount" name="amount" min="1" step="1" required
                       inputmode="numeric" value="<?php echo h($amount); ?>">
                <div class="hint" id="split-hint"><?php echo h(t('Whole francs only, for example 50000.')); ?></div>
            </div>

            <div class="form-group">
                <label for="interest_percent"><?php echo h(t('Interest (%)')); ?></label>
                <input type="number" id="interest_percent" name="interest_percent"
                       min="0" max="100" step="0.01" required inputmode="decimal"
                       value="<?php echo h($interest); ?>">
                <div class="hint"><?php echo h(t('This interest is charged once, not monthly. The total to repay is the amount plus this one-time interest.')); ?></div>
            </div>

            <div class="form-group">
                <label for="loan_date"><?php echo h(t('Loan date')); ?></label>
                <input type="date" id="loan_date" name="loan_date" required
                       value="<?php echo h($loan_date); ?>">
            </div>

            <div class="form-group">
                <label for="due_date"><?php echo h(t('Due date')); ?></label>
                <input type="date" id="due_date" name="due_date" required
                       value="<?php echo h($due_date); ?>">
            </div>

            <div class="form-actions">
                <button class="btn" type="submit"><?php echo h(t('Save group loan')); ?></button>
                <a class="btn btn-secondary" href="loans.php"><?php echo h(t('Cancel')); ?></a>
            </div>
        </form>
    </div>

    <script>
    (function () {
        var amount = document.getElementById('amount');
        var hint = document.getElementById('split-hint');
        var boxes = document.querySelectorAll('input[name="member_ids[]"]');
        if (!amount || !hint || boxes.length === 0) {
            return;
        }

        function updateHint() {
            var count = 0;
            Array.prototype.forEach.call(boxes, function (box) {
                if (box.checked) { count++; }
            });
            var total = parseInt(amount.value, 10);
            if (count < 2 || !total || total <= 0) {
                hint.textContent = <?php echo json_encode(t('Whole francs only, for example 50000.')); ?>;
                return;
            }
            var share = Math.floor(total / count);
            var leftover = total - (share * count);
            var text = <?php echo json_encode(t('Each person pays %s RWF.')); ?>.replace('%s', share.toLocaleString());
            if (leftover > 0) {
                text += ' ' + <?php echo json_encode(t('%d people pay 1 RWF more.')); ?>.replace('%d', leftover);
            }
            hint.textContent = text;
        }

        amount.addEventListener('input', updateHint);
        Array.prototype.forEach.call(boxes, function (box) {
            box.addEventListener('change', updateHint);
        });
        updateHint();
    })();
    </script>
<?php endif; ?>
<?php require 'footer.php'; ?>
