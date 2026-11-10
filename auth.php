<?php
// auth.php - shared session check, roles, settings and small helpers.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'i18n.php';
require_once 'db.php';

// Guests must log in first.
function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Only the secretary (role "admin") may change data.
function require_admin()
{
    require_login();

    if (!is_admin()) {
        set_flash('error', 'Only the secretary can do that.');
        header('Location: index.php');
        exit;
    }
}

// True when the logged-in user is the secretary.
function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// The member linked to the logged-in user; 0 for an admin with no member record.
function current_member_id()
{
    return isset($_SESSION['member_id']) ? (int) $_SESSION['member_id'] : 0;
}

// Escape text before printing it in HTML.
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Format a whole RWF amount, for example 12500 -> "12,500".
function money($amount)
{
    return number_format((float) $amount, 0);
}

// First letters of a name, for the round avatar chips.
function initials($name)
{
    $words = preg_split('/\s+/', trim((string) $name));

    if (!$words || $words[0] === '') {
        return '?';
    }

    $first = mb_substr($words[0], 0, 1);
    $last = count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : '';

    return mb_strtoupper($first . $last);
}

// Age in whole years from a YYYY-MM-DD date, or null when it is not set.
function age_from_date($date)
{
    if (!$date) {
        return null;
    }

    $birth = strtotime($date);
    if (!$birth) {
        return null;
    }

    $age = (int) date('Y') - (int) date('Y', $birth);
    if (date('md') < date('md', $birth)) {
        $age--;
    }

    return $age >= 0 ? $age : null;
}

// Read one value from the database (SUM, COUNT, ...) with a fallback.
function db_value($sql, $default = 0)
{
    global $conn;

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return $default;
    }

    $row = mysqli_fetch_row($result);
    return ($row && $row[0] !== null) ? $row[0] : $default;
}

// Total amount a loan must repay: principal plus interest, in whole RWF.
function loan_total_due($amount, $interest_percent)
{
    return round((float) $amount * (1 + ((float) $interest_percent / 100)));
}

// Load the single settings row, with safe defaults.
function get_setting()
{
    global $conn;

    $result = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1 LIMIT 1");
    $settings = $result ? mysqli_fetch_assoc($result) : null;

    if (!$settings) {
        return array(
            'group_name' => 'Ikimina Savings Group',
            'contribution_amount' => 0,
            'loan_interest_percent' => 0
        );
    }

    return $settings;
}

// Save a one-time message for the next page.
function set_flash($type, $message)
{
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

// Read and remove the one-time message.
function get_flash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// Check a date in YYYY-MM-DD format.
function valid_date($date)
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    list($year, $month, $day) = array_map('intval', explode('-', $date));
    return checkdate($month, $day, $year);
}

// Accept 7-20 digits, spaces, +, -, ( and ).
function valid_phone($phone)
{
    return (bool) preg_match('/^[0-9+\s()-]{7,20}$/', $phone);
}
