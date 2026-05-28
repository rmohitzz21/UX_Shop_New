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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="assets/css/orders.css" />
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
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
            </div>
        </section>
    </main>

    <!-- ── Footer ── -->
    <footer class="site-footer">
        <div class="footer-main">
            <div class="footer-top">
                <div class="footer-brand">
                    <img src="img/logo1.webp" alt="UX Pacific" />
                    <p>Design resources and merchandise trusted by creators worldwide — built to be used, worn, and valued.</p>
                    <div class="footer-socials">
                        <a href="https://dribbble.com/social-ux-pacific" target="_blank" rel="noopener"><img src="img/bl.webp" alt="Dribbble" /></a>
                        <a href="https://www.instagram.com/official_uxpacific/" target="_blank" rel="noopener"><img src="img/i.webp" alt="Instagram" /></a>
                        <a href="https://www.linkedin.com/company/uxpacific/" target="_blank" rel="noopener"><img src="img/in1.png" alt="LinkedIn" /></a>
                        <a href="https://in.pinterest.com/uxpacific/" target="_blank" rel="noopener"><img src="img/p.webp" alt="Pinterest" /></a>
                        <a href="https://www.behance.net/ux_pacific" target="_blank" rel="noopener"><img src="img/be.webp" alt="Behance" /></a>
                    </div>
                </div>
                <div class="footer-contact">
                    <p>Support : +91 9274061063&nbsp;&nbsp;&nbsp;&nbsp;|</p>
                    <p>Email : <a href="https://mail.google.com/mail/?view=cm&fs=1&to=hello@uxpacific.com" style="text-decoration:none;color:inherit;" target="_blank">hello@uxpacific.com</a></p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>©2026 UXPacific. All rights reserved.</p>
            <div class="footer-links">
                <a href="policies.php">Our Policies</a>
                <span>•</span>
                <a href="contact.php" style="text-decoration:none;">Contact Us</a>
            </div>
        </div>
    </footer>
</div>

<script src="script.js"></script>
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

    function renderOrderCard(order) {
        const total = parseFloat(order.total) || 0;
        const isFreeOrder = total === 0;
        const statusSlug = (order.status || 'pending').toLowerCase().replace(/\s+/g, '_');

        const itemsHtml = (order.items || []).map(item => renderOrderItem(item)).join('');

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

            <div class="order-card-footer">
                <div class="order-total-block">
                    ${isFreeOrder
                        ? '<span class="order-total-free"><i class="ph ph-gift"></i> Free Order</span>'
                        : `<span class="order-total-label">Total</span><span class="order-total-amount">₹${fmt(total)}</span>`
                    }
                </div>
                <div class="order-card-actions">
                    <a href="checkout.php?reorder=${encodeURIComponent(order.orderNumber || order.id)}" class="btn-ghost small order-action-btn">
                        <i class="ph ph-arrow-clockwise"></i> Reorder
                    </a>
                </div>
            </div>
        </div>`;
    }

    function renderOrderItem(item) {
        const price = parseFloat(item.price) || 0;
        const qty   = parseInt(item.quantity) || 1;
        const isFree = price === 0;
        const itemTotal = price * qty;

        const img = escHtml(item.image || 'img/poster.webp');
        const name = escHtml(item.name || 'Item');
        const type = (item.item_type || 'product').toLowerCase();
        const typeLabel = type === 'bundle' ? 'Bundle' : 'Product';

        const priceBadgeClass = isFree ? 'item-type-badge item-type-badge--free' : 'item-type-badge item-type-badge--paid';
        const priceBadgeText  = isFree ? 'FREE' : '₹' + fmt(itemTotal);

        const sizeText = item.size ? `<span class="item-meta-chip">Size: ${escHtml(String(item.size))}</span>` : '';
        const typeChip = `<span class="item-meta-chip item-meta-chip--type">${typeLabel}</span>`;

        return `
        <div class="order-item-row">
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
            </div>
            <div class="order-item-price-block">
                <span class="${priceBadgeClass}">${priceBadgeText}</span>
            </div>
        </div>`;
    }

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
