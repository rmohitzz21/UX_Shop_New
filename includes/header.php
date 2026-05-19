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
                </form>
                <a href="cart.php" class="icon-btn cart-btn" aria-label="Cart">
                    <img src="img/ss/hugeicons_shopping-basket-secure-01.png" alt="Cart" />
                </a>
                <div class="user-menu">
                    <a href="account.php" style="display:flex; align-items:center; gap: 8px; text-decoration: none; color: white;">
                        <img src="img/ss/nav/iconoir_user.png" alt="User" />
                        <span style="font-size: 0.875rem;">Mohit</span>
                        <i class="ph ph-caret-down"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>
