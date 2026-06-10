// Production admin dashboard controller.

// ── Generic confirm modal ─────────────────────────────────────────────────────
let _confirmResolve = null;

function showConfirmModal(title, message, confirmLabel = 'Delete') {
  document.getElementById('confirm-modal-title').textContent = title;
  document.getElementById('confirm-modal-message').innerHTML = message;
  document.getElementById('confirm-modal-btn').textContent = confirmLabel;
  document.getElementById('confirm-modal-overlay').classList.add('active');
  return new Promise(resolve => { _confirmResolve = resolve; });
}

function _resolveConfirm(value) {
  document.getElementById('confirm-modal-overlay').classList.remove('active');
  if (_confirmResolve) { _confirmResolve(value); _confirmResolve = null; }
}

window._resolveConfirm = _resolveConfirm;

const state = {
  users: [],
  products: [],
  orders: [],
  categories: [],
  bundles: [],
  freebies: [],
};

async function fetchJson(url, options = {}) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const method = (options.method || 'GET').toUpperCase();
  const headers = { ...(options.headers || {}) };
  if (csrfToken && method !== 'GET' && method !== 'HEAD') headers['X-CSRF-Token'] = csrfToken;

  const response = await fetch(url, { credentials: 'same-origin', ...options, headers });
  const text = await response.text();
  let json;
  try {
    json = JSON.parse(text);
  } catch {
    throw new Error('Invalid server response.');
  }
  if (!response.ok || json.status === 'error') throw new Error(json.message || 'Request failed.');
  return Object.prototype.hasOwnProperty.call(json, 'data') ? json.data : json;
}

function escapeHtml(value) {
  if (value === null || value === undefined) return '';
  return String(value).replace(/[&<>"']/g, ch => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[ch]);
}

function money(value) {
  return '₹' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
}

function normalizeStatus(status) {
  return String(status || 'pending').trim().toLowerCase().replace(/\s+/g, '_');
}

function showToast(message, type = 'info') {
  let host = document.getElementById('toast-host');
  if (!host) {
    host = document.createElement('div');
    host.id = 'toast-host';
    document.body.appendChild(host);
  }

  const icons = {
    success: '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>',
    error:   '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
    info:    '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
  };

  const toast = document.createElement('div');
  toast.className = `toast toast-${type === 'success' ? 'success' : type === 'error' ? 'error' : 'info'}`;
  toast.innerHTML = `
    ${icons[type] || icons.info}
    <div class="toast-body">
      <div class="toast-title">${escapeHtml(message)}</div>
    </div>
    <div class="toast-bar"></div>`;

  host.appendChild(toast);

  const dismiss = () => {
    toast.classList.add('toast-out');
    toast.addEventListener('animationend', () => toast.remove(), { once: true });
  };
  window.setTimeout(dismiss, 3200);
  toast.addEventListener('click', dismiss);
}

function getStatusBadge(status) {
  const key = normalizeStatus(status);
  const label = key.replace(/_/g, ' ').replace(/\b\w/g, m => m.toUpperCase());
  const cls = ['delivered', 'paid', 'active'].includes(key) ? 'badge-success'
    : ['cancelled', 'failed', 'blocked', 'archived'].includes(key) ? 'badge-danger'
    : key === 'pending' ? 'badge-warning'
    : 'badge-info';
  return `<span class="badge ${cls}">${escapeHtml(label)}</span>`;
}

function getOrderCustomerName(order) {
  const name = `${order.first_name || ''} ${order.last_name || ''}`.trim();
  if (name) return name;
  let shipping = order.shipping_address;
  if (typeof shipping === 'string') {
    try { shipping = JSON.parse(shipping); } catch { shipping = {}; }
  }
  const shipName = `${shipping?.firstName || ''} ${shipping?.lastName || ''}`.trim();
  return shipName || 'Guest';
}

function getOrderEmail(order) {
  if (order.email) return order.email;
  let shipping = order.shipping_address_parsed || order.shipping_address;
  if (typeof shipping === 'string') {
    try { shipping = JSON.parse(shipping); } catch { shipping = {}; }
  }
  return shipping?.email || 'N/A';
}

function formatOrderShipping(order) {
  let shipping = order.shipping_address_parsed || order.shipping_address;
  if (typeof shipping === 'string') {
    try { shipping = JSON.parse(shipping); } catch { return shipping || '—'; }
  }
  if (!shipping || typeof shipping !== 'object') return '—';
  const lines = [
    `${shipping.firstName || ''} ${shipping.lastName || ''}`.trim(),
    shipping.address || shipping.street || '',
    [shipping.city, shipping.state, shipping.zip || shipping.postal].filter(Boolean).join(', '),
    shipping.phone || '',
  ].filter(Boolean);
  return lines.length ? lines.map(line => escapeHtml(line)).join('<br>') : '—';
}

async function getProducts() {
  state.products = await fetchJson('../api/admin/product/list.php');
  return state.products;
}

async function getUsers(options = {}) {
  const params = new URLSearchParams();
  if (options.q) params.set('q', options.q);
  if (options.blocked !== undefined && options.blocked !== '') {
    params.set('blocked', String(options.blocked));
  }
  const qs = params.toString();
  state.users = await fetchJson('../api/admin/user/list.php' + (qs ? `?${qs}` : ''));
  return state.users;
}

function userIsPrivileged(user) {
  const role = String(user?.role || '').toLowerCase();
  return ['admin', 'super_admin', 'superadmin'].includes(role);
}

async function getOrders(options = {}) {
  const params = new URLSearchParams();
  params.set('limit', String(options.limit ?? 500));
  params.set('page', String(options.page ?? 1));
  if (options.q) params.set('q', options.q);
  if (options.status) params.set('status', options.status);
  const payload = await fetchJson(`../api/admin/order/list.php?${params.toString()}`);
  if (Array.isArray(payload)) {
    state.orders = payload;
  } else if (payload && Array.isArray(payload.orders)) {
    state.orders = payload.orders;
    state.ordersMeta = {
      total: payload.total ?? payload.orders.length,
      page: payload.page ?? 1,
      limit: payload.limit ?? payload.orders.length,
      has_more: Boolean(payload.has_more),
    };
  } else {
    state.orders = [];
    state.ordersMeta = { total: 0, page: 1, limit: 0, has_more: false };
  }
  return state.orders;
}

async function getCategories() {
  state.categories = await fetchJson('../api/admin/categories/list.php');
  return state.categories;
}

async function getBundles() {
  state.bundles = await fetchJson('../api/admin/bundles/list.php');
  return state.bundles;
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

function populateCategoryControls() {
  const names = state.categories.filter(c => c.is_active == 1).map(c => c.name).filter(Boolean);
  const fallback = ['T-Shirts', 'Stickers', 'Booklet', 'Workbook', 'Mockup', 'Badges', 'Template'];
  const values = Array.from(new Set([...names, ...fallback]));
  const filter = document.getElementById('product-category-filter');
  if (filter) {
    const current = filter.value;
    filter.innerHTML = '<option value="">All Categories</option>' + values.map(v => `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join('');
    filter.value = current;
  }
  const select = document.getElementById('edit-product-category');
  if (select) {
    const current = select.value;
    select.innerHTML = values.map(v => `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join('');
    if (current) select.value = current;
  }
}

async function loadOverview() {
  try {
    const stats = await fetchJson('../api/admin/stats/overview.php');
    setText('stat-total-users', stats.users?.total ?? 0);
    setText('stat-total-products', stats.products?.total ?? 0);
    setText('stat-total-orders', stats.orders?.total ?? 0);
    setText('stat-total-revenue', money(stats.revenue?.total ?? 0));
    setText('stat-users-change', stats.users?.change || 'No change');
    setText('stat-products-change', stats.products?.change || 'No change');
    setText('stat-orders-change', stats.orders?.change || 'No change');
    setText('stat-revenue-change', stats.revenue?.change || 'No change');
    setText('stat-pending-orders', stats.orders?.pending ?? 0);
    setText('stat-low-stock', stats.inventory?.low_stock_count ?? 0);
    setText('stat-unread-messages', stats.messages?.unread ?? 0);
    renderRecentOrders(stats.recent_orders || []);
    renderTopProducts(stats.top_products || []);
  } catch (err) {
    console.error(err);
    showToast(err.message, 'error');
  }
}

function renderRecentOrders(orders) {
  const table = document.getElementById('recent-orders-table');
  if (!table) return;
  if (!orders.length) {
    table.innerHTML = '<tr><td colspan="4" class="tbl-empty">No orders yet</td></tr>';
    return;
  }
  table.innerHTML = orders.map(o => `
    <tr class="recent-order-row" style="cursor:pointer;" onclick="viewOrder(${Number(o.id)})" title="View order details">
      <td>${escapeHtml(o.order_number || 'N/A')}</td>
      <td>${escapeHtml(getOrderCustomerName(o))}</td>
      <td>${money(o.total)}</td>
      <td>${getStatusBadge(o.status)}</td>
    </tr>
  `).join('');
}

function renderTopProducts(rows) {
  const table = document.getElementById('top-products-table');
  if (!table) return;
  if (!rows.length) {
    table.innerHTML = '<tr><td colspan="3" class="tbl-empty">No data yet</td></tr>';
    return;
  }
  table.innerHTML = rows.slice(0, 6).map(row => {
    const typeLabel = row.item_type === 'bundle' ? 'Bundle · ' : '';
    return `
    <tr>
      <td><strong>${escapeHtml(row.name || 'Item')}</strong><div style="font-size:.75rem;opacity:.65;">${escapeHtml(typeLabel + (row.category || ''))}</div></td>
      <td>${Number(row.units_sold || 0).toLocaleString('en-IN')}</td>
      <td>${money(row.revenue)}</td>
    </tr>`;
  }).join('');
}

async function loadAnalytics() {
  const analyticsTable = document.getElementById('analytics-top-products-table');
  if (analyticsTable) {
    analyticsTable.innerHTML = '<tr><td colspan="3" class="tbl-empty">Loading…</td></tr>';
  }
  try {
    const stats = await fetchJson('../api/admin/stats/analytics.php');
    const rev = stats.revenue || {};
    const orders = stats.orders || {};
    const customers = stats.customers || {};

    setText('analytics-today-revenue', money(rev.today ?? 0));
    setText('analytics-month-revenue', money(rev.month ?? 0));
    setText('analytics-avg-order', money(rev.avg_order_month ?? 0));
    setText('analytics-conversion', `${Number(customers.conversion_rate ?? 0).toFixed(1)}%`);

    setText('analytics-today-orders', String(orders.paid_today ?? 0));
    setText('analytics-month-orders', String(orders.paid_month ?? 0));
    setText('analytics-revenue-change', rev.change || 'No change');
    setText(
      'analytics-conversion-hint',
      customers.total
        ? `${customers.with_paid_order ?? 0} of ${customers.total} customers placed a paid order`
        : 'No customers yet'
    );

    const topRows = stats.top_products || [];
    if (analyticsTable) {
      if (!topRows.length) {
        analyticsTable.innerHTML = '<tr><td colspan="3" class="tbl-empty">No paid sales yet</td></tr>';
      } else {
        analyticsTable.innerHTML = topRows.map(row => {
          const typeLabel = row.item_type === 'bundle' ? 'Bundle · ' : '';
          return `
          <tr>
            <td><strong>${escapeHtml(row.name || 'Item')}</strong><div style="font-size:.75rem;opacity:.65;">${escapeHtml(typeLabel + (row.category || ''))}</div></td>
            <td>${Number(row.units_sold || 0).toLocaleString('en-IN')}</td>
            <td>${money(row.revenue)}</td>
          </tr>`;
        }).join('');
      }
    }
  } catch (err) {
    console.error(err);
    showToast(err.message || 'Could not load analytics.', 'error');
    if (analyticsTable) {
      analyticsTable.innerHTML = `<tr><td colspan="3" class="tbl-empty">${escapeHtml(err.message || 'Failed to load')}</td></tr>`;
    }
  }
}


async function loadProducts() {
  const table = document.getElementById('products-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="8" class="tbl-empty">Loading products...</td></tr>';
  try {
    await Promise.all([getProducts(), getCategories()]);
    populateCategoryControls();
    renderProducts(state.products);
  } catch (err) {
    table.innerHTML = `<tr><td colspan="8" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

function productPurchaseCell(p) {
  const buyers = Number(p.buyer_count || 0);
  const downloads = Number(p.download_total || 0);
  const sales = Number(p.sales_count || 0);
  const isDigital = ['digital', 'both'].includes(String(p.available_type || 'digital').toLowerCase());

  if (isDigital) {
    const main = downloads > 0 ? `${downloads.toLocaleString()} downloads` : `${sales.toLocaleString()} sales`;
    const sub = buyers > 0 ? `${buyers.toLocaleString()} buyer${buyers === 1 ? '' : 's'}` : '';
    return sub
      ? `<div>${escapeHtml(main)}</div><div class="cell-sub">${escapeHtml(sub)}</div>`
      : escapeHtml(main);
  }

  const sold = sales > 0 ? sales : buyers;
  return `${Number(sold).toLocaleString()} sold`;
}

function renderProducts(products) {
  const table = document.getElementById('products-table');
  const q = document.getElementById('product-search')?.value.toLowerCase().trim() || '';
  const category = document.getElementById('product-category-filter')?.value.toLowerCase().trim() || '';
  const rows = products.filter(p => {
    const haystack = `${p.name || ''} ${p.sku || ''} ${p.category || ''} ${p.tags || ''}`.toLowerCase();
    return (!q || haystack.includes(q)) && (!category || String(p.category || '').toLowerCase() === category);
  });
  if (!rows.length) {
    table.innerHTML = '<tr><td colspan="8" class="tbl-empty">No products found</td></tr>';
    return;
  }
  table.innerHTML = rows.map(p => {
    const img = '../' + escapeHtml(p.image || 'img/sticker.webp');
    return `
      <tr>
        <td>
          <div class="cell-with-img">
            <img src="${img}" alt="${escapeHtml(p.name)}" class="cell-img" onerror="this.src='../img/sticker.webp'">
            <div>
              <div class="cell-name">${escapeHtml(p.name)}</div>
              <div class="cell-sub">${escapeHtml(p.sku || '')}</div>
            </div>
          </div>
        </td>
        <td><span class="badge badge-info">${escapeHtml(p.category || 'Uncategorized')}</span></td>
        <td>${money(p.price)}${p.is_free == 1 ? ' <span class="badge badge-success" style="margin-left:4px;">Free</span>' : ''}</td>
        <td>${productPurchaseCell(p)}</td>
        <td>${escapeHtml(p.rating || '0.0')}</td>
        <td>${p.is_active == 1 ? getStatusBadge('active') : getStatusBadge('archived')}</td>
        <td>
          <div class="tbl-actions">
            <button class="btn btn-xs btn-ghost" onclick="editProduct(${Number(p.id)})">Edit</button>
            <button class="btn btn-xs ${p.is_active == 1 ? 'btn-danger-ghost' : 'btn-success-ghost'}" onclick="toggleProductStatus(${Number(p.id)}, ${p.is_active == 1 ? 0 : 1})">${p.is_active == 1 ? 'Archive' : 'Restore'}</button>
            <button class="btn btn-xs btn-danger-ghost" onclick="deleteProduct(${Number(p.id)})">Delete</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function filterProducts() {
  renderProducts(state.products);
}

const productMediaState = {
  main: '',
  gallery: [],
  pendingMain: null,
  pendingGallery: [],
};

function syncProductAdditionalImagesField() {
  const hidden = document.getElementById('edit-product-additional-images');
  if (!hidden) return;
  const paths = productMediaState.gallery.filter(p => p && p !== productMediaState.main);
  hidden.value = JSON.stringify(paths);
}

function productMediaThumbHtml(src, label, removeAction) {
  const safeSrc = escapeHtml(src);
  const safeLabel = escapeHtml(label || '');
  const removeBtn = removeAction
    ? `<button type="button" class="product-media-thumb-remove" title="Remove" onclick="${removeAction}">&times;</button>`
    : '';
  return `
    <div class="product-media-thumb">
      ${removeBtn}
      <img src="${safeSrc}" alt="" onerror="this.src='../img/sticker.webp'">
      <div class="product-media-thumb-label">${safeLabel}</div>
    </div>`;
}

function renderProductMediaPreviews() {
  const mainEl = document.getElementById('current-image-preview');
  const galleryEl = document.getElementById('product-gallery-preview');
  const pendingEl = document.getElementById('product-gallery-pending');
  if (!mainEl || !galleryEl || !pendingEl) return;

  if (productMediaState.main) {
    mainEl.innerHTML = productMediaThumbHtml(
      '../' + productMediaState.main,
      productMediaState.main,
      'removeProductMainImage()'
    );
  } else {
    mainEl.innerHTML = '';
  }

  galleryEl.innerHTML = productMediaState.gallery
    .map((path, index) => {
      if (!path || path === productMediaState.main) return '';
      return productMediaThumbHtml(
        '../' + path,
        path,
        `removeProductGalleryImage(${index})`
      );
    })
    .join('');

  if (productMediaState.pendingMain || productMediaState.pendingGallery.length) {
    let pendingHtml = '<p class="product-media-pending-title">New uploads (saved when you click Save Product)</p>';
    if (productMediaState.pendingMain) {
      pendingHtml += productMediaThumbHtml(productMediaState.pendingMain.url, productMediaState.pendingMain.name, '');
    }
    pendingHtml += productMediaState.pendingGallery
      .map(item => productMediaThumbHtml(item.url, item.name, ''))
      .join('');
    pendingEl.innerHTML = pendingHtml;
  } else {
    pendingEl.innerHTML = '';
  }

  syncProductAdditionalImagesField();
}

function revokeProductPendingUrls() {
  if (productMediaState.pendingMain?.url) URL.revokeObjectURL(productMediaState.pendingMain.url);
  productMediaState.pendingGallery.forEach(item => {
    if (item.url) URL.revokeObjectURL(item.url);
  });
}

function renderProductCustomFields(rows = []) {
  const container = document.getElementById('product-custom-fields');
  const hidden = document.getElementById('edit-product-custom-fields');
  if (!container || !hidden) return;
  const list = Array.isArray(rows) && rows.length ? rows : [];
  container.innerHTML = list.length
    ? list.map((row) => `
      <div class="qv-custom-row product-custom-row">
        <div class="form-group">
          <label class="form-label">Label</label>
          <input class="form-input product-cf-label" type="text" value="${escapeHtml(row.label || '')}" placeholder="e.g. Artboard Size" />
        </div>
        <div class="form-group">
          <label class="form-label">Value</label>
          <input class="form-input product-cf-value" type="text" value="${escapeHtml(row.value || '')}" placeholder="e.g. 1920×1080px" />
        </div>
        <button type="button" class="btn btn-ghost btn-xs product-cf-remove" aria-label="Remove field">Remove</button>
      </div>
    `).join('')
    : '<p class="form-hint" style="margin:0;">No custom fields yet. Use <strong>+ Add field</strong> to add rows like Support, File size, or Dimensions.</p>';
  syncProductCustomFieldsHidden();
}

function collectProductCustomFields() {
  return [...document.querySelectorAll('#product-custom-fields .product-custom-row')]
    .map((row) => ({
      label: row.querySelector('.product-cf-label')?.value.trim() || '',
      value: row.querySelector('.product-cf-value')?.value.trim() || '',
    }))
    .filter((row) => row.label && row.value);
}

function syncProductCustomFieldsHidden() {
  const hidden = document.getElementById('edit-product-custom-fields');
  if (hidden) {
    hidden.value = JSON.stringify(collectProductCustomFields());
  }
}

function clearProductForm() {
  const form = document.getElementById('edit-product-form');
  if (!form) return;
  revokeProductPendingUrls();
  productMediaState.main = '';
  productMediaState.gallery = [];
  productMediaState.pendingMain = null;
  productMediaState.pendingGallery = [];
  form.reset();
  form.dataset.mode = 'create';
  document.getElementById('edit-product-id').value = '';
  document.getElementById('edit-product-existing-image').value = '';
  document.getElementById('edit-product-rating').value = '4.5';
  document.getElementById('edit-product-stock').value = '0';
  document.getElementById('edit-product-active').value = '1';
  document.getElementById('edit-product-featured').value = '0';
  const freeCb = document.getElementById('edit-product-is-free');
  if (freeCb) freeCb.checked = false;
  document.getElementById('edit-product-available').value = 'digital';
  syncProductIsFreePriceField();
  const galleryInput = document.getElementById('edit-product-gallery');
  if (galleryInput) galleryInput.value = '';
  renderProductMediaPreviews();
  renderProductCustomFields([]);
  toggleProductResourcesSection();
  loadProductResources(0);
}

function removeProductMainImage() {
  productMediaState.main = '';
  document.getElementById('edit-product-existing-image').value = '';
  const mainInput = document.getElementById('edit-product-image');
  if (mainInput) mainInput.value = '';
  if (productMediaState.pendingMain?.url) URL.revokeObjectURL(productMediaState.pendingMain.url);
  productMediaState.pendingMain = null;
  renderProductMediaPreviews();
}

function removeProductGalleryImage(index) {
  productMediaState.gallery.splice(index, 1);
  renderProductMediaPreviews();
}

function onProductMainImageSelected(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (productMediaState.pendingMain?.url) URL.revokeObjectURL(productMediaState.pendingMain.url);
  productMediaState.pendingMain = { url: URL.createObjectURL(file), name: file.name };
  renderProductMediaPreviews();
}

function onProductGalleryFilesSelected(event) {
  productMediaState.pendingGallery.forEach(item => {
    if (item.url) URL.revokeObjectURL(item.url);
  });
  productMediaState.pendingGallery = Array.from(event.target.files || []).map(file => ({
    url: URL.createObjectURL(file),
    name: file.name,
  }));
  renderProductMediaPreviews();
}

function loadProductMediaFromRow(p) {
  revokeProductPendingUrls();
  productMediaState.pendingMain = null;
  productMediaState.pendingGallery = [];
  productMediaState.main = (p.image || '').trim();
  document.getElementById('edit-product-existing-image').value = productMediaState.main;

  let gallery = [];
  if (Array.isArray(p.additional_images_list) && p.additional_images_list.length) {
    gallery = p.additional_images_list.filter(Boolean);
  } else if (p.additional_images) {
    try {
      const parsed = JSON.parse(p.additional_images);
      if (Array.isArray(parsed)) gallery = parsed.filter(Boolean);
    } catch (_) { /* ignore */ }
  }
  productMediaState.gallery = gallery.filter(path => path !== productMediaState.main);
  renderProductMediaPreviews();
  toggleProductResourcesSection();
  loadProductResources(0);
}

const bundleMediaState = {
  main: '',
  gallery: [],
  pendingMain: null,
  pendingGallery: [],
};

function syncBundleAdditionalImagesField() {
  const hidden = document.getElementById('bundle-additional-images');
  if (!hidden) return;
  const paths = bundleMediaState.gallery.filter(p => p && p !== bundleMediaState.main);
  hidden.value = JSON.stringify(paths);
}

function revokeBundlePendingUrls() {
  if (bundleMediaState.pendingMain?.url) URL.revokeObjectURL(bundleMediaState.pendingMain.url);
  bundleMediaState.pendingGallery.forEach(item => {
    if (item.url) URL.revokeObjectURL(item.url);
  });
}

function renderBundleMediaPreviews() {
  const mainEl = document.getElementById('bundle-main-preview');
  const galleryEl = document.getElementById('bundle-gallery-preview');
  const pendingEl = document.getElementById('bundle-gallery-pending');
  if (!mainEl || !galleryEl || !pendingEl) return;

  if (bundleMediaState.main) {
    mainEl.innerHTML = productMediaThumbHtml(
      '../' + bundleMediaState.main,
      bundleMediaState.main,
      'removeBundleMainImage()'
    );
  } else {
    mainEl.innerHTML = '';
  }

  galleryEl.innerHTML = bundleMediaState.gallery
    .map((path, index) => {
      if (!path || path === bundleMediaState.main) return '';
      return productMediaThumbHtml(
        '../' + path,
        path,
        `removeBundleGalleryImage(${index})`
      );
    })
    .join('');

  if (bundleMediaState.pendingMain || bundleMediaState.pendingGallery.length) {
    let pendingHtml = '<p class="product-media-pending-title">New uploads (saved when you submit the form)</p>';
    if (bundleMediaState.pendingMain) {
      pendingHtml += productMediaThumbHtml(bundleMediaState.pendingMain.url, bundleMediaState.pendingMain.name, '');
    }
    pendingHtml += bundleMediaState.pendingGallery
      .map(item => productMediaThumbHtml(item.url, item.name, ''))
      .join('');
    pendingEl.innerHTML = pendingHtml;
  } else {
    pendingEl.innerHTML = '';
  }

  syncBundleAdditionalImagesField();
}

function clearBundleMediaState() {
  revokeBundlePendingUrls();
  bundleMediaState.main = '';
  bundleMediaState.gallery = [];
  bundleMediaState.pendingMain = null;
  bundleMediaState.pendingGallery = [];
  const galleryInput = document.getElementById('bundle-gallery-input');
  if (galleryInput) galleryInput.value = '';
  renderBundleMediaPreviews();
}

function loadBundleMediaFromRow(row) {
  revokeBundlePendingUrls();
  bundleMediaState.pendingMain = null;
  bundleMediaState.pendingGallery = [];
  bundleMediaState.main = (row.image || '').trim();

  let gallery = [];
  if (Array.isArray(row.additional_images_list) && row.additional_images_list.length) {
    gallery = row.additional_images_list.filter(Boolean);
  } else if (row.additional_images) {
    try {
      const parsed = JSON.parse(row.additional_images);
      if (Array.isArray(parsed)) gallery = parsed.filter(Boolean);
    } catch (_) { /* ignore */ }
  }
  bundleMediaState.gallery = gallery.filter(path => path !== bundleMediaState.main);
  renderBundleMediaPreviews();
}

function removeBundleMainImage() {
  bundleMediaState.main = '';
  const form = document.getElementById('bundle-editor-form');
  if (form?.elements['existing_image']) form.elements['existing_image'].value = '';
  const mainInput = document.getElementById('bundle-cover-image');
  if (mainInput) mainInput.value = '';
  if (bundleMediaState.pendingMain?.url) URL.revokeObjectURL(bundleMediaState.pendingMain.url);
  bundleMediaState.pendingMain = null;
  renderBundleMediaPreviews();
}

function removeBundleGalleryImage(index) {
  bundleMediaState.gallery.splice(index, 1);
  renderBundleMediaPreviews();
}

function onBundleMainImageSelected(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (bundleMediaState.pendingMain?.url) URL.revokeObjectURL(bundleMediaState.pendingMain.url);
  bundleMediaState.pendingMain = { url: URL.createObjectURL(file), name: file.name };
  renderBundleMediaPreviews();
}

function onBundleGalleryFilesSelected(event) {
  bundleMediaState.pendingGallery.forEach(item => {
    if (item.url) URL.revokeObjectURL(item.url);
  });
  bundleMediaState.pendingGallery = Array.from(event.target.files || []).map(file => ({
    url: URL.createObjectURL(file),
    name: file.name,
  }));
  renderBundleMediaPreviews();
}

function syncProductIsFreePriceField() {
  const freeCb = document.getElementById('edit-product-is-free');
  const priceEl = document.getElementById('edit-product-price');
  if (!freeCb || !priceEl) return;
  if (freeCb.checked) {
    priceEl.value = '0';
    priceEl.readOnly = true;
    priceEl.classList.add('is-readonly');
  } else {
    priceEl.readOnly = false;
    priceEl.classList.remove('is-readonly');
  }
}

function initProductIsFreeToggle() {
  const freeCb = document.getElementById('edit-product-is-free');
  if (!freeCb || freeCb.dataset.bound === '1') return;
  freeCb.dataset.bound = '1';
  freeCb.addEventListener('change', syncProductIsFreePriceField);
}

async function openCreateProductModal() {
  if (!state.categories.length) {
    await getCategories();
    populateCategoryControls();
  }
  clearProductForm();
  document.getElementById('product-modal-title').textContent = 'Add Product';
  openEditProductModal();
}

async function editProduct(productId) {
  const data = await fetchJson(`../api/admin/product/get.php?id=${encodeURIComponent(productId)}`);
  const p = data.product || data;
  if (!state.categories.length) {
    await getCategories();
    populateCategoryControls();
  }
  const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value ?? ''; };
  clearProductForm();
  document.getElementById('product-modal-title').textContent = 'Edit Product';
  set('edit-product-id', p.id);
  set('edit-product-existing-image', p.image);
  set('edit-product-name', p.name);
  set('edit-product-sku', p.sku);
  set('edit-product-category', p.category);
  set('edit-product-rating', p.rating);
  set('edit-product-available', p.available_type || 'digital');
  set('edit-product-description', p.description);
  set('edit-product-whats', p.whats_included);
  set('edit-product-specs', p.file_specification);
  set('edit-product-high-resolution', p.high_resolution);
  set('edit-product-compatible-software', p.compatible_software);
  set('edit-product-software-version', p.software_version);
  set('edit-product-files-included', p.files_included);
  set('edit-product-grid-columns', p.grid_columns);
  set('edit-product-layout-type', p.layout_type);
  set('edit-product-license-type', p.license_type);
  set('edit-product-tags', p.tags);
  const customFields = Array.isArray(p.custom_fields_parsed)
    ? p.custom_fields_parsed
    : (() => {
      try {
        return JSON.parse(p.custom_fields || '[]');
      } catch {
        return [];
      }
    })();
  renderProductCustomFields(customFields);
  set('edit-product-price', p.price);
  set('edit-product-old-price', p.old_price);
  set('edit-product-commercial-price', p.commercial_price);
  set('edit-product-stock', p.stock);
  set('edit-product-active', String(p.is_active ?? 1));
  set('edit-product-featured', String(p.is_featured ?? 0));
  const freeCb = document.getElementById('edit-product-is-free');
  if (freeCb) freeCb.checked = Number(p.is_free) === 1;
  syncProductIsFreePriceField();
  loadProductMediaFromRow(p);
  toggleProductResourcesSection();
  loadProductResources(p.id);
  openEditProductModal();
}

async function saveProductForm(event) {
  event.preventDefault();
  const form = event.target;
  syncProductAdditionalImagesField();
  syncProductCustomFieldsHidden();
  document.getElementById('edit-product-existing-image').value = productMediaState.main || '';
  const endpoint = form.querySelector('[name="id"]').value ? '../api/admin/product/update.php' : '../api/admin/product/create.php';
  const submit = form.querySelector('button[type="submit"]');
  const label = submit.textContent;
  submit.disabled = true;
  submit.textContent = 'Saving...';
  try {
    await fetchJson(endpoint, { method: 'POST', body: new FormData(form) });
    revokeProductPendingUrls();
    productMediaState.pendingMain = null;
    productMediaState.pendingGallery = [];
    closeEditProductModal();
    await Promise.all([loadProducts(), loadOverview()]);
    showToast('Product saved.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    submit.disabled = false;
    submit.textContent = label;
  }
}

async function toggleProductStatus(productId, isActive) {
  await fetchJson('../api/admin/product/toggle_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: productId, is_active: isActive }),
  });
  await loadProducts();
}

async function duplicateProduct(productId) {
  await fetchJson('../api/admin/product/duplicate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: productId }),
  });
  await loadProducts();
  showToast('Product duplicated as archived.', 'success');
}

async function deleteProduct(productId) {
  const product = state.products.find(p => p.id == productId);
  const name = product ? escapeHtml(product.name) : `Product #${productId}`;
  const confirmed = await showConfirmModal(
    'Delete Product',
    `Are you sure you want to delete <strong>${name}</strong>?<br><br>If this product has existing orders it will be <em>archived</em> instead of permanently deleted.`
  );
  if (!confirmed) return;
  try {
    const result = await fetchJson('../api/admin/product/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: productId }),
    });
    await loadProducts();
    const action = result?.action || 'deleted';
    showToast(action === 'archived' ? 'Product archived (has existing orders).' : 'Product permanently deleted.', 'success');
  } catch (err) {
    showToast(err.message || 'Could not delete product.', 'error');
  }
}

async function loadAdminCategories() {
  const table = document.getElementById('categories-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="5" class="tbl-empty">Loading categories...</td></tr>';
  try {
    await getCategories();
    populateCategoryControls();
    table.innerHTML = state.categories.length ? state.categories.map(row => `
      <tr>
        <td>
          <div class="cell-name">${escapeHtml(row.name)}</div>
          <div class="cell-sub">${Number(row.product_count || 0)} products</div>
        </td>
        <td><code style="font-size:.75rem;background:var(--surface-3);padding:2px 6px;border-radius:4px;">${escapeHtml(row.slug)}</code></td>
        <td style="max-width:220px;white-space:normal;font-size:.8rem;color:var(--text-2);">${escapeHtml(row.description || '—')}</td>
        <td>${row.is_active == 1 ? getStatusBadge('active') : getStatusBadge('archived')}</td>
        <td>
          <div class="tbl-actions">
            <button class="btn btn-xs btn-ghost" onclick='adminEditCategory(${Number(row.id)})'>Edit</button>
            <button class="btn btn-xs ${row.is_active == 1 ? 'btn-ghost' : 'btn-success-ghost'}" onclick='adminToggleCategory(${Number(row.id)}, ${row.is_active == 1 ? 0 : 1})'>${row.is_active == 1 ? 'Hide' : 'Show'}</button>
            <button class="btn btn-xs btn-danger-ghost" onclick='adminDeleteCategory(${Number(row.id)})'>Delete</button>
          </div>
        </td>
      </tr>
    `).join('') : '<tr><td colspan="5" class="tbl-empty">No categories found</td></tr>';
  } catch (err) {
    table.innerHTML = `<tr><td colspan="5" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

async function adminSaveCategory(event) {
  event.preventDefault();
  const form = event.target;
  try {
    await fetchJson('../api/admin/categories/save.php', { method: 'POST', body: new FormData(form) });
    form.reset();
    closeCategoryForm();
    await Promise.all([loadAdminCategories(), loadProducts(), loadOverview()]);
    showToast('Category saved.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function adminToggleCategory(id, isActive) {
  const row = state.categories.find(c => Number(c.id) === Number(id));
  if (!row) return;
  try {
    await fetchJson('../api/admin/categories/save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id: row.id,
        name: row.name,
        description: row.description || '',
        accent: row.accent || 'purple',
        sort_order: row.sort_order ?? 0,
        is_active: isActive,
        existing_icon: row.icon || '',
      }),
    });
    await Promise.all([loadAdminCategories(), loadProducts()]);
    showToast(isActive ? 'Category shown.' : 'Category hidden.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function adminDeleteCategory(id) {
  if (!confirm('Delete this empty category?')) return;
  try {
    await fetchJson('../api/admin/categories/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    });
    await loadAdminCategories();
    showToast('Category deleted.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}


async function loadAdminBundles() {
  const table = document.getElementById('bundles-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="5" class="tbl-empty">Loading bundles...</td></tr>';
  try {
    await getBundles();
    table.innerHTML = state.bundles.length ? state.bundles.map(row => `
      <tr>
        <td>
          <div class="cell-with-img">
            <img class="cell-img" src="../${escapeHtml(row.image || 'img/poster.webp')}" onerror="this.src='../img/poster.webp'">
            <div>
              <div class="cell-name">${escapeHtml(row.name)}</div>
              <div class="cell-sub">${Number(row.product_count || 0)} products</div>
            </div>
          </div>
        </td>
        <td>${money(row.price)}${row.old_price > 0 ? `<div class="cell-sub" style="text-decoration:line-through;">${money(row.old_price)}</div>` : ''}</td>
        <td>${row.is_featured == 1 ? '<span class="badge badge-info">Best Seller</span>' : '<span class="badge badge-neutral">Standard</span>'}</td>
        <td>${row.is_active == 1 ? getStatusBadge('active') : getStatusBadge('archived')}</td>
        <td>
          <div class="tbl-actions">
            <button class="btn btn-xs btn-ghost" onclick='adminEditBundle(${Number(row.id)})'>Edit</button>
            <button class="btn btn-xs ${row.is_featured == 1 ? 'btn-ghost' : 'btn-success-ghost'}" onclick='adminToggleBundle(${Number(row.id)}, "is_featured")'>${row.is_featured == 1 ? 'Unfeature' : 'Feature'}</button>
            <button class="btn btn-xs ${row.is_active == 1 ? 'btn-ghost' : 'btn-success-ghost'}" onclick='adminToggleBundle(${Number(row.id)}, "is_active")'>${row.is_active == 1 ? 'Hide' : 'Show'}</button>
            <button class="btn btn-xs btn-danger-ghost" onclick='adminDeleteBundle(${Number(row.id)})'>Delete</button>
          </div>
        </td>
      </tr>
    `).join('') : '<tr><td colspan="5" class="tbl-empty">No bundles found</td></tr>';
  } catch (err) {
    table.innerHTML = `<tr><td colspan="5" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

async function adminSaveBundle(event) {
  event.preventDefault();
  const form = event.target;
  syncBundleAdditionalImagesField();
  if (form.elements['existing_image']) {
    form.elements['existing_image'].value = bundleMediaState.main || '';
  }
  const fd = new FormData(form);
  const isFeaturedEl = form.querySelector('input[name="is_featured"]');
  const isActiveEl = form.querySelector('input[name="is_active"]');
  fd.set('is_featured', isFeaturedEl && isFeaturedEl.checked ? '1' : '0');
  fd.set('is_active', isActiveEl && isActiveEl.checked ? '1' : '0');
  fd.set('additional_images', document.getElementById('bundle-additional-images')?.value || '[]');
  // whats_included is a textarea — server derives included_items JSON from it
  try {
    await fetchJson('../api/admin/bundles/save.php', { method: 'POST', body: fd });
    revokeBundlePendingUrls();
    bundleMediaState.pendingMain = null;
    bundleMediaState.pendingGallery = [];
    form.reset();
    clearBundleMediaState();
    closeBundleForm();
    await Promise.all([loadAdminBundles(), loadOverview()]);
    showToast('Bundle saved.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

function adminEditBundle(id) {
  const row = state.bundles.find(b => Number(b.id) === Number(id));
  if (!row) return;

  const form = document.getElementById('bundle-editor-form');
  if (!form) return;

  form.elements['id'].value = String(row.id);
  form.elements['existing_image'].value = row.image || '';
  loadBundleMediaFromRow(row);
  form.elements['name'].value = row.name || '';
  form.elements['price'].value = row.price ?? '';
  form.elements['old_price'].value = row.old_price ?? '';
  form.elements['category'].value = row.category || '';
  form.elements['badge_text'].value = row.badge_text || '';
  form.elements['tags'].value = row.tags || '';
  form.elements['stock'].value = row.stock ?? '';
  form.elements['rating'].value = row.rating ?? '';
  form.elements['description'].value = row.description || '';
  form.elements['product_ids'].value = '';

  // whats_included: stored as plain text (newline-separated)
  // Fall back to deriving from included_items_list if whats_included is empty
  let whatsIncluded = row.whats_included || '';
  if (!whatsIncluded.trim() && Array.isArray(row.included_items_list) && row.included_items_list.length) {
    whatsIncluded = row.included_items_list
      .map(it => (typeof it === 'string' ? it : String(it.label ?? it.name ?? '')).trim())
      .filter(Boolean)
      .join('\n');
  }
  form.elements['whats_included'].value = whatsIncluded;
  form.elements['file_specification'].value = row.file_specification || '';

  const isFeaturedEl = form.querySelector('input[name="is_featured"]');
  const isActiveEl = form.querySelector('input[name="is_active"]');
  if (isFeaturedEl) isFeaturedEl.checked = Number(row.is_featured) === 1;
  if (isActiveEl) isActiveEl.checked = Number(row.is_active) === 1;

  const submitBtn = document.getElementById('bundle-submit-btn');
  const cancelBtn = document.getElementById('bundle-cancel-btn');
  const formTitle = document.getElementById('bundle-form-title');
  if (submitBtn) submitBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Update Bundle';
  if (cancelBtn) cancelBtn.style.display = 'inline-flex';
  if (formTitle) formTitle.textContent = 'Edit Bundle';

  loadBundleResources(row.id);

  // Show panel and scroll into view
  const panel = document.getElementById('bundle-form-panel');
  if (panel) panel.style.display = 'block';
  form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function adminCancelBundleEdit() {
  const form = document.getElementById('bundle-editor-form');
  if (!form) return;

  form.reset();
  form.elements['id'].value = '';
  form.elements['existing_image'].value = '';
  clearBundleMediaState();
  bundleResourcesState.bundleId = 0;
  bundleResourcesState.rows = [];
  const bResList = document.getElementById('bundle-resources-list');
  if (bResList) bResList.innerHTML = '<p class="form-hint">Save the bundle first, then add digital resources.</p>';

  const submitBtn = document.getElementById('bundle-submit-btn');
  const cancelBtn = document.getElementById('bundle-cancel-btn');
  const formTitle = document.getElementById('bundle-form-title');
  if (submitBtn) submitBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Bundle';
  if (cancelBtn) cancelBtn.style.display = 'none';
  if (formTitle) formTitle.textContent = 'Add New Bundle';
}

function openBundleForm() {
  const panel = document.getElementById('bundle-form-panel');
  if (!panel) return;
  adminCancelBundleEdit();
  panel.style.display = 'block';
  panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeBundleForm() {
  const panel = document.getElementById('bundle-form-panel');
  if (!panel) return;
  adminCancelBundleEdit();
  panel.style.display = 'none';
}

function renderCategoryIconPreview(path) {
  const el = document.getElementById('category-icon-preview');
  if (!el) return;
  if (!path) {
    el.innerHTML = '';
    return;
  }
  el.innerHTML = productMediaThumbHtml('../' + path, path, 'removeCategoryIcon()');
}

function removeCategoryIcon() {
  document.getElementById('category-existing-icon').value = '';
  const fileInput = document.querySelector('#category-form-panel [name="image"]');
  if (fileInput) fileInput.value = '';
  renderCategoryIconPreview('');
}

function onCategoryIconSelected(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  const url = URL.createObjectURL(file);
  const el = document.getElementById('category-icon-preview');
  if (el) {
    el.innerHTML = productMediaThumbHtml(url, file.name, '');
  }
}

function openCategoryForm() {
  const panel = document.getElementById('category-form-panel');
  if (!panel) return;
  const form = panel.querySelector('form');
  if (form) form.reset();
  document.getElementById('category-id').value = '';
  document.getElementById('category-slug').value = '';
  document.getElementById('category-existing-icon').value = '';
  renderCategoryIconPreview('');
  const formTitle = document.getElementById('category-form-title');
  if (formTitle) formTitle.textContent = 'Add New Category';
  const submitBtn = document.getElementById('category-submit-btn');
  if (submitBtn) submitBtn.textContent = 'Add Category';
  panel.style.display = 'block';
  panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeCategoryForm() {
  const panel = document.getElementById('category-form-panel');
  if (!panel) return;
  const form = panel.querySelector('form');
  if (form) form.reset();
  document.getElementById('category-id').value = '';
  document.getElementById('category-slug').value = '';
  document.getElementById('category-existing-icon').value = '';
  renderCategoryIconPreview('');
  panel.style.display = 'none';
}

function adminEditCategory(id) {
  const row = state.categories.find(c => Number(c.id) === Number(id));
  if (!row) return;
  const panel = document.getElementById('category-form-panel');
  if (!panel) return;
  const form = panel.querySelector('form');
  if (form) form.reset();

  document.getElementById('category-id').value = row.id;
  document.getElementById('category-slug').value = row.slug || '';
  document.getElementById('category-existing-icon').value = row.icon || row.image || '';
  renderCategoryIconPreview(row.icon || row.image || '');

  const nameInput = form.querySelector('[name="name"]');
  if (nameInput) nameInput.value = row.name || '';
  const descInput = form.querySelector('[name="description"]');
  if (descInput) descInput.value = row.description || '';
  const accentInput = form.querySelector('[name="accent"]');
  if (accentInput) accentInput.value = row.accent || 'purple';
  const sortInput = form.querySelector('[name="sort_order"]');
  if (sortInput) sortInput.value = row.sort_order ?? 0;

  document.getElementById('category-form-title').textContent = 'Edit Category';
  document.getElementById('category-submit-btn').textContent = 'Save Changes';

  panel.style.display = 'block';
  panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function adminToggleBundle(id, field) {
  const row = state.bundles.find(b => Number(b.id) === Number(id));
  if (!row) return;
  const payload = { ...row };
  payload[field] = row[field] == 1 ? 0 : 1;
  payload.included_items = JSON.stringify(row.included_items_list || []);
  await fetchJson('../api/admin/bundles/save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  await loadAdminBundles();
}

async function adminDeleteBundle(id) {
  const bundle = state.bundles.find(b => b.id == id);
  const name = bundle ? escapeHtml(bundle.name) : `Bundle #${id}`;
  const confirmed = await showConfirmModal(
    'Delete Bundle',
    `Are you sure you want to delete <strong>${name}</strong>?<br><br>If this bundle has existing orders it will be <em>archived</em> instead of permanently deleted. All attached digital resources will also be removed.`
  );
  if (!confirmed) return;
  try {
    const result = await fetchJson('../api/admin/bundles/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    });
    await loadAdminBundles();
    const action = result?.action || 'deleted';
    showToast(action === 'archived' ? 'Bundle archived (has existing orders).' : 'Bundle permanently deleted.', 'success');
  } catch (err) {
    showToast(err.message || 'Could not delete bundle.', 'error');
  }
}

async function loadReviews(options = {}) {
  const table = document.getElementById('reviews-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="6" class="tbl-empty">Loading reviews...</td></tr>';
  try {
    const params = new URLSearchParams();
    if (options.q) params.set('q', options.q);
    if (options.status !== undefined && options.status !== '') params.set('status', String(options.status));
    const qs = params.toString();
    const rows = await fetchJson('../api/admin/reviews/list.php' + (qs ? `?${qs}` : ''));
    if (!rows.length) {
      const hasFilter = Boolean(options.q || (options.status !== undefined && options.status !== ''));
      table.innerHTML = `<tr><td colspan="6" class="tbl-empty">${hasFilter ? 'No reviews match your search.' : 'No reviews yet. Add test rows in the database or wait for customer submissions — approved reviews show on product and bundle pages.'}</td></tr>`;
      return;
    }
    table.innerHTML = rows.map(r => {
      const rating = Math.min(5, Math.max(0, Number(r.rating || 0)));
      const itemLabel = r.item_type === 'bundle' ? 'Bundle' : (r.item_type === 'product' ? 'Product' : '—');
      return `
      <tr>
        <td>
          <div class="cell-name">${escapeHtml(r.product_name || 'Unknown')}</div>
          <div class="cell-sub">${itemLabel}</div>
        </td>
        <td>
          <div class="cell-name">${escapeHtml(r.user_name || r.reviewer_name || 'Guest')}</div>
          ${r.user_email ? `<div class="cell-sub">${escapeHtml(r.user_email)}</div>` : ''}
        </td>
        <td><span style="color:#f59e0b;letter-spacing:1px;" title="${rating}/5">${'★'.repeat(rating)}${'☆'.repeat(5 - rating)}</span></td>
        <td style="max-width:260px;white-space:normal;font-size:.81rem;color:var(--text-2);">${escapeHtml(r.comment || r.body || '—')}</td>
        <td>${r.is_approved == 1 ? getStatusBadge('active') : getStatusBadge('pending')}</td>
        <td>
          <div class="tbl-actions">
            <button class="btn btn-xs ${r.is_approved == 1 ? 'btn-ghost' : 'btn-success-ghost'}" onclick="toggleReviewApproval(${Number(r.id)}, ${r.is_approved == 1 ? 0 : 1})">${r.is_approved == 1 ? 'Unapprove' : 'Approve'}</button>
            <button class="btn btn-xs btn-danger-ghost" onclick="deleteReview(${Number(r.id)})">Delete</button>
          </div>
        </td>
      </tr>`;
    }).join('');
  } catch (err) {
    table.innerHTML = `<tr><td colspan="6" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

function filterReviews() {
  const q = document.getElementById('review-search')?.value.trim() || '';
  const status = document.getElementById('review-status-filter')?.value ?? '';
  loadReviews({ q, status });
}

async function toggleReviewApproval(id, approve) {
  try {
    await fetchJson('../api/admin/reviews/moderate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, approve }),
    });
    await loadReviews({
      q: document.getElementById('review-search')?.value.trim() || '',
      status: document.getElementById('review-status-filter')?.value ?? '',
    });
    showToast(approve ? 'Review approved.' : 'Review moved to pending.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function deleteReview(id) {
  if (!confirm('Delete this review permanently?')) return;
  try {
    await fetchJson('../api/admin/reviews/moderate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, delete: 1 }),
    });
    await loadReviews({
      q: document.getElementById('review-search')?.value.trim() || '',
      status: document.getElementById('review-status-filter')?.value ?? '',
    });
    showToast('Review deleted.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

function filterMessages() {
  loadMessages();
}

async function loadMessages() {
  const table = document.getElementById('messages-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="6" class="tbl-empty">Loading messages...</td></tr>';
  const q = document.getElementById('message-search')?.value.trim() || '';
  const status = document.getElementById('message-status-filter')?.value ?? '';
  const params = new URLSearchParams();
  if (q) params.set('q', q);
  if (status === '0' || status === '1') params.set('status', status);
  const url = '../api/admin/messages/list.php' + (params.toString() ? `?${params}` : '');
  try {
    const rows = await fetchJson(url);
    if (!rows.length) {
      table.innerHTML = '<tr><td colspan="6" class="tbl-empty">No messages found</td></tr>';
      return;
    }
    table.innerHTML = rows.map(m => {
      const isUnread = Number(m.is_read) !== 1;
      const phone = m.phone ? `<div style="font-size:.72rem;color:var(--text-3);margin-top:2px;">${escapeHtml(m.phone)}</div>` : '';
      return `
      <tr class="${isUnread ? 'msg-row-unread' : ''}">
        <td>
          <div class="cell-name">
            ${isUnread ? '<span class="cell-read-dot" title="Unread"></span>' : ''}
            ${escapeHtml(m.name || 'N/A')}
          </div>
        </td>
        <td style="font-size:.8rem;color:var(--text-2);">
          <a href="mailto:${escapeHtml(m.email || '')}" style="color:inherit;">${escapeHtml(m.email || 'N/A')}</a>
          ${phone}
        </td>
        <td><div class="cell-name" style="font-weight:500;">${escapeHtml(m.subject || 'General')}</div></td>
        <td style="max-width:280px;white-space:normal;font-size:.8rem;color:var(--text-2);">${escapeHtml(String(m.message || '').substring(0, 130))}${String(m.message || '').length > 130 ? '…' : ''}</td>
        <td style="white-space:nowrap;font-size:.79rem;color:var(--text-3);">${new Date(m.created_at || Date.now()).toLocaleDateString()}</td>
        <td>
          <div class="tbl-actions">
            ${isUnread
              ? `<button class="btn btn-xs btn-success-ghost" onclick="markMessageRead(${Number(m.id)})">Mark Read</button>`
              : `<button class="btn btn-xs btn-ghost" onclick="markMessageUnread(${Number(m.id)})">Mark Unread</button>`}
            <button class="btn btn-xs btn-ghost" onclick="archiveMessage(${Number(m.id)})">Archive</button>
            <button class="btn btn-xs btn-danger-ghost" onclick="deleteMessage(${Number(m.id)})">Delete</button>
          </div>
        </td>
      </tr>`;
    }).join('');
  } catch (err) {
    table.innerHTML = `<tr><td colspan="6" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

async function messageAction(id, action, successMsg) {
  try {
    await fetchJson('../api/admin/messages/update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, action }),
    });
    showToast(successMsg, 'success');
    await loadMessages();
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function markMessageRead(id) {
  await messageAction(id, 'read', 'Message marked as read.');
}

async function markMessageUnread(id) {
  await messageAction(id, 'unread', 'Message marked as unread.');
}

async function archiveMessage(id) {
  if (!confirm('Archive this message? It will be hidden from the inbox.')) return;
  await messageAction(id, 'archive', 'Message archived.');
}

async function deleteMessage(id) {
  if (!confirm('Delete this message permanently?')) return;
  await messageAction(id, 'delete', 'Message deleted.');
}

async function loadOrders() {
  const table = document.getElementById('orders-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="8" class="tbl-empty">Loading orders...</td></tr>';
  try {
    await getOrders();
    renderOrders();
  } catch (err) {
    table.innerHTML = `<tr><td colspan="8" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

function renderOrders() {
  const table = document.getElementById('orders-table');
  const q = document.getElementById('order-search')?.value.toLowerCase().trim() || '';
  const filterRaw = (document.getElementById('order-status-filter')?.value || '').trim();
  const filter = filterRaw ? normalizeStatus(filterRaw) : '';
  const statusMatches = (orderStatus, filterKey) => {
    if (!filterKey) return true;
    const normalized = normalizeStatus(orderStatus);
    if (normalized === filterKey) return true;
    if (filterKey === 'cancelled' && normalized === 'failed') return true;
    if (filterKey === 'paid' && normalized === 'processing') return true;
    return false;
  };
  const rows = state.orders.filter(order => {
    const text = `${order.order_number || ''} ${getOrderCustomerName(order)} ${getOrderEmail(order)} ${order.status || ''}`.toLowerCase();
    return (!q || text.includes(q)) && statusMatches(order.status, filter);
  });
  const meta = state.ordersMeta;
  const moreHint = meta?.has_more
    ? `<tr><td colspan="8" class="tbl-empty" style="font-size:.8rem;">Showing ${state.orders.length} of ${meta.total} orders. Use search or contact dev to raise list limit.</td></tr>`
    : '';
  if (!rows.length) {
    table.innerHTML = '<tr><td colspan="8" class="tbl-empty">No orders found</td></tr>';
    return;
  }
  table.innerHTML = rows.map(order => `
    <tr>
      <td><div class="cell-name">${escapeHtml(order.order_number || 'N/A')}</div></td>
      <td>
        <div class="cell-name">${escapeHtml(getOrderCustomerName(order))}</div>
        <div class="cell-sub">${escapeHtml(getOrderEmail(order))}</div>
      </td>
      <td style="font-size:.8rem;">${Number(order.items_count || 0)} item(s)</td>
      <td style="font-size:.79rem;color:var(--text-2);white-space:nowrap;">${new Date(order.created_at || Date.now()).toLocaleDateString()}</td>
      <td style="font-weight:600;">${money(order.total)}</td>
      <td><span class="badge badge-neutral">${escapeHtml(order.payment_method || 'card')}</span></td>
      <td>${getStatusBadge(order.status)}</td>
      <td>
        <div class="tbl-actions">
          <button class="btn btn-xs btn-ghost" onclick="viewOrder(${Number(order.id)})">View</button>
          <button class="btn btn-xs btn-ghost" onclick="openStatusEditor(${Number(order.id)})">Status</button>
          <button class="btn btn-xs btn-danger-ghost" onclick="deleteOrder(${Number(order.id)})">Delete</button>
        </div>
      </td>
    </tr>
  `).join('') + moreHint;
}

function filterOrders() {
  renderOrders();
}

function openStatusEditor(orderId) {
  const order = state.orders.find(o => Number(o.id) === Number(orderId));
  if (!order) return;
  document.getElementById('modal-order-id').value = String(order.id);
  document.getElementById('modal-order-number').textContent = order.order_number || String(order.id);
  document.getElementById('modal-order-customer').textContent = getOrderCustomerName(order);
  document.getElementById('modal-current-status').innerHTML = getStatusBadge(order.status);
  document.getElementById('status-select').value = normalizeStatus(order.status);
  openStatusModal();
}

async function confirmStatusUpdate() {
  const orderId = Number(document.getElementById('modal-order-id')?.value || 0);
  const status = document.getElementById('status-select').value;
  if (!orderId || !status) {
    showToast('Select a valid status.', 'error');
    return;
  }
  try {
    await fetchJson('../api/admin/order/update_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: orderId, status }),
    });
    closeStatusModal();
    await Promise.all([loadOrders(), loadOverview()]);
    showToast('Order status updated.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function viewOrder(orderId) {
  const content = document.getElementById('order-details-content');
  content.innerHTML = '<div class="tbl-empty">Loading order...</div>';
  openOrderDetailsModal();
  try {
    const data = await fetchJson(`../api/admin/order/get_details.php?id=${encodeURIComponent(orderId)}`);
    const items = data.items || [];
    const subtotal = Number(data.subtotal ?? 0);
    const shipping = Number(data.shipping ?? 0);
    const tax = Number(data.tax ?? 0);
    content.innerHTML = `
      <div class="order-info-grid">
        <div class="order-info-block">
          <h3>Customer</h3>
          <p>${escapeHtml(getOrderCustomerName(data))}</p>
          <p>${escapeHtml(getOrderEmail(data))}</p>
          ${data.phone ? `<p>${escapeHtml(data.phone)}</p>` : ''}
        </div>
        <div class="order-info-block">
          <h3>Order</h3>
          <p>${escapeHtml(data.order_number || String(data.id))}</p>
          <p>${getStatusBadge(data.status)}</p>
          <p style="font-size:.8rem;color:var(--text-2);margin-top:4px;">${new Date(data.created_at || Date.now()).toLocaleString()}</p>
          <p style="font-weight:700;font-size:1rem;margin-top:4px;">${money(data.total)}</p>
          <p style="font-size:.75rem;color:var(--text-3);margin-top:4px;">Subtotal ${money(subtotal)} · Shipping ${money(shipping)} · Tax ${money(tax)}</p>
        </div>
        <div class="order-info-block">
          <h3>Payment</h3>
          <p>${escapeHtml(data.payment_method || '—')}</p>
          ${data.payment_id ? `<p style="font-size:.8rem;">ID: ${escapeHtml(data.payment_id)}</p>` : ''}
          ${data.razorpay_order_id ? `<p style="font-size:.8rem;">Razorpay: ${escapeHtml(data.razorpay_order_id)}</p>` : ''}
        </div>
        <div class="order-info-block">
          <h3>Shipping</h3>
          <p style="font-size:.85rem;line-height:1.5;">${formatOrderShipping(data)}</p>
        </div>
      </div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
          <tbody>${items.map(item => `
            <tr>
              <td><div class="cell-name">${escapeHtml(item.name || item.product_name || 'Item')}</div></td>
              <td>${Number(item.quantity || 0)}</td>
              <td>${money(item.price)}</td>
              <td style="font-weight:600;">${money(Number(item.price || 0) * Number(item.quantity || 0))}</td>
            </tr>
          `).join('')}</tbody>
        </table>
      </div>
    `;
  } catch (err) {
    content.innerHTML = `<div class="tbl-empty">${escapeHtml(err.message)}</div>`;
  }
}

async function deleteOrder(orderId) {
  if (!confirm('Delete this order? This cannot be undone.')) return;
  try {
    await fetchJson('../api/admin/order/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: orderId }),
    });
    await Promise.all([loadOrders(), loadOverview()]);
    showToast('Order deleted.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function loadUsers(options = {}) {
  const table = document.getElementById('users-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="7" class="tbl-empty">Loading users...</td></tr>';
  try {
    await getUsers(options);
    renderUsers();
  } catch (err) {
    table.innerHTML = `<tr><td colspan="7" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

function renderUsers() {
  const table = document.getElementById('users-table');
  const rows = state.users;
  if (!rows.length) {
    table.innerHTML = '<tr><td colspan="7" class="tbl-empty">No users found</td></tr>';
    return;
  }
  table.innerHTML = rows.map(user => {
    const name = user.name || `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.username || 'N/A';
    const initial = name.charAt(0).toUpperCase();
    const blocked = user.is_blocked == 1;
    const privileged = userIsPrivileged(user);
    const actionCell = privileged
      ? '<span class="cell-sub">Admin account</span>'
      : `<button class="btn btn-xs ${blocked ? 'btn-success-ghost' : 'btn-danger-ghost'}" onclick="toggleUserBlock(${Number(user.id)}, ${blocked ? 0 : 1})">${blocked ? 'Unblock' : 'Block'}</button>`;
    return `
      <tr>
        <td>
          <div class="cell-with-img">
            <div class="cell-avatar">${initial}</div>
            <div>
              <div class="cell-name">${escapeHtml(name)}</div>
              <div class="cell-sub">${escapeHtml(user.role || 'customer')}</div>
            </div>
          </div>
        </td>
        <td style="font-size:.8rem;color:var(--text-2);">${escapeHtml(user.email || 'N/A')}</td>
        <td style="font-size:.8rem;color:var(--text-2);">${escapeHtml(user.phone || '—')}</td>
        <td style="font-size:.79rem;color:var(--text-3);white-space:nowrap;">${user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}</td>
        <td style="font-weight:600;">${Number(user.order_count || 0)}</td>
        <td>${blocked ? getStatusBadge('blocked') : getStatusBadge('active')}</td>
        <td>${actionCell}</td>
      </tr>
    `;
  }).join('');
}

function filterUsers() {
  const q = document.getElementById('user-search')?.value.trim() || '';
  loadUsers({ q });
}

async function toggleUserBlock(userId, block) {
  try {
    await fetchJson('../api/admin/user/block.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: userId, action: block ? 'block' : 'unblock' }),
    });
    await loadUsers();
    showToast(block ? 'User blocked.' : 'User unblocked.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

function openEditProductModal() {
  document.getElementById('edit-product-modal-overlay')?.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeEditProductModal() {
  document.getElementById('edit-product-modal-overlay')?.classList.remove('active');
  document.body.style.overflow = '';
}

function openOrderDetailsModal() {
  document.getElementById('order-details-modal-overlay')?.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeOrderDetailsModal() {
  document.getElementById('order-details-modal-overlay')?.classList.remove('active');
  document.body.style.overflow = '';
}

function openStatusModal() {
  document.getElementById('status-modal-overlay')?.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeStatusModal() {
  document.getElementById('status-modal-overlay')?.classList.remove('active');
  document.body.style.overflow = '';
}

function handleAdminLogout() {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  fetch('../api/auth/logout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
    body: JSON.stringify({})
  }).finally(() => { window.location.href = 'admin-login.php'; });
}

/* ─────────────────────────────────────────────
   FREEBIES ADMIN
───────────────────────────────────────────── */

function renderFreebieImagePreview(path) {
  const el = document.getElementById('freebie-image-preview');
  if (!el) return;
  if (!path) {
    el.innerHTML = '';
    return;
  }
  el.innerHTML = productMediaThumbHtml('../' + path, path, 'removeFreebieImage()');
}

function removeFreebieImage() {
  document.getElementById('freebie-existing-image').value = '';
  const fileInput = document.getElementById('freebie-cover-image');
  if (fileInput) fileInput.value = '';
  renderFreebieImagePreview('');
}

function onFreebieCoverSelected(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  const el = document.getElementById('freebie-image-preview');
  if (el) {
    el.innerHTML = productMediaThumbHtml(URL.createObjectURL(file), file.name, '');
  }
}

function resetFreebieForm() {
  const form = document.getElementById('freebie-form');
  if (form) form.reset();
  document.getElementById('freebie-id').value = '0';
  document.getElementById('freebie-existing-image').value = '';
  document.getElementById('freebie-is-active').checked = true;
  document.getElementById('freebie-is-featured').checked = false;
  document.getElementById('freebie-form-title').textContent = 'Add New Freebie';
  document.getElementById('freebie-submit-btn').innerHTML = `
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Freebie`;
  renderFreebieImagePreview('');
  loadFreebieResources(0);
}

function openFreebiesForm() {
  resetFreebieForm();
  document.getElementById('freebie-form-panel').style.display = '';
  document.getElementById('freebie-form-panel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeFreebiesForm() {
  document.getElementById('freebie-form-panel').style.display = 'none';
  resetFreebieForm();
}

async function loadAdminFreebies(q) {
  const table = document.getElementById('freebies-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="5" class="tbl-empty">Loading freebies...</td></tr>';
  try {
    const url = '../api/admin/freebies/list.php' + (q ? '?q=' + encodeURIComponent(q) : '');
    state.freebies = await fetchJson(url);
    table.innerHTML = state.freebies.length
      ? state.freebies.map(row => `
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              ${row.image ? `<img src="../${escapeHtml(row.image)}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;flex-shrink:0;" alt="" />` : ''}
              <div>
                <div class="cell-name">${escapeHtml(row.name)}</div>
                <div class="cell-sub" style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(row.description || '—')}</div>
              </div>
            </div>
          </td>
          <td><span style="font-size:.8rem;padding:2px 8px;background:rgba(111,75,255,.12);border-radius:999px;color:var(--accent-soft);">${escapeHtml(row.category || 'General')}</span></td>
          <td>${Number(row.download_count || 0).toLocaleString()}</td>
          <td>${row.is_active == 1 ? getStatusBadge('active') : getStatusBadge('archived')}</td>
          <td>
            <div class="tbl-actions">
              <button class="btn btn-xs btn-ghost" onclick='adminEditFreebie(${Number(row.id)})'>Edit</button>
              <button class="btn btn-xs ${row.is_active == 1 ? 'btn-ghost' : 'btn-success-ghost'}" onclick='adminToggleFreebie(${Number(row.id)}, ${row.is_active == 1 ? 0 : 1})'>${row.is_active == 1 ? 'Hide' : 'Show'}</button>
              <button class="btn btn-xs btn-danger-ghost" onclick='adminDeleteFreebie(${Number(row.id)})'>Delete</button>
            </div>
          </td>
        </tr>
      `).join('')
      : '<tr><td colspan="5" class="tbl-empty">No freebies found</td></tr>';
  } catch (err) {
    table.innerHTML = `<tr><td colspan="5" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

function filterAdminFreebies(q) {
  loadAdminFreebies(q.trim());
}

function adminEditFreebie(id) {
  const row = state.freebies.find(f => Number(f.id) === Number(id));
  if (!row) return;
  openFreebiesForm();
  document.getElementById('freebie-id').value = row.id;
  document.getElementById('freebie-name').value = row.name || '';
  document.getElementById('freebie-category').value = row.category || '';
  document.getElementById('freebie-file-url').value = row.file_url || '';
  document.getElementById('freebie-sort-order').value = row.sort_order || 0;
  document.getElementById('freebie-description').value = row.description || '';
  document.getElementById('freebie-is-active').checked = row.is_active == 1;
  document.getElementById('freebie-is-featured').checked = row.is_featured == 1;
  document.getElementById('freebie-existing-image').value = row.image || '';
  renderFreebieImagePreview(row.image || '');
  document.getElementById('freebie-form-title').textContent = 'Edit Freebie';
  document.getElementById('freebie-submit-btn').innerHTML = `
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:15px;height:15px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
    Save Changes`;
  loadFreebieResources(row.id);
}

async function adminSaveFreebies(event) {
  event.preventDefault();
  const form = event.target;
  document.getElementById('freebie-existing-image').value =
    document.getElementById('freebie-existing-image').value || '';
  const fd = new FormData(form);
  if (document.getElementById('freebie-is-active').checked) fd.set('is_active', '1');
  else fd.set('is_active', '0');
  if (document.getElementById('freebie-is-featured').checked) fd.set('is_featured', '1');
  else fd.set('is_featured', '0');
  const fileUrl = (document.getElementById('freebie-file-url')?.value || '').trim();
  if (fileUrl && !/^https?:\/\//i.test(fileUrl)) {
    showToast('Legacy link must be a valid https:// URL, or leave it blank.', 'error');
    return;
  }
  try {
    const saved = await fetchJson('../api/admin/freebies/save.php', { method: 'POST', body: fd });
    const freebieId = Number(saved?.id || document.getElementById('freebie-id')?.value || 0);
    if (freebieId > 0) {
      document.getElementById('freebie-id').value = String(freebieId);
      await loadFreebieResources(freebieId);
    }
    await loadAdminFreebies();
    showToast('Freebie saved.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function adminToggleFreebie(id, isActive) {
  const row = state.freebies.find(f => Number(f.id) === Number(id));
  if (!row) return;
  try {
    await fetchJson('../api/admin/freebies/save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id: row.id,
        name: row.name,
        description: row.description || '',
        category: row.category || 'General',
        file_url: row.file_url || '',
        sort_order: row.sort_order ?? 0,
        is_active: isActive,
        is_featured: row.is_featured ?? 0,
        existing_image: row.image || '',
      }),
    });
    await loadAdminFreebies();
    showToast(isActive ? 'Freebie shown.' : 'Freebie hidden.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function adminDeleteFreebie(id) {
  if (!confirm('Delete this freebie? This cannot be undone.')) return;
  try {
    await fetchJson('../api/admin/freebies/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    });
    await loadAdminFreebies();
    showToast('Freebie deleted.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

const productResourcesState = { rows: [], productId: 0 };
const RESOURCE_TYPES = ['file', 'zip', 'pdf', 'canva', 'figma', 'external_link', 'instructions'];

function resourceDeliveryMode(type) {
  if (['canva', 'figma', 'external_link'].includes(type)) return 'open_link';
  if (type === 'instructions') return 'instructions';
  return 'download';
}

// ── Resource Modal ────────────────────────────────────────────────────────────
const resourceModalState = { ownerKey: '', ownerId: 0, editId: 0 };

const RESOURCE_TYPE_COLORS = {
  file: '#6366f1', zip: '#8b5cf6', pdf: '#ef4444',
  canva: '#06b6d4', figma: '#f59e0b', external_link: '#10b981',
  instructions: '#64748b',
};

function openResourceModal(ownerKey, ownerId, existingRow) {
  resourceModalState.ownerKey = ownerKey;
  resourceModalState.ownerId = Number(ownerId) || 0;
  resourceModalState.editId = existingRow ? Number(existingRow.id) : 0;

  document.getElementById('resource-modal-title').textContent = existingRow ? 'Edit Resource' : 'Add Resource';
  document.getElementById('rm-id').value = resourceModalState.editId || '';
  document.getElementById('rm-owner-key').value = ownerKey;
  document.getElementById('rm-owner-id').value = resourceModalState.ownerId;

  const type = existingRow ? (existingRow.resource_type || 'file') : 'file';
  document.getElementById('rm-title').value = existingRow ? (existingRow.title || '') : '';
  document.getElementById('rm-type').value = type;
  document.getElementById('rm-url').value = existingRow ? (existingRow.external_url || '') : '';
  document.getElementById('rm-instructions').value = existingRow ? (existingRow.instructions || '') : '';
  document.getElementById('rm-dl-limit').value = existingRow ? (existingRow.download_limit ?? 5) : 5;
  document.getElementById('rm-expiry').value = existingRow ? (existingRow.expiry_days ?? 30) : 30;
  document.getElementById('rm-sort').value = existingRow ? (existingRow.sort_order ?? 0) : 0;
  document.getElementById('rm-active').checked = existingRow ? Number(existingRow.is_active) === 1 : true;

  updateResourceModalFields(type);
  document.getElementById('resource-modal-overlay').classList.add('active');
  setTimeout(() => document.getElementById('rm-title').focus(), 50);
}

function closeResourceModal() {
  document.getElementById('resource-modal-overlay').classList.remove('active');
}

function updateResourceModalFields(type) {
  const mode = resourceDeliveryMode(type);
  document.getElementById('rm-url-group').style.display = mode === 'open_link' ? '' : 'none';
  document.getElementById('rm-instructions-group').style.display = mode === 'instructions' ? '' : 'none';
}

async function saveResourceModal() {
  const title = document.getElementById('rm-title').value.trim();
  if (!title) { showToast('Title is required.', 'error'); return; }

  const type = document.getElementById('rm-type').value;
  const mode = resourceDeliveryMode(type);
  let externalUrl = '';
  let instructions = '';

  if (mode === 'open_link') {
    externalUrl = document.getElementById('rm-url').value.trim();
    if (!/^https:\/\//i.test(externalUrl)) {
      showToast('External URL must start with https://', 'error');
      return;
    }
  } else if (mode === 'instructions') {
    instructions = document.getElementById('rm-instructions').value.trim();
    if (!instructions) { showToast('Instructions text is required.', 'error'); return; }
  }

  const ownerKey = document.getElementById('rm-owner-key').value;
  const ownerId = Number(document.getElementById('rm-owner-id').value);
  const editId = Number(document.getElementById('rm-id').value) || 0;

  const payload = {
    [ownerKey]: ownerId,
    title,
    resource_type: type,
    external_url: externalUrl,
    instructions,
    download_limit: Math.max(1, Math.min(100, Number.parseInt(document.getElementById('rm-dl-limit').value, 10) || 5)),
    expiry_days: Math.max(1, Math.min(3650, Number.parseInt(document.getElementById('rm-expiry').value, 10) || 30)),
    sort_order: Number.parseInt(document.getElementById('rm-sort').value, 10) || 0,
    is_active: document.getElementById('rm-active').checked ? 1 : 0,
  };
  if (editId > 0) payload.id = editId;

  const saveBtn = document.getElementById('resource-modal-save-btn');
  saveBtn.disabled = true;
  saveBtn.textContent = 'Saving…';
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    await fetchJson('../api/admin/resources/save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(payload),
    });
    closeResourceModal();
    if (ownerKey === 'product_id') await loadProductResources(ownerId);
    else if (ownerKey === 'bundle_id') await loadBundleResources(ownerId);
    else await loadFreebieResources(ownerId);
    showToast(editId > 0 ? 'Resource updated.' : 'Resource created.', 'success');
  } catch (err) {
    showToast(err.message || 'Could not save resource.', 'error');
  } finally {
    saveBtn.disabled = false;
    saveBtn.textContent = 'Save Resource';
  }
}

function openResourceViewModal(resourceId, ownerKey) {
  const rows = ownerKey === 'product_id'
    ? productResourcesState.rows
    : ownerKey === 'bundle_id'
      ? bundleResourcesState.rows
      : freebieResourcesState.rows;
  const r = rows.find(x => x.id === resourceId);
  if (!r) return;

  const mode = r.delivery_mode || '';
  const typeColor = RESOURCE_TYPE_COLORS[r.resource_type] || '#6366f1';
  const typeLabel = String(r.resource_type || 'file').replace('_', ' ');

  const statusHtml = r.has_file
    ? '<span style="color:#22c55e;">&#10003; File attached</span>'
    : mode === 'open_link'
      ? (r.external_url ? '<span style="color:#22c55e;">&#10003; Link set</span>' : '<span style="color:#f59e0b;">No link set</span>')
      : mode === 'instructions'
        ? '<span style="color:#22c55e;">&#10003; Instructions set</span>'
        : '<span style="color:#f59e0b;">No file uploaded yet</span>';

  const rows2 = [
    ['Title', escapeHtml(r.title)],
    ['Type', `<span style="background:${typeColor}20;color:${typeColor};border:1px solid ${typeColor}40;padding:2px 10px;border-radius:9999px;font-size:.75rem;font-weight:600;">${escapeHtml(typeLabel)}</span>`],
    ['Delivery', escapeHtml(r.delivery_mode || '—')],
    ['Status', statusHtml],
    mode === 'open_link' && r.external_url ? ['Link', `<span style="word-break:break-all;font-size:.82rem;">${escapeHtml(r.external_url)}</span>`] : null,
    mode === 'instructions' && r.instructions ? ['Instructions', `<span style="white-space:pre-wrap;font-size:.82rem;">${escapeHtml(r.instructions)}</span>`] : null,
    ['Download limit', `${r.download_limit} per customer`],
    ['Expiry', `${r.expiry_days} days`],
    ['Sort order', String(r.sort_order ?? 0)],
    ['Active', r.is_active ? '<span style="color:#22c55e;">Yes</span>' : '<span style="color:#94a3b8;">No</span>'],
  ].filter(Boolean);

  document.getElementById('resource-view-content').innerHTML = `
    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
      ${rows2.map(([k, v]) => `
        <tr>
          <td style="padding:8px 12px 8px 0;color:var(--text-3);font-weight:600;white-space:nowrap;vertical-align:top;width:38%;">${k}</td>
          <td style="padding:8px 0;color:var(--text);">${v}</td>
        </tr>`).join('')}
    </table>`;

  const linkBtn = document.getElementById('resource-view-link');
  if (mode === 'open_link' && r.external_url) {
    linkBtn.href = r.external_url;
    linkBtn.style.display = '';
  } else {
    linkBtn.style.display = 'none';
  }

  document.getElementById('resource-view-modal-overlay').classList.add('active');
}

function closeResourceViewModal() {
  document.getElementById('resource-view-modal-overlay').classList.remove('active');
}

function renderResourceRows(rows, ownerKey) {
  const actionMap = {
    product_id: { upload: 'upload-resource', edit: 'edit-resource', view: 'view-resource', del: 'delete-resource' },
    bundle_id:  { upload: 'upload-bundle-resource', edit: 'edit-bundle-resource', view: 'view-bundle-resource', del: 'delete-bundle-resource' },
    freebie_id: { upload: 'upload-freebie-resource', edit: 'edit-freebie-resource', view: 'view-freebie-resource', del: 'delete-freebie-resource' },
  };
  const actions = actionMap[ownerKey] || actionMap.product_id;
  const upload = actions.upload;
  const edit   = actions.edit;
  const view   = actions.view;
  const del    = actions.del;

  return rows.map(r => {
    const type = String(r.resource_type || 'file');
    const mode = String(r.delivery_mode || resourceDeliveryMode(type));
    const isActive = Number(r.is_active) === 1;
    const typeColor = RESOURCE_TYPE_COLORS[type] || '#6366f1';
    const typeLabel = type.replace('_', ' ');

    let accessHtml;
    if (mode === 'open_link') {
      accessHtml = r.external_url
        ? '<span style="color:#22c55e;">Link set</span>'
        : '<span style="color:#f59e0b;">No link — edit to add URL</span>';
    } else if (mode === 'instructions') {
      accessHtml = r.instructions
        ? '<span style="color:#22c55e;">Instructions set</span>'
        : '<span style="color:#f59e0b;">No instructions yet</span>';
    } else {
      accessHtml = r.has_file
        ? '<span style="color:#22c55e;">File attached</span>'
        : '<span style="color:#f59e0b;">No file — upload required</span>';
    }

    const uploadBtn = mode === 'download'
      ? `<button class="btn btn-ghost btn-xs" data-action="${upload}" data-id="${r.id}">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
           Upload
         </button>` : '';

    return `
    <div class="adm-resource-row" data-id="${r.id}">
      <div class="resource-row-main">
        <div class="resource-row-header">
          <span class="resource-row-name">${escapeHtml(r.title)}</span>
          <span class="resource-type-chip" style="background:${typeColor}20;color:${typeColor};border:1px solid ${typeColor}40;">${escapeHtml(typeLabel)}</span>
          ${!isActive ? '<span class="resource-type-chip" style="background:rgba(100,116,139,.12);color:#94a3b8;border:1px solid rgba(100,116,139,.2);">Inactive</span>' : ''}
        </div>
        <div class="resource-row-meta">${accessHtml} &middot; Limit&nbsp;${escapeHtml(String(r.download_limit ?? 5))} &middot; Expires&nbsp;${escapeHtml(String(r.expiry_days ?? 30))}d</div>
      </div>
      <div class="resource-row-actions">
        <button class="btn btn-ghost btn-xs" data-action="${view}" data-id="${r.id}">View</button>
        <button class="btn btn-ghost btn-xs" data-action="${edit}" data-id="${r.id}">Edit</button>
        ${uploadBtn}
        <button class="btn btn-danger-ghost btn-xs" data-action="${del}" data-id="${r.id}">Remove</button>
      </div>
    </div>`;
  }).join('');
}

function toggleProductResourcesSection() {
  const avail = document.getElementById('edit-product-available')?.value || 'digital';
  const section = document.getElementById('product-digital-resources-section');
  if (!section) return;
  section.style.display = (avail === 'digital' || avail === 'both') ? '' : 'none';
}

async function loadProductResources(productId) {
  productResourcesState.productId = Number(productId) || 0;
  productResourcesState.rows = [];
  const list = document.getElementById('product-resources-list');
  if (!list) return;
  if (productResourcesState.productId <= 0) {
    list.innerHTML = '<p class="form-hint">Save the product first, then add digital resources.</p>';
    return;
  }
  try {
    const rows = await fetchJson(`../api/admin/resources/list.php?product_id=${productResourcesState.productId}`);
    productResourcesState.rows = Array.isArray(rows) ? rows : [];
    renderProductResourcesList();
  } catch (err) {
    list.innerHTML = `<p class="form-hint">${escapeHtml(err.message)}</p>`;
  }
}

function renderProductResourcesList() {
  const list = document.getElementById('product-resources-list');
  if (!list) return;
  if (!productResourcesState.rows.length) {
    list.innerHTML = '<p class="form-hint">No resources yet. Click "+ Add resource" to get started.</p>';
    return;
  }
  list.innerHTML = renderResourceRows(productResourcesState.rows, 'product_id');
}

async function addProductResourceRow() {
  const productId = productResourcesState.productId || Number(document.getElementById('edit-product-id')?.value || 0);
  if (productId <= 0) {
    showToast('Save the product before adding resources.', 'error');
    return;
  }
  openResourceModal('product_id', productId, null);
}

function editProductResource(resourceId) {
  const r = productResourcesState.rows.find(x => x.id === resourceId);
  if (!r) return;
  openResourceModal('product_id', productResourcesState.productId, r);
}

function viewProductResource(resourceId) {
  openResourceViewModal(resourceId, 'product_id');
}

async function uploadProductResourceFile(resourceId) {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = '.pdf,.zip,.png,.jpg,.jpeg,.webp,.svg';
  input.onchange = async () => {
    const file = input.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('resource_id', String(resourceId));
    fd.append('file', file);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const res = await fetch('../api/admin/resources/upload.php', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrf },
      body: fd,
    });
    const data = await res.json();
    if (data.status !== 'success') throw new Error(data.message || 'Upload failed');
    await loadProductResources(productResourcesState.productId);
    showToast('File uploaded.', 'success');
  };
  input.click();
}

async function deleteProductResource(resourceId) {
  const r = productResourcesState.rows.find(x => x.id === resourceId);
  const confirmed = await showConfirmModal(
    'Remove Resource',
    `Remove <strong>${r ? escapeHtml(r.title) : 'this resource'}</strong>? Any uploaded file will also be permanently deleted.`,
    'Remove'
  );
  if (!confirmed) return;
  try {
    await fetchJson('../api/admin/resources/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: resourceId }),
    });
    await loadProductResources(productResourcesState.productId);
    showToast('Resource removed.', 'success');
  } catch (err) {
    showToast(err.message || 'Could not remove resource.', 'error');
  }
}

// ── Bundle Digital Resources ──────────────────────────────────────────────────
const bundleResourcesState = { rows: [], bundleId: 0 };

async function loadBundleResources(bundleId) {
  bundleResourcesState.bundleId = Number(bundleId) || 0;
  bundleResourcesState.rows = [];
  const list = document.getElementById('bundle-resources-list');
  if (!list) return;
  if (bundleResourcesState.bundleId <= 0) {
    list.innerHTML = '<p class="form-hint">Save the bundle first, then add digital resources.</p>';
    return;
  }
  const rows = await fetchJson(`../api/admin/resources/list.php?bundle_id=${bundleResourcesState.bundleId}`);
  bundleResourcesState.rows = Array.isArray(rows) ? rows : [];
  renderBundleResourcesList();
}

function renderBundleResourcesList() {
  const list = document.getElementById('bundle-resources-list');
  if (!list) return;
  if (!bundleResourcesState.rows.length) {
    list.innerHTML = '<p class="form-hint">No resources yet. Click "+ Add resource" to get started.</p>';
    return;
  }
  list.innerHTML = renderResourceRows(bundleResourcesState.rows, 'bundle_id');
}

async function addBundleResourceRow() {
  const bundleId = bundleResourcesState.bundleId || Number(document.querySelector('#bundle-editor-form [name="id"]')?.value || 0);
  if (!bundleId) { showToast('Save the bundle before adding resources.', 'error'); return; }
  openResourceModal('bundle_id', bundleId, null);
}

function editBundleResource(resourceId) {
  const r = bundleResourcesState.rows.find(x => x.id === resourceId);
  if (!r) return;
  openResourceModal('bundle_id', bundleResourcesState.bundleId, r);
}

function viewBundleResource(resourceId) {
  openResourceViewModal(resourceId, 'bundle_id');
}

async function uploadBundleResourceFile(resourceId) {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = '.pdf,.zip,.png,.jpg,.jpeg,.webp,.svg';
  input.onchange = async () => {
    const file = input.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('resource_id', String(resourceId));
    fd.append('file', file);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const res = await fetch('../api/admin/resources/upload.php', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrf },
      body: fd,
    });
    const data = await res.json();
    if (data.status !== 'success') { showToast(data.message || 'Upload failed.', 'error'); return; }
    await loadBundleResources(bundleResourcesState.bundleId);
    showToast('File uploaded.', 'success');
  };
  input.click();
}

async function deleteBundleResource(resourceId) {
  const r = bundleResourcesState.rows.find(x => x.id === resourceId);
  const confirmed = await showConfirmModal(
    'Remove Resource',
    `Remove <strong>${r ? escapeHtml(r.title) : 'this resource'}</strong>? Any uploaded file will also be permanently deleted.`,
    'Remove'
  );
  if (!confirmed) return;
  try {
    await fetchJson('../api/admin/resources/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: resourceId }),
    });
    await loadBundleResources(bundleResourcesState.bundleId);
    showToast('Resource removed.', 'success');
  } catch (err) {
    showToast(err.message || 'Could not remove resource.', 'error');
  }
}

// ── Freebie Digital Resources ─────────────────────────────────────────────────
const freebieResourcesState = { rows: [], freebieId: 0 };

async function loadFreebieResources(freebieId) {
  freebieResourcesState.freebieId = Number(freebieId) || 0;
  freebieResourcesState.rows = [];
  const list = document.getElementById('freebie-resources-list');
  if (!list) return;
  if (freebieResourcesState.freebieId <= 0) {
    list.innerHTML = '<p class="form-hint">Save the freebie first, then add digital resources.</p>';
    return;
  }
  try {
    const rows = await fetchJson(`../api/admin/resources/list.php?freebie_id=${freebieResourcesState.freebieId}`);
    freebieResourcesState.rows = Array.isArray(rows) ? rows : [];
    renderFreebieResourcesList();
  } catch (err) {
    list.innerHTML = `<p class="form-hint">${escapeHtml(err.message)}</p>`;
  }
}

function renderFreebieResourcesList() {
  const list = document.getElementById('freebie-resources-list');
  if (!list) return;
  if (!freebieResourcesState.rows.length) {
    list.innerHTML = '<p class="form-hint">No resources yet. Click "+ Add resource" to upload an encrypted file or add a link.</p>';
    return;
  }
  list.innerHTML = renderResourceRows(freebieResourcesState.rows, 'freebie_id');
}

async function addFreebieResourceRow() {
  const freebieId = freebieResourcesState.freebieId || Number(document.getElementById('freebie-id')?.value || 0);
  if (freebieId <= 0) {
    showToast('Save the freebie before adding resources.', 'error');
    return;
  }
  openResourceModal('freebie_id', freebieId, null);
}

function editFreebieResource(resourceId) {
  const r = freebieResourcesState.rows.find(x => x.id === resourceId);
  if (!r) return;
  openResourceModal('freebie_id', freebieResourcesState.freebieId, r);
}

function viewFreebieResource(resourceId) {
  openResourceViewModal(resourceId, 'freebie_id');
}

async function uploadFreebieResourceFile(resourceId) {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = '.pdf,.zip,.png,.jpg,.jpeg,.webp,.svg';
  input.onchange = async () => {
    const file = input.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('resource_id', String(resourceId));
    fd.append('file', file);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const res = await fetch('../api/admin/resources/upload.php', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrf },
      body: fd,
    });
    const data = await res.json();
    if (data.status !== 'success') { showToast(data.message || 'Upload failed.', 'error'); return; }
    await loadFreebieResources(freebieResourcesState.freebieId);
    showToast('File uploaded to private storage.', 'success');
  };
  input.click();
}

async function deleteFreebieResource(resourceId) {
  const r = freebieResourcesState.rows.find(x => x.id === resourceId);
  const confirmed = await showConfirmModal(
    'Remove Resource',
    `Remove <strong>${r ? escapeHtml(r.title) : 'this resource'}</strong>? Any uploaded file will also be permanently deleted.`,
    'Remove'
  );
  if (!confirmed) return;
  try {
    await fetchJson('../api/admin/resources/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: resourceId }),
    });
    await loadFreebieResources(freebieResourcesState.freebieId);
    showToast('Resource removed.', 'success');
  } catch (err) {
    showToast(err.message || 'Could not remove resource.', 'error');
  }
}

function initDashboard() {
  Promise.allSettled([getCategories()]).then(populateCategoryControls);
  loadOverview();
}

function bindDashboard() {
  const form = document.getElementById('edit-product-form');
  if (form) {
    form.onsubmit = saveProductForm;
  }
  const mainImageInput = document.getElementById('edit-product-image');
  const galleryInput = document.getElementById('edit-product-gallery');
  if (mainImageInput) mainImageInput.addEventListener('change', onProductMainImageSelected);
  if (galleryInput) galleryInput.addEventListener('change', onProductGalleryFilesSelected);
  const bundleCoverInput = document.getElementById('bundle-cover-image');
  const bundleGalleryInput = document.getElementById('bundle-gallery-input');
  if (bundleCoverInput) bundleCoverInput.addEventListener('change', onBundleMainImageSelected);
  if (bundleGalleryInput) bundleGalleryInput.addEventListener('change', onBundleGalleryFilesSelected);
  const categoryIconInput = document.querySelector('#category-form-panel [name="image"]');
  if (categoryIconInput) categoryIconInput.addEventListener('change', onCategoryIconSelected);
  const freebieCoverInput = document.getElementById('freebie-cover-image');
  if (freebieCoverInput) freebieCoverInput.addEventListener('change', onFreebieCoverSelected);
  document.getElementById('freebie-resource-add-btn')?.addEventListener('click', addFreebieResourceRow);
  document.getElementById('freebie-resources-list')?.addEventListener('click', event => {
    const btn = event.target.closest('[data-action]');
    if (!btn) return;
    const id = Number(btn.dataset.id || 0);
    if (btn.dataset.action === 'upload-freebie-resource') uploadFreebieResourceFile(id);
    if (btn.dataset.action === 'edit-freebie-resource')   editFreebieResource(id);
    if (btn.dataset.action === 'view-freebie-resource')   viewFreebieResource(id);
    if (btn.dataset.action === 'delete-freebie-resource') deleteFreebieResource(id);
  });
  document.getElementById('edit-product-available')?.addEventListener('change', toggleProductResourcesSection);
  document.getElementById('product-resource-add-btn')?.addEventListener('click', addProductResourceRow);
  document.getElementById('product-resources-list')?.addEventListener('click', event => {
    const btn = event.target.closest('[data-action]');
    if (!btn) return;
    const id = Number(btn.dataset.id || 0);
    if (btn.dataset.action === 'upload-resource')  uploadProductResourceFile(id);
    if (btn.dataset.action === 'edit-resource')    editProductResource(id);
    if (btn.dataset.action === 'view-resource')    viewProductResource(id);
    if (btn.dataset.action === 'delete-resource')  deleteProductResource(id);
  });
  document.getElementById('product-add-custom-field')?.addEventListener('click', () => {
    renderProductCustomFields([...collectProductCustomFields(), { label: '', value: '' }]);
  });
  document.getElementById('product-custom-fields')?.addEventListener('input', syncProductCustomFieldsHidden);
  document.getElementById('product-custom-fields')?.addEventListener('click', (event) => {
    const btn = event.target.closest('.product-cf-remove');
    if (!btn) return;
    btn.closest('.product-custom-row')?.remove();
    if (!document.querySelector('#product-custom-fields .product-custom-row')) {
      renderProductCustomFields([]);
    } else {
      syncProductCustomFieldsHidden();
    }
  });
  document.getElementById('bundle-resource-add-btn')?.addEventListener('click', addBundleResourceRow);
  document.getElementById('bundle-resources-list')?.addEventListener('click', event => {
    const btn = event.target.closest('[data-action]');
    if (!btn) return;
    const id = Number(btn.dataset.id || 0);
    if (btn.dataset.action === 'upload-bundle-resource')  uploadBundleResourceFile(id);
    if (btn.dataset.action === 'edit-bundle-resource')    editBundleResource(id);
    if (btn.dataset.action === 'view-bundle-resource')    viewBundleResource(id);
    if (btn.dataset.action === 'delete-bundle-resource')  deleteBundleResource(id);
  });
  document.getElementById('rm-type')?.addEventListener('change', e => updateResourceModalFields(e.target.value));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      _resolveConfirm(false);
      closeStatusModal();
      closeOrderDetailsModal();
      closeEditProductModal();
      closeResourceModal();
      closeResourceViewModal();
    }
  });
  document.getElementById('confirm-modal-overlay')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) _resolveConfirm(false);
  });
  initDashboard();
}

const exported = {
  handleAdminLogout,
  loadOverview,
  loadUsers,
  loadProducts,
  loadOrders,
  loadAnalytics,
  loadAdminCategories,
  loadAdminBundles,
  adminSaveCategory,
  adminToggleCategory,
  adminDeleteCategory,
  openCategoryForm,
  closeCategoryForm,
  adminEditCategory,
  adminSaveBundle,
  adminEditBundle,
  adminCancelBundleEdit,
  openBundleForm,
  closeBundleForm,
  adminToggleBundle,
  adminDeleteBundle,
  loadReviews,
  filterReviews,
  toggleReviewApproval,
  deleteReview,
  loadMessages,
  markMessageRead,
  deleteMessage,
  loadAdminFreebies,
  adminSaveFreebies,
  adminEditFreebie,
  adminToggleFreebie,
  adminDeleteFreebie,
  openFreebiesForm,
  closeFreebiesForm,
  filterAdminFreebies,
  filterUsers,
  filterProducts,
  filterOrders,
  openCreateProductModal,
  editProduct,
  duplicateProduct,
  deleteProduct,
  toggleProductStatus,
  handleUpdateProduct: saveProductForm,
  viewOrder,
  updateOrderStatusFromBtn: btn => openStatusEditor(Number(btn?.dataset?.orderId || 0)),
  confirmStatusUpdate,
  closeStatusModal,
  closeOrderDetailsModal,
  closeEditProductModal,
  removeProductMainImage,
  removeProductGalleryImage,
  removeBundleMainImage,
  removeBundleGalleryImage,
  removeCategoryIcon,
  removeFreebieImage,
  closeResourceModal,
  closeResourceViewModal,
  saveResourceModal,
};

Object.assign(window, exported);
document.addEventListener('DOMContentLoaded', () => {
  initProductIsFreeToggle();
  window.setTimeout(() => {
    Object.assign(window, exported);
    bindDashboard();
  }, 0);
});
