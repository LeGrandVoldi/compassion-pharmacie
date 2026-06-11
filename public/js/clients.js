const BASE_URL = window.location.origin;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

const CLIENTS_KEY = 'clients_store_v1';
const CLIENTS_QUEUE_KEY = 'clients_queue_v1';
const CLIENTS_HISTORY_KEY = 'clients_history_v1_';

const PAGE = window.CLIENTS_PAGE_INITIAL || {};
const CAN_ADD = !!PAGE.canAdd;
const CAN_EDIT = !!PAGE.canEdit;
const CAN_DELETE = false;
const IS_ADMIN = !!PAGE.isAdmin;

let clientsStore = Array.isArray(PAGE.clients) ? PAGE.clients : [];
let searchTimer = null;

function showToast(message, type = 'success', duration = 4000) {
  const container = document.getElementById('toastContainer');
  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const toast = document.createElement('div');
  toast.className = `toast-item toast-${type}`;
  toast.innerHTML = `
    <span class="toast-icon">${icons[type] || icons.info}</span>
    <span style="flex:1">${message}</span>
    <button class="toast-close" onclick="this.closest('.toast-item').remove()">×</button>
  `;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), duration);
}

function fmt(n) {
  return Number(n || 0).toLocaleString('fr-FR');
}

function ajaxRequest(url, method = 'GET', body = null, isFormData = false) {
  const headers = {
    'X-CSRF-TOKEN': CSRF_TOKEN,
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  };
  if (!isFormData && body) headers['Content-Type'] = 'application/json';

  const opts = { method, headers };
  if (body) opts.body = isFormData ? body : JSON.stringify(body);

  return fetch(url, opts).then(async res => {
    const json = await res.json();
    if (!res.ok) throw new Error(json.message || 'Erreur serveur');
    return json;
  });
}

function saveStore() {
  try {
    localStorage.setItem(CLIENTS_KEY, JSON.stringify({
      savedAt: Date.now(),
      clients: clientsStore
    }));
  } catch (e) {}
}

function loadStore() {
  try {
    const raw = localStorage.getItem(CLIENTS_KEY);
    if (!raw) return false;
    const data = JSON.parse(raw);
    if (!data || !Array.isArray(data.clients)) return false;
    clientsStore = data.clients;
    return true;
  } catch (e) {
    return false;
  }
}

function queueGet() {
  try { return JSON.parse(localStorage.getItem(CLIENTS_QUEUE_KEY) || '[]'); } catch (e) { return []; }
}
function queueSet(queue) {
  localStorage.setItem(CLIENTS_QUEUE_KEY, JSON.stringify(queue));
}
function enqueue(op) {
  const queue = queueGet();
  queue.push({ ...op, _qid: Date.now() + '_' + Math.random().toString(36).slice(2) });
  queueSet(queue);
  updateSyncBanner();
}

function updateSyncBanner() {
  const queue = queueGet();
  const banner = document.getElementById('syncBanner');
  if (queue.length > 0 && navigator.onLine) {
    document.getElementById('syncBannerText').textContent = `Connexion active — ${queue.length} client(s) en attente. Cliquez ici pour synchroniser.`;
    banner.classList.add('show');
  } else {
    banner.classList.remove('show');
  }
}

function setLoading(flag) {
  document.getElementById('tableLoadingOverlay')?.classList.toggle('show', flag);
}

function generateClientNumber() {
  const existing = new Set(clientsStore.map(c => String(c.client_number)));
  let candidate = '';
  do {
    candidate = String(Math.floor(Math.random() * 1000000)).padStart(6, '0');
  } while (existing.has(candidate));
  return candidate;
}

function clientCacheKey(clientNumber, from, to) {
  return `${CLIENTS_HISTORY_KEY}${clientNumber}_${from || 'all'}_${to || 'all'}`;
}

function cacheSet(key, data) {
  try {
    localStorage.setItem(key, JSON.stringify({
      savedAt: Date.now(),
      data
    }));
  } catch (e) {}
}

function cacheGet(key) {
  try {
    const raw = localStorage.getItem(key);
    if (!raw) return null;
    return JSON.parse(raw);
  } catch (e) {
    return null;
  }
}

function applyFilters() {
  const q = document.getElementById('clientsSearch').value.trim().toLowerCase();
  return clientsStore.filter(c => {
    const hay = `${c.client_number || ''} ${c.name || ''} ${c.phone || ''} ${c.address || ''}`.toLowerCase();
    return !q || hay.includes(q);
  });
}

function renderTable(clients) {
  const tbody = document.getElementById('clientsTableBody');
  if (!clients || !clients.length) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4">Aucun client trouvé.</td></tr>`;
    return;
  }

  tbody.innerHTML = clients.map(c => `
    <tr data-id="${c.id}" data-search="${(c.client_number || '') + ' ' + (c.name || '') + ' ' + (c.phone || '') + ' ' + (c.address || '')}">
      <td>${c.id}</td>
      <td>${c.client_number || '—'}</td>
      <td>${c.name || '—'}</td>
      <td>${c.phone || '—'}</td>
      <td>${c.address || '—'}</td>
      <td><strong>${fmt(c.total_spent || 0)} CDF</strong></td>
      <td>${c.last_purchase_at || '—'}</td>
      <td>
        <div class="d-flex gap-1">
          <button class="btn-actions btn-client-history" data-client-id="${c.id}" data-client-number="${c.client_number}" data-client-name="${c.name}" title="Historique">⌛</button>
          <button class="btn-actions btn-edit-client ${CAN_EDIT ? '' : 'disabled-action'}" data-can-edit="${CAN_EDIT ? 1 : 0}" data-id="${c.id}" data-client-number="${c.client_number}" data-name="${c.name}" data-phone="${c.phone || ''}" data-address="${c.address || ''}" title="Modifier">✎</button>
        </div>
      </td>
    </tr>
  `).join('');
}

function renderCurrentView() {
  const filtered = applyFilters();
  renderTable(filtered);
  document.getElementById('clientsFooterCount').innerHTML = `Affichage ${filtered.length} client(s)`;
}

function upsertClient(client) {
  const idx = clientsStore.findIndex(c => String(c.id) === String(client.id));
  if (idx >= 0) {
    clientsStore[idx] = client;
  } else {
    clientsStore.unshift(client);
  }
  saveStore();
  renderCurrentView();
}

function refreshFromServer({ silent = false } = {}) {
  if (!navigator.onLine) return Promise.resolve(false);
  if (!silent) setLoading(true);

  const url = `${BASE_URL}/clients/list-all`;
  return ajaxRequest(url).then(res => {
    clientsStore = res.clients || [];
    saveStore();
    renderCurrentView();
    if (res.stats) renderStats(res.stats);
    return true;
  }).catch(err => {
    showToast('Erreur de chargement : ' + err.message, 'error');
    return false;
  }).finally(() => {
    if (!silent) setLoading(false);
  });
}

function renderStats(stats) {
  document.getElementById('statTotalClients').textContent = fmt(stats.total_clients || 0);
  document.getElementById('statClientsWithSales').textContent = fmt(stats.clients_with_sales_day || 0);
  document.getElementById('statTotalAmountDay').textContent = `${fmt(stats.total_amount_day || 0)} CDF`;
  document.getElementById('statTopClient').textContent = stats.top_client || '—';
}

function openAddModal() {
  if (!CAN_ADD) {
    showToast("Vous n'avez pas le droit d'ajouter un client.", 'warning');
    return;
  }
  document.getElementById('addClientNumber').value = generateClientNumber();
  document.getElementById('addClientName').value = '';
  document.getElementById('addClientPhone').value = '';
  document.getElementById('addClientAddress').value = '';
  new bootstrap.Modal(document.getElementById('addClientModal')).show();
}

function openEditModal(btn) {
  if (btn.dataset.canEdit !== '1') {
    showToast("Vous n'avez pas le droit de modifier un client.", 'warning');
    return;
  }
  document.getElementById('editClientId').value = btn.dataset.id;
  document.getElementById('editClientNumber').value = btn.dataset.clientNumber || '';
  document.getElementById('editClientName').value = btn.dataset.name || '';
  document.getElementById('editClientPhone').value = btn.dataset.phone || '';
  document.getElementById('editClientAddress').value = btn.dataset.address || '';
  new bootstrap.Modal(document.getElementById('editClientModal')).show();
}

function renderHistoryRows(sales) {
  if (!sales || !sales.length) {
    return `<tr><td colspan="6" class="text-center">Aucun achat trouvé.</td></tr>`;
  }

  return sales.map(sale => `
    <tr>
      <td>${sale.id}</td>
      <td>${sale.created_at || ''}</td>
      <td>${sale.user_name || '—'}</td>
      <td>${sale.payment_type || '—'}</td>
      <td>
        <div class="d-flex flex-column gap-1">
          ${(sale.items || []).map(item => `<span class="badge bg-light text-dark border">${item.product_name} x${item.quantity}</span>`).join('')}
        </div>
      </td>
      <td><strong>${fmt(sale.total_amount || 0)} CDF</strong></td>
    </tr>
  `).join('');
}

function renderHistorySummary(summary) {
  document.getElementById('historyTotalSales').textContent = fmt(summary.total_sales || 0);
  document.getElementById('historyTotalAmount').textContent = `${fmt(summary.total_amount || 0)} CDF`;
  document.getElementById('historyTotalItems').textContent = fmt(summary.total_items || 0);
}

function loadClientHistory(clientId, clientNumber, clientName, forceRefresh = false) {
  const from = document.getElementById('historyFrom').value || '';
  const to = document.getElementById('historyTo').value || '';
  const key = clientCacheKey(clientNumber, from, to);

  const title = document.getElementById('clientHistoryTitle');
  const body = document.getElementById('historyBody');

  title.textContent = `Historique d'achat — ${clientName} (${clientNumber})`;
  body.innerHTML = `<tr><td colspan="6" class="text-center">Chargement...</td></tr>`;
  new bootstrap.Modal(document.getElementById('clientHistoryModal')).show();

  const cached = cacheGet(key);

  if (!navigator.onLine && cached?.data) {
    renderHistorySummary(cached.data.summary || {});
    body.innerHTML = renderHistoryRows(cached.data.sales || []);
    return;
  }

  if (!navigator.onLine && !cached?.data) {
    body.innerHTML = `<tr><td colspan="6" class="text-center text-warning">Historique non disponible hors-ligne.</td></tr>`;
    return;
  }

  ajaxRequest(`${BASE_URL}/clients/${clientId}/history?date_from=${encodeURIComponent(from)}&date_to=${encodeURIComponent(to)}`)
    .then(res => {
      cacheSet(key, res);
      renderHistorySummary(res.summary || {});
      body.innerHTML = renderHistoryRows(res.sales || []);
    })
    .catch(err => {
      if (cached?.data) {
        renderHistorySummary(cached.data.summary || {});
        body.innerHTML = renderHistoryRows(cached.data.sales || []);
        showToast('Historique affiché depuis le cache.', 'info');
      } else {
        body.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Erreur : ${err.message}</td></tr>`;
      }
    });
}

document.addEventListener('click', (e) => {
  const btnAdd = e.target.closest('#btnOpenAddClient');
  if (btnAdd) {
    openAddModal();
    return;
  }

  const editBtn = e.target.closest('.btn-edit-client');
  if (editBtn) {
    openEditModal(editBtn);
    return;
  }

  const historyBtn = e.target.closest('.btn-client-history');
  if (historyBtn) {
    loadClientHistory(
      historyBtn.dataset.clientId,
      historyBtn.dataset.clientNumber,
      historyBtn.dataset.clientName
    );
    return;
  }
});

document.getElementById('clientsSearch')?.addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(renderCurrentView, 200);
});
document.getElementById('btnApplyFilters')?.addEventListener('click', renderCurrentView);

document.getElementById('addClientForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  if (!CAN_ADD) {
    showToast("Vous n'avez pas le droit d'ajouter un client.", 'warning');
    return;
  }

  const btn = document.getElementById('addClientSubmitBtn');
  const payload = Object.fromEntries(new FormData(this).entries());

  if (!navigator.onLine) {
    enqueue({
      type: 'addClient',
      url: `${BASE_URL}/clients`,
      method: 'POST',
      data: payload
    });

    const tmp = {
      id: 'tmp-' + Date.now(),
      client_number: payload.client_number,
      name: payload.name,
      phone: payload.phone || null,
      address: payload.address || null,
      total_sales: 0,
      total_spent: 0,
      last_purchase_at: null,
      pending_sync: true,
    };
    upsertClient(tmp);

    showToast('Client mis en file d\'attente — synchronisation à la reconnexion.', 'warning');
    bootstrap.Modal.getInstance(document.getElementById('addClientModal'))?.hide();
    this.reset();
    return;
  }

  btn.disabled = true;
  try {
    const res = await ajaxRequest(`${BASE_URL}/clients`, 'POST', payload);
    showToast(res.message, 'success');
    if (res.client) {
      upsertClient({
        id: res.client.id,
        client_number: res.client.client_number,
        name: res.client.name,
        phone: res.client.phone,
        address: res.client.address,
        total_sales: 0,
        total_spent: 0,
        last_purchase_at: null,
      });
    }
    bootstrap.Modal.getInstance(document.getElementById('addClientModal'))?.hide();
    this.reset();
    await refreshFromServer({ silent: true });
  } catch (err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    btn.disabled = false;
  }
});

document.getElementById('editClientForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  if (!CAN_EDIT) {
    showToast("Vous n'avez pas le droit de modifier un client.", 'warning');
    return;
  }

  const id = document.getElementById('editClientId').value;
  const btn = document.getElementById('editClientSubmitBtn');
  const payload = Object.fromEntries(new FormData(this).entries());

  if (!navigator.onLine) {
    enqueue({
      type: 'updateClient',
      url: `${BASE_URL}/clients/${id}`,
      method: 'PUT',
      data: payload
    });

    const idx = clientsStore.findIndex(c => String(c.id) === String(id));
    if (idx >= 0) {
      clientsStore[idx] = {
        ...clientsStore[idx],
        client_number: payload.client_number || clientsStore[idx].client_number,
        name: payload.name,
        phone: payload.phone || null,
        address: payload.address || null,
      };
      saveStore();
      renderCurrentView();
    }

    showToast('Modification mise en file d\'attente.', 'warning');
    bootstrap.Modal.getInstance(document.getElementById('editClientModal'))?.hide();
    return;
  }

  btn.disabled = true;
  try {
    const res = await ajaxRequest(`${BASE_URL}/clients/${id}`, 'POST', payload, false);
    showToast(res.message, 'success');
    if (res.client) {
      upsertClient({
        id: res.client.id,
        client_number: res.client.client_number,
        name: res.client.name,
        phone: res.client.phone,
        address: res.client.address,
        total_sales: 0,
        total_spent: 0,
        last_purchase_at: null,
      });
    }
    bootstrap.Modal.getInstance(document.getElementById('editClientModal'))?.hide();
    await refreshFromServer({ silent: true });
  } catch (err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    btn.disabled = false;
  }
});

function processQueue() {
  const queue = queueGet();
  if (!queue.length || !navigator.onLine) return;

  showToast(`Synchronisation de ${queue.length} client(s)...`, 'info', 5000);

  (async () => {
    const remaining = [];

    for (const op of queue) {
      try {
        if (op.type === 'addClient') {
          await ajaxRequest(`${BASE_URL}/clients`, 'POST', op.data);
        } else if (op.type === 'updateClient') {
          await ajaxRequest(op.url, 'POST', { ...op.data, _method: 'PUT' });
        }
      } catch (e) {
        remaining.push(op);
      }
    }

    queueSet(remaining);
    await refreshFromServer({ silent: true });
    showToast('Synchronisation terminée.', 'success');
    updateSyncBanner();
  })();
}

document.getElementById('syncBanner')?.addEventListener('click', processQueue);

window.addEventListener('online', () => {
  document.getElementById('offlineBanner')?.classList.remove('show');
  updateSyncBanner();
  processQueue();
});
window.addEventListener('offline', () => {
  document.getElementById('offlineBanner')?.classList.add('show');
});



document.getElementById('btnApplyHistoryFilter')?.addEventListener('click', () => {
  const titleText = document.getElementById('clientHistoryTitle')?.textContent || '';
  const match = titleText.match(/\(([^)]+)\)$/);
  const clientNumber = match ? match[1] : null;

  if (!clientNumber) return;

  const client = clientsStore.find(c => String(c.client_number) === String(clientNumber));
  if (!client) return;

  // On garde le modal ouvert et on recharge juste le contenu
  loadClientHistory(client.id, client.client_number, client.name, true);
});

document.getElementById('clientHistoryModal')?.addEventListener('hidden.bs.modal', () => {
  document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
  document.body.classList.remove('modal-open');
  document.body.style.removeProperty('padding-right');
});

document.addEventListener('DOMContentLoaded', () => {
  if (!loadStore()) saveStore();
  if (PAGE.clients?.length) {
    clientsStore = PAGE.clients;
    saveStore();
  }
  renderCurrentView();
  renderStats(PAGE.stats || { total_clients: 0, clients_with_sales_day: 0, total_amount_day: 0, top_client: '—' });
  updateSyncBanner();
  if (navigator.onLine) refreshFromServer({ silent: true });
});
