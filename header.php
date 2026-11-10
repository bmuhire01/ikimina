<?php
// header.php - shared mobile app bar and bottom tab navigation.

// Reuse settings loaded by the page, or load them here.
if (!isset($settings)) {
    $settings = get_setting();
}

$group_name = isset($settings['group_name']) ? $settings['group_name'] : 'Ikimina';
$page_title = isset($page_title) ? $page_title : 'Home';
$active = isset($active) ? $active : 'home';

// Map sub-pages to the tab that should look selected.
$tab = $active;
if (in_array($tab, array('contributions', 'balances'), true)) {
    $tab = 'money';
}
if (in_array($tab, array('families'), true)) {
    $tab = 'members';
}
if (in_array($tab, array('penalties', 'meetings', 'settings', 'password', 'more', 'report'), true)) {
    $tab = 'more';
}

// Small line icons used in the bottom tabs.
function nav_icon($name)
{
    $paths = array(
        'home'    => '<path d="M3.5 10.5 12 3.5l8.5 7"/><path d="M5.5 9.5V20h13V9.5"/>',
        'members' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 20c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/><path d="M16.5 5.6a3 3 0 0 1 0 4.8"/><path d="M17.6 15.4c1.7.6 2.7 2.1 2.7 4.6"/>',
        'family'  => '<circle cx="8.5" cy="7.5" r="2.6"/><circle cx="17" cy="8" r="2.1"/><path d="M3.5 20c0-3 2.2-5.2 5-5.2s5 2.2 5 5.2"/><path d="M14.4 15.8c2.5.1 4.6 1.8 4.6 4.2"/>',
        'money'   => '<rect x="3" y="6" width="18" height="12" rx="3"/><circle cx="12" cy="12" r="2.6"/><path d="M7 9.6h.01M17 14.4h.01"/>',
        'loans'   => '<path d="M7 3.5h8.5L19 7v13.5H7z"/><path d="M15 3.5V7h3.5"/><path d="M10 11h6M10 14.5h6"/>',
        'more'    => '<circle cx="6" cy="12" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="18" cy="12" r="1.4"/>',
        'calendar'=> '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3.5v3M16 3.5v3"/>',
        'balance' => '<rect x="3" y="6" width="18" height="13" rx="3"/><path d="M3 10.5h18"/><path d="M16.5 15h.01"/>',
        'penalty' => '<path d="M12 3.5 21 20H3z"/><path d="M12 9v4.5M12 16.3h.01"/>',
        'settings'=> '<path d="M4 7h9M19 7h1M4 17h5M15 17h5"/><circle cx="16" cy="7" r="2.2"/><circle cx="12" cy="17" r="2.2"/>',
        'language'=> '<circle cx="12" cy="12" r="8.5"/><path d="M3.8 9h16.4M3.8 15h16.4"/><path d="M12 3.5c2.5 2.4 3.8 5.2 3.8 8.5s-1.3 6.1-3.8 8.5c-2.5-2.4-3.8-5.2-3.8-8.5S9.5 5.9 12 3.5z"/>',
        'logout'  => '<path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h10"/>',
        'add'     => '<path d="M12 5v14M5 12h14"/>',
    );

    $body = isset($paths[$name]) ? $paths[$name] : $paths['more'];

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"'
        . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="<?php echo h(current_lang()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#14532d">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Ikimina">
    <title><?php echo h(t($page_title)); ?> - <?php echo h($group_name); ?></title>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href="icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="appbar">
    <div class="appbar-inner">
        <a class="appbar-brand" href="index.php">
            <span class="appbar-logo">IK</span>
            <span class="appbar-titles">
                <strong><?php echo h($group_name); ?></strong>
                <small><?php echo h(t($page_title)); ?></small>
            </span>
        </a>
        <div class="appbar-actions">
            <a class="lang-switch" href="<?php echo h(language_url(other_lang())); ?>"
               title="<?php echo h(t('Switch language')); ?>">
                <?php echo other_lang() === 'rw' ? 'RW' : 'EN'; ?>
            </a>
            <a class="avatar" href="more.php" title="<?php echo h(t('Account and more')); ?>">
                <?php echo h(initials($_SESSION['username'])); ?>
            </a>
        </div>
    </div>
</header>

<main class="app-main">
<?php $flash = get_flash(); ?>
<?php if ($flash): ?>
    <div class="alert <?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
        <?php echo h($flash['message']); ?>
    </div>
<?php endif; ?>
<?php if (!is_admin() && in_array($active, array('members', 'balances', 'contributions', 'loans', 'penalties', 'families', 'meetings', 'report'), true)): ?>
    <div class="readonly-note">
        <?php echo h(t('Read-only view: you can see the group records but cannot change them.')); ?>
    </div>
<?php endif; ?>
