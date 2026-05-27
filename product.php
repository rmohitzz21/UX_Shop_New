<?php
require_once 'includes/config.php';

// Get product ID. If navbar opens product.php without an id, show the first active product.
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    $firstProduct = $conn->query("SELECT id FROM products WHERE is_active = 1 ORDER BY is_featured DESC, id ASC LIMIT 1");
    if ($firstProduct && ($first = $firstProduct->fetch_assoc())) {
        $product_id = (int) $first['id'];
    }
}

// Fetch product details
// We also fetch available_type column
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    // Redirect to shop if product not found or inactive
    header("Location: shopAll.php");
    exit;
}

// Prepare Data
$name = htmlspecialchars($product['name']);
$description = htmlspecialchars($product['description']);
$price = number_format($product['price'], 2);
$commercial_price_val = !empty($product['commercial_price']) ? $product['commercial_price'] : ($product['price'] * 1.4);
$commercial_price = number_format($commercial_price_val, 2);
$old_price = !empty($product['old_price']) ? number_format($product['old_price'], 2) : '';
$rating = $product['rating'] ?: '0.0';
$stock = $product['stock'];
$category = htmlspecialchars($product['category']);
$available_type = $product['available_type'] ?? 'physical'; // physical, digital, both

$category_lower_raw = strtolower((string) ($product['category'] ?? ''));
$fit_is_cover = ($available_type !== 'digital') && (
    strpos($category_lower_raw, 't-shirt') !== false ||
    strpos($category_lower_raw, 'hoodie') !== false ||
    strpos($category_lower_raw, 'tee') !== false ||
    strpos($category_lower_raw, 'apparel') !== false ||
    strpos($category_lower_raw, 'merch') !== false
);
$fit_class = $fit_is_cover ? 'fit-cover' : 'fit-contain';

// Handle Images
$images = [];

// 1. Main Image from 'image' column (Priority 1)
if (!empty($product['image'])) {
    $images[] = $product['image'];
}

// 2. Additional Images from 'additional_images' column
if (!empty($product['additional_images'])) {
    $add_imgs = json_decode($product['additional_images'], true);
    
    // Validate JSON decode
    if (json_last_error() === JSON_ERROR_NONE && is_array($add_imgs)) {
        foreach ($add_imgs as $img) {
            // Avoid duplicates of the main image or within additional images
            if (!in_array($img, $images)) {
                $images[] = $img;
            }
        }
    }
}

// 3. Fallback if no images found at all
if (empty($images)) {
    $images[] = 'img/sticker.webp';
}

// Ensure $images is indexed 0, 1, 2...
$images = array_values($images);

// Handle Tabs Data
function renderBulletHtml($raw, string $fallbackText): string {
    $text = trim((string) $raw);
    if ($text === '') {
        return '<div class="empty-state">Not specified</div>';
    }

    $lines = preg_split('/\r?\n/', $text) ?: [];
    $items = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        $line = preg_replace('/^[-•*]\s*/', '', $line);
        if ($line !== '') $items[] = $line;
    }

    if (!count($items)) {
        return '<div class="empty-state">Not specified</div>';
    }

    $lis = '';
    foreach ($items as $it) {
        $lis .= '<li>' . htmlspecialchars($it, ENT_QUOTES, 'UTF-8') . '</li>';
    }

    return '<ul class="feature-bullets">' . $lis . '</ul>';
}

$whats_included = renderBulletHtml($product['whats_included'] ?? '', 'Not specified');
$file_specs = renderBulletHtml($product['file_specification'] ?? '', 'Not specified');


// Fetch Related Products
$related_html = '';
// Function to fetch related products
function getRelatedProducts($conn, $product) {
    $related_html = '';
    $ids_str = '';

    // 1. Try manual related products
    if (!empty($product['related_products'])) {
        $related_ids = array_map('intval', explode(',', $product['related_products']));
        $ids_str = implode(',', $related_ids);
    } 
    
    // 2. If no manual related products, find by category
    if (empty($ids_str)) {
         $cat = $conn->real_escape_string($product['category']);
         $pid = (int)$product['id'];
         $rel_sql = "SELECT id FROM products WHERE category = '$cat' AND id != $pid AND is_active = 1 LIMIT 4";
         $rel_res = $conn->query($rel_sql);
         $ids = [];
         while($r = $rel_res->fetch_assoc()){ $ids[] = $r['id']; }
         if(!empty($ids)){
             $ids_str = implode(',', $ids);
         }
    }

    // Safety check for empty list
    if (!empty($ids_str)) {
        $rel_sql = "SELECT * FROM products WHERE id IN ($ids_str) AND is_active = 1 LIMIT 4";
        $rel_res = $conn->query($rel_sql);
        
        if ($rel_res && $rel_res->num_rows > 0) {
            while ($rel = $rel_res->fetch_assoc()) {
                $r_id = $rel['id'];
                $r_name = htmlspecialchars($rel['name']);
                $r_price = number_format($rel['price'], 2);
                $r_old = !empty($rel['old_price']) ? number_format($rel['old_price'], 2) : '';
                $r_img = !empty($rel['image']) ? $rel['image'] : 'img/sticker.webp';
                $r_rating = $rel['rating'] ?: '0.0';
                $r_desc = substr(htmlspecialchars($rel['description']), 0, 80) . '...';
                $r_cat = htmlspecialchars($rel['category']);
                
                // JS Safe strings
                $jsRName = addslashes($rel['name']);
                $jsRImage = addslashes($rel['image']);
                $jsRCategory = addslashes($rel['category']);
                $jsRAvailableType = addslashes($rel['available_type'] ?? 'physical');
                
                $related_html .= "
                  <article class='product-related-card' data-category='$r_cat'>
                    <a class='product-related-media' href='product.php?id=$r_id' aria-label='View $r_name'>
                      <img src='$r_img' alt='$r_name' onerror=\"this.src='img/sticker.webp'\" />
                      <span class='product-related-tag'>$r_cat</span>
                    </a>
                    <div class='product-related-body'>
                      <h3>$r_name</h3>
                      <p>$r_desc</p>
                      <div class='product-related-meta'>
                        <div class='product-related-price'>₹$r_price " . ($r_old ? "<span>₹$r_old</span>" : "") . "</div>
                        <div class='product-related-rating'>★ $r_rating</div>
                      </div>
                      <div class='product-related-actions'>
                        <button onclick=\"addToCart('$r_id', null, 1, {name: '$jsRName', price: {$rel['price']}, image: '$jsRImage', category: '$jsRCategory'}, '$jsRAvailableType')\" class='btn-primary small' aria-label='Add to cart'>Add to Cart</button>
                        <a href='product.php?id=$r_id' class='btn-ghost small'>View Details</a>
                      </div>
                    </div>
                  </article>";
            }
        }
    }
    return $related_html;
}

$related_html = getRelatedProducts($conn, $product);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <meta name="description" content="<?php echo $name; ?> - Premium design resource from UX Pacific Shop." />
    <?php include 'includes/auth-preload.php'; ?>
    <title><?php echo $name; ?> – UX Pacific Shop</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
      <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
    <link rel="stylesheet" href="style.css" />
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
  </head>
  <body>
    <?php include 'includes/header.php'; ?>

    <div class="product-page">
      <!-- LEFT : IMAGE GALLERY -->
      <div class="product-gallery">
        <div class="main-image">
          <span class="slide-badge" aria-label="Image count">
            <span id="slideCount">1 / <?php echo count($images); ?></span>
          </span>
          <img
            id="mainProductImage"
            class="<?php echo $fit_class; ?>"
            src="<?php echo $images[0]; ?>"
            alt="<?php echo $name; ?>"
          />
          <?php if (count($images) > 1): ?>
          <button class="nav prev" onclick="changeImage(-1)">‹</button>
          <button class="nav next" onclick="changeImage(1)">›</button>
          <?php endif; ?>
          <div class="image-dots" id="sliderDots">
             <?php foreach ($images as $i => $img): ?>
                <span onclick="setImage(<?php echo $i; ?>)" class="<?php echo $i === 0 ? 'active' : ''; ?>"></span>
             <?php endforeach; ?>
          </div>
        </div>
        <div class="thumbnail-row" aria-label="Thumbnails">
          <?php foreach ($images as $i => $img): ?>
            <img
              src="<?php echo $img; ?>"
              class="thumb <?php echo $i === 0 ? 'active' : ''; ?> <?php echo $fit_class; ?>"
              onclick="setImage(<?php echo $i; ?>)"
              alt="Thumbnail <?php echo $i + 1; ?>"
            />
          <?php endforeach; ?>
        </div>
      </div>

      <!-- RIGHT : PRODUCT INFO -->
      <div class="product-info modern-product-info">
        <div class="product-head-kicker">
          <span class="product-chip"><?php echo $category; ?></span>
          <span class="product-chip product-chip-muted"><?php echo htmlspecialchars(ucfirst($available_type)); ?></span>
        </div>
        <h1><?php echo $name; ?></h1>
        <p class="description"><?php echo $description; ?></p>
        <div class="rating">★★★★★ <span><?php echo $rating; ?> (<?php echo rand(50, 500); ?> reviews)</span></div>
        <div class="price">
          <span class="current">₹<?php echo $price; ?></span>
          <?php if ($old_price): ?>
            <span class="old">₹<?php echo $old_price; ?></span>
            <span class="badge">SALE</span>
          <?php endif; ?>
        </div>

         <div class="product-config-card">
          <!-- Dynamic Options based on Available Type -->
          
          <!-- Format Selection (Physical vs Digital) -->
          <?php if ($available_type === 'both'): ?>
              <div class="option">
                <label>Format</label>
                <select id="product-format-select" onchange="toggleFormat(this.value)">
                  <option value="physical">Physical Product</option>
                  <option value="digital">Digital Product</option>
                </select>
              </div>
          <?php else: ?>
              <!-- Hidden input for single type products -->
              <input type="hidden" id="product-format-select" value="<?php echo $available_type; ?>">
          <?php endif; ?>

          <!-- Digital Options Container -->
          <div id="digital-options" style="display: <?php echo ($available_type === 'digital' ? 'block' : 'none'); ?>;">
              <div class="option">
                <label>License Type</label>
                <select id="license-type" onchange="updatePrice()">
                  <option value="Personal">Personal License</option>
                  <option value="Commercial">Commercial License</option>
                </select>
              </div>
          </div>

          <!-- Physical Options Container -->
          <div id="physical-options" style="display: <?php echo ($available_type === 'physical' || $available_type === 'both' ? 'block' : 'none'); ?>;">
              <?php 
                $category_lower = strtolower($category);
                $show_sizes = (strpos($category_lower, 't-shirt') !== false || strpos($category_lower, 'hoodie') !== false);
              ?>
              
              <?php if ($show_sizes): ?>
              <div class="block">
                <label>Select Size</label>
                <div class="sizes" id="size-selector">
                  <button type="button" onclick="selectSize(this, 'S')">S</button>
                  <button type="button" onclick="selectSize(this, 'M')">M</button>
                  <button type="button" class="active" onclick="selectSize(this, 'L')">L</button>
                  <button type="button" onclick="selectSize(this, 'XL')">XL</button>
                </div>
              </div>
              <?php endif; ?>
          </div>
          
          <!-- Hidden Input for Logic -->
          <!-- Hidden Input for Logic -->
          <input type="hidden" id="product-selected-size" value="<?php echo $show_sizes ? 'L' : 'One Size'; ?>" />

          <div class="block">
            <label>Quantity</label>
            <div class="qty">
              <button onclick="qty(-1)">−</button>
              <span id="count">1</span>
              <button onclick="qty(1)">+</button>
            </div>
          </div>
        </div>

        <div class="product-buttons modern-product-buttons">
          <?php if ($stock <= 0 && $available_type === 'physical'): ?>
            <button class="btn-primary product-page-btn" disabled style="opacity:0.45; cursor:not-allowed;">Out of Stock</button>
          <?php else: ?>
            <?php if ($stock > 0 && $stock <= 5 && $available_type !== 'digital'): ?>
              <p style="color: #ef4444; font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 600;">Only <?php echo $stock; ?> left in stock!</p>
            <?php endif; ?>
            <button class="btn-ghost product-page-btn" onclick="addToCartWrapper(<?php echo $product_id; ?>)">Add to Cart</button>
            <button class="btn-primary product-page-btn" onclick="handleBuyNowWrapper(<?php echo $product_id; ?>)">Buy Now</button>
          <?php endif; ?>
        </div>

        <!-- TRUST CARDS -->
        <div class="trust-grid right-trust">
          <div class="trust-card">
            <span class="icon"><img src="img/m4.webp" alt="Secure Purchase Icon" /></span>
            <div><h4>Secure Purchase</h4><p>256-bit SSL encrypted</p></div>
          </div>
          <div class="trust-card">
            <span class="icon"><img src="img/m2.webp" alt="Instant Download Icon" /></span>
            <div><h4>Instant Download</h4><p>Access immediately</p></div>
          </div>
          <div class="trust-card">
            <span class="icon"><img src="img/m3.webp" alt="Safe Payment Icon" /></span>
            <div><h4>Safe Payment</h4><p>Multiple payment options</p></div>
          </div>
          <div class="trust-card">
            <span class="icon"><img src="img/m1.webp" alt="Refund Policy Icon" /></span>
            <div><h4>Refund Policy</h4><p>30-day money back</p></div>
          </div>
        </div>
      </div>
    </div>


    <!-- TABS -->
    <section class="product-extra">
      <div class="product-tabs">
        <button class="tab-btn active" data-tab="desc">Description</button>
        <button class="tab-btn" data-tab="included">What’s Included</button>
        <!-- Show File Specs ONLY if digital or available as digital -->
        <button class="tab-btn" id="specs-tab-btn" data-tab="specs" style="display: <?php echo ($available_type !== 'physical' ? 'inline-block' : 'none'); ?>">File Specification</button>
      </div>

      <div class="tab-box active" id="desc">
        <h3>About This Product</h3>
        <p><?php echo nl2br($description); ?></p>
      </div>
      <div class="tab-box" id="included">
        <div class="feature-list">
            <?php echo $whats_included; ?>
        </div>
      </div>
      <div class="tab-box" id="specs">
         <div class="feature-list">
            <?php echo $file_specs; ?>
        </div>
      </div>
    </section>

    <!-- RELATED PRODUCTS -->
    <?php if ($related_html): ?>
    <section class="related-section">
      <h2 class="section-title">Related Products</h2>
      <div class="related-products-grid">
        <?php echo $related_html; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- FOOTER -->
    <footer class="site-footer">
      <div class="footer-main">
        <div class="footer-top">
          <div class="footer-brand">
            <img src="img/logo1.webp" alt="UX Pacific" />
            <p>Design resources and merchandise trusted by creators worldwide.</p>
             <div class="footer-socials">
              <a href="https://dribbble.com/social-ux-pacific" target="_blank"><img src="img/bl.webp" alt="Dribbble" /></a>
              <a href="https://www.instagram.com/official_uxpacific/" target="_blank"><img src="img/i.webp" alt="Instagram" /></a>
              <a href="https://www.linkedin.com/company/uxpacific/" target="_blank"><img src="img/in1.png" alt="LinkedIn" /></a>
              <a href="https://in.pinterest.com/uxpacific/" target="_blank"><img src="img/p.webp" alt="Pinterest" /></a>
              <a href="https://www.behance.net/ux_pacific" target="_blank"><img src="img/be.webp" alt="Behance" /></a>
            </div>
          </div>
          <div class="footer-contact">
             <p>Support : +91 9274061063 | Email : <a href="mailto:hello@uxpacific.com"    style="text-decoration: none; color: inherit"
                target="_blank" >hello@uxpacific.com</a></p>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>©2026 UXPacific. All rights reserved.</p>
        <div class="footer-links">
          <a href="policies.php">Our Policies</a> <span>•</span> <a href="contact.php">Contact Us</a>
        </div>
      </div>
    </footer>

    <script src="script.js"></script>
    <script>
      // PHP-fed image array for slider logic
      const productImages = <?php echo json_encode($images); ?>;
      let currentIndex = 0;
      const mainImage = document.getElementById("mainProductImage");
      const thumbs = document.querySelectorAll(".thumb");
      const slideCount = document.getElementById("slideCount");
      const dots = document.querySelectorAll("#sliderDots span");

      function updateSlider() {
        if(mainImage) mainImage.src = productImages[currentIndex];
        if(slideCount) slideCount.textContent = `${currentIndex + 1} / ${productImages.length}`;
        
        thumbs.forEach((t, i) => t.classList.toggle("active", i === currentIndex));
        dots.forEach((d, i) => d.classList.toggle("active", i === currentIndex));
      }

      window.setImage = function(i) {
        currentIndex = i;
        updateSlider();
      }

      window.changeImage = function(s) {
        currentIndex = (currentIndex + s + productImages.length) % productImages.length;
        updateSlider();
      }

      // Size Selection
      window.selectSize = function(btn, size) {
          const buttons = document.querySelectorAll('#size-selector button');
          buttons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          const sizeInput = document.getElementById('product-selected-size');
          if(sizeInput) {
              sizeInput.value = size;
          } else {
              console.error('Size input not found! ID: product-selected-size');
          }
      }

      // Quantity Logic
      let quantity = 1;
      window.qty = function(change) {
        quantity += change;
        if (quantity < 1) quantity = 1;
        document.getElementById("count").textContent = quantity;
      }
      
      // Dynamic Logic for Price Update (License check)
      const basePrice = <?php echo floatval($product['price']); ?>;
      const commercialPrice = <?php echo floatval($commercial_price_val); ?>;
      
      window.updatePrice = function() {
          const format = document.getElementById('product-format-select').value;
          const license = document.getElementById('license-type').value;
          const priceDisplay = document.querySelector('.price .current');
          
          if (format === 'digital' && license === 'Commercial') {
              priceDisplay.innerText = '₹' + commercialPrice.toFixed(2);
          } else {
              // Default or Physical
              priceDisplay.innerText = '₹' + basePrice.toFixed(2);
          }
      }

      // Dynamic Logic for Format Selection (Physical/Digital)
      window.toggleFormat = function(format) {
          const physicalOpts = document.getElementById('physical-options');
          const digitalOpts = document.getElementById('digital-options');
          const specsTab = document.getElementById('specs-tab-btn');
          
          if (format === 'digital') {
              if(physicalOpts) physicalOpts.style.display = 'none';
              if(digitalOpts) digitalOpts.style.display = 'block';
              if(specsTab) specsTab.style.display = 'inline-block';
          } else {
              if(physicalOpts) physicalOpts.style.display = 'block';
              if(digitalOpts) digitalOpts.style.display = 'none';
              if(specsTab) specsTab.style.display = 'none';
          }
          // Also update price in case default is diff
          window.updatePrice();
      }

      // Get selected options for Cart
      window.getSelectedSize = function() {
          const typeInput = document.getElementById('product-format-select');
          const type = typeInput ? typeInput.value : 'physical';
          
          if (type === 'digital') {
              // Return License
              const licenseInput = document.getElementById('license-type');
              return licenseInput ? licenseInput.value : 'Personal License';
          } else {
              // Return Size or One Size
              const sizeInput = document.getElementById('product-selected-size');
              const val = sizeInput ? sizeInput.value : 'One Size';
              return val;
          }
      }

      window.addToCartWrapper = function(id) {
         // Determine current price being displayed
         const format = document.getElementById('product-format-select').value;
         const license = document.getElementById('license-type') ? document.getElementById('license-type').value : null;
         
         let finalPrice = basePrice;
         if (format === 'digital' && license === 'Commercial') {
             finalPrice = commercialPrice;
         }
         
         addToCart(
             id, 
             getSelectedSize(), 
             parseInt(document.getElementById('count').textContent), 
             {
                 name: '<?php echo addslashes($name); ?>', 
                 price: finalPrice, 
                 image: '<?php echo addslashes($images[0]); ?>'
             }, 
             format
         );
      }

      window.handleBuyNowWrapper = async function(id) {
         // Determine current price being displayed
         const format = document.getElementById('product-format-select').value;
         const license = document.getElementById('license-type') ? document.getElementById('license-type').value : null;
         
         let finalPrice = basePrice;
         if (format === 'digital' && license === 'Commercial') {
             finalPrice = commercialPrice;
         }

         // Auth Check
         const userSession = localStorage.getItem('userSession');
         let isLoggedIn = false;
         if (userSession) {
             try {
                const session = JSON.parse(userSession);
                if (session && session.id) isLoggedIn = true;
             } catch(e) {}
         }

         if (!isLoggedIn) {
             // Not logged in -> Redirect to Signin with message
             window.location.href = 'signin.php?redirect=checkout.php&message=Please sign in first';
             return;
         }
         
         // Logged in -> Add to Cart then Checkout
         try {
             await addToCart(
                 id, 
                 getSelectedSize(), 
                 parseInt(document.getElementById('count').textContent), 
                 {
                     name: '<?php echo addslashes($name); ?>', 
                     price: finalPrice, 
                     image: '<?php echo addslashes($images[0]); ?>'
                 }, 
                 format
             );
             window.location.href = 'checkout.php';
         } catch(e) {
             console.error("Buy Now failed", e);
         }
      }
      
      // Handle Sign Out (simple version)
      window.handleSignOut = function() {
          fetch('api/auth/logout.php', { method: 'POST', headers: { 'X-CSRF-Token': getCsrfToken() } })
            .finally(() => {
              localStorage.removeItem('userSession');
              window.location.href = 'index.php';
            });
      }
    </script>
  </body>
</html>
