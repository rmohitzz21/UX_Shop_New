    <?php
        $headerUserId = $_SESSION['user_id'] ?? null;
        $headerFirstName = trim((string) ($_SESSION['first_name'] ?? ''));
        $headerLastName = trim((string) ($_SESSION['last_name'] ?? ''));
        $headerUserName = trim($headerFirstName . ' ' . $headerLastName);
        if ($headerUserName === '') {
            $headerUserName = 'Profile';
        }
    ?>
    <!-- NAVBAR -->
    <header class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php"><img src="img/1.png" alt="UX PACIFIC" class="logo" style="height:28px;" onerror="this.outerHTML='<span style=\'font-weight:700; font-size: 1.25rem; letter-spacing: -0.5px;\'>UX PACIFIC</span>'" /></a>
            </div>
            <nav class="nav-links">
                <a href="index.php" class="active">Home</a>
                <a href="#products">Products</a>
                <a href="#category">Category</a>
                <a href="bundles.php">Bundles</a>
            </nav>
            <div class="nav-actions">
                <form class="nav-search" action="search.php" method="get" role="search">
                    <input id="header-search-input" class="nav-search-input" type="search" name="q" placeholder="Search products" autocomplete="off" />
                    <button class="icon-btn nav-search-trigger" type="submit" aria-label="Search">
                        <img src="img/ss/nav/Vector.png" alt="" />
                    </button>
                    <div class="nav-search-suggestions" role="listbox" aria-label="Search suggestions"></div>
                </form>
                <a href="wishlist.php" class="icon-btn wishlist-btn" aria-label="Wishlist">
                    <span class="wishlist-heart" aria-hidden="true">♡</span>
                    <span id="wishlist-count" class="nav-count-badge">0</span>
                </a>
                <a href="cart.php" class="icon-btn cart-btn" data-cart-toggle aria-label="Cart">
                    <img src="img/ss/hugeicons_shopping-basket-secure-01.png" alt="Cart" />
                    <span id="cart-count" class="nav-count-badge">0</span>
                </a>
                <a href="signin.php" class="nav-cta header-signin-cta" <?php echo $headerUserId ? 'style="display:none;"' : ''; ?>>Sign In</a>
                <div class="user-menu profile-menu" <?php echo $headerUserId ? '' : 'style="display:none;"'; ?>>
                    <button type="button" class="profile-menu-toggle" aria-haspopup="true" aria-expanded="false">
                        <img src="img/ss/nav/iconoir_user.png" alt="User" />
                        <span class="user-name" style="font-size: 0.875rem;"><?php echo htmlspecialchars($headerUserName); ?></span>
                        <i class="ph ph-caret-down"></i>
                    </button>
                    <div class="profile-dropdown" role="menu">
                        <a href="account.php" role="menuitem">Edit Profile</a>
                        <button type="button" role="menuitem" onclick="handleSignOut()">Logout</button>
                    </div>
                </div>
            </div>
        </div>
    </header>
