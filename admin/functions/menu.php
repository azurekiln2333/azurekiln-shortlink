<header class="ak-admin-nav">
    <div class="ak-shell ak-admin-nav-inner">
        <a class="ak-brand" href="index.php">
            <span class="ak-brand-mark" aria-hidden="true">A</span>
            <span class="ak-brand-text">管理面板</span>
        </a>
        <nav class="ak-admin-links" aria-label="管理导航">
            <a href="index.php">概览</a>
            <a href="users.php">用户审核</a>
            <a href="links.php">链接</a>
            <a href="blocked-domains.php">域名黑名单</a>
            <a href="audit.php">审计日志</a>
            <a href="settings.php">设置</a>
            <a href="../index.php">返回站点</a>
            <form class="ak-nav-form" action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <button class="ak-nav-link" type="submit">注销</button>
            </form>
            <button class="ak-icon-button" type="button" data-theme-toggle aria-label="切换深色模式">
                <span class="ak-material" data-theme-icon aria-hidden="true">dark_mode</span>
            </button>
        </nav>
    </div>
</header>
