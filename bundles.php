<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Ready-made UI/UX career bundles from UX Pacific." />
    <title>Ready-Made Career Bundles - UX Pacific Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,200;0,9..40,300;0,9..40,600;1,9..40,200&family=Gabarito:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>
<body class="bundles-page figma-bundles">
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
                <a href="index.php" >Home</a>
                <a href="index.php#products">Products</a>
                <a href="index.php#category">Category</a>
                <a href="bundles.php" class="active">Bundles</a>
            </nav>
            <div class="nav-actions">
                <form class="nav-search" action="search.php" method="get" role="search">
                    <input id="header-search-input" class="nav-search-input" type="search" name="q" placeholder="Search products" autocomplete="off" />
                    <button class="icon-btn nav-search-trigger" type="submit" aria-label="Search">
                        <img src="img/ss/nav/Vector.png" alt="" />
                    </button>
                    <div class="nav-search-suggestions" role="listbox" aria-label="Search suggestions"></div>
                </form>
                <a href="cart.php" class="icon-btn cart-btn" data-cart-toggle aria-label="Cart">
                    <img src="img/ss/hugeicons_shopping-basket-secure-01.png" alt="Cart" />
                    <span id="cart-count" class="nav-count-badge">0</span>
                </a>
                <a href="signin.php" class="btn-primary header-signin-cta" <?php echo $headerUserId ? 'style="display:none;"' : ''; ?>>Sign In</a>
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



    <main class="bundles-figma-main">
        <section class="figma-hero">
            <h1>
                <span>Ready-Made Career</span>
                <em>Bundles</em>
                <span>for</span>
                <em>UI/UX Designers</em>
            </h1>
            <p>Explore curated UI/UX learning kits, templates, case studies, and career resources designed to help creators grow faster.</p>
        </section>

        <section class="figma-featured-card">
            <div class="figma-featured-image">
                <img src="img/poster.webp" alt="Ultimate UI/UX Career Bundle" />
                <span>Best Seller</span>
            </div>
            <div class="figma-featured-content">
                <h2>Ultimate UI/UX Career Bundle</h2>
                <div class="figma-rating-row">
                    <span class="figma-stars" aria-label="5 stars">*****</span>
                    <span>4.9 (234 reviews)</span>
                </div>
                <p class="figma-featured-desc">Everything you need to build a professional UI/UX career. From portfolio templates to interview preparation.</p>
                <h3>What's Included:</h3>
                <ul class="figma-featured-list">
                    <li>15 UI Screens</li>
                    <li>Portfolio Templates</li>
                    <li>UX Workbook</li>
                    <li>Resume Kit</li>
                    <li>Interview Guide</li>
                    <li>Freelance Proposal Templates</li>
                </ul>
                <div class="figma-featured-price">
                    <strong>&#8377;1,499</strong>
                    <del>&#8377;2,999</del>
                </div>
                <div class="figma-featured-actions">
                    <a class="figma-btn figma-btn-primary" href="shopAll.php?cat=bundles">View Details</a>
                    <button class="figma-btn figma-btn-outline" type="button" onclick="addToCart('bundle-ultimate-uiux-career', null, 1, {name: 'Ultimate UI/UX Career Bundle', price: 1499, image: 'img/poster.webp', category: 'Bundles', description: 'Everything you need to build a professional UI/UX career.'}, 'digital')">Buy Now</button>
                </div>
            </div>
        </section>

        <section class="figma-card-grid" aria-label="Career bundles">
            <?php
            $bundleImages = ['img/poster1.webp', 'img/poster2.webp', 'img/poster3.webp', 'img/poster4.webp', 'img/poster.webp', 'img/poster1.webp'];
            foreach ($bundleImages as $loopIndex => $image):
            ?>
                <article class="figma-product-card">
                    <div class="figma-product-image">
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Portfolio Builder Kit" />
                    </div>
                    <div class="figma-product-body">
                        <div class="figma-product-title-row">
                            <h3>Portfolio Builder Kit</h3>
                            <div class="figma-product-rating">
                                <span>****</span><span class="empty">*</span> 4.8
                            </div>
                        </div>
                        <p class="figma-product-desc">Everything needed to create a professional UX portfolio.</p>
                        <p class="figma-product-included"><strong>What's Included:</strong> Templates, Case study layouts, Resume files</p>
                        <div class="figma-product-price">
                            <strong>&#8377;799</strong>
                            <del>&#8377;1,499</del>
                        </div>
                        <div class="figma-product-actions">
                            <a class="figma-btn-sm figma-btn-primary" href="shopAll.php?cat=bundles">View Details</a>
                            <button class="figma-btn-sm figma-btn-outline" type="button" onclick="addToCart('bundle-portfolio-builder-<?php echo (int) $loopIndex; ?>', null, 1, {name: 'Portfolio Builder Kit', price: 799, image: '<?php echo htmlspecialchars($image); ?>', category: 'Bundles', description: 'Everything needed to create a professional UX portfolio.'}, 'digital')">Buy Now</button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="figma-grow-section">
            <h2>How These Bundles Help You Grow</h2>
            <div class="figma-grow-grid">
                <article><strong>01</strong><h3>Learn Skills</h3><p>Master UI/UX fundamentals</p></article>
                <article><strong>02</strong><h3>Build Portfolio</h3><p>Create stunning case studies</p></article>
                <article><strong>03</strong><h3>Practice UX</h3><p>Apply real-world methods</p></article>
                <article><strong>04</strong><h3>Apply for Jobs</h3><p>Land your dream role</p></article>
                <article><strong>05</strong><h3>Start Freelancing</h3><p>Build your client base</p></article>
                <article><strong>06</strong><h3>Grow Career</h3><p>Become a design leader</p></article>
            </div>
        </section>

        <section class="figma-community-section">
            <h2>What Our Community Says</h2>
            <div class="figma-testimonial-track">
                <article>
                    <div class="figma-person"><span>&#128105;&#8205;&#128188;</span><div><h3>Priya Sharma</h3><p>UI/UX Designer at Google</p></div></div>
                    <div class="figma-stars">*****</div>
                    <p>These bundles helped me land my dream job at Google. The portfolio templates and case study guides were game-changers!</p>
                </article>
                <article>
                    <div class="figma-person"><span>&#128104;&#8205;&#128187;</span><div><h3>Rahul Verma</h3><p>Freelance Designer</p></div></div>
                    <div class="figma-stars">*****</div>
                    <p>Started my freelancing career with the freelancing bundle. Made &#8377;50,000 in my first month!</p>
                </article>
                <article>
                    <div class="figma-person"><span>&#128105;&#8205;&#127912;</span><div><h3>Sneha Patel</h3><p>Product Designer at Flipkart</p></div></div>
                    <div class="figma-stars">*****</div>
                    <p>The design system kit is incredibly comprehensive. Saved me weeks of work on my projects.</p>
                </article>
                <article>
                    <div class="figma-person"><span>&#128105;&#8205;&#127912;</span><div><h3>Sneha Patel</h3><p>Product Designer at Flipkart</p></div></div>
                    <div class="figma-stars">*****</div>
                    <p>The design system kit is incredibly comprehensive. Saved me weeks of work on my projects.</p>
                </article>
                <article>
                    <div class="figma-person"><span>&#128105;&#8205;&#128188;</span><div><h3>Priya Sharma</h3><p>UI/UX Designer at Google</p></div></div>
                    <div class="figma-stars">*****</div>
                    <p>These bundles helped me land my dream job at Google. The portfolio templates and case study guides were game-changers!</p>
                </article>
            </div>
        </section>

        <section class="figma-explore-cta">
            <div class="figma-cta-content">
                <h2>Ready to Explore More Products?</h2>
                <p>Explore the complete UXPacific Shop collection and discover high-quality merch, mockups, UI templates, workbooks, badge packs, and creative digital assets designed especially for modern creators.</p>
                <div>
                    <a class="btn-primary" href="shopAll.php">Shop All Products</a>
                    <a class="btn-secondary" href="index.php#community">Join Our Community</a>
                </div>
            </div>
        </section>
    </main>

        <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-logo">
                <img src="img/1.png" alt="UX PACIFIC" class="logo" style="height:36px; margin: 0 auto; margin-bottom: 20px; display:block;" onerror="this.outerHTML='<span style=\'font-weight:700; font-size: 1.5rem; letter-spacing: -0.5px; display:flex; justify-content:center; gap:8px;\'><i class=\'ph ph-squares-four\'></i>UX PACIFIC</span>'" />
            </div>
            <nav class="footer-nav">
                <a href="#">Academy</a> &bull;
                <a href="#">Community</a> &bull;
                <a href="shopAll.php">Shop</a>
            </nav>
            <div class="social-links">
                <a href="https://www.linkedin.com/company/uxpacific/" class="social-icon" target="_blank" rel="noopener"><img src="img/in1.png" alt="LinkedIn" /></a>
                <a href="https://www.instagram.com/official_uxpacific/" class="social-icon" target="_blank" rel="noopener"><img src="img/i.webp" alt="Instagram" /></a>
                <a href="https://www.behance.net/ux_pacific" class="social-icon" target="_blank" rel="noopener"><img src="img/be.webp" alt="Behance" /></a>
                <a href="https://in.pinterest.com/uxpacific/" class="social-icon" target="_blank" rel="noopener"><img src="img/p.webp" alt="Pinterest" /></a>
                <a href="https://dribbble.com/social-ux-pacific" class="social-icon" target="_blank" rel="noopener"><img src="img/bl.webp" alt="Dribbble" /></a>
                <a href="#" class="social-icon"><img src="img/medium.png" alt="Medium" /></a>
            </div>
            <p class="contact-info">+91 9274061063 <span style="margin: 0 10px;">|</span> social@uxpacific.com</p>
            <p class="address">512, Majestic Building, Near Law Garden BRTS Stand, Ahmedabad</p>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 UX Pacific Community. All rights reserved.</p>
            <div class="legal-links">
                <a href="policies.php">Privacy Policy</a> |
                <a href="policies.php">Cookie Policy</a> |
                <a href="policies.php">Terms and Condition</a>
            </div>
        </div>
    </footer>

    <script>
        (() => {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            document.querySelectorAll('.figma-btn, .figma-btn-sm, .figma-cta-btn, .figma-contact-btn').forEach((button) => {
                button.addEventListener('pointerdown', () => button.classList.add('is-clicking'));
                button.addEventListener('pointerup', () => button.classList.remove('is-clicking'));
                button.addEventListener('pointerleave', () => button.classList.remove('is-clicking'));
            });
        })();
    </script>
</body>
</html>
