<?php
require_once 'includes/config.php';

// Server-side auth guard — redirect to sign-in immediately
if (empty($_SESSION['user_id'])) {
    header('Location: signin.php?redirect=orders.php');
    exit;
}

$userName = trim((string) ($_SESSION['first_name'] ?? ''));
if ($userName === '') {
    $userName = trim((string) ($_SESSION['user_email'] ?? 'Customer'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <title>My Orders – UX Pacific Shop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" href="img/fav.png" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
    <link rel="stylesheet" href="assets/css/orders.css" />
    <script defer src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="orders-page">
<div class="page">
    <?php include 'includes/header.php'; ?>

    <main class="main">
        <!-- ── Hero ────────────────────────────────────────────────── -->
        <section class="orders-hero">
            <div class="orders-hero-inner">
                <div class="orders-hero-text">
                    <h1>My Orders</h1>
                    <p>Hey <strong><?php echo htmlspecialchars($userName); ?></strong>, here's everything you've ordered.</p>
                </div>
                <div class="orders-hero-stats" id="orders-hero-stats" style="display:none;">
                    <div class="orders-stat">
                        <span class="orders-stat-num" id="stat-total">0</span>
                        <span class="orders-stat-label">Total Orders</span>
                    </div>
                    <div class="orders-stat">
                        <span class="orders-stat-num" id="stat-delivered">0</span>
                        <span class="orders-stat-label">Delivered</span>
                    </div>
                    <div class="orders-stat">
                        <span class="orders-stat-num" id="stat-spent">₹0</span>
                        <span class="orders-stat-label">Total Spent</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Content ──────────────────────────────────────────────── -->
        <section class="orders-content-section">
            <div class="orders-content-inner">

                <!-- Filter pills -->
                <div class="orders-filters" id="orders-filters" style="display:none;">
                    <button class="orders-filter-pill active" data-filter="all">All</button>
                    <button class="orders-filter-pill" data-filter="recent">Recent</button>
                    <button class="orders-filter-pill" data-filter="past">Past</button>
                    <button class="orders-filter-pill" data-filter="pending">Pending</button>
                    <button class="orders-filter-pill" data-filter="awaiting_payment">Awaiting Payment</button>
                    <button class="orders-filter-pill" data-filter="processing">Processing</button>
                    <button class="orders-filter-pill" data-filter="shipped">Shipped</button>
                    <button class="orders-filter-pill" data-filter="delivered">Delivered</button>
                    <button class="orders-filter-pill" data-filter="cancelled">Cancelled</button>
                </div>

                <!-- Skeleton loader -->
                <div id="orders-skeleton">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="order-skeleton">
                        <div class="skel skel-head"></div>
                        <div class="skel skel-item"></div>
                        <div class="skel skel-item skel-item--short"></div>
                        <div class="skel skel-foot"></div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Empty state -->
                <div class="orders-empty" id="orders-empty" style="display:none;">
                    <div class="orders-empty-icon">
                        <i class="ph ph-package"></i>
                    </div>
                    <h2>No orders yet</h2>
                    <p>You haven't placed any orders. Explore our products and bundles.</p>
                    <div class="orders-empty-actions">
                        <a href="shopAll.php" class="btn-primary">Browse Products</a>
                        <a href="bundles.php" class="btn-ghost">View Bundles</a>
                    </div>
                </div>

                <!-- No filter results -->
                <div class="orders-no-results" id="orders-no-results" style="display:none;">
                    <i class="ph ph-funnel-x"></i>
                    <p>No orders match this filter.</p>
                    <button class="btn-ghost small" onclick="setFilter('all')">Show all orders</button>
                </div>

                <!-- Orders list -->
                <div id="orders-list" class="orders-list"></div>

                <div id="orders-review-banner" class="orders-review-banner" style="display:none;" role="status">
                    <i class="ph ph-star"></i>
                    <p>You have items ready to review. Scroll down and tap <strong>Write review</strong> on any eligible product.</p>
                    <button type="button" class="btn-ghost small" id="orders-review-banner-dismiss">Dismiss</button>
                </div>
            </div>
        </section>
    </main>

    <!-- Review modal -->
    <div id="review-modal" class="review-modal" aria-hidden="true" hidden>
        <div class="review-modal-backdrop" data-review-close></div>
        <div class="review-modal-panel" role="dialog" aria-labelledby="review-modal-title" aria-modal="true">
            <header class="review-modal-head">
                <h2 id="review-modal-title">Write a review</h2>
                <button type="button" class="review-modal-close" data-review-close aria-label="Close">
                    <i class="ph ph-x"></i>
                </button>
            </header>
            <p class="review-modal-product" id="review-modal-product-name"></p>
            <form id="review-form" class="review-form">
                <input type="hidden" id="review-product-id" name="product_id" value="" />
                <input type="hidden" id="review-bundle-id" name="bundle_id" value="" />
                <fieldset class="review-stars-field">
                    <legend>Your rating</legend>
                    <div class="review-stars" id="review-stars" role="radiogroup" aria-label="Rating from 1 to 5 stars">
                        <button type="button" class="review-star" data-value="1" aria-label="1 star">★</button>
                        <button type="button" class="review-star" data-value="2" aria-label="2 stars">★</button>
                        <button type="button" class="review-star" data-value="3" aria-label="3 stars">★</button>
                        <button type="button" class="review-star" data-value="4" aria-label="4 stars">★</button>
                        <button type="button" class="review-star" data-value="5" aria-label="5 stars">★</button>
                    </div>
                    <input type="hidden" id="review-rating" name="rating" value="0" required />
                </fieldset>
                <label class="review-comment-label" for="review-comment">Your review (optional)</label>
                <textarea id="review-comment" name="comment" rows="4" maxlength="2000" placeholder="What did you like about this item?"></textarea>
                <p class="review-form-hint">Reviews are moderated before they appear on the shop.</p>
                <div class="review-form-actions">
                    <button type="button" class="btn-ghost" data-review-close>Cancel</button>
                    <button type="submit" class="btn-primary" id="review-submit-btn">Submit review</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
<script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
<script>
/* ── Orders page controller ────────────────────────────────────── */
(function () {
    'use strict';

    let allOrders = [];
    let activeFilter = 'all';

    const STATUS_LABELS = {
        pending:          'Pending',
        awaiting_payment: 'Awaiting Payment',
        processing:       'Processing',
        shipped:          'Shipped',
        delivered:        'Delivered',
        cancelled:        'Cancelled',
        paid:             'Paid',
        failed:           'Failed',
    };

    const PAYMENT_LABELS = {
        cod:       'Cash on Delivery',
        razorpay:  'Online Payment',
        stripe:    'Card Payment',
        free:      'Free',
    };

    function fmt(n) {
        return Number(n).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function fmtDate(str) {
        const d = new Date(str);
        if (isNaN(d)) return str;
        return d.toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function statusLabel(s) {
        return STATUS_LABELS[s] || s.replace(/_/g, ' ');
    }

    function paymentLabel(p) {
        return PAYMENT_LABELS[p] || (p ? p.charAt(0).toUpperCase() + p.slice(1) : '—');
    }

    /* ── Fetch orders ─────────────────────────────────────────── */
    function loadOrders() {
        fetch('api/order/get.php', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                hideSkeleton();
                if (data.status !== 'success') {
                    showEmpty();
                    return;
                }
                allOrders = Array.isArray(data.data) ? data.data : [];
                if (allOrders.length === 0) {
                    showEmpty();
                    return;
                }
                buildStats();
                showFilters();
                renderOrders();
                maybeShowReviewBanner();
            })
            .catch(() => {
                hideSkeleton();
                showEmpty();
            });
    }

    /* ── Stats ───────────────────────────────────────────────── */
    function buildStats() {
        const delivered = allOrders.filter(o => o.status === 'delivered').length;
        const spent = allOrders
            .filter(o => !['cancelled', 'failed'].includes(o.status))
            .reduce((sum, o) => sum + (parseFloat(o.total) || 0), 0);

        document.getElementById('stat-total').textContent = allOrders.length;
        document.getElementById('stat-delivered').textContent = delivered;
        document.getElementById('stat-spent').textContent = '₹' + fmt(spent);
        document.getElementById('orders-hero-stats').style.display = '';
    }

    /* ── Filter ──────────────────────────────────────────────── */
    window.setFilter = function (filter) {
        activeFilter = filter;
        document.querySelectorAll('.orders-filter-pill').forEach(pill => {
            pill.classList.toggle('active', pill.dataset.filter === filter);
        });
        renderOrders();
    };

    function showFilters() {
        document.getElementById('orders-filters').style.display = '';
    }

    /* ── Render ──────────────────────────────────────────────── */
    function renderOrders() {
        const nowTs = Date.now();
        const recentCutoff = nowTs - (1000 * 60 * 60 * 24 * 30);
        const isRecent = (order) => {
            const ts = new Date(order.date || order.created_at || '').getTime();
            return Number.isFinite(ts) && ts >= recentCutoff;
        };

        const filtered = (() => {
            if (activeFilter === 'all') return allOrders;
            if (activeFilter === 'recent') return allOrders.filter(isRecent);
            if (activeFilter === 'past') return allOrders.filter(o => !isRecent(o));
            return allOrders.filter(o => o.status === activeFilter);
        })();

        const list = document.getElementById('orders-list');
        const noResults = document.getElementById('orders-no-results');

        if (filtered.length === 0) {
            list.innerHTML = '';
            noResults.style.display = '';
            return;
        }
        noResults.style.display = 'none';

        if (activeFilter === 'all') {
            const recent = filtered.filter(isRecent);
            const past = filtered.filter(o => !isRecent(o));
            list.innerHTML = [
                renderOrderGroup('Recent Orders', 'Placed in the last 30 days', recent),
                renderOrderGroup('Past Orders', 'Older order history', past),
            ].join('');
            return;
        }

        list.innerHTML = filtered.map(renderOrderCard).join('');
    }

    function renderOrderGroup(title, subtitle, orders) {
        if (!orders.length) {
            return `
            <div class="order-group-empty">
                <h3>${escHtml(title)}</h3>
                <p>No orders in this section.</p>
            </div>`;
        }
        return `
        <section class="order-group">
            <header class="order-group-head">
                <h3>${escHtml(title)}</h3>
                <p>${escHtml(subtitle)}</p>
            </header>
            <div class="order-group-list">
                ${orders.map(renderOrderCard).join('')}
            </div>
        </section>`;
    }

    function renderDownloadsSection(order) {
        const downloads = order.downloads || [];
        if (downloads.length === 0) return '';

        const dlHtml = downloads.map(dl => {
            const usedLabel = `${dl.download_count}/${dl.download_limit} downloads used`;
            const expiry    = dl.expires_at ? new Date(dl.expires_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
            const expiryLabel = expiry ? ` · Expires ${expiry}` : '';

            let action;
            if (dl.is_available && dl.has_file) {
                const label = dl.action_label || (dl.delivery_mode === 'open_link' ? 'Open' : (dl.delivery_mode === 'instructions' ? 'View instructions' : 'Download'));
                const icon = dl.delivery_mode === 'open_link' ? 'ph-arrow-square-out' : (dl.delivery_mode === 'instructions' ? 'ph-note' : 'ph-download-simple');
                action = `<a class="order-dl-btn" href="${escHtml(dl.download_url || ('api/download/file.php?token=' + encodeURIComponent(dl.token)))}">
                    <i class="ph ${icon}"></i> ${escHtml(label)}
                </a>`;
            } else if (!dl.has_file) {
                action = `<span class="order-dl-pending"><i class="ph ph-clock"></i> File pending</span>`;
            } else if (dl.download_count >= dl.download_limit) {
                action = `<span class="order-dl-exhausted"><i class="ph ph-x-circle"></i> Limit reached</span>`;
            } else {
                action = `<span class="order-dl-expired"><i class="ph ph-prohibit"></i> Expired</span>`;
            }

            return `<div class="order-dl-row">
                <div class="order-dl-info">
                    <span class="order-dl-name">${escHtml(dl.item_name)}</span>
                    <span class="order-dl-meta">${usedLabel}${expiryLabel}</span>
                </div>
                ${action}
            </div>`;
        }).join('');

        return `<div class="order-downloads-section">
            <div class="order-downloads-header"><i class="ph ph-download-simple"></i> Your Digital Files</div>
            ${dlHtml}
        </div>`;
    }

    function renderOrderCard(order) {
        const total = parseFloat(order.total) || 0;
        const isFreeOrder = total === 0;
        const statusSlug = (order.status || 'pending').toLowerCase().replace(/\s+/g, '_');

        const itemsHtml = (order.items || []).map(item => renderOrderItem(item, statusSlug)).join('');

        const payMethod = isFreeOrder ? 'free' : (order.paymentMethod || order.payment_method || '');
        const payLabel  = isFreeOrder ? 'Free' : paymentLabel(payMethod);
        const payChipClass = isFreeOrder ? 'pay-chip pay-chip--free' : 'pay-chip';

        return `
        <div class="order-card" data-status="${statusSlug}">
            <div class="order-card-header">
                <div class="order-card-meta">
                    <div class="order-num-row">
                        <span class="order-num">Order #${escHtml(String(order.orderNumber || order.order_number || order.id))}</span>
                        <span class="status-badge status-${statusSlug}">${statusLabel(statusSlug)}</span>
                    </div>
                    <div class="order-meta-row">
                        <span class="order-date"><i class="ph ph-calendar-blank"></i> ${fmtDate(order.date || order.created_at)}</span>
                        <span class="${payChipClass}">
                            <i class="ph ${isFreeOrder ? 'ph-gift' : 'ph-credit-card'}"></i>
                            ${escHtml(payLabel)}
                        </span>
                    </div>
                </div>
            </div>

            <div class="order-items-list">
                ${itemsHtml || '<p class="order-no-items">No items found for this order.</p>'}
            </div>

            ${renderDownloadsSection(order)}

            <div class="order-card-footer">
                <div class="order-total-block">
                    ${isFreeOrder
                        ? '<span class="order-total-free"><i class="ph ph-gift"></i> Free Order</span>'
                        : `<span class="order-total-label">Total</span><span class="order-total-amount">₹${fmt(total)}</span>`
                    }
                </div>
                <div class="order-card-actions">
                    ${statusSlug === 'awaiting_payment' ? `
                    <button type="button" class="btn-primary small order-action-btn"
                        data-order-id="${order.id}"
                        data-order-number="${escHtml(String(order.orderNumber || order.order_number || ''))}"
                        data-total="${total}"
                        onclick="handleOrderPayNow(this)">
                        <i class="ph ph-credit-card"></i> Pay Now
                    </button>` : ''}
                    <button type="button" class="btn-ghost small order-action-btn"
                        data-order-id="${order.id}"
                        data-items="${escHtml(JSON.stringify(order.items || []))}"
                        onclick="handleReorder(+this.dataset.orderId, JSON.parse(this.dataset.items))">
                        <i class="ph ph-arrow-clockwise"></i> Reorder
                    </button>
                </div>
            </div>
        </div>`;
    }

    function renderOrderItem(item, orderStatus) {
        const price = parseFloat(item.price) || 0;
        const qty   = parseInt(item.quantity) || 1;
        const isFree = price === 0;
        const itemTotal = price * qty;

        const img = escHtml(item.image || 'img/poster.webp');
        const name = escHtml(item.name || 'Item');
        const rawName = String(item.name || 'Item');
        const type = (item.item_type || 'product').toLowerCase();
        const typeLabel = type === 'bundle' ? 'Bundle' : 'Product';

        const priceBadgeClass = isFree ? 'item-type-badge item-type-badge--free' : 'item-type-badge item-type-badge--paid';
        const priceBadgeText  = isFree ? 'FREE' : '₹' + fmt(itemTotal);

        const sizeText = item.size ? `<span class="item-meta-chip">Size: ${escHtml(String(item.size))}</span>` : '';
        const typeChip = `<span class="item-meta-chip item-meta-chip--type">${typeLabel}</span>`;

        let reviewHtml = '';
        if (item.has_reviewed && item.review) {
            const stars = '★'.repeat(item.review.rating) + '☆'.repeat(5 - item.review.rating);
            const statusTag = item.review.is_approved 
                ? '' 
                : ' <span class="review-submitted-tag">Submitted</span>';
            reviewHtml = `<p class="order-item-review-done"><span class="review-stars-display">${stars}</span>${statusTag}</p>`;
        } else if (item.can_review) {
            const pid = parseInt(item.product_id, 10) || 0;
            const bid = parseInt(item.bundle_id, 10) || 0;
            reviewHtml = `
                <button type="button" class="btn-ghost small order-review-btn"
                    data-product-id="${pid}"
                    data-bundle-id="${bid}"
                    data-item-name="${escHtml(rawName)}"
                    onclick="openReviewModal(this)">
                    <i class="ph ph-star"></i> Write review
                </button>`;
        }

        const reviewableClass = item.can_review ? ' order-item-row--reviewable' : '';

        return `
        <div class="order-item-row${reviewableClass}" data-order-status="${escHtml(orderStatus || '')}">
            <div class="order-item-thumb">
                <img src="${img}" alt="${name}" loading="lazy" onerror="this.src='img/poster.webp'" />
            </div>
            <div class="order-item-body">
                <p class="order-item-name">${name}</p>
                <div class="order-item-chips">
                    ${typeChip}
                    ${sizeText}
                    <span class="item-meta-chip">Qty: ${qty}</span>
                </div>
                ${reviewHtml}
            </div>
            <div class="order-item-price-block">
                <span class="${priceBadgeClass}">${priceBadgeText}</span>
            </div>
        </div>`;
    }

    /* ── Review modal ─────────────────────────────────────────── */
    let reviewSelectedRating = 0;

    window.openReviewModal = function (btn) {
        const modal = document.getElementById('review-modal');
        const productId = parseInt(btn.dataset.productId, 10) || 0;
        const bundleId  = parseInt(btn.dataset.bundleId, 10) || 0;
        const itemName  = btn.dataset.itemName || 'Item';

        document.getElementById('review-product-id').value = productId > 0 ? String(productId) : '';
        document.getElementById('review-bundle-id').value  = bundleId > 0 ? String(bundleId) : '';
        document.getElementById('review-modal-product-name').textContent = itemName;
        document.getElementById('review-comment').value = '';
        setReviewRating(0);

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('review-modal-open');
        document.getElementById('review-comment').focus();
    };

    function closeReviewModal() {
        const modal = document.getElementById('review-modal');
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('review-modal-open');
    }

    function setReviewRating(value) {
        reviewSelectedRating = value;
        document.getElementById('review-rating').value = value > 0 ? String(value) : '';
        document.querySelectorAll('#review-stars .review-star').forEach(btn => {
            const v = parseInt(btn.dataset.value, 10);
            btn.classList.toggle('active', v > 0 && v <= value);
            btn.setAttribute('aria-checked', v === value ? 'true' : 'false');
        });
    }

    document.querySelectorAll('[data-review-close]').forEach(el => {
        el.addEventListener('click', closeReviewModal);
    });

    document.getElementById('review-stars').addEventListener('click', function (e) {
        const star = e.target.closest('.review-star');
        if (star) setReviewRating(parseInt(star.dataset.value, 10));
    });

    document.getElementById('review-form').addEventListener('submit', function (e) {
        e.preventDefault();
        if (reviewSelectedRating < 1) {
            if (typeof showToast === 'function') showToast('Please select a star rating.', 'error');
            return;
        }
        const productId = parseInt(document.getElementById('review-product-id').value, 10) || 0;
        const bundleId  = parseInt(document.getElementById('review-bundle-id').value, 10) || 0;
        const payload = {
            rating: reviewSelectedRating,
            comment: document.getElementById('review-comment').value.trim(),
        };
        if (productId > 0) payload.product_id = productId;
        else if (bundleId > 0) payload.bundle_id = bundleId;
        else {
            if (typeof showToast === 'function') showToast('Invalid item for review.', 'error');
            return;
        }

        const submitBtn = document.getElementById('review-submit-btn');
        submitBtn.disabled = true;

        fetch('api/reviews/submit.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': typeof getCsrfToken === 'function' ? getCsrfToken() : '',
            },
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'success') throw new Error(data.message || 'Could not submit review');
                if (typeof showToast === 'function') showToast(data.message || 'Review submitted!', 'success');
                closeReviewModal();
                loadOrders();
            })
            .catch(err => {
                if (typeof showToast === 'function') showToast(err.message || 'Failed to submit review.', 'error');
            })
            .finally(() => { submitBtn.disabled = false; });
    });

    function maybeShowReviewBanner() {
        const params = new URLSearchParams(window.location.search);
        const wantsReview = params.get('review') === '1';
        const hasReviewable = allOrders.some(o =>
            (o.items || []).some(i => i.can_review)
        );
        const banner = document.getElementById('orders-review-banner');
        if (!banner || !hasReviewable) return;
        banner.style.display = '';
        if (wantsReview) {
            const first = document.querySelector('.order-item-row--reviewable');
            if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    document.getElementById('orders-review-banner-dismiss')?.addEventListener('click', function () {
        document.getElementById('orders-review-banner').style.display = 'none';
    });

    /* ── Helpers ──────────────────────────────────────────────── */
    function hideSkeleton() {
        const s = document.getElementById('orders-skeleton');
        if (s) s.style.display = 'none';
    }

    function showEmpty() {
        document.getElementById('orders-empty').style.display = '';
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /* ── Pay Now retry: open Razorpay modal for awaiting_payment orders ── */
    window.handleOrderPayNow = async function(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner"></i> Loading…';

        const orderId      = +btn.dataset.orderId;
        const orderNumber  = btn.dataset.orderNumber || ('Order #' + orderId);
        const csrf         = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers      = { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf };

        try {
            const rzpRes = await fetch('api/payment/razorpay-create-order.php', {
                method: 'POST', headers,
                body: JSON.stringify({ order_id: orderId })
            }).then(r => r.json());

            if (rzpRes.status !== 'success') throw new Error(rzpRes.message || 'Could not initiate payment.');
            const rzpData = rzpRes.data;

            if (!window.Razorpay) throw new Error('Payment gateway not loaded. Please refresh.');

            await new Promise((resolve, reject) => {
                const options = {
                    key:      rzpData.key_id,
                    amount:   rzpData.amount_paise,
                    currency: rzpData.currency || 'INR',
                    order_id: rzpData.razorpay_order_id,
                    name:     'UX Pacific Shop',
                    description: orderNumber,
                    handler: function(response) {
                        fetch('api/payment/razorpay-verify.php', {
                            method: 'POST', headers,
                            body: JSON.stringify({
                                razorpay_order_id:   response.razorpay_order_id,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_signature:  response.razorpay_signature,
                                order_id: orderId
                            })
                        }).then(r => r.json()).then(vRes => {
                            if (vRes.status !== 'success') { reject(new Error(vRes.message || 'Verification failed.')); return; }
                            resolve(vRes);
                        }).catch(reject);
                    },
                    modal: { ondismiss: () => reject(new Error('__dismissed__')) },
                    theme: { color: '#6d3dff' }
                };
                new window.Razorpay(options).open();
            });

            if (typeof showToast === 'function') showToast('Payment successful!', 'success');
            setTimeout(() => loadOrders(), 800);
        } catch (err) {
            if (err.message === '__dismissed__') {
                if (typeof showToast === 'function') showToast('Payment cancelled.', 'error');
            } else {
                console.error(err);
                if (typeof showToast === 'function') showToast(err.message || 'Payment failed.', 'error');
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-credit-card"></i> Pay Now';
        }
    };

    /* ── Filter pills event delegation ───────────────────────── */
    document.getElementById('orders-filters').addEventListener('click', function (e) {
        const pill = e.target.closest('.orders-filter-pill');
        if (pill) setFilter(pill.dataset.filter);
    });

    /* ── Boot ────────────────────────────────────────────────── */
    loadOrders();
})();
</script>
</body>
</html>
