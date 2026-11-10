<?php
// db.php - connects the app to the ikimina database.

// Use Rwanda time for dates and months.
date_default_timezone_set('Africa/Kigali');

// XAMPP default settings: user "root" with no password.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ikimina');

// Return false on error instead of throwing an exception.
mysqli_report(MYSQLI_REPORT_OFF);

// Open the database connection.
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Stop with a clear message if the connection failed.
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

// Store text (names, phone numbers) as UTF-8.
mysqli_set_charset($conn, 'utf8mb4');
