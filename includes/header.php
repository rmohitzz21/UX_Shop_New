<?php
    $headerUserId = $_SESSION['user_id'] ?? null;
    $headerFirstName = trim((string) ($_SESSION['first_name'] ?? ''));
    $headerLastName = trim((string) ($_SESSION['last_name'] ?? ''));
    $headerUserName = $headerFirstName !== '' ? $headerFirstName : trim($headerFirstName . ' ' . $headerLastName);
    if ($headerUserName === '') $headerUserName = $_SESSION['user_email'] ?? 'Profile';
    $headerCurrent = basename(parse_url($_SERVER['REQUEST_URI'] ?? 'index.php', PHP_URL_PATH) ?: 'index.php');
    $isHome = $headerCurrent === '' || $headerCurrent === 'index.php';
    $isShop = in_array($headerCurrent, ['shopAll.php', 'search.php', 'products.php', 'product.php'], true);
    $isBundles = $headerCurrent === 'bundles.php';
    $isFreebies = $headerCurrent === 'freebies.php';
?>
<header class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="index.php"><img src="img/1.png" alt="UX PACIFIC" class="logo" style="height:28px;" onerror="this.outerHTML='<span style=\'font-weight:700; font-size: 1.25rem; letter-spacing: -0.5px;\'>UX PACIFIC</span>'" /></a>
        </div>
        <nav class="nav-links" id="site-nav-links">
            <a href="index.php" class="<?php echo $isHome ? 'active' : ''; ?>">Home</a>
            <a href="shopAll.php" class="<?php echo $isShop ? 'active' : ''; ?>">Products</a>
            <a href="bundles.php" class="<?php echo $isBundles ? 'active' : ''; ?>">Bundles</a>
            <a href="freebies.php" class="<?php echo $isFreebies ? 'active' : ''; ?>">Freebies</a>
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
                    <a href="account.php" role="menuitem"><i class="ph ph-user-circle"></i> Edit Profile</a>
                    <a href="orders.php" role="menuitem"><i class="ph ph-package"></i> My Orders</a>
                    <button type="button" role="menuitem" onclick="handleSignOut()"><i class="ph ph-sign-out"></i> Logout</button>
                </div>
            </div>
            <button type="button" class="icon-btn mobile-nav-toggle" aria-label="Menu" aria-expanded="false">Menu</button>
        </div>
    </div>
    <div class="mobile-nav-panel">
        <a href="index.php">Home</a>
        <a href="shopAll.php">Products</a>
        <a href="bundles.php">Bundles</a>
        <a href="freebies.php">Freebies</a>
        <a href="cart.php">Cart</a>
        <?php if ($headerUserId): ?>
        <a href="orders.php">My Orders</a>
        <a href="account.php">Edit Profile</a>
        <?php endif; ?>
    </div>
</header>
<div class="search-modal" id="site-search-modal" aria-hidden="true">
    <div class="search-modal-backdrop" data-search-close></div>
    <section class="search-modal-panel" role="dialog" aria-modal="true" aria-labelledby="search-modal-title">
        <button type="button" class="search-modal-close" data-search-close aria-label="Close search">&times;</button>
        <p class="search-modal-kicker">Search marketplace</p>
        <h2 id="search-modal-title">Find products and bundles</h2>
        <div class="search-modal-box">
            <input id="site-search-modal-input" type="search" placeholder="Search products" autocomplete="off" />
            <button type="button" id="site-search-modal-submit">Search</button>
        </div>
        <div class="search-modal-results" id="site-search-modal-results" role="listbox"></div>
    </section>
</div>
