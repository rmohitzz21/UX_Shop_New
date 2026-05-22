<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="UX Pacific Shop - Premium UX/UI design resources" />
    <title>UX Pacific – Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>
    <!-- <header class="navbar">
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
    </header> -->
    <?php include 'includes/header.php'; ?>
    <main class="main-content">
        <section class="hero-section">
            <div class="hero-container">
                <h1 class="hero-title">
                    Explore <span class="highlight-italic">High-Quality</span><br />
                    Products <span class="highlight-italic">Made for</span> Creators
                </h1>
                <p class="hero-subtitle">
                    View products on UXPacific Shop and purchase safely on partner platforms like<br> Freepik, Behance, and Gumroad.
                </p>
                <div class="hero-buttons">
                    <a href="#bundles" class="btn btn-primary" style="width: 200px; text-align: center;">Explore Bundles</a>
                    <a href="#products" class="btn btn-secondary" style="width: 200px; text-align: center;">Shop All Products</a>
                </div>
            </div>
        </section>

        <section class="how-works">
            <h2 class="section-heading">How UX Pacific Shop Works</h2>
            <div class="works-grid">
                <div class="work-card">
                    <div class="work-icon"><img src="img/ss/Vector.png" alt="" /></div>
                    <div class="work-text">
                        <h3>Browse Products</h3>
                        <p>Discover UXPacific merch and design assets in one place. View clean previews and product details before you decide.</p>
                    </div>
                </div>
                <div class="work-card">
                    <div class="work-icon"><img src="img/ss/hugeicons_shopping-basket-secure-01.png" alt="" /></div>
                    <div class="work-text">
                        <h3>Click 'Buy Now'</h3>
                        <p>Choose your product and tap the Buy button. You will be redirected to the official platform where the item is listed.</p>
                    </div>
                </div>
                <div class="work-card">
                    <div class="work-icon"><img src="img/ss/Vector-1.png" alt="" /></div>
                    <div class="work-text">
                        <h3>Purchase Securely</h3>
                        <p>Complete your purchase on trusted sites like Freepik, Behance, or Gumroad. Safe checkout and instant access.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="products" class="top-products">
            <h2 class="section-heading">Shop Top UX Pacific Products</h2>
            <div class="product-filters" style="margin-bottom: 20px;">
                <button class="filter-btn active" style="background: #6D3DFF; border-color: #6D3DFF;">All</button>
                <button class="filter-btn">Templates</button>
                <button class="filter-btn">UI Kit</button>
                <button class="filter-btn">Video Tutorials</button>
                <button class="filter-btn">Poster</button>
                <button class="filter-btn">Mockup</button>
                <button class="filter-btn">Layout</button>
            </div>
            <div class="product-filters">
                <button class="filter-btn">Bundles</button>
                <button class="filter-btn">Books</button>
                <button class="filter-btn">Workbook</button>
                <button class="filter-btn">Tshirts</button>
                <button class="filter-btn">Badges</button>
            </div>
            
            <div class="products-grid">
                <?php
                if (!isset($conn)) {
                    @require_once 'includes/config.php';
                }
                
                if (isset($conn)) {
                    $sql = "SELECT * FROM products WHERE is_active = 1 ORDER BY id ASC LIMIT 4";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $id = $row['id'];
                            $name = htmlspecialchars($row['name']);
                            $category = htmlspecialchars($row['category']);
                            $image = htmlspecialchars($row['image']);
                            $price = number_format($row['price']);
                            $old_price = !empty($row['old_price']) ? number_format($row['old_price']) : '';
                            $rating = $row['rating'] ?: '4.5';
                            $available_type = htmlspecialchars($row['available_type'] ?? 'physical');
                            $jsName = htmlspecialchars(json_encode($name), ENT_QUOTES, 'UTF-8');
                            $jsImage = htmlspecialchars(json_encode($image), ENT_QUOTES, 'UTF-8');
                            $jsCategory = htmlspecialchars(json_encode($category), ENT_QUOTES, 'UTF-8');
                            
                            $desc = "A clean design asset designed for communities, profiles and achievements.";
                            $specs = "Size: PNG / SVG / 1024px";
                            if(strpos(strtolower($name), 'workbook') !== false) {
                                $desc = "A practical workbook for UX learners and designers.";
                                $specs = "Size: A4 / Digital PDF";
                            } else if(strpos(strtolower($name), 'template') !== false) {
                                $desc = "Premium mobile mockup with clean branding and soft fabric shadows.";
                                $specs = "Size: Figma File / Auto Layout Ready";
                            }
                            
                            echo "
                            <article class='uxp-product-card'
    data-product-id='$id'
    data-category='$category'
    data-type='$available_type'
    data-price='{$row['price']}'
    data-rating='$rating'>

    <a href='product.php?id=$id'
       class='uxp-product-media'
       aria-label='View $name'>

        <img
            src='$image'
            alt='$name'
            loading='lazy'
            width='480'
            height='360'
            onerror=\"this.src='img/poster.webp'\"
        />

        <span class='uxp-product-badge-icon' aria-hidden='true'>
            <svg viewBox='0 0 24 24'
                 fill='none'
                 stroke='currentColor'
                 stroke-width='1.8'>

                <path d='M12 3v18M3 12h18'></path>
                <circle cx='12' cy='12' r='8'></circle>

            </svg>
        </span>
    </a>

    <div class='uxp-product-body'>

        <div class='uxp-product-title-row'>

            <h3>$name</h3>

            <div class='uxp-rating'
                 aria-label='Rating $rating out of 5'>

                <span aria-hidden='true'>&#9733;</span>
                <b>$rating</b>

            </div>

        </div>

        <p>$desc</p>

        <p class='uxp-product-spec'>$specs</p>

        <div class='uxp-product-meta'>

            <div class='uxp-product-price'>

                ₹$price

                " . ($old_price ? "
                    <span class='uxp-old-price'>
                        ₹$old_price (67% OFF)
                    </span>
                " : "") . "

            </div>

        </div>

        <div class='uxp-product-actions'>

            <a href='product.php?id=$id'
               class='uxp-card-btn uxp-card-btn-primary'>

               Buy Now

            </a>

            <button
                class='uxp-card-btn uxp-card-btn-secondary'
                type='button'

                onclick='addToCart(
                    \"$id\",
                    null,
                    1,
                    {
                        name: $jsName,
                        price: {$row['price']},
                        image: $jsImage,
                        category: $jsCategory,
                        description: \"$desc\"
                    },
                    \"$available_type\"
                )'

                " . ($row['stock'] <= 0 && $available_type === 'physical'
                    ? 'disabled'
                    : '') . ">

                Add to Cart

            </button>

        </div>

    </div>

</article>
                            ";
                        }
                    } else {
                        echo "
                        <div class='prod-card'>
                            <div class='prod-img'>
                                <img src='img/poster.webp' onerror=\"this.src='img/sticker.webp'\" />
                                <div class='prod-badge'><img src='img/ss/Vector.png' alt='' /></div>
                            </div>
                            <div class='prod-info'>
                                <div class='prod-header'><h3>UXPacific Badge Pack</h3><div class='rating'><i class='ph-fill ph-star' style='color:#F8B84E'></i> 4.5</div></div>
                                <p class='prod-desc'>A clean badge pack designed for communities, profiles, and achievements.</p>
                                <p class='prod-specs'>Size: PNG / SVG / 1024px</p>
                                <div class='prod-price'><span class='current'>₹199</span><span class='old' style='text-decoration: line-through; color: #8c89a0;'>₹499</span><span class='discount' style='color: #8c89a0;'>(67% OFF)</span></div>
                                <div class='prod-actions'><button class='btn btn-primary btn-sm' style='width:100%; border-radius: 999px; background: #6147bd; border: none;'>Buy Now</button><button class='btn btn-outline btn-sm' style='width:100%; border-radius: 999px; border: 1px solid #6147bd; background: transparent; color: white;'>View Details</button></div>
                            </div>
                        </div>
                        <div class='prod-card'>
                            <div class='prod-img'>
                                <img src='img/poster1.webp' onerror=\"this.src='img/sticker.webp'\" />
                                <div class='prod-badge'><img src='img/ss/Vector.png' alt='' /></div>
                            </div>
                            <div class='prod-info'>
                                <div class='prod-header'><h3>UXPacific UI Template</h3><div class='rating'><i class='ph-fill ph-star' style='color:#F8B84E'></i> 4.5</div></div>
                                <p class='prod-desc'>Premium hoodie mockup with clean branding and soft fabric shadows.</p>
                                <p class='prod-specs'>Size: Figma File / Auto Layout Ready</p>
                                <div class='prod-price'><span class='current'>₹399</span><span class='old' style='text-decoration: line-through; color: #8c89a0;'>₹499</span><span class='discount' style='color: #8c89a0;'>(67% OFF)</span></div>
                                <div class='prod-actions'><button class='btn btn-primary btn-sm' style='width:100%; border-radius: 999px; background: #6147bd; border: none;'>Buy Now</button><button class='btn btn-outline btn-sm' style='width:100%; border-radius: 999px; border: 1px solid #6147bd; background: transparent; color: white;'>View Details</button></div>
                            </div>
                        </div>
                         <div class='prod-card'>
                            <div class='prod-img'>
                                <img src='img/poster2.webp' onerror=\"this.src='img/sticker.webp'\" />
                                <div class='prod-badge'><img src='img/ss/Vector.png' alt='' /></div>
                            </div>
                            <div class='prod-info'>
                                <div class='prod-header'><h3>UXPacific UX Workbook</h3><div class='rating'><i class='ph-fill ph-star' style='color:#F8B84E'></i> 4.5</div></div>
                                <p class='prod-desc'>A practical workbook for UX learners and designers.</p>
                                <p class='prod-specs'>Size: A4 / Digital PDF</p>
                                <div class='prod-price'><span class='current'>₹499</span><span class='old' style='text-decoration: line-through; color: #8c89a0;'>₹499</span><span class='discount' style='color: #8c89a0;'>(67% OFF)</span></div>
                                <div class='prod-actions'><button class='btn btn-primary btn-sm' style='width:100%; border-radius: 999px; background: #6147bd; border: none;'>Buy Now</button><button class='btn btn-outline btn-sm' style='width:100%; border-radius: 999px; border: 1px solid #6147bd; background: transparent; color: white;'>View Details</button></div>
                            </div>
                        </div>
                        <div class='prod-card'>
                            <div class='prod-img'>
                                <img src='img/poster3.webp' onerror=\"this.src='img/sticker.webp'\" />
                                <div class='prod-badge'><img src='img/ss/Vector.png' alt='' /></div>
                            </div>
                            <div class='prod-info'>
                                <div class='prod-header'><h3>UXPacific UX Workbook</h3><div class='rating'><i class='ph-fill ph-star' style='color:#F8B84E'></i> 4.5</div></div>
                                <p class='prod-desc'>A practical workbook for UX learners and designers.</p>
                                <p class='prod-specs'>Size: A4 / Digital PDF</p>
                                <div class='prod-price'><span class='current'>₹499</span><span class='old' style='text-decoration: line-through; color: #8c89a0;'>₹499</span><span class='discount' style='color: #8c89a0;'>(67% OFF)</span></div>
                                <div class='prod-actions'><button class='btn btn-primary btn-sm' style='width:100%; border-radius: 999px; background: #6147bd; border: none;'>Buy Now</button><button class='btn btn-outline btn-sm' style='width:100%; border-radius: 999px; border: 1px solid #6147bd; background: transparent; color: white;'>View Details</button></div>
                            </div>
                        </div>
                        ";
                    }
                }
                ?>
            </div>
        </section>

        <!-- EXPLORE BY CATEGORY -->
        <section class="uxp-category-section" aria-labelledby="category-title">
  <div class="uxp-container">

    <div class="uxp-category-heading">
        <h2 id="category-title">Explore by Category</h2>
        <p>
            Browse our curated collection of premium resources organized by your needs.
        </p>
    </div>

    <div class="uxp-category-grid">

        <?php
        if (!isset($conn)) {
            @require_once 'includes/config.php';
        }

        $fallbackImages = [
            'img/poster.webp',
            'img/poster1.webp',
            'img/poster2.webp',
            'img/poster3.webp'
        ];

        $colorClasses = [
            'uxp-category-teal',
            'uxp-category-pink',
            'uxp-category-purple',
            'uxp-category-orange'
        ];

        if (isset($conn)) {

            $sql = "
                SELECT 
                    c.*,
                    COUNT(p.id) as product_count,
                    MIN(p.image) as product_image
                FROM categories c
                LEFT JOIN products p 
                    ON p.category = c.name
                GROUP BY c.id
                ORDER BY c.id ASC
                LIMIT 4
            ";

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {

                $index = 0;

                while($cat = $result->fetch_assoc()) {

                    $name = htmlspecialchars($cat['name']);
                    $slug = htmlspecialchars($cat['slug']);
                    $description = htmlspecialchars(
                        $cat['description'] 
                        ?: 'Explore premium curated design resources.'
                    );

                    $productCount = (int)$cat['product_count'];

                    $image = !empty($cat['product_image'])
                        ? htmlspecialchars($cat['product_image'])
                        : $fallbackImages[$index % count($fallbackImages)];

                    $colorClass = $colorClasses[$index % count($colorClasses)];

                    echo "

                    <a href='shopAll.php?category=$slug'
                       class='uxp-category-card $colorClass'>

                        <div class='uxp-category-art' aria-hidden='true'>

                            <span class='uxp-category-bubble'></span>

                            <span class='uxp-category-icon'>

                                <img 
                                    src='$image'
                                    alt='$name'
                                    loading='lazy'
                                    onerror=\"this.src='img/poster.webp'\"
                                />

                            </span>

                        </div>

                        <div class='uxp-category-body'>

                            <h3>$name</h3>

                            <span>$productCount+ items</span>

                            <p>$description</p>

                            <strong>

                                Explore

                                <svg viewBox='0 0 24 24'
                                     fill='none'
                                     stroke='currentColor'
                                     stroke-width='2'>

                                    <path d='M5 12h14'></path>
                                    <path d='m13 6 6 6-6 6'></path>

                                </svg>

                            </strong>

                        </div>

                    </a>
                    ";

                    $index++;
                }

            } else {

                echo "<p>No categories found.</p>";

            }
        }
        ?>

    </div>

</div>
</section>

        <section class="uxp-bundles-section" aria-labelledby="bundles-title">
  <div class="uxp-container">
    <div class="uxp-bundle-heading">
      <h2 id="bundles-title">Ready-Made Career Bundles</h2>
    </div>

    <div class="uxp-bundle-grid">
      <article class="uxp-bundle-card">
        <div class="uxp-bundle-image">
          <img src="img/poster1.webp" alt="Portfolio Builder Kit preview" loading="lazy" />
          <span>Most Popular</span>
        </div>

        <div class="uxp-bundle-content">
          <div class="uxp-bundle-title-row">
            <h3>Portfolio Builder Kit</h3>
            <span class="uxp-bundle-rating" aria-label="Rated 4.5 out of 5">
              &#9733; <b>4.5</b>
            </span>
          </div>

          <p>Build a recruiter-ready UI/UX portfolio in 5 days</p>

          <ul>
            <li>Complete, polished case study</li>
            <li>Portfolio website layout</li>
            <li>Typography &amp; grid system</li>
            <li>UX writing guide</li>
          </ul>

          <div class="uxp-bundle-footer">
            <strong>&#8377;1,499 <span>&#8377;4,999</span></strong>

            <div>
              <a href="shopAll.php" class="uxp-card-btn uxp-card-btn-primary">View Templates</a>
              <a href="shopAll.php" class="uxp-card-btn uxp-card-btn-secondary">Buy Now</a>
            </div>
          </div>
        </div>
      </article>

      <article class="uxp-bundle-card">
        <div class="uxp-bundle-image">
          <img src="img/poster1.webp" alt="Portfolio Builder Kit preview" loading="lazy" />
          <span>Most Popular</span>
        </div>

        <div class="uxp-bundle-content">
          <div class="uxp-bundle-title-row">
            <h3>Portfolio Builder Kit</h3>
            <span class="uxp-bundle-rating" aria-label="Rated 4.5 out of 5">
              &#9733; <b>4.5</b>
            </span>
          </div>

          <p>Build a recruiter-ready UI/UX portfolio in 5 days</p>

          <ul>
            <li>Complete, polished case study</li>
            <li>Portfolio website layout</li>
            <li>Typography &amp; grid system</li>
            <li>UX writing guide</li>
          </ul>

          <div class="uxp-bundle-footer">
            <strong>&#8377;1,499 <span>&#8377;4,999</span></strong>

            <div>
              <a href="shopAll.php" class="uxp-card-btn uxp-card-btn-primary">View Templates</a>
              <a href="shopAll.php" class="uxp-card-btn uxp-card-btn-secondary">Buy Now</a>
            </div>
          </div>
        </div>
      </article>

      <a href="shopAll.php" class="uxp-bundle-see-all" aria-label="See all bundles">
        <span aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
            <path d="M7 17 17 7"></path>
            <path d="M8 7h9v9"></path>
          </svg>
        </span>
        <b>See all</b>
      </a>
    </div>
  </div>
</section>
    </main>

    <section class="achieve-section">
        <h2 class="section-heading">What You'll Achieve</h2>
            <div class="achieve-grid">
            <div class="achieve-card">
                <div class="icon-wrap"><img src="img/ss/newsec/streamline-ultimate_job-responsibility-bag-hand.png" alt="briefcase" style="height:36px; width:36px; object-fit:contain;" /></div>
                <h4>Get Job Ready</h4>
                <p>Build a portfolio that makes recruiters say yes</p>
            </div>
            <div class="achieve-card">
                <div class="icon-wrap"><img src="img/ss/newsec/uil_arrow-growth.png" alt="trend up" style="height:36px; width:36px; object-fit:contain;" /></div>
                <h4>Land Internships</h4>
                <p>Stand out from hundreds of applicants</p>
            </div>
            <div class="achieve-card">
                <div class="icon-wrap"><img src="img/ss/newsec/Vector2.png" alt="laptop" style="height:36px; width:36px; object-fit:contain;" /></div>
                <h4>Start Freelancing</h4>
                <p>Earn while you learn with client-ready deliverables</p>
            </div>
            <div class="achieve-card">
                <div class="icon-wrap"><img src="img/ss/newsec/Vector-1.png" alt="books" style="height:36px; width:36px; object-fit:contain;" /></div>
                <h4>Master UI/UX</h4>
                <p>Learn the right way with structured resources</p>
            </div>
        </div>
    </section>

    <section class="explore-more">
        <div class="explore-banner">
            <h2>Ready to Explore More Products?</h2>
            <p>Explore the complete UXPacific Shop collection and discover high-quality merch, mockups, UI templates, workbooks, badge packs, and creative digital assets designed especially for modern creators.</p>
            <div class="explore-actions">
                <a href="shopAll.php" class="btn btn-primary" style="width: 200px; text-align:center;">Shop All Products</a>
                <a href="#" class="btn btn-secondary" style="width: 200px; text-align:center;">Join Our Community</a>
            </div>
        </div>
    </section>

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

    <script src="script.js"></script>
</body>
</html>
