<?php
require_once '../includes/config.php';
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$adminEmail = htmlspecialchars($_SESSION['admin_email'] ?? 'Admin');
$adminInitial = strtoupper(substr($adminEmail, 0, 1));
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard – UX Pacific Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
  <link rel="icon" type="image/png" href="../img/fav.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="admin.css" />
</head>

<body>
<div class="adm" id="adm-root">

  <!-- Mobile Overlay -->
  <div class="sb-overlay" id="sb-overlay" onclick="closeSidebar()"></div>

  <!-- ═══════════════════════════════════════════════════
       SIDEBAR
  ═══════════════════════════════════════════════════ -->
  <aside class="adm-sidebar" id="adm-sidebar">

    <a href="../index.php" class="sb-logo">
      <img src="../img/logo1.webp" alt="UX Pacific" onerror="this.style.display='none'" />
      <!-- <span class="sb-logo-name">UX Pacific</span> -->
    </a>

    <nav class="sb-nav">

      <div class="sb-section-label">Main</div>

      <a href="#" class="sb-item active" data-tab="overview" data-tooltip="Overview" onclick="switchTab('overview',this)">
        <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
          <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
        </svg>
        <span class="sb-label">Overview</span>
      </a>

      <a href="#" class="sb-item" data-tab="analytics" data-tooltip="Analytics" onclick="switchTab('analytics',this)">
        <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        <span class="sb-label">Analytics</span>
      </a>

      <div class="sb-section-label">Catalog</div>

      <a href="#" class="sb-item" data-tab="products" data-tooltip="Products" onclick="switchTab('products',this)">
        <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
          <line x1="12" y1="22.08" x2="12" y2="12"/>
        </svg>
        <span class="sb-label">Products</span>
      </a>

      <a href="#" class="sb-item" data-tab="bundles" data-tooltip="Bundles" onclick="switchTab('bundles',this)">
        <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
        </svg>
        <span class="sb-label">Bundles</span>
      </a>

      <a href="#" class="sb-item" data-tab="categories" data-tooltip="Categories" onclick="switchTab('categories',this)">
        <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 4h7v7H4z"/><path d="M13 4h7v7h-7z"/><path d="M4 13h7v7H4z"/><path d="M13 13h7v7h-7z"/>
        </svg>
        <span class="sb-label">Categories</span>
      </a>

      <div class="sb-section-label">Customers</div>

      <a href="#" class="sb-item" data-tab="orders" data-tooltip="Orders" onclick="switchTab('orders',this)">
        <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
          <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
          <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>
        </svg>
        <span class="sb-label">Orders</span>
      </a>

      <a href="#" class="sb-item" data-tab="users" data-tooltip="Users" onclick="switchTab('users',this)">
        <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <span class="sb-label">Users</span>
      </a>

      <div class="sb-section-label">Engagement</div>

      <a href="#" class="sb-item" data-tab="reviews" data-tooltip="Reviews" onclick="switchTab('reviews',this)">
        <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
        <span class="sb-label">Reviews</span>
      </a>

      <a href="#" class="sb-item" data-tab="messages" data-tooltip="Messages" onclick="switchTab('messages',this)">
        <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
          <polyline points="22,6 12,13 2,6"/>
        </svg>
        <span class="sb-label">Messages</span>
      </a>

      <div class="sb-section-label">Resources</div>

      <a href="#" class="sb-item" data-tab="freebies" data-tooltip="Freebies" onclick="switchTab('freebies',this)">
        <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/>
          <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        <span class="sb-label">Freebies</span>
      </a>

    </nav>

    <div class="sb-footer">
      <button class="sb-collapse-btn" onclick="toggleSidebarCollapse()" aria-label="Collapse sidebar">
        <svg class="sb-collapse-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>
        </svg>
        <span class="sb-collapse-label" style="font-size:.78rem;">Collapse</span>
      </button>
    </div>
  </aside>

  <!-- ═══════════════════════════════════════════════════
       MAIN
  ═══════════════════════════════════════════════════ -->
  <main class="adm-main" id="adm-main">

    <!-- TOPBAR -->
    <header class="adm-topbar">
      <button class="topbar-mobile-btn" onclick="openSidebar()" aria-label="Open sidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>

      <div class="topbar-title" id="topbar-title">Overview</div>

      <div class="topbar-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" placeholder="Quick search…" id="topbar-search-input" oninput="handleTopbarSearch(this.value)" />
      </div>

      <div class="topbar-actions">
        <!-- Theme toggle -->
        <button class="tb-btn" onclick="toggleTheme()" id="theme-toggle" aria-label="Toggle theme" title="Toggle theme">
          <svg id="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
            <circle cx="12" cy="12" r="5"/>
            <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
            <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
          </svg>
          <svg id="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
          </svg>
        </button>

        <!-- Admin info -->
        <div class="tb-avatar" title="<?php echo $adminEmail; ?>"><?php echo $adminInitial; ?></div>
        <div class="tb-admin-info">
          <span class="tb-admin-name" id="admin-email-display"><?php echo $adminEmail; ?></span>
          <span class="tb-admin-role">Administrator</span>
        </div>

        <!-- Logout -->
        <button class="tb-logout" onclick="handleAdminLogout()">
          <svg style="width:13px;height:13px;display:inline;margin-right:4px;vertical-align:-1px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
          Logout
        </button>
      </div>
    </header>

    <!-- ─── CONTENT ─── -->
    <div class="adm-content">

      <!-- ══════════════ OVERVIEW TAB ══════════════ -->
      <div class="adm-tab active" id="overview-tab">
        <div class="page-header">
          <div>
            <h1>Dashboard Overview</h1>
            <p>Welcome back — here's what's happening in your store today.</p>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
          <a href="#" class="qa-card" onclick="switchTab('products',document.querySelector('[data-tab=products]'));openCreateProductModal();return false;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Product
          </a>
          <a href="#" class="qa-card" onclick="switchTab('orders',document.querySelector('[data-tab=orders]'));return false;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
            Manage Orders
          </a>
          <a href="../index.php" target="_blank" class="qa-card">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            View Store
          </a>
          <a href="#" class="qa-card" onclick="switchTab('bundles',document.querySelector('[data-tab=bundles]'));return false;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
            Add Bundle
          </a>
        </div>

        <!-- Stat Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon si-purple">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-body">
              <div class="stat-title">Total Customers</div>
              <div class="stat-value" id="stat-total-users">—</div>
              <span class="stat-change neutral" id="stat-users-change">Loading</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-blue">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
            <div class="stat-body">
              <div class="stat-title">Total Products</div>
              <div class="stat-value" id="stat-total-products">—</div>
              <span class="stat-change neutral" id="stat-products-change">Loading</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-cyan">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
            </div>
            <div class="stat-body">
              <div class="stat-title">Total Orders</div>
              <div class="stat-value" id="stat-total-orders">—</div>
              <span class="stat-change neutral" id="stat-orders-change">Loading</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-green">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="stat-body">
              <div class="stat-title">Total Revenue</div>
              <div class="stat-value" id="stat-total-revenue">—</div>
              <span class="stat-change neutral" id="stat-revenue-change">Loading</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-orange">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-body">
              <div class="stat-title">Pending Orders</div>
              <div class="stat-value" id="stat-pending-orders">—</div>
              <span class="stat-change warn">Awaiting action</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-red">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div class="stat-body">
              <div class="stat-title">Low Stock Items</div>
              <div class="stat-value" id="stat-low-stock">—</div>
              <span class="stat-change bad">Stock &le; 5 units</span>
            </div>
          </div>
          <div class="stat-card" style="cursor:pointer;" onclick="switchTab('messages',document.querySelector('[data-tab=messages]')); return false;" title="Open Messages inbox">
            <div class="stat-icon si-purple">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div class="stat-body">
              <div class="stat-title">Unread Messages</div>
              <div class="stat-value" id="stat-unread-messages">—</div>
              <span class="stat-change neutral">Contact form inbox</span>
            </div>
          </div>
        </div>

        <!-- Recent Orders + Top Products -->
        <div class="gap-grid">
          <div class="panel">
            <div class="panel-head">
              <div><h2>Recent Orders</h2><p>Latest transactions</p></div>
              <a href="#" class="btn btn-ghost btn-xs" onclick="switchTab('orders',document.querySelector('[data-tab=orders]'));return false;">
                View all
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
              </a>
            </div>
            <div class="tbl-wrap">
              <table class="tbl">
                <thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody id="recent-orders-table">
                  <tr><td colspan="4" class="tbl-empty">Loading…</td></tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="panel">
            <div class="panel-head">
              <div><h2>Top Products</h2><p>By units sold</p></div>
            </div>
            <div class="tbl-wrap">
              <table class="tbl">
                <thead><tr><th>Product</th><th>Units</th><th>Revenue</th></tr></thead>
                <tbody id="top-products-table">
                  <tr><td colspan="3" class="tbl-empty">Loading…</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div><!-- /overview-tab -->

      <!-- ══════════════ ANALYTICS TAB ══════════════ -->
      <div class="adm-tab" id="analytics-tab">
        <div class="page-header">
          <div>
            <h1>Analytics</h1>
            <p>Revenue and performance breakdown</p>
          </div>
        </div>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon si-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div class="stat-body">
              <div class="stat-title">Today's Revenue</div>
              <div class="stat-value" id="analytics-today-revenue">—</div>
              <span class="stat-change neutral"><span id="analytics-today-orders">0</span> paid orders today</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
            <div class="stat-body">
              <div class="stat-title">This Month (paid)</div>
              <div class="stat-value" id="analytics-month-revenue">—</div>
              <span class="stat-change neutral" id="analytics-revenue-change">Loading</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div class="stat-body">
              <div class="stat-title">Avg Order (this month)</div>
              <div class="stat-value" id="analytics-avg-order">—</div>
              <span class="stat-change neutral"><span id="analytics-month-orders">0</span> paid orders this month</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-cyan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
            <div class="stat-body">
              <div class="stat-title">Customer Conversion</div>
              <div class="stat-value" id="analytics-conversion">—</div>
              <span class="stat-change neutral" id="analytics-conversion-hint">Paid-order customers ÷ total customers</span>
            </div>
          </div>
        </div>
        <div class="panel" style="margin-top:8px;">
          <div class="panel-head"><div><h2>Top Sellers</h2><p>Products and bundles from paid orders only</p></div></div>
          <div class="tbl-wrap">
            <table class="tbl">
              <thead><tr><th>Product</th><th>Units Sold</th><th>Revenue (₹)</th></tr></thead>
              <tbody id="analytics-top-products-table"><tr><td colspan="3" class="tbl-empty">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </div><!-- /analytics-tab -->

      <!-- ══════════════ PRODUCTS TAB ══════════════ -->
      <div class="adm-tab" id="products-tab">
        <div class="page-header">
          <div>
            <h1>Products</h1>
            <p>Manage your catalog</p>
          </div>
        </div>
        <div class="panel">
          <div class="panel-head">
            <div><h2>All Products</h2></div>
            <div class="panel-filters">
              <div class="f-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="product-search" placeholder="Search products…" oninput="filterProducts()" />
              </div>
              <select class="f-select" id="product-category-filter" onchange="filterProducts()">
                <option value="">All Categories</option>
                <option value="T-Shirts">T-Shirts</option>
                <option value="Stickers">Stickers</option>
                <option value="Booklet">Booklet</option>
                <option value="Workbook">Workbook</option>
                <option value="Mockup">Mockup</option>
                <option value="Badges">Badges</option>
                <option value="Template">UI Template</option>
              </select>
              <button class="btn btn-primary" onclick="openCreateProductModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Product
              </button>
            </div>
          </div>
          <div class="tbl-wrap">
            <table class="tbl">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Category</th>
                  <th>Price</th>
                  <th>Purchases</th>
                  <th>Rating</th>
                  <th>Status</th>
                  <th style="width:1%">Actions</th>
                </tr>
              </thead>
              <tbody id="products-table">
                <tr><td colspan="7" class="tbl-empty">Loading products…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /products-tab -->

      <!-- ══════════════ BUNDLES TAB ══════════════ -->
      <div class="adm-tab" id="bundles-tab">
        <div class="page-header">
          <div>
            <h1>Bundles</h1>
            <p>Create and manage bundle packages</p>
          </div>
          <button class="btn btn-primary" type="button" onclick="openBundleForm()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Bundle
          </button>
        </div>

        <!-- Add / Edit Bundle Form Panel (hidden by default) -->
        <div class="panel adm-collapsible-form" id="bundle-form-panel" style="display:none;">
          <div class="panel-head">
            <div>
              <h2 id="bundle-form-title">Add New Bundle</h2>
              <p>Fields marked <span class="req">*</span> are required</p>
            </div>
            <button type="button" class="btn btn-ghost btn-xs" onclick="closeBundleForm()" aria-label="Close form">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <form id="bundle-editor-form" onsubmit="adminSaveBundle(event)" enctype="multipart/form-data">
            <input type="hidden" name="id" value="">
            <input type="hidden" name="existing_image" value="">
            <div class="panel-body">

              <!-- Section: Basic Info -->
              <div class="form-section-label">Basic Information</div>
              <div class="form-grid">
                <div class="form-group form-col-full">
                  <label class="form-label">Bundle Name <span class="req">*</span></label>
                  <input class="form-input" name="name" placeholder="e.g. Ultimate UI/UX Career Bundle" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Price (₹) <span class="req">*</span></label>
                  <input class="form-input" name="price" type="number" step="0.01" min="0" placeholder="1499" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Compare-at Price (₹) <span class="form-help">shows strikethrough</span></label>
                  <input class="form-input" name="old_price" type="number" step="0.01" min="0" placeholder="2999" />
                </div>
                <div class="form-group">
                  <label class="form-label">Category</label>
                  <input class="form-input" name="category" placeholder="e.g. Career Pack" />
                </div>
                <div class="form-group">
                  <label class="form-label">Badge Text <span class="form-help">card label (auto if blank)</span></label>
                  <input class="form-input" name="badge_text" placeholder="Best Seller" />
                </div>
                <div class="form-group">
                  <label class="form-label">Stock Quantity</label>
                  <input class="form-input" name="stock" type="number" min="0" placeholder="999" />
                </div>
                <div class="form-group">
                  <label class="form-label">Rating (0–5)</label>
                  <input class="form-input" name="rating" type="number" step="0.1" min="0" max="5" placeholder="4.7" />
                </div>
                <div class="form-group">
                  <label class="form-label">Tags <span class="form-help">comma-separated</span></label>
                  <input class="form-input" name="tags" placeholder="design, ux, career" />
                </div>
                <div class="form-group">
                  <label class="form-label">Product IDs to link <span class="form-help">leave blank to keep existing</span></label>
                  <input class="form-input" name="product_ids" placeholder="1, 2, 3" />
                </div>
                <div class="form-group form-col-full">
                  <label class="form-label">Description</label>
                  <textarea class="form-textarea" name="description" rows="3" placeholder="What makes this bundle valuable? Who is it for?"></textarea>
                </div>
              </div>

              <!-- Section: Bundle Contents -->
              <div class="form-section-label" style="margin-top:20px;">Bundle Contents</div>
              <div class="form-grid">
                <div class="form-group form-col-full">
                  <label class="form-label">What's Included <span class="req">*</span> <span class="form-help">one item per line — displayed in popup &amp; cards</span></label>
                  <textarea class="form-textarea" name="whats_included" rows="6" required placeholder="15+ UI Screens&#10;UX Workbook&#10;Interview Prep Guide&#10;Portfolio Templates&#10;Resume &amp; Cover Letter Kit&#10;LinkedIn Banner Set"></textarea>
                </div>
                <div class="form-group form-col-full">
                  <label class="form-label">File Specifications <span class="form-help">one per line — e.g. file types, resolution, format</span></label>
                  <textarea class="form-textarea" name="file_specification" rows="4" placeholder="AI (Adobe Illustrator)&#10;PDF (Print-ready)&#10;PNG (300 DPI)&#10;Figma source file"></textarea>
                </div>
              </div>

              <!-- Section: Media -->
              <div class="form-section-label" style="margin-top:20px;">Media</div>
              <div class="form-grid">
                <div class="form-group form-col-full">
                  <label class="form-label">Cover Image</label>
                  <span class="form-help">Primary image on bundle cards and popup</span>
                  <input class="form-input" id="bundle-cover-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" />
                  <div id="bundle-main-preview" class="product-media-preview product-media-preview--main"></div>
                </div>
                <div class="form-group form-col-full">
                  <label class="form-label">Gallery Images</label>
                  <span class="form-help">Select multiple files (JPG, PNG, WebP, GIF — max 5MB each). Shown in the bundle popup gallery.</span>
                  <input class="form-input" id="bundle-gallery-input" name="media[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple />
                  <input type="hidden" name="additional_images" id="bundle-additional-images" value="[]" />
                  <div id="bundle-gallery-preview" class="product-media-preview product-media-gallery"></div>
                  <div id="bundle-gallery-pending" class="product-media-pending"></div>
                </div>
              </div>

              <!-- Digital Resources -->
              <div class="form-section-label" style="margin-top:20px;">Digital Resources</div>
              <p class="form-hint" style="margin:0 0 12px;color:#94a3b8;font-size:0.875rem;">
                Upload PDF/ZIP to private storage. Canva/Figma links must be HTTPS. Users receive download tokens after purchase.
              </p>
              <div id="bundle-resources-list"><p class="form-hint">Save the bundle first, then add digital resources.</p></div>
              <button type="button" class="btn-ghost small" id="bundle-resource-add-btn">+ Add resource</button>

              <div class="form-actions">
                <div class="form-actions-left">
                  <label class="toggle-wrap">
                    <span class="toggle"><input type="checkbox" name="is_active" value="1" checked><span class="toggle-track"></span></span>
                    <span class="toggle-label">Active</span>
                  </label>
                  <label class="toggle-wrap">
                    <span class="toggle"><input type="checkbox" name="is_featured" value="1"><span class="toggle-track"></span></span>
                    <span class="toggle-label">Best Seller</span>
                  </label>
                </div>
                <div class="form-actions-right">
                  <button id="bundle-cancel-btn" class="btn btn-ghost" type="button" style="display:none;" onclick="adminCancelBundleEdit()">Cancel</button>
                  <button id="bundle-submit-btn" class="btn btn-primary" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Bundle
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>

        <p class="panel-note">
          <svg style="width:13px;height:13px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Bundles marked <strong>Best Seller</strong> appear in the featured slider on <code>bundles.php</code>.
        </p>

        <!-- Bundle List Panel -->
        <div class="panel">
          <div class="panel-head">
            <div><h2>All Bundles</h2><p>Click Edit to modify a bundle</p></div>
          </div>
          <div class="tbl-wrap">
            <table class="tbl">
              <thead>
                <tr>
                  <th>Bundle</th>
                  <th>Price</th>
                  <th>Best Seller</th>
                  <th>Status</th>
                  <th style="width:1%">Actions</th>
                </tr>
              </thead>
              <tbody id="bundles-table">
                <tr><td colspan="5" class="tbl-empty">Loading bundles…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /bundles-tab -->

      <!-- ══════════════ CATEGORIES TAB ══════════════ -->
      <div class="adm-tab" id="categories-tab">
        <div class="page-header">
          <div>
            <h1>Categories</h1>
            <p>Organise your product catalog</p>
          </div>
          <button class="btn btn-primary" type="button" onclick="openCategoryForm()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Category
          </button>
        </div>

        <!-- Add Category Form Panel (hidden by default) -->
        <div class="panel adm-collapsible-form" id="category-form-panel" style="display:none;">
          <div class="panel-head">
            <div>
              <h2 id="category-form-title">Add New Category</h2>
              <p>Create a new category for your products</p>
            </div>
            <button type="button" class="btn btn-ghost btn-xs" onclick="closeCategoryForm()" aria-label="Close form">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <form onsubmit="adminSaveCategory(event)" enctype="multipart/form-data">
            <input type="hidden" name="id" id="category-id" value="" />
            <input type="hidden" name="slug" id="category-slug" value="" />
            <input type="hidden" name="existing_icon" id="category-existing-icon" value="" />
            <div class="panel-body">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Category Name <span class="req">*</span></label>
                  <input class="form-input" name="name" placeholder="e.g. T-Shirts" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Accent Colour</label>
                  <select class="form-select" name="accent">
                    <option value="purple">Purple</option>
                    <option value="green">Green</option>
                    <option value="pink">Pink</option>
                    <option value="orange">Orange</option>
                    <option value="blue">Blue</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Sort Order</label>
                  <input class="form-input" name="sort_order" type="number" min="0" placeholder="0" />
                </div>
                <div class="form-group form-col-full">
                  <label class="form-label">Icon / Image</label>
                  <span class="form-help">Optional category icon (JPG, PNG, WebP, GIF — max 5MB)</span>
                  <input class="form-input" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" />
                  <div id="category-icon-preview" class="product-media-preview"></div>
                </div>
                <div class="form-group form-col-full">
                  <label class="form-label">Description</label>
                  <input class="form-input" name="description" placeholder="Brief description of this category" />
                </div>
              </div>
              <div class="form-actions">
                <div></div>
                <div class="form-actions-right">
                  <button class="btn btn-primary" type="submit" id="category-submit-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Category
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- Category List Panel -->
        <div class="panel">
          <div class="panel-head">
            <div><h2>All Categories</h2><p>Manage your product categories</p></div>
          </div>
          <div class="tbl-wrap">
            <table class="tbl">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Slug</th>
                  <th>Description</th>
                  <th>Status</th>
                  <th style="width:1%">Actions</th>
                </tr>
              </thead>
              <tbody id="categories-table">
                <tr><td colspan="5" class="tbl-empty">Loading categories…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /categories-tab -->

      <!-- ══════════════ ORDERS TAB ══════════════ -->
      <div class="adm-tab" id="orders-tab">
        <div class="page-header">
          <div>
            <h1>Orders</h1>
            <p>Track and manage customer orders</p>
          </div>
        </div>
        <div class="panel">
          <div class="panel-head">
            <div><h2>All Orders</h2></div>
            <div class="panel-filters">
              <div class="f-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="order-search" placeholder="Search orders…" oninput="filterOrders()" />
              </div>
              <select class="f-select" id="order-status-filter" onchange="filterOrders()">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="awaiting_payment">Awaiting Payment</option>
                <option value="paid">Paid</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="failed">Failed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>
          <div class="tbl-wrap">
            <table class="tbl">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Items</th>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Payment</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="orders-table">
                <tr><td colspan="8" class="tbl-empty">Loading orders…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /orders-tab -->

      <!-- ══════════════ USERS TAB ══════════════ -->
      <div class="adm-tab" id="users-tab">
        <div class="page-header">
          <div>
            <h1>Users</h1>
            <p>Manage customer accounts</p>
          </div>
        </div>
        <div class="panel">
          <div class="panel-head">
            <div><h2>All Users</h2></div>
            <div class="panel-filters">
              <div class="f-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="user-search" placeholder="Search users…" oninput="filterUsers()" />
              </div>
            </div>
          </div>
          <div class="tbl-wrap">
            <table class="tbl">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Registered</th>
                  <th>Orders</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="users-table">
                <tr><td colspan="7" class="tbl-empty">Loading users…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /users-tab -->

      <!-- ══════════════ REVIEWS TAB ══════════════ -->
      <div class="adm-tab" id="reviews-tab">
        <div class="page-header">
          <div>
            <h1>Reviews</h1>
            <p>Moderate customer reviews</p>
          </div>
        </div>
        <div class="panel">
          <div class="panel-head">
            <div><h2>All Reviews</h2><p>Approve or remove customer reviews (products and bundles)</p></div>
            <div class="panel-filters">
              <div class="f-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="review-search" placeholder="Search reviews…" oninput="filterReviews()" />
              </div>
              <select class="f-select" id="review-status-filter" onchange="filterReviews()">
                <option value="">All status</option>
                <option value="0">Pending</option>
                <option value="1">Approved</option>
              </select>
            </div>
          </div>
          <div class="tbl-wrap">
            <table class="tbl">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Customer</th>
                  <th>Rating</th>
                  <th>Comment</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="reviews-table">
                <tr><td colspan="6" class="tbl-empty">Loading reviews…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /reviews-tab -->

      <!-- ══════════════ MESSAGES TAB ══════════════ -->
      <div class="adm-tab" id="messages-tab">
        <div class="page-header">
          <div>
            <h1>Messages</h1>
            <p>Contact form inbox</p>
          </div>
        </div>
        <div class="panel">
          <div class="panel-head">
            <div><h2>Contact Messages</h2><p>Messages sent through your contact form</p></div>
            <div class="panel-filters">
              <div class="f-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="message-search" placeholder="Search messages…" oninput="filterMessages()" />
              </div>
              <select class="f-select" id="message-status-filter" onchange="filterMessages()">
                <option value="">All</option>
                <option value="0">Unread</option>
                <option value="1">Read</option>
              </select>
            </div>
          </div>
          <div class="tbl-wrap">
            <table class="tbl">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Subject</th>
                  <th>Message</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="messages-table">
                <tr><td colspan="6" class="tbl-empty">Loading messages…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /messages-tab -->

      <!-- ══════════════ FREEBIES TAB ══════════════ -->
      <div class="adm-tab" id="freebies-tab">
        <div class="page-header">
          <div>
            <h1>Freebies</h1>
            <p>Figma files, Canva templates &amp; free downloads for visitors</p>
          </div>
          <button class="btn btn-primary" type="button" onclick="openFreebiesForm()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Freebie
          </button>
        </div>

        <!-- Add / Edit Freebie Form Panel -->
        <div class="panel adm-collapsible-form" id="freebie-form-panel" style="display:none;">
          <div class="panel-head">
            <div>
              <h2 id="freebie-form-title">Add New Freebie</h2>
              <p>Add a Figma file, Canva template, or any downloadable resource link — visible to all visitors for free</p>
            </div>
            <button type="button" class="btn btn-ghost btn-xs" onclick="closeFreebiesForm()" aria-label="Close form">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <form onsubmit="adminSaveFreebies(event)" enctype="multipart/form-data" id="freebie-form">
            <input type="hidden" name="id" id="freebie-id" value="0" />
            <input type="hidden" name="existing_image" id="freebie-existing-image" value="" />
            <div class="panel-body">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Name <span class="req">*</span></label>
                  <input class="form-input" name="name" id="freebie-name" placeholder="e.g. UI Kit Pro" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Category</label>
                  <input class="form-input" name="category" id="freebie-category" placeholder="e.g. Figma UI Kit, Canva Template, Icons" />
                </div>
                <div class="form-group form-col-full">
                  <label class="form-label">Legacy link (optional)</label>
                  <input class="form-input" name="file_url" id="freebie-file-url" type="url" placeholder="https://www.figma.com/… (optional — prefer Digital Resources below)" />
                  <small style="color:var(--text-2);font-size:.75rem;margin-top:4px;display:block;">For external Figma/Canva links only. Uploaded files use encrypted private storage via Digital Resources.</small>
                </div>
                <div class="form-group">
                  <label class="form-label">Sort Order</label>
                  <input class="form-input" name="sort_order" id="freebie-sort-order" type="number" min="0" placeholder="0" />
                </div>
                <div class="form-group form-col-full">
                  <label class="form-label">Cover Image</label>
                  <span class="form-help">Optional thumbnail (JPG, PNG, WebP, GIF — max 5MB)</span>
                  <input class="form-input" id="freebie-cover-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" />
                  <div id="freebie-image-preview" class="product-media-preview"></div>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:16px;padding-top:28px;">
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.875rem;">
                    <input type="checkbox" name="is_active" id="freebie-is-active" value="1" checked style="accent-color:var(--accent);width:16px;height:16px;" />
                    Active
                  </label>
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.875rem;">
                    <input type="checkbox" name="is_featured" id="freebie-is-featured" value="1" style="accent-color:var(--accent);width:16px;height:16px;" />
                    Featured
                  </label>
                </div>
                <div class="form-group form-col-full">
                  <label class="form-label">Description</label>
                  <textarea class="form-input" name="description" id="freebie-description" rows="3" placeholder="Brief description of this resource" style="resize:vertical;"></textarea>
                </div>
              </div>

              <div class="form-section" id="freebie-digital-resources-section" style="margin-top:24px;padding-top:24px;border-top:1px solid var(--border);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
                  <div>
                    <h3 style="margin:0;font-size:1rem;">Digital Resources</h3>
                    <p class="form-hint" style="margin:4px 0 0;">Secure encrypted files, HTTPS links, or instructions — delivered after free checkout.</p>
                  </div>
                  <button class="btn btn-ghost btn-sm" type="button" id="freebie-resource-add-btn">+ Add resource</button>
                </div>
                <div id="freebie-resources-list" class="adm-resources-list">
                  <p class="form-hint">Save the freebie first, then add digital resources.</p>
                </div>
              </div>

              <div class="form-actions">
                <div></div>
                <div class="form-actions-right">
                  <button class="btn btn-ghost" type="button" onclick="closeFreebiesForm()">Cancel</button>
                  <button class="btn btn-primary" type="submit" id="freebie-submit-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Freebie
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- Freebie List Panel -->
        <div class="panel">
          <div class="panel-head">
            <div><h2>All Freebies</h2><p>Figma files, Canva templates, and other free resources available to all visitors</p></div>
            <div class="panel-filters">
              <input class="form-input" style="max-width:200px;" type="search" placeholder="Search freebies…" oninput="filterAdminFreebies(this.value)" />
            </div>
          </div>
          <div class="tbl-wrap">
            <table class="tbl">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Category</th>
                  <th>Downloads</th>
                  <th>Status</th>
                  <th style="width:1%">Actions</th>
                </tr>
              </thead>
              <tbody id="freebies-table">
                <tr><td colspan="5" class="tbl-empty">Loading freebies…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /freebies-tab -->

    </div><!-- /adm-content -->
  </main><!-- /adm-main -->

</div><!-- /adm -->

<!-- ═══════════════════════════════════════════════════
     TOAST HOST
═══════════════════════════════════════════════════ -->
<div id="toast-host"></div>

<!-- ═══════════════════════════════════════════════════
     ORDER DETAILS MODAL
═══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="order-details-modal-overlay" onclick="closeOrderDetailsModal()">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h2>Order Details</h2>
      <button class="modal-close" onclick="closeOrderDetailsModal()" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body" id="order-details-content">
      <div class="tbl-empty">Loading order…</div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeOrderDetailsModal()">Close</button>
      <button class="btn btn-primary" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     ORDER STATUS MODAL
═══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="status-modal-overlay" onclick="closeStatusModal()">
  <div class="modal modal-sm" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h2>Update Order Status</h2>
      <button class="modal-close" onclick="closeStatusModal()" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="order-info-grid" style="grid-template-columns:1fr;">
        <div class="order-info-block">
          <input type="hidden" id="modal-order-id" value="" />
          <p><strong>Order:</strong> <span id="modal-order-number">—</span></p>
          <p style="margin-top:6px;"><strong>Customer:</strong> <span id="modal-order-customer">—</span></p>
          <p style="margin-top:6px;"><strong>Current Status:</strong> <span id="modal-current-status">—</span></p>
        </div>
      </div>
      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">New Status <span class="req">*</span></label>
        <select class="form-select" id="status-select">
          <option value="">Select status…</option>
          <option value="pending">Pending</option>
          <option value="awaiting_payment">Awaiting Payment</option>
          <option value="paid">Paid</option>
          <option value="processing">Processing</option>
          <option value="shipped">Shipped</option>
          <option value="delivered">Delivered</option>
          <option value="failed">Failed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeStatusModal()">Cancel</button>
      <button class="btn btn-primary" onclick="confirmStatusUpdate()">Update Status</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     EDIT / ADD PRODUCT MODAL
═══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="edit-product-modal-overlay" onclick="closeEditProductModal()">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h2 id="product-modal-title">Add Product</h2>
      <button class="modal-close" onclick="closeEditProductModal()" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form id="edit-product-form" onsubmit="handleUpdateProduct(event)">
        <input type="hidden" id="edit-product-id" name="id">
        <input type="hidden" id="edit-product-existing-image" name="existing_image">

        <!-- Basic Info -->
        <div class="form-section">
          <div class="form-section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Basic Information
          </div>
          <div class="form-grid">
            <div class="form-group form-col-full">
              <label class="form-label">Product Name <span class="req">*</span></label>
              <input class="form-input" id="edit-product-name" name="name" required placeholder="e.g. UX Sticker Pack" />
            </div>
            <div class="form-group">
              <label class="form-label">SKU</label>
              <input class="form-input" id="edit-product-sku" name="sku" placeholder="UXP-001" />
            </div>
            <div class="form-group">
              <label class="form-label">Category <span class="req">*</span></label>
              <select class="form-select" id="edit-product-category" name="category" required>
                <option value="T-Shirts">T-Shirts</option>
                <option value="Stickers">Stickers</option>
                <option value="Booklet">Booklet</option>
                <option value="Workbook">Workbook</option>
                <option value="Mockup">Mockup</option>
                <option value="Badges">Badges</option>
                <option value="Template">UI Template</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Type</label>
              <select class="form-select" id="edit-product-available" name="available_type">
                <option value="digital">Digital</option>
                <option value="physical">Physical</option>
                <option value="both">Both</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Rating (0–5)</label>
              <input class="form-input" id="edit-product-rating" name="rating" type="number" step="0.1" min="0" max="5" placeholder="4.5" />
            </div>
            <div class="form-group form-col-full">
              <label class="form-label">Description</label>
              <textarea class="form-textarea" id="edit-product-description" name="description" placeholder="Describe the product…"></textarea>
            </div>
            <div class="form-group form-col-full">
              <label class="form-label">What's Included</label>
              <textarea class="form-textarea" id="edit-product-whats" name="whats_included" placeholder="List what's included…"></textarea>
            </div>
            <div class="form-group form-col-full">
              <label class="form-label">Specifications</label>
              <textarea class="form-textarea" id="edit-product-specs" name="file_specification" placeholder="File specs, dimensions…"></textarea>
            </div>
            <div class="form-group form-col-full">
              <label class="form-label">Tags</label>
              <input class="form-input" id="edit-product-tags" name="tags" placeholder="ux, design, sticker" />
            </div>
          </div>
        </div>

        <!-- Product Information (quick view) -->
        <div class="form-section">
          <div class="form-section-title">Product Information</div>
          <p class="form-hint" style="margin:0 0 12px;color:#94a3b8;font-size:0.875rem;">
            Shown in the quick view modal for this product. Leave blank to use shop-wide defaults from Shop Settings.
          </p>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="edit-product-high-resolution">High Resolution</label>
              <input class="form-input" id="edit-product-high-resolution" name="high_resolution" placeholder="Yes" />
            </div>
            <div class="form-group">
              <label class="form-label" for="edit-product-software-version">Software Version</label>
              <input class="form-input" id="edit-product-software-version" name="software_version" placeholder="Latest" />
            </div>
            <div class="form-group form-col-full">
              <label class="form-label" for="edit-product-compatible-software">Compatible With</label>
              <input class="form-input" id="edit-product-compatible-software" name="compatible_software" placeholder="Figma, Adobe XD, Sketch" />
            </div>
            <div class="form-group">
              <label class="form-label" for="edit-product-files-included">Files Included</label>
              <input class="form-input" id="edit-product-files-included" name="files_included" placeholder="FIG, PNG, PDF, SVG" />
            </div>
            <div class="form-group">
              <label class="form-label" for="edit-product-grid-columns">Column</label>
              <input class="form-input" id="edit-product-grid-columns" name="grid_columns" placeholder="12 Column" />
            </div>
            <div class="form-group">
              <label class="form-label" for="edit-product-layout-type">Layout</label>
              <input class="form-input" id="edit-product-layout-type" name="layout_type" placeholder="Responsive" />
            </div>
            <div class="form-group">
              <label class="form-label" for="edit-product-license-type">License</label>
              <input class="form-input" id="edit-product-license-type" name="license_type" placeholder="Premium" />
            </div>
          </div>

          <div class="panel-subsection">
            <div class="panel-subsection-head">
              <div>
                <h3>Custom fields</h3>
                <p>Add extra label/value rows shown in quick view for this product only.</p>
              </div>
              <button type="button" class="btn btn-ghost btn-xs" id="product-add-custom-field">+ Add field</button>
            </div>
            <div id="product-custom-fields" class="qv-custom-rows"></div>
            <input type="hidden" id="edit-product-custom-fields" name="custom_fields" value="[]" />
          </div>
        </div>

        <!-- Digital Resources (digital / both products) -->
        <div class="form-section" id="product-digital-resources-section" style="display:none;">
          <div class="form-section-title">Digital Resources</div>
          <p class="form-hint" style="margin:0 0 12px;color:#94a3b8;font-size:0.875rem;">
            Upload PDF/ZIP to private storage. Canva/Figma links must be HTTPS. Users can reshare external template links after purchase.
          </p>
          <div id="product-resources-list"></div>
          <button type="button" class="btn-ghost small" id="product-resource-add-btn">+ Add resource</button>
        </div>

        <!-- Pricing -->
        <div class="form-section">
          <div class="form-section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Pricing & Inventory
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Price (₹) <span class="req">*</span></label>
              <input class="form-input" id="edit-product-price" name="price" type="number" step="0.01" required placeholder="499" />
            </div>
            <div class="form-group">
              <label class="form-label">Old Price (₹)</label>
              <input class="form-input" id="edit-product-old-price" name="old_price" type="number" step="0.01" placeholder="799" />
            </div>
            <div class="form-group">
              <label class="form-label">Commercial Price (₹)</label>
              <input class="form-input" id="edit-product-commercial-price" name="commercial_price" type="number" step="0.01" />
            </div>
            <div class="form-group">
              <label class="form-label">Stock <span class="req">*</span></label>
              <input class="form-input" id="edit-product-stock" name="stock" type="number" required placeholder="100" />
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select class="form-select" id="edit-product-active" name="is_active">
                <option value="1">Active</option>
                <option value="0">Archived</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Featured</label>
              <select class="form-select" id="edit-product-featured" name="is_featured">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>
            <div class="form-group form-col-full">
              <label class="form-label" style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" id="edit-product-is-free" name="is_free" value="1" style="accent-color:var(--accent);width:16px;height:16px;" />
                Free product — show on Freebies page
              </label>
              <p class="form-hint" style="margin:4px 0 0;color:#94a3b8;font-size:0.875rem;">
                Sets price to ₹0 and lists this item on <code>freebies.php</code>. Use normal digital files/resources; checkout uses the same product cart flow.
              </p>
            </div>
          </div>
        </div>

        <!-- Media -->
        <div class="form-section">
          <div class="form-section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Media
          </div>
          <div class="form-group">
            <label class="form-label">Main Image</label>
            <span class="form-help">Primary image on product cards and detail page</span>
            <input class="form-input" id="edit-product-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" />
          </div>
          <div id="current-image-preview" class="product-media-preview product-media-preview--main"></div>

          <div class="form-group" style="margin-top:16px;">
            <label class="form-label">Gallery Images</label>
            <span class="form-help">Select multiple files (JPG, PNG, WebP, GIF — max 5MB each). Shown in the product popup gallery.</span>
            <input class="form-input" id="edit-product-gallery" name="media[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple />
          </div>
          <input type="hidden" name="additional_images" id="edit-product-additional-images" value="[]" />
          <div id="product-gallery-preview" class="product-media-preview product-media-gallery"></div>
          <div id="product-gallery-pending" class="product-media-pending"></div>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:12px;justify-content:flex-end;padding-top:16px;border-top:1px solid var(--border);">
          <button type="button" class="btn btn-ghost" onclick="closeEditProductModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Save Product
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════ -->
<script src="admin-dashboard.js?v=<?php echo time(); ?>"></script>
<script>
/* ── Theme ── */
function toggleTheme() {
  const root = document.documentElement;
  const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  root.setAttribute('data-theme', next);
  localStorage.setItem('admin-theme', next);
  updateThemeIcon(next);
}
function updateThemeIcon(theme) {
  document.getElementById('theme-icon-sun').style.display  = theme === 'dark' ? 'block' : 'none';
  document.getElementById('theme-icon-moon').style.display = theme === 'dark' ? 'none'  : 'block';
}

/* ── Sidebar ── */
function toggleSidebarCollapse() {
  const sb = document.getElementById('adm-sidebar');
  sb.classList.toggle('collapsed');
  localStorage.setItem('admin-sidebar-collapsed', sb.classList.contains('collapsed'));
}
function openSidebar() {
  document.getElementById('adm-sidebar').classList.add('open');
  document.getElementById('sb-overlay').classList.add('active');
}
function closeSidebar() {
  document.getElementById('adm-sidebar').classList.remove('open');
  document.getElementById('sb-overlay').classList.remove('active');
}
function toggleSidebar() {
  const sb = document.getElementById('adm-sidebar');
  sb.classList.contains('open') ? closeSidebar() : openSidebar();
}

/* ── Tab switching ── */
const tabTitles = {
  overview:'Overview', analytics:'Analytics', products:'Products',
  bundles:'Bundles', categories:'Categories',
  orders:'Orders', users:'Users', reviews:'Reviews', messages:'Messages', freebies:'Freebies'
};
function switchTab(tab, element) {
  event && event.preventDefault && event.preventDefault();
  document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active'));
  if (element) element.classList.add('active');
  document.querySelectorAll('.adm-tab').forEach(t => t.classList.remove('active'));
  const el = document.getElementById(tab + '-tab');
  if (el) el.classList.add('active');
  const title = document.getElementById('topbar-title');
  if (title) title.textContent = tabTitles[tab] || tab;
  if (tab === 'overview')    loadOverview();
  else if (tab === 'analytics')  loadAnalytics();
  else if (tab === 'products')   loadProducts();
  else if (tab === 'bundles')    loadAdminBundles();
  else if (tab === 'categories') loadAdminCategories();
  else if (tab === 'orders')     loadOrders();
  else if (tab === 'users')      loadUsers();
  else if (tab === 'reviews')    loadReviews();
  else if (tab === 'messages')   loadMessages();
  else if (tab === 'freebies')   loadAdminFreebies();
  if (window.innerWidth <= 1100) closeSidebar();
}

/* ── Topbar search passthrough ── */
function handleTopbarSearch(val) {
  const activeTab = document.querySelector('.adm-tab.active');
  if (!activeTab) return;
  const id = activeTab.id;
  if (id === 'products-tab') {
    const s = document.getElementById('product-search');
    if (s) { s.value = val; filterProducts(); }
  } else if (id === 'orders-tab') {
    const s = document.getElementById('order-search');
    if (s) { s.value = val; filterOrders(); }
  } else if (id === 'users-tab') {
    const s = document.getElementById('user-search');
    if (s) { s.value = val; filterUsers(); }
  }
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', function () {
  const savedTheme = localStorage.getItem('admin-theme') || 'dark';
  document.documentElement.setAttribute('data-theme', savedTheme);
  updateThemeIcon(savedTheme);
  const sbCollapsed = localStorage.getItem('admin-sidebar-collapsed') === 'true';
  if (sbCollapsed && window.innerWidth > 1100) {
    document.getElementById('adm-sidebar').classList.add('collapsed');
  }
});

window.toggleTheme = toggleTheme;
window.toggleSidebarCollapse = toggleSidebarCollapse;
window.toggleSidebar = toggleSidebar;
window.switchTab = switchTab;
</script>

<!-- ═══════════════════════════════════════════════════
     GENERIC CONFIRM MODAL
═══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="confirm-modal-overlay">
  <div class="modal modal-sm" onclick="event.stopPropagation()" style="max-width:440px;">
    <div class="modal-head">
      <h2 id="confirm-modal-title">Confirm</h2>
      <button class="modal-close" onclick="_resolveConfirm(false)" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p id="confirm-modal-message" style="margin:0;color:var(--text-2);line-height:1.65;font-size:.9rem;"></p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="_resolveConfirm(false)">Cancel</button>
      <button class="btn btn-danger" id="confirm-modal-btn" onclick="_resolveConfirm(true)">Delete</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     RESOURCE EDITOR MODAL
═══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="resource-modal-overlay" onclick="closeResourceModal()">
  <div class="modal modal-sm" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h2 id="resource-modal-title">Add Resource</h2>
      <button class="modal-close" onclick="closeResourceModal()" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="rm-id" value="" />
      <input type="hidden" id="rm-owner-key" value="" />
      <input type="hidden" id="rm-owner-id" value="" />

      <div class="form-group" style="margin-bottom:16px;">
        <label class="form-label">Title <span class="req">*</span></label>
        <input class="form-input" id="rm-title" placeholder="e.g. Main Template PDF" autocomplete="off" />
      </div>

      <div class="form-group" style="margin-bottom:16px;">
        <label class="form-label">Resource Type <span class="req">*</span></label>
        <select class="form-select" id="rm-type">
          <option value="file">File (generic)</option>
          <option value="zip">ZIP Archive</option>
          <option value="pdf">PDF Document</option>
          <option value="canva">Canva Template</option>
          <option value="figma">Figma File</option>
          <option value="external_link">External Link</option>
          <option value="instructions">Instructions Only</option>
        </select>
      </div>

      <div class="form-group" id="rm-url-group" style="margin-bottom:16px;display:none;">
        <label class="form-label">HTTPS Link <span class="req">*</span></label>
        <input class="form-input" id="rm-url" type="url" placeholder="https://…" />
        <span class="form-help" style="font-size:.75rem;color:var(--text-3);margin-top:4px;display:block;">Must start with https://</span>
      </div>

      <div class="form-group" id="rm-instructions-group" style="margin-bottom:16px;display:none;">
        <label class="form-label">Instructions <span class="req">*</span></label>
        <textarea class="form-textarea" id="rm-instructions" rows="3" placeholder="Access instructions shown to customers after purchase…"></textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
        <div class="form-group">
          <label class="form-label">Download Limit</label>
          <input class="form-input" id="rm-dl-limit" type="number" min="1" max="100" value="5" />
          <span class="form-help" style="font-size:.75rem;color:var(--text-3);margin-top:4px;display:block;">1–100 per customer</span>
        </div>
        <div class="form-group">
          <label class="form-label">Expiry (days)</label>
          <input class="form-input" id="rm-expiry" type="number" min="1" max="3650" value="30" />
          <span class="form-help" style="font-size:.75rem;color:var(--text-3);margin-top:4px;display:block;">1–3650 days</span>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input class="form-input" id="rm-sort" type="number" value="0" />
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:2px;">
          <label class="toggle-wrap">
            <span class="toggle"><input type="checkbox" id="rm-active" checked /><span class="toggle-track"></span></span>
            <span class="toggle-label">Active</span>
          </label>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeResourceModal()">Cancel</button>
      <button class="btn btn-primary" id="resource-modal-save-btn" onclick="saveResourceModal()">Save Resource</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     RESOURCE VIEW MODAL
═══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="resource-view-modal-overlay" onclick="closeResourceViewModal()">
  <div class="modal modal-sm" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h2>Resource Details</h2>
      <button class="modal-close" onclick="closeResourceViewModal()" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body" id="resource-view-content" style="line-height:1.8;"></div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeResourceViewModal()">Close</button>
      <a id="resource-view-link" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="display:none;">
        Open Link
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      </a>
    </div>
  </div>
</div>

</body>
</html>
