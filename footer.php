<?php
// footer.php - closes the app layout and shows the bottom tabs.
?>
</main>
<nav class="tabbar" aria-label="Main navigation">
    <div class="tabbar-inner">
        <a href="index.php" class="tab <?php echo $tab === 'home' ? 'active' : ''; ?>">
            <?php echo nav_icon('home'); ?><span><?php echo h(t('Home')); ?></span>
        </a>
        <a href="members.php" class="tab <?php echo $tab === 'members' ? 'active' : ''; ?>">
            <?php echo nav_icon('members'); ?><span><?php echo h(t('Members')); ?></span>
        </a>
        <a href="contributions.php" class="tab <?php echo $tab === 'money' ? 'active' : ''; ?>">
            <?php echo nav_icon('money'); ?><span><?php echo h(t('Money')); ?></span>
        </a>
        <a href="loans.php" class="tab <?php echo $tab === 'loans' ? 'active' : ''; ?>">
            <?php echo nav_icon('loans'); ?><span><?php echo h(t('Loans')); ?></span>
        </a>
        <a href="more.php" class="tab <?php echo $tab === 'more' ? 'active' : ''; ?>">
            <?php echo nav_icon('more'); ?><span><?php echo h(t('More')); ?></span>
        </a>
    </div>
</nav>
</body>
</html>
