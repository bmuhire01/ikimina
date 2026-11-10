<?php
// db.php - connects the app to the ikimina database.

// Use Rwanda time for dates and months.
date_default_timezone_set('Africa/Kigali');

// Load server-specific credentials if the file exists. It is ignored by Git.
$local_config = __DIR__ . '/config.local.php';
if (is_file($local_config)) {
    require $local_config;
}

// XAMPP default settings, used when no local or environment config is present.
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('IKIMINA_DB_HOST') !== false ? getenv('IKIMINA_DB_HOST') : 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('IKIMINA_DB_USER') !== false ? getenv('IKIMINA_DB_USER') : 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('IKIMINA_DB_PASS') !== false ? getenv('IKIMINA_DB_PASS') : '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('IKIMINA_DB_NAME') !== false ? getenv('IKIMINA_DB_NAME') : 'ikimina');
}

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
