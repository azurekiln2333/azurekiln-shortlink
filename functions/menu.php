<header class="ak-header">
    <div class="ak-shell ak-header-inner">
        <a class="ak-brand" href="<?php echo htmlspecialchars(public_base_url(), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="ak-brand-mark" aria-hidden="true">A</span>
            <span class="ak-brand-text"><?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <nav class="ak-nav" aria-label="主导航">
            <a href="about.php">关于我们</a>
            <a href="api-about.php">API 接口</a>
            <a href="tos.php">使用许可</a>
            <a href="statics.php">统计</a>
            <button class="ak-icon-button" type="button" data-bookmarklet-open aria-label="快捷缩短">
                <span class="ak-material" aria-hidden="true">bolt</span>
            </button>
        </nav>
        <div class="ak-header-actions">
            <a class="ak-icon-button" href="account.php" aria-label="用户账户" title="用户账户">
                <span class="ak-material" aria-hidden="true">person</span>
            </a>
            <?php if (!empty($_SESSION['user_id'])): ?><a class="ak-icon-button" href="my-links.php" aria-label="我的短链" title="我的短链"><span class="ak-material" aria-hidden="true">link</span></a><?php endif; ?>
            <button class="ak-icon-button" type="button" data-theme-toggle aria-label="切换深色模式">
                <span class="ak-material" data-theme-icon aria-hidden="true">dark_mode</span>
            </button>
            <a class="ak-icon-button" href="admin/login.php" aria-label="管理登录">
                <span class="ak-material" aria-hidden="true">account_circle</span>
            </a>
        </div>
    </div>
</header>
<div id="bookmarklet-modal" class="ak-modal" hidden role="dialog" aria-modal="true" aria-labelledby="bookmarklet-title">
    <div class="ak-card ak-modal-card">
        <div class="ak-modal-head">
            <h2 id="bookmarklet-title" class="ak-modal-title">收藏夹快捷功能</h2>
            <button class="ak-modal-close" type="button" data-bookmarklet-close aria-label="关闭">
                <span class="ak-material" aria-hidden="true">close</span>
            </button>
        </div>
        <p>短链生成现在需要 OAuth2 登录，并通过管理员审核。</p>
        <p><a href="account.php">打开用户账户</a></p>
    </div>
</div>
