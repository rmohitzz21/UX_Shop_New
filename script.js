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

/**
 * Async read — resolves with a valid token, fetching from /api/auth/csrf.php if needed.
 * Pass forceRefresh=true to bypass the cache and meta tag and always pull the live
 * server-side token. Use this on auth forms (signin/signup/forgot-password/reset)
 * where a stale meta tag (e.g. left over after a session_regenerate_id in another
 * tab) would otherwise cause a 403.
 */
async function getCsrfTokenAsync(forceRefresh = false) {
  if (!forceRefresh) {
    if (_csrfToken) return _csrfToken;
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (meta) { _csrfToken = meta; return _csrfToken; }
  }
  // Deduplicate concurrent calls — only one inflight request at a time
  if (!_csrfFetchPromise) {
    _csrfFetchPromise = fetch('api/auth/csrf.php', { credentials: 'same-origin', cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        const fresh = d.data?.token || d.token || '';
        if (fresh) {
          _csrfToken = fresh;
          const meta = document.querySelector('meta[name="csrf-token"]');
          if (meta) meta.setAttribute('content', fresh);
        }
        _csrfFetchPromise = null;
        return _csrfToken || '';
      })
      .catch(() => { _csrfFetchPromise = null; return _csrfToken || ''; });
  }
  return _csrfFetchPromise;
}

function setCsrfToken(token) {
  if (!token) return;
  _csrfToken = token;
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta) meta.setAttribute('content', token);
}

/**
 * secureFetch — drop-in replacement for fetch() that automatically injects
 * the X-CSRF-Token header on every request, AND auto-recovers from a stale
 * CSRF token (403) by fetching a fresh one and retrying once.
 *
 * Usage: secureFetch('api/cart/add.php', { method:'POST', body: JSON.stringify(data) })
 */
async function secureFetch(url, options = {}) {
  const send = async (forceRefresh) => {
    const token = await getCsrfTokenAsync(forceRefresh);
    return fetch(url, {
      ...options,
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token,
        ...(options.headers || {}),
      },
    });
  };

  let res = await send(false);
  if (res.status === 403) {
    // Could be a stale CSRF token (session rotated in another tab, etc.).
    // Pull a fresh one and retry exactly once before surfacing the 403.
    res = await send(true);
  }
  return res;
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
          <div class="product-price">₹${price.toLocaleString()} ${oldPrice ? `<span>₹${oldPrice.toLocaleString()}</span>` : ''}</div>
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
     // Checkout page loads cart inside initCheckoutPageAsync to avoid empty-cart race.
     if (!window.location.pathname.includes('checkout.php')) {
       fetchCartFromAPI();
     }
  } else {
     // LocalStorage already loaded
     updateCartCount();
     if (window.location.pathname.includes('cart.php')) loadCartPage();
  }
});

let _cartFetchInFlight = null;
let _cartFetchLastAt = 0;
function fetchCartFromAPI() {
    // Dedupe: if a request is already in flight, return that promise instead of firing another.
    if (_cartFetchInFlight) return _cartFetchInFlight;
    // Throttle: ignore calls within 250ms of the previous successful response.
    if (Date.now() - _cartFetchLastAt < 250) return Promise.resolve();

    _cartFetchInFlight = fetch('api/cart/list.php', { credentials: 'same-origin' })
      .then(res => {
         if (res.status === 401) return null; // Guest / session expired
         return res.json();
      })
      .then(data => {
         if (data && data.status === 'success') {
             cart = data.data;
             saveCart();
             updateCartCount();
             if (typeof window.renderCartDrawer === 'function') window.renderCartDrawer();
             if (window.location.pathname.includes('cart.php')) loadCartPage();
             if (window.location.pathname.includes('checkout.php')) loadCheckoutPage();
         }
         return data;
      })
      .catch(err => {
         console.error('Error fetching cart:', err);
         return null;
      })
      .finally(() => {
         _cartFetchInFlight = null;
         _cartFetchLastAt = Date.now();
      });
    return _cartFetchInFlight;
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
function addToCart(productId, size = null, quantity = 1, explicitDetails = null, productFormat = null, options = {}) {
  const silent = options === true || (options && options.silent === true);
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
    const itemType = (explicitDetails && explicitDetails.item_type) ? explicitDetails.item_type : 'product';
    const isBundle = itemType === 'bundle';

    if (userSession && userSession.id) {
        // LOGGED IN: Use API
        const payload = {
            item_type: itemType,
            quantity: quantity,
            size: size,
            available_type: available_type,
            selected_format: available_type,
        };
        if (isBundle) {
            payload.bundle_id = productId;
        } else {
            payload.product_id = productId;
        }

        secureFetch('api/cart/add.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        })
        .then(async (res) => {
            let data;
            try {
                data = await res.json();
            } catch (_) {
                data = { status: 'error', message: 'Unexpected server response.' };
            }
            if (data.status === 'success') {
                fetchCartFromAPI()
                  .finally(() => {
                    if (!silent) showToast('Item added to cart!', 'success');
                    resolve(data);
                  });
            } else {
                const message = data.message || 'Failed to add item';
                if (!silent) showToast(message, 'error');
                reject({ message, status: res.status });
            }
        })
        .catch(err => {
            console.error(err);
            if (!silent) showToast('Error adding item', 'error');
            reject(typeof err === 'object' && err?.message ? err : { message: 'Error adding item', status: 0 });
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
            item_type: itemType,
            product_id: isBundle ? undefined : productId,
            bundle_id: isBundle ? productId : undefined,
            name: product.name,
            price: product.price,
            image: product.image,
            size: size,
            quantity: quantity,
            available_type: available_type,
            selected_format: available_type,
            description: product.description,
          });
        }
        
        saveCart();
        updateCartCount();
        if (!silent) {
          showToast('Item added to cart!', 'success');
          if (window.location.pathname.includes('cart.php')) {
            loadCartPage();
          }
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

function findCartLine(productId, size = null) {
  return cart.find(i => String(i.id) === String(productId) && normalizeSize(i.size) === normalizeSize(size));
}

function cartLineApiPayload(item, productId, size, quantity = null) {
  const cartId = item && item.cart_id ? Number(item.cart_id) : 0;
  if (cartId > 0) {
    const body = { cart_id: cartId };
    if (quantity !== null) body.quantity = quantity;
    return body;
  }
  const itemType = item?.item_type || 'product';
  const body = {
    item_type: itemType,
    quantity: quantity !== null ? quantity : undefined,
    size: size,
    available_type: item?.available_type || 'physical',
    selected_format: item?.selected_format || item?.available_type || 'digital',
  };
  if (itemType === 'bundle') {
    body.bundle_id = Number(item?.bundle_id || productId);
  } else {
    body.product_id = Number(productId);
  }
  return body;
}

// Remove from cart
function removeFromCart(productId, size = null, cartIdOverride = 0) {
  const userSession = getUserSession();

  const item = findCartLine(productId, size);

  if (userSession && userSession.id) {
      const body = Number(cartIdOverride) > 0
        ? { cart_id: Number(cartIdOverride) }
        : cartLineApiPayload(item, productId, size);
      secureFetch('api/cart/remove.php', {
          method: 'POST',
          body: JSON.stringify(body)
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
function updateCartQuantity(productId, size, newQuantity, cartIdOverride = 0) {
  const userSession = getUserSession();

  if (newQuantity <= 0) {
      removeFromCart(productId, size, cartIdOverride);
      return;
  }

  if (newQuantity > 10) {
      showToast('Maximum 10 items per product', 'error');
      return;
  }

  const item = findCartLine(productId, size);

  if (userSession && userSession.id) {
     const body = Number(cartIdOverride) > 0
       ? { cart_id: Number(cartIdOverride), quantity: newQuantity }
       : cartLineApiPayload(item, productId, size, newQuantity);
     secureFetch('api/cart/update.php', {
         method: 'POST',
         body: JSON.stringify(body)
     })
     .then(res => res.json())
     .then(data => {
         if(data.status === 'success') {
             fetchCartFromAPI();
         } else {
             showToast(data.message || 'Failed to update quantity', 'error');
             fetchCartFromAPI();
         }
     })
     .catch(() => showToast('Failed to update quantity', 'error'));

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

function isCatalogItemFree(item) {
  if (!item) return false;
  if (item.type === 'freebie' || item.item_type === 'freebie') return true;
  return Boolean(item.is_free) || Number(item.price || 0) <= 0;
}

function isFreeCheckout() {
  if (!cart.length) return false;
  if (getCartTotal() <= 0) return true;
  return cart.every(isCatalogItemFree);
}

function loadRazorpayScript() {
  return new Promise((resolve, reject) => {
    if (window.Razorpay) {
      resolve();
      return;
    }
    const existing = document.querySelector('script[src*="checkout.razorpay.com"]');
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('Payment gateway failed to load.')), { once: true });
      return;
    }
    const script = document.createElement('script');
    script.src = 'https://checkout.razorpay.com/v1/checkout.js';
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Payment gateway failed to load.'));
    document.body.appendChild(script);
  });
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
            <p class="cart-item-price">₹${itemTotal.toLocaleString()}</p>
          </div>
          <div class="cart-item-actions">
            <div class="cart-item-qty">
              <button onclick="updateCartQuantity('${esc(item.id)}', '${esc(item.size || '')}', ${item.quantity - 1}, ${Number(item.cart_id || 0)})">−</button>
              <span>${item.quantity}</span>
              <button onclick="updateCartQuantity('${esc(item.id)}', '${esc(item.size || '')}', ${item.quantity + 1}, ${Number(item.cart_id || 0)})">+</button>
            </div>
            <button class="remove-item" onclick="removeFromCart('${esc(item.id)}', '${esc(item.size || '')}', ${Number(item.cart_id || 0)})">Remove</button>
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
          document.getElementById('cart-subtotal').textContent = `₹${subtotal.toLocaleString()}`;
          document.getElementById('cart-shipping').textContent = shipping > 0 ? `₹${shipping}` : 'Free';
          document.getElementById('cart-tax').textContent = `₹${tax.toLocaleString()}`;
          document.getElementById('cart-total').textContent = `₹${total.toLocaleString()}`;
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

  // Background price refresh — only needed for guest/localStorage carts.
  // Logged-in cart items already have fresh data from list.php (they have cart_id).
  const needsFetch = cart.some(item => !item.cart_id);
  if (!needsFetch) return;

  // Only request product-type IDs; bundle items keep their API-provided data.
  const productIds = [...new Set(
    cart.filter(item => !item.item_type || item.item_type === 'product')
        .map(item => parseInt(item.id))
  )];
  if (productIds.length === 0) return;

  try {
      const response = await fetch('api/product/get_details.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ids: productIds })
      });
      const result = await response.json();

      if (result.status === 'success') {
          globalProductDetailsCache = { ...globalProductDetailsCache, ...result.data };
          const validProductIds = new Set(Object.keys(result.data || {}).map(String));
          const beforeLen = cart.length;
          // Only remove product-type rows whose product no longer exists; keep bundles
          cart = cart.filter(item => {
              if (item.item_type === 'bundle') return true;
              return validProductIds.has(String(item.id));
          });
          if (cart.length !== beforeLen) {
              saveCart();
              updateCartCount();
          }
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
async function initCheckoutPageAsync() {
  const userSession = getUserSession();
  if (!userSession || !userSession.id) {
    if (userSession) clearUserSession();
    showToast('Please sign in with a valid account to proceed to checkout', 'error');
    window.location.href = 'signin.php?redirect=checkout.php';
    return;
  }

  const checkoutItemsContainer = document.getElementById('checkout-items');
  if (checkoutItemsContainer) {
    checkoutItemsContainer.innerHTML = '<p>Loading your cart…</p>';
  }

  await fetchCartFromAPI();

  if (cart.length === 0) {
    showToast('Your cart is empty!', 'error');
    window.location.href = 'cart.php';
    return;
  }

  loadCheckoutPage();
  loadRazorpayScript().catch(() => {});
  initCheckoutAddresses();

  document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const cardDetails = document.getElementById('card-details');
      if (this.value === 'card' || this.value === 'upi') {
        cardDetails.style.display = 'block';
      } else {
        cardDetails.style.display = 'none';
      }
    });
  });
}

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
      <div class="checkout-item-price">₹${(item.price * item.quantity).toLocaleString()}</div>
    </div>
  `).join('');

  const hasDigital = cart.some(cartItemIsDigital);
  const hasPhysical = cart.some(cartItemIsPhysical);
  const onlyDigital = hasDigital && !hasPhysical;
  const onlyPhysical = hasPhysical && !hasDigital;

  // Update totals
  const subtotal = getCartTotal();
  const shipping = (subtotal > 0 && hasPhysical) ? 50 : 0;
  const tax = Math.round(subtotal * 0.18);
  const total = subtotal + shipping + tax;

  document.getElementById('checkout-subtotal').textContent = `₹${subtotal.toLocaleString()}`;
  document.getElementById('checkout-shipping').textContent = shipping > 0 ? `₹${shipping}` : 'Free';
  document.getElementById('checkout-tax').textContent = `₹${tax.toLocaleString()}`;
  document.getElementById('checkout-total').textContent = `₹${total.toLocaleString()}`;

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
  syncCheckoutRequiredFields({ onlyDigital });

  const paymentBlock = document.getElementById('checkout-payment-block');
  const freeNotice = document.getElementById('free-checkout-notice');
  const cardDetails = document.getElementById('card-details');
  const digitalNotice = document.getElementById('digital-delivery-notice');
  const orderText = document.getElementById('order-text');
  const codOption = document.getElementById('cod-option');
  const codRadio = document.getElementById('cod-radio');
  const codMessage = document.getElementById('cod-disabled-message');
  const cardRadio = document.querySelector('#checkout-form input[name="paymentMethod"][value="card"]');
  const isFree = isFreeCheckout();

  document.querySelectorAll('#checkout-form input[name="paymentMethod"]').forEach((radio) => {
    radio.required = !isFree;
    radio.disabled = isFree;
  });

  if (isFree) {
    if (paymentBlock) paymentBlock.style.display = 'none';
    if (freeNotice) freeNotice.style.display = '';
    if (cardDetails) cardDetails.style.display = 'none';
    if (digitalNotice) {
      digitalNotice.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:8px;">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="7 10 12 15 17 10"></polyline>
          <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        Your free download will be available immediately in <strong>My Orders</strong> after checkout.`;
    }
    if (orderText) orderText.textContent = 'Get Free Download';
    // Pre-fill email from session for frictionless free checkout
    const _freeSession = getUserSession();
    const _freeEmailField = document.getElementById('email');
    if (_freeSession && _freeSession.email && _freeEmailField && !_freeEmailField.value) {
      _freeEmailField.value = _freeSession.email;
    }
  } else {
    if (paymentBlock) paymentBlock.style.display = '';
    if (freeNotice) freeNotice.style.display = 'none';
    if (cardDetails) cardDetails.style.display = '';
    document.querySelectorAll('#checkout-form input[name="paymentMethod"]').forEach((radio) => {
      radio.disabled = false;
    });
    if (digitalNotice) {
      digitalNotice.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:8px;">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="7 10 12 15 17 10"></polyline>
          <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        Your digital products will be available for download immediately after payment in <strong>My Orders</strong>.`;
    }
    if (orderText) orderText.textContent = 'Place Order';
    if (onlyPhysical) {
      if (codRadio) codRadio.disabled = false;
      if (codMessage) codMessage.style.display = 'none';
      if (codOption) {
        codOption.style.opacity = '1';
        codOption.style.cursor = 'pointer';
        codOption.style.pointerEvents = 'auto';
      }
    } else {
      if (codRadio) {
        codRadio.disabled = true;
        codRadio.checked = false;
      }
      if (codMessage) codMessage.style.display = 'block';
      if (codOption) {
        codOption.style.opacity = '0.5';
        codOption.style.cursor = 'not-allowed';
        codOption.style.pointerEvents = 'none';
      }
      if (cardRadio) cardRadio.checked = true;
    }
  }
}

function syncCheckoutRequiredFields(options = {}) {
  const form = document.getElementById('checkout-form');
  if (!form) return;

  const hasPhysicalInCart = cart.some(cartItemIsPhysical);
  const onlyDigital = typeof options.onlyDigital === 'boolean' ? options.onlyDigital : !hasPhysicalInCart;
  const usingSavedAddress = isUsingSavedAddress();

  const addressFields = ['address', 'city', 'state', 'zip', 'country'];
  addressFields.forEach((name) => {
    const field = form.elements[name];
    if (!field) return;
    if (usingSavedAddress || onlyDigital) {
      field.removeAttribute('required');
    } else {
      field.setAttribute('required', 'required');
    }
  });

  const identityFields = ['firstName', 'lastName', 'phone'];
  identityFields.forEach((name) => {
    const field = form.elements[name];
    if (!field) return;
    if (usingSavedAddress) {
      field.removeAttribute('required');
    } else {
      field.setAttribute('required', 'required');
    }
  });

  const emailField = form.elements['email'];
  if (emailField) {
    if (usingSavedAddress) {
      emailField.removeAttribute('required');
    } else {
      emailField.setAttribute('required', 'required');
    }
  }

  const emailSavedField = form.elements['emailSaved'];
  if (emailSavedField) {
    if (usingSavedAddress) {
      emailSavedField.setAttribute('required', 'required');
    } else {
      emailSavedField.removeAttribute('required');
    }
  }
}

// Toast notifications — glassmorphic stack (success | error | info | warning)
const UXP_TOAST_ICONS = {
  success: 'ph-check-circle',
  error: 'ph-warning-circle',
  info: 'ph-info',
  warning: 'ph-warning',
};
const UXP_TOAST_DURATION = { success: 4000, error: 5000, info: 4000, warning: 4500 };
const UXP_TOAST_MAX = 4;

function getToastHost() {
  let host = document.getElementById('uxp-toast-host');
  if (!host) {
    host = document.createElement('div');
    host.id = 'uxp-toast-host';
    host.setAttribute('aria-live', 'polite');
    host.setAttribute('aria-relevant', 'additions');
    document.body.appendChild(host);
  }
  return host;
}

function dismissToast(toast) {
  if (!toast || toast.classList.contains('uxp-toast-out')) return;
  toast.classList.add('uxp-toast-out');
  const remove = () => toast.remove();
  toast.addEventListener('animationend', remove, { once: true });
  setTimeout(remove, 400);
}

function showToast(message, type = 'success') {
  const normalized = ['success', 'error', 'info', 'warning'].includes(type) ? type : 'info';
  const host = getToastHost();
  const duration = UXP_TOAST_DURATION[normalized] || 4000;

  while (host.children.length >= UXP_TOAST_MAX) {
    dismissToast(host.firstElementChild);
  }

  const toast = document.createElement('div');
  toast.className = `uxp-toast uxp-toast--${normalized}`;
  toast.setAttribute('role', normalized === 'error' ? 'alert' : 'status');
  toast.innerHTML = `
    <span class="uxp-toast-icon-wrap" aria-hidden="true"><i class="ph ${UXP_TOAST_ICONS[normalized]}"></i></span>
    <div class="uxp-toast-body">
      <div class="uxp-toast-title">${esc(message)}</div>
    </div>
    <button type="button" class="uxp-toast-close" aria-label="Dismiss notification">
      <i class="ph ph-x" aria-hidden="true"></i>
    </button>
    <span class="uxp-toast-progress" aria-hidden="true"></span>`;

  host.appendChild(toast);

  const closeBtn = toast.querySelector('.uxp-toast-close');
  closeBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    dismissToast(toast);
  });

  toast.addEventListener('click', () => dismissToast(toast));

  let remaining = duration;
  let dismissAt = Date.now() + duration;
  let timerId = null;
  const progressBar = toast.querySelector('.uxp-toast-progress');

  function scheduleDismiss() {
    clearTimeout(timerId);
    timerId = setTimeout(() => dismissToast(toast), remaining);
  }

  scheduleDismiss();

  toast.addEventListener('mouseenter', () => {
    clearTimeout(timerId);
    remaining = Math.max(0, dismissAt - Date.now());
    if (progressBar) progressBar.style.animationPlayState = 'paused';
  });

  toast.addEventListener('mouseleave', () => {
    dismissAt = Date.now() + remaining;
    if (progressBar) progressBar.style.animationPlayState = 'running';
    scheduleDismiss();
  });
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
    initCheckoutPageAsync();
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
  // Strip the server-rendered hidden class so updateUserMenu sees logged-out DOM state
  document.querySelectorAll('.nav-cta[href="signin.php"], .header-signin-cta').forEach((btn) => {
    btn.classList.remove('uxp-sr-hide');
    btn.style.removeProperty('display');
  });
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

function isSignInCtaHidden(btn) {
  if (btn.classList.contains('uxp-sr-hide')) return true;
  if (btn.style.display === 'none') return true;
  const computed = window.getComputedStyle(btn);
  return computed && computed.display === 'none';
}

function setSignInCtaVisible(visible) {
  document.querySelectorAll('.nav-cta[href="signin.php"], .header-signin-cta').forEach((btn) => {
    btn.classList.toggle('uxp-sr-hide', !visible);
    btn.style.removeProperty('display');
  });
}

function setProfileMenuVisible(visible) {
  const isMobileHeader = window.matchMedia('(max-width: 900px)').matches;
  document.querySelectorAll('.nav-user, .user-menu.profile-menu').forEach((menu) => {
    menu.style.display = visible && !isMobileHeader ? 'flex' : 'none';
  });
}

function updateUserMenu() {
  const userSession = getUserSession();
  const userMenus = document.querySelectorAll('.nav-user, .user-menu.profile-menu');
  const signInButtons = document.querySelectorAll('.nav-cta[href="signin.php"], .header-signin-cta');
  const signInHidden = Array.from(signInButtons).some(isSignInCtaHidden);
  const serverRenderedAuth = userMenus.length > 0 && signInHidden;
  const signedIn = !!(userSession && (userSession.id || userSession.email || userSession.name)) || serverRenderedAuth;

  if (signedIn) {
    setProfileMenuVisible(true);
    userMenus.forEach(menu => {
      const userName = menu.querySelector('.user-name');
      const userAvatar = menu.querySelector('.user-avatar');
      const displayName = userSession ? getUserFirstName(userSession) : (userName ? userName.textContent.trim() : 'Profile');

      if (userName && displayName) {
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

    if (!userSession && serverRenderedAuth) {
      const userNameEl = userMenus[0]?.querySelector('.user-name');
      const userName = userNameEl ? userNameEl.textContent.trim() : '';
      if (userName && userName !== 'User' && userName !== 'Profile') {
        localStorage.setItem('userSession', JSON.stringify({
          name: userName,
          firstName: userName.split(' ')[0],
          loginTime: new Date().toISOString(),
          source: 'server-hydrated'
        }));
      }
    }

    setSignInCtaVisible(false);
  } else {
    setProfileMenuVisible(false);
    setSignInCtaVisible(true);
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
                   field.closest('.auth-premium-field')?.querySelector('.field-error-modern') ||
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
                   field.closest('.auth-premium-field')?.querySelector('.field-error-modern') ||
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
  if (errorDiv) errorDiv.style.display = 'none';
  if (successDiv) successDiv.style.display = 'none';
  
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
  } else if (password.length < 8) {
    showFieldError(form.password, 'Password must be at least 8 characters');
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
  
  // Call API — force-refresh CSRF token to defeat stale meta tags (e.g. after
  // session_regenerate_id in another tab).
  getCsrfTokenAsync(true).then((csrfToken) => fetch('api/auth/login.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({
      email: email,
      password: password,
      csrf_token: csrfToken
    })
  }))
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      const user = data.data?.user;
      if (!user) {
        throw new Error('Invalid login response');
      }
      if (data.data?.csrf_token) {
        setCsrfToken(data.data.csrf_token);
      }
      
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
      
      // Merge local cart if exists
      const localCart = JSON.parse(localStorage.getItem('cart')) || [];
      if (localCart.length > 0) {
          secureFetch('api/cart/merge.php', {
              method: 'POST',
              body: JSON.stringify({ cart: localCart })
          })
          .then(res => res.json())
          .then(data => {
              // Clear local cart after merge
              localStorage.removeItem('cart');
              cart = [];
              
              if (successDiv) {
                successDiv.textContent = 'Sign in successful! Redirecting...';
                successDiv.style.display = 'block';
              }
              handleSignInRedirect();
          })
          .catch(err => {
              console.error('Merge error:', err);
              // Proceed anyway
              if (successDiv) {
                successDiv.textContent = 'Sign in successful! Redirecting...';
                successDiv.style.display = 'block';
              }
              handleSignInRedirect();
          });
      } else {
          if (successDiv) {
            successDiv.textContent = 'Sign in successful! Redirecting...';
            successDiv.style.display = 'block';
          }
          handleSignInRedirect();
      }
    } else {
      const msg = data.message || 'Invalid email or password';
      if (errorDiv) {
        errorDiv.textContent = msg;
        errorDiv.style.display = 'block';
      } else {
        showToast(msg, 'error');
      }
      btn.disabled = false;
      btnText.style.display = 'inline';
      btnLoader.style.display = 'none';
    }
  })
  .catch(error => {
    console.error('Login error:', error);
    const msg = 'An error occurred. Please try again.';
    if (errorDiv) {
      errorDiv.textContent = msg;
      errorDiv.style.display = 'block';
    } else {
      showToast(msg, 'error');
    }
    btn.disabled = false;
    btnText.style.display = 'inline';
    btnLoader.style.display = 'none';
  });
}

// Sign out handler
async function handleSignOut() {
  try {
    const csrfToken = await getCsrfTokenAsync();
    await fetch('api/auth/logout.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify({ csrf_token: csrfToken })
    });
  } catch (err) {
    console.error('Logout error:', err);
  } finally {
    clearUserSession();
    showToast('Signed out successfully', 'success');
    setTimeout(() => {
      window.location.href = 'index.php';
    }, 600);
  }
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
    const res = await secureFetch('api/contact/send.php', {
      method: 'POST',
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
    window.location.href = 'signin.php?redirect=checkout.php';
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

  const hasPhysicalInCart = cart.some(cartItemIsPhysical);
  const onlyDigitalInCart = !hasPhysicalInCart;

  // Totals (needed for free-order validation below)
  const subtotal = getCartTotal();
  const shippingCost = (subtotal > 0 && !onlyDigitalInCart) ? 50 : 0;
  const tax = Math.round(subtotal * 0.18);
  const total = subtotal + shippingCost + tax;

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
  } else if (isFreeCheckout() && onlyDigitalInCart) {
    // Free digital order — only email required, no address/phone needed
    if (!email) {
      if (form.email) showFieldError(form.email, 'Email is required');
      isValid = false;
    } else if (!validateEmail(email)) {
      if (form.email) showFieldError(form.email, 'Please enter a valid email address');
      isValid = false;
    } else if (form.email) clearFieldError(form.email);
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
      item_type: item.item_type || 'product',
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
  
  // Note: createDraftOrder uses secureFetch — it injects a fresh CSRF token
  // (with auto-retry on 403) on every send, so no upfront token needed here.

  function resetOrderBtn() {
    btn.disabled = false;
    btnText.style.display = 'inline';
    btnLoader.style.display = 'none';
  }

  function handleOrderSuccess(orderData, serverData, verifyData) {
    cart = [];
    saveCart();
    updateCartCount();
    const confirmId = serverData.data.orderId || '';
    const redirect = serverData.data.redirect
      || ('order-confirmation.php' + (confirmId ? '?order_id=' + encodeURIComponent(confirmId) : ''));
    window.location.href = redirect;
  }

  function createDraftOrder() {
    return secureFetch('api/order/create.php', {
      method: 'POST',
      body: JSON.stringify(orderPayload)
    }).then(res => {
      if (res.status === 401) {
        clearUserSession();
        showToast('Session expired. Please sign in again.', 'error');
        window.location.href = 'signin.php?redirect=checkout.php';
        throw new Error('Session expired');
      }
      return res.json();
    }).then(data => {
      if (data.status !== 'success') throw new Error(data.message || 'Order creation failed');
      return data;
    });
  }

  if (isFreeCheckout()) {
    orderPayload.paymentMethod = 'free';
    createDraftOrder()
      .then(data => handleOrderSuccess(orderPayload, data))
      .catch(err => {
        console.error('Order error:', err);
        showToast('Failed to place order: ' + err.message, 'error');
        resetOrderBtn();
      });
    return;
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

  // ── Test payment flow (dev only) ───────────────────────────────────────────
  if (paymentMethod === 'test') {
    createDraftOrder()
      .then(serverData => {
        const orderId = serverData.data.orderId;
        return secureFetch('api/payment/test-pay.php', {
          method: 'POST',
          body: JSON.stringify({ order_id: orderId })
        })
          .then(r => r.json())
          .then(testRes => {
            if (testRes.status !== 'success') throw new Error(testRes.message || 'Test payment failed');
            handleOrderSuccess(orderPayload, serverData, testRes);
          });
      })
      .catch(err => {
        console.error('Test payment error:', err);
        showToast('Test payment failed: ' + err.message, 'error');
        resetOrderBtn();
      });
    return;
  }

  // ── Razorpay (card / UPI) flow ─────────────────────────────────────────────
  orderPayload.paymentMethod = 'razorpay';

  loadRazorpayScript()
    .then(() => createDraftOrder())
    .then(serverData => {
      const orderId = serverData.data.orderId;
      return secureFetch('api/payment/razorpay-create-order.php', {
        method: 'POST',
        body: JSON.stringify({ order_id: orderId })
      })
        .then(r => r.json())
        .then(rzpRes => {
          if (rzpRes.status !== 'success') throw new Error(rzpRes.message || 'Could not initiate payment');
          return { serverData, rzpData: rzpRes.data };
        });
    })
    .then(({ serverData, rzpData }) => {
      if (!window.Razorpay) throw new Error('Payment gateway not loaded. Please refresh and try again.');
      return new Promise((resolve, reject) => {
        const options = {
          key:      rzpData.key_id,
          amount:   rzpData.amount_paise,
          currency: rzpData.currency || 'INR',
          order_id: rzpData.razorpay_order_id,
          name:     'UX Pacific Shop',
          description: 'Order ' + serverData.data.orderNumber,
          handler: function(response) {
            if (btnLoader) btnLoader.textContent = 'Confirming payment...';
            secureFetch('api/payment/razorpay-verify.php', {
              method: 'POST',
              body: JSON.stringify({
                razorpay_order_id:   response.razorpay_order_id,
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_signature:  response.razorpay_signature,
                order_id:            serverData.data.orderId
              })
            })
              .then(r => r.json())
              .then(verifyData => {
                if (verifyData.status !== 'success') throw new Error(verifyData.message || 'Payment verification failed');
                resolve({ serverData, verifyData });
              })
              .catch(reject);
          },
          modal: {
            ondismiss: function() {
              reject(new Error('__dismissed__'));
            }
          },
          prefill: {
            email:   orderPayload.shipping?.email   || '',
            contact: orderPayload.shipping?.phone   || ''
          },
          theme: { color: '#6d3dff' }
        };
        new window.Razorpay(options).open();
      });
    })
    .then(({ serverData, verifyData }) => {
      handleOrderSuccess(orderPayload, serverData, verifyData);
    })
    .catch(err => {
      if (err.message === '__dismissed__') {
        showToast('Payment cancelled. Your order is saved — you can retry from the orders page.', 'error');
      } else {
        console.error(err);
        showToast(err.message || 'Payment failed. Please try again.', 'error');
      }
      resetOrderBtn();
    });
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
  } else if (password.length < 8) {
    const errorSpan = form.password.parentElement?.querySelector('.field-error-modern') || 
                     form.password.parentElement?.querySelector('.field-error');
    if (errorSpan) {
      errorSpan.textContent = 'Password must be at least 8 characters';
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
  
  // Call actual API — force-refresh CSRF token (stale meta-tag tolerant).
  getCsrfTokenAsync(true).then((csrfToken) => fetch('api/auth/signup.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({
      firstName: firstName,
      lastName: lastName,
      fullName: form.fullName ? form.fullName.value.trim() : null,
      email: email,
      phone: phone,
      password: password,
      csrf_token: csrfToken
    })
  }))
  .then(async (response) => {
    const text = await response.text();
    try {
      return JSON.parse(text);
    } catch (_) {
      throw new Error('Server error. Please try again.');
    }
  })
  .then(data => {
    if (data.status === 'success') {
      const user = data.data?.user;
      if (data.data?.csrf_token) {
        setCsrfToken(data.data.csrf_token);
      }
      
      if (user && data.data?.auto_login) {
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
        
        if (successDiv) {
          successDiv.textContent = 'Account created! Signing you in...';
          successDiv.style.display = 'block';
        }
        setTimeout(() => {
          const redirectParam = new URLSearchParams(window.location.search).get('redirect');
          window.location.href = getSafeRedirect(redirectParam);
        }, 1200);
      } else {
        if (successDiv) {
          successDiv.textContent = 'Account created successfully! Redirecting to sign in...';
          successDiv.style.display = 'block';
        }
        setTimeout(() => {
          const redirectParam = new URLSearchParams(window.location.search).get('redirect');
          const signinUrl = 'signin.php?message=' + encodeURIComponent('Account created! Please sign in.')
            + (redirectParam ? '&redirect=' + encodeURIComponent(redirectParam) : '');
          window.location.href = signinUrl;
        }, 1500);
      }
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
    const csrfToken = await getCsrfTokenAsync(true);
    const res = await fetch('api/auth/forgot-password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
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

// Make functions globally available
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.updateCartQuantity = updateCartQuantity;
window.handleSignIn = handleSignIn;
window.handleSignUp = handleSignUp;
window.handleForgotPassword = handleForgotPassword;
window.handleContactSubmit = handleContactSubmit;
window.handleCheckout = handleCheckout;

// ==================== REORDER ====================

window.handleReorder = function(orderId, items) {
  if (!Array.isArray(items) || items.length === 0) {
    showToast('No items to reorder.', 'error');
    return;
  }
  const adds = items.map(item => {
    const isBundle = (item.item_type === 'bundle');
    const itemId   = isBundle ? (item.bundle_id || item.product_id) : (item.product_id || item.id);
    if (!itemId) return Promise.resolve();
    return addToCart(
      Number(itemId),
      item.size || null,
      item.quantity || 1,
      {
        name: item.name || 'Product',
        price: parseFloat(item.price) || 0,
        image: item.image || 'img/sticker.webp',
        item_type: isBundle ? 'bundle' : 'product'
      },
      'digital'
    ).catch(() => {});
  });
  Promise.allSettled(adds).then(() => {
    showToast('Items added to cart!', 'success');
    setTimeout(() => { window.location.href = 'cart.php'; }, 900);
  });
};

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
    syncCheckoutRequiredFields();
    return;
  }

  // Has saved addresses - show selector
  savedSection.style.display = 'block';
  if (newAddressSection) newAddressSection.style.display = 'none';
  if (emailOnlySection) emailOnlySection.style.display = 'block';
  if (saveAddressRow) saveAddressRow.style.display = 'none';
  syncCheckoutRequiredFields();

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
      syncCheckoutRequiredFields();
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
    syncCheckoutRequiredFields();
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
  syncCheckoutRequiredFields();
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

  window.openSiteSearchModal = openSearchModal;

  function closeSearchModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    activeIndex = -1;
  }

  function searchTypeLabel(type) {
    if (type === 'bundle') return 'Bundle';
    if (type === 'freebie') return 'Freebie';
    return 'Product';
  }

  function openSearchResult(itemType, itemId, triggerEl) {
    const type = itemType === 'bundle' ? 'bundle' : 'product';
    if (typeof openMarketplaceModal === 'function') {
      openMarketplaceModal(type, itemId, triggerEl);
      return;
    }
    if (type === 'bundle') {
      window.location.href = 'bundles.php';
      return;
    }
    window.location.href = `search.php?q=${encodeURIComponent(input.value.trim() || '')}`;
  }

  function renderSearchLoading() {
    results.classList.add('is-loading');
    results.innerHTML = `
      <div class="search-modal-skeleton" aria-hidden="true">
        <div class="search-modal-skeleton-line"></div>
        <div class="search-modal-skeleton-line"></div>
        <div class="search-modal-skeleton-line"></div>
      </div>`;
  }

  async function fetchSearchResults(query) {
    renderSearchLoading();
    try {
      const response = await fetch(`api/product/search.php?q=${encodeURIComponent(query)}&limit=8`, {
        credentials: 'same-origin',
      });
      const text = await response.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch {
        throw new Error('Search is temporarily unavailable.');
      }
      if (!response.ok || data.status === 'error') {
        throw new Error(data.message || 'Search failed.');
      }
      results.classList.remove('is-loading');
      const items = data?.data?.items || [];
      const hasQuery = query.trim().length >= 2;
      const sectionLabel = hasQuery ? 'Results' : 'Trending searches';
      const trends = `
        <p class="search-modal-section-label">${sectionLabel}</p>
        <div class="search-modal-trends">
          ${['UI Kit', 'Mockups', 'Dashboard', 'Icons', 'Landing Page'].map((term) => `<button type="button" class="search-trend-pill" data-trend="${term}">${term}</button>`).join('')}
        </div>
        ${hasQuery ? `<div class="search-result-count">${items.length} result${items.length === 1 ? '' : 's'} found</div>` : ''}
      `;
      const listHtml = items.length
        ? items.map((item, index) => {
            const itemType = item.type || 'product';
            const priceLabel = isCatalogItemFree(item) ? 'Free' : formatMoney(item.price);
            return `
              <button type="button" class="search-modal-result" data-index="${index}" data-type="${esc(itemType)}" data-id="${esc(item.id)}">
                <img src="${esc(item.image)}" alt="" loading="lazy" onerror="this.src='img/poster.webp'">
                <span><strong>${esc(item.name)}</strong><em>${esc(searchTypeLabel(itemType))} · ${esc(item.category || 'General')}</em></span>
                <b>${priceLabel}</b>
              </button>`;
          }).join('')
        : `<div class="search-modal-empty">${hasQuery ? 'No matching products or bundles found.' : 'Start typing to search the catalog.'}</div>`;
      const viewAllQuery = query.trim();
      const viewAllHref = viewAllQuery ? `search.php?q=${encodeURIComponent(viewAllQuery)}` : 'shopAll.php';
      const viewAllLabel = viewAllQuery ? `View all results for “${esc(viewAllQuery)}” →` : 'Browse all products →';
      results.innerHTML = `${trends}${listHtml}<button type="button" class="search-modal-view-all" data-search-view-all="${esc(viewAllHref)}">${viewAllLabel}</button>`;
    } catch (error) {
      results.classList.remove('is-loading');
      results.innerHTML = `<div class="search-modal-empty">${esc(error.message || 'Could not load search results.')}</div>`;
    }
  }

  document.addEventListener('keydown', (event) => {
    if ((event.key === '/' || (event.ctrlKey && event.key === 'k')) && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
      event.preventDefault();
      openSearchModal('');
    }
  });

  input.addEventListener('input', () => {
    clearTimeout(timer);
    activeIndex = -1;
    const query = input.value.trim();
    if (query.length === 0) {
      fetchSearchResults('');
      return;
    }
    if (query.length === 1) {
      results.classList.remove('is-loading');
      results.innerHTML = '<div class="search-modal-empty">Keep typing — search starts at 2 characters.</div>';
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
    const viewAll = event.target.closest('[data-search-view-all]');
    if (viewAll) {
      closeSearchModal();
      window.location.href = viewAll.dataset.searchViewAll || 'shopAll.php';
      return;
    }
    const trend = event.target.closest('.search-trend-pill');
    if (trend) {
      input.value = trend.dataset.trend || '';
      fetchSearchResults(input.value);
      return;
    }
    const item = event.target.closest('.search-modal-result');
    if (!item) return;
    closeSearchModal();
    openSearchResult(item.dataset.type || 'product', item.dataset.id, item);
  });

  submit?.addEventListener('click', () => {
    const query = input.value.trim();
    if (query.length >= 2) {
      const first = results.querySelector('.search-modal-result');
      if (first) {
        first.click();
        return;
      }
      window.location.href = `search.php?q=${encodeURIComponent(query)}`;
      return;
    }
    input.focus();
  });

  modal.querySelectorAll('[data-search-close]').forEach((el) => {
    el.addEventListener('click', closeSearchModal);
  });

  document.querySelectorAll('.mobile-search-trigger').forEach((el) => {
    el.addEventListener('click', (event) => {
      event.preventDefault();
      document.body.classList.remove('mobile-menu-open');
      document.querySelector('.navbar')?.classList.remove('mobile-open');
      openSearchModal('');
    });
  });
}

function formatMoney(value) {
  return '₹' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
}

function isGalleryImageUrl(url) {
  const path = String(url || '').trim();
  if (!path) return false;
  if (/\.(php|html?|js|css|zip|rar|7z|pdf|fig|xd|sketch)$/i.test(path)) return false;
  if (/(^|\/)(storage\/private|digital_resources|api\/)/i.test(path)) return false;
  return /\.(jpe?g|png|webp|gif|avif|svg)(\?|$)/i.test(path);
}

function getMarketplaceGalleryImages(item) {
  const raw = [item?.image, ...(item?.additional_images || [])].filter(Boolean);
  const filtered = raw.filter(isGalleryImageUrl);
  const unique = [...new Set(filtered)];
  return unique.length ? unique : ['img/poster.webp'];
}

function renderCatalogPriceRow(item) {
  const free = isCatalogItemFree(item);
  if (free) {
    return `<div class="mp-price-row mp-price-row--free">
      <strong>${formatMoney(0)}</strong>
      <span class="mp-tag-pill mp-tag-pill--accent">Free</span>
    </div>`;
  }
  return `<div class="mp-price-row">
    <strong>${formatMoney(item.price)}</strong>
    ${item.old_price ? `<span class="mp-old-price">${formatMoney(item.old_price)}</span>` : ''}
    ${item.discount_percent ? `<em class="mp-discount">${item.discount_percent}% OFF</em>` : ''}
  </div>`;
}

const marketplaceDetailCache = new Map();
const marketplaceInflight = new Map();
let marketplaceOpenToken = 0;

function normalizeMarketplaceType(t) {
  const raw = String(t || 'product').toLowerCase();
  if (raw === 'bundle') return 'bundle';
  if (raw === 'freebie') return 'freebie';
  return 'product';
}

function getCardPreviewData(card) {
  if (!card) return null;
  const img = card.querySelector('.uxp-product-media img, .uxp-bundle-image img, .prod-img img, img');
  const isFree = card.dataset.isFree === '1' || card.dataset.isFree === 'true'
    || card.dataset.type === 'freebie' || Number(card.dataset.price || 0) <= 0;
  const rawType = card.dataset.itemType || card.dataset.type || 'product';
  return {
    id: card.dataset.productId || card.dataset.id,
    type: normalizeMarketplaceType(rawType),
    name: card.dataset.name || card.querySelector('h3')?.textContent?.trim() || 'Product',
    image: card.dataset.image || img?.getAttribute('src') || 'img/poster.webp',
    price: isFree ? 0 : Number(card.dataset.price || 0),
    is_free: isFree,
    old_price: card.dataset.oldPrice ? Number(card.dataset.oldPrice) : null,
    category: card.dataset.category || '',
    rating: card.dataset.rating || '4.5',
  };
}

function marketplaceCacheKey(type, id) {
  return `${type}:${id}`;
}

function fetchMarketplaceDetail(type, id) {
  const key = marketplaceCacheKey(type, id);
  if (marketplaceDetailCache.has(key)) {
    return Promise.resolve(marketplaceDetailCache.get(key));
  }
  if (marketplaceInflight.has(key)) {
    return marketplaceInflight.get(key);
  }
  const promise = fetch(`api/catalog/detail.php?type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}`, {
    credentials: 'same-origin',
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.status !== 'success') throw new Error(data.message || 'Unable to load item.');
      marketplaceDetailCache.set(key, data.data);
      return data.data;
    })
    .finally(() => marketplaceInflight.delete(key));
  marketplaceInflight.set(key, promise);
  return promise;
}

function prefetchMarketplaceDetail(type, id) {
  fetchMarketplaceDetail(type, id).catch(() => {});
}

function trackMarketplaceView(type, id) {
  fetch(`api/catalog/view.php?type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}`, {
    credentials: 'same-origin',
    keepalive: true,
  }).catch(() => {});
}

function ensureMarketplaceModal() {
  let modal = document.getElementById('marketplace-modal');
  if (modal && !modal.querySelector('.mp-modal-toolbar')) {
    modal.remove();
    modal = null;
  }
  if (modal) return modal;
  modal = document.createElement('div');
  modal.id = 'marketplace-modal';
  modal.className = 'marketplace-modal';
  modal.innerHTML = `
    <div class="marketplace-modal-backdrop" data-close-marketplace-modal></div>
    <section class="marketplace-modal-panel" role="dialog" aria-modal="true" aria-labelledby="marketplace-modal-title" aria-live="polite">
      <div class="mp-modal-toolbar">
        <button type="button" class="marketplace-modal-close mp-toolbar-btn" data-close-marketplace-modal aria-label="Close">×</button>
      </div>
      <div class="marketplace-modal-content is-loading">
        <div class="marketplace-modal-body"></div>
      </div>
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

function renderInstantModalShell(preview, type) {
  const itemType = preview.type || type;
  const typeLabel = itemType === 'bundle' ? 'Bundle' : (itemType === 'freebie' ? 'Freebie' : 'Product');
  const isFree = isCatalogItemFree(preview);
  const buyLabel = isFree ? 'Get Free' : 'Buy Now';
  return `
    <div class="mp-gallery-col mp-scroll-col" data-mp-scroll>
      <div class="mp-gallery-inner">
        <div class="mp-gallery-stage">
          <div class="mp-gallery-frame">
            <img id="mp-main-image" src="${esc(preview.image)}" alt="${esc(preview.name)}" decoding="async" onerror="this.src='img/poster.webp'">
          </div>
        </div>
      </div>
    </div>
    <div class="mp-details-scroll mp-scroll-col" data-mp-scroll>
      <div class="mp-details is-hydrating">
        <div class="mp-tags">
          <span class="mp-tag-pill">${esc(preview.category || typeLabel)}</span>
          ${isFree ? '<span class="mp-tag-pill mp-tag-pill--accent">Free</span>' : ''}
        </div>
        <h2 id="marketplace-modal-title">${esc(preview.name)}</h2>
        <div class="mp-rating-row mp-rating-row--loading">
          <span class="mp-rating-text">★ ${esc(preview.rating || '4.5')}</span>
        </div>
        <div class="mp-price-row">
          <strong>${formatMoney(isFree ? 0 : preview.price)}</strong>
        </div>
        <div class="mp-action-stack mp-action-stack--instant">
          <button type="button" class="mp-btn-cart" onclick="addMarketplaceItemToCart('${esc(itemType)}', '${esc(preview.id)}')">Add to Cart</button>
          <button type="button" class="mp-btn-buy" onclick="mpModalBuyNow('${esc(itemType)}', '${esc(preview.id)}', ${isFree ? 'true' : 'false'})">${buyLabel}</button>
        </div>
        <div class="mp-shimmer-block">
          <div class="mp-shimmer-line"></div>
          <div class="mp-shimmer-line short"></div>
          <div class="mp-shimmer-line"></div>
        </div>
      </div>
    </div>`;
}

function mpProductShowsSizes(item) {
  const cat = String(item.category || '').toLowerCase();
  const physical = item.available_type === 'physical' || item.available_type === 'both';
  if (!physical) return false;
  return /t-?shirt|hoodie|apparel|merch|tee/.test(cat);
}

function mpProductTypeLabel(item, itemType) {
  if (itemType === 'bundle') return 'Bundle';
  const map = { physical: 'Physical Product', digital: 'Digital Product', both: 'Digital & Physical' };
  return map[item.available_type] || 'Digital Product';
}

function renderMpStarRating(rating, reviewCount) {
  const value = Math.min(5, Math.max(0, parseFloat(rating) || 0));
  const full = Math.round(value);
  let stars = '';
  for (let i = 1; i <= 5; i += 1) {
    stars += `<span class="mp-star${i <= full ? ' is-filled' : ''}" aria-hidden="true">★</span>`;
  }
  const reviews = reviewCount > 0 ? ` (${reviewCount} reviews)` : '';
  return `<div class="mp-rating-row" aria-label="Rated ${value.toFixed(1)} out of 5">${stars}<span class="mp-rating-text">${value.toFixed(1)}${reviews}</span></div>`;
}

function renderMpSizeSelector(item) {
  if (!mpProductShowsSizes(item)) return '';
  const sizes = ['S', 'M', 'L', 'XL'];
  return `
    <div class="mp-field">
      <span class="mp-field-label">Select Size</span>
      <div class="mp-size-grid" role="group" aria-label="Select size">
        ${sizes.map((size) => `<button type="button" class="mp-size-btn${size === 'L' ? ' is-active' : ''}" data-mp-size="${size}">${size}</button>`).join('')}
      </div>
    </div>`;
}

function renderMpGalleryCol(uniqueGallery, itemName) {
  const images = uniqueGallery.length ? uniqueGallery : ['img/poster.webp'];
  const imageData = images.map((s) => esc(s)).join('||');
  const hasMany = images.length > 1;
  const dots = hasMany
    ? `<div class="mp-gallery-dots" data-mp-dots>${images.map((_, i) => `<span class="mp-dot${i === 0 ? ' is-active' : ''}"></span>`).join('')}</div>`
    : '';
  const thumbs = hasMany
    ? `<div class="mp-gallery-thumbs">${images.map((src, i) => `<button type="button" class="mp-thumb${i === 0 ? ' is-active' : ''}" data-gallery-index="${i}" aria-label="Image ${i + 1}"><img src="${esc(src)}" alt="" loading="lazy" decoding="async" onerror="this.src='img/poster.webp'"></button>`).join('')}</div>`
    : '';
  const nav = hasMany
    ? `<button type="button" class="mp-gallery-nav mp-gallery-prev" data-mp-prev aria-label="Previous image">‹</button>
       <button type="button" class="mp-gallery-nav mp-gallery-next" data-mp-next aria-label="Next image">›</button>`
    : '';
  const counter = hasMany ? `<span class="mp-gallery-counter" data-mp-counter>1/${images.length}</span>` : '';

  const zoomBtn = `<button type="button" class="mp-gallery-zoom-btn" data-mp-zoom title="View full size" aria-label="View full size">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
  </button>`;

  return `
    <div class="mp-gallery-col mp-scroll-col" data-mp-gallery data-images="${imageData}" data-mp-gallery-scroll>
      <div class="mp-gallery-inner">
        <div class="mp-gallery-stage">
          ${counter}
          ${nav}
          ${zoomBtn}
          <div class="mp-gallery-frame">
            <img id="mp-main-image" src="${esc(images[0])}" alt="${esc(itemName)}" decoding="async" onerror="this.src='img/poster.webp'">
          </div>
          ${dots}
        </div>
        ${thumbs}
      </div>
    </div>`;
}

function getGlobalExtraInfoRows(item) {
  return Array.isArray(item.global_extra_rows) ? item.global_extra_rows : [];
}

function buildProductInfoRowPairs(item, options = {}) {
  const { includeMeta = false, itemType = 'product' } = options;
  const typeLabels = {
    physical: 'Physical Product',
    digital: 'Digital Download',
    both: 'Digital & Physical',
  };
  const stockNum = Number(item.stock || 0);
  const availability = stockNum > 0 ? `In stock (${stockNum} available)` : 'Out of stock';
  const productType = typeLabels[item.available_type] || item.available_type || 'Digital Product';
  const rows = [];

  if (includeMeta) {
    rows.push(['Category', item.category || (itemType === 'bundle' ? 'Bundle' : '—')]);
    rows.push(['Product Type', productType]);
    rows.push(['Availability', availability]);
  }

  rows.push(
    ['Last Update', item.last_update],
    ['High Resolution', item.high_resolution],
    ['Compatible With', item.compatible_software],
    ['Software Version', item.software_version],
    ['Files Included', item.files_included],
    ['Column', item.grid_columns],
    ['Layout', item.layout_type],
    ['License', item.license_type],
  );

  getGlobalExtraInfoRows(item).forEach((row) => {
    if (row?.label && row?.value) rows.push([row.label, row.value]);
  });

  return rows.filter(([, value]) => value != null && String(value).trim() !== '' && String(value) !== '—');
}

function renderMpCompactProductInfo(item) {
  const sectionTitle = item.product_info_section_title || 'Product Information';
  const rows = buildProductInfoRowPairs(item);
  if (!rows.length && !item.global_info_note) return '';
  const noteHtml = item.global_info_note
    ? `<p class="mp-info-card-note">${esc(item.global_info_note)}</p>`
    : '';
  return `
    <section class="mp-info-card">
      <h4 class="mp-info-card-title">${esc(sectionTitle)}</h4>
      <dl class="mp-info-card-table">
        ${rows.map(([label, value]) => renderProductInfoRow(label, value)).join('')}
      </dl>
      ${noteHtml}
    </section>`;
}

function getMpModalQty() {
  return Math.max(1, Math.min(10, Number(document.getElementById('marketplace-qty-input')?.value || 1)));
}

function getMpModalSize() {
  const active = document.querySelector('.mp-size-btn.is-active');
  if (active?.dataset.mpSize) return active.dataset.mpSize;
  const grid = document.querySelector('.mp-size-grid');
  return grid ? null : 'One Size';
}

function parseProductTextList(text) {
  if (!text) return [];
  return String(text)
    .split(/\r?\n/)
    .map((line) => line.replace(/^[-•*]\s*/, '').trim())
    .filter(Boolean);
}

function renderProductInfoRow(label, value) {
  if (value === null || value === undefined || value === '' || value === '—') return '';
  return `
    <div class="mp-info-row">
      <dt class="mp-info-label">${esc(label)}</dt>
      <dd class="mp-info-value">${esc(value)}</dd>
    </div>`;
}

function renderProductInfoPanels(item, itemType, skipMainInfo = false, skipDescription = false) {
  const sectionTitle = item.product_info_section_title || 'Product Information';
  const infoRows = buildProductInfoRowPairs(item, { includeMeta: true, itemType })
    .map(([label, value]) => renderProductInfoRow(label, value))
    .filter(Boolean)
    .join('');
  const infoNoteHtml = item.global_info_note
    ? `<p class="mp-info-card-note">${esc(item.global_info_note)}</p>`
    : '';

  const whatsIncluded = parseProductTextList(item.whats_included);
  const fileSpecs = parseProductTextList(item.file_specification);

  const checkSvg = `<svg class="mp-bl-icon" viewBox="0 0 14 14" fill="none" aria-hidden="true"><circle cx="7" cy="7" r="6" fill="rgba(97,71,189,0.18)" stroke="rgba(124,107,196,0.5)" stroke-width="1"/><polyline points="3.5 7 6 9.5 10.5 4.5" stroke="#a78bfa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
  const fileSvg = `<svg class="mp-bl-icon" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M8.5 1H3.5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V5l-3-4z" fill="rgba(97,71,189,0.15)" stroke="rgba(124,107,196,0.5)" stroke-width="1"/><polyline points="8.5 1 8.5 5 11.5 5" stroke="rgba(124,107,196,0.5)" stroke-width="1"/></svg>`;

  const aboutIconSvg = `<svg class="mp-panel-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M11 2H5a1.5 1.5 0 0 0-1.5 1.5v9A1.5 1.5 0 0 0 5 14h6a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 11 2z" stroke="currentColor" stroke-width="1.25"/><path d="M5.5 5.5h5M5.5 8h5M5.5 10.5h3" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/></svg>`;
  const infoIconSvg = `<svg class="mp-panel-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.25"/><path d="M8 5.5v.5M8 8v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>`;
  const listIconSvg = `<svg class="mp-panel-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6.5 4.5h6M6.5 8h6M6.5 11.5h6" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/><circle cx="3.5" cy="4.5" r="1" fill="currentColor"/><circle cx="3.5" cy="8" r="1" fill="currentColor"/><circle cx="3.5" cy="11.5" r="1" fill="currentColor"/></svg>`;
  const fileIconSvg = `<svg class="mp-panel-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M9.5 1.5H4a1.5 1.5 0 0 0-1.5 1.5v10A1.5 1.5 0 0 0 4 14.5h8A1.5 1.5 0 0 0 13.5 13V5.5L9.5 1.5z" stroke="currentColor" stroke-width="1.25"/><polyline points="9.5 1.5 9.5 5.5 13.5 5.5" stroke="currentColor" stroke-width="1.25"/><path d="M5.5 8.5h5M5.5 11h3.5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/></svg>`;

  const includedHtml = whatsIncluded.length
    ? `<section class="mp-panel">
        <header class="mp-panel-head">${listIconSvg}<h3>What&rsquo;s Included</h3></header>
        <ul class="mp-bullet-list">${whatsIncluded.map((line) => `<li>${checkSvg}<span>${esc(line)}</span></li>`).join('')}</ul>
      </section>`
    : '';

  const fileSpecHtml = fileSpecs.length
    ? `<section class="mp-panel">
        <header class="mp-panel-head">${fileIconSvg}<h3>File Specifications</h3></header>
        <ul class="mp-bullet-list">${fileSpecs.map((line) => `<li>${fileSvg}<span>${esc(line)}</span></li>`).join('')}</ul>
      </section>`
    : '';

  return `
    ${(!skipDescription && item.description) ? `
    <section class="mp-panel mp-panel--about">
      <header class="mp-panel-head">${aboutIconSvg}<h3>About this product</h3></header>
      <p class="mp-about-text">${esc(item.description)}</p>
    </section>` : ''}
    ${(!skipMainInfo && infoRows) ? `
    <section class="mp-panel mp-panel--info">
      <header class="mp-panel-head">
        ${infoIconSvg}
        <div class="mp-panel-head-text">
          <h3>${esc(sectionTitle)}</h3>
          <span class="mp-panel-sub">Technical details &amp; specifications</span>
        </div>
      </header>
      <dl class="mp-info-table">${infoRows}</dl>
      ${infoNoteHtml}
    </section>` : ''}
    ${includedHtml}
    ${fileSpecHtml}`;
}

function renderBundleDetailModal(item, related) {
  const uniqueGallery = getMarketplaceGalleryImages(item);

  const price = parseFloat(item.price) || 0;
  const oldPrice = parseFloat(item.old_price) || 0;
  const savings = oldPrice > price ? Math.round(oldPrice - price) : 0;
  const reviewCount = Number(item.review_count || 0);

  // Merge whats_included text + included_items_parsed JSON into one list
  const textItems = parseProductTextList(item.whats_included);
  const jsonItems = (item.included_items_parsed || []).map((i) =>
    typeof i === 'string' ? i : String(i.label || i.name || '').trim()
  ).filter(Boolean);
  const allItems = textItems.length ? textItems : jsonItems;

  const previewMax = 5;
  const previewItems = allItems.slice(0, previewMax);
  const remaining = allItems.length - previewMax;

  const checkSvg = `<svg class="mp-bundle-check" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="1.5" opacity=".3"/><polyline points="5.5 10 8.5 13 14.5 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
  const pkgSvg = `<svg class="mp-bundle-pkg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>`;

  const includedBoxHtml = allItems.length ? `
    <div class="mp-bundle-box">
      <div class="mp-bundle-box-head">
        ${pkgSvg}
        <span>What&rsquo;s Included</span>
        <em class="mp-bundle-count">${allItems.length} item${allItems.length !== 1 ? 's' : ''}</em>
      </div>
      <ul class="mp-bundle-checklist">
        ${previewItems.map((it) => `<li>${checkSvg}<span>${esc(it)}</span></li>`).join('')}
        ${remaining > 0 ? `<li class="mp-bundle-checklist-more">${checkSvg}<span>+ ${remaining} more item${remaining > 1 ? 's' : ''} inside&hellip;</span></li>` : ''}
      </ul>
    </div>` : '';

  const allItemsGridHtml = allItems.length ? `
    <section class="mp-panel mp-bundle-items-panel">
      <header class="mp-panel-head">
        <h3>Everything Inside</h3>
        <span class="mp-panel-sub">${allItems.length} premium item${allItems.length !== 1 ? 's' : ''}</span>
      </header>
      <div class="mp-bundle-items-grid">
        ${allItems.map((it, i) => `
          <div class="mp-bundle-item-card">
            <span class="mp-bundle-item-num">${String(i + 1).padStart(2, '0')}</span>
            <span class="mp-bundle-item-label">${esc(it)}</span>
          </div>`).join('')}
      </div>
    </section>` : '';

  const bundleAboutIconSvg = `<svg class="mp-panel-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M11 2H5a1.5 1.5 0 0 0-1.5 1.5v9A1.5 1.5 0 0 0 5 14h6a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 11 2z" stroke="currentColor" stroke-width="1.25"/><path d="M5.5 5.5h5M5.5 8h5M5.5 10.5h3" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/></svg>`;
  const bundleInfoIconSvg = `<svg class="mp-panel-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.25"/><path d="M8 5.5v.5M8 8v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>`;
  const bundleFileIconSvg = `<svg class="mp-panel-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M9.5 1.5H4a1.5 1.5 0 0 0-1.5 1.5v10A1.5 1.5 0 0 0 4 14.5h8A1.5 1.5 0 0 0 13.5 13V5.5L9.5 1.5z" stroke="currentColor" stroke-width="1.25"/><polyline points="9.5 1.5 9.5 5.5 13.5 5.5" stroke="currentColor" stroke-width="1.25"/></svg>`;
  const bundleFileSvg = `<svg class="mp-bl-icon" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M8.5 1H3.5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V5l-3-4z" fill="rgba(97,71,189,0.15)" stroke="rgba(124,107,196,0.5)" stroke-width="1"/><polyline points="8.5 1 8.5 5 11.5 5" stroke="rgba(124,107,196,0.5)" stroke-width="1"/></svg>`;

  const fileSpecs = parseProductTextList(item.file_specification);
  const fileSpecHtml = fileSpecs.length ? `
    <section class="mp-panel">
      <header class="mp-panel-head">${bundleFileIconSvg}<h3>File Specifications</h3></header>
      <ul class="mp-bullet-list">${fileSpecs.map((l) => `<li>${bundleFileSvg}<span>${esc(l)}</span></li>`).join('')}</ul>
    </section>` : '';

  const infoRows = [
    renderProductInfoRow('Category', item.category),
    renderProductInfoRow('License', item.license_type),
    renderProductInfoRow('Compatibility', item.compatible_software),
    renderProductInfoRow('High Resolution', item.high_resolution),
    renderProductInfoRow('Format', item.files_included),
    renderProductInfoRow('Last Updated', item.last_update),
  ].filter(Boolean).join('');

  const relatedHtml = related.length ? `
    <section class="mp-related mp-related--compact">
      <div class="mp-related-head">
        <h3>More Bundles</h3>
        <div class="mp-related-nav">
          <button type="button" class="mp-related-arrow" data-related-prev aria-label="Scroll left">‹</button>
          <button type="button" class="mp-related-arrow" data-related-next aria-label="Scroll right">›</button>
        </div>
      </div>
      <div class="mp-related-track" data-related-track>
        ${related.slice(0, 6).map((rel) => `
          <button type="button" class="mp-related-item" onclick="openMarketplaceModal('bundle', '${esc(rel.id)}')">
            <div class="mp-related-item-media">
              <img src="${esc(rel.image)}" alt="" loading="lazy" decoding="async" onerror="this.src='img/poster.webp'">
              <span class="mp-related-item-cat">${esc(rel.category || 'Bundle')}</span>
            </div>
            <div class="mp-related-item-body">
              <span class="mp-related-item-name">${esc(rel.name)}</span>
              <div class="mp-related-item-meta">
                <strong>${formatMoney(rel.price)}</strong>
                ${rel.old_price ? `<em class="mp-related-item-old">${formatMoney(rel.old_price)}</em>` : ''}
              </div>
            </div>
          </button>`).join('')}
      </div>
    </section>` : '';

  return `
    ${renderMpGalleryCol(uniqueGallery, item.name)}
    <div class="mp-details-scroll mp-scroll-col" data-mp-scroll>
      <div class="mp-details">
        <div class="mp-tags">
          <span class="mp-tag-pill">${esc(item.category || 'Bundle')}</span>
          <span class="mp-tag-pill mp-tag-pill--muted">Digital Bundle</span>
          ${item.is_featured ? '<span class="mp-tag-pill mp-tag-pill--accent">Best Seller</span>' : ''}
        </div>

        <h2 id="marketplace-modal-title">${esc(item.name)}</h2>
        ${renderMpStarRating(item.rating || '4.5', reviewCount)}

        <div class="mp-price-row">
          <strong>${formatMoney(price)}</strong>
          ${oldPrice > 0 ? `<span class="mp-old-price">${formatMoney(oldPrice)}</span>` : ''}
          ${item.discount_percent ? `<em class="mp-discount">${item.discount_percent}% OFF</em>` : ''}
          ${savings > 0 ? `<span class="mp-bundle-savings">Save ${formatMoney(savings)}</span>` : ''}
        </div>

        ${(() => {
          const chips = detectFormatChips(item);
          const defaultBundleChips = [
            { cls: 'figma', label: 'Figma' },
            { cls: 'canva', label: 'Canva' },
            { cls: 'zip',   label: 'ZIP' },
            { cls: 'pdf',   label: 'PDF' },
          ];
          const showChips = chips.length ? chips : defaultBundleChips;
          return `<div class="mp-format-chips">${showChips.map(c => `<span class="mp-format-chip mp-format-chip--${c.cls}">${c.label}</span>`).join('')}</div>`;
        })()}

        <div class="mp-delivery-badge">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Instant digital delivery — all files after payment
        </div>

        ${includedBoxHtml}

        <div class="mp-field">
          <span class="mp-field-label">Quantity</span>
          <div class="mp-qty-stepper">
            <button type="button" class="mp-qty-btn" data-mp-qty-minus aria-label="Decrease quantity">−</button>
            <span class="mp-qty-value" id="mp-qty-display">1</span>
            <input type="hidden" id="marketplace-qty-input" value="1">
            <button type="button" class="mp-qty-btn" data-mp-qty-plus aria-label="Increase quantity">+</button>
          </div>
        </div>

        <div class="mp-action-stack">
          <button type="button" class="mp-btn-cart" onclick="addMarketplaceItemToCart('bundle', '${esc(item.id)}')">Add to Cart</button>
          <button type="button" class="mp-btn-buy" onclick="mpModalBuyNow('bundle', '${esc(item.id)}')">Buy Now</button>
        </div>

      </div>

      ${item.description ? `
      <section class="mp-panel mp-panel--about">
        <header class="mp-panel-head">${bundleAboutIconSvg}<h3>About this bundle</h3></header>
        <p class="mp-about-text">${esc(item.description)}</p>
      </section>` : ''}

      ${allItemsGridHtml}

      ${infoRows ? `
      <section class="mp-panel mp-panel--info">
        <header class="mp-panel-head">
          ${bundleInfoIconSvg}
          <div class="mp-panel-head-text">
            <h3>Bundle Details</h3>
            <span class="mp-panel-sub">License &amp; compatibility</span>
          </div>
        </header>
        <dl class="mp-info-table">${infoRows}</dl>
      </section>` : ''}

      ${fileSpecHtml}
      ${relatedHtml}
    </div>`;
}

function detectFormatChips(item) {
  const name = String(item.name || '').toLowerCase();
  const specs = String(item.file_specification || '').toLowerCase();
  const files = String(item.files_included || '').toLowerCase();
  const software = String(item.compatible_software || '').toLowerCase();
  const all = name + ' ' + specs + ' ' + files + ' ' + software;

  const chips = [];
  if (/figma/.test(all))  chips.push({ cls: 'figma',  label: 'Figma' });
  if (/canva/.test(all))  chips.push({ cls: 'canva',  label: 'Canva' });
  if (/\.zip|zip file|zip pack/.test(all)) chips.push({ cls: 'zip', label: 'ZIP' });
  if (/\.pdf|pdf guide|pdf file/.test(all)) chips.push({ cls: 'pdf', label: 'PDF' });
  if (/photoshop|\.psd/.test(all)) chips.push({ cls: 'psd', label: 'Photoshop' });
  if (/adobe xd|\.xd/.test(all))  chips.push({ cls: 'xd',  label: 'Adobe XD' });
  if (/sketch/.test(all)) chips.push({ cls: 'sketch', label: 'Sketch' });
  if (/\.png|png file|high.res/.test(all)) chips.push({ cls: 'png', label: 'PNG/JPG' });
  if (/instruction|guide|tutorial/.test(all)) chips.push({ cls: 'pdf', label: 'Instructions' });

  // Default for digital products
  if (!chips.length && (item.available_type === 'digital' || !item.available_type)) {
    chips.push({ cls: 'zip', label: 'Digital Files' });
  }
  return chips;
}

function renderFreebieDetailModal(item, related) {
  const uniqueGallery = getMarketplaceGalleryImages(item);
  const categoryLabel = esc(item.category || 'Freebie');
  const downloads = Number(item.download_count || item.sales_count || 0);
  const relatedHtml = related.length
    ? `<section class="mp-related mp-related--compact">
        <div class="mp-related-head"><h3>More free resources</h3></div>
        <div class="mp-related-track" data-related-track>
          ${related.slice(0, 8).map((rel) => `<button type="button" class="mp-related-item" onclick="openMarketplaceModal('freebie', '${esc(rel.id)}')">
            <div class="mp-related-item-media"><img src="${esc(rel.image)}" alt="" loading="lazy" onerror="this.src='img/poster.webp'"></div>
            <div class="mp-related-item-body"><span class="mp-related-item-name">${esc(rel.name)}</span><strong>₹0</strong></div>
          </button>`).join('')}
        </div>
      </section>`
    : '';

  return `
    ${renderMpGalleryCol(uniqueGallery, item.name)}
    <div class="mp-details-scroll mp-scroll-col" data-mp-scroll>
      <div class="mp-details">
        <div class="mp-tags">
          <span class="mp-tag-pill">${categoryLabel}</span>
          <span class="mp-tag-pill mp-tag-pill--accent">Free</span>
        </div>
        <h2 id="marketplace-modal-title">${esc(item.name)}</h2>
        ${renderCatalogPriceRow(item)}
        <p class="mp-desc-lead">${esc(item.description || 'Free design resource from UX Pacific.')}</p>
        <div class="mp-delivery-badge">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Instant access after free checkout (${downloads.toLocaleString()} downloads)
        </div>
        <div class="mp-action-stack">
          <button type="button" class="mp-btn-cart" onclick="addMarketplaceItemToCart('freebie', '${esc(item.id)}')">Add to Cart</button>
          <button type="button" class="mp-btn-buy" onclick="mpModalBuyNow('freebie', '${esc(item.id)}', true)">Get Free</button>
        </div>
        ${relatedHtml}
      </div>
    </div>`;
}

function renderProductDetailModal(item, type, related, tagTokens) {
  const itemType = item.type || type;
  const uniqueGallery = getMarketplaceGalleryImages(item);
  const isFree = isCatalogItemFree(item);
  const categoryLabel = esc(item.category || (itemType === 'bundle' ? 'Bundle' : 'Product'));
  const typePill = esc(mpProductTypeLabel(item, itemType));
  const reviewCount = Number(item.review_count || 0);
  const ratingHtml = renderMpStarRating(item.rating || '4.5', reviewCount);
  const sizeHtml = renderMpSizeSelector(item);
  const lowerPanelsHtml = renderProductInfoPanels(item, itemType, false, true);
  const descHtml = item.description
    ? `<div class="mp-desc-inline"><p class="mp-about-text">${esc(item.description)}</p></div>`
    : '';

  const relatedHtml = related.length
    ? `<section class="mp-related mp-related--compact">
        <div class="mp-related-head">
          <h3>You may also like</h3>
          <div class="mp-related-nav">
            <button type="button" class="mp-related-arrow" data-related-prev aria-label="Scroll related left">‹</button>
            <button type="button" class="mp-related-arrow" data-related-next aria-label="Scroll related right">›</button>
          </div>
        </div>
        <div class="mp-related-track" data-related-track>
          ${related.slice(0, 8).map((rel) => {
            const relType = rel.type || itemType;
            const relCat = esc(rel.category || 'Product');
            const relRating = esc(rel.rating || '4.5');
            return `<button type="button" class="mp-related-item" onclick="openMarketplaceModal('${esc(relType)}', '${esc(rel.id)}')">
              <div class="mp-related-item-media">
                <img src="${esc(rel.image)}" alt="" loading="lazy" decoding="async" onerror="this.src='img/poster.webp'">
                <span class="mp-related-item-cat">${relCat}</span>
              </div>
              <div class="mp-related-item-body">
                <span class="mp-related-item-name">${esc(rel.name)}</span>
                <div class="mp-related-item-meta">
                  <strong>${formatMoney(rel.price)}</strong>
                  <em>★ ${relRating}</em>
                </div>
              </div>
            </button>`;
          }).join('')}
        </div>
      </section>`
    : '';

  return `
    ${renderMpGalleryCol(uniqueGallery, item.name)}
    <div class="mp-details-scroll mp-scroll-col" data-mp-scroll>
      <div class="mp-details">
        <div class="mp-tags">
          <span class="mp-tag-pill">${categoryLabel}</span>
          <span class="mp-tag-pill mp-tag-pill--muted">${typePill}</span>
          ${item.is_featured ? '<span class="mp-tag-pill mp-tag-pill--accent">Featured</span>' : ''}
        </div>
        <h2 id="marketplace-modal-title">${esc(item.name)}</h2>
        ${ratingHtml}

        ${renderCatalogPriceRow(item)}

        ${descHtml}

        ${isFree ? '' : (() => {
          const chips = detectFormatChips(item);
          if (!chips.length) return '';
          return `<div class="mp-format-chips">${chips.map(c => `<span class="mp-format-chip mp-format-chip--${c.cls}">${c.label}</span>`).join('')}</div>`;
        })()}

        ${(() => {
          const isPhysical = item.available_type === 'physical';
          const isDigital = !isPhysical;
          if (isFree && isDigital) {
            return `<div class="mp-delivery-badge">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Instant digital delivery — free checkout at ₹0
            </div>`;
          }
          if (isDigital) {
            return `<div class="mp-delivery-badge">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Instant digital delivery after payment
            </div>`;
          }
          return `<div class="mp-delivery-badge mp-delivery-badge--physical">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
              Physical product — ships to your address
            </div>`;
        })()}

        ${sizeHtml}

        <div class="mp-field">
          <span class="mp-field-label">Quantity</span>
          <div class="mp-qty-stepper">
            <button type="button" class="mp-qty-btn" data-mp-qty-minus aria-label="Decrease quantity">−</button>
            <span class="mp-qty-value" id="mp-qty-display">1</span>
            <input type="hidden" id="marketplace-qty-input" value="1">
            <button type="button" class="mp-qty-btn" data-mp-qty-plus aria-label="Increase quantity">+</button>
          </div>
        </div>

        <div class="mp-action-stack">
          <button type="button" class="mp-btn-cart" onclick="addMarketplaceItemToCart('${esc(itemType)}', '${esc(item.id)}')">Add to Cart</button>
          <button type="button" class="mp-btn-buy" onclick="mpModalBuyNow('${esc(itemType)}', '${esc(item.id)}', ${isFree ? 'true' : 'false'})">${isFree ? 'Get Free' : 'Buy Now'}</button>
        </div>

        <div class="mp-lower-panels">
          ${lowerPanelsHtml}
        </div>

        ${relatedHtml}
      </div>
    </div>`;
}

function initMarketplaceGallery() {
  const gallery = document.querySelector('[data-mp-gallery]');
  if (!gallery) return;
  const raw = gallery.dataset.images || '';
  const images = raw ? raw.split('||').filter(Boolean) : [];
  if (!images.length) return;

  let index = 0;
  const main = document.getElementById('mp-main-image');
  const counter = gallery.querySelector('[data-mp-counter]');
  const dots = gallery.querySelectorAll('.mp-dot');
  const thumbs = gallery.querySelectorAll('.mp-thumb');

  const setIndex = (next) => {
    index = (next + images.length) % images.length;
    if (main) {
      main.style.transition = 'opacity 0.15s ease, transform 0.18s ease';
      main.style.opacity = '0';
      main.style.transform = 'scale(0.97)';
      setTimeout(() => {
        main.src = images[index];
        main.onerror = () => { main.src = 'img/poster.webp'; };
        requestAnimationFrame(() => {
          main.style.opacity = '1';
          main.style.transform = 'scale(1)';
        });
      }, 150);
    }
    if (counter) counter.textContent = `${index + 1}/${images.length}`;
    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
    thumbs.forEach((thumb, i) => thumb.classList.toggle('is-active', i === index));
  };

  gallery.querySelector('[data-mp-prev]')?.addEventListener('click', () => setIndex(index - 1));
  gallery.querySelector('[data-mp-next]')?.addEventListener('click', () => setIndex(index + 1));
  thumbs.forEach((thumb, i) => {
    thumb.addEventListener('click', () => setIndex(i));
  });

  // Lightbox / zoom
  const zoomBtn = gallery.querySelector('[data-mp-zoom]');
  if (zoomBtn && main) {
    zoomBtn.addEventListener('click', () => {
      let lb = document.getElementById('mp-lightbox');
      if (!lb) {
        lb = document.createElement('div');
        lb.id = 'mp-lightbox';
        lb.innerHTML = `<button id="mp-lightbox-close" aria-label="Close preview">×</button><img src="" alt="Product preview">`;
        document.body.appendChild(lb);
        lb.querySelector('#mp-lightbox-close').addEventListener('click', () => lb.classList.remove('is-open'));
        lb.addEventListener('click', (e) => { if (e.target === lb) lb.classList.remove('is-open'); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') lb.classList.remove('is-open'); });
      }
      lb.querySelector('img').src = main.src;
      lb.classList.add('is-open');
    });
  }
}

function initMpModalControls() {
  const qtyInput = document.getElementById('marketplace-qty-input');
  const qtyDisplay = document.getElementById('mp-qty-display');
  const syncQty = (value) => {
    const qty = Math.max(1, Math.min(10, value));
    if (qtyInput) qtyInput.value = String(qty);
    if (qtyDisplay) qtyDisplay.textContent = String(qty);
  };
  document.querySelector('[data-mp-qty-minus]')?.addEventListener('click', () => {
    syncQty(getMpModalQty() - 1);
  });
  document.querySelector('[data-mp-qty-plus]')?.addEventListener('click', () => {
    syncQty(getMpModalQty() + 1);
  });

  document.querySelectorAll('.mp-size-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.mp-size-btn').forEach((b) => b.classList.remove('is-active'));
      btn.classList.add('is-active');
    });
  });
}

async function mpModalBuyNow(type, id, isFree = false) {
  const buyBtn = document.querySelector('.mp-btn-buy');
  const cartBtn = document.querySelector('.mp-btn-cart');

  try {
    if (buyBtn) {
      buyBtn.classList.add('is-loading');
      buyBtn.disabled = true;
    }
    if (cartBtn) cartBtn.disabled = true;

    const qty = getMpModalQty();
    const size = getMpModalSize();

    if (type === 'bundle') {
      await addMarketplaceItemToCart(type, id);
      await fetchCartFromAPI();
      window.location.href = 'checkout.php';
      return;
    }

    await buyNow(id, size, qty, type === 'freebie' ? 'freebie' : 'product', { isFree: !!isFree });
  } catch (error) {
    const msg = error?.message || (typeof error === 'string' ? error : 'Could not process request.');
    showToast(msg, 'error');
    if (buyBtn) {
      buyBtn.classList.remove('is-loading');
      buyBtn.disabled = false;
    }
    if (cartBtn) cartBtn.disabled = false;
  }
}
window.mpModalBuyNow = mpModalBuyNow;

function initRelatedCarousel() {
  const track = document.querySelector('[data-related-track]');
  if (!track) return;
  const step = () => Math.max(220, track.clientWidth * 0.75);
  document.querySelector('[data-related-prev]')?.addEventListener('click', () => {
    track.scrollBy({ left: -step(), behavior: 'smooth' });
  });
  document.querySelector('[data-related-next]')?.addEventListener('click', () => {
    track.scrollBy({ left: step(), behavior: 'smooth' });
  });
}

function initProductCardPopups() {
  const cardSelector = '.uxp-product-card, .shop-product-card, .marketplace-card, .uxp-bundle-card, .freebie-card';

  const prefetchFromCard = (card) => {
    const preview = getCardPreviewData(card);
    if (preview?.id) prefetchMarketplaceDetail(preview.type, preview.id);
  };

  document.addEventListener('mouseenter', (event) => {
    const card = event.target.closest?.(cardSelector);
    if (!card) return;
    prefetchFromCard(card);
  }, true);

  document.addEventListener('mousedown', (event) => {
    const card = event.target.closest?.(cardSelector);
    if (!card) return;
    prefetchFromCard(card);
  }, true);

  document.addEventListener('focusin', (event) => {
    const card = event.target.closest?.(cardSelector);
    if (!card) return;
    prefetchFromCard(card);
  });

  if ('IntersectionObserver' in window) {
    const prefetchObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        prefetchFromCard(entry.target);
        prefetchObserver.unobserve(entry.target);
      });
    }, { rootMargin: '120px' });
    document.querySelectorAll(cardSelector).forEach((card) => prefetchObserver.observe(card));
  }

  document.addEventListener('click', (event) => {
    const buyNowBtn = event.target.closest('.js-buy-now');
    if (buyNowBtn) {
      event.stopPropagation();
      event.preventDefault();
      if (buyNowBtn.disabled) return;
      const id = buyNowBtn.dataset.productId;
      const itemType = normalizeMarketplaceType(buyNowBtn.dataset.itemType || 'product');
      if (!id) return;
      const card = buyNowBtn.closest('.uxp-product-card, .shop-product-card, .marketplace-card, [data-product-id]');
      const format = buyNowBtn.dataset.availableType || card?.dataset.type || null;
      const isFree = String(card?.dataset.isFree || buyNowBtn.dataset.isFree || '') === '1'
        || Number(card?.dataset.price || 0) === 0;
      const origText = buyNowBtn.textContent.trim();
      buyNowBtn.disabled = true;
      buyNowBtn.textContent = '…';
      buyNow(id, null, 1, itemType, { format, isFree }).catch(() => {
        buyNowBtn.disabled = false;
        buyNowBtn.textContent = origText;
      });
      return;
    }

    const popupLink = event.target.closest('a.js-product-popup');
    if (popupLink) {
      event.preventDefault();
      const card = popupLink.closest('[data-product-id], [data-id]');
      const id = popupLink.dataset.productId || card?.dataset.productId || card?.dataset.id;
      const itemType = normalizeMarketplaceType(popupLink.dataset.itemType || card?.dataset.type || 'product');
      if (id && typeof openMarketplaceModal === 'function') {
        openMarketplaceModal(itemType, id, popupLink);
      }
      return;
    }

    const popupBtn = event.target.closest('button.js-product-popup');
    if (popupBtn) {
      event.preventDefault();
      const id = popupBtn.dataset.productId || popupBtn.closest('[data-product-id]')?.dataset.productId;
      const itemType = normalizeMarketplaceType(popupBtn.dataset.itemType || popupBtn.closest('[data-type]')?.dataset.type || 'product');
      if (id && typeof openMarketplaceModal === 'function') {
        openMarketplaceModal(itemType, id, popupBtn);
      }
      return;
    }

    const card = event.target.closest('.uxp-product-card, .shop-product-card, .marketplace-card, .uxp-bundle-card, .freebie-card');
    if (!card) return;
    if (event.target.closest('button')) return;
    const plainLink = event.target.closest('a');
    if (plainLink && !plainLink.classList.contains('js-product-popup')) return;

    const id = card.dataset.productId || card.dataset.id;
    if (!id) return;
    const itemType = normalizeMarketplaceType(card.dataset.type || 'product');
    event.preventDefault();
    if (typeof openMarketplaceModal === 'function') {
      openMarketplaceModal(itemType, id, card);
    }
  });
}

async function openMarketplaceModal(type, id, triggerEl) {
  marketplaceTriggerEl = triggerEl || document.activeElement;
  const modal = ensureMarketplaceModal();
  const content = modal.querySelector('.marketplace-modal-content');
  let body = content.querySelector('.marketplace-modal-body');
  if (!body) {
    body = document.createElement('div');
    body.className = 'marketplace-modal-body';
    body.innerHTML = content.innerHTML;
    content.innerHTML = '';
    content.appendChild(body);
  }
  const openToken = ++marketplaceOpenToken;
  const card = triggerEl?.closest?.('[data-product-id], [data-id]') || document.querySelector(`[data-product-id="${id}"]`);
  const preview = getCardPreviewData(card) || { id, name: 'Product', image: 'img/poster.webp', price: 0, category: '', rating: '4.5', type };

  modal.classList.add('is-open');
  document.body.classList.add('modal-open');
  content.className = 'marketplace-modal-content is-loading';
  body.innerHTML = renderInstantModalShell(preview, type);

  prefetchMarketplaceDetail(type, id);

  try {
    const payload = await fetchMarketplaceDetail(type, id);
    if (openToken !== marketplaceOpenToken) return;
    trackMarketplaceView(type, id);
    const item = payload.item;
    const related = payload.related || [];
    const tagTokens = payload.tag_tokens || [];
    content.className = 'marketplace-modal-content';
    body.innerHTML = type === 'bundle'
      ? renderBundleDetailModal(item, related)
      : type === 'freebie'
        ? renderFreebieDetailModal(item, related)
        : renderProductDetailModal(item, type, related, tagTokens);
    initMarketplaceGallery();
    initMpModalControls();
    initRelatedCarousel();
    body.querySelectorAll('.mp-scroll-col').forEach((el) => { el.scrollTop = 0; });
    
    const closeBtn = modal.querySelector('.marketplace-modal-close');
    if (closeBtn) closeBtn.focus();
  } catch (error) {
    if (openToken !== marketplaceOpenToken) return;
    content.className = 'marketplace-modal-content marketplace-modal-content--error';
    body.innerHTML = `<div class="marketplace-error">${esc(error.message || 'Something went wrong.')}</div>`;
  }
}

function closeMarketplaceModal() {
  const modal = document.getElementById('marketplace-modal');
  if (!modal || !modal.classList.contains('is-open')) return;
  
  marketplaceOpenToken += 1;
  modal.classList.add('is-closing');
  
  setTimeout(() => {
    modal.classList.remove('is-open', 'is-closing');
    document.body.classList.remove('modal-open');
    
    if (marketplaceTriggerEl && typeof marketplaceTriggerEl.focus === 'function') {
      marketplaceTriggerEl.focus();
      marketplaceTriggerEl = null;
    }
  }, 200);
}

let marketplaceTriggerEl = null;

async function fetchMarketplaceItem(type, id) {
  const payload = await fetchMarketplaceDetail(type, id);
  return payload.item;
}

async function addMarketplaceItemToCart(type, id) {
  const cartBtn = document.querySelector('.mp-btn-cart');
  const buyBtn = document.querySelector('.mp-btn-buy');
  
  try {
    if (cartBtn) {
      cartBtn.classList.add('is-loading');
      cartBtn.disabled = true;
    }
    if (buyBtn) buyBtn.disabled = true;
    
    const item = await fetchMarketplaceItem(type, id);
    const qty = getMpModalQty();
    const size = getMpModalSize();
    const format = isCatalogItemFree(item) ? 'digital' : (item.available_type || 'digital');
    await addToCart(
      item.id,
      size,
      qty,
      { ...item, item_type: type },
      format,
    );
    showToast('Added to cart!', 'success');
  } catch (error) {
    const msg = error?.message || (typeof error === 'string' ? error : 'Could not add item.');
    if (error?.status === 409 || /already claimed/i.test(msg)) {
      showToast(msg, 'error');
      window.location.href = 'orders.php';
      return;
    }
    showToast(msg, 'error');
  } finally {
    if (cartBtn) {
      cartBtn.classList.remove('is-loading');
      cartBtn.disabled = false;
    }
    if (buyBtn) buyBtn.disabled = false;
  }
}


function initTopProductsFilters() {
  const section = document.querySelector('.uxp-top-products-section');
  if (!section) return;
  const buttons = section.querySelectorAll('.uxp-product-filters .filter-btn');
  const cards = section.querySelectorAll('.uxp-product-card');
  if (!buttons.length || !cards.length) return;

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const filter = (btn.dataset.filter || 'all').toLowerCase();
      buttons.forEach((b) => {
        const active = b === btn;
        b.classList.toggle('active', active);
        b.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      cards.forEach((card) => {
        const cat = (card.dataset.categoryFilter || card.dataset.category || '').toLowerCase();
        const show = filter === 'all' || cat === filter;
        card.classList.toggle('is-filter-hidden', !show);
      });
    });
  });
}

function initMobileNav() {
  const navbar = document.querySelector('.navbar');
  const toggle = document.querySelector('.mobile-nav-toggle');
  const panel = document.getElementById('mobile-nav-panel');
  const backdrop = document.querySelector('.mobile-nav-backdrop');
  if (!navbar || !toggle || !panel) return;

  function setMobileNavOpen(open) {
    navbar.classList.toggle('mobile-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    panel.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (backdrop) backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
    document.body.classList.toggle('mobile-nav-open', open);
  }

  toggle.addEventListener('click', (event) => {
    event.stopPropagation();
    setMobileNavOpen(!navbar.classList.contains('mobile-open'));
  });

  backdrop?.addEventListener('click', () => setMobileNavOpen(false));

  panel.querySelectorAll('a.mobile-nav-link').forEach((link) => {
    link.addEventListener('click', () => setMobileNavOpen(false));
  });

  document.addEventListener('click', (event) => {
    if (!navbar.classList.contains('mobile-open')) return;
    if (navbar.contains(event.target)) return;
    setMobileNavOpen(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && navbar.classList.contains('mobile-open')) {
      setMobileNavOpen(false);
      toggle.focus();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 900) setMobileNavOpen(false);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initSearchModal();
  initProductCardPopups();
  initTopProductsFilters();
  initMobileNav();
});

window.openMarketplaceModal = openMarketplaceModal;
window.closeMarketplaceModal = closeMarketplaceModal;
window.addMarketplaceItemToCart = addMarketplaceItemToCart;

function toggleMarketplaceWishlist(itemType, itemId) {
  const session = getUserSession();
  if (!session || !session.id) {
    window.location.href = 'signin.php?redirect=' + encodeURIComponent(window.location.pathname);
    return;
  }
  secureFetch('api/wishlist/toggle.php', {
    method: 'POST',
    body: JSON.stringify({ item_type: itemType, item_id: Number(itemId) })
  })
    .then(r => r.json())
    .then(data => {
      if (data.status === 'success') {
        const added = data.data?.action === 'added';
        showToast(added ? 'Saved to wishlist!' : 'Removed from wishlist.', 'success');
      } else {
        showToast(data.message || 'Could not update wishlist.', 'error');
      }
    })
    .catch(() => showToast('Could not update wishlist.', 'error'));
}

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
    const studioPages = new Set(['studio.php']);
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
      if (studioPages.has(file)) return 'studio';
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
      if (studioPages.has(file) || text === 'studio') return 'studio';
      if (homePages.has(file) && !url.hash && text === 'home') return 'home';
      if (homePages.has(file) && url.hash === '#category') return 'category';
      return '';
    }

    function setActiveNav(group) {
      navLinks.forEach(link => {
        // Only mark as active if both the page and link have a meaningful group
        const isActive = group !== '' && linkGroup(link) === group;
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

// Buy Now / Get Free — add item to cart, then go to checkout (or sign-in first)
async function buyNow(productId, size, quantity, itemType = 'product', options = {}) {
  const isFree = options.isFree === true || itemType === 'freebie';
  const format = isFree ? 'digital' : (options.format || null);
  const details = { item_type: itemType, ...(options.details || {}) };

  const userSession = getUserSession();
  if (!userSession || !userSession.id) {
    if (userSession) clearUserSession();
    // Guest cart is localStorage — synchronous, no need to await.
    addToCart(productId, size, quantity, details, format, { silent: true }).catch(() => {});
    showToast('Please sign in to continue', 'error');
    window.location.href = 'signin.php?redirect=checkout.php';
    return;
  }

  try {
    await addToCart(productId, size, quantity, details, format, { silent: true });
    // Ensure local cart is synced before navigation (checkout reads it on first paint).
    await fetchCartFromAPI();
    window.location.href = 'checkout.php';
  } catch (err) {
    const message = (typeof err === 'object' && err?.message) ? err.message : String(err || 'Failed to add item to cart.');
    if (err?.status === 409 || /already claimed/i.test(message)) {
      showToast(message || 'You have already claimed this free item.', 'error');
      window.location.href = 'orders.php';
      return;
    }
    showToast(message, 'error');
    throw err;
  }
}

// Check authentication before proceeding to checkout
function checkAuthBeforeCheckout(event) {
  const userSession = getUserSession();
  if (!userSession || !userSession.id) {
    if (userSession) clearUserSession();
    event.preventDefault();
    showToast('Please sign in to proceed to checkout', 'error');
    window.location.href = 'signin.php?redirect=checkout.php';
    return false;
  }
  return true;
}

const SAFE_REDIRECT_PATHS = [
  'index.php', 'cart.php', 'checkout.php', 'account.php', 'orders.php',
  'order-confirmation.php', 'shopAll.php', 'wishlist.php', 'product.php',
  'bundles.php', 'freebies.php', 'search.php', 'contact.php', 'policies.php',
];

function getSafeRedirect(rawParam) {
  if (!rawParam || typeof rawParam !== 'string') return 'index.php';
  let decoded;
  try {
    decoded = decodeURIComponent(rawParam);
  } catch (_) {
    return 'index.php';
  }
  if (
    decoded.startsWith('//') ||
    decoded.startsWith('http:') ||
    decoded.startsWith('https:') ||
    decoded.includes('..') ||
    decoded.includes('\\') ||
    decoded.includes('\n') ||
    decoded.includes('\r')
  ) {
    return 'index.php';
  }
  const pathOnly = decoded.split('?')[0].split('#')[0];
  if (SAFE_REDIRECT_PATHS.includes(pathOnly)) {
    return decoded;
  }
  return 'index.php';
}

function cartItemIsDigital(item) {
  if ((item.item_type || 'product') === 'bundle') return true;
  const fmt = String(item.selected_format || item.available_type || '').toLowerCase();
  if (fmt === 'digital') return true;
  if (fmt === 'physical') return false;
  return String(item.available_type || '').toLowerCase() === 'digital';
}

function cartItemIsPhysical(item) {
  return !cartItemIsDigital(item);
}

function handleSignInRedirect() {
  const urlParams = new URLSearchParams(window.location.search);
  const redirectUrl = getSafeRedirect(urlParams.get('redirect'));
  window.location.href = redirectUrl;
}

window.buyNow = buyNow;
window.handleSignOut = handleSignOut;
window.checkAuthBeforeCheckout = checkAuthBeforeCheckout;

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
            <button type="button" data-cart-id="${Number(item.cart_id || 0)}" data-cart-qty="${esc(item.id)}" data-size="${esc(item.size || '')}" data-next="${Number(item.quantity || 1) - 1}">-</button>
            <span>${Number(item.quantity || 1)}</span>
            <button type="button" data-cart-id="${Number(item.cart_id || 0)}" data-cart-qty="${esc(item.id)}" data-size="${esc(item.size || '')}" data-next="${Number(item.quantity || 1) + 1}">+</button>
            <button type="button" data-cart-id="${Number(item.cart_id || 0)}" data-cart-remove="${esc(item.id)}" data-size="${esc(item.size || '')}">Remove</button>
          </div>
        </div>
      </article>
    `).join('');

    itemsEl.querySelectorAll('[data-cart-qty]').forEach((button) => {
      button.addEventListener('click', () => window.updateCartQuantity(
        button.dataset.cartQty,
        button.dataset.size || null,
        Number(button.dataset.next),
        Number(button.dataset.cartId || 0)
      ));
    });
    itemsEl.querySelectorAll('[data-cart-remove]').forEach((button) => {
      button.addEventListener('click', () => window.removeFromCart(
        button.dataset.cartRemove,
        button.dataset.size || null,
        Number(button.dataset.cartId || 0)
      ));
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

  function profileDropdownMenuHtml() {
    const icon = (paths) => `<svg class="nav-menu-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
    const userIcon = icon('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>');
    const ordersIcon = icon('<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>');
    const logoutIcon = icon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>');
    return `
      <a href="account.php" role="menuitem">${userIcon} Edit Profile</a>
      <a href="orders.php" role="menuitem">${ordersIcon} My Orders</a>
      <button type="button" role="menuitem" onclick="handleSignOut()">${logoutIcon} Logout</button>
    `;
  }

  function syncHeaderAuth(user) {
    const signedIn = !!(user && user.id);
    const displayName = signedIn ? getUserFirstName(user) : '';
    const firstInitial = (displayName || 'U').charAt(0).toUpperCase();
    const isMobileHeader = window.matchMedia('(max-width: 900px)').matches;

    setSignInCtaVisible(!signedIn);
    document.querySelectorAll('.header-signin-cta, .nav-cta[href="signin.php"]').forEach((el) => {
      el.textContent = 'Sign In';
    });

    document.querySelectorAll('.profile-menu, .user-menu, .nav-user').forEach((menu) => {
      menu.style.display = signedIn && !isMobileHeader ? 'flex' : 'none';
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
          <a href="orders.php" class="user-dropdown-item"><span>My Orders</span></a>
          <button type="button" class="user-dropdown-item logout" onclick="handleSignOut()"><span>Logout</span></button>
        `;
      }

      const profileDropdown = menu.querySelector('.profile-dropdown');
      if (profileDropdown && signedIn) {
        profileDropdown.innerHTML = profileDropdownMenuHtml();
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
              ${profileDropdownMenuHtml()}
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
    const TRENDS = ['UI Kit', 'Portfolio', 'Mockups', 'Case Study', 'Icons', 'Dashboard'];

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
      let activeIdx = -1;

      function getItems() {
        return Array.from(panel.querySelectorAll('.nav-search-suggestion'));
      }

      function setActive(idx) {
        const items = getItems();
        activeIdx = Math.max(-1, Math.min(idx, items.length - 1));
        items.forEach((el, i) => el.classList.toggle('is-focused', i === activeIdx));
      }

      function closePanel() {
        panel.classList.remove('is-visible');
        activeIdx = -1;
      }

      function showTrends() {
        panel.innerHTML = `
          <div class="nss-section-label">Trending</div>
          <div class="nss-trends">
            ${TRENDS.map(t => `<button type="button" class="nss-trend-pill" data-trend="${esc(t)}">${esc(t)}</button>`).join('')}
          </div>`;
        panel.classList.add('is-visible');
      }

      function showLoading() {
        panel.innerHTML = `<div class="nss-loading"><span></span><span></span><span></span></div>`;
        panel.classList.add('is-visible');
      }

      async function fetchAndShow(q) {
        showLoading();
        try {
          const response = await fetch(`api/product/search.php?q=${encodeURIComponent(q)}&limit=7`);
          const data = await response.json();
          const items = data?.data?.items || [];
          if (!items.length) {
            panel.innerHTML = `
              <div class="nss-section-label">No results for "<em>${esc(q)}</em>"</div>
              <div class="nss-empty-hint">Try a different keyword or <a href="search.php?q=${encodeURIComponent(q)}">browse all</a></div>`;
            panel.classList.add('is-visible');
            return;
          }
          const listHtml = items.map((item) => {
            const typeBadge = item.type === 'bundle'
              ? `<span class="nss-type-badge nss-type-bundle">Bundle</span>`
              : `<span class="nss-type-badge nss-type-product">Product</span>`;
            return `
              <button type="button" class="nav-search-suggestion" data-type="${esc(item.type || 'product')}" data-id="${esc(item.id)}" data-name="${esc(item.name)}">
                <img src="${esc(item.image)}" alt="" loading="lazy" onerror="this.src='img/sticker.webp'" />
                <span class="nss-info">
                  <span class="nss-name">${esc(item.name)}</span>
                  <span class="nss-meta">${typeBadge} <span class="nss-cat">${esc(item.category || '')}</span></span>
                </span>
                <strong class="nss-price">${money(item.price)}</strong>
              </button>`;
          }).join('');
          const viewAll = `<a class="nss-view-all" href="search.php?q=${encodeURIComponent(q)}">View all results for "<em>${esc(q)}</em>" →</a>`;
          panel.innerHTML = listHtml + viewAll;
          panel.classList.add('is-visible');
          activeIdx = -1;
        } catch {
          closePanel();
        }
      }

      input.addEventListener('focus', () => {
        if (!input.value.trim()) showTrends();
      });

      input.addEventListener('input', () => {
        clearTimeout(timer);
        activeIdx = -1;
        const q = input.value.trim();
        if (!q) { showTrends(); return; }
        if (q.length < 2) { closePanel(); return; }
        timer = setTimeout(() => fetchAndShow(q), 200);
      });

      input.addEventListener('keydown', (event) => {
        if (!panel.classList.contains('is-visible')) return;
        const items = getItems();
        if (event.key === 'ArrowDown') {
          event.preventDefault();
          setActive(activeIdx + 1);
        } else if (event.key === 'ArrowUp') {
          event.preventDefault();
          setActive(activeIdx - 1);
        } else if (event.key === 'Enter' && activeIdx >= 0 && items[activeIdx]) {
          event.preventDefault();
          items[activeIdx].click();
        } else if (event.key === 'Escape') {
          closePanel();
          form.classList.remove('is-open');
          input.blur();
        }
      });

      panel.addEventListener('click', (event) => {
        const trend = event.target.closest('.nss-trend-pill');
        if (trend) {
          input.value = trend.dataset.trend;
          fetchAndShow(trend.dataset.trend);
          return;
        }
        const viewAll = event.target.closest('.nss-view-all');
        if (viewAll) { closePanel(); return; }

        const item = event.target.closest('.nav-search-suggestion');
        if (!item) return;
        closePanel();
        if (item.dataset.type === 'bundle') {
          if (typeof openMarketplaceModal === 'function') {
            openMarketplaceModal('bundle', item.dataset.id, item);
          } else {
            window.location.href = `bundles.php?quick=bundle&id=${encodeURIComponent(item.dataset.id)}`;
          }
          return;
        }
        if (typeof openMarketplaceModal === 'function') {
          openMarketplaceModal('product', item.dataset.id, item);
        } else {
          window.location.href = `product.php?id=${encodeURIComponent(item.dataset.id)}`;
        }
      });

      document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) closePanel();
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closePanel();
      });
    });
  }

  function initDataAddButtons() {
    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-add-to-cart]');
      if (!button) return;
      event.preventDefault();
      const itemType = button.dataset.itemType || 'product';
      const details = {
        name: button.dataset.name || 'Product',
        price: Number(button.dataset.price || 0),
        image: button.dataset.image || 'img/sticker.webp',
        category: button.dataset.category || 'Products',
        description: button.dataset.description || '',
        item_type: itemType,
      };
      window.addToCart(button.dataset.addToCart, null, 1, details, button.dataset.type || 'digital');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    ensureCartDrawer();
    hydrateSession();
    initProfileMenus();
    initCartLinks();
    initSearchSuggestions();
    initDataAddButtons();
    renderCartDrawer();
  });
})();
