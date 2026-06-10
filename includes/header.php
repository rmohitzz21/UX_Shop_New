<?php
    $headerUserId = $_SESSION['user_id'] ?? null;
    $headerFirstName = trim((string) ($_SESSION['first_name'] ?? ''));
    $headerLastName = trim((string) ($_SESSION['last_name'] ?? ''));
    $headerUserName = $headerFirstName !== '' ? $headerFirstName : trim($headerFirstName . ' ' . $headerLastName);
    if ($headerUserName === '') $headerUserName = $_SESSION['user_email'] ?? 'Profile';
    $headerPath = $_SERVER['SCRIPT_NAME'] ?? parse_url($_SERVER['REQUEST_URI'] ?? 'index.php', PHP_URL_PATH) ?: 'index.php';
    $headerCurrent = strtolower(basename($headerPath));
    if ($headerCurrent === '' || $headerCurrent === '/') {
        $headerCurrent = 'index.php';
    }
    $isHome = $headerCurrent === '' || $headerCurrent === 'index.php';
    $isShop = in_array($headerCurrent, ['shopall.php', 'search.php', 'products.php', 'product.php', 'category.php'], true);
    $isBundles = $headerCurrent === 'bundles.php';
    $isStudio = $headerCurrent === 'studio.php';
?>
<header class="navbar">
    <div class="navbar-shell">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php"><img src="img/1.png" alt="UX PACIFIC" class="logo" style="height:28px;" onerror="this.outerHTML='<span style=\'font-weight:700; font-size: 1.25rem; letter-spacing: -0.5px;\'>UX PACIFIC</span>'" /></a>
            </div>
            <nav class="nav-links" id="site-nav-links">
                <a href="index.php" class="<?php echo $isHome ? 'active' : ''; ?>">Home</a>
                <a href="shopAll.php" class="<?php echo $isShop ? 'active' : ''; ?>">Products</a>
                <a href="bundles.php" class="<?php echo $isBundles ? 'active' : ''; ?>">Bundles</a>
                <a href="studio.php" class="<?php echo $isStudio ? 'active' : ''; ?>">Studio</a>
            </nav>
            <div class="nav-actions">
                <form class="nav-search" action="search.php" method="get" role="search">
                    <input id="header-search-input" class="nav-search-input" type="search" name="q" placeholder="Search products" autocomplete="off" />
                    <button class="icon-btn nav-search-trigger" type="button" aria-label="Search">
                        <img src="img/ss/nav/Vector.png" alt="" />
                    </button>
                    <div class="nav-search-suggestions" role="listbox" aria-label="Search suggestions"></div>
                </form>
                <a href="cart.php" class="icon-btn cart-btn" data-cart-toggle aria-label="Cart">
                    <img src="img/ss/hugeicons_shopping-basket-secure-01.png" alt="Cart" />
                    <span id="cart-count" class="nav-count-badge">0</span>
                </a>
                <a href="signin.php" class="btn-primary header-signin-cta<?php echo $headerUserId ? ' uxp-sr-hide' : ''; ?>">Sign In</a>
                <div class="user-menu profile-menu" <?php echo $headerUserId ? '' : 'style="display:none;"'; ?>>
                    <button type="button" class="profile-menu-toggle" aria-haspopup="true" aria-expanded="false">
                        <img src="img/ss/nav/iconoir_user.png" alt="User" />
                        <span class="user-name" style="font-size: 0.875rem;"><?php echo htmlspecialchars($headerUserName); ?></span>
                        <i class="ph ph-caret-down"></i>
                    </button>
                    <div class="profile-dropdown" role="menu">
                        <a href="account.php" role="menuitem">Edit Profile</a>
                        <a href="orders.php" role="menuitem">My Orders</a>
                        <button type="button" role="menuitem" onclick="handleSignOut()">Logout</button>
                    </div>
                </div>
                <button type="button" class="icon-btn mobile-nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-nav-panel">
                    <span class="hamburger-box" aria-hidden="true">
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                    </span>
                </button>
            </div>
        </div>
        <nav class="mobile-nav-panel" id="mobile-nav-panel" aria-hidden="true">
            <a href="index.php" class="mobile-nav-link<?php echo $isHome ? ' active' : ''; ?>"><i class="ph ph-house"></i> Home</a>
            <a href="shopAll.php" class="mobile-nav-link<?php echo $isShop ? ' active' : ''; ?>"><i class="ph ph-shopping-bag"></i> Products</a>
            <a href="bundles.php" class="mobile-nav-link<?php echo $isBundles ? ' active' : ''; ?>"><i class="ph ph-package"></i> Bundles</a>
            <a href="studio.php" class="mobile-nav-link<?php echo $isStudio ? ' active' : ''; ?>"><i class="ph ph-paint-brush"></i> Studio</a>
            <button type="button" class="mobile-nav-link mobile-nav-search mobile-search-trigger" id="mobile-search-trigger"><i class="ph ph-magnifying-glass"></i> Search</button>
            <a href="cart.php" class="mobile-nav-link"><i class="ph ph-shopping-cart"></i> Cart</a>
            <?php if ($headerUserId): ?>
            <a href="orders.php" class="mobile-nav-link"><i class="ph ph-tray"></i> My Orders</a>
            <a href="account.php" class="mobile-nav-link"><i class="ph ph-user-circle"></i> Edit Profile</a>
            <button type="button" class="mobile-nav-link mobile-nav-logout" onclick="handleSignOut()"><i class="ph ph-sign-out"></i> Logout</button>
            <?php else: ?>
            <a href="signin.php" class="mobile-nav-link mobile-nav-cta"><i class="ph ph-sign-in"></i> Sign In</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="mobile-nav-backdrop" aria-hidden="true"></div>
</header>
<div class="search-modal" id="site-search-modal" aria-hidden="true">
    <div class="search-modal-backdrop" data-search-close></div>
    <section class="search-modal-panel" role="dialog" aria-modal="true" aria-labelledby="search-modal-title">
        <header class="search-modal-header">
            <p class="search-modal-kicker">Search marketplace</p>
            <h2 id="search-modal-title">Find products &amp; bundles</h2>
            <div class="search-modal-box">
                <img src="img/ss/nav/Vector.png" alt="" class="search-modal-icon" />
                <input id="site-search-modal-input" type="text" inputmode="search" enterkeyhint="search" role="searchbox" placeholder="Search by name, category, or keyword…" autocomplete="off" spellcheck="false" />
                <button type="button" id="site-search-modal-submit" class="search-modal-submit" aria-label="Search">Search</button>
            </div>
            <p class="search-modal-shortcuts" aria-hidden="true">
                <span><kbd>↵</kbd> open</span>
                <span><kbd>↑↓</kbd> navigate</span>
                <span><kbd>esc</kbd> close</span>
            </p>
        </header>
        <div class="search-modal-body">
            <div class="search-modal-results" id="site-search-modal-results" role="listbox" aria-label="Search results"></div>
        </div>
    </section>
</div>
