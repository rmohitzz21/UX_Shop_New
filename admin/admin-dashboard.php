
<?php
require_once '../includes/config.php';


if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit;
}

// Generate CSRF Token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard – UX Pacific Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="../style.css" />
            <link rel="icon" type="image/x-icon" href="../img/faviconUXP444@4x-789.png" />

  <style>
    /* ==================== ADMIN DASHBOARD STYLES ==================== */

    :root {
      --admin-bg-light: #f5f7fa;
      --admin-bg-dark: #0f172a;
      --admin-card-light: #ffffff;
      --admin-card-dark: #1e293b;
      --admin-text-light: #1a1a1a;
      --admin-text-dark: #f1f5f9;
      --admin-border-light: #e5e7eb;
      --admin-border-dark: #334155;
      --admin-sidebar-light: #ffffff;
      --admin-sidebar-dark: #1e293b;
      --admin-accent: #667eea;
      --admin-overlay-light: rgba(15, 23, 42, 0.6);
      --admin-overlay-dark: rgba(0, 0, 0, 0.8);
    }

    [data-theme="dark"] {
      --admin-bg: var(--admin-bg-dark);
      --admin-card: var(--admin-card-dark);
      --admin-text: var(--admin-text-dark);
      --admin-border: var(--admin-border-dark);
      --admin-sidebar: var(--admin-sidebar-dark);
      --admin-overlay: var(--admin-overlay-dark);
      --admin-logo-img: url('../img/logo1.webp');

    }

    [data-theme="light"] {
      --admin-bg: var(--admin-bg-light);
      --admin-card: var(--admin-card-light);
      --admin-text: var(--admin-text-light);
      --admin-border: var(--admin-border-light);
      --admin-sidebar: var(--admin-sidebar-light);
      --admin-overlay: var(--admin-overlay-light);


    }

    .admin-dashboard {
      min-height: 100vh;
      background: var(--admin-bg);
      color: var(--admin-text);
      transition: background 0.3s ease, color 0.3s ease;
      display: flex;
    }

    /* Sidebar */
    .admin-sidebar {
      width: 280px;
      background: var(--admin-sidebar);
      border-right: 1px solid var(--admin-border);
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      overflow-y: auto;
      transition: transform 0.3s ease, width 0.3s ease;
      z-index: 1000;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
    }

    .admin-sidebar.collapsed {
      width: 80px;
    }

    .admin-sidebar-header {
      padding: 1.5rem;
      border-bottom: 1px solid var(--admin-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      min-height: 80px;
    }

    .admin-logo {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      text-decoration: none;
      color: var(--admin-text);
    }

    .admin-logo img {
      width: 190px;
      height: 40px;
      object-fit: contain;
    }

    .admin-logo-text {
      font-size: 1.25rem;
      font-weight: 700;
      white-space: nowrap;
      transition: opacity 0.3s;
    }

    .admin-sidebar.collapsed .admin-logo-text {
      opacity: 0;
      width: 0;
      overflow: hidden;
    }

    .sidebar-toggle {
      background: none;
      border: none;
      color: var(--admin-text);
      cursor: pointer;
      padding: 0.5rem;
      border-radius: 6px;
      transition: background 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .sidebar-toggle:hover {
      background: var(--admin-bg);
    }

    .admin-sidebar-nav {
      padding: 1rem 0;
    }

    .sidebar-nav-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.875rem 1.5rem;
      color: var(--admin-text);
      text-decoration: none;
      transition: all 0.2s;
      border-left: 3px solid transparent;
      cursor: pointer;
    }

    .sidebar-nav-item:hover {
      background: var(--admin-bg);
      border-left-color: var(--admin-accent);
    }

    .sidebar-nav-item.active {
      background: var(--admin-bg);
      border-left-color: var(--admin-accent);
      color: var(--admin-accent);
      font-weight: 600;
    }

    .sidebar-nav-item svg {
      width: 20px;
      height: 20px;
      flex-shrink: 0;
    }

    .sidebar-nav-item span {
      white-space: nowrap;
      transition: opacity 0.3s;
    }

    .admin-sidebar.collapsed .sidebar-nav-item span {
      opacity: 0;
      width: 0;
      overflow: hidden;
    }

    /* Main Content Area */
    .admin-main {
      flex: 1;
      margin-left: 280px;
      transition: margin-left 0.3s ease;
      display: flex;
      flex-direction: column;
    }

    .admin-sidebar.collapsed~.admin-main {
      margin-left: 80px;
    }

    /* Header */
    .admin-header {
      background: var(--admin-card);
      padding: 1.5rem 2rem;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      border-bottom: 1px solid var(--admin-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .admin-header-left {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .mobile-sidebar-toggle {
      display: none;
      background: none;
      border: none;
      color: var(--admin-text);
      cursor: pointer;
      padding: 0.5rem;
      border-radius: 6px;
      font-size: 1.5rem;
    }

    .admin-header h1 {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--admin-text);
      margin: 0;
    }

    .admin-header-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .theme-toggle {
      background: var(--admin-bg);
      border: 1px solid var(--admin-border);
      color: var(--admin-text);
      width: 40px;
      height: 40px;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }

    .theme-toggle:hover {
      background: var(--admin-accent);
      color: white;
      border-color: var(--admin-accent);
    }

    .theme-toggle svg {
      width: 20px;
      height: 20px;
    }

    /* Content */
    .admin-content {
      padding: 2rem;
      max-width: 1400px;
      width: 100%;
      margin: 0 auto;
    }

    .admin-tab {
      display: none;
      animation: fadeIn 0.3s ease;
    }

    .admin-tab.active {
      display: block;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--admin-card);
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      border: 1px solid var(--admin-border);
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .stat-card-title {
      font-size: 0.875rem;
      color: var(--admin-text);
      opacity: 0.7;
      margin-bottom: 0.5rem;
      font-weight: 500;
    }

    .stat-card-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--admin-text);
      margin-bottom: 0.5rem;
    }

    .stat-card-change {
      font-size: 0.875rem;
      color: #10b981;
    }

    /* Tables */
    .data-table {
      background: var(--admin-card);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      border: 1px solid var(--admin-border);
    }

    .table-header {
      padding: 1.5rem;
      border-bottom: 1px solid var(--admin-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .table-header h2 {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--admin-text);
      margin: 0;
    }

    .table-container {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead {
      background: var(--admin-bg);
    }

    th {
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      color: var(--admin-text);
      font-size: 0.875rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border-bottom: 1px solid var(--admin-border);
    }

    td {
      padding: 1rem;
      border-top: 1px solid var(--admin-border);
      color: var(--admin-text);
    }

    tbody tr:hover {
      background: var(--admin-bg);
    }

    /* Badges */
    .badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .badge-success {
      background: #d1fae5;
      color: #065f46;
    }

    [data-theme="dark"] .badge-success {
      background: rgba(16, 185, 129, 0.2);
      color: #6ee7b7;
    }

    .badge-warning {
      background: #fef3c7;
      color: #92400e;
    }

    [data-theme="dark"] .badge-warning {
      background: rgba(245, 158, 11, 0.2);
      color: #fcd34d;
    }

    .badge-danger {
      background: #fee2e2;
      color: #991b1b;
    }

    [data-theme="dark"] .badge-danger {
      background: rgba(239, 68, 68, 0.2);
      color: #fca5a5;
    }

    .badge-info {
      background: #dbeafe;
      color: #1e40af;
    }

    [data-theme="dark"] .badge-info {
      background: rgba(59, 130, 246, 0.2);
      color: #93c5fd;
    }

    .product-image {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 8px;
    }

    .action-buttons {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .btn-small {
      padding: 0.375rem 0.75rem;
      font-size: 0.875rem;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s;
    }

    .btn-edit {
      background: #dbeafe;
      color: #1e40af;
    }

    [data-theme="dark"] .btn-edit {
      background: rgba(59, 130, 246, 0.2);
      color: #93c5fd;
    }

    .btn-edit:hover {
      background: #bfdbfe;
    }

    [data-theme="dark"] .btn-edit:hover {
      background: rgba(59, 130, 246, 0.3);
    }

    .btn-delete {
      background: #fee2e2;
      color: #991b1b;
    }

    [data-theme="dark"] .btn-delete {
      background: rgba(239, 68, 68, 0.2);
      color: #fca5a5;
    }

    .btn-delete:hover {
      background: #fecaca;
    }

    [data-theme="dark"] .btn-delete:hover {
      background: rgba(239, 68, 68, 0.3);
    }

    .btn-success {
      background: #d1fae5;
      color: #065f46;
    }

    [data-theme="dark"] .btn-success {
      background: rgba(16, 185, 129, 0.2);
      color: #6ee7b7;
    }

    .btn-success:hover {
      background: #a7f3d0;
    }

    [data-theme="dark"] .btn-success:hover {
       background: rgba(16, 185, 129, 0.3);
    }

    .empty-state {
      text-align: center;
      padding: 3rem;
      color: var(--admin-text);
      opacity: 0.7;
    }

    .search-filter {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      width: 100%;
    }

    .search-input {
      flex: 1;
      min-width: 200px;
      padding: 0.75rem;
      border: 1px solid var(--admin-border);
      border-radius: 8px;
      font-size: 0.875rem;
      background: var(--admin-card);
      color: var(--admin-text);
    }

    .search-input:focus {
      outline: none;
      border-color: var(--admin-accent);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .filter-select {
      padding: 0.75rem;
      border: 1px solid var(--admin-border);
      border-radius: 8px;
      font-size: 0.875rem;
      background: var(--admin-card);
      color: var(--admin-text);
      cursor: pointer;
    }

    .filter-select:focus {
      outline: none;
      border-color: var(--admin-accent);
    }

    /* Mobile Responsive */
    @media (max-width: 1024px) {
      .admin-sidebar {
        transform: translateX(-100%);
      }

      .admin-sidebar.open {
        transform: translateX(0);
      }

      .admin-main {
        margin-left: 0;
      }

      .mobile-sidebar-toggle {
        display: block;
      }

      .admin-content {
        padding: 1rem;
      }

      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
      }

      .table-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .search-filter {
        width: 100%;
      }
    }

    @media (max-width: 768px) {
      .admin-header {
        padding: 1rem;
      }

      .admin-content {
        padding: 1rem 0.5rem;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .table-container {
        font-size: 0.875rem;
      }

      th,
      td {
        padding: 0.75rem 0.5rem;
      }

      .action-buttons {
        flex-direction: column;
      }
    }

    @media (max-width: 480px) {
      .admin-header h1 {
        font-size: 1.25rem;
      }

      .stat-card-value {
        font-size: 1.5rem;
      }

      table {
        font-size: 0.75rem;
      }

      th,
      td {
        padding: 0.5rem 0.25rem;
      }
    }

    /* Overlay for mobile sidebar */
    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 999;
    }

    .sidebar-overlay.active {
      display: block;
    }

    @media (max-width: 1024px) {
      .sidebar-overlay.active {
        display: block;
      }
    }

    .admin-logo-img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .logo-dark {
      display: none;
    }

    [data-theme="dark"] .logo-light {
      display: none;
    }

    [data-theme="dark"] .logo-dark {
      display: block;
    }

    /* Modal Styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: var(--admin-overlay);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      z-index: 2000;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.2s ease;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-dialog {
      background-color: var(--admin-card);
      border-radius: 12px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
      width: 90%;
      max-width: 900px;
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      animation: slideUp 0.3s ease;
      border: 1px solid var(--admin-border);
      color: var(--admin-text);
    }

    @keyframes slideUp {
      from {
        transform: translateY(20px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      padding: 1.5rem;
      border-bottom: 1px solid var(--admin-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: transparent;
    }

    .modal-header h2 {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--admin-text);
      margin: 0;
    }

    .modal-close {
      background: none;
      border: none;
      color: var(--admin-text);
      cursor: pointer;
      padding: 0.5rem;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s;
      opacity: 0.7;
    }

    .modal-close:hover {
      background: var(--admin-bg);
      opacity: 1;
    }

    .modal-close svg {
      width: 20px;
      height: 20px;
    }

    .modal-body {
      padding: 1.5rem;
      background: transparent;
      overflow-y: auto;
    }

    .modal-order-info {
      background: var(--admin-bg);
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }

    .modal-order-info p {
      margin: 0.5rem 0;
      color: var(--admin-text);
      font-size: 0.875rem;
    }

    .modal-order-info strong {
      font-weight: 600;
      margin-right: 0.5rem;
    }

    .modal-footer {
      padding: 1.5rem;
      border-top: 1px solid var(--admin-border);
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      background: transparent;
    }

    .form-label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--admin-text);
      margin-bottom: 0.5rem;
    }

    .form-label .required {
      color: #ef4444;
      margin-left: 0.25rem;
    }

    .form-section {
      margin-bottom: 2rem;
    }

    .form-section-title {
      font-size: 1.1rem;
      /* Slightly smaller for modal */
      font-weight: 600;
      color: var(--admin-text);
      margin-bottom: 1.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--admin-border);
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    .form-help-text {
      font-size: 0.75rem;
      color: var(--admin-text);
      opacity: 0.6;
      margin-top: 0.5rem;
    }

    .form-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--admin-border);
    }

    .btn-secondary {
      padding: 0.75rem 1.5rem;
      background: var(--admin-bg);
      border: 1px solid var(--admin-border);
      border-radius: 8px;
      color: var(--admin-text);
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-secondary:hover {
      background: var(--admin-card);
      border-color: var(--admin-accent);
    }

    .btn-primary {
      padding: 0.75rem 1.5rem;
      background: var(--admin-accent);
      border: 1px solid var(--admin-accent);
      border-radius: 8px;
      color: #ffffff;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }

    .btn-primary:hover {
      opacity: 0.9;
    }

    .btn-primary:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* Form Elements for Modals */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      margin-bottom: 1rem;
    }

    .edit-product-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 1.5rem;
    }
    @media (max-width: 768px) {
      .edit-product-grid {
        grid-template-columns: 1fr;
      }
    }

    .form-input,
    .form-select,
    .form-textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--admin-border);
      border-radius: 8px;
      font-size: 0.875rem;
      background-color: var(--admin-bg);
      color: var(--admin-text);
      font-family: 'Inter', sans-serif;
      transition: all 0.2s;
    }

    .form-select {
      cursor: pointer;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
      outline: none;
      border-color: var(--admin-accent);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-textarea {
      min-height: 120px;
      resize: vertical;
    }

    @media (max-width: 768px) {
      .modal-dialog {
        width: 95%;
        margin: 1rem;
      }

      .modal-footer {
        flex-direction: column-reverse;
      }

      .modal-footer button {
        width: 100%;
      }
    }
    /* Toggle Switch */
    .switch {
      position: relative;
      display: inline-block;
      width: 34px;
      height: 20px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      -webkit-transition: .4s;
      transition: .4s;
      border-radius: 34px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 14px;
      width: 14px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      -webkit-transition: .4s;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked+.slider {
      background-color: #ef4444; 
    }

    input:focus+.slider {
      box-shadow: 0 0 1px #ef4444;
    }

    input:checked+.slider:before {
      -webkit-transform: translateX(14px);
      -ms-transform: translateX(14px);
      transform: translateX(14px);
    }
  </style>
</head>

<body>
  <div class="admin-dashboard" data-theme="light">
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Left Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
      <div class="admin-sidebar-header">
        <a href="../index.php" class="admin-logo">
          <img src="../img/logo1.webp" alt="UX Pacific" class="logo-light" />
          <img src="../img/logo1.webp" alt="UX Pacific" class="logo-dark" />
          <!-- <span class="admin-logo-text">UX Pacific</span> -->
        </a>
        <button class="sidebar-toggle" onclick="toggleSidebarCollapse()" aria-label="Toggle sidebar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <nav class="admin-sidebar-nav">
        <a href="#" class="sidebar-nav-item active" data-tab="overview" onclick="switchTab('overview', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          <span>Overview</span>
        </a>
        <a href="#" class="sidebar-nav-item" data-tab="users" onclick="switchTab('users', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
          <span>Users</span>
        </a>
        <a href="#" class="sidebar-nav-item" data-tab="products" onclick="switchTab('products', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path
              d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
            </path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
          <span>Products</span>
        </a>
        <a href="#" class="sidebar-nav-item" data-tab="orders" onclick="switchTab('orders', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
          </svg>
          <span>Orders</span>
        </a>
        <a href="#" class="sidebar-nav-item" data-tab="categories" onclick="switchTab('categories', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h7v7H4z"></path><path d="M13 4h7v7h-7z"></path><path d="M4 13h7v7H4z"></path><path d="M13 13h7v7h-7z"></path></svg>
          <span>Categories</span>
        </a>
        <a href="#" class="sidebar-nav-item" data-tab="bundles" onclick="switchTab('bundles', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path></svg>
          <span>Bundles</span>
        </a>
        <a href="#" class="sidebar-nav-item" data-tab="reviews" onclick="switchTab('reviews', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          <span>Reviews</span>
        </a>
        <a href="#" class="sidebar-nav-item" data-tab="messages" onclick="switchTab('messages', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
          <span>Messages</span>
        </a>
        <a href="#" class="sidebar-nav-item" data-tab="analytics" onclick="switchTab('analytics', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
          </svg>
          <span>Analytics</span>
        </a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
      <!-- Header -->
      <div class="admin-header">
        <div class="admin-header-left">
          <button class="mobile-sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
            ☰
          </button>
          <h1>Admin Dashboard</h1>
        </div>
        <div class="admin-header-actions">
          <span id="admin-email-display"
            style="color: var(--admin-text); opacity: 0.7; margin-right: 1rem; font-size: 0.875rem;"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'Admin'); ?></span>
          <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme" id="theme-toggle">
            <svg id="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              style="display: none;">
              <circle cx="12" cy="12" r="5"></circle>
              <line x1="12" y1="1" x2="12" y2="3"></line>
              <line x1="12" y1="21" x2="12" y2="23"></line>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
              <line x1="1" y1="12" x2="3" y2="12"></line>
              <line x1="21" y1="12" x2="23" y2="12"></line>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
            <svg id="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
          </button>
          <button onclick="handleAdminLogout()" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
            Logout
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="admin-content">
        <!-- Overview Tab -->
        <div class="admin-tab active" id="overview-tab">
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-card-title">Total Customers</div>
              <div class="stat-card-value" id="stat-total-users">0</div>
              <div class="stat-card-change" id="stat-users-change">—</div>
            </div>
            <div class="stat-card">
              <div class="stat-card-title">Total Products</div>
              <div class="stat-card-value" id="stat-total-products">0</div>
              <div class="stat-card-change" id="stat-products-change">—</div>
            </div>
            <div class="stat-card">
              <div class="stat-card-title">Total Orders</div>
              <div class="stat-card-value" id="stat-total-orders">0</div>
              <div class="stat-card-change" id="stat-orders-change">—</div>
            </div>
            <div class="stat-card">
              <div class="stat-card-title">Total Revenue</div>
              <div class="stat-card-value" id="stat-total-revenue">₹0</div>
              <div class="stat-card-change" id="stat-revenue-change">—</div>
            </div>
            <div class="stat-card">
              <div class="stat-card-title">Pending Orders</div>
              <div class="stat-card-value" id="stat-pending-orders" style="color:#f59e0b;">0</div>
              <div class="stat-card-change">Awaiting action</div>
            </div>
            <div class="stat-card">
              <div class="stat-card-title">Low Stock Items</div>
              <div class="stat-card-value" id="stat-low-stock" style="color:#ef4444;">0</div>
              <div class="stat-card-change">Stock &le; 5 units</div>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:1.5rem;margin-top:1.5rem;">
            <div class="data-table">
              <div class="table-header">
                <h2>Recent Orders</h2>
                <a href="#" class="btn-primary" style="font-size:.8rem;padding:.4rem .9rem;" onclick="switchTab('orders', document.querySelector('[data-tab=orders]'));return false;">View All</a>
              </div>
              <div class="table-container">
                <table>
                  <thead>
                    <tr>
                      <th>Order</th>
                      <th>Customer</th>
                      <th>Amount (₹)</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="recent-orders-table">
                  </tbody>
                </table>
              </div>
            </div>
            <div class="data-table">
              <div class="table-header">
                <h2>Top Products</h2>
              </div>
              <div class="table-container">
                <table>
                  <thead>
                    <tr>
                      <th>Product</th>
                      <th>Units Sold</th>
                      <th>Revenue (₹)</th>
                    </tr>
                  </thead>
                  <tbody id="top-products-table">
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Users Tab -->
         <div class="admin-tab" id="users-tab">
          <div class="data-table">
            <div class="table-header">
              <h2>All Users</h2>
              <div class="search-filter">
                <input type="text" class="search-input" id="user-search" placeholder="Search users..."
                  onkeyup="filterUsers()">
              </div>
            </div>
            <div class="table-container">
              <table>
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Registered</th>
                    <th>Orders</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="users-table">
                  <!-- Will be populated by JS -->
                   
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Products Tab -->
        <div class="admin-tab" id="products-tab">
          <div class="data-table">
            <div class="table-header">
              <h2>All Products</h2>
              <div class="search-filter">
                <input type="text" class="search-input" id="product-search" placeholder="Search products..."
                  onkeyup="filterProducts()">
                <select class="filter-select" id="product-category-filter" onchange="filterProducts()">
                  <option value="">All Categories</option>
                  <option value="T-Shirts">T-Shirts</option>
                  <option value="Stickers">Stickers</option>
                  <option value="Booklet">Booklet</option>
                  <option value="Workbook">Workbook</option>
                  <option value="Mockup">Mockup</option>
                  <option value="Badges">Badges</option>
                  <option value="Template">UI Template</option>
                </select>
                <button type="button" class="btn-primary" onclick="openCreateProductModal()" style="padding: 0.75rem 1.5rem; display: inline-flex; align-items: center; gap: 0.5rem; white-space: nowrap;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                  </svg>
                  Add Product
                </button>
              </div>
            </div>
            <div class="table-container">
              <table>
                <thead>
                  <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="products-table">
                  <!-- Will be populated by JS -->
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Orders Tab -->
        <div class="admin-tab" id="orders-tab">
          <div class="data-table">
            <div class="table-header">
              <h2>All Orders</h2>
              <div class="search-filter">
                <input type="text" class="search-input" id="order-search" placeholder="Search orders..."
                  onkeyup="filterOrders()">
                <select class="filter-select" id="order-status-filter" onchange="filterOrders()">
                  <option value="">All Status</option>
                  <option value="pending">Pending</option>
                  <option value="awaiting payment">Awaiting payment</option>
                  <option value="paid">Paid</option>
                  <option value="processing">Processing</option>
                  <option value="shipped">Shipped</option>
                  <option value="delivered">Delivered</option>
                  <option value="failed">Failed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>
            </div>
            <div class="table-container">
              <table>
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
                  <!-- Will be populated by JS -->
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="admin-tab" id="categories-tab">
          <div class="data-table">
            <div class="table-header">
              <h2>Categories</h2>
              <form class="search-filter" onsubmit="adminSaveCategory(event)" enctype="multipart/form-data">
                <input class="search-input" name="name" placeholder="Category name" required>
                <input class="search-input" name="description" placeholder="Description">
                <select class="filter-select" name="accent">
                  <option value="purple">Purple</option><option value="green">Green</option><option value="pink">Pink</option><option value="orange">Orange</option>
                </select>
                <input class="search-input" name="sort_order" type="number" placeholder="Order" style="max-width: 100px;">
                <input class="search-input" name="image" type="file" accept="image/*">
                <button class="btn-primary" type="submit">Add Category</button>
              </form>
            </div>
            <div class="table-container">
              <table><thead><tr><th>Name</th><th>Slug</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead><tbody id="categories-table"></tbody></table>
            </div>
          </div>
        </div>

        <div class="admin-tab" id="bundles-tab">
          <div class="data-table">
            <div class="table-header">
              <h2>Bundles</h2>
              <form id="bundle-editor-form" class="search-filter" onsubmit="adminSaveBundle(event)" enctype="multipart/form-data" style="flex-wrap:wrap;align-items:center;">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="existing_image" value="">

                <input class="search-input" name="name" placeholder="Bundle name" required>
                <input class="search-input" name="price" type="number" step="0.01" placeholder="Price" required>
                <input class="search-input" name="old_price" type="number" step="0.01" placeholder="Old price" style="max-width:120px;">
                <input class="search-input" name="category" placeholder="Category (e.g. Career Pack)">
                <input class="search-input" name="tags" placeholder="Tags (comma separated)" style="min-width:180px;">
                <input class="search-input" name="stock" type="number" placeholder="Stock" style="max-width: 100px;">
                <input class="search-input" name="rating" type="number" step="0.1" placeholder="Rating (0-5)">

                <input class="search-input" name="included_items" placeholder="What's included, comma separated" style="min-width:220px;">
                <input class="search-input" name="product_ids" placeholder="Product IDs, comma separated (leave empty to keep current)">

                <textarea class="search-input" name="description" rows="2" placeholder="Description" style="min-width:260px;"></textarea>

                <input class="search-input" name="image" type="file" accept="image/*">

                <label class="form-label" style="display:inline-flex;align-items:center;gap:8px;margin:0;white-space:nowrap;">
                  <input type="checkbox" name="is_active" value="1" checked>
                  Active
                </label>

                <label class="form-label" style="display:inline-flex;align-items:center;gap:8px;margin:0;white-space:nowrap;">
                  <input type="checkbox" name="is_featured" value="1">
                  Best Seller (horizontal slider)
                </label>

                <button id="bundle-submit-btn" class="btn-primary" type="submit">Add Bundle</button>
                <button id="bundle-cancel-btn" class="btn-secondary" type="button" style="display:none;" onclick="adminCancelBundleEdit()">Cancel</button>
              </form>
              <p style="font-size:.8rem;opacity:.75;margin:.5rem 0 0;">Bundles with <strong>Best Seller</strong> on appear in the top slider on <code>bundles.php</code>. Others show in the grid below.</p>
            </div>
            <div class="table-container">
              <table><thead><tr><th>Bundle</th><th>Price</th><th>Best Seller</th><th>Status</th><th>Actions</th></tr></thead><tbody id="bundles-table"></tbody></table>
            </div>
          </div>
        </div>

        <!-- Reviews Tab -->
        <div class="admin-tab" id="reviews-tab">
          <div class="data-table">
            <div class="table-header">
              <h2>Product Reviews</h2>
              <p style="font-size:.85rem;opacity:.7;margin:0;">Approve or remove customer reviews.</p>
            </div>
            <div class="table-container">
              <table>
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
                  <tr><td colspan="6" class="empty-state">Select Reviews from the sidebar to load</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Messages Tab -->
        <div class="admin-tab" id="messages-tab">
          <div class="data-table">
            <div class="table-header">
              <h2>Contact Messages</h2>
              <p style="font-size:.85rem;opacity:.7;margin:0;">Inbox from the contact form.</p>
            </div>
            <div class="table-container">
              <table>
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
                  <tr><td colspan="6" class="empty-state">Select Messages from the sidebar to load</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Analytics Tab -->
        <div class="admin-tab" id="analytics-tab">
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-card-title">Today's Revenue</div>
                <div class="stat-card-value" id="analytics-today-revenue">₹0</div>
            </div>
            <div class="stat-card">
              <div class="stat-card-title">This Month's Revenue</div>
                <div class="stat-card-value" id="analytics-month-revenue">₹0</div>
            </div>
            <div class="stat-card">
              <div class="stat-card-title">Average Order Value</div>
                <div class="stat-card-value" id="analytics-avg-order">₹0</div>
            </div>
            <div class="stat-card">
              <div class="stat-card-title">Conversion Rate</div>
              <div class="stat-card-value" id="analytics-conversion">0%</div>
            </div>
          </div>

          <div class="data-table" style="margin-top: 2rem;">
            <div class="table-header">
              <h2>Top Selling Products</h2>
            </div>
            <div class="table-container">
              <table>
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Units Sold</th>
                    <th>Revenue (₹)</th>
                  </tr>
                </thead>
                <tbody id="analytics-top-products-table">
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>

  <!-- Order Details Modal -->
  <div class="modal-overlay" id="order-details-modal-overlay" onclick="closeOrderDetailsModal()">
    <div class="modal-dialog" style="max-width: 800px;" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h2>Order Details</h2>
        <button class="modal-close" onclick="closeOrderDetailsModal()" aria-label="Close modal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body" id="order-details-content">
        <!-- Will be populated by JS -->
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="closeOrderDetailsModal()">Close</button>
        <button class="btn-primary" id="print-order-btn" onclick="window.print()">Print Order</button>
      </div>
    </div>
  </div>

  <!-- Order Status Update Modal -->
  <div class="modal-overlay" id="status-modal-overlay" onclick="closeStatusModal()">
    <div class="modal-dialog" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h2>Update Order Status</h2>
        <button class="modal-close" onclick="closeStatusModal()" aria-label="Close modal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <div class="modal-order-info">
          <p><strong>Order Number:</strong> <span id="modal-order-number">N/A</span></p>
          <p><strong>Customer:</strong> <span id="modal-order-customer">N/A</span></p>
          <p><strong>Current Status:</strong> <span id="modal-current-status"><span class="badge badge-info">Processing</span></span></p>
        </div>
        <div class="form-group" style="margin-top: 1.5rem;">
          <label class="form-label" for="status-select">
            Select New Status
            <span class="required">*</span>
          </label>
          <select id="status-select" class="form-select">
            <option value="">Select Status</option>
            <option value="pending">Pending</option>
            <option value="awaiting_payment">Awaiting payment</option>
            <option value="paid">Paid</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="failed">Failed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="closeStatusModal()">Cancel</button>
        <button class="btn-primary" onclick="confirmStatusUpdate()">Update Status</button>
      </div>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div class="modal-overlay" id="edit-product-modal-overlay" onclick="closeEditProductModal()">
    <div class="modal-dialog" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h2 id="product-modal-title">Edit Product</h2>
        <button class="modal-close" onclick="closeEditProductModal()" aria-label="Close modal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <form id="edit-product-form" onsubmit="handleUpdateProduct(event)">
          <input type="hidden" id="edit-product-id" name="id">
          <input type="hidden" id="edit-product-existing-image" name="existing_image">

          <div class="form-section">
            <h3 class="form-section-title">Basic Information</h3>
            <div class="form-grid">
              <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="edit-product-name">Product Name <span class="required">*</span></label>
                <input type="text" id="edit-product-name" name="name" class="form-input" required />
              </div>
              <div class="form-group">
                <label class="form-label" for="edit-product-sku">SKU</label>
                <input type="text" id="edit-product-sku" name="sku" class="form-input" />
              </div>
              <div class="form-group">
                <label class="form-label" for="edit-product-category">Category <span class="required">*</span></label>
                <select id="edit-product-category" name="category" class="form-select" required>
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
                <label class="form-label" for="edit-product-rating">Rating</label>
                <input type="number" id="edit-product-rating" name="rating" class="form-input" step="0.1" min="0"
                  max="5" />
              </div>
              <div class="form-group">
                <label class="form-label" for="edit-product-available">Type</label>
                <select id="edit-product-available" name="available_type" class="form-select">
                  <option value="digital">Digital</option>
                  <option value="physical">Physical</option>
                  <option value="both">Both</option>
                </select>
              </div>
              <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="edit-product-description">Description</label>
                <textarea id="edit-product-description" name="description" class="form-textarea"></textarea>
              </div>
              <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="edit-product-whats">What's Included</label>
                <textarea id="edit-product-whats" name="whats_included" class="form-textarea"></textarea>
              </div>
              <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="edit-product-specs">Specifications</label>
                <textarea id="edit-product-specs" name="file_specification" class="form-textarea"></textarea>
              </div>
              <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="edit-product-tags">Tags</label>
                <input type="text" id="edit-product-tags" name="tags" class="form-input" />
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3 class="form-section-title">Pricing & Inventory</h3>
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label" for="edit-product-price">Price <span class="required">*</span></label>
                <input type="number" id="edit-product-price" name="price" class="form-input" step="0.01" required />
              </div>
              <div class="form-group">
                <label class="form-label" for="edit-product-old-price">Old Price (Optional)</label>
                <input type="number" id="edit-product-old-price" name="old_price" class="form-input" step="0.01" />
              </div>
              <div class="form-group">
                <label class="form-label" for="edit-product-commercial-price">Commercial Price</label>
                <input type="number" id="edit-product-commercial-price" name="commercial_price" class="form-input" step="0.01" />
              </div>
              <div class="form-group">
                <label class="form-label" for="edit-product-stock">Stock <span class="required">*</span></label>
                <input type="number" id="edit-product-stock" name="stock" class="form-input" required />
              </div>
              <div class="form-group">
                <label class="form-label" for="edit-product-active">Status</label>
                <select id="edit-product-active" name="is_active" class="form-select">
                  <option value="1">Active</option>
                  <option value="0">Archived</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" for="edit-product-featured">Featured</label>
                <select id="edit-product-featured" name="is_featured" class="form-select">
                  <option value="0">No</option>
                  <option value="1">Yes</option>
                </select>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3 class="form-section-title">Media</h3>
            <label class="form-label" for="edit-product-image">Update Main Image</label>
            <div style="display: flex; align-items: center; gap: 1rem;">
              <input type="file" id="edit-product-image" name="image" class="form-input" accept="image/*" style="flex: 1;">
            </div>
            <div id="current-image-preview"
              style="margin-top: 1rem; font-size: 0.8rem; color: var(--admin-text); opacity: 0.8;"></div>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="closeEditProductModal()">Cancel</button>
            <button type="submit" class="btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  </div>

  <script src="../script.js"></script>
  <script src="admin-dashboard.js?v=<?php echo time(); ?>"></script>
  <script>
    // Theme Toggle
    function toggleTheme() {
      const dashboard = document.querySelector('.admin-dashboard');
      const currentTheme = dashboard.getAttribute('data-theme');
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      dashboard.setAttribute('data-theme', newTheme);
      localStorage.setItem('admin-theme', newTheme);
      updateThemeIcon(newTheme);
    }

    function updateThemeIcon(theme) {
      const sunIcon = document.getElementById('theme-icon-sun');
      const moonIcon = document.getElementById('theme-icon-moon');
      if (theme === 'dark') {
        sunIcon.style.display = 'block';
        moonIcon.style.display = 'none';
      } else {
        sunIcon.style.display = 'none';
        moonIcon.style.display = 'block';
      }
    }

    // Sidebar Toggle
    function toggleSidebarCollapse() {
      const sidebar = document.getElementById('admin-sidebar');
      sidebar.classList.toggle('collapsed');
      localStorage.setItem('admin-sidebar-collapsed', sidebar.classList.contains('collapsed'));
    }

    function toggleSidebar() {
      const sidebar = document.getElementById('admin-sidebar');
      const overlay = document.getElementById('sidebar-overlay');
      sidebar.classList.toggle('open');
      overlay.classList.toggle('active');
    }

    // Tab Switching
    function switchTab(tab, element) {
      event.preventDefault();

      // Update sidebar nav
      document.querySelectorAll('.sidebar-nav-item').forEach(item => {
        item.classList.remove('active');
      });
      element.classList.add('active');

      // Update tabs
      document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
      document.getElementById(tab + '-tab').classList.add('active');

      // Reload data
      if (tab === 'overview') loadOverview();
      else if (tab === 'users') loadUsers();
      else if (tab === 'products') loadProducts();
      else if (tab === 'orders') loadOrders();
      else if (tab === 'categories') loadAdminCategories();
      else if (tab === 'bundles') loadAdminBundles();
      else if (tab === 'reviews') loadReviews();
      else if (tab === 'messages') loadMessages();
      else if (tab === 'analytics') loadAnalytics();

      // Close mobile sidebar
      if (window.innerWidth <= 1024) {
        toggleSidebar();
      }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function () {
      // Load saved theme
      const savedTheme = localStorage.getItem('admin-theme') || 'light';
      document.querySelector('.admin-dashboard').setAttribute('data-theme', savedTheme);
      updateThemeIcon(savedTheme);

      // Load sidebar state
      const sidebarCollapsed = localStorage.getItem('admin-sidebar-collapsed') === 'true';
      if (sidebarCollapsed && window.innerWidth > 1024) {
        document.getElementById('admin-sidebar').classList.add('collapsed');
      }
    });

    window.toggleTheme = toggleTheme;
    window.toggleSidebarCollapse = toggleSidebarCollapse;
    window.toggleSidebar = toggleSidebar;
    window.switchTab = switchTab;

  </script>
</body>

</html>
