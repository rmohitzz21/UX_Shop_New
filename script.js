// ── CSRF helpers ─────────────────────────────────────────────────────────────
// In-memory cache — populated from meta tag on first use, or fetched from API
// if the meta tag is absent (pages that haven't had the tag added yet).
let _csrfToken = null;
let _csrfFetchPromise = null;

/** Synchronous read — returns cached token or meta-tag value, never fetches. */
function getCsrfToken() {
  if (_csrfToken) return _csrfToken;
  const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  if (meta) _csrfToken = meta;
  return _csrfToken || '';
}

/** Async read — resolves with a valid token, fetching from /api/auth/csrf.php if needed. */
async function getCsrfTokenAsync() {
  if (_csrfToken) return _csrfToken;
  const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  if (meta) { _csrfToken = meta; return _csrfToken; }
  // Deduplicate concurrent calls — only one inflight request at a time
  if (!_csrfFetchPromise) {
    _csrfFetchPromise = fetch('api/auth/csrf.php')
      .then(r => r.json())
      .then(d => { _csrfToken = d.token || ''; _csrfFetchPromise = null; return _csrfToken; })
      .catch(() => { _csrfFetchPromise = null; return ''; });
  }
  return _csrfFetchPromise;
}

/**
 * secureFetch — drop-in replacement for fetch() that automatically injects
 * the X-CSRF-Token header on every request.
 * Usage: secureFetch('api/cart/add.php', { method:'POST', body: JSON.stringify(data) })
 */
async function secureFetch(url, options = {}) {
  const token = await getCsrfTokenAsync();
  return fetch(url, {
    ...options,
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
      ...(options.headers || {}),
    },
  });
}

// ── Security: HTML entity encoder — use on ALL user data injected via innerHTML ─
function esc(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#x27;');
}

// Mobile nav toggle with accessibility support
const mobileBtn = document.getElementById("mobile-menu-btn");
const mobileMenu = document.getElementById("mobile-menu");

if (mobileBtn && mobileMenu) {
  mobileBtn.addEventListener("click", () => {
    const isOpen = mobileMenu.classList.toggle("open");
    mobileBtn.setAttribute("aria-expanded", isOpen);
  });
  
  // Close menu when clicking outside
  document.addEventListener("click", (e) => {
    if (!mobileBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
      mobileMenu.classList.remove("open");
      mobileBtn.setAttribute("aria-expanded", "false");
    }
  });
  
  // Close menu on Escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && mobileMenu.classList.contains("open")) {
      mobileMenu.classList.remove("open");
      mobileBtn.setAttribute("aria-expanded", "false");
      mobileBtn.focus();
    }
  });
}



// Active state is synchronized from the current URL below, so it also works
// after redirects, reloads, and direct deep links.





// ---------- PRODUCT FILTERING ----------
// const filterPills = document.querySelectorAll(".filter-pill");
// const productCards = document.querySelectorAll(".product-card");

// filterPills.forEach((pill) => {
//   pill.addEventListener("click", () => {
//     const filter = pill.dataset.filter; // e.g. "tshirts", "mockup", "all"

//     // active pill UI
//     filterPills.forEach((p) => p.classList.remove("active"));
//     pill.classList.add("active");

//     // show/hide products
//     productCards.forEach((card) => {
//       const categories = (card.dataset.category || "").split(","); // array

//       const match =
//         filter === "all" || categories.map((c) => c.trim()).includes(filter);

//       card.style.display = match ? "" : "none";
//     });
//   });
// });




 // --- MOBILE MENU LOGIC ---
    // const mobileBtn2 = document.getElementById('mobile-menu-btn');
    // const mobileMenu2 = document.getElementById('mobile-menu');
    
    // if (mobileBtn2 && mobileMenu2) {
    //     mobileBtn2.addEventListener('click', () => {
    //       mobileMenu2.classList.toggle('open');
    //     });
    // }

    // --- SMOOTH SCROLL ANIMATION LOGIC (Vanilla JS) ---
    const scrollContainer = document.querySelector('.hero-scroll-container');
    const cards = document.querySelectorAll('.hero-card');
    
    // Variables for smoothing (Linear Interpolation)
    let currentProgress = 0;
    let targetProgress = 0;
    const ease = 0.08; // 0.08 gives a heavy, professional, smooth feel

    function lerp(start, end, t) {
      return start * (1 - t) + end * t;
    }

    // Update target progress based on scroll position
    function handleScroll() {
      if(!scrollContainer) return;
      
      const viewportHeight = window.innerHeight;
      const rect = scrollContainer.getBoundingClientRect();
      
      // Calculate raw progress 0 to 1
      const totalScrollDistance = scrollContainer.offsetHeight - viewportHeight;
      let rawProgress = -rect.top / totalScrollDistance;
      
      // Clamp
      if (rawProgress < 0) rawProgress = 0;
      if (rawProgress > 1) rawProgress = 1;
      
      targetProgress = rawProgress;
    }

    // Animation Loop (Runs every frame for smoothness)
    function animate() {
      // Lerp current value towards target value
      // This creates the "buttery smooth" delay effect
      currentProgress = lerp(currentProgress, targetProgress, ease);
      
      const width = window.innerWidth;
      const height = window.innerHeight;

      // --- CONFIGURATION ---
      
      // START POSITIONS (Deck at Bottom - Half Visible)
      // anchored at top: 100% (bottom of screen).
      // y: -100 means the center of the card is 100px above bottom.
      // Since cards are ~300px tall, this makes them look like a deck peeking up.
      const startPositions = [
        { x: -60, y: -70, r: -15 },  // Card 1 (Left fan)
        { x: 60,  y: -70, r: 15 },   // Card 2 (Right fan)
        { x: -120, y: -40, r: -25 }, // Card 3 (Far Left fan)
        { x: 120,  y: -40, r: 25 },  // Card 4 (Far Right fan)
        { x: 0,   y: -100, r: 0 },   // Card 5 (Middle - Top of stack)
      ];

      // END POSITIONS (Spread Out)
      const endPositions = [
          { x: -width * 0.35, y: -height * 0.85, r: -20, s: 1.0 }, // Top Left 
          { x: width * 0.35,  y: -height * 0.85, r: 20,  s: 1.0 }, // Top Right 
          { x: -width * 0.35, y: -height * 0.25, r: -10, s: 1.0 }, // Bottom Left 
          { x: width * 0.35,  y: -height * 0.25, r: 10,  s: 1.0 }, // Bottom Right 
          { x: 0,             y: 0,              r: 0,   s: 1.3 }, // CENTER (Big & Centered)
      ];

      cards.forEach((card, index) => {
        if (!startPositions[index] || !endPositions[index]) return;

        const start = startPositions[index];
        const end = endPositions[index];

        const currentX = lerp(start.x, end.x, currentProgress);
        const currentY = lerp(start.y, end.y, currentProgress);
        const currentR = lerp(start.r, end.r, currentProgress);
        
        // Scale logic: Start smallish (0.7), go to specific end scale
        // Card 5 ends at 1.3, others at 1.0
        const startScale = 0.7;
        const endScale = end.s || 1.0;
        const currentScale = lerp(startScale, endScale, currentProgress);
        
        card.style.transform = `translate(calc(-50% + ${currentX}px), calc(-50% + ${currentY}px)) rotate(${currentR}deg) scale(${currentScale})`;
      });

      // NOTE: Text animation removed so it stays STATIC as requested.

      requestAnimationFrame(animate);
    }

    window.addEventListener('scroll', handleScroll);
    window.addEventListener('resize', handleScroll);
    
    // Kick off animation loop
    animate();



/* Helper: Generate Product Card HTML (matches index.php style) */
function generateProductCardHTML(product) {
    const category = product.category || 'Uncategorized';
    
    // Safety for JS strings in onclick
    const safeName = (product.name || '').replace(/'/g, "\\'");
    const safeImage = (product.image || '').replace(/'/g, "\\'");
    const safeCategory = (category || '').replace(/'/g, "\\'");
    
    // Formatting
    const price = Number(product.price) || 0;
    const oldPrice = product.old_price ? Number(product.old_price) : null;
    const desc = (product.description || '');

    return `
    <article class="product-card" data-category="${esc(category)}">
      <div class="product-img">
        <img src="${esc(product.image)}" alt="${esc(product.name)}" onerror="this.src='img/sticker.webp'" />
        <span class="product-tag">${esc(category)}</span>
      </div>
      <div class="product-body">
        <h3>${esc(product.name)}</h3>
        <p style="margin-bottom: 0.5rem; font-size: 0.95rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${esc(desc)}</p>
        <div class="product-meta">
          <div class="product-price">$${price.toLocaleString()} ${oldPrice ? `<span>$${oldPrice.toLocaleString()}</span>` : ''}</div>
          <div class="product-rating">★ ${esc(product.rating || '0.0')}</div>
        </div>
        <div class="product-actions">
          <button onclick="addToCart('${esc(product.id)}', null, 1, {name: '${safeName}', price: ${price}, image: '${safeImage}', category: '${safeCategory}'})" class="btn-primary small" aria-label="Add to cart" ${product.stock <= 0 ? 'disabled' : ''}>Add to Cart</button>
          <a href="product.php?id=${encodeURIComponent(product.id)}" class="btn-ghost small">View Details</a>
        </div>
      </div>
    </article>
    `;
}

document.addEventListener("DOMContentLoaded", async function () {
  // only run on shopAll page
  if (!document.body.classList.contains("shopAll")) return;

  // NOW select the elements, after they have been injected into the DOM (via PHP)
  const filterButtons = Array.from(document.querySelectorAll(".shop-all-filters .filter-pill"));
  const productCards = Array.from(document.querySelectorAll(".product-card"));

  if (!filterButtons.length || !productCards.length) return; // nothing to do

  // Normalize text helper
  const norm = (s) => (s || "").toString().trim().toLowerCase();

  const ANIM_DURATION = 380; // ms
  const STAGGER = 65; // ms between cards entering
  let isFiltering = false;
  let queuedFilter = null;

  // Utility to test if card matches filter - IMPROVED CATEGORY MATCHING
  function cardMatchesFilter(card, filterValue) {
    if (!filterValue || filterValue === "all") return true;
    
    // Normalize filter value
    const normalizedFilter = norm(filterValue);
    
    // Get card categories - handle multiple categories separated by comma
    const cardCategory = norm(card.dataset.category || "");
    const cardCats = cardCategory
      .split(",")
      .map(c => c.trim())
      .filter(Boolean);
    
    // Also check the product-tag text content as fallback
    const productTag = card.querySelector(".product-tag");
    const tagText = productTag ? norm(productTag.textContent.trim()) : "";
    
    // Match against:
    // 1. Direct category match
    // 2. Tag text match
    // 3. Handle special cases like "UI Template" vs "template"
    const matchesCategory = cardCats.some(cat => {
      const normalizedCat = norm(cat);
      return normalizedCat === normalizedFilter || 
             normalizedCat.includes(normalizedFilter) ||
             normalizedFilter.includes(normalizedCat);
    });
    
    const matchesTag = tagText && (
      tagText === normalizedFilter ||
      tagText.includes(normalizedFilter) ||
      normalizedFilter.includes(tagText)
    );
    
    // Special handling for "UI Template" / "template" / "Template"
    if (normalizedFilter === "template" || normalizedFilter === "ui template") {
      return matchesCategory || matchesTag || 
             cardCats.some(cat => norm(cat).includes("template"));
    }
    
    // Special handling for "Badges" / "Badge"
    if (normalizedFilter === "badges" || normalizedFilter === "badge") {
      return matchesCategory || matchesTag ||
             cardCats.some(cat => norm(cat).includes("badge"));
    }
    
    return matchesCategory || matchesTag;
  }

  // Capture the original labels so we can append counts cleanly
  const baseLabels = new Map();
  filterButtons.forEach(btn => {
    baseLabels.set(btn, btn.textContent.trim());
  });

  // Pre-compute category counts for badge display
  const categoryCounts = productCards.reduce((acc, card) => {
    const cats = norm(card.dataset.category || "")
      .split(",")
      .map(c => c.trim())
      .filter(Boolean);
    cats.forEach(cat => {
      acc.set(cat, (acc.get(cat) || 0) + 1);
    });
    return acc;
  }, new Map());

  // Insert / update count badge on each pill
  function renderPillCounts() {
    filterButtons.forEach(btn => {
      const baseLabel = baseLabels.get(btn) || btn.textContent.trim();
      const key = norm(btn.dataset.filter || "all");
      const count = key === "all" ? productCards.length : (categoryCounts.get(key) || 0);

      // Reset text then append a count badge span for consistent layout
      btn.textContent = baseLabel;
      let badge = btn.querySelector(".pill-count");
      if (!badge) {
        badge = document.createElement("span");
        badge.className = "pill-count";
      }
      badge.textContent = count;
      btn.appendChild(badge);
    });
  }

  // Ensure all cards start visible and clean - INITIAL STATE
  productCards.forEach(card => {
    card.style.display = "";
    card.style.visibility = "visible";
    card.classList.remove("is-hidden", "is-exiting", "will-show");
    card.style.removeProperty("--card-delay");
    // Ensure card is in normal state
    card.style.opacity = "";
    card.style.transform = "";
  });

  renderPillCounts();

  // Animate cards out before removing from the grid - SMOOTH EXIT
  function animateOut(cards) {
    if (!cards.length) return Promise.resolve();
    return Promise.all(
      cards.map((card, idx) => new Promise(resolve => {
        // Remove any entrance classes
        card.classList.remove("will-show");
        // Add exit class for animation
        card.classList.add("is-exiting");
        card.style.setProperty("--card-delay", `${idx * STAGGER * 0.3}ms`);

        const finalize = () => {
          // Mark as hidden and remove from layout
          card.classList.add("is-hidden");
          card.classList.remove("is-exiting");
          card.style.display = "none";
          card.style.removeProperty("--card-delay");
          card.style.visibility = "hidden";
          resolve();
        };

        // Wait for transition to complete
        card.addEventListener("transitionend", (evt) => {
          if (evt.target !== card || evt.propertyName !== "opacity") return;
          finalize();
        }, { once: true });

        // Fallback timeout
        setTimeout(finalize, ANIM_DURATION + 100);
      }))
    );
  }

  // Animate cards into view with staggered delays - SMOOTH ENTRANCE
  function animateIn(cards) {
    if (!cards.length) return Promise.resolve();

    // Prepare cards for entrance animation
    cards.forEach((card, idx) => {
      // Remove hidden/exiting states
      card.classList.remove("is-hidden", "is-exiting");
      // Make visible in layout
      card.style.display = "";
      card.style.visibility = "visible";
      // Start with will-show class (hidden state)
      card.classList.add("will-show");
      // Set staggered delay
      card.style.setProperty("--card-delay", `${idx * STAGGER}ms`);
    });

    // Use double rAF to ensure styles are applied before animation
    return new Promise(resolve => {
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          // Remove will-show to trigger entrance animation
          cards.forEach((card, idx) => {
            card.classList.remove("will-show");
            // Clean up delay after animation completes
            card.addEventListener("transitionend", (evt) => {
              if (evt.target !== card || evt.propertyName !== "opacity") return;
              card.style.removeProperty("--card-delay");
            }, { once: true });
          });
          // Resolve after all animations should be complete
          setTimeout(resolve, ANIM_DURATION + cards.length * STAGGER + 50);
        });
      });
    });
  }

  // Main filter handler with graceful animation + layout stability
  function applyFilter(filterValue, clickedBtn) {
    // If a filter is already mid-animation, queue the latest request
    if (isFiltering) {
      queuedFilter = { filterValue, clickedBtn };
      return;
    }
    isFiltering = true;

    // Update active state on buttons
    filterButtons.forEach(b => b.classList.toggle("active", b === clickedBtn));

    // Find matching cards using improved matching logic
    const matchingCards = productCards.filter(card => cardMatchesFilter(card, filterValue));
    
    // Cards that should exit (currently visible but don't match)
    const exiting = productCards.filter(card => {
      const isCurrentlyVisible = !card.classList.contains("is-hidden") && 
                                  card.style.display !== "none";
      const shouldBeVisible = matchingCards.includes(card);
      return isCurrentlyVisible && !shouldBeVisible;
    });
    
    // Cards that should enter (currently hidden but should match)
    const entering = matchingCards.filter(card => {
      const isCurrentlyHidden = card.classList.contains("is-hidden") || 
                                 card.style.display === "none";
      return isCurrentlyHidden;
    });

    // Animate out first, then animate in
    animateOut(exiting)
      .then(() => {
        // Small delay to ensure DOM updates
        return new Promise(resolve => setTimeout(resolve, 50));
      })
      .then(() => animateIn(entering))
      .then(() => {
        isFiltering = false;
        if (queuedFilter) {
          const { filterValue: queuedValue, clickedBtn: queuedBtn } = queuedFilter;
          queuedFilter = null;
          applyFilter(queuedValue, queuedBtn);
        }
      })
      .catch(err => {
        console.error("Filter animation error:", err);
        isFiltering = false;
      });

    // Optional: scroll first visible card into view on mobile
    setTimeout(() => {
      const firstVisible = matchingCards.find(c => {
        return !c.classList.contains("is-hidden") && 
               c.style.display !== "none";
      });
      if (firstVisible && window.innerWidth <= 900) {
        firstVisible.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    }, ANIM_DURATION + 100);
  }

  // attach listeners
  filterButtons.forEach(btn => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      const filterVal = btn.dataset.filter ? btn.dataset.filter.trim() : "all";
      applyFilter(filterVal, btn);
    });
  });

  // Optional: support URL query like ?filter=Stickers
  const urlParams = new URLSearchParams(window.location.search);
  const initialFilter = urlParams.get("filter");
  if (initialFilter) {
    const matchingBtn = filterButtons.find(b => norm(b.dataset.filter) === norm(initialFilter));
    if (matchingBtn) {
      matchingBtn.click();
    }
  } else if (filterButtons.length) {
    // Default to the first pill being active to avoid "no active" flicker
    filterButtons[0].classList.add("active");
  }
});


document.querySelectorAll(".sizes button").forEach(btn => {
  btn.addEventListener("click", () => {
    document.querySelectorAll(".sizes button")
      .forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
  });
});

// Qty selector logic

 let count = 1;
  const countEl = document.getElementById("count");

  function qty(change) {
    count += change;

    // Prevent quantity from going below 1
    if (count < 1) {
      count = 1;
    }
    // Max 10 per product
    if (count > 10) {
      count = 10;
      showToast('Maximum 10 items per product', 'error');
    }

    countEl.textContent = count;
  }



  
  const tabButtons = document.querySelectorAll(".tab-btn");
  const tabBoxes = document.querySelectorAll(".tab-box");

  tabButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const target = btn.dataset.tab;

      // Remove active state from all tabs
      tabButtons.forEach((b) => b.classList.remove("active"));
      tabBoxes.forEach((box) => box.classList.remove("active"));

      // Activate clicked tab + content
      btn.classList.add("active");
      document.getElementById(target).classList.add("active");
    });
  });


let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Initialize Cart on Load
document.addEventListener('DOMContentLoaded', function() {
  const userSession = getUserSession();
  if (userSession && userSession.id) {
     // Load from API if logged in
     fetchCartFromAPI();
  } else {
     // LocalStorage already loaded
     updateCartCount();
     if (window.location.pathname.includes('cart.php')) loadCartPage();
  }
});

function fetchCartFromAPI() {
    fetch('api/cart/list.php')
      .then(res => {
         if(res.status === 401) return []; // Guest/Expired
         return res.json();
      })
      .then(data => {
         if(data.status === 'success') {
             cart = data.data; // Sync global cart variable
             saveCart(); // Optional: Keep local storage in sync or just rely on memory? 
                         // Better to just update memory for logged in users to avoid conflicts.
                         // But for now, let's keep cart variable as source of truth.
             updateCartCount();
             if (typeof window.renderCartDrawer === 'function') window.renderCartDrawer();
             if (window.location.pathname.includes('cart.php')) loadCartPage();
             if (window.location.pathname.includes('checkout.php')) loadCheckoutPage();
         }
      })
      .catch(err => console.error('Error fetching cart:', err));
}

// Product database (temporary - replace with API call later)
const products = {
  'sticker-pack-001': { id: 'sticker-pack-001', name: 'Designer Sticker Pack', price: 499, oldPrice: 899, image: 'img/sticker.webp', category: 'Stickers' },
  'tshirt-001': { id: 'tshirt-001', name: 'UXPacific Classic T-Shirt', price: 349, oldPrice: 899, image: 'img/tule.webp', category: 'T-Shirts' },
  'booklet-001': { id: 'booklet-001', name: 'UXPacific Booklet', price: 349, oldPrice: 899, image: 'img/bk.webp', category: 'Booklet' },
  'mockup-001': { id: 'mockup-001', name: 'UXPacific Mockup', price: 349, oldPrice: 899, image: 'img/mockup.webp', category: 'Mockup' },
  'badge-001': { id: 'badge-001', name: 'UXPacific Badge Pack', price: 349, oldPrice: 899, image: 'img/badg.webp', category: 'Badges' },
  'template-001': { id: 'template-001', name: 'UXPacific UI Template', price: 349, oldPrice: 899, image: 'img/template.webp', category: 'Template' },
  'workbook-001': { id: 'workbook-001', name: 'UXPacific Workbook', price: 349, oldPrice: 899, image: 'img/workbk.webp', category: 'Workbook' }
};

// Add to cart
// Add to cart
// Add to cart
function addToCart(productId, size = null, quantity = 1, explicitDetails = null, productFormat = null) {
  return new Promise((resolve, reject) => {
    // If explicitDetails are provided (e.g. from shop page), use them for immediate feedback.
    // Otherwise, default to placeholders. The cart page will fetch fresh data from API using ID.
    
    let product = {
       id: productId,
       name: (explicitDetails && explicitDetails.name) ? explicitDetails.name : 'Product',
       price: (explicitDetails && explicitDetails.price) ? explicitDetails.price : 0,
       image: (explicitDetails && explicitDetails.image) ? explicitDetails.image : 'img/sticker.webp',
       description: (explicitDetails && explicitDetails.description) ? explicitDetails.description : ''
    };
  
    // Get available_type priority: Argument > localStorage > default
    // If product is 'both', default to 'physical' (user chooses on product page)
    let available_type = productFormat || localStorage.getItem('available_type') || 'physical';
    if (available_type === 'both') available_type = 'physical';
    
    const userSession = getUserSession();
  
    if (userSession && userSession.id) {
        // LOGGED IN: Use API
        const payload = {
            product_id: productId,
            quantity: quantity,
            size: size,
            available_type: available_type,
            details: product
        };
  
        fetch('api/cart/add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Item added to cart!', 'success');
                fetchCartFromAPI(); // Refresh cart
                resolve(data);
            } else {
                showToast(data.message || 'Failed to add item', 'error');
                reject(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Error adding item', 'error');
            reject(err);
        });
  
    } else {
        // GUEST: Use LocalStorage
        const existingIndex = cart.findIndex(
          item => item.id === productId && item.size === size && item.available_type === available_type
        );
        
        if (existingIndex > -1) {
          cart[existingIndex].quantity += quantity;
          if (cart[existingIndex].quantity > 10) {
            cart[existingIndex].quantity = 10;
            showToast('Maximum 10 items per product', 'error');
          }
        } else {
          if (quantity > 10) quantity = 10;
          cart.push({
            id: productId,
            name: product.name,
            price: product.price,
            image: product.image,
            size: size,
            quantity: quantity,
            available_type: available_type,
            description: product.description
          });
        }
        
        saveCart();
        updateCartCount();
        showToast('Item added to cart!', 'success');
        
        // If on cart page, refresh it
        if (window.location.pathname.includes('cart.php')) {
          loadCartPage();
        }
        resolve({ status: 'success' });
    }
  });
}

// Remove from cart
// Helper to normalize size for comparison
function normalizeSize(s) {
  return (s === null || s === undefined || s === 'null') ? '' : String(s);
}

// Remove from cart
function removeFromCart(productId, size = null) {
  const userSession = getUserSession();
  
  // Find item to get its available_type (needed for API)
  const item = cart.find(i => String(i.id) === String(productId) && normalizeSize(i.size) === normalizeSize(size));
  const available_type = item ? (item.available_type || 'physical') : 'physical';

  if (userSession && userSession.id) {
      // LOGGED IN: API
      fetch('api/cart/remove.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken()},
          body: JSON.stringify({
              product_id: productId,
              size: size,
              available_type: available_type
          })
      })
      .then(res => res.json())
      .then(data => {
          if(data.status === 'success') {
              showToast('Item removed from cart', 'success');
              fetchCartFromAPI();
          } else {
              showToast('Failed to remove item', 'error');
          }
      });
  } else {
      // GUEST: LocalStorage
      cart = cart.filter(item => {
          // Keep item if ID doesn't match OR size doesn't match
          const idMatch = String(item.id) === String(productId);
          const sizeMatch = normalizeSize(item.size) === normalizeSize(size);
          return !(idMatch && sizeMatch); 
      });
      saveCart();
      updateCartCount();
      showToast('Item removed from cart', 'success');
      
      if (window.location.pathname.includes('cart.php')) {
        loadCartPage();
      }
  }
}

// Update cart item quantity
function updateCartQuantity(productId, size, newQuantity) {
  const userSession = getUserSession();

  if (newQuantity <= 0) {
      removeFromCart(productId, size);
      return;
  }

  if (newQuantity > 10) {
      showToast('Maximum 10 items per product', 'error');
      return;
  }

  // Find item to get its available_type (needed for API)
  const item = cart.find(i => String(i.id) === String(productId) && normalizeSize(i.size) === normalizeSize(size));
  const available_type = item ? (item.available_type || 'physical') : 'physical';
  
  if (userSession && userSession.id) {
     // LOGGED IN: API
     fetch('api/cart/update.php', {
         method: 'POST',
         headers: {'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken()},
         body: JSON.stringify({
             product_id: productId,
             quantity: newQuantity,
             size: size,
             available_type: available_type
         })
     })
     .then(res => res.json())
     .then(data => {
         if(data.status === 'success') {
             fetchCartFromAPI(); // Refresh to update totals etc
         } else {
             showToast('Failed to update quantity', 'error');
         }
     });

  } else {
      // GUEST: LocalStorage
      const item = cart.find(item => {
          const idMatch = String(item.id) === String(productId);
          const sizeMatch = normalizeSize(item.size) === normalizeSize(size);
          return idMatch && sizeMatch;
      });
      
      if (item) {
          item.quantity = newQuantity;
          saveCart();
          updateCartCount();
          if (window.location.pathname.includes('cart.php')) {
            loadCartPage();
          }
      } else {
          console.warn("Item not found for update:", productId, size);
      }
  }
}

// Save cart to localStorage
function saveCart() {
  localStorage.setItem('cart', JSON.stringify(cart));
}

// Update cart count badge
function updateCartCount() {
  const count = cart.reduce((sum, item) => sum + item.quantity, 0);
  const badges = document.querySelectorAll('#cart-count');
  badges.forEach(badge => {
    if (badge) {
      badge.textContent = count;
      badge.style.display = count > 0 ? 'flex' : 'none';
      if (count > 0) {
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }
  });
}

// Calculate cart total
function getCartTotal() {
  return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
}

// Load cart page
// Global cache for product details to ensure instant IO
let globalProductDetailsCache = {};

// Load cart page
async function loadCartPage() {
  const digitalContainer = document.getElementById('cart-items-digital');
  const physicalContainer = document.getElementById('cart-items-physical');
  const digitalSection = document.getElementById('cart-section-digital');
  const physicalSection = document.getElementById('cart-section-physical');
  const cartEmpty = document.getElementById('cart-empty');
  const checkoutBtn = document.getElementById('checkout-btn');

  if (!digitalContainer && !physicalContainer) return;

  const cartSummary = document.querySelector('.cart-summary-wrapper');

  if (cart.length === 0) {
    if (cartEmpty) cartEmpty.style.display = 'block';
    if (cartSummary) cartSummary.style.display = 'none';
    if (digitalSection) digitalSection.style.display = 'none';
    if (physicalSection) physicalSection.style.display = 'none';
    if (digitalContainer) digitalContainer.innerHTML = '';
    if (physicalContainer) physicalContainer.innerHTML = '';
    return;
  }

  if (cartEmpty) cartEmpty.style.display = 'none';
  if (cartSummary) cartSummary.style.display = 'block';

  // Helper: render a single cart item HTML
  const renderItemHTML = (item, detailsSource) => {
    const apiProduct = detailsSource[item.id] || detailsSource[String(item.id)];
    const name = apiProduct ? apiProduct.name : (item.name || 'Loading...');
    const image = apiProduct ? apiProduct.image : (item.image || 'img/sticker.webp');
    const price = apiProduct ? Number(apiProduct.price) : (item.price || 0);
    const description = apiProduct ? apiProduct.description : (item.description || '');
    // Use product's available_type from API as fallback if cart item doesn't have it
    const productType = item.available_type || (apiProduct ? apiProduct.available_type : 'physical');
    // Sync back to cart item so filters work correctly
    if (!item.available_type && productType) item.available_type = productType;
    const itemTotal = price * item.quantity;

    return {
      html: `
        <div class="cart-item">
          <img src="${esc(image)}" alt="${esc(name)}" class="cart-item-image" onerror="this.src='img/sticker.webp'" />
          <div class="cart-item-details">
            <h3 class="cart-item-title">${esc(name)}</h3>
            <p class="cart-item-desc" style="font-size: 0.85rem; color: #777; margin-bottom: 4px;">${esc(description || '')}</p>
            <p class="cart-item-meta">
              ${productType ? `Format: <span style="text-transform:capitalize">${esc(productType)}</span> • ` : ''}
              ${item.size ? `Size: ${esc(item.size)} • ` : ''}
              Quantity: ${item.quantity}
            </p>
            <p class="cart-item-price">$${itemTotal.toLocaleString()}</p>
          </div>
          <div class="cart-item-actions">
            <div class="cart-item-qty">
              <button onclick="updateCartQuantity('${esc(item.id)}', '${esc(item.size || '')}', ${item.quantity - 1})">−</button>
              <span>${item.quantity}</span>
              <button onclick="updateCartQuantity('${esc(item.id)}', '${esc(item.size || '')}', ${item.quantity + 1})">+</button>
            </div>
            <button class="remove-item" onclick="removeFromCart('${esc(item.id)}', '${esc(item.size || '')}')">Remove</button>
          </div>
        </div>
      `,
      total: itemTotal
    };
  };

  // Helper function to render cart HTML split by product type
  const renderCartHTML = (detailsSource) => {
      let subtotal = 0;

      // Sync available_type from product API data before filtering
      cart.forEach(item => {
        if (!item.available_type || item.available_type === 'physical') {
          const apiProduct = detailsSource[item.id] || detailsSource[String(item.id)];
          if (apiProduct && apiProduct.available_type) {
            // Use product's available_type (but 'both' defaults to what cart stored)
            if (apiProduct.available_type === 'digital') {
              item.available_type = 'digital';
            } else if (apiProduct.available_type === 'both' && !item.available_type) {
              item.available_type = 'physical'; // default for 'both'
            }
          }
        }
      });

      const digitalItems = cart.filter(item => item.available_type === 'digital');
      const physicalItems = cart.filter(item => item.available_type !== 'digital');

      // Render digital items
      if (digitalItems.length > 0) {
        const digitalResults = digitalItems.map(item => renderItemHTML(item, detailsSource));
        digitalContainer.innerHTML = digitalResults.map(r => r.html).join('');
        subtotal += digitalResults.reduce((sum, r) => sum + r.total, 0);
        if (digitalSection) digitalSection.style.display = 'block';
      } else {
        if (digitalContainer) digitalContainer.innerHTML = '';
        if (digitalSection) digitalSection.style.display = 'none';
      }

      // Render physical items
      if (physicalItems.length > 0) {
        const physicalResults = physicalItems.map(item => renderItemHTML(item, detailsSource));
        physicalContainer.innerHTML = physicalResults.map(r => r.html).join('');
        subtotal += physicalResults.reduce((sum, r) => sum + r.total, 0);
        if (physicalSection) physicalSection.style.display = 'block';
      } else {
        if (physicalContainer) physicalContainer.innerHTML = '';
        if (physicalSection) physicalSection.style.display = 'none';
      }

      // Update totals — shipping is $0 if only digital items
      const hasPhysicalItems = physicalItems.length > 0;
      const shipping = (subtotal > 0 && hasPhysicalItems) ? 50 : 0;
      const tax = Math.round(subtotal * 0.18);
      const total = subtotal + shipping + tax;

      if(document.getElementById('cart-subtotal')) {
          document.getElementById('cart-subtotal').textContent = `$${subtotal.toLocaleString()}`;
          document.getElementById('cart-shipping').textContent = shipping > 0 ? `$${shipping}` : 'Free';
          document.getElementById('cart-tax').textContent = `$${tax.toLocaleString()}`;
          document.getElementById('cart-total').textContent = `$${total.toLocaleString()}`;
      }

      // Show/Hide checkout button based on signin
      const userSession = getUserSession();
      if (checkoutBtn) {
        if (userSession) {
          checkoutBtn.style.display = 'block';
          checkoutBtn.href = 'checkout.php';
        } else {
          checkoutBtn.style.display = 'block';
          checkoutBtn.href = 'signin.php?redirect=checkout.php';
          checkoutBtn.textContent = 'Sign in to Checkout';
          checkoutBtn.classList.add('checkout-signin-prompt');
        }
      }
  };

  // STRATEGY:
  // 1. If we have cached data, RENDER IMMEDIATELY. This makes +/- instant.
  // 2. If we lack data, show loading.
  // 3. Always fetch fresh data in background to ensure price accuracy.

  const hasDetails = cart.length > 0 && cart[0].name && cart[0].price && cart[0].image;
  const hasCache = Object.keys(globalProductDetailsCache).length > 0;

  if (hasDetails) {
      const source = {};
      cart.forEach(item => source[item.id] = item);
      renderCartHTML(source);
  } else if (hasCache) {
      renderCartHTML(globalProductDetailsCache);
  } else {
      if (physicalContainer) physicalContainer.innerHTML = '<p style="text-align:center; padding:20px;">Updating cart details...</p>';
      if (physicalSection) physicalSection.style.display = 'block';
  }

  // Fetch up-to-date product details (Background update)
  const uniqueIds = [...new Set(cart.map(item => parseInt(item.id)))];

  try {
      const response = await fetch('api/product/get_details.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ids: uniqueIds })
      });
      const result = await response.json();

      if (result.status === 'success') {
          globalProductDetailsCache = { ...globalProductDetailsCache, ...result.data };
          renderCartHTML(globalProductDetailsCache);
      }
  } catch (e) {
      console.error("Error fetching cart details", e);
      if (!hasCache) {
           renderCartHTML({});
      }
  }
}

// Load checkout page
function loadCheckoutPage() {
  const checkoutItemsContainer = document.getElementById('checkout-items');
  
  if (!checkoutItemsContainer) return;
  
  if (cart.length === 0) {
    // If not logged in and empty local storage, or logged in and empty DB cart
    // But wait, if we are fetching API, cart might be empty momentarily.
    // So let's skip redirect if we are potentially waiting for data?
    // Actually, handling empty cart redirect should be done carefully.
    const userSession = getUserSession();
    if (!userSession && cart.length === 0) {
        window.location.href = 'cart.php';
        return;
    } 
    // If logged in, let's show empty state or redirect after a short delay if truly empty
    if (userSession && cart.length === 0) {
         checkoutItemsContainer.innerHTML = '<p>Loading your cart...</p>';
         // If genuinely empty after fetch, the fetch callback will not re-trigger this if empty?
         // Actually fetchCart updates cart. If cart becomes [] then we redirect.
         // Let's just return for now.
         return;
    }
  }
  
  checkoutItemsContainer.innerHTML = cart.map(item => `
    <div class="checkout-item">
      <img src="${esc(item.image)}" alt="${esc(item.name)}" class="checkout-item-image" onerror="this.src='img/sticker.webp'" />
      <div class="checkout-item-info">
        <div class="checkout-item-name">${esc(item.name)}</div>
        <div class="checkout-item-details">
          ${item.available_type ? `<span style="text-transform:capitalize">${esc(item.available_type)}</span> • ` : ''}
          ${item.size ? `Size: ${esc(item.size)} • ` : ''}Qty: ${item.quantity}
        </div>
      </div>
      <div class="checkout-item-price">$${(item.price * item.quantity).toLocaleString()}</div>
    </div>
  `).join('');

  // Determine cart composition
  const hasDigital = cart.some(item => item.available_type === 'digital');
  const hasPhysical = cart.some(item => item.available_type === 'physical' || !item.available_type);
  const onlyDigital = hasDigital && !hasPhysical;

  // Update totals
  const subtotal = getCartTotal();
  const shipping = (subtotal > 0 && hasPhysical) ? 50 : 0;
  const tax = Math.round(subtotal * 0.18);
  const total = subtotal + shipping + tax;

  document.getElementById('checkout-subtotal').textContent = `$${subtotal.toLocaleString()}`;
  document.getElementById('checkout-shipping').textContent = shipping > 0 ? `$${shipping}` : 'Free';
  document.getElementById('checkout-tax').textContent = `$${tax.toLocaleString()}`;
  document.getElementById('checkout-total').textContent = `$${total.toLocaleString()}`;

  // Show/hide shipping address fields based on cart composition
  const shippingFields = document.getElementById('shipping-fields');
  const digitalDeliveryInfo = document.getElementById('digital-delivery-info');
  const blockTitle = document.querySelector('.checkout-block .block-title');

  if (onlyDigital) {
    // Hide shipping address, show digital delivery message
    if (shippingFields) shippingFields.classList.add('hidden');
    if (digitalDeliveryInfo) digitalDeliveryInfo.style.display = 'flex';
    if (blockTitle) blockTitle.textContent = 'Contact Information';
  } else {
    // Show shipping address
    if (shippingFields) shippingFields.classList.remove('hidden');
    if (digitalDeliveryInfo) digitalDeliveryInfo.style.display = hasDigital ? 'flex' : 'none';
    if (blockTitle) blockTitle.textContent = 'Shipping Information';
  }

  // Handle COD availability
  const codOption = document.getElementById('cod-option');
  const codRadio = document.getElementById('cod-radio');
  const codMessage = document.getElementById('cod-disabled-message');

  if (codRadio) codRadio.disabled = false;
  if (codMessage) codMessage.style.display = 'none';
  if (codOption) {
      codOption.style.opacity = '1';
      codOption.style.cursor = 'pointer';
      codOption.style.pointerEvents = 'auto';
  }
}

// Toast notification with improved accessibility
function showToast(message, type = 'success') {
  // Remove existing toasts to prevent stacking
  const existingToasts = document.querySelectorAll('.toast');
  existingToasts.forEach(t => t.remove());
  
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  toast.setAttribute('role', 'alert');
  toast.setAttribute('aria-live', 'polite');
  toast.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${type === 'success' ? '#4caf50' : '#ff4444'};
    color: white;
    padding: 16px 24px;
    border-radius: 12px;
    z-index: 10000;
    animation: slideIn 0.3s ease;
    max-width: 90vw;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  `;
  
  document.body.appendChild(toast);
  
  // Focus management for screen readers
  toast.focus();
  
  setTimeout(() => {
    toast.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 3000);
}

// Initialize cart on page load
document.addEventListener('DOMContentLoaded', function() {
  updateCartCount();
  updateUserMenu();
  
  // Initialize user dropdown
  document.querySelectorAll('.nav-user').forEach(menu => {
    menu.addEventListener('click', toggleUserDropdown);
  });
  
  if (window.location.pathname.includes('cart.php')) {
    loadCartPage();
  }
  
  if (window.location.pathname.includes('checkout.php')) {
    // Check if user is signed in
    const userSession = getUserSession();
    if (!userSession || !userSession.id) {
      if (userSession) clearUserSession(); // Clear invalid session
      showToast('Please sign in with a valid account to proceed to checkout', 'error');
      setTimeout(() => {
        window.location.href = 'signin.php?redirect=checkout.php';
      }, 1500);
      return;
    }
    
    // Check if cart is empty
    if (cart.length === 0) {
      showToast('Your cart is empty!', 'error');
      setTimeout(() => {
        window.location.href = 'cart.php';
      }, 1500);
      return;
    }
    
    loadCheckoutPage();

    // Initialize address selection
    initCheckoutAddresses();

    // Handle payment method change
    document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
      radio.addEventListener('change', function() {
        const cardDetails = document.getElementById('card-details');
        // Show Razorpay info for both card and UPI (both use Razorpay gateway)
        if (this.value === 'card' || this.value === 'upi') {
          cardDetails.style.display = 'block';
        } else {
          cardDetails.style.display = 'none';
        }
      });
    });
  }
  
  // Load order confirmation page
  if (window.location.pathname.includes('order-confirmation.php')) {
    loadOrderConfirmationPage();
  }
});

// ==================== FORM HANDLERS ====================

// User Session Management
function getUserSession() {
  return JSON.parse(localStorage.getItem('userSession')) || null;
}

function setUserSession(userData) {
  localStorage.setItem('userSession', JSON.stringify(userData));
  updateUserMenu();
}

function clearUserSession() {
  localStorage.removeItem('userSession');
  updateUserMenu();
}

function getUserFirstName(user) {
  if (!user) return 'Profile';
  const firstName = String(user.firstName || '').trim();
  if (firstName) return firstName;
  const name = String(user.name || '').trim();
  if (name) return name.split(/\s+/)[0];
  const email = String(user.email || '').trim();
  return email ? email.split('@')[0] : 'Profile';
}

function updateUserMenu() {
  const userSession = getUserSession();
  // Support both old (.nav-user) and new (.user-menu.profile-menu) header structures
  const userMenus = document.querySelectorAll('.nav-user, .user-menu.profile-menu');
  const signInButtons = document.querySelectorAll('.nav-cta[href="signin.php"], .header-signin-cta');
  
  if (userSession) {
    // Client-side session found - show user menu, hide sign in button
    userMenus.forEach(menu => {
      menu.style.display = 'flex';
      const userName = menu.querySelector('.user-name');
      const userAvatar = menu.querySelector('.user-avatar');
      const displayName = getUserFirstName(userSession);
      
      if (userName) {
        userName.textContent = displayName;
      }
      
      if (userAvatar) {
        userAvatar.innerHTML = `
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        `;
      }
    });
    
    signInButtons.forEach(btn => {
      btn.style.display = 'none';
    });
  } else {
    // No client-side session. Check if server rendered the user menu (PHP session is active).
    // If profile menu has no display:none style and Sign In button HAS display:none, 
    // it means server rendered auth.
    let serverRenderedAuth = false;
    
    // Check if any sign-in button is hidden
    const signInHidden = Array.from(signInButtons).some(btn => {
      if (btn.style.display === 'none') return true;
      const computed = window.getComputedStyle(btn);
      return computed && computed.display === 'none';
    });
    
    // Server auth is detected if: user menu exists AND (no sign-in buttons exist OR they're hidden)
    if (userMenus.length > 0 && signInHidden) {
        serverRenderedAuth = true;
    }
    
    if (serverRenderedAuth) {
        // Server auth detected - keep showing user menu (already visible from PHP),
        // ensure sign-in buttons are hidden, and sync name to localStorage if present
        userMenus.forEach(menu => {
            menu.style.display = 'flex';
            
            const userNameEl = menu.querySelector('.user-name');
            const userName = userNameEl ? userNameEl.textContent.trim() : 'User';
            
            // Attempt to sync to localStorage if we have a name
            if (userName && userName !== 'User' && userName !== 'Profile') {
                 const derivedSession = {
                     name: userName,
                     firstName: userName.split(' ')[0], 
                     loginTime: new Date().toISOString(),
                     source: 'server-hydrated'
                 };
                 localStorage.setItem('userSession', JSON.stringify(derivedSession));
            }
        });
        
        // Ensure sign-in buttons are hidden
        signInButtons.forEach(btn => {
          btn.style.display = 'none';
        });
    } else {
        // Standard case: No auth detected - hide user menus, show sign-in button
        userMenus.forEach(menu => {
          menu.style.display = 'none';
        });
        
        signInButtons.forEach(btn => {
          btn.style.display = 'inline-flex';
        });
    }
  }
}

// Simple user dropdown toggle (profile menu only)
document.addEventListener('DOMContentLoaded', function () {
  const toggles = document.querySelectorAll('.profile-menu-toggle');
  if (!toggles.length) return;

  toggles.forEach((toggle) => {
    toggle.addEventListener('click', function (event) {
      event.stopPropagation();
      const menu = toggle.closest('.user-menu.profile-menu');
      if (!menu) return;

      const isOpen = !menu.classList.contains('is-open');

      // Close any other open menus
      document.querySelectorAll('.user-menu.profile-menu.is-open').forEach((openMenu) => {
        if (openMenu !== menu) {
          openMenu.classList.remove('is-open');
          const btn = openMenu.querySelector('.profile-menu-toggle');
          if (btn) btn.setAttribute('aria-expanded', 'false');
        }
      });

      menu.classList.toggle('is-open', isOpen);
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  });

  // Close menu on outside click
  document.addEventListener('click', function () {
    document.querySelectorAll('.user-menu.profile-menu.is-open').forEach((menu) => {
      menu.classList.remove('is-open');
      const btn = menu.querySelector('.profile-menu-toggle');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    });
  });
});

// ==================== VALIDATION FUNCTIONS ====================

// Email validation
function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// Phone validation (supports international formats)
function validatePhone(phone) {
  const phoneRegex = /^[\d\s\-\+\(\)]{10,}$/;
  return phoneRegex.test(phone.replace(/\s/g, ''));
}

// Password validation
function validatePassword(password) {
  // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
  const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
  return passwordRegex.test(password);
}

// Show field error (supports both old and new format)
function showFieldError(field, message) {
  if (!field) return;
  
  // Try to find error span (supports both .field-error and .field-error-modern)
  const errorSpan = field.parentElement?.querySelector('.field-error-modern') || 
                   field.parentElement?.querySelector('.field-error') ||
                   field.closest('.form-field-modern')?.querySelector('.field-error-modern') ||
                   field.closest('.form-field')?.querySelector('.field-error');
  
  if (errorSpan) {
    errorSpan.textContent = message;
    errorSpan.style.display = 'block';
  }
  
  // Update field border
  field.style.borderColor = '#ef4444';
}

// Clear field error (supports both old and new format)
function clearFieldError(field) {
  if (!field) return;
  
  // Try to find error span (supports both .field-error and .field-error-modern)
  const errorSpan = field.parentElement?.querySelector('.field-error-modern') || 
                   field.parentElement?.querySelector('.field-error') ||
                   field.closest('.form-field-modern')?.querySelector('.field-error-modern') ||
                   field.closest('.form-field')?.querySelector('.field-error');
  
  if (errorSpan) {
    errorSpan.textContent = '';
    errorSpan.style.display = 'none';
  }
  
  // Reset field border
  field.style.borderColor = '';
}

// Real-time validation
function setupRealTimeValidation() {
  // Email fields
  document.querySelectorAll('input[type="email"]').forEach(field => {
    field.addEventListener('blur', function() {
      if (this.value && !validateEmail(this.value)) {
        showFieldError(this, 'Please enter a valid email address');
      } else {
        clearFieldError(this);
      }
    });
    
    field.addEventListener('input', function() {
      if (this.value && validateEmail(this.value)) {
        clearFieldError(this);
      }
    });
  });
  
  // Phone fields
  document.querySelectorAll('input[type="tel"]').forEach(field => {
    field.addEventListener('blur', function() {
      if (this.value && !validatePhone(this.value)) {
        showFieldError(this, 'Please enter a valid phone number');
      } else {
        clearFieldError(this);
      }
    });
    
    field.addEventListener('input', function() {
      if (this.value && validatePhone(this.value)) {
        clearFieldError(this);
      }
    });
  });
  
  // Password fields
  document.querySelectorAll('input[type="password"]').forEach(field => {
    if (field.name === 'password' || field.name === 'newPassword') {
      field.addEventListener('blur', function() {
        if (this.value && this.value.length < 8) {
          showFieldError(this, 'Password must be at least 8 characters');
        } else if (this.value && !validatePassword(this.value)) {
          showFieldError(this, 'Password must contain uppercase, lowercase, and number');
        } else {
          clearFieldError(this);
        }
      });
    }
    
    if (field.name === 'confirmPassword' || field.name === 'confirmNewPassword') {
      field.addEventListener('blur', function() {
        const passwordField = this.form.querySelector('input[name="password"], input[name="newPassword"]');
        if (passwordField && this.value !== passwordField.value) {
          showFieldError(this, 'Passwords do not match');
        } else {
          clearFieldError(this);
        }
      });
    }
  });
  
  // Card number formatting
  document.querySelectorAll('input[name="cardNumber"]').forEach(field => {
    field.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\s/g, '');
      let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
      if (formattedValue.length <= 19) {
        e.target.value = formattedValue;
      }
    });
  });
  
  // Expiry date formatting
  document.querySelectorAll('input[name="expiry"]').forEach(field => {
    field.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
      }
      e.target.value = value;
    });
  });
  
  // CVV validation
  document.querySelectorAll('input[name="cvv"]').forEach(field => {
    field.addEventListener('input', function(e) {
      e.target.value = e.target.value.replace(/\D/g, '');
    });
  });
  
  // ZIP code validation
  document.querySelectorAll('input[name="zip"]').forEach(field => {
    field.addEventListener('input', function(e) {
      e.target.value = e.target.value.replace(/\D/g, '');
    });
  });
}

// Initialize real-time validation on page load
document.addEventListener('DOMContentLoaded', function() {
  setupRealTimeValidation();
  updateUserMenu(); // Sync user menu from server-rendered session
});

// Sign in handler with enhanced validation
function handleSignIn(event) {
  event.preventDefault();
  
  const form = event.target;
  const email = form.email.value.trim();
  const password = form.password.value;
  const btn = document.getElementById('signin-btn');
  const btnText = document.getElementById('signin-text');
  const btnLoader = document.getElementById('signin-loader');
  const errorDiv = document.getElementById('auth-error');
  const successDiv = document.getElementById('auth-success');
  
  // Clear previous errors
  errorDiv.style.display = 'none';
  successDiv.style.display = 'none';
  
  // Validation
  let isValid = true;
  
  if (!email) {
    showFieldError(form.email, 'Email is required');
    isValid = false;
  } else if (!validateEmail(email)) {
    showFieldError(form.email, 'Please enter a valid email address');
    isValid = false;
  } else {
    clearFieldError(form.email);
  }
  
  if (!password) {
    showFieldError(form.password, 'Password is required');
    isValid = false;
  } else if (password.length < 6) {
    showFieldError(form.password, 'Password must be at least 6 characters');
    isValid = false;
  } else {
    clearFieldError(form.password);
  }
  
  if (!isValid) {
    return;
  }
  
  // Show loading
  btn.disabled = true;
  btnText.style.display = 'none';
  btnLoader.style.display = 'inline';
  
  // Call API
  const csrfToken = getCsrfToken();
  fetch('api/auth/login.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      email: email,
      password: password,
      csrf_token: csrfToken
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      const user = data.data.user;
      const tokens = data.data.tokens;
      
      const userData = {
        id: user.id,
        email: user.email,
        firstName: user.firstName,
        lastName: user.lastName,
        name: `${user.firstName} ${user.lastName}`.trim(),
        role: user.role,
        loginTime: new Date().toISOString()
      };
      
      setUserSession(userData);
      localStorage.setItem('accessToken', tokens.access_token);
      localStorage.setItem('refreshToken', tokens.refresh_token);
      
      // Merge local cart if exists
      const localCart = JSON.parse(localStorage.getItem('cart')) || [];
      if (localCart.length > 0) {
          fetch('api/cart/merge.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
              body: JSON.stringify({ cart: localCart })
          })
          .then(res => res.json())
          .then(data => {
              // Clear local cart after merge
              localStorage.removeItem('cart');
              cart = [];
              
              successDiv.textContent = 'Sign in successful! Redirecting...';
              successDiv.style.display = 'block';
              handleSignInRedirect();
          })
          .catch(err => {
              console.error('Merge error:', err);
              // Proceed anyway
              successDiv.textContent = 'Sign in successful! Redirecting...';
              successDiv.style.display = 'block';
              handleSignInRedirect();
          });
      } else {
          successDiv.textContent = 'Sign in successful! Redirecting...';
          successDiv.style.display = 'block';
          handleSignInRedirect();
      }
    } else {
      errorDiv.textContent = data.message || 'Invalid email or password';
      errorDiv.style.display = 'block';
      btn.disabled = false;
      btnText.style.display = 'inline';
      btnLoader.style.display = 'none';
    }
  })
  .catch(error => {
    console.error('Login error:', error);
    errorDiv.textContent = 'An error occurred. Please try again.';
    errorDiv.style.display = 'block';
    btn.disabled = false;
    btnText.style.display = 'inline';
    btnLoader.style.display = 'none';
  });
}

// Sign out handler
function handleSignOut() {
  // Call server logout API to destroy PHP session
  fetch('api/auth/logout.php', { credentials: 'same-origin' })
    .then(response => {
        // Regardless of server response, clear client state
        clearUserSession();
        showToast('Signed out successfully', 'success');
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 1000);
    })
    .catch(err => {
        console.error('Logout error:', err);
        // Fallback
        clearUserSession();
        window.location.href = 'index.php';
    });
}

// Contact form handler
async function handleContactSubmit(event) {
  event.preventDefault();

  const form = event.target;
  const submitBtn = form.querySelector('[type="submit"]');
  const formData = {
    name:    form.name.value.trim(),
    email:   form.email.value.trim(),
    phone:   form.phone?.value.trim() || '',
    subject: form.subject?.value.trim() || '',
    message: form.message.value.trim()
  };

  if (!formData.name || !formData.email || !formData.message) {
    showToast('Please fill in all required fields.', 'error');
    return;
  }

  if (submitBtn) submitBtn.disabled = true;

  try {
    const res = await fetch('api/contact/send.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      body: JSON.stringify(formData)
    });
    const data = await res.json();
    if (data.status === 'success') {
      showToast('Thank you! We will get back to you shortly.', 'success');
      form.reset();
    } else {
      showToast(data.message || 'Failed to send message. Please try again.', 'error');
    }
  } catch (err) {
    showToast('Network error. Please try again later.', 'error');
  }

  if (submitBtn) submitBtn.disabled = false;
}

// Checkout handler with enhanced validation
function handleCheckout(event) {
  event.preventDefault();
  
  // Check if user is signed in and has a valid ID
  const userSession = getUserSession();
  if (!userSession || !userSession.id) {
    if (userSession) clearUserSession(); // Clear invalid session
    showToast('Your session is invalid. Please sign in again.', 'error');
    setTimeout(() => {
      window.location.href = 'signin.php?redirect=checkout.php';
    }, 1500);
    return;
  }
  
  if (cart.length === 0) {
    showToast('Your cart is empty!', 'error');
    return;
  }
  
  const form = event.target;
  const btn = document.getElementById('place-order-btn');
  const btnText = document.getElementById('order-text');
  const btnLoader = document.getElementById('order-loader');

  // Determine cart composition for conditional validation
  const hasPhysicalInCart = cart.some(item => item.available_type === 'physical' || !item.available_type);
  const onlyDigitalInCart = !hasPhysicalInCart;

  // Validation
  let isValid = true;

  // Shipping information validation
  const firstName = form.firstName?.value.trim();
  const lastName = form.lastName?.value.trim();
  const email = form.email?.value.trim();
  const phone = form.phone?.value.trim();
  const address = form.address?.value.trim();
  const city = form.city?.value.trim();
  const state = form.state?.value.trim();
  const zip = form.zip?.value.trim();
  const country = form.country?.value;
  const paymentMethod = form.paymentMethod?.value;

  // Check if user is using a saved address
  const usingSavedAddressForValidation = isUsingSavedAddress();

  // When using saved address, only validate email
  if (usingSavedAddressForValidation) {
    const savedEmail = document.getElementById('email-saved')?.value.trim();
    if (!savedEmail) {
      showFieldError(document.getElementById('email-saved'), 'Email is required');
      isValid = false;
    } else if (!validateEmail(savedEmail)) {
      showFieldError(document.getElementById('email-saved'), 'Please enter a valid email address');
      isValid = false;
    } else {
      clearFieldError(document.getElementById('email-saved'));
    }
  } else {
    // Manual address entry - validate all fields
    if (!firstName || firstName.length < 2) {
      if (form.firstName) showFieldError(form.firstName, 'First name must be at least 2 characters');
      isValid = false;
    } else if (form.firstName) clearFieldError(form.firstName);

    if (!lastName || lastName.length < 2) {
      if (form.lastName) showFieldError(form.lastName, 'Last name must be at least 2 characters');
      isValid = false;
    } else if (form.lastName) clearFieldError(form.lastName);

    if (!email) {
      if (form.email) showFieldError(form.email, 'Email is required');
      isValid = false;
    } else if (!validateEmail(email)) {
      if (form.email) showFieldError(form.email, 'Please enter a valid email address');
      isValid = false;
    } else if (form.email) clearFieldError(form.email);

    if (!phone) {
      if (form.phone) showFieldError(form.phone, 'Phone number is required');
      isValid = false;
    } else if (!validatePhone(phone)) {
      if (form.phone) showFieldError(form.phone, 'Please enter a valid phone number');
      isValid = false;
    } else if (form.phone) clearFieldError(form.phone);

    // Address validation — only required when cart has physical items
    if (!onlyDigitalInCart) {
      if (!address || address.length < 5) {
        if (form.address) showFieldError(form.address, 'Please enter a valid address');
        isValid = false;
      } else if (form.address) clearFieldError(form.address);

      if (!city || city.length < 2) {
        if (form.city) showFieldError(form.city, 'City is required');
        isValid = false;
      } else if (form.city) clearFieldError(form.city);

      if (!state || state.length < 2) {
        if (form.state) showFieldError(form.state, 'State is required');
        isValid = false;
      } else if (form.state) clearFieldError(form.state);

      if (!zip || zip.length < 4) {
        if (form.zip) showFieldError(form.zip, 'Please enter a valid ZIP code');
        isValid = false;
      } else if (form.zip) clearFieldError(form.zip);
    }
  }
  
  // Card/UPI payment is handled by Razorpay modal — no client-side card field validation needed
  if (!isValid) {
    showToast('Please fill in all required fields correctly', 'error');
    return;
  }
  
  // Show loading
  btn.disabled = true;
  btnText.style.display = 'none';
  btnLoader.style.display = 'inline';
  
  // Calculate totals
  const subtotal = getCartTotal();
  const shippingCost = (subtotal > 0 && !onlyDigitalInCart) ? 50 : 0;
  const tax = Math.round(subtotal * 0.18);
  const total = subtotal + shippingCost + tax;

  // Get User ID
  const userId = userSession ? userSession.id : 0;

  // Check if using saved address
  const usingSavedAddress = isUsingSavedAddress();
  const savedAddressId = usingSavedAddress ? getSelectedAddressId() : null;

  // Get email - either from saved email field or main email field
  const emailField = usingSavedAddress ? document.getElementById('email-saved') : form.email;
  const orderEmail = emailField?.value || form.email?.value || '';

  // Prepare Order Data
  const orderPayload = {
    userId: userId,
    items: cart.map(item => ({
      id: item.id,
      name: item.name,
      price: item.price,
      image: item.image,
      size: item.size,
      quantity: item.quantity,
      available_type: item.available_type || 'physical'
    })),
    total: total,
    subtotal: subtotal,
    shipping_cost: shippingCost,
    tax: tax,
    paymentMethod: form.paymentMethod?.value || 'card',
    savedAddressId: savedAddressId,
    saveAddress: document.getElementById('save-address-checkbox')?.checked || false,
    shipping: usingSavedAddress ? {
      // When using saved address, only send email (address fetched from DB)
      email: orderEmail
    } : {
      firstName: form.firstName?.value || '',
      lastName: form.lastName?.value || '',
      email: orderEmail,
      phone: form.phone?.value || '',
      address: onlyDigitalInCart ? '' : (form.address?.value || ''),
      city: onlyDigitalInCart ? '' : (form.city?.value || ''),
      state: onlyDigitalInCart ? '' : (form.state?.value || ''),
      zip: onlyDigitalInCart ? '' : (form.zip?.value || ''),
      country: onlyDigitalInCart ? '' : (form.country?.value || 'IN')
    }
  };
  
  const csrfToken = getCsrfToken();
  const orderHeaders = { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken };

  function resetOrderBtn() {
    btn.disabled = false;
    btnText.style.display = 'inline';
    btnLoader.style.display = 'none';
  }

  function handleOrderSuccess(orderData, serverData, verifyData) {
    showToast('Order placed successfully!', 'success');
    cart = [];
    saveCart();
    updateCartCount();
    const apiStatus =
      (verifyData && verifyData.data && verifyData.data.status) ||
      (serverData.data && serverData.data.status) ||
      'pending';
    const confirmationData = {
      ...orderData,
      orderNumber: serverData.data.orderNumber,
      orderId:     serverData.data.orderId,
      date:        new Date().toISOString(),
      status:      apiStatus,
      total:        serverData.data.total        ?? orderData.total,
      subtotal:     serverData.data.subtotal     ?? orderData.subtotal,
      tax:          serverData.data.tax          ?? orderData.tax,
      shipping_cost: serverData.data.shipping_cost ?? orderData.shipping_cost,
    };
    localStorage.setItem('lastOrder', JSON.stringify(confirmationData));
    setTimeout(() => { window.location.href = 'order-confirmation.php'; }, 1000);
  }

  function createDraftOrder() {
    return fetch('api/order/create.php', {
      method: 'POST',
      headers: orderHeaders,
      body: JSON.stringify(orderPayload)
    }).then(res => {
      if (res.status === 401) {
        clearUserSession();
        showToast('Session expired. Please sign in again.', 'error');
        setTimeout(() => { window.location.href = 'signin.php?redirect=checkout.php'; }, 1500);
        throw new Error('Session expired');
      }
      return res.json();
    }).then(data => {
      if (data.status !== 'success') throw new Error(data.message || 'Order creation failed');
      return data;
    });
  }

  // ── COD flow ──────────────────────────────────────────────────────────────
  if (paymentMethod === 'cod') {
    createDraftOrder()
      .then(data => handleOrderSuccess(orderPayload, data))
      .catch(err => {
        console.error('Order error:', err);
        showToast('Failed to place order: ' + err.message, 'error');
        resetOrderBtn();
      });
    return;
  }

  // Manual order simulation: payment gateways are intentionally not required.
  createDraftOrder()
    .then(data => handleOrderSuccess(orderPayload, data))
    .catch(err => {
      console.error(err);
      showToast(err.message || 'Failed to place order', 'error');
      resetOrderBtn();
    });
  return;

}

// Load order confirmation page with order details
function loadOrderConfirmationPage() {
  const orderData = JSON.parse(localStorage.getItem('lastOrder'));
  
  if (!orderData) {
    // If no order data, redirect to shop
    showToast('No order found. Redirecting to shop...', 'error');
    setTimeout(() => {
      window.location.href = 'shopAll.php';
    }, 2000);
    return;
  }
  
  // Set order number
  const orderNumberEl = document.getElementById('order-number');
  if (orderNumberEl) {
    orderNumberEl.textContent = orderData.orderNumber || '#UXP-2024-001234';
  }
  
  // Set order date
  const orderDateEl = document.getElementById('order-date');
  if (orderDateEl) {
    const orderDate = orderData.date ? new Date(orderData.date) : new Date();
    orderDateEl.textContent = orderDate.toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    });
  }
  
  // Set order total
  const orderTotalEl = document.getElementById('order-total');
  if (orderTotalEl) {
    orderTotalEl.textContent = `$${orderData.total || 0}`;
  }
  
  // Set payment method
  const paymentMethodEl = document.getElementById('payment-method');
  if (paymentMethodEl) {
    const paymentMethods = {
      'card': 'Credit/Debit Card',
      'upi': 'UPI',
      'cod': 'Cash on Delivery'
    };
    paymentMethodEl.textContent = paymentMethods[orderData.paymentMethod] || 'Credit Card';
  }
  
  // Load order items from orderData.items (not cart, since cart is cleared)
  const itemsList = document.getElementById('confirmation-items-list');
  if (itemsList && orderData.items && orderData.items.length > 0) {
    itemsList.innerHTML = orderData.items.map(item => `
      <div class="confirmation-item">
        <img src="${esc(item.image)}" alt="${esc(item.name)}" class="item-image" />
        <div class="item-info">
          <h4>${esc(item.name)}</h4>
          <p>${item.size ? `Size: ${esc(item.size)} • ` : ''}Quantity: ${item.quantity}</p>
        </div>
        <div class="item-price">$${item.price * item.quantity}</div>
      </div>
    `).join('');
  } else if (itemsList) {
    itemsList.innerHTML = '<p class="empty-message">No items found in this order.</p>';
  }
  
  // Load shipping address
  const shippingDiv = document.getElementById('shipping-address');
  if (shippingDiv && orderData.shipping) {
    shippingDiv.innerHTML = `
      <p><strong>${esc(orderData.shipping.firstName || '')} ${esc(orderData.shipping.lastName || '')}</strong></p>
      <p>${esc(orderData.shipping.address || '')}</p>
      <p>${esc(orderData.shipping.city || '')}, ${esc(orderData.shipping.state || '')} ${esc(orderData.shipping.zip || '')}</p>
      <p>${esc(orderData.shipping.country || 'India')}</p>
      ${orderData.shipping.phone ? `<p>Phone: ${esc(orderData.shipping.phone)}</p>` : ''}
      ${orderData.shipping.email ? `<p>Email: ${esc(orderData.shipping.email)}</p>` : ''}
    `;
  } else if (shippingDiv) {
    shippingDiv.innerHTML = '<p class="empty-message">Shipping address not available.</p>';
  }
}

// Sign up handler with enhanced validation
function handleSignUp(event) {
  event.preventDefault();
  
  const form = event.target;
  
  // Support both old format (firstName/lastName) and new format (fullName)
  let firstName, lastName;
  if (form.fullName) {
    // New format: fullName
    const fullName = form.fullName.value.trim();
    if (!fullName || fullName.length < 2) {
      const errorSpan = form.fullName.parentElement.querySelector('.field-error-modern') || 
                       form.fullName.parentElement.querySelector('.field-error');
      if (errorSpan) {
        errorSpan.textContent = 'Full name must be at least 2 characters';
        errorSpan.style.display = 'block';
      }
      form.fullName.style.borderColor = '#ef4444';
      return;
    }
    const names = fullName.split(' ');
    firstName = names[0] || '';
    lastName = names.slice(1).join(' ') || '';
  } else {
    // Old format: firstName/lastName
    firstName = form.firstName?.value.trim() || '';
    lastName = form.lastName?.value.trim() || '';
  }
  
  const email = form.email.value.trim();
  const phone = form.phone?.value.trim() || ''; // Phone is optional in new design
  const password = form.password.value;
  const confirmPassword = form.confirmPassword.value;
  const terms = form.terms.checked;
  
  const btn = document.getElementById('signup-btn');
  const btnText = document.getElementById('signup-text');
  const btnLoader = document.getElementById('signup-loader');
  const errorDiv = document.getElementById('auth-error');
  const successDiv = document.getElementById('auth-success');
  
  // Clear previous errors
  if (errorDiv) errorDiv.style.display = 'none';
  if (successDiv) successDiv.style.display = 'none';
  
  // Validation
  let isValid = true;
  
  // Validate name (either fullName or firstName/lastName)
  if (form.fullName) {
    const fullName = form.fullName.value.trim();
    if (!fullName || fullName.length < 2) {
      const errorSpan = form.fullName.parentElement.querySelector('.field-error-modern') || 
                       form.fullName.parentElement.querySelector('.field-error');
      if (errorSpan) {
        errorSpan.textContent = 'Full name must be at least 2 characters';
        errorSpan.style.display = 'block';
      }
      form.fullName.style.borderColor = '#ef4444';
      isValid = false;
    } else {
      const errorSpan = form.fullName.parentElement.querySelector('.field-error-modern') || 
                       form.fullName.parentElement.querySelector('.field-error');
      if (errorSpan) errorSpan.style.display = 'none';
      form.fullName.style.borderColor = '';
    }
  } else {
    if (!firstName || firstName.length < 2) {
      if (form.firstName) showFieldError(form.firstName, 'First name must be at least 2 characters');
      isValid = false;
    } else {
      if (form.firstName) clearFieldError(form.firstName);
    }
    
    if (!lastName || lastName.length < 2) {
      if (form.lastName) showFieldError(form.lastName, 'Last name must be at least 2 characters');
      isValid = false;
    } else {
      if (form.lastName) clearFieldError(form.lastName);
    }
  }
  
  if (!email) {
    showFieldError(form.email, 'Email is required');
    isValid = false;
  } else if (!validateEmail(email)) {
    showFieldError(form.email, 'Please enter a valid email address');
    isValid = false;
  } else {
    clearFieldError(form.email);
  }
  
  // Phone validation (optional in new design)
  if (form.phone) {
    if (phone && !validatePhone(phone)) {
      showFieldError(form.phone, 'Please enter a valid phone number');
      isValid = false;
    } else if (form.phone) {
      clearFieldError(form.phone);
    }
  }
  
  if (!password) {
    const errorSpan = form.password.parentElement?.querySelector('.field-error-modern') || 
                     form.password.parentElement?.querySelector('.field-error');
    if (errorSpan) {
      errorSpan.textContent = 'Password is required';
      errorSpan.style.display = 'block';
    }
    form.password.style.borderColor = '#ef4444';
    isValid = false;
  } else if (password.length < 6) {
    const errorSpan = form.password.parentElement?.querySelector('.field-error-modern') || 
                     form.password.parentElement?.querySelector('.field-error');
    if (errorSpan) {
      errorSpan.textContent = 'Password must be at least 6 characters';
      errorSpan.style.display = 'block';
    }
    form.password.style.borderColor = '#ef4444';
    isValid = false;
  } else if (!validatePassword(password)) {
    const errorSpan = form.password.parentElement?.querySelector('.field-error-modern') || 
                     form.password.parentElement?.querySelector('.field-error');
    if (errorSpan) {
      errorSpan.textContent = 'Password must contain uppercase, lowercase, and number';
      errorSpan.style.display = 'block';
    }
    form.password.style.borderColor = '#ef4444';
    isValid = false;
  } else {
    const errorSpan = form.password.parentElement?.querySelector('.field-error-modern') || 
                     form.password.parentElement?.querySelector('.field-error');
    if (errorSpan) errorSpan.style.display = 'none';
    form.password.style.borderColor = '';
  }
  
  if (!confirmPassword) {
    const errorSpan = form.confirmPassword.parentElement?.querySelector('.field-error-modern') || 
                     form.confirmPassword.parentElement?.querySelector('.field-error');
    if (errorSpan) {
      errorSpan.textContent = 'Please confirm your password';
      errorSpan.style.display = 'block';
    }
    form.confirmPassword.style.borderColor = '#ef4444';
    isValid = false;
  } else if (password !== confirmPassword) {
    const errorSpan = form.confirmPassword.parentElement?.querySelector('.field-error-modern') || 
                     form.confirmPassword.parentElement?.querySelector('.field-error');
    if (errorSpan) {
      errorSpan.textContent = 'Passwords do not match';
      errorSpan.style.display = 'block';
    }
    form.confirmPassword.style.borderColor = '#ef4444';
    isValid = false;
  } else {
    const errorSpan = form.confirmPassword.parentElement?.querySelector('.field-error-modern') || 
                     form.confirmPassword.parentElement?.querySelector('.field-error');
    if (errorSpan) errorSpan.style.display = 'none';
    form.confirmPassword.style.borderColor = '';
  }
  
  if (!terms) {
    if (errorDiv) {
      errorDiv.textContent = 'Please agree to the Terms & Conditions';
      errorDiv.style.display = 'block';
    }
    isValid = false;
  }
  
  if (!isValid) {
    return;
  }
  
  // Show loading
  if (btn) {
    btn.disabled = true;
    if (btnText) btnText.style.display = 'none';
    if (btnLoader) btnLoader.style.display = 'inline';
  }
  
  // Call actual API
  fetch('api/auth/signup.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      firstName: firstName,
      lastName: lastName,
      fullName: form.fullName ? form.fullName.value.trim() : null,
      email: email,
      phone: phone,
      password: password,
      csrf_token: getCsrfToken()
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      if (successDiv) {
        successDiv.textContent = 'Account created successfully! Redirecting to sign in...';
        successDiv.style.display = 'block';
      }
      setTimeout(() => {
        window.location.href = 'signin.php?message=' + encodeURIComponent('Registration successful! Please sign in.');
      }, 1500);
    } else {
      if (errorDiv) {
        errorDiv.textContent = data.message || 'Registration failed';
        errorDiv.style.display = 'block';
      }
      if (btn) {
        btn.disabled = false;
        if (btnText) btnText.style.display = 'inline';
        if (btnLoader) btnLoader.style.display = 'none';
      }
    }
  })
  .catch(error => {
    console.error('Signup error:', error);
    if (errorDiv) {
      errorDiv.textContent = 'An error occurred. Please try again.';
      errorDiv.style.display = 'block';
    }
    if (btn) {
      btn.disabled = false;
      if (btnText) btnText.style.display = 'inline';
      if (btnLoader) btnLoader.style.display = 'none';
    }
  });
}

// Forgot password handler — sends real reset-link email
async function handleForgotPassword(event) {
  event.preventDefault();

  const form = event.target;
  const email = form.email.value.trim();
  const btn = document.getElementById('reset-btn');
  const btnText = document.getElementById('reset-text');
  const btnLoader = document.getElementById('reset-loader');
  const errorDiv = document.getElementById('auth-error');
  const successDiv = document.getElementById('auth-success');

  btn.disabled = true;
  btnText.style.display = 'none';
  btnLoader.style.display = 'inline';
  if (errorDiv) errorDiv.style.display = 'none';
  if (successDiv) successDiv.style.display = 'none';

  try {
    const csrfToken = getCsrfToken();
    const res = await fetch('api/auth/forgot-password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, csrf_token: csrfToken })
    });
    const data = await res.json();

    if (data.status === 'success') {
      if (successDiv) {
        successDiv.textContent = data.message;
        successDiv.style.display = 'block';
      }
      form.style.display = 'none';
    } else {
      if (errorDiv) {
        errorDiv.textContent = data.message || 'Something went wrong. Please try again.';
        errorDiv.style.display = 'block';
      }
    }
  } catch (err) {
    if (errorDiv) {
      errorDiv.textContent = 'Network error. Please try again.';
      errorDiv.style.display = 'block';
    }
  }

  btn.disabled = false;
  btnText.style.display = 'inline';
  btnLoader.style.display = 'none';
}

// Social sign in
function signInWithGoogle() {
  showToast('Google sign in coming soon!', 'success');
}

// Social sign up
function signUpWithGoogle() {
  showToast('Google sign up coming soon!', 'success');
}

// Make functions globally available
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.updateCartQuantity = updateCartQuantity;
window.handleSignIn = handleSignIn;
window.handleSignUp = handleSignUp;
window.handleForgotPassword = handleForgotPassword;
window.handleContactSubmit = handleContactSubmit;
window.handleCheckout = handleCheckout;

// ==================== ADDRESS MANAGEMENT FUNCTIONS ====================

/**
 * Fetch user's saved addresses from API
 */
async function fetchSavedAddresses() {
  try {
    const csrfToken = getCsrfToken();
    const response = await fetch('api/address/get.php', {
      headers: { 'X-CSRF-Token': csrfToken }
    });
    const result = await response.json();

    if (result.status === 'success') {
      return result.data || [];
    }
    console.error('Failed to fetch addresses:', result.message);
    return [];
  } catch (error) {
    console.error('Error fetching addresses:', error);
    return [];
  }
}

/**
 * Render address selection cards at checkout
 */
function renderAddressSelector(addresses) {
  const container = document.getElementById('saved-addresses-list');
  const savedSection = document.getElementById('saved-addresses-section');
  const newAddressSection = document.getElementById('new-address-section');
  const emailOnlySection = document.getElementById('email-only-section');
  const saveAddressRow = document.getElementById('save-address-row');

  if (!container || !savedSection) return;

  if (addresses.length === 0) {
    // No saved addresses - show manual form
    savedSection.style.display = 'none';
    if (newAddressSection) newAddressSection.style.display = 'block';
    if (emailOnlySection) emailOnlySection.style.display = 'none';
    return;
  }

  // Has saved addresses - show selector
  savedSection.style.display = 'block';
  if (newAddressSection) newAddressSection.style.display = 'none';
  if (emailOnlySection) emailOnlySection.style.display = 'block';
  if (saveAddressRow) saveAddressRow.style.display = 'none';

  container.innerHTML = '<div class="address-cards">' + addresses.map((addr, index) => `
    <label class="address-card ${addr.is_default ? 'selected' : ''}" data-address-id="${addr.id}">
      <input type="radio" name="savedAddressId" value="${addr.id}"
             ${addr.is_default ? 'checked' : ''} />
      <div class="address-card-content">
        <div class="address-card-info">
          <h4>
            ${esc(addr.first_name)} ${esc(addr.last_name)}
            ${addr.label ? `<span class="address-card-label">${esc(addr.label)}</span>` : ''}
            ${addr.is_default ? '<span class="address-card-default">Default</span>' : ''}
          </h4>
          <p>
            ${esc(addr.address_line1)}${addr.address_line2 ? ', ' + esc(addr.address_line2) : ''}<br>
            ${esc(addr.city)}, ${esc(addr.state)} ${esc(addr.zip_code)}<br>
            ${esc(addr.country)} &bull; ${esc(addr.phone)}
          </p>
        </div>
      </div>
    </label>
  `).join('') + '</div>';

  // Add click handlers for visual selection
  container.querySelectorAll('.address-card').forEach(card => {
    card.addEventListener('click', function() {
      container.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
      this.classList.add('selected');
    });
  });

  // Pre-fill email field with user's email from session
  const userSession = getUserSession();
  if (userSession && userSession.email) {
    const emailSavedField = document.getElementById('email-saved');
    if (emailSavedField && !emailSavedField.value) {
      emailSavedField.value = userSession.email;
    }
  }
}

/**
 * Setup toggle between saved addresses and new address form
 */
function setupAddressFormToggle() {
  const toggleBtn = document.getElementById('use-new-address-btn');
  const newAddressSection = document.getElementById('new-address-section');
  const savedSection = document.getElementById('saved-addresses-section');
  const emailOnlySection = document.getElementById('email-only-section');
  const saveAddressRow = document.getElementById('save-address-row');

  if (!toggleBtn) return;

  let showingNewForm = false;

  toggleBtn.addEventListener('click', function() {
    showingNewForm = !showingNewForm;

    if (showingNewForm) {
      // Show new address form
      if (newAddressSection) newAddressSection.style.display = 'block';
      if (emailOnlySection) emailOnlySection.style.display = 'none';
      if (saveAddressRow) saveAddressRow.style.display = 'block';
      toggleBtn.textContent = '← Use Saved Address';

      // Uncheck any selected saved address
      document.querySelectorAll('input[name="savedAddressId"]').forEach(r => r.checked = false);
      document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
    } else {
      // Show saved addresses
      if (newAddressSection) newAddressSection.style.display = 'none';
      if (emailOnlySection) emailOnlySection.style.display = 'block';
      if (saveAddressRow) saveAddressRow.style.display = 'none';
      toggleBtn.textContent = '+ Use a Different Address';

      // Re-select default address
      const defaultRadio = document.querySelector('.address-card.selected input[name="savedAddressId"]');
      if (defaultRadio) {
        defaultRadio.checked = true;
      } else {
        // Select first address if no default
        const firstRadio = document.querySelector('input[name="savedAddressId"]');
        if (firstRadio) {
          firstRadio.checked = true;
          firstRadio.closest('.address-card').classList.add('selected');
        }
      }
    }
  });
}

/**
 * Pre-fill checkout contact fields from address
 */
function prefillCheckoutContact(addr) {
  const form = document.getElementById('checkout-form');
  if (!form || !addr) return;

  // Only prefill if fields are empty
  const firstName = form.querySelector('[name="firstName"]');
  const lastName = form.querySelector('[name="lastName"]');
  const phone = form.querySelector('[name="phone"]');

  if (firstName && !firstName.value) firstName.value = addr.first_name || '';
  if (lastName && !lastName.value) lastName.value = addr.last_name || '';
  if (phone && !phone.value) phone.value = addr.phone || '';
}

/**
 * Initialize address selection at checkout
 */
async function initCheckoutAddresses() {
  const userSession = getUserSession();
  if (!userSession || !userSession.id) return;

  const addresses = await fetchSavedAddresses();

  if (addresses.length > 0) {
    renderAddressSelector(addresses);
    setupAddressFormToggle();

    // Pre-fill contact info from default address
    const defaultAddr = addresses.find(a => a.is_default) || addresses[0];
    if (defaultAddr) {
      prefillCheckoutContact(defaultAddr);
    }
  }
}

/**
 * Check if using saved address at checkout
 */
function isUsingSavedAddress() {
  const savedSection = document.getElementById('saved-addresses-section');
  const newAddressSection = document.getElementById('new-address-section');

  if (!savedSection || savedSection.style.display === 'none') return false;
  if (newAddressSection && newAddressSection.style.display !== 'none') return false;

  const selectedRadio = document.querySelector('input[name="savedAddressId"]:checked');
  return selectedRadio !== null;
}

/**
 * Get selected saved address ID
 */
function getSelectedAddressId() {
  const selectedRadio = document.querySelector('input[name="savedAddressId"]:checked');
  return selectedRadio ? parseInt(selectedRadio.value) : null;
}

// ==================== ADDRESS CRUD FOR ACCOUNT PAGE ====================

/**
 * Add new address via API
 */
async function addAddress(addressData) {
  const csrfToken = getCsrfToken();
  try {
    const response = await fetch('api/address/add.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify(addressData)
    });
    const result = await response.json();

    if (result.status === 'success') {
      showToast('Address added successfully', 'success');
      return result.data;
    } else {
      showToast(result.message || 'Failed to add address', 'error');
      return null;
    }
  } catch (error) {
    console.error('Error adding address:', error);
    showToast('Failed to add address', 'error');
    return null;
  }
}

/**
 * Update address via API
 */
async function updateAddress(addressData) {
  const csrfToken = getCsrfToken();
  try {
    const response = await fetch('api/address/update.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify(addressData)
    });
    const result = await response.json();

    if (result.status === 'success') {
      showToast('Address updated successfully', 'success');
      return true;
    } else {
      showToast(result.message || 'Failed to update address', 'error');
      return false;
    }
  } catch (error) {
    console.error('Error updating address:', error);
    showToast('Failed to update address', 'error');
    return false;
  }
}

/**
 * Delete address via API
 */
async function deleteAddress(addressId) {
  const csrfToken = getCsrfToken();
  try {
    const response = await fetch('api/address/delete.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify({ id: addressId })
    });
    const result = await response.json();

    if (result.status === 'success') {
      showToast('Address deleted successfully', 'success');
      return true;
    } else {
      showToast(result.message || 'Failed to delete address', 'error');
      return false;
    }
  } catch (error) {
    console.error('Error deleting address:', error);
    showToast('Failed to delete address', 'error');
    return false;
  }
}

/**
 * Set address as default via API
 */
async function setDefaultAddress(addressId) {
  const csrfToken = getCsrfToken();
  try {
    const response = await fetch('api/address/set-default.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify({ id: addressId })
    });
    const result = await response.json();

    if (result.status === 'success') {
      showToast('Default address updated', 'success');
      return true;
    } else {
      showToast(result.message || 'Failed to set default address', 'error');
      return false;
    }
  } catch (error) {
    console.error('Error setting default address:', error);
    showToast('Failed to set default address', 'error');
    return false;
  }
}

/**
 * Render addresses list in account page
 */
async function renderAccountAddresses() {
  const container = document.getElementById('addresses-list');
  if (!container) return;

  container.innerHTML = '<div class="loading-spinner">Loading addresses...</div>';

  const addresses = await fetchSavedAddresses();

  if (addresses.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <p>No saved addresses yet.</p>
        <p>Add an address to speed up your checkout process.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = addresses.map(addr => `
    <div class="address-card-account" data-address-id="${addr.id}">
      <div class="address-card-header">
        <div class="address-card-badges">
          ${addr.label ? `<span class="address-card-label">${esc(addr.label)}</span>` : ''}
          ${addr.is_default ? '<span class="address-card-default">Default</span>' : ''}
        </div>
        <div class="address-card-actions">
          <button class="btn-icon" onclick="editAddressModal(${addr.id})" title="Edit">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
          </button>
          ${!addr.is_default ? `
            <button class="btn-icon" onclick="confirmSetDefault(${addr.id})" title="Set as Default">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
            </button>
          ` : ''}
          <button class="btn-icon btn-danger" onclick="confirmDeleteAddress(${addr.id})" title="Delete">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6l-2 14H7L5 6"></path>
              <path d="M10 11v6"></path>
              <path d="M14 11v6"></path>
            </svg>
          </button>
        </div>
      </div>
      <div class="address-card-body">
        <h4>${esc(addr.first_name)} ${esc(addr.last_name)}</h4>
        <p>
          ${esc(addr.address_line1)}${addr.address_line2 ? '<br>' + esc(addr.address_line2) : ''}<br>
          ${esc(addr.city)}, ${esc(addr.state)} ${esc(addr.zip_code)}<br>
          ${esc(addr.country)}<br>
          <strong>Phone:</strong> ${esc(addr.phone)}
        </p>
      </div>
    </div>
  `).join('');
}

/**
 * Open add address modal
 */
function openAddAddressModal() {
  const modal = document.getElementById('address-modal');
  const form = document.getElementById('address-form');
  const title = document.getElementById('address-modal-title');

  if (!modal || !form) return;

  // Reset form
  form.reset();
  document.getElementById('address-id').value = '';
  if (title) title.textContent = 'Add New Address';

  modal.style.display = 'flex';
}

/**
 * Open edit address modal with data
 */
async function editAddressModal(addressId) {
  const modal = document.getElementById('address-modal');
  const form = document.getElementById('address-form');
  const title = document.getElementById('address-modal-title');

  if (!modal || !form) return;

  // Fetch address data
  const addresses = await fetchSavedAddresses();
  const addr = addresses.find(a => a.id === addressId);

  if (!addr) {
    showToast('Address not found', 'error');
    return;
  }

  // Populate form
  document.getElementById('address-id').value = addr.id;
  form.firstName.value = addr.first_name;
  form.lastName.value = addr.last_name;
  form.address.value = addr.address_line1;
  if (form.address2) form.address2.value = addr.address_line2 || '';
  form.city.value = addr.city;
  form.state.value = addr.state;
  form.zip.value = addr.zip_code;
  form.country.value = addr.country;
  form.phone.value = addr.phone;
  if (form.label) form.label.value = addr.label || '';
  if (form.isDefault) form.isDefault.checked = addr.is_default;

  if (title) title.textContent = 'Edit Address';

  modal.style.display = 'flex';
}

/**
 * Close address modal
 */
function closeAddressModal() {
  const modal = document.getElementById('address-modal');
  if (modal) modal.style.display = 'none';
}

/**
 * Handle address form submit
 */
async function handleAddressFormSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const addressId = document.getElementById('address-id').value;

  const addressData = {
    firstName: form.firstName.value.trim(),
    lastName: form.lastName.value.trim(),
    address: form.address.value.trim(),
    address2: form.address2?.value.trim() || '',
    city: form.city.value.trim(),
    state: form.state.value.trim(),
    zip: form.zip.value.trim(),
    country: form.country.value,
    phone: form.phone.value.trim(),
    label: form.label?.value || null,
    isDefault: form.isDefault?.checked || false
  };

  let success;
  if (addressId) {
    // Update existing
    addressData.id = parseInt(addressId);
    success = await updateAddress(addressData);
  } else {
    // Add new
    success = await addAddress(addressData);
  }

  if (success) {
    closeAddressModal();
    renderAccountAddresses();
  }
}

/**
 * Confirm and delete address
 */
async function confirmDeleteAddress(addressId) {
  if (!confirm('Are you sure you want to delete this address?')) return;

  const success = await deleteAddress(addressId);
  if (success) {
    renderAccountAddresses();
  }
}

/**
 * Confirm and set default address
 */
async function confirmSetDefault(addressId) {
  const success = await setDefaultAddress(addressId);
  if (success) {
    renderAccountAddresses();
  }
}

// Export address functions
window.fetchSavedAddresses = fetchSavedAddresses;
window.renderAddressSelector = renderAddressSelector;
window.initCheckoutAddresses = initCheckoutAddresses;
window.isUsingSavedAddress = isUsingSavedAddress;
window.getSelectedAddressId = getSelectedAddressId;
window.addAddress = addAddress;
window.updateAddress = updateAddress;
window.deleteAddress = deleteAddress;
window.setDefaultAddress = setDefaultAddress;
window.renderAccountAddresses = renderAccountAddresses;
window.openAddAddressModal = openAddAddressModal;
window.editAddressModal = editAddressModal;
window.closeAddressModal = closeAddressModal;
window.handleAddressFormSubmit = handleAddressFormSubmit;
window.confirmDeleteAddress = confirmDeleteAddress;
window.confirmSetDefault = confirmSetDefault;

// ==================== WISHLIST FUNCTIONALITY ====================

// Add to wishlist
function addToWishlist(productId, productName, productPrice, productImage, productCategory, productDescription, productRating) {
  let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
  
  // Check if already in wishlist
  if (wishlist.find(item => item.id === productId)) {
    showToast('Already in wishlist', 'info');
    return;
  }
  
  wishlist.push({
    id: productId,
    name: productName,
    price: productPrice,
    image: productImage,
    category: productCategory,
    description: productDescription,
    rating: productRating || 4.5
  });
  
  localStorage.setItem('wishlist', JSON.stringify(wishlist));
  updateWishlistCount();
  showToast('Added to wishlist', 'success');
}

// Remove from wishlist
function removeFromWishlist(productId) {
  let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
  wishlist = wishlist.filter(item => item.id !== productId);
  localStorage.setItem('wishlist', JSON.stringify(wishlist));
  updateWishlistCount();
  showToast('Removed from wishlist', 'success');
}

// Check if product is in wishlist
function isInWishlist(productId) {
  const wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
  return wishlist.find(item => item.id === productId) !== undefined;
}

// Update wishlist count in header
function updateWishlistCount() {
  const wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
  const wishlistCount = document.getElementById('wishlist-count');
  if (wishlistCount) {
    wishlistCount.textContent = wishlist.length;
    wishlistCount.style.display = wishlist.length > 0 ? 'flex' : 'none';
  }
}

// Initialize wishlist count on page load
if (typeof document !== 'undefined') {
  document.addEventListener('DOMContentLoaded', function() {
    updateWishlistCount();
  });
}

// Export functions
window.addToWishlist = addToWishlist;
window.removeFromWishlist = removeFromWishlist;
window.isInWishlist = isInWishlist;
window.updateWishlistCount = updateWishlistCount;

// Header search functionality
function performHeaderSearch() {
  const query = document.getElementById('header-search-input')?.value.trim();
  if (query) {
    window.location.href = `search.php?q=${encodeURIComponent(query)}`;
  }
}

let headerSearchScrollPosition = null;

function rememberHeaderSearchScroll() {
  headerSearchScrollPosition = {
    x: window.scrollX,
    y: window.scrollY
  };
}

function restoreHeaderSearchScroll() {
  if (!headerSearchScrollPosition) return;

  const { x, y } = headerSearchScrollPosition;
  window.scrollTo(x, y);
  requestAnimationFrame(() => window.scrollTo(x, y));
}

function focusHeaderSearch(input) {
  if (!input) return;

  rememberHeaderSearchScroll();

  try {
    input.focus({ preventScroll: true });
  } catch (error) {
    input.focus();
  }

  restoreHeaderSearchScroll();
}

// Search on Enter key
if (typeof document !== 'undefined') {
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('header-search-input');
    if (searchInput) {
      const searchForm = searchInput.closest('.nav-search');
      const searchButton = searchForm?.querySelector('.nav-search-trigger');

      searchForm?.addEventListener('pointerdown', rememberHeaderSearchScroll);

      searchButton?.addEventListener('click', function(e) {
        if (!searchForm) return;

        e.preventDefault();

        const query = searchInput.value.trim();
        if (!searchForm.classList.contains('is-open')) {
          searchForm.classList.add('is-open');
          focusHeaderSearch(searchInput);
          return;
        }

        if (query) {
          performHeaderSearch();
        } else {
          focusHeaderSearch(searchInput);
        }
      });

      searchForm?.addEventListener('click', function(e) {
        if (!searchForm.classList.contains('is-open') && e.target !== searchInput) {
          e.preventDefault();
          searchForm.classList.add('is-open');
          focusHeaderSearch(searchInput);
        }
      });

      searchInput.addEventListener('focus', function() {
        rememberHeaderSearchScroll();
        searchForm?.classList.add('is-open');
        restoreHeaderSearchScroll();
      });

      searchInput.addEventListener('input', function() {
        restoreHeaderSearchScroll();
      });

      searchInput.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter') {
          restoreHeaderSearchScroll();
        }
      });

      searchInput.addEventListener('keyup', function(e) {
        if (e.key !== 'Enter') {
          restoreHeaderSearchScroll();
        }
      });

      searchInput.addEventListener('blur', function() {
        headerSearchScrollPosition = null;
      });

      searchForm?.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!searchInput.value.trim()) {
          searchForm.classList.add('is-open');
          focusHeaderSearch(searchInput);
          return;
        }

        performHeaderSearch();
      });

      document.addEventListener('click', function(e) {
        if (searchForm && !searchForm.contains(e.target) && !searchInput.value.trim()) {
          searchForm.classList.remove('is-open');
        }
      });

      searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          performHeaderSearch();
        }
      });
    }
  });
}

window.performHeaderSearch = performHeaderSearch;

function initSearchModal() {
  const modal = document.getElementById('site-search-modal');
  const input = document.getElementById('site-search-modal-input');
  const results = document.getElementById('site-search-modal-results');
  const submit = document.getElementById('site-search-modal-submit');
  if (!modal || !input || !results) return;

  let timer = null;
  let activeIndex = -1;

  function openSearchModal(seed = '') {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    input.value = seed;
    setTimeout(() => input.focus(), 20);
    if (seed.length >= 2) fetchSearchResults(seed);
    else fetchSearchResults('');
  }

  function closeSearchModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    activeIndex = -1;
  }

  async function fetchSearchResults(query) {
    const response = await fetch(`api/product/search.php?q=${encodeURIComponent(query)}&limit=8`);
    const data = await response.json();
    const items = data?.data?.items || [];
    const trends = `
      <div class="search-modal-trends">
        ${['UI Kit', 'Mockups', 'Dashboard', 'Icons', 'Landing Page'].map(term => `<button type="button" class="search-trend-pill" data-trend="${term}">${term}</button>`).join('')}
      </div>
      <div class="search-result-count">${items.length} result${items.length === 1 ? '' : 's'} found</div>
    `;
    results.innerHTML = trends + (items.length ? items.map((item, index) => `
      <button type="button" class="search-modal-result" data-index="${index}" data-type="${esc(item.type || 'product')}" data-id="${esc(item.id)}">
        <img src="${esc(item.image)}" alt="" onerror="this.src='img/poster.webp'">
        <span><strong>${esc(item.name)}</strong><em>${esc(item.type || 'product')} / ${esc(item.category || '')}</em></span>
        <b>${money(item.price).replace('₹', '$')}</b>
      </button>
    `).join('') : '<div class="search-modal-empty">No matching products or bundles found.</div>');
  }

  document.querySelectorAll('.nav-search-trigger').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      const navInput = button.closest('.nav-search')?.querySelector('.nav-search-input');
      openSearchModal(navInput?.value.trim() || '');
    });
  });

  document.querySelectorAll('.nav-search').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      openSearchModal(form.querySelector('.nav-search-input')?.value.trim() || '');
    });
  });

  input.addEventListener('input', () => {
    clearTimeout(timer);
    activeIndex = -1;
    const query = input.value.trim();
    if (query.length < 2) {
      results.innerHTML = '<div class="search-modal-empty">Type at least 2 characters to search.</div>';
      return;
    }
    timer = setTimeout(() => fetchSearchResults(query), 180);
  });

  input.addEventListener('keydown', (event) => {
    const options = Array.from(results.querySelectorAll('.search-modal-result'));
    if (event.key === 'Escape') closeSearchModal();
    if (!options.length) return;
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      activeIndex = (activeIndex + 1) % options.length;
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      activeIndex = (activeIndex - 1 + options.length) % options.length;
    } else if (event.key === 'Enter') {
      event.preventDefault();
      (options[activeIndex] || options[0]).click();
      return;
    } else {
      return;
    }
    options.forEach((option, index) => option.classList.toggle('is-active', index === activeIndex));
  });

  results.addEventListener('click', (event) => {
    const trend = event.target.closest('.search-trend-pill');
    if (trend) {
      input.value = trend.dataset.trend || '';
      fetchSearchResults(input.value);
      return;
    }
    const item = event.target.closest('.search-modal-result');
    if (!item) return;
    closeSearchModal();
    if (item.dataset.type === 'bundle') {
      window.location.href = `bundles.php?quick=bundle&id=${encodeURIComponent(item.dataset.id)}`;
      return;
    }
    window.location.href = `product.php?id=${encodeURIComponent(item.dataset.id)}`;
  });

  submit?.addEventListener('click', () => {
    const first = results.querySelector('.search-modal-result');
    if (first) first.click();
    else if (input.value.trim()) window.location.href = `search.php?q=${encodeURIComponent(input.value.trim())}`;
  });

  modal.querySelectorAll('[data-search-close]').forEach((el) => {
    el.addEventListener('click', closeSearchModal);
  });
}

function ensureMarketplaceModal() {
  let modal = document.getElementById('marketplace-modal');
  if (modal) return modal;
  modal = document.createElement('div');
  modal.id = 'marketplace-modal';
  modal.className = 'marketplace-modal';
  modal.innerHTML = `
    <div class="marketplace-modal-backdrop" data-close-marketplace-modal></div>
    <section class="marketplace-modal-panel" role="dialog" aria-modal="true" aria-live="polite">
      <button type="button" class="marketplace-modal-close" data-close-marketplace-modal aria-label="Close">×</button>
      <div class="marketplace-modal-content skeleton">Loading...</div>
    </section>`;
  document.body.appendChild(modal);
  modal.addEventListener('click', (event) => {
    if (event.target.closest('[data-close-marketplace-modal]')) closeMarketplaceModal();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMarketplaceModal();
  });
  return modal;
}

async function openMarketplaceModal(type, id) {
  const modal = ensureMarketplaceModal();
  const content = modal.querySelector('.marketplace-modal-content');
  modal.classList.add('is-open');
  document.body.classList.add('modal-open');
  content.className = 'marketplace-modal-content skeleton';
  content.innerHTML = 'Loading...';
  try {
    const response = await fetch(`api/catalog/detail.php?type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}`);
    const data = await response.json();
    if (data.status !== 'success') throw new Error(data.message || 'Unable to load item.');
    const item = data.data.item;
    const related = data.data.related || [];
    const reviews = data.data.reviews || [];
    const reviewHtml = reviews.length
      ? reviews.map(review => `<div class="review-row"><strong>${esc(review.user_name || 'Customer')}</strong><span>★ ${esc(review.rating)}</span><p>${esc(review.comment || 'Verified purchase')}</p></div>`).join('')
      : '<div class="review-row"><strong>Reviews</strong><p>No reviews yet. Be the first to review this item after purchase.</p></div>';
    const relatedHtml = related.length
      ? related.map(rel => `<button type="button" class="related-chip" onclick="openMarketplaceModal('${esc(rel.type || type)}', '${esc(rel.id)}')"><img src="${esc(rel.image)}" alt=""><span>${esc(rel.name)}</span><strong>${money(rel.price)}</strong></button>`).join('')
      : '';
    content.className = 'marketplace-modal-content';
    content.innerHTML = `
      <div class="marketplace-gallery">
        <img src="${esc(item.image)}" alt="${esc(item.name)}" onerror="this.src='img/poster.webp'">
      </div>
      <div class="marketplace-details">
        <div class="marketplace-kicker">${esc(item.type || type)} · ${esc(item.category || '')}</div>
        <h2>${esc(item.name)}</h2>
        <p>${esc(item.description || '')}</p>
        <div class="marketplace-meta">
          <span>★ ${esc(item.rating || '4.5')}</span>
          <span>${Number(item.stock || 0) > 0 ? 'In stock' : 'Out of stock'}</span>
          ${(item.tags || '').split(',').filter(Boolean).slice(0, 3).map(tag => `<span>${esc(tag.trim())}</span>`).join('')}
        </div>
        <div class="marketplace-price">
          <strong>${money(item.price)}</strong>
          ${item.old_price ? `<span>${money(item.old_price)}</span>` : ''}
          ${item.discount_percent ? `<em>${item.discount_percent}% OFF</em>` : ''}
        </div>
        <div class="marketplace-qty">
          <label for="marketplace-qty-input">Qty</label>
          <input id="marketplace-qty-input" type="number" min="1" max="10" value="1">
        </div>
        <div class="marketplace-modal-actions">
          <button type="button" class="btn btn-primary" onclick="addMarketplaceItemToCart('${esc(item.type || type)}', '${esc(item.id)}')">Add to Cart</button>
          <button type="button" class="btn btn-outline" onclick="toggleMarketplaceWishlist('${esc(item.type || type)}', '${esc(item.id)}')">Wishlist</button>
        </div>
        <div class="marketplace-reviews">${reviewHtml}</div>
        ${relatedHtml ? `<h3>Suggested for you</h3><div class="marketplace-related">${relatedHtml}</div>` : ''}
      </div>`;
  } catch (error) {
    content.className = 'marketplace-modal-content';
    content.innerHTML = `<div class="marketplace-error">${esc(error.message || 'Something went wrong.')}</div>`;
  }
}

function closeMarketplaceModal() {
  const modal = document.getElementById('marketplace-modal');
  if (!modal) return;
  modal.classList.remove('is-open');
  document.body.classList.remove('modal-open');
}

async function fetchMarketplaceItem(type, id) {
  const response = await fetch(`api/catalog/detail.php?type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}`);
  const data = await response.json();
  if (data.status !== 'success') throw new Error(data.message || 'Unable to load item.');
  return data.data.item;
}

async function addMarketplaceItemToCart(type, id) {
  try {
    const item = await fetchMarketplaceItem(type, id);
    const qty = Math.max(1, Math.min(10, Number(document.getElementById('marketplace-qty-input')?.value || 1)));
    await addToCart(type === 'bundle' ? `bundle-${item.id}` : item.id, null, qty, item, item.available_type || 'digital');
  } catch (error) {
    showToast(error.message || 'Could not add item.', 'error');
  }
}

async function toggleMarketplaceWishlist(type, id) {
  const userSession = getUserSession();
  if (!userSession || !userSession.id) {
    showToast('Please sign in to use wishlist.', 'error');
    setTimeout(() => { window.location.href = 'signin.php?redirect=wishlist.php'; }, 700);
    return;
  }
  try {
    const payload = type === 'bundle' ? { item_type: 'bundle', bundle_id: Number(id) } : { item_type: 'product', product_id: Number(id) };
    const response = await fetch('api/wishlist/add.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      body: JSON.stringify(payload)
    });
    const data = await response.json();
    if (data.status !== 'success') throw new Error(data.message || 'Wishlist failed.');
    showToast('Added to wishlist.', 'success');
    updateWishlistCount();
  } catch (error) {
    showToast(error.message || 'Wishlist failed.', 'error');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  initSearchModal();
  document.querySelector('.mobile-nav-toggle')?.addEventListener('click', (event) => {
    const header = event.currentTarget.closest('.navbar');
    const open = !header.classList.contains('mobile-open');
    header.classList.toggle('mobile-open', open);
    event.currentTarget.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
});

window.openMarketplaceModal = openMarketplaceModal;
window.closeMarketplaceModal = closeMarketplaceModal;
window.addMarketplaceItemToCart = addMarketplaceItemToCart;
window.toggleMarketplaceWishlist = toggleMarketplaceWishlist;

// Global navbar active states for both header systems used in the project.
if (typeof document !== 'undefined') {
  document.addEventListener('DOMContentLoaded', function() {
    const navLinks = Array.from(document.querySelectorAll(
      '.navbar .nav-links a, .navbar .mobile-nav-panel a, .site-header .nav-links a, .site-header .nav-mobile-menu a'
    ));
    if (!navLinks.length) return;

    const productPages = new Set(['shopall.php', 'products.php', 'product.php', 'search.php']);
    const categoryPages = new Set(['category.php']);
    const bundlePages = new Set(['bundles.php']);
    const homePages = new Set(['', '/', 'index.php']);

    function cleanPath(pathname) {
      const file = (pathname || '').split('/').filter(Boolean).pop() || 'index.php';
      return file.toLowerCase();
    }

    function pageGroup(url) {
      const file = cleanPath(url.pathname);
      if (productPages.has(file)) return 'products';
      if (categoryPages.has(file)) return 'category';
      if (bundlePages.has(file)) return 'bundles';
      if (homePages.has(file)) {
        if (url.hash === '#products') return 'home-products';
        if (url.hash === '#category') return 'category';
        return 'home';
      }
      return '';
    }

    function linkGroup(link) {
      const url = new URL(link.getAttribute('href') || '', window.location.href);
      const file = cleanPath(url.pathname);
      const text = (link.textContent || '').trim().toLowerCase();

      if (productPages.has(file) || ['products', 'buy now', 'shop'].includes(text)) return 'products';
      if (homePages.has(file) && url.hash === '#products') return 'home-products';
      if (categoryPages.has(file) || text === 'category') return 'category';
      if (bundlePages.has(file) || text === 'bundles') return 'bundles';
      if (homePages.has(file) && !url.hash && text === 'home') return 'home';
      if (homePages.has(file) && url.hash === '#category') return 'category';
      return '';
    }

    function setActiveNav(group) {
      navLinks.forEach(link => {
        const isActive = linkGroup(link) === group;
        link.classList.toggle('active', isActive);
        link.classList.toggle('nav-link-active', isActive);
        if (isActive) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
      });
    }

    function syncFromUrl() {
      setActiveNav(pageGroup(new URL(window.location.href)));
    }

    syncFromUrl();
    window.addEventListener('hashchange', syncFromUrl);

    if (cleanPath(window.location.pathname) === 'index.php') {
      const sectionLinks = navLinks
        .map(link => new URL(link.href, window.location.href))
        .filter(url => url.hash && cleanPath(url.pathname) === 'index.php');
      const sections = sectionLinks
        .map(url => document.querySelector(url.hash))
        .filter(Boolean);

      if (sections.length) {
        function syncHomeSections() {
          if (window.scrollY < 220) {
            setActiveNav('home');
            return;
          }
          const current = sections
            .filter(section => section.offsetTop - 140 <= window.scrollY)
            .sort((a, b) => b.offsetTop - a.offsetTop)[0];
          if (current?.id === 'products') setActiveNav('home-products');
          else if (current?.id === 'category') setActiveNav('category');
          else setActiveNav('home');
        }
        window.addEventListener('scroll', syncHomeSections, { passive: true });
        syncHomeSections();
      }
    }
  });
}

// Buy Now function - adds to cart and redirects to checkout
function buyNow(productId, size, quantity) {
  // Check if user is signed in
  const userSession = getUserSession();
  if (!userSession || !userSession.id) {
    if (userSession) clearUserSession(); // Clear invalid session
    // Add to cart first
    addToCart(productId, size, quantity);
    showToast('Please sign in to complete your purchase', 'error');
    // Redirect to sign in with redirect to checkout
    setTimeout(() => {
      window.location.href = 'signin.php?redirect=checkout.php';
    }, 1500);
    return;
  }
  
  // Add to cart first
  addToCart(productId, size, quantity);
  
  // Redirect to checkout after a short delay
  setTimeout(() => {
    window.location.href = 'checkout.php';
  }, 500);
}

// Check authentication before proceeding to checkout
function checkAuthBeforeCheckout(event) {
  const userSession = getUserSession();
  if (!userSession || !userSession.id) {
    if (userSession) clearUserSession(); // Clear invalid session
    event.preventDefault();
    showToast('Please sign in to proceed to checkout', 'error');
    setTimeout(() => {
      window.location.href = 'signin.php?redirect=checkout.php';
    }, 1500);
    return false;
  }
  return true;
}

// Handle redirect after sign in
function handleSignInRedirect() {
  const urlParams = new URLSearchParams(window.location.search);
  const redirect = urlParams.get('redirect');
  // Only allow relative redirects to prevent open redirect attacks
  const allowedPages = ['index.php', 'cart.php', 'checkout.php', 'account.php', 'orders.php', 'shopAll.php', 'wishlist.php'];
  if (redirect && allowedPages.some(page => redirect.includes(page)) && !redirect.includes('://')) {
    setTimeout(() => {
      window.location.href = redirect;
    }, 1500);
  } else {
    setTimeout(() => {
      window.location.href = 'index.php';
    }, 1500);
  }
}

window.buyNow = buyNow;
window.handleSignOut = handleSignOut;
window.signInWithGoogle = signInWithGoogle;
window.signUpWithGoogle = signUpWithGoogle;
window.checkAuthBeforeCheckout = checkAuthBeforeCheckout;
window.loadOrderConfirmationPage = loadOrderConfirmationPage;

// ==================== LOCALHOST SHOP INTERACTIONS ====================
(function initLocalhostShop() {
  const money = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN')}`;
  const currentAddToCart = window.addToCart || addToCart;
  const currentRemoveFromCart = window.removeFromCart || removeFromCart;
  const currentUpdateCartQuantity = window.updateCartQuantity || updateCartQuantity;

  function ensureCartDrawer() {
    let drawer = document.getElementById('cart-drawer');
    if (drawer) return drawer;

    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <div class="cart-drawer-overlay" data-cart-close></div>
      <aside id="cart-drawer" class="cart-drawer" aria-hidden="true" aria-label="Shopping cart">
        <div class="cart-drawer-head">
          <div>
            <span>Your Cart</span>
            <strong id="cart-drawer-count">0 items</strong>
          </div>
          <button type="button" class="cart-drawer-close" data-cart-close aria-label="Close cart">x</button>
        </div>
        <div id="cart-drawer-items" class="cart-drawer-items"></div>
        <div class="cart-drawer-foot">
          <div class="cart-drawer-total"><span>Subtotal</span><strong id="cart-drawer-total">Rs. 0</strong></div>
          <p id="cart-drawer-message" class="cart-drawer-message"></p>
          <button type="button" id="cart-drawer-checkout" class="cart-drawer-checkout">Proceed to Checkout</button>
        </div>
      </aside>
    `;
    document.body.append(...wrap.children);
    document.querySelectorAll('[data-cart-close]').forEach((node) => {
      node.addEventListener('click', closeCartDrawer);
    });
    document.getElementById('cart-drawer-checkout')?.addEventListener('click', () => {
      const user = getUserSession();
      if (!user || !user.id) {
        const msg = document.getElementById('cart-drawer-message');
        if (msg) {
          msg.innerHTML = 'Please <a href="signin.php?redirect=checkout.php">sign in</a> or <a href="signup.php">sign up</a> to checkout.';
        }
        showToast('Please sign in to proceed to checkout', 'error');
        return;
      }
      window.location.href = 'checkout.php';
    });
    return document.getElementById('cart-drawer');
  }

  function renderCartDrawer() {
    ensureCartDrawer();
    const itemsEl = document.getElementById('cart-drawer-items');
    const countEl = document.getElementById('cart-drawer-count');
    const totalEl = document.getElementById('cart-drawer-total');
    if (!itemsEl || !countEl || !totalEl) return;

    const totalCount = cart.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    const subtotal = cart.reduce((sum, item) => sum + (Number(item.price || 0) * Number(item.quantity || 1)), 0);
    countEl.textContent = `${totalCount} item${totalCount === 1 ? '' : 's'}`;
    totalEl.textContent = money(subtotal);

    if (!cart.length) {
      itemsEl.innerHTML = '<div class="cart-drawer-empty">Your cart is empty.</div>';
      return;
    }

    itemsEl.innerHTML = cart.map((item) => `
      <article class="cart-drawer-item">
        <img src="${esc(item.image || 'img/sticker.webp')}" alt="${esc(item.name || 'Product')}" onerror="this.src='img/sticker.webp'" />
        <div>
          <h4>${esc(item.name || 'Product')}</h4>
          <p>${esc(item.available_type || 'physical')}${item.size ? ` / ${esc(item.size)}` : ''}</p>
          <strong>${money((Number(item.price || 0) * Number(item.quantity || 1)))}</strong>
          <div class="cart-drawer-qty">
            <button type="button" data-cart-qty="${esc(item.id)}" data-size="${esc(item.size || '')}" data-next="${Number(item.quantity || 1) - 1}">-</button>
            <span>${Number(item.quantity || 1)}</span>
            <button type="button" data-cart-qty="${esc(item.id)}" data-size="${esc(item.size || '')}" data-next="${Number(item.quantity || 1) + 1}">+</button>
            <button type="button" data-cart-remove="${esc(item.id)}" data-size="${esc(item.size || '')}">Remove</button>
          </div>
        </div>
      </article>
    `).join('');

    itemsEl.querySelectorAll('[data-cart-qty]').forEach((button) => {
      button.addEventListener('click', () => window.updateCartQuantity(button.dataset.cartQty, button.dataset.size || null, Number(button.dataset.next)));
    });
    itemsEl.querySelectorAll('[data-cart-remove]').forEach((button) => {
      button.addEventListener('click', () => window.removeFromCart(button.dataset.cartRemove, button.dataset.size || null));
    });
  }

  function openCartDrawer() {
    ensureCartDrawer();
    renderCartDrawer();
    document.body.classList.add('cart-drawer-open');
    document.getElementById('cart-drawer')?.setAttribute('aria-hidden', 'false');
  }

  function closeCartDrawer() {
    document.body.classList.remove('cart-drawer-open');
    document.getElementById('cart-drawer')?.setAttribute('aria-hidden', 'true');
  }

  window.openCartDrawer = openCartDrawer;
  window.closeCartDrawer = closeCartDrawer;
  window.renderCartDrawer = renderCartDrawer;

  window.addToCart = function enhancedAddToCart(...args) {
    const result = currentAddToCart(...args);
    Promise.resolve(result).then(() => {
      setTimeout(() => {
        renderCartDrawer();
        openCartDrawer();
      }, 120);
    }).catch(() => {});
    return result;
  };

  window.removeFromCart = function enhancedRemoveFromCart(...args) {
    const result = currentRemoveFromCart(...args);
    setTimeout(renderCartDrawer, 180);
    return result;
  };

  window.updateCartQuantity = function enhancedUpdateCartQuantity(...args) {
    const result = currentUpdateCartQuantity(...args);
    setTimeout(renderCartDrawer, 180);
    return result;
  };

  function syncHeaderAuth(user) {
    const signedIn = !!(user && user.id);
    const displayName = signedIn ? getUserFirstName(user) : '';
    const firstInitial = (displayName || 'U').charAt(0).toUpperCase();

    document.querySelectorAll('.header-signin-cta, .nav-cta[href="signin.php"]').forEach((el) => {
      el.style.display = signedIn ? 'none' : 'inline-flex';
      el.textContent = 'Sign In';
    });

    document.querySelectorAll('.profile-menu, .user-menu, .nav-user').forEach((menu) => {
      menu.style.display = signedIn ? 'flex' : 'none';
      const name = menu.querySelector('.user-name') || menu.querySelector('span');
      if (name && signedIn) {
        name.textContent = displayName;
      }

      const avatar = menu.querySelector('.user-avatar');
      if (avatar && signedIn) {
        avatar.innerHTML = `
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        `;
        avatar.setAttribute('aria-label', displayName || firstInitial);
      }

      const legacyDropdown = menu.querySelector('.user-dropdown');
      if (legacyDropdown && signedIn) {
        legacyDropdown.innerHTML = `
          <a href="account.php" class="user-dropdown-item"><span>Edit Profile</span></a>
          <button type="button" class="user-dropdown-item logout" onclick="handleSignOut()"><span>Logout</span></button>
        `;
      }

      const profileDropdown = menu.querySelector('.profile-dropdown');
      if (profileDropdown && signedIn) {
        profileDropdown.innerHTML = `
          <a href="account.php" role="menuitem">Edit Profile</a>
          <button type="button" role="menuitem" onclick="handleSignOut()">Logout</button>
        `;
      }

      if (signedIn && menu.classList.contains('user-menu') && !menu.querySelector('.profile-dropdown')) {
        menu.classList.add('profile-menu');
        const anchor = menu.querySelector('a');
        if (anchor) {
          anchor.outerHTML = `
            <button type="button" class="profile-menu-toggle" aria-haspopup="true" aria-expanded="false">
              <img src="img/ss/nav/iconoir_user.png" alt="User" />
              <span class="user-name">${esc(displayName)}</span>
              <i class="ph ph-caret-down"></i>
            </button>
            <div class="profile-dropdown" role="menu">
              <a href="account.php" role="menuitem">Edit Profile</a>
              <button type="button" role="menuitem" onclick="handleSignOut()">Logout</button>
            </div>
          `;
        }
      }
    });
  }

  async function hydrateSession() {
    try {
      const response = await fetch('api/auth/session.php');
      const data = await response.json();
      const user = data?.data?.user || null;
      if (user) {
        setUserSession({
          id: user.id,
          email: user.email,
          firstName: user.firstName,
          lastName: user.lastName,
          name: `${user.firstName || ''} ${user.lastName || ''}`.trim(),
          role: user.role || 'customer'
        });
      } else {
        clearUserSession();
      }
      syncHeaderAuth(getUserSession());
    } catch (error) {
      syncHeaderAuth(getUserSession());
    }
  }

  function initProfileMenus() {
    document.addEventListener('click', (event) => {
      const toggle = event.target.closest('.profile-menu-toggle');
      document.querySelectorAll('.profile-menu').forEach((menu) => {
        if (!toggle || !menu.contains(toggle)) menu.classList.remove('is-open');
      });
      if (toggle) {
        event.preventDefault();
        const menu = toggle.closest('.profile-menu');
        const nextState = !menu.classList.contains('is-open');
        menu.classList.toggle('is-open', nextState);
        toggle.setAttribute('aria-expanded', String(nextState));
      }
    });
  }

  function initCartLinks() {
    document.addEventListener('click', (event) => {
      const cartLink = event.target.closest('[data-cart-toggle], .cart-btn, .nav-cart');
      if (!cartLink) return;
      event.preventDefault();
      openCartDrawer();
    });
  }

  function initSearchSuggestions() {
    document.querySelectorAll('.nav-search').forEach((form) => {
      const input = form.querySelector('input[type="search"]');
      let panel = form.querySelector('.nav-search-suggestions');
      if (!input) return;
      if (!panel) {
        panel = document.createElement('div');
        panel.className = 'nav-search-suggestions';
        form.appendChild(panel);
      }

      let timer = null;
      input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) {
          panel.classList.remove('is-visible');
          panel.innerHTML = '';
          return;
        }
        timer = setTimeout(async () => {
          try {
            const response = await fetch(`api/product/search.php?q=${encodeURIComponent(q)}&limit=6`);
            const data = await response.json();
            const items = data?.data?.items || [];
            panel.innerHTML = items.length ? items.map((item) => `
              <button type="button" class="nav-search-suggestion" data-type="${esc(item.type || 'product')}" data-id="${esc(item.id)}" data-query="${esc(item.name)}">
                <img src="${esc(item.image)}" alt="" onerror="this.src='img/sticker.webp'" />
                <span>${esc(item.name)} <em>${esc(item.type || 'product')}</em></span>
                <strong>${money(item.price)}</strong>
              </button>
            `).join('') : '<div class="nav-search-empty">No matching products found</div>';
            panel.classList.add('is-visible');
          } catch (error) {
            panel.classList.remove('is-visible');
          }
        }, 180);
      });

      panel.addEventListener('click', (event) => {
        const item = event.target.closest('.nav-search-suggestion');
        if (!item) return;
        panel.classList.remove('is-visible');
        if ((item.dataset.type || 'product') === 'bundle') {
          window.location.href = 'bundles.php';
          return;
        }
        window.location.href = `product.php?id=${encodeURIComponent(item.dataset.id)}`;
      });
    });
  }

  function initDataAddButtons() {
    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-add-to-cart]');
      if (!button) return;
      event.preventDefault();
      const details = {
        name: button.dataset.name || 'Product',
        price: Number(button.dataset.price || 0),
        image: button.dataset.image || 'img/sticker.webp',
        category: button.dataset.category || 'Products',
        description: button.dataset.description || ''
      };
      window.addToCart(button.dataset.addToCart, null, 1, details, button.dataset.type || 'physical');
    });
  }

  const localAddToWishlist = window.addToWishlist;
  window.addToWishlist = function enhancedWishlist(productId, productName, productPrice, productImage, productCategory, productDescription, productRating) {
    const user = getUserSession();
    if (!user || !user.id) {
      return localAddToWishlist(productId, productName, productPrice, productImage, productCategory, productDescription, productRating);
    }
    const payload = {
      product_id: productId,
      details: {
        name: productName,
        price: productPrice,
        image: productImage,
        category: productCategory,
        description: productDescription,
        rating: productRating || 4.5
      }
    };
    return fetch('api/wishlist/add.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      body: JSON.stringify(payload)
    }).then((response) => response.json()).then((data) => {
      if (data.status === 'success') {
        showToast('Added to wishlist', 'success');
        syncWishlistFromAPI();
      } else {
        showToast(data.message || 'Could not update wishlist', 'error');
      }
    }).catch(() => localAddToWishlist(productId, productName, productPrice, productImage, productCategory, productDescription, productRating));
  };

  async function syncWishlistFromAPI() {
    if (!getUserSession()?.id) {
      updateWishlistCount();
      return;
    }
    try {
      const response = await fetch('api/wishlist/list.php');
      const data = await response.json();
      if (data.status === 'success') {
        localStorage.setItem('wishlist', JSON.stringify(data.data || []));
        updateWishlistCount();
      }
    } catch (error) {
      updateWishlistCount();
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    ensureCartDrawer();
    hydrateSession();
    initProfileMenus();
    initCartLinks();
    initSearchSuggestions();
    initDataAddButtons();
    renderCartDrawer();
    syncWishlistFromAPI();
  });
})();
