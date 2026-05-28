// Production admin dashboard controller.

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

  const response = await fetch(url, { ...options, headers });
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
  let shipping = order.shipping_address;
  if (typeof shipping === 'string') {
    try { shipping = JSON.parse(shipping); } catch { shipping = {}; }
  }
  return shipping?.email || 'N/A';
}

async function getProducts() {
  state.products = await fetchJson('../api/admin/product/list.php');
  return state.products;
}

async function getUsers() {
  state.users = await fetchJson('../api/admin/user/list.php');
  return state.users;
}

async function getOrders() {
  const payload = await fetchJson('../api/admin/order/list.php');
  if (Array.isArray(payload)) {
    state.orders = payload;
  } else if (payload && Array.isArray(payload.orders)) {
    state.orders = payload.orders;
  } else {
    state.orders = [];
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
  const names = state.categories.map(c => c.name).filter(Boolean);
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
    <tr>
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
  table.innerHTML = rows.slice(0, 6).map(row => `
    <tr>
      <td><strong>${escapeHtml(row.name || 'Item')}</strong><div style="font-size:.75rem;opacity:.65;">${escapeHtml(row.category || '')}</div></td>
      <td>${Number(row.units_sold || 0).toLocaleString('en-IN')}</td>
      <td>${money(row.revenue)}</td>
    </tr>
  `).join('');
}

async function loadAnalytics() {
  try {
    const [orders, users, stats] = await Promise.all([getOrders(), getUsers(), fetchJson('../api/admin/stats/overview.php')]);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const month = new Date(today.getFullYear(), today.getMonth(), 1);
    const todayOrders = orders.filter(o => {
      const d = new Date(o.created_at || 0);
      d.setHours(0, 0, 0, 0);
      return d.getTime() === today.getTime();
    });
    const monthOrders = orders.filter(o => new Date(o.created_at || 0) >= month);
    const total = rows => rows.reduce((sum, row) => sum + Number(row.total || 0), 0);
    setText('analytics-today-revenue', money(total(todayOrders)));
    setText('analytics-month-revenue', money(total(monthOrders)));
    setText('analytics-avg-order', money(orders.length ? total(orders) / orders.length : 0));
    setText('analytics-conversion', `${users.length ? ((orders.length / users.length) * 100).toFixed(1) : '0.0'}%`);
    const topRows = stats.top_products || [];
    const analyticsTable = document.getElementById('analytics-top-products-table');
    if (analyticsTable) {
      if (!topRows.length) {
        analyticsTable.innerHTML = '<tr><td colspan="3" class="tbl-empty">No data yet</td></tr>';
      } else {
        analyticsTable.innerHTML = topRows.map(row => `
          <tr>
            <td><strong>${escapeHtml(row.name || 'Item')}</strong><div style="font-size:.75rem;opacity:.65;">${escapeHtml(row.category || '')}</div></td>
            <td>${Number(row.units_sold || 0).toLocaleString('en-IN')}</td>
            <td>${money(row.revenue)}</td>
          </tr>
        `).join('');
      }
    }
  } catch (err) {
    console.error(err);
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
        <td>${money(p.price)}</td>
        <td>${Number(p.stock || 0).toLocaleString()}</td>
        <td>${escapeHtml(p.rating || '0.0')}</td>
        <td>${p.is_active == 1 ? getStatusBadge('active') : getStatusBadge('archived')}</td>
        <td>
          <div class="tbl-actions">
            <button class="btn btn-xs btn-ghost" onclick="editProduct(${Number(p.id)})">Edit</button>
            <button class="btn btn-xs btn-ghost" onclick="duplicateProduct(${Number(p.id)})">Duplicate</button>
            <button class="btn btn-xs ${p.is_active == 1 ? 'btn-danger-ghost' : 'btn-success-ghost'}" onclick="toggleProductStatus(${Number(p.id)}, ${p.is_active == 1 ? 0 : 1})">${p.is_active == 1 ? 'Archive' : 'Restore'}</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function filterProducts() {
  renderProducts(state.products);
}

function clearProductForm() {
  const form = document.getElementById('edit-product-form');
  if (!form) return;
  form.reset();
  form.dataset.mode = 'create';
  document.getElementById('edit-product-id').value = '';
  document.getElementById('edit-product-existing-image').value = '';
  document.getElementById('edit-product-rating').value = '4.5';
  document.getElementById('edit-product-stock').value = '0';
  document.getElementById('edit-product-active').value = '1';
  document.getElementById('edit-product-featured').value = '0';
  document.getElementById('edit-product-available').value = 'digital';
  document.getElementById('current-image-preview').innerHTML = '';
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
  set('edit-product-tags', p.tags);
  set('edit-product-price', p.price);
  set('edit-product-old-price', p.old_price);
  set('edit-product-commercial-price', p.commercial_price);
  set('edit-product-stock', p.stock);
  set('edit-product-active', String(p.is_active ?? 1));
  set('edit-product-featured', String(p.is_featured ?? 0));
  document.getElementById('current-image-preview').innerHTML = p.image
    ? `<img src="../${escapeHtml(p.image)}" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-right:10px;">${escapeHtml(p.image)}`
    : '';
  openEditProductModal();
}

async function saveProductForm(event) {
  event.preventDefault();
  const form = event.target;
  const endpoint = form.querySelector('[name="id"]').value ? '../api/admin/product/update.php' : '../api/admin/product/create.php';
  const submit = form.querySelector('button[type="submit"]');
  const label = submit.textContent;
  submit.disabled = true;
  submit.textContent = 'Saving...';
  try {
    await fetchJson(endpoint, { method: 'POST', body: new FormData(form) });
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
  if (!confirm('Archive this product?')) return;
  await fetchJson('../api/admin/product/delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: productId }),
  });
  await loadProducts();
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
  await fetchJson('../api/admin/categories/save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ...row, is_active: isActive }),
  });
  await loadAdminCategories();
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
  const fd = new FormData(form);
  const isFeaturedEl = form.querySelector('input[name="is_featured"]');
  const isActiveEl = form.querySelector('input[name="is_active"]');
  fd.set('is_featured', isFeaturedEl && isFeaturedEl.checked ? '1' : '0');
  fd.set('is_active', isActiveEl && isActiveEl.checked ? '1' : '0');
  // whats_included is a textarea — server derives included_items JSON from it
  try {
    await fetchJson('../api/admin/bundles/save.php', { method: 'POST', body: fd });
    form.reset();
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

  // additional_images: stored as JSON array → show as newline-separated paths
  let additionalImages = '';
  if (row.additional_images) {
    try {
      const imgs = JSON.parse(row.additional_images);
      if (Array.isArray(imgs)) additionalImages = imgs.join('\n');
    } catch (_) {
      additionalImages = row.additional_images;
    }
  }
  form.elements['additional_images'].value = additionalImages;

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

function openCategoryForm() {
  const panel = document.getElementById('category-form-panel');
  if (!panel) return;
  const form = panel.querySelector('form');
  if (form) form.reset();
  const formTitle = document.getElementById('category-form-title');
  if (formTitle) formTitle.textContent = 'Add New Category';
  panel.style.display = 'block';
  panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeCategoryForm() {
  const panel = document.getElementById('category-form-panel');
  if (!panel) return;
  const form = panel.querySelector('form');
  if (form) form.reset();
  panel.style.display = 'none';
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
  if (!confirm('Delete this bundle?')) return;
  try {
    await fetchJson('../api/admin/bundles/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    });
    await loadAdminBundles();
    showToast('Bundle deleted.', 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function loadReviews() {
  const table = document.getElementById('reviews-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="6" class="tbl-empty">Loading reviews...</td></tr>';
  try {
    const rows = await fetchJson('../api/admin/reviews/list.php');
    if (!rows.length) {
      table.innerHTML = '<tr><td colspan="6" class="tbl-empty">No reviews found</td></tr>';
      return;
    }
    table.innerHTML = rows.map(r => `
      <tr>
        <td><div class="cell-name">${escapeHtml(r.product_name || 'N/A')}</div></td>
        <td><div class="cell-name">${escapeHtml(r.user_name || r.reviewer_name || 'Guest')}</div></td>
        <td><span style="color:#f59e0b;letter-spacing:1px;">${'★'.repeat(Math.min(5, Math.max(0, Number(r.rating || 0))))}${'☆'.repeat(Math.max(0, 5 - Math.min(5, Number(r.rating || 0))))}</span></td>
        <td style="max-width:260px;white-space:normal;font-size:.81rem;color:var(--text-2);">${escapeHtml(r.comment || r.body || '—')}</td>
        <td>${r.is_approved == 1 ? getStatusBadge('active') : getStatusBadge('pending')}</td>
        <td>
          <div class="tbl-actions">
            <button class="btn btn-xs ${r.is_approved == 1 ? 'btn-ghost' : 'btn-success-ghost'}" onclick="toggleReviewApproval(${Number(r.id)}, ${r.is_approved == 1 ? 0 : 1})">${r.is_approved == 1 ? 'Unapprove' : 'Approve'}</button>
            <button class="btn btn-xs btn-danger-ghost" onclick="deleteReview(${Number(r.id)})">Delete</button>
          </div>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    table.innerHTML = `<tr><td colspan="6" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

async function toggleReviewApproval(id, approve) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  await fetchJson('../api/admin/reviews/moderate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, approve, csrf_token: csrfToken }),
  });
  await loadReviews();
  showToast(approve ? 'Review approved.' : 'Review unapproved.', 'success');
}

async function deleteReview(id) {
  if (!confirm('Delete this review permanently?')) return;
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  await fetchJson('../api/admin/reviews/moderate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, delete: 1, csrf_token: csrfToken }),
  });
  await loadReviews();
  showToast('Review deleted.', 'success');
}

async function loadMessages() {
  const table = document.getElementById('messages-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="6" class="tbl-empty">Loading messages...</td></tr>';
  try {
    const rows = await fetchJson('../api/admin/messages/list.php');
    if (!rows.length) {
      table.innerHTML = '<tr><td colspan="6" class="tbl-empty">No messages found</td></tr>';
      return;
    }
    table.innerHTML = rows.map(m => `
      <tr>
        <td>
          <div class="cell-name">
            ${m.is_read != 1 ? '<span class="cell-read-dot" title="Unread"></span>' : ''}
            ${escapeHtml(m.name || 'N/A')}
          </div>
        </td>
        <td style="font-size:.8rem;color:var(--text-2);">${escapeHtml(m.email || 'N/A')}</td>
        <td><div class="cell-name" style="font-weight:500;">${escapeHtml(m.subject || 'General')}</div></td>
        <td style="max-width:280px;white-space:normal;font-size:.8rem;color:var(--text-2);">${escapeHtml(String(m.message || '').substring(0, 130))}${String(m.message || '').length > 130 ? '…' : ''}</td>
        <td style="white-space:nowrap;font-size:.79rem;color:var(--text-3);">${new Date(m.created_at || Date.now()).toLocaleDateString()}</td>
        <td>
          <div class="tbl-actions">
            ${m.is_read != 1 ? `<button class="btn btn-xs btn-success-ghost" onclick="markMessageRead(${Number(m.id)})">Mark Read</button>` : ''}
            <button class="btn btn-xs btn-danger-ghost" onclick="deleteMessage(${Number(m.id)})">Delete</button>
          </div>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    table.innerHTML = `<tr><td colspan="6" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

async function markMessageRead(id) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  await fetchJson('../api/admin/messages/update.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, action: 'read', csrf_token: csrfToken }),
  });
  await loadMessages();
}

async function deleteMessage(id) {
  if (!confirm('Delete this message permanently?')) return;
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  await fetchJson('../api/admin/messages/update.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, action: 'delete', csrf_token: csrfToken }),
  });
  await loadMessages();
  showToast('Message deleted.', 'success');
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
  const filter = normalizeStatus(document.getElementById('order-status-filter')?.value || '');
  const rows = state.orders.filter(order => {
    const text = `${order.order_number || ''} ${getOrderCustomerName(order)} ${getOrderEmail(order)} ${order.status || ''}`.toLowerCase();
    return (!q || text.includes(q)) && (!filter || normalizeStatus(order.status) === filter);
  });
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
  `).join('');
}

function filterOrders() {
  renderOrders();
}

function openStatusEditor(orderId) {
  const order = state.orders.find(o => Number(o.id) === Number(orderId));
  if (!order) return;
  document.getElementById('modal-order-number').textContent = order.order_number || String(order.id);
  document.getElementById('modal-order-customer').textContent = getOrderCustomerName(order);
  document.getElementById('modal-current-status').innerHTML = getStatusBadge(order.status);
  document.getElementById('status-select').value = normalizeStatus(order.status);
  openStatusModal();
}

async function confirmStatusUpdate() {
  const orderNumber = document.getElementById('modal-order-number').textContent;
  const status = document.getElementById('status-select').value;
  await fetchJson('../api/admin/order/update_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ order_number: orderNumber, status }),
  });
  closeStatusModal();
  await Promise.all([loadOrders(), loadOverview()]);
  showToast('Order status updated.', 'success');
}

async function viewOrder(orderId) {
  const content = document.getElementById('order-details-content');
  content.innerHTML = '<div class="tbl-empty">Loading order...</div>';
  openOrderDetailsModal();
  try {
    const data = await fetchJson(`../api/admin/order/get_details.php?id=${encodeURIComponent(orderId)}`);
    const items = data.items || [];
    content.innerHTML = `
      <div class="order-info-grid">
        <div class="order-info-block">
          <h3>Customer</h3>
          <p>${escapeHtml(getOrderCustomerName(data))}</p>
          <p>${escapeHtml(getOrderEmail(data))}</p>
        </div>
        <div class="order-info-block">
          <h3>Order</h3>
          <p>${escapeHtml(data.order_number || String(data.id))}</p>
          <p>${getStatusBadge(data.status)}</p>
          <p style="font-weight:700;font-size:1rem;margin-top:4px;">${money(data.total)}</p>
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
  await fetchJson('../api/admin/order/delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: orderId }),
  });
  await Promise.all([loadOrders(), loadOverview()]);
}

async function loadUsers() {
  const table = document.getElementById('users-table');
  if (!table) return;
  table.innerHTML = '<tr><td colspan="7" class="tbl-empty">Loading users...</td></tr>';
  try {
    await getUsers();
    renderUsers();
  } catch (err) {
    table.innerHTML = `<tr><td colspan="7" class="tbl-empty">${escapeHtml(err.message)}</td></tr>`;
  }
}

function renderUsers() {
  const table = document.getElementById('users-table');
  const q = document.getElementById('user-search')?.value.toLowerCase().trim() || '';
  const rows = state.users.filter(u => `${u.name || ''} ${u.first_name || ''} ${u.last_name || ''} ${u.email || ''} ${u.phone || ''}`.toLowerCase().includes(q));
  if (!rows.length) {
    table.innerHTML = '<tr><td colspan="7" class="tbl-empty">No users found</td></tr>';
    return;
  }
  table.innerHTML = rows.map(user => {
    const name = user.name || `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.username || 'N/A';
    const initial = name.charAt(0).toUpperCase();
    const blocked = user.is_blocked == 1;
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
        <td><button class="btn btn-xs ${blocked ? 'btn-success-ghost' : 'btn-danger-ghost'}" onclick="toggleUserBlock(${Number(user.id)}, ${blocked ? 0 : 1})">${blocked ? 'Unblock' : 'Block'}</button></td>
      </tr>
    `;
  }).join('');
}

function filterUsers() {
  renderUsers();
}

async function toggleUserBlock(userId, block) {
  await fetchJson('../api/admin/user/block.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: userId, action: block ? 'block' : 'unblock' }),
  });
  await loadUsers();
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
  fetch('../api/auth/logout.php').finally(() => { window.location.href = 'admin-login.php'; });
}

/* ─────────────────────────────────────────────
   FREEBIES ADMIN
───────────────────────────────────────────── */

function openFreebiesForm() {
  document.getElementById('freebie-form-panel').style.display = '';
  document.getElementById('freebie-form-panel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeFreebiesForm() {
  document.getElementById('freebie-form-panel').style.display = 'none';
  document.getElementById('freebie-form').reset();
  document.getElementById('freebie-id').value = '0';
  document.getElementById('freebie-existing-image').value = '';
  document.getElementById('freebie-form-title').textContent = 'Add New Freebie';
  document.getElementById('freebie-submit-btn').textContent = 'Add Freebie';
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
  document.getElementById('freebie-form-title').textContent = 'Edit Freebie';
  document.getElementById('freebie-submit-btn').innerHTML = `
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:15px;height:15px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
    Save Changes`;
}

async function adminSaveFreebies(event) {
  event.preventDefault();
  const form = event.target;
  const fd = new FormData(form);
  if (document.getElementById('freebie-is-active').checked) fd.set('is_active', '1');
  else fd.set('is_active', '0');
  if (document.getElementById('freebie-is-featured').checked) fd.set('is_featured', '1');
  else fd.set('is_featured', '0');
  try {
    await fetchJson('../api/admin/freebies/save.php', { method: 'POST', body: fd });
    closeFreebiesForm();
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
      body: JSON.stringify({ ...row, is_active: isActive }),
    });
    await loadAdminFreebies();
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

function initDashboard() {
  Promise.allSettled([getCategories()]).then(populateCategoryControls);
  loadOverview();
}

function bindDashboard() {
  const form = document.getElementById('edit-product-form');
  if (form) {
    form.onsubmit = saveProductForm;
  }
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeStatusModal();
      closeOrderDetailsModal();
      closeEditProductModal();
    }
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
  adminSaveBundle,
  adminEditBundle,
  adminCancelBundleEdit,
  openBundleForm,
  closeBundleForm,
  adminToggleBundle,
  adminDeleteBundle,
  loadReviews,
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
};

Object.assign(window, exported);
document.addEventListener('DOMContentLoaded', () => {
  window.setTimeout(() => {
    Object.assign(window, exported);
    bindDashboard();
  }, 0);
});
