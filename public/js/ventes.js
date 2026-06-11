const BASE_URL = window.location.origin;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

const SALES_KEY = 'sales_store_v7';
const SALES_QUEUE_KEY = 'sales_queue_v7';
const SALES_INVOICE_KEY = 'sales_invoice_v7';
const SALES_STATS_KEY = 'sales_stats_v7';
const CLIENTS_KEY = 'clients_store_v7';
const OUTGOINGS_KEY = 'outgoings_store_v7';
const OUTGOINGS_QUEUE_KEY = 'outgoings_queue_v7';
const CURRENT_INVOICE_KEY = 'current_invoice_v7';

const PAGE = window.SALES_PAGE_INITIAL || {};
const CAN_ADD = !!PAGE.canAdd;
const CAN_EDIT = !!PAGE.canEdit;
const CAN_DELETE = !!PAGE.canDelete;

const CURRENT_USER_NAME =
  PAGE.currentUserName ||
  document.querySelector('.user-name')?.textContent?.trim() ||
  '—';

let salesStore = Array.isArray(PAGE.sales) ? PAGE.sales : [];
let productsStore = Array.isArray(PAGE.products) ? PAGE.products : [];
let clientsStore = Array.isArray(PAGE.clients) ? PAGE.clients : [];
let outgoingsStore = Array.isArray(PAGE.outgoings) ? PAGE.outgoings : [];

let currentSaleLines = [];
let searchDebounce = null;
let clientSearchDebounce = null;
let outgoingSearchDebounce = null;
let currentSalesPage = 1;
const SALES_PER_PAGE = 5;

let isGeneratingPdf = false;

function $(id) {
  return document.getElementById(id);
}

function normalizeText(v) {
  return (v || '')
    .toString()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

function fmt(n) {
  return Number(n || 0).toLocaleString('fr-FR');
}

function setBtnLoading(btn, loading) {
  if (!btn) return;
  let spinner = btn.querySelector('.btn-spinner');
  if (loading) {
    btn.classList.add('btn-disabled-overlay');
    btn.disabled = true;
    if (!spinner) {
      spinner = document.createElement('span');
      spinner.className = 'btn-spinner';
      btn.prepend(spinner);
    }
  } else {
    btn.classList.remove('btn-disabled-overlay');
    btn.disabled = false;
    spinner?.remove();
  }
}

function showToast(message, type = 'success', duration = 3500) {
  const container = $('toastContainer');
  if (!container) return;

  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const toast = document.createElement('div');
  toast.className = `toast-item toast-${type}`;
  toast.innerHTML = `
    <span>${icons[type] || icons.info}</span>
    <span style="flex:1">${message}</span>
    <button class="toast-close" type="button">×</button>
  `;
  toast.querySelector('.toast-close').onclick = () => toast.remove();
  container.appendChild(toast);

  setTimeout(() => {
    if (toast.isConnected) toast.remove();
  }, duration);
}

async function ajaxRequest(url, method = 'GET', body = null) {
  const headers = {
    'X-CSRF-TOKEN': CSRF_TOKEN,
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  };
  if (body !== null) headers['Content-Type'] = 'application/json';

  const options = { method, headers };
  if (body !== null) options.body = JSON.stringify(body);

  const res = await fetch(url, options);
  let json = {};
  try {
    json = await res.json();
  } catch (_) {}
  if (!res.ok) throw new Error(json.message || 'Erreur serveur');
  return json;
}

function saveStore() {
  try {
    localStorage.setItem(SALES_KEY, JSON.stringify({
      savedAt: Date.now(),
      sales: salesStore,
      products: productsStore,
      clients: clientsStore,
      outgoings: outgoingsStore,
    }));
  } catch (_) {}
}

function saveStatsCache(stats) {
  try {
    localStorage.setItem(SALES_STATS_KEY, JSON.stringify({
      savedAt: Date.now(),
      stats,
    }));
  } catch (_) {}
}

function loadStore() {
  try {
    const raw = localStorage.getItem(SALES_KEY);
    if (!raw) return false;
    const data = JSON.parse(raw);
    if (!data) return false;
    if (Array.isArray(data.sales)) salesStore = data.sales;
    if (Array.isArray(data.products)) productsStore = data.products;
    if (Array.isArray(data.clients)) clientsStore = data.clients;
    if (Array.isArray(data.outgoings)) outgoingsStore = data.outgoings;
    return true;
  } catch (_) {
    return false;
  }
}

function loadClientsCache() {
  try {
    const raw = localStorage.getItem(CLIENTS_KEY);
    if (!raw) return [];
    const data = JSON.parse(raw);
    return Array.isArray(data.clients) ? data.clients : [];
  } catch (_) {
    return [];
  }
}

function saveClientsCache() {
  try {
    localStorage.setItem(CLIENTS_KEY, JSON.stringify({
      savedAt: Date.now(),
      clients: clientsStore,
    }));
  } catch (_) {}
}

function loadOutgoingsCache() {
  try {
    const raw = localStorage.getItem(OUTGOINGS_KEY);
    if (!raw) return [];
    const data = JSON.parse(raw);
    return Array.isArray(data.outgoings) ? data.outgoings : [];
  } catch (_) {
    return [];
  }
}

function saveOutgoingsCache() {
  try {
    localStorage.setItem(OUTGOINGS_KEY, JSON.stringify({
      savedAt: Date.now(),
      outgoings: outgoingsStore,
    }));
  } catch (_) {}
}

function saveCurrentInvoice(sale) {
  try {
    localStorage.setItem(CURRENT_INVOICE_KEY, JSON.stringify(sale));
  } catch (_) {}
}

function loadCurrentInvoice() {
  try {
    const raw = localStorage.getItem(CURRENT_INVOICE_KEY);
    if (!raw) return null;
    return JSON.parse(raw);
  } catch (_) {
    return null;
  }
}

function getCurrentInvoiceData() {
  try {
    return loadCurrentInvoice() || JSON.parse(localStorage.getItem(SALES_INVOICE_KEY) || 'null');
  } catch (_) {
    return loadCurrentInvoice();
  }
}

function queueGet(key) {
  try {
    return JSON.parse(localStorage.getItem(key) || '[]');
  } catch (_) {
    return [];
  }
}

function queueSet(key, queue) {
  localStorage.setItem(key, JSON.stringify(queue));
  updateSyncBanner();
}

function queuePush(key, op) {
  const queue = queueGet(key);
  queue.push({ ...op, _qid: Date.now() + '_' + Math.random().toString(36).slice(2) });
  queueSet(key, queue);
}

function updateSyncBanner() {
  const banner = $('syncBanner');
  const text = $('syncBannerText');
  if (!banner || !text) return;

  const saleQueue = queueGet(SALES_QUEUE_KEY);
  const outQueue = queueGet(OUTGOINGS_QUEUE_KEY);
  const count = saleQueue.length + outQueue.length;

  if (count > 0 && navigator.onLine) {
    banner.classList.add('show');
    text.textContent = `Connexion active — ${count} opération(s) en attente. Cliquez ici pour synchroniser.`;
  } else {
    banner.classList.remove('show');
  }
}

function setLoading(flag) {
  $('tableLoadingOverlay')?.classList.toggle('show', !!flag);
}

function setTableLoading(flag) {
  setLoading(flag);
}

function normalizeProduct(p) {
  if (!p) return p;
  return {
    ...p,
    available_stock: Number(p.available_stock || 0),
    current_sale_price: Number(p.current_sale_price || 0),
    current_purchase_price: Number(p.current_purchase_price || 0),
    units: Array.isArray(p.units) ? p.units : [],
  };
}

function getProductById(productId) {
  return productsStore.find(x => String(x.id) === String(productId)) || null;
}

function getClientByQuery(q) {
  const query = normalizeText(q);
  return clientsStore
    .filter(c => {
      const hay = normalizeText(`${c.name || ''} ${c.client_number || ''} ${c.phone || ''} ${c.address || ''}`);
      return hay.includes(query);
    })
    .slice(0, 20);
}

function paymentLabel(v) {
  return v === 'cash' ? 'Cash' : v === 'mobile_money' ? 'Mobile Money' : 'Partenaire';
}

function saleStatusBadge(v) {
  return v === 'paid'
    ? '<span class="badge bg-success">Payée</span>'
    : '<span class="badge bg-warning text-dark">Non payée</span>';
}

function buildSalesStats(sales) {
  const total_sales = sales.length;
  const total_amount = sales.reduce((s, x) => s + Number(x.total_amount || 0), 0);

  const items = [];
  sales.forEach(s => (s.items || []).forEach(i => items.push(i)));

  const byProduct = {};
  items.forEach(i => {
    const key = i.product_name || '—';
    byProduct[key] = (byProduct[key] || 0) + Number(i.quantity || 0);
  });

  const sorted = Object.entries(byProduct).sort((a, b) => b[1] - a[1]);
  const top_product = sorted[0]?.[0] || '—';
  const low_product = sorted[sorted.length - 1]?.[0] || '—';

  const benefit_day = sales.reduce((sum, sale) => {
    const saleBenefit = (sale.items || []).reduce((s, item) => {
      const purchase = Number(item.purchase_price || 0);
      const salePrice = Number(item.price || 0);
      const qty = Number(item.quantity || 0);
      return s + Math.max(0, (salePrice - purchase) * qty);
    }, 0);
    return sum + saleBenefit + Number(sale.extra_expense_amount || 0);
  }, 0);

  return { total_sales, total_amount, top_product, low_product, benefit_day };
}

function renderStats(stats) {
  $('statTotalSales') && ($('statTotalSales').textContent = fmt(stats.total_sales || 0));
  $('statTotalAmount') && ($('statTotalAmount').textContent = `${fmt(stats.total_amount || 0)} CDF`);
  $('statBenefitDay') && ($('statBenefitDay').textContent = `${fmt(stats.benefit_day || 0)} CDF`);
  $('statTopProduct') && ($('statTopProduct').textContent = stats.top_product || '—');
}

function persistComputedStats() {
  const stats = buildSalesStats(salesStore);
  saveStatsCache(stats);
  renderStats(stats);
  return stats;
}

function renderSalesPagination(totalItems) {
  const container = $('salesPagination');
  if (!container) return;

  const totalPages = Math.max(1, Math.ceil(totalItems / SALES_PER_PAGE));

  if (totalPages <= 1) {
    container.innerHTML = '';
    return;
  }

  currentSalesPage = Math.max(1, Math.min(currentSalesPage, totalPages));

  const prevDisabled = currentSalesPage <= 1 ? 'disabled' : '';
  const nextDisabled = currentSalesPage >= totalPages ? 'disabled' : '';

  let html = '';
  html += `<button type="button" class="btn btn-sm btn-outline-secondary" data-page="${currentSalesPage - 1}" ${prevDisabled}>Précédent</button>`;

  for (let p = 1; p <= totalPages; p++) {
    html += `<button type="button" class="btn btn-sm ${p === currentSalesPage ? 'btn-primary' : 'btn-outline-primary'}" data-page="${p}">${p}</button>`;
  }

  html += `<button type="button" class="btn btn-sm btn-outline-secondary" data-page="${currentSalesPage + 1}" ${nextDisabled}>Suivant</button>`;
  container.innerHTML = html;
}

function renderSalesTable(sales) {
  const tbody = $('salesTableBody');
  if (!tbody) return;

  const totalPages = Math.max(1, Math.ceil((sales || []).length / SALES_PER_PAGE));
  currentSalesPage = Math.max(1, Math.min(currentSalesPage, totalPages));

  const start = (currentSalesPage - 1) * SALES_PER_PAGE;
  const pageSales = (sales || []).slice(start, start + SALES_PER_PAGE);

  if (!pageSales.length) {
    tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4">Aucune vente pour aujourd'hui.</td></tr>`;
    renderSalesPagination(0);
    return;
  }

  tbody.innerHTML = pageSales.map(sale => `
    <tr data-id="${sale.id}" data-user-id="${sale.user_id}" data-payment="${sale.payment_type}">
      <td>${sale.id}</td>
      <td>${sale.created_at || ''}</td>
      <td>${sale.user_name || '—'}</td>
      <td>${sale.client_number || '—'}</td>
      <td>
        <div class="d-flex flex-column gap-1">
          ${(sale.items || []).map(item => `<span class="badge bg-light text-dark border">${item.product_name} x${item.quantity}</span>`).join('')}
        </div>
      </td>
      <td><strong>${fmt(sale.extra_expense_amount || 0)} CDF</strong></td>
      <td><strong>${fmt(sale.total_amount)} CDF</strong></td>
      <td>${paymentLabel(sale.payment_type)}</td>
      <td>${saleStatusBadge(sale.status)}</td>
      <td>
        <div class="d-flex gap-1">
          <button class="btn-actions btn-view-invoice" data-sale-id="${sale.id}" title="Facture">🧾</button>
          <button class="btn-actions btn-delete-sale ${CAN_DELETE ? '' : 'disabled-action'}" data-sale-id="${sale.id}" title="Supprimer">🗑</button>
        </div>
      </td>
    </tr>
  `).join('');

  renderSalesPagination(sales.length);
}

function renderOutgoingsTable(rows) {
  const tbody = $('outflowTableBody');
  const inline = $('outflowTableBodyInline');
  const html = !rows || !rows.length
    ? `<tr><td colspan="8" class="text-center py-4">Aucune autre sortie.</td></tr>`
    : rows.map(row => `
      <tr data-id="${row.id}" data-user-id="${row.user_id}">
        <td>${row.id}</td>
        <td>${row.created_at || ''}</td>
        <td>${row.user_name || '—'}</td>
        <td>${row.product_name || '—'}</td>
        <td>${row.unit_name || '—'}</td>
        <td>${row.quantity || 0}</td>
        <td>${row.reason || '—'}</td>
        <td>
          <div class="d-flex gap-1">
            <button class="btn-actions btn-edit-outgoing" data-id="${row.id}" title="Modifier">✎</button>
            <button class="btn-actions btn-delete-outgoing" data-id="${row.id}" title="Supprimer">🗑</button>
          </div>
        </td>
      </tr>
    `).join('');

  if (tbody) tbody.innerHTML = html;
  if (inline) inline.innerHTML = html;
}

function renderInvoice(sale) {
  const items = (sale.items || []).map(item => `
    <tr>
      <td style="padding:8px;border:1px solid #dee2e6;">${item.product_name || '—'}</td>
      <td style="padding:8px;border:1px solid #dee2e6;">${item.unit_name || '—'}</td>
      <td style="padding:8px;border:1px solid #dee2e6;text-align:center;">${Number(item.quantity || 0)}</td>
      <td style="padding:8px;border:1px solid #dee2e6;text-align:right;">${fmt(item.price)} CDF</td>
      <td style="padding:8px;border:1px solid #dee2e6;text-align:right;">${fmt(item.total)} CDF</td>
    </tr>
  `).join('');

  const extraExpense = Number(sale.extra_expense_amount || 0);
  const grandTotal = Number(sale.total_amount || 0);

  return `
    <div id="invoice-content-wrapper" style="width:100%; max-width:180mm; margin:0 auto; background:#fff; color:#111; box-sizing:border-box; font-family:Arial, sans-serif;">
      <div style="padding:12mm 10mm 10mm 10mm;">
        <div style="text-align:center; margin-bottom:18px;">
          <img src="/Imgs/Logos/logo_full.png" alt="Logo" style="max-height:70px; max-width:100%; display:block; margin:0 auto 10px auto;" onerror="this.style.display='none'">
          <h4 style="margin:0; font-size:20px; font-weight:700; letter-spacing:.5px;">FACTURE</h4>
          <div style="color:#6b7280; font-size:13px; margin-top:4px;">Compassion Pharmacie</div>
        </div>

        <div style="display:flex; justify-content:space-between; gap:16px; margin-bottom:18px; flex-wrap:wrap;">
          <div style="flex:1; min-width:240px; font-size:13px; line-height:1.7;">
            <div><strong>N° Vente :</strong> ${sale.id || '—'}</div>
            <div><strong>Date :</strong> ${sale.created_at || '—'}</div>
            <div><strong>Vendeur :</strong> ${sale.user_name || '—'}</div>
            <div><strong>Dépense supplémentaire :</strong> ${fmt(extraExpense)} CDF</div>
            <div><strong>Motif :</strong> ${sale.extra_expense_description || '—'}</div>
          </div>

          <div style="flex:1; min-width:240px; font-size:13px; line-height:1.7; text-align:right;">
            <div><strong>Client :</strong> ${sale.client_number || '—'}</div>
            <div><strong>Paiement :</strong> ${paymentLabel(sale.payment_type)}</div>
            <div><strong>Statut :</strong> ${sale.status === 'paid' ? 'Payée' : 'Non payée'}</div>
          </div>
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:13px; table-layout:fixed;">
          <thead>
            <tr>
              <th style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;text-align:left;">Produit</th>
              <th style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;text-align:left;">Unité</th>
              <th style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;text-align:center;">Qté</th>
              <th style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;text-align:right;">Prix</th>
              <th style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;text-align:right;">Total</th>
            </tr>
          </thead>
          <tbody>${items || `
            <tr>
              <td colspan="5" style="padding:12px;border:1px solid #dee2e6;text-align:center;color:#6b7280;">Aucun article</td>
            </tr>
          `}</tbody>
          <tfoot>
            <tr>
              <th colspan="4" style="padding:10px;border:1px solid #dee2e6;text-align:right;">TOTAL</th>
              <th style="padding:10px;border:1px solid #dee2e6;text-align:right;">${fmt(grandTotal)} CDF</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  `;
}

function filterSales() {
  const search = normalizeText($('salesSearch')?.value || '');
  const payment = $('filterPayment')?.value || 'all';
  const userId = $('filterUser')?.value || 'all';

  return salesStore.filter(sale => {
    const hay = normalizeText(
      `${sale.id} ${sale.client_number || ''} ${sale.user_name || ''} ${(sale.items || []).map(i => i.product_name).join(' ')} ${sale.total_amount || ''}`
    );
    const searchOk = !search || hay.includes(search);
    const paymentOk = payment === 'all' || sale.payment_type === payment;
    const userOk = userId === 'all' || String(sale.user_id) === String(userId);
    return searchOk && paymentOk && userOk;
  });
}

function filterOutgoings() {
  const search = normalizeText($('outflowSearch')?.value || '');
  const userId = $('filterOutflowUser')?.value || 'all';

  return outgoingsStore.filter(row => {
    const hay = normalizeText(`${row.id} ${row.product_name || ''} ${row.reason || ''} ${row.description || ''} ${row.user_name || ''}`);
    const searchOk = !search || hay.includes(search);
    const userOk = userId === 'all' || String(row.user_id) === String(userId);
    return searchOk && userOk;
  });
}

function renderCurrentSalesView() {
  setTableLoading(true);
  requestAnimationFrame(() => {
    const filtered = filterSales();
    renderSalesTable(filtered);
    $('salesFooterCount') && ($('salesFooterCount').textContent = `Affichage ${filtered.length} vente(s)`);
    persistComputedStats();
    setTableLoading(false);
  });
}

function renderCurrentOutgoingsView() {
  setTableLoading(true);
  requestAnimationFrame(() => {
    const filtered = filterOutgoings();
    renderOutgoingsTable(filtered);
    $('outflowFooterCount') && ($('outflowFooterCount').textContent = `Affichage ${filtered.length} sortie(s)`);
    $('outflowTotalCount') && ($('outflowTotalCount').textContent = `${filtered.length}`);
    setTableLoading(false);
  });
}

function decreaseLocalStock(productId, qty) {
  const p = getProductById(productId);
  if (!p) return;
  p.available_stock = Math.max(0, Number(p.available_stock || 0) - Number(qty || 0));
  saveStore();
}

function applyStockUpdates(updates) {
  if (!Array.isArray(updates)) return;
  updates.forEach(update => {
    const idx = productsStore.findIndex(p => String(p.id) === String(update.id));
    if (idx >= 0) {
      productsStore[idx] = normalizeProduct({
        ...productsStore[idx],
        ...update,
      });
    }
  });
  saveStore();
}

function applyPriceUpdates(updates) {
  if (!Array.isArray(updates)) return;

  updates.forEach(update => {
    const idx = productsStore.findIndex(p => String(p.id) === String(update.product_id));
    if (idx >= 0) {
      productsStore[idx] = {
        ...normalizeProduct(productsStore[idx]),
        current_sale_price: Number(update.new_price || 0),
      };
    }
  });

  saveStore();
}

function getReservedQuantity(productId, unitId, excludeIndex = null) {
  return currentSaleLines.reduce((sum, line, idx) => {
    if (excludeIndex !== null && idx === excludeIndex) return sum;
    if (String(line.product_id) !== String(productId)) return sum;
    if (unitId && String(line.product_unit_id || '') !== String(unitId)) return sum;
    return sum + Number(line.quantity || 0);
  }, 0);
}

function renderSaleLine(line, index) {
  const p = line.product || null;
  const units = p?.units || [];
  const qty = Number(line.quantity || 1);
  const price = Number(line.price || p?.current_sale_price || 0);

  return `
    <div class="line-item-card" data-line-index="${index}">
      <div class="line-item-grid">
        <div class="position-relative">
          <label class="form-label">Produit</label>
          <input type="text" class="form-control sale-product-search" value="${p ? (p.name || '') : ''}" placeholder="Rechercher...">
          <input type="hidden" class="sale-product-id" value="${p ? p.id : ''}">
          <div class="search-results-box d-none sale-product-results"></div>
        </div>

        <div>
          <label class="form-label">Unité</label>
          <select class="form-select sale-unit">
            ${units.length ? units.map(u => `<option value="${u.id}" ${String(u.id) === String(line.product_unit_id) ? 'selected' : ''}>${u.name}</option>`).join('') : '<option value="">Aucune unité</option>'}
          </select>
        </div>

        <div>
          <label class="form-label">Stock</label>
          <input type="text" class="form-control sale-stock-display" value="${p ? fmt(p.available_stock) : ''}" readonly>
        </div>

        <div>
          <label class="form-label">Prix</label>
          <input type="number" class="form-control sale-price" value="${price}" min="0" required data-base-price="${price}" data-edited="0">
        </div>

        <button type="button" class="btn btn-outline-danger btn-remove-line">×</button>
      </div>

      <div class="row g-2 mt-2">
        <div class="col-md-4">
          <label class="form-label">Quantité</label>
          <input type="number" class="form-control sale-qty" value="${qty}" min="1" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Total ligne</label>
          <input type="text" class="form-control sale-line-total" value="${fmt(qty * price)} CDF" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label">Disponible</label>
          <input type="text" class="form-control sale-stock-display" value="${p ? fmt(p.available_stock) : ''}" readonly>
        </div>
      </div>
    </div>
  `;
}

function updateGrandTotal() {
  let total = 0;
  document.querySelectorAll('.line-item-card').forEach(card => {
    const qty = Number(card.querySelector('.sale-qty')?.value || 0);
    const price = Number(card.querySelector('.sale-price')?.value || 0);
    total += qty * price;
  });

  total += Number($('extraExpenseAmount')?.value || 0);

  const el = $('saleGrandTotal');
  if (el) el.textContent = `${fmt(total)} CDF`;
}

function renderSaleLines() {
  const container = $('saleLines');
  if (!container) return;
  container.innerHTML = currentSaleLines.map((l, i) => renderSaleLine(l, i)).join('');
  wireSaleLineEvents();
  updateGrandTotal();
}

function addSaleLine(defaultProduct = null) {
  currentSaleLines.push({
    product: defaultProduct,
    product_id: defaultProduct?.id || '',
    product_unit_id: defaultProduct?.unit_id || '',
    quantity: 1,
    price: defaultProduct?.current_sale_price || 0,
  });
  renderSaleLines();
}

function removeSaleLine(index) {
  currentSaleLines.splice(index, 1);
  if (!currentSaleLines.length) addSaleLine(null);
  renderSaleLines();
}

function wireSaleLineEvents() {
  document.querySelectorAll('.line-item-card').forEach((card, index) => {
    const productSearch = card.querySelector('.sale-product-search');
    const productIdInput = card.querySelector('.sale-product-id');
    const resultsBox = card.querySelector('.sale-product-results');
    const unitSel = card.querySelector('.sale-unit');
    const qtyInput = card.querySelector('.sale-qty');
    const priceInput = card.querySelector('.sale-price');
    const stockInput = card.querySelector('.sale-stock-display');
    const totalInput = card.querySelector('.sale-line-total');
    const removeBtn = card.querySelector('.btn-remove-line');

    if (!productSearch || !productIdInput || !resultsBox || !unitSel || !qtyInput || !priceInput || !stockInput || !totalInput || !removeBtn) return;

    removeBtn.onclick = () => removeSaleLine(index);

    productSearch.oninput = () => {
      const q = normalizeText(productSearch.value.trim());
      if (q.length < 1) {
        resultsBox.classList.add('d-none');
        resultsBox.innerHTML = '';
        return;
      }

      const found = productsStore.filter(p => {
        const hay = normalizeText(`${p.name || ''} ${p.reference || ''} ${p.category || ''}`);
        return hay.includes(q);
      }).slice(0, 20);

      if (!found.length) {
        resultsBox.innerHTML = `<div class="search-results-item text-muted">Aucun produit trouvé</div>`;
        resultsBox.classList.remove('d-none');
        return;
      }

      resultsBox.innerHTML = found.map(p => `
        <div class="search-results-item sale-product-option" data-id="${p.id}">
          <div style="font-weight:600">${p.name}</div>
          <div style="font-size:12px;color:#64748b">${p.reference || ''} — ${p.category || ''}</div>
        </div>
      `).join('');
      resultsBox.classList.remove('d-none');
    };

    resultsBox.onclick = (e) => {
      const opt = e.target.closest('.sale-product-option');
      if (!opt) return;

      const product = getProductById(opt.dataset.id);
      if (!product) return;

      productSearch.value = product.name || '';
      productIdInput.value = product.id;
      resultsBox.classList.add('d-none');

      const units = product.units || [];
      unitSel.innerHTML = units.length
        ? units.map(u => `<option value="${u.id}">${u.name}</option>`).join('')
        : '<option value="">Aucune unité</option>';

      if (product.unit_id) unitSel.value = String(product.unit_id);

      const previousQty = Number(currentSaleLines[index]?.quantity || 1);
      const stock = Number(product.available_stock || 0);
      const price = Number(product.current_sale_price || 0);

      stockInput.value = fmt(stock);
      priceInput.value = price;
      priceInput.dataset.basePrice = String(price);
      priceInput.dataset.edited = '0';
      qtyInput.value = Math.min(previousQty, Math.max(1, stock || 1));
      totalInput.value = `${fmt(Number(qtyInput.value || 0) * price)} CDF`;

      currentSaleLines[index].product = product;
      currentSaleLines[index].product_id = product.id;
      currentSaleLines[index].product_unit_id = product.unit_id || (units[0] ? units[0].id : '');
      currentSaleLines[index].price = price;
      currentSaleLines[index].quantity = Number(qtyInput.value || 1);

      updateGrandTotal();
    };

    unitSel.onchange = () => {
      currentSaleLines[index].product_unit_id = unitSel.value;
    };

    qtyInput.oninput = () => {
      const product = currentSaleLines[index].product;
      const qty = Number(qtyInput.value || 0);
      const stock = Number(product?.available_stock || 0);
      const reserved = getReservedQuantity(product?.id, currentSaleLines[index].product_unit_id, index);
      const allowed = Math.max(0, stock - reserved);

      if (qty > allowed) {
        showToast(`La quantité ne peut pas dépasser le stock disponible (${allowed}).`, 'warning');
        qtyInput.value = allowed;
      }

      const safeQty = Number(qtyInput.value || 0);
      const price = Number(priceInput.value || 0);
      currentSaleLines[index].quantity = safeQty;
      currentSaleLines[index].price = price;
      totalInput.value = `${fmt(safeQty * price)} CDF`;
      updateGrandTotal();
    };

    priceInput.oninput = () => {
      priceInput.dataset.edited = '1';
      const qty = Number(qtyInput.value || 0);
      const price = Number(priceInput.value || 0);
      currentSaleLines[index].price = price;
      currentSaleLines[index].quantity = qty;
      totalInput.value = `${fmt(qty * price)} CDF`;
      updateGrandTotal();
    };

    document.addEventListener('click', function hideHandler(ev) {
      if (!resultsBox.contains(ev.target) && ev.target !== productSearch) {
        resultsBox.classList.add('d-none');
      }
    }, { once: true });
  });
}

function detectPriceChanges() {
  const changes = [];

  document.querySelectorAll('.line-item-card').forEach((card) => {
    const productId = card.querySelector('.sale-product-id')?.value;
    const product = getProductById(productId);
    const priceInput = card.querySelector('.sale-price');

    if (!product || !priceInput) return;

    const oldPrice = Number(priceInput.dataset.basePrice || product.current_sale_price || 0);
    const newPrice = Number(priceInput.value || 0);
    const edited = priceInput.dataset.edited === '1';

    if (edited && newPrice !== oldPrice) {
      changes.push({
        product_id: product.id,
        product_name: product.name,
        old_price: oldPrice,
        new_price: newPrice
      });
    }
  });

  return changes;
}

function initClientSearch() {
  const clientInput = $('clientSearch');
  const clientResults = $('clientSearchResults');
  const clientNumber = $('clientNumber');

  if (!clientInput || !clientResults || !clientNumber) return;

  clientInput.addEventListener('input', () => {
    clearTimeout(clientSearchDebounce);
    clientSearchDebounce = setTimeout(() => {
      const q = normalizeText(clientInput.value.trim());

      if (!q) {
        clientResults.classList.add('d-none');
        clientResults.innerHTML = '';
        return;
      }

      const found = getClientByQuery(q);

      if (!found.length) {
        clientResults.innerHTML = `<div class="search-results-item text-muted">Aucun client trouvé</div>`;
        clientResults.classList.remove('d-none');
        return;
      }

      clientResults.innerHTML = found.map(c => `
        <div class="search-results-item client-option"
             data-client-number="${c.client_number || ''}"
             data-client-name="${(c.name || '').replace(/"/g, '&quot;')}">
          <div style="font-weight:600">${c.name || '—'}</div>
          <div style="font-size:12px;color:#64748b">
            ${c.client_number || ''}${c.phone ? ' — ' + c.phone : ''}
          </div>
        </div>
      `).join('');

      clientResults.classList.remove('d-none');
    }, 180);
  });

  clientResults.addEventListener('click', (e) => {
    const opt = e.target.closest('.client-option');
    if (!opt) return;
    clientNumber.value = opt.dataset.clientNumber || '';
    clientInput.value = opt.dataset.clientNumber || opt.dataset.clientName || '';
    clientResults.classList.add('d-none');
  });

  document.addEventListener('click', (e) => {
    if (!clientResults.contains(e.target) && e.target !== clientInput) {
      clientResults.classList.add('d-none');
    }
  });
}

function initOutgoingSearch() {
  const input = $('outflowProductSearch');
  const results = $('outflowProductResults');
  const productId = $('outflowProductId');
  const unitSel = $('outflowUnitId');

  if (!input || !results || !productId || !unitSel) return;

  input.addEventListener('input', () => {
    clearTimeout(outgoingSearchDebounce);
    outgoingSearchDebounce = setTimeout(() => {
      const q = normalizeText(input.value.trim());
      if (!q) {
        results.classList.add('d-none');
        results.innerHTML = '';
        return;
      }

      const found = productsStore
        .filter(p => Number(p.available_stock || 0) > 0)
        .filter(p => normalizeText(`${p.name || ''} ${p.reference || ''} ${p.category || ''}`).includes(q))
        .slice(0, 20);

      if (!found.length) {
        results.innerHTML = `<div class="search-results-item text-muted">Aucun produit trouvé</div>`;
        results.classList.remove('d-none');
        return;
      }

      results.innerHTML = found.map(p => `
        <div class="search-results-item outflow-product-option" data-id="${p.id}">
          <div style="font-weight:600">${p.name}</div>
          <div style="font-size:12px;color:#64748b">${p.reference || ''} — ${p.category || ''}</div>
        </div>
      `).join('');
      results.classList.remove('d-none');
    }, 180);
  });

  results.addEventListener('click', (e) => {
    const opt = e.target.closest('.outflow-product-option');
    if (!opt) return;

    const p = getProductById(opt.dataset.id);
    if (!p) return;

    input.value = p.name || '';
    productId.value = p.id;
    results.classList.add('d-none');

    const units = p.units || [];
    unitSel.innerHTML = units.length
      ? units.map(u => `<option value="${u.id}">${u.name}</option>`).join('')
      : '<option value="">Aucune unité</option>';

    if (p.unit_id) unitSel.value = String(p.unit_id);

    $('outflowQuantity').value = 1;
    $('outflowStockView') && ($('outflowStockView').value = `${fmt(p.available_stock || 0)} en stock`);
  });

  document.addEventListener('click', (e) => {
    if (!results.contains(e.target) && e.target !== input) {
      results.classList.add('d-none');
    }
  });
}

function refreshProductsFromServer() {
  if (!navigator.onLine) return Promise.resolve(false);
  return ajaxRequest(`${BASE_URL}/stock/list-all`)
    .then(res => {
      if (Array.isArray(res.products)) {
        productsStore = res.products.map(normalizeProduct);
        saveStore();
      }
      return true;
    })
    .catch(() => false);
}

function refreshClientsFromServer() {
  if (!navigator.onLine) return Promise.resolve(false);
  return ajaxRequest(`${BASE_URL}/clients/list-all`)
    .then(res => {
      if (Array.isArray(res.clients)) {
        clientsStore = res.clients;
        saveClientsCache();
      }
      return true;
    })
    .catch(() => false);
}

function refreshSalesFromServer({ silent = false } = {}) {
  if (!navigator.onLine) return Promise.resolve(false);
  if (!silent) setLoading(true);

  return ajaxRequest(`${BASE_URL}/ventes/list-all?user_id=${encodeURIComponent($('filterUser')?.value || 'all')}`)
    .then(res => {
      if (Array.isArray(res.sales)) salesStore = res.sales;
      if (res.products && Array.isArray(res.products)) {
        productsStore = res.products.map(normalizeProduct);
      }
      if (res.stats) {
        saveStatsCache(res.stats);
        renderStats(res.stats);
      } else {
        persistComputedStats();
      }

      saveStore();
      renderCurrentSalesView();
      return true;
    })
    .catch(() => false)
    .finally(() => { if (!silent) setLoading(false); });
}

function refreshOutgoingsFromServer({ silent = false } = {}) {
  if (!navigator.onLine) return Promise.resolve(false);
  if (!silent) setLoading(true);

  return ajaxRequest(`${BASE_URL}/sorties/list-all`)
    .then(res => {
      if (Array.isArray(res.outgoings)) {
        outgoingsStore = res.outgoings;
        saveOutgoingsCache();
      }
      renderCurrentOutgoingsView();
      return true;
    })
    .catch(() => false)
    .finally(() => { if (!silent) setLoading(false); });
}

function decreaseStockAfterOutgoing(productId, qty) {
  const p = getProductById(productId);
  if (!p) return;
  p.available_stock = Math.max(0, Number(p.available_stock || 0) - Number(qty || 0));
  saveStore();
}

function decreaseProductsLocalFromSaleItems(items) {
  (items || []).forEach(item => decreaseLocalStock(item.product_id, item.quantity));
  saveStore();
}

function openModal(id) {
  const el = $(id);
  if (!el) return null;
  return bootstrap.Modal.getOrCreateInstance(el);
}

function prepareSaleModal() {
  currentSaleLines = [{ product: null, product_id: '', product_unit_id: '', quantity: 1, price: 0 }];
  renderSaleLines();
  $('clientSearch') && ($('clientSearch').value = '');
  $('clientNumber') && ($('clientNumber').value = '');
  $('paymentType') && ($('paymentType').value = 'cash');
  $('saleStatus') && ($('saleStatus').value = 'paid');
  $('extraExpenseAmount') && ($('extraExpenseAmount').value = '');
  $('extraExpenseDescription') && ($('extraExpenseDescription').value = '');
  updateGrandTotal();
}

function prepareOutflowModal() {
  $('outflowId') && ($('outflowId').value = '');
  $('outflowMethod') && ($('outflowMethod').value = 'POST');
  $('outflowProductSearch') && ($('outflowProductSearch').value = '');
  $('outflowProductId') && ($('outflowProductId').value = '');
  $('outflowUnitId') && ($('outflowUnitId').innerHTML = '<option value="">Choisir d\'abord un produit</option>');
  $('outflowQuantity') && ($('outflowQuantity').value = 1);
  $('outflowReason') && ($('outflowReason').value = '');
  $('outflowStockView') && ($('outflowStockView').value = '');
  $('outflowProductResults')?.classList.add('d-none');
  renderCurrentOutgoingsView();
}

function clearModalValues(modalEl) {
  if (modalEl.id === 'invoiceModal' && isGeneratingPdf) {
    return;
  }

  const form = modalEl.querySelector('form');
  if (form) form.reset();

  modalEl.querySelectorAll('input[type="hidden"]').forEach(input => {
    if (input.id === 'outflowMethod') {
      input.value = 'POST';
    } else if (input.id !== 'editOutgoingId') {
      input.value = '';
    }
  });

  modalEl.querySelectorAll('.search-results-box').forEach(box => {
    box.classList.add('d-none');
    box.innerHTML = '';
  });

  if (modalEl.id === 'saleModal') {
    currentSaleLines = [];
  }

  if (modalEl.id === 'outflowModal' && $('outflowStockView')) {
    $('outflowStockView').value = '';
  }

  if (modalEl.id === 'invoiceModal') {
    const body = $('invoiceBody');
    if (body && !isGeneratingPdf) {
      body.innerHTML = '';
    }
  }
}

async function processSalesQueue() {
  const queue = queueGet(SALES_QUEUE_KEY);
  if (!queue.length || !navigator.onLine) return;

  showToast(`Synchronisation de ${queue.length} vente(s)...`, 'info', 5000);
  const remaining = [];

  for (const op of queue) {
    try {
      const res = await ajaxRequest(`${BASE_URL}/ventes`, 'POST', op.payload);
      if (res.sale) {
        salesStore.unshift(res.sale);
        if (Array.isArray(res.stock_updates)) applyStockUpdates(res.stock_updates);
        if (Array.isArray(res.price_updates)) applyPriceUpdates(res.price_updates);
        saveStore();
      }
    } catch (_) {
      remaining.push(op);
    }
  }

  queueSet(SALES_QUEUE_KEY, remaining);
  renderCurrentSalesView();
  await refreshSalesFromServer({ silent: true });
  await refreshProductsFromServer();
  persistComputedStats();
  showToast('Synchronisation ventes terminée.', 'success');
}

async function processOutgoingsQueue() {
  const queue = queueGet(OUTGOINGS_QUEUE_KEY);
  if (!queue.length || !navigator.onLine) return;

  showToast(`Synchronisation de ${queue.length} autre(s) sortie(s)...`, 'info', 5000);
  const remaining = [];

  for (const op of queue) {
    try {
      const res = await ajaxRequest(`${BASE_URL}/sorties`, 'POST', op.payload);
      if (res.outgoing) {
        outgoingsStore.unshift(res.outgoing);
        saveOutgoingsCache();
        decreaseStockAfterOutgoing(op.payload.product_id, op.payload.quantity);
      }
    } catch (_) {
      remaining.push(op);
    }
  }

  queueSet(OUTGOINGS_QUEUE_KEY, remaining);
  renderCurrentOutgoingsView();
  await refreshOutgoingsFromServer({ silent: true });
  await refreshProductsFromServer();
  persistComputedStats();
  showToast('Synchronisation autres sorties terminée.', 'success');
}

function handleOffline() {
  const offlineBanner = $('offlineBanner');
  if (!offlineBanner) return;
  offlineBanner.classList.toggle('show', !navigator.onLine);
}

document.addEventListener('click', (e) => {
  if (e.target.closest('#btnOpenSale')) {
    if (!CAN_ADD) return showToast("Vous n'avez pas le droit d'ajouter une vente.", 'warning');
    prepareSaleModal();
    openModal('saleModal')?.show();
    return;
  }

  if (e.target.closest('#btnOpenOutflow')) {
    if (!CAN_ADD) return showToast("Vous n'avez pas le droit d'ajouter une autre sortie.", 'warning');
    prepareOutflowModal();
    openModal('outflowModal')?.show();
    return;
  }
});

$('btnAddSaleLine')?.addEventListener('click', () => addSaleLine(null));

$('btnPrintDailyReport')?.addEventListener('click', () => {
  const userId = $('filterUser')?.value || 'all';
  window.open(`${BASE_URL}/ventes/report/daily?user_id=${encodeURIComponent(userId)}`, '_blank');
});

$('salesSearch')?.addEventListener('input', () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    currentSalesPage = 1;
    renderCurrentSalesView();
  }, 180);
});

$('filterPayment')?.addEventListener('change', () => {
  currentSalesPage = 1;
  renderCurrentSalesView();
});

$('filterUser')?.addEventListener('change', () => {
  currentSalesPage = 1;
  renderCurrentSalesView();
});

$('btnApplyFilters')?.addEventListener('click', () => {
  currentSalesPage = 1;
  renderCurrentSalesView();
});

$('extraExpenseAmount')?.addEventListener('input', updateGrandTotal);
$('extraExpenseDescription')?.addEventListener('input', updateGrandTotal);

$('salesPagination')?.addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-page]');
  if (!btn) return;

  const page = Number(btn.dataset.page || 1);
  const totalPages = Math.max(1, Math.ceil(filterSales().length / SALES_PER_PAGE));

  if (page < 1 || page > totalPages) return;

  currentSalesPage = page;
  renderCurrentSalesView();
});

$('outflowSearch')?.addEventListener('input', () => {
  clearTimeout(outgoingSearchDebounce);
  outgoingSearchDebounce = setTimeout(renderCurrentOutgoingsView, 180);
});

$('filterOutflowUser')?.addEventListener('change', renderCurrentOutgoingsView);

$('outflowUnitId')?.addEventListener('change', () => {
  const productId = $('outflowProductId')?.value;
  const p = getProductById(productId);
  if ($('outflowStockView')) {
    $('outflowStockView').value = p ? `${fmt(p.available_stock || 0)} en stock` : '';
  }
});

$('saleForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!CAN_ADD) return showToast("Vous n'avez pas le droit d'ajouter une vente.", 'warning');

  const saleItems = [];
  let valid = true;

  document.querySelectorAll('.line-item-card').forEach((card, idx) => {
    const productId = card.querySelector('.sale-product-id')?.value;
    const unitId = card.querySelector('.sale-unit')?.value;
    const qty = Number(card.querySelector('.sale-qty')?.value || 0);
    const price = Number(card.querySelector('.sale-price')?.value || 0);
    const product = getProductById(productId);

    if (!product || qty < 1) valid = false;

    const reserved = getReservedQuantity(productId, unitId, idx);
    const allowed = Math.max(0, Number(product?.available_stock || 0) - reserved + qty);
    if (qty > allowed) valid = false;

    saleItems.push({
      product_id: productId,
      product_unit_id: unitId,
      quantity: qty,
      price: price,
    });
  });

  if (!valid) {
    showToast('Vérifiez les produits, unités et quantités.', 'warning');
    return;
  }

  const extraExpenseAmount = Number($('extraExpenseAmount')?.value || 0);
  const extraExpenseDescription = $('extraExpenseDescription')?.value || '';

  const priceChanges = detectPriceChanges();

  if (priceChanges.length > 0) {
    const text = priceChanges.length === 1
      ? `Êtes-vous sûr de vouloir modifier le prix du produit "${priceChanges[0].product_name}" ?`
      : `Voulez-vous vraiment modifier les prix de vente des produits suivants : ${priceChanges.map(c => c.product_name).join(', ')} ?`;

    const result = await Swal.fire({
      title: "Confirmer la modification",
      text: text,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Oui, modifier",
      cancelButtonText: "Annuler"
    });

    if (!result.isConfirmed) return;
  }

  const payload = {
    client_number: $('clientNumber')?.value || null,
    payment_type: $('paymentType')?.value || 'cash',
    status: $('saleStatus')?.value || 'paid',
    items: saleItems,
    extra_expense_amount: extraExpenseAmount,
    extra_expense_description: extraExpenseDescription,
    apply_price_changes: priceChanges.length ? 1 : 0,
    price_changes: priceChanges,
  };

  const btn = $('saleSubmitBtn');

  if (!navigator.onLine) {
    queuePush(SALES_QUEUE_KEY, { type: 'sale', payload, createdAt: Date.now() });

    const salePreview = {
      id: 'OFF-' + Date.now(),
      user_id: PAGE.userId || null,
      user_name: CURRENT_USER_NAME,
      client_number: payload.client_number,
      total_amount: saleItems.reduce((s, x) => s + x.quantity * x.price, 0) + extraExpenseAmount,
      extra_expense_amount: extraExpenseAmount,
      extra_expense_description: extraExpenseDescription,
      payment_type: payload.payment_type,
      status: payload.status,
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
      items: saleItems.map(i => {
        const p = getProductById(i.product_id);
        return {
          product_name: p?.name || '—',
          unit_name: (p?.units || []).find(u => String(u.id) === String(i.product_unit_id))?.name || '—',
          quantity: i.quantity,
          price: i.price,
          total: i.quantity * i.price,
        };
      }),
    };

    decreaseProductsLocalFromSaleItems(saleItems);
    applyPriceUpdates(priceChanges.map(c => ({
      product_id: c.product_id,
      new_price: c.new_price,
    })));
    saveStore();
    persistComputedStats();

    saveCurrentInvoice(salePreview);
    localStorage.setItem(SALES_INVOICE_KEY, JSON.stringify(salePreview));

    const invoiceBody = $('invoiceBody');
    if (invoiceBody) invoiceBody.innerHTML = renderInvoice(salePreview);

    openModal('invoiceModal')?.show();
    openModal('saleModal')?.hide();
    renderCurrentSalesView();
    showToast('Vente mise en attente hors-ligne.', 'warning');
    return;
  }

  setBtnLoading(btn, true);
  setTableLoading(true);
  try {
    const res = await ajaxRequest(`${BASE_URL}/ventes`, 'POST', payload);
    showToast(res.message || 'Vente enregistrée.', 'success');

    if (res.sale) {
      salesStore.unshift(res.sale);

      saveCurrentInvoice(res.sale);
      localStorage.setItem(SALES_INVOICE_KEY, JSON.stringify(res.sale));

      const invoiceBody = $('invoiceBody');
      if (invoiceBody) {
        invoiceBody.innerHTML = res.invoice_html || renderInvoice(res.sale);
      }

      if (Array.isArray(res.stock_updates)) applyStockUpdates(res.stock_updates);
      else decreaseProductsLocalFromSaleItems(saleItems);

      if (Array.isArray(res.price_updates) && res.price_updates.length) {
        applyPriceUpdates(res.price_updates);
      }

      saveStore();
      persistComputedStats();
      openModal('invoiceModal')?.show();
    }

    openModal('saleModal')?.hide();
    await refreshSalesFromServer({ silent: true });
    await refreshProductsFromServer();
    persistComputedStats();
  } catch (err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    setBtnLoading(btn, false);
    setTableLoading(false);
  }
});

$('outflowForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!CAN_ADD) return showToast("Vous n'avez pas le droit d'ajouter une autre sortie.", 'warning');

  const payload = {
    product_id: $('outflowProductId')?.value || null,
    product_unit_id: $('outflowUnitId')?.value || null,
    quantity: Number($('outflowQuantity')?.value || 0),
    reason: $('outflowReason')?.value || '',
    description: $('outflowReason')?.value || '',
  };

  if (!payload.product_id || !payload.quantity || !payload.reason.trim()) {
    return showToast('Produit, quantité et raison sont obligatoires.', 'warning');
  }

  const btn = $('outflowSubmitBtn');

  if (!navigator.onLine) {
    queuePush(OUTGOINGS_QUEUE_KEY, { type: 'outgoing', payload });
    const product = getProductById(payload.product_id);

    outgoingsStore.unshift({
      id: 'OFF-' + Date.now(),
      user_id: PAGE.userId || null,
      user_name: CURRENT_USER_NAME,
      product_name: product?.name || '—',
      unit_name: (product?.units || []).find(u => String(u.id) === String(payload.product_unit_id))?.name || '—',
      quantity: payload.quantity,
      reason: payload.reason,
      description: payload.description,
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
    });

    decreaseStockAfterOutgoing(payload.product_id, payload.quantity);
    saveOutgoingsCache();
    saveStore();
    persistComputedStats();
    renderCurrentOutgoingsView();
    openModal('outflowModal')?.hide();
    showToast('Autre sortie mise en attente hors-ligne.', 'warning');
    return;
  }

  setBtnLoading(btn, true);
  setTableLoading(true);
  try {
    const res = await ajaxRequest(`${BASE_URL}/sorties`, 'POST', payload);
    showToast(res.message || 'Sortie enregistrée.', 'success');

    if (res.outgoing) {
      outgoingsStore.unshift(res.outgoing);
      saveOutgoingsCache();
    }
    if (res.product) {
      const idx = productsStore.findIndex(p => String(p.id) === String(res.product.id));
      if (idx >= 0) productsStore[idx] = normalizeProduct(res.product);
      saveStore();
    } else {
      decreaseStockAfterOutgoing(payload.product_id, payload.quantity);
    }

    persistComputedStats();
    renderCurrentOutgoingsView();
    openModal('outflowModal')?.hide();
    await refreshOutgoingsFromServer({ silent: true });
    await refreshProductsFromServer();
    persistComputedStats();
  } catch (err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    setBtnLoading(btn, false);
    setTableLoading(false);
  }
});

function getCurrentInvoiceFileName() {
  const sale = getCurrentInvoiceData();
  if (sale?.id) return `facture-${sale.id}.pdf`;
  return 'facture.pdf';
}

async function downloadInvoicePdf() {
  if (isGeneratingPdf) {
    showToast('Génération du PDF en cours.', 'info');
    return;
  }

  const currentSale = getCurrentInvoiceData();
  if (!currentSale) {
    showToast("Aucune facture à télécharger. Ouvre d'abord une facture.", 'warning');
    return;
  }

  if (typeof window.html2pdf !== 'function') {
    showToast("Le générateur PDF n'est pas chargé. Rafraîchis la page.", 'error');
    return;
  }

  isGeneratingPdf = true;
  const downloadBtn = $('btnDownloadInvoicePdf');
  setBtnLoading(downloadBtn, true);

  const temp = document.createElement('div');
  temp.id = 'invoice-pdf-temp';
  temp.style.position = 'fixed';
  temp.style.left = '-10000px';
  temp.style.top = '0';
  temp.style.background = '#fff';
  temp.style.width = '210mm';
  temp.style.minHeight = '297mm';
  temp.style.boxSizing = 'border-box';
  temp.style.display = 'flex';
  temp.style.justifyContent = 'center';
  temp.style.alignItems = 'flex-start';
  temp.innerHTML = renderInvoice(currentSale);

  document.body.appendChild(temp);

  try {
    if (document.fonts?.ready) {
      await document.fonts.ready;
    }
    await new Promise(resolve => setTimeout(resolve, 250));

    const content = temp.querySelector('#invoice-content-wrapper') || temp;

    const opt = {
      margin: [8, 8, 8, 8],
      filename: getCurrentInvoiceFileName(),
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: {
        scale: 2,
        useCORS: true,
        allowTaint: false,
        backgroundColor: '#ffffff',
        scrollY: 0,
        windowWidth: 1200,
      },
      jsPDF: {
        unit: 'mm',
        format: 'a4',
        orientation: 'portrait',
      },
      pagebreak: { mode: ['avoid-all', 'css', 'legacy'] },
    };

    await html2pdf().set(opt).from(content).save();
    showToast('PDF téléchargé avec succès.', 'success');
  } catch (err) {
    console.error('PDF generation error:', err);
    showToast("Erreur lors de la génération du PDF. Réessaie.", 'error');
  } finally {
    temp.remove();
    isGeneratingPdf = false;
    setBtnLoading(downloadBtn, false);
  }
}

$('btnDownloadInvoicePdf')?.addEventListener('click', downloadInvoicePdf);

$('btnPrintInvoice')?.addEventListener('click', () => {
  const sale = getCurrentInvoiceData();
  if (!sale) {
    showToast('Aucune facture à imprimer.', 'warning');
    return;
  }

  const html = renderInvoice(sale);
  const w = window.open('', '_blank');
  if (!w) {
    showToast("Impossible d'ouvrir la fenêtre d'impression.", 'error');
    return;
  }

  w.document.write(`
    <html>
      <head>
        <title>Facture</title>
        <meta charset="utf-8">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
          body { margin: 0; background: #fff; font-family: Arial, sans-serif; }
          @page { size: A4; margin: 8mm; }
          @media print { body { margin: 0; } }
        </style>
      </head>
      <body>${html}</body>
    </html>
  `);
  w.document.close();
  w.focus();
  setTimeout(() => w.print(), 300);
});

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.btn-view-invoice');
  if (!btn) return;

  const sale = salesStore.find(s => String(s.id) === String(btn.dataset.saleId));
  if (!sale) {
    showToast('Facture introuvable en cache.', 'warning');
    return;
  }

  saveCurrentInvoice(sale);
  localStorage.setItem(SALES_INVOICE_KEY, JSON.stringify(sale));

  const invoiceBody = $('invoiceBody');
  if (invoiceBody) {
    invoiceBody.innerHTML = renderInvoice(sale);
  }

  openModal('invoiceModal')?.show();
});

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.btn-delete-sale');
  if (!btn) return;

  if (!CAN_DELETE) return showToast("Vous n'avez pas le droit de supprimer une vente.", 'warning');

  const saleId = btn.dataset.saleId;
  if (!window.confirm('Voulez-vous vraiment supprimer cette vente ?')) return;

  try {
    setTableLoading(true);
    const res = await ajaxRequest(`${BASE_URL}/ventes/${saleId}`, 'DELETE');
    showToast(res.message || 'Vente supprimée.', 'success');
    salesStore = salesStore.filter(s => String(s.id) !== String(saleId));
    saveStore();
    persistComputedStats();
    renderCurrentSalesView();
    await refreshSalesFromServer({ silent: true });
    await refreshProductsFromServer();
    persistComputedStats();
  } catch (err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    setTableLoading(false);
  }
});

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.btn-delete-outgoing');
  if (!btn) return;

  if (!CAN_DELETE) return showToast("Vous n'avez pas le droit de supprimer une autre sortie.", 'warning');

  const id = btn.dataset.id;
  const result = await Swal.fire({
    title: "Êtes-vous sûr ?",
    text: "Voulez-vous vraiment supprimer cette sortie ?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Oui, supprimer",
    cancelButtonText: "Annuler"
  });

  if (!result.isConfirmed) return;

  Swal.fire({
    title: "Suppression lancée",
    text: "La sortie est en cours de suppression.",
    icon: "success",
    timer: 1500,
    showConfirmButton: false
  });

  try {
    setTableLoading(true);
    const res = await ajaxRequest(`${BASE_URL}/sorties/${id}`, 'DELETE');
    showToast(res.message || 'Sortie supprimée.', 'success');
    outgoingsStore = outgoingsStore.filter(x => String(x.id) !== String(id));
    saveOutgoingsCache();
    persistComputedStats();
    renderCurrentOutgoingsView();
    await refreshOutgoingsFromServer({ silent: true });
    await refreshProductsFromServer();
    persistComputedStats();
  } catch (err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    setTableLoading(false);
  }
});

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.btn-edit-outgoing');
  if (!btn) return;

  if (!CAN_EDIT) return showToast("Vous n'avez pas le droit de modifier une autre sortie.", 'warning');

  const row = outgoingsStore.find(x => String(x.id) === String(btn.dataset.id));
  if (!row) return;

  $('editOutgoingId').value = row.id;
  $('editOutgoingReason').value = row.reason || '';
  $('editOutgoingDescription').value = row.description || '';
  $('editOutgoingQuantity').value = row.quantity || 1;
  openModal('editOutgoingModal')?.show();
});

$('editOutgoingForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const id = $('editOutgoingId')?.value;
  const row = outgoingsStore.find(x => String(x.id) === String(id));

  const payload = {
    product_id: row?.product_id,
    product_unit_id: row?.product_unit_id,
    reason: $('editOutgoingReason')?.value || '',
    description: $('editOutgoingDescription')?.value || '',
    quantity: Number($('editOutgoingQuantity')?.value || 0),
  };

  try {
    setTableLoading(true);
    const res = await ajaxRequest(`${BASE_URL}/sorties/${id}`, 'PUT', payload);
    showToast(res.message || 'Sortie modifiée.', 'success');
    await refreshOutgoingsFromServer({ silent: true });
    await refreshProductsFromServer();
    persistComputedStats();
    openModal('editOutgoingModal')?.hide();
  } catch (err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    setTableLoading(false);
  }
});

$('syncBanner')?.addEventListener('click', async () => {
  await processSalesQueue();
  await processOutgoingsQueue();
});

window.addEventListener('online', async () => {
  handleOffline();
  updateSyncBanner();
  await refreshClientsFromServer();
  await refreshProductsFromServer();
  await processSalesQueue();
  await processOutgoingsQueue();
  persistComputedStats();
});

window.addEventListener('offline', () => {
  handleOffline();
  updateSyncBanner();
});

document.addEventListener('hidden.bs.modal', (event) => {
  clearModalValues(event.target);
});

document.addEventListener('DOMContentLoaded', async () => {
  if (!loadStore()) saveStore();

  if (!clientsStore.length) clientsStore = loadClientsCache();
  if (!outgoingsStore.length) outgoingsStore = loadOutgoingsCache();

  if (Array.isArray(PAGE.sales) && PAGE.sales.length) {
    salesStore = PAGE.sales.map(s => ({
      ...s,
      items: (s.items || []).map(i => ({
        ...i,
        purchase_price: Number(i.purchase_price || 0),
        price: Number(i.price || 0),
        quantity: Number(i.quantity || 0),
      }))
    }));
  }

  if (Array.isArray(PAGE.products) && PAGE.products.length) productsStore = PAGE.products.map(normalizeProduct);
  if (Array.isArray(PAGE.clients) && PAGE.clients.length) clientsStore = PAGE.clients;
  if (Array.isArray(PAGE.outgoings) && PAGE.outgoings.length) outgoingsStore = PAGE.outgoings;

  saveStore();
  saveClientsCache();
  saveOutgoingsCache();

  initClientSearch();
  initOutgoingSearch();
  renderSaleLines();
  persistComputedStats();

  handleOffline();
  updateSyncBanner();

  renderCurrentSalesView();
  renderCurrentOutgoingsView();

  if (navigator.onLine) {
    await refreshProductsFromServer();
    await refreshClientsFromServer();
    await refreshSalesFromServer({ silent: true });
    await refreshOutgoingsFromServer({ silent: true });
  }
});
