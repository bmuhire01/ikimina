<?php
// logout.php - ends the session and sends the user back to the login page.
session_start();

// Remove all session data and destroy the session.
session_unset();
session_destroy();

// Remove the session cookie too.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

header('Location: login.php');
exit;
