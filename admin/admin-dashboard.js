// Admin Dashboard JavaScript

// ==========================================
// 1. CORE API LAYER
// ==========================================
async function fetchJson(url, options = {}) {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const method = (options.method || 'GET').toUpperCase();
    const headers = { ...(options.headers || {}) };
    if (csrfToken && method !== 'GET' && method !== 'HEAD') {
      headers['X-CSRF-Token'] = csrfToken;
    }
    const res = await fetch(url, { ...options, headers });
    const text = await res.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch(e) {
      throw new Error('Invalid JSON response from server');
    }
    
    // Handle {status: 'success', data: [...]} format
    if (json && typeof json === 'object' && 'status' in json) {
      if (json.status !== 'success') {
        throw new Error(json.message || 'API error');
      }
      return json.data !== undefined ? json.data : [];
    }
    
    // Handle raw array fallback
    if (Array.isArray(json)) {
      return json;
    }
    
    return json;
  } catch (err) {
    console.error(`API Error (${url}):`, err);
    throw err;
  }
}

// ==========================================
// 2. UTILS & HELPERS
// ==========================================
function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str).replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  })[m]);
}

function showToast(msg, type = 'info') {
  alert(`[${type.toUpperCase()}] ${msg}`);
}

function getStatusBadge(status) {
  const key = String(status || 'pending').trim().toLowerCase();
  const badges = {
    'pending': '<span class="badge badge-warning">Pending</span>',
    'awaiting_payment': '<span class="badge badge-info">Awaiting payment</span>',
    'paid': '<span class="badge badge-success">Paid</span>',
    'processing': '<span class="badge badge-info">Processing</span>',
    'shipped': '<span class="badge badge-info">Shipped</span>',
    'delivered': '<span class="badge badge-success">Delivered</span>',
    'failed': '<span class="badge badge-danger">Failed</span>',
    'cancelled': '<span class="badge badge-danger">Cancelled</span>',
  };
  return badges[key] || `<span class="badge badge-warning">${escapeHtml(String(status || 'unknown'))}</span>`;
}

function getOrderCustomerName(order) {
  if (order.first_name || order.last_name) return `${order.first_name || ''} ${order.last_name || ''}`.trim();
  let ship = order.shipping_address;
  if (typeof ship === 'string') try { ship = JSON.parse(ship); } catch(e) {}
  if (ship && (ship.firstName || ship.lastName)) return `${ship.firstName || ''} ${ship.lastName || ''} (Guest)`.trim();
  return 'Guest';
}

function getOrderEmail(order) {
  if (order.email) return order.email;
  let ship = order.shipping_address;
  if (typeof ship === 'string') try { ship = JSON.parse(ship); } catch(e) {}
  return (ship && ship.email) ? ship.email : 'N/A';
}

// ==========================================
// 3. INIT & NAVIGATION
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
  setupTabNavigation();
  initDashboard();
  setupMediaUpload();

  // Event delegation
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-toggle-block');
    if (btn) {
      const userId = parseInt(btn.dataset.userId, 10);
      const isBlocked = btn.dataset.blocked === '1';
      if (!isNaN(userId)) toggleUserBlock(userId, btn, isBlocked);
    }
  });

  // Escape key modal close
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeStatusModal();
      closeOrderDetailsModal();
      closeEditProductModal();
    }
  });
});

function initDashboard() {
  loadOverview();
  loadUsers();
  loadProducts();
  loadOrders();
  loadAnalytics();
}

function setupTabNavigation() {
  document.querySelectorAll('.sidebar-nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
      e.preventDefault();
      const tab = this.dataset.tab;
      document.querySelectorAll('.sidebar-nav-item').forEach(nav => nav.classList.remove('active'));
      this.classList.add('active');
      document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
      document.getElementById(tab + '-tab').classList.add('active');
      
      const loaders = { overview: loadOverview, users: loadUsers, products: loadProducts, orders: loadOrders, analytics: loadAnalytics };
      if (loaders[tab]) loaders[tab]();
      
      if (window.innerWidth <= 1024) {
        document.getElementById('admin-sidebar')?.classList.remove('open');
        document.getElementById('sidebar-overlay')?.classList.remove('active');
      }
    });
  });
}

function handleAdminLogout() {
  if (confirm('Are you sure you want to logout?')) {
    fetch('../api/auth/logout.php')
      .then(() => window.location.href = 'admin-login.php')
      .catch(() => window.location.href = 'admin-login.php');
  }
}

// ==========================================
// 4. DATA FETCHERS
// ==========================================
async function getOrders() {
  const data = await fetchJson('../api/admin/order/list.php');
  return Array.isArray(data) ? data : [];
}

async function getUsers() {
  const data = await fetchJson('../api/admin/user/list.php');
  return Array.isArray(data) ? data : [];
}

async function getProducts() {
  const data = await fetchJson('../api/admin/product/list.php');
  return Array.isArray(data) ? data : [];
}

// ==========================================
// 5. VIEW CONTROLLERS (LOADERS/RENDERERS)
// ==========================================

// --- ORDERS ---
async function loadOrders() {
  const table = document.getElementById('orders-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="8" style="text-align:center;">Loading orders...</td></tr>';
  
  try {
    const orders = await getOrders();
    renderOrders(table, orders);
  } catch (err) {
    table.innerHTML = `<tr><td colspan="8" class="empty-state" style="color:red">Failed to load orders: ${err.message}</td></tr>`;
  }
}

function renderOrders(table, orders) {
  if (orders.length === 0) {
    table.innerHTML = '<tr><td colspan="8" class="empty-state">No orders found</td></tr>';
    return;
  }
  
  table.innerHTML = orders.map(order => {
    const date = new Date(order.created_at || Date.now()).toLocaleDateString();
    const safeOrderNum = escapeHtml(order.order_number || 'N/A');
    const safeCustName = escapeHtml(getOrderCustomerName(order));
    const safeStatus = escapeHtml(order.status || 'pending');
    const orderId = parseInt(order.id, 10);
    
    return `
      <tr>
        <td>${safeOrderNum}</td>
        <td>${safeCustName}</td>
        <td>${order.items_count || 0} item(s)</td>
        <td>${date}</td>
        <td>$${parseFloat(order.total || 0).toLocaleString()}</td>
        <td><span class="badge badge-info">${(order.payment_method || 'card').toUpperCase()}</span></td>
        <td>${getStatusBadge(order.status || 'pending')}</td>
        <td>
          <div class="action-buttons">
            <button class="btn-small btn-edit" onclick="viewOrder(${orderId})">View</button>
            <button class="btn-small btn-edit" onclick="updateOrderStatusFromBtn(this)" 
              data-order-number="${safeOrderNum}" data-order-status="${safeStatus}" data-customer-name="${safeCustName}">Update</button>
            <button class="btn-small btn-delete" data-order-id="${orderId}" onclick="deleteOrder(${orderId})">Delete</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// --- OVERVIEW ---
async function loadOverview() {
  try {
    const [users, products, orders] = await Promise.all([getUsers(), getProducts(), getOrders()]);
    
    const setEl = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
    setEl('stat-total-users', users.length);
    setEl('stat-total-products', products.length);
    setEl('stat-total-orders', orders.length);
    
    const revenue = orders.reduce((sum, o) => sum + parseFloat(o.total || 0), 0);
    setEl('stat-total-revenue', `$${revenue.toLocaleString()}`);

    renderRecentOrders(orders);
    loadOverviewStats(); // Background extra fetch
  } catch (err) {
    console.error("Overview load error:", err);
  }
}

function renderRecentOrders(orders) {
  const table = document.getElementById('recent-orders-table');
  if (!table) return;
  const recent = orders.slice(0, 5);
  if (recent.length === 0) {
    table.innerHTML = '<tr><td colspan="5" class="empty-state">No orders yet</td></tr>';
    return;
  }
  table.innerHTML = recent.map(o => `
    <tr>
      <td>${escapeHtml(o.order_number || 'N/A')}</td>
      <td>${escapeHtml(getOrderCustomerName(o))}</td>
      <td>${new Date(o.created_at || Date.now()).toLocaleDateString()}</td>
      <td>$${parseFloat(o.total || 0).toLocaleString()}</td>
      <td>${getStatusBadge(o.status || 'pending')}</td>
    </tr>
  `).join('');
}

async function loadOverviewStats() {
  try {
    const statsData = await fetchJson('../api/admin/stats/overview.php');
    if (!statsData) return;
    const d = statsData.data || statsData;
    const setChange = (id, text, isPositive) => {
      const el = document.getElementById(id);
      if (el && text) {
        el.textContent = text;
        el.style.color = isPositive ? '#22c55e' : (text.startsWith('-') ? '#ef4444' : '#aaa');
      }
    };
    if (d.users) setChange('stat-users-change', d.users.change, d.users.change.startsWith('+'));
    if (d.products) setChange('stat-products-change', d.products.change, d.products.change.startsWith('+'));
    if (d.orders) setChange('stat-orders-change', d.orders.change, d.orders.change.startsWith('+'));
    if (d.revenue) setChange('stat-revenue-change', d.revenue.change, d.revenue.change.startsWith('+'));
  } catch(e) {}
}

// --- ANALYTICS ---
async function loadAnalytics() {
  try {
    const [orders, users] = await Promise.all([getOrders(), getUsers()]);
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const thisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    const todayOrders = orders.filter(o => {
      const d = new Date(o.created_at || Date.now());
      d.setHours(0, 0, 0, 0);
      return d.getTime() === today.getTime();
    });
    const monthOrders = orders.filter(o => new Date(o.created_at || Date.now()) >= thisMonth);
    
    const tot = arr => arr.reduce((s, o) => s + parseFloat(o.total || 0), 0);
    const setEl = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };

    setEl('analytics-today-revenue', `$${tot(todayOrders).toLocaleString()}`);
    setEl('analytics-month-revenue', `$${tot(monthOrders).toLocaleString()}`);
    setEl('analytics-avg-order', `$${Math.round(orders.length ? tot(orders)/orders.length : 0).toLocaleString()}`);
    setEl('analytics-conversion', `${users.length ? (orders.length / users.length * 100).toFixed(1) : 0}%`);
    
    const topProdTable = document.getElementById('top-products-table');
    if (topProdTable) topProdTable.innerHTML = '<tr><td colspan="4" class="empty-state">Detailed product analytics available in DB view</td></tr>';
  } catch(e) {
    console.error("Analytics load error:", e);
  }
}

// --- USERS ---
async function loadUsers() {
  const table = document.getElementById('users-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="7" style="text-align:center;">Loading users...</td></tr>';
  
  try {
    const users = await getUsers();
    renderUsers(table, users);
  } catch (err) {
    table.innerHTML = `<tr><td colspan="7" class="empty-state" style="color:red">Failed to load users: ${err.message}</td></tr>`;
  }
}

function renderUsers(table, users) {
  if (users.length === 0) {
    table.innerHTML = '<tr><td colspan="7" class="empty-state">No users found</td></tr>';
    return;
  }
  table.innerHTML = users.map(user => {
    const dateStr = user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A';
    const name = escapeHtml(user.name || `${user.first_name||''} ${user.last_name||''}`.trim() || user.username || 'N/A');
    const isBlocked = user.is_blocked == 1;
    const statusHtml = isBlocked ? '<span class="badge badge-danger">Blocked</span>' : '<span class="badge badge-success">Active</span>';
    const blockBtnHtml = user.id ? `
      <button class="btn-small ${isBlocked?'btn-success':'btn-delete'} js-toggle-block" data-user-id="${user.id}" data-blocked="${isBlocked?'1':'0'}">
        ${isBlocked ? 'Unblock' : 'Block'}
      </button>` : '<span style="color:#aaa;">Guest</span>';
      
    return `
      <tr>
        <td>${name}</td>
        <td>${escapeHtml(user.email || 'N/A')}</td>
        <td>${escapeHtml(user.phone || 'N/A')}</td>
        <td>${dateStr}</td>
        <td>${user.order_count || 0}</td>
        <td>${statusHtml}</td>
        <td>${blockBtnHtml}</td>
      </tr>
    `;
  }).join('');
}

// --- PRODUCTS ---
async function loadProducts() {
  const table = document.getElementById('products-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="8" style="text-align:center;">Loading products...</td></tr>';
  
  try {
    const products = await getProducts();
    renderProducts(table, products);
  } catch (err) {
    table.innerHTML = `<tr><td colspan="8" class="empty-state" style="color:red">Failed to load products: ${err.message}</td></tr>`;
  }
}

function renderProducts(table, products) {
  if (products.length === 0) {
    table.innerHTML = '<tr><td colspan="8" class="empty-state">No products found</td></tr>';
    return;
  }
  table.innerHTML = products.map(p => {
    const img = p.image ? '../'+escapeHtml(p.image) : '../img/sticker.webp';
    const isChecked = p.is_active == 1 ? 'checked' : '';
    return `
      <tr>
        <td><img src="${img}" alt="${escapeHtml(p.name)}" class="product-image" onerror="this.src='../img/sticker.webp'"></td>
        <td>${escapeHtml(p.name || 'N/A')}</td>
        <td><span class="badge badge-info">${escapeHtml(p.category || 'Uncategorized')}</span></td>
        <td>$${parseFloat(p.price || 0).toLocaleString()}</td>
        <td>${p.stock || 0}</td>
        <td>★ ${escapeHtml(p.rating || '0.0')}</td>
        <td>
          <label class="switch">
            <input type="checkbox" ${isChecked} onchange="toggleProductStatus(${p.id}, this)">
            <span class="slider round"></span>
          </label>
        </td>
        <td>
          <div class="action-buttons">
            <button class="btn-small btn-edit" onclick="editProduct('${p.id}')">Edit</button>
            <button class="btn-small btn-delete" onclick="deleteProduct('${p.id}')">Delete</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// ==========================================
// 6. ACTION HANDLERS
// ==========================================

// --- ORDERS ACTIONS ---
async function deleteOrder(orderId) {
  if (!confirm('Delete this order? Cannot be undone.')) return;
  const btn = document.querySelector(`button[data-order-id="${orderId}"]`);
  let ogText = 'Delete';
  if (btn) { ogText = btn.innerText; btn.innerText = '...'; btn.disabled = true; }
  
  try {
    await fetchJson('../api/admin/order/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: orderId })
    });
    showToast('Order deleted', 'success');
    loadOrders(); loadOverview();
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    if (btn) { btn.innerText = ogText; btn.disabled = false; }
  }
}

async function viewOrder(orderId) {
  const content = document.getElementById('order-details-content');
  if(!content) return;
  content.innerHTML = '<div style="text-align:center; padding: 2rem;">Loading...</div>';
  openOrderDetailsModal();

  try {
    const data = await fetchJson(`../api/admin/order/get_details.php?id=${orderId}`);
    const order = data; // fetchJson unwraps 'data'
    if(!order) throw new Error("Order not found");
    
    let shipping = {};
    if(typeof order.shipping_address === 'string') try { shipping = JSON.parse(order.shipping_address); } catch(e){}
    else if(order.shipping_address) shipping = order.shipping_address;

    const itemsHtml = (order.items || []).map(item => `
      <tr>
        <td>
          <div style="display: flex; align-items: center; gap: 10px;">
            <img src="../${escapeHtml(item.image||'img/sticker.webp')}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
            <div>
              <div style="font-weight: 600;">${escapeHtml(item.name)}</div>
              ${item.size ? `<div style="font-size:0.75rem;color:#ccc;">Size: ${escapeHtml(item.size)}</div>` : ''}
             </div>
          </div>
        </td>
        <td>$${parseFloat(item.price).toFixed(2)}</td>
        <td>${parseInt(item.quantity, 10)}</td>
        <td style="text-align: right;">$${(parseFloat(item.price)*item.quantity).toFixed(2)}</td>
      </tr>
    `).join('');

    const custName = getOrderCustomerName(order);
    
    content.innerHTML = `
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
        <div>
          <h3 style="color:#666;font-size:0.9rem;text-transform:uppercase;">Customer</h3>
          <p><strong>Name:</strong> ${escapeHtml(custName)}</p>
          <p><strong>Email:</strong> ${escapeHtml(getOrderEmail(order))}</p>
          <p><strong>Phone:</strong> ${escapeHtml(order.phone || shipping.phone || 'N/A')}</p>
        </div>
        <div>
          <h3 style="color:#666;font-size:0.9rem;text-transform:uppercase;">Order</h3>
          <p><strong>ID:</strong> ${escapeHtml(order.order_number)}</p>
          <p><strong>Date:</strong> ${new Date(order.created_at).toLocaleString()}</p>
          <p><strong>Status:</strong> ${getStatusBadge(order.status)}</p>
        </div>
      </div>
      <div>
        <h3 style="color:#666;font-size:0.9rem;text-transform:uppercase;">Shipping</h3>
        <p>${escapeHtml(shipping.address || 'N/A')}</p>
        <p>${escapeHtml(shipping.city || '')}, ${escapeHtml(shipping.postalCode || '')}</p>
        <p>${escapeHtml(shipping.country || '')}</p>
      </div>
      <table style="width:100%; margin-top:1.5rem;">
        <thead><tr style="border-bottom:2px solid var(--admin-border);">
          <th style="text-align:left;">Item</th><th style="text-align:left;">Price</th>
          <th style="text-align:left;">Qty</th><th style="text-align:right;">Total</th>
        </tr></thead>
        <tbody>${itemsHtml}</tbody>
        <tfoot>
          <tr style="border-top:2px solid var(--admin-border);font-weight:700;">
            <td colspan="3" style="text-align:right;padding-top:1rem;">Total:</td>
            <td style="text-align:right;padding-top:1rem;font-size:1.2rem;color:var(--admin-accent);">$${parseFloat(order.total).toLocaleString()}</td>
          </tr>
        </tfoot>
      </table>
    `;
  } catch (err) {
    content.innerHTML = `<div style="text-align:center;color:red;padding:2rem;">Error: ${err.message}</div>`;
  }
}

function updateOrderStatusFromBtn(btn) {
  document.getElementById('modal-order-number').textContent = btn.dataset.orderNumber;
  document.getElementById('modal-order-customer').textContent = btn.dataset.customerName;
  document.getElementById('modal-current-status').innerHTML = getStatusBadge(btn.dataset.orderStatus);
  document.getElementById('status-select').value = btn.dataset.orderStatus;
  openStatusModal();
}

async function confirmStatusUpdate() {
  const orderNumber = document.getElementById('modal-order-number').textContent;
  const newStatus = document.getElementById('status-select').value;
  const btn = document.querySelector('#status-modal-overlay .btn-primary');
  
  if (!orderNumber || !newStatus) return;
  const ogText = btn.innerText; btn.innerText = '...'; btn.disabled = true;

  try {
    await fetchJson('../api/admin/order/update_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_number: orderNumber, status: newStatus })
    });
    closeStatusModal();
    loadOrders(); loadOverview();
    showToast('Status updated');
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    btn.innerText = ogText; btn.disabled = false;
  }
}

// --- PRODUCTS ACTIONS ---
function editProduct(productId) {
  window.location.href = `editproduct.php?id=${productId}`;
}

async function toggleProductStatus(productId, checkbox) {
  try {
    await fetchJson('../api/admin/product/toggle_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: productId, is_active: checkbox.checked ? 1 : 0 })
    });
  } catch (err) {
    showToast('Failed to toggle status', 'error');
    checkbox.checked = !checkbox.checked;
  }
}

async function deleteProduct(productId) {
  if (!confirm('Delete this product?')) return;
  try {
    const fd = new FormData(); fd.append('id', productId);
    await fetchJson('../api/admin/product/delete.php', { method: 'POST', body: fd });
    loadProducts(); loadOverview();
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function handleUpdateProduct(e) {
  e.preventDefault();
  const form = e.target;
  const btn = form.querySelector('button[type="submit"]');
  const ogText = btn.innerText; btn.disabled = true; btn.innerText = '...';
  try {
    await fetchJson('../api/admin/product/update.php', { method: 'POST', body: new FormData(form) });
    closeEditProductModal(); loadProducts(); loadOverview(); showToast('Product updated');
  } catch (err) {
    showToast(err.message, 'error');
  } finally { btn.disabled = false; btn.innerText = ogText; }
}

async function handleCreateProduct(e) {
  e.preventDefault();
  const form = e.target;
  const btn = form.querySelector('button[type="submit"]');
  const ogText = btn.innerText; btn.disabled = true; btn.innerText = '...';
  try {
    await fetchJson('../api/admin/product/create.php', { method: 'POST', body: new FormData(form) });
    form.reset(); selectedProductFiles = []; updateMediaUI();
    loadProducts(); loadOverview(); showToast('Product created');
  } catch (err) {
    showToast(err.message, 'error');
  } finally { btn.disabled = false; btn.innerText = ogText; }
}

// --- USERS ACTIONS ---
async function toggleUserBlock(userId, btn, currentIsBlocked) {
  const action = currentIsBlocked ? 'unblock' : 'block';
  if (!confirm(`Are you sure you want to ${action} this user?`)) return;
  const ogText = btn.innerText; btn.innerText = '...'; btn.disabled = true;
  try {
    await fetchJson('../api/admin/user/block.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: userId, action })
    });
    btn.innerText = currentIsBlocked ? 'Block' : 'Unblock';
    btn.className = `btn-small ${!currentIsBlocked?'btn-success':'btn-delete'} js-toggle-block`;
    btn.dataset.blocked = currentIsBlocked ? '0' : '1';
  } catch (err) {
    showToast(err.message, 'error');
    btn.innerText = ogText;
  } finally { btn.disabled = false; }
}

// ==========================================
// 7. FILTERS
// ==========================================
function filterTable(tableId, searchInputId, filterInputId = null) {
  const search = document.getElementById(searchInputId)?.value.toLowerCase() || '';
  const filter = filterInputId ? document.getElementById(filterInputId)?.value.toLowerCase() : '';
  document.querySelectorAll(`#${tableId} tr`).forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(search) && (!filter || text.includes(filter)) ? '' : 'none';
  });
}

const filterUsers = () => filterTable('users-table', 'user-search');
const filterProducts = () => filterTable('products-table', 'product-search', 'product-category-filter');
const filterOrders = () => filterTable('orders-table', 'order-search', 'order-status-filter');


// ==========================================
// 8. MODAL CONTROLS & MEDIA UPLOAD
// ==========================================
function openOrderDetailsModal() { document.getElementById('order-details-modal-overlay')?.classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeOrderDetailsModal() { document.getElementById('order-details-modal-overlay')?.classList.remove('active'); document.body.style.overflow = ''; }
function openStatusModal() { document.getElementById('status-modal-overlay')?.classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeStatusModal() { document.getElementById('status-modal-overlay')?.classList.remove('active'); document.body.style.overflow = ''; }
function openEditProductModal() { document.getElementById('edit-product-modal-overlay')?.classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeEditProductModal() { 
  const el = document.getElementById('edit-product-modal-overlay');
  if(el){ el.classList.remove('active'); document.body.style.overflow = ''; document.getElementById('edit-product-form')?.reset(); document.getElementById('current-image-preview') && (document.getElementById('current-image-preview').innerHTML=''); }
}

let selectedProductFiles = [];
function setupMediaUpload() {
  const input = document.getElementById('product-media-input');
  const dropZone = document.getElementById('media-upload-area');
  if (input) {
    input.setAttribute('multiple', 'multiple'); input.setAttribute('name', 'media[]');
    input.addEventListener('change', e => handleFiles(e.target.files));
  }
  if (dropZone && input) {
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor='var(--accent)'; dropZone.style.background='rgba(111,75,255,0.05)'; });
    dropZone.addEventListener('dragleave', e => { e.preventDefault(); dropZone.style.borderColor=''; dropZone.style.background=''; });
    dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.style.borderColor=''; dropZone.style.background=''; handleFiles(e.dataTransfer.files); });
    dropZone.addEventListener('click', e => { if(e.target !== input) input.click(); });
  }
}

function handleFiles(files) {
  const MAX_SIZE = 15*1024*1024;
  const EXT = ['jpg','jpeg','png','gif','webp','mp4','webm','ogg','pdf','doc','docx'];
  Array.from(files).forEach(f => {
    const e = f.name.split('.').pop().toLowerCase();
    if (!EXT.includes(e)) return alert(`Skipped: ${f.name} type unsupported`);
    if (f.size > MAX_SIZE) return alert(`Skipped: ${f.name} > 15MB`);
    if (!selectedProductFiles.some(x => x.name===f.name && x.size===f.size)) selectedProductFiles.push(f);
  });
  updateMediaUI();
}

function updateMediaUI() {
  const preview = document.getElementById('media-preview-grid');
  const input = document.getElementById('product-media-input');
  if(!preview || !input) return;
  const dt = new DataTransfer(); selectedProductFiles.forEach(f => dt.items.add(f)); input.files = dt.files;
  preview.innerHTML = '';
  selectedProductFiles.forEach((f, i) => {
    const item = document.createElement('div'); item.className = 'media-preview-item';
    const rm = document.createElement('div'); rm.className = 'media-remove-btn'; rm.innerHTML = '&times;';
    rm.onclick = e => { e.stopPropagation(); selectedProductFiles.splice(i,1); updateMediaUI(); };
    const url = URL.createObjectURL(f);
    let cn = f.type.startsWith('image/') ? `<img src="${url}">` : f.type.startsWith('video/') ? `<video src="${url}"></video>` : `<div>Doc</div>`;
    cn += `<span class="media-size-badge">${(f.size/1024/1024).toFixed(2)} MB</span>`;
    item.innerHTML = cn; item.appendChild(rm); preview.appendChild(item);
  });
}

// EXPORT TO WINDOW
Object.assign(window, {
  handleAdminLogout, filterUsers, filterProducts, filterOrders, editProduct, closeEditProductModal,
  handleUpdateProduct, deleteProduct, viewOrder, updateOrderStatusFromBtn, confirmStatusUpdate, closeStatusModal,
  toggleUserBlock, toggleProductStatus, handleCreateProduct
});
