@php
    $isAdmin = session('role') === 'admin';
    $permissions = session('permissions', []);
    $clientsPerms = $clientsPermissions ?? [
        'is_admin' => $isAdmin,
        'view'     => $isAdmin ? true : !empty(data_get($permissions, 'clients.view', 1)),
        'add'      => $isAdmin ? true : !empty(data_get($permissions, 'clients.add')),
        'edit'     => $isAdmin ? true : !empty(data_get($permissions, 'clients.edit')),
        'delete'   => false,
    ];

    $canAdd = $clientsPerms['add'];
    $canEdit = $clientsPerms['edit'];
    $canDelete = false;
@endphp

@php
     $profileImage = session('profile_image_url');
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Clients - Compassion Pharmacie</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=<?= time() ?>">
  @include('partials.header')

  <style>
    .section-card{background:white;border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow-sm);overflow:hidden;}
    .stat-card-small{background:white;border-radius:var(--radius);border:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;gap:14px;box-shadow:var(--shadow-sm);transition:all 0.2s;}
    .stat-card-small:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);}
    .stat-icon-sm{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
    .stat-value-sm{font-size:22px;font-weight:700;color:var(--text-dark);line-height:1.1;}
    .stat-label-sm{font-size:12px;color:var(--text-muted);margin-top:1px;}
    .tab-nav{display:flex;border-bottom:1px solid var(--border);padding:0;overflow:auto;}
    .tab-nav-item{padding:12px 20px;font-size:14px;font-weight:500;color:var(--text-muted);cursor:pointer;border-bottom:2px solid transparent;transition:all 0.2s;white-space:nowrap;text-decoration:none;}
    .tab-nav-item:hover{color:var(--primary);}
    .tab-nav-item.active{color:var(--primary);border-bottom-color:var(--primary);font-weight:600;}
    .section-header{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-light);flex-wrap:wrap;gap:10px;}
    .section-header h3{font-size:15px;font-weight:700;color:var(--text-dark);margin:0;}
    .btn-actions{background:white;border:1px solid var(--border);border-radius:var(--radius-sm);width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;color:var(--text-muted);transition:all 0.2s;}
    .btn-actions:hover{background:var(--bg-page);color:var(--primary);}
    .btn-success-new{background:var(--success);color:white;border:none;border-radius:var(--radius-sm);padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all 0.2s;white-space:nowrap;}
    .btn-success-new:hover{background:#16a34a;transform:translateY(-1px);}
    .search-results-box{position:absolute; z-index:2000; left:0; right:0; top:100%; background:#fff; border:1px solid #dee2e6; border-radius:12px; max-height:260px; overflow:auto; box-shadow:0 12px 35px rgba(0,0,0,.12);}
    .search-results-item{padding:10px 12px;border-bottom:1px solid #f1f5f9;cursor:pointer;}
    .search-results-item:hover{background:#f8fafc;}
    .disabled-action{opacity:.45;cursor:not-allowed;}
    #toastContainer{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;}
    .toast-item{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.15);padding:14px 18px;display:flex;align-items:center;gap:12px;font-size:14px;font-weight:500;min-width:280px;max-width:380px;pointer-events:all;animation:slideIn 0.3s ease;border-left:4px solid #22c55e;}
    .toast-item.toast-error{border-left-color:#ef4444;}
    .toast-item.toast-warning{border-left-color:#f59e0b;}
    .toast-item.toast-info{border-left-color:#3b82f6;}
    .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;color:#94a3b8;font-size:16px;padding:0;}
    #offlineBanner{display:none;position:fixed;top:0;left:0;right:0;z-index:10000;background:#ef4444;color:white;text-align:center;padding:10px 16px;font-size:14px;font-weight:600;align-items:center;justify-content:center;gap:10px;}
    #offlineBanner.show{display:flex;}
    #syncBanner{display:none;position:fixed;top:0;left:0;right:0;z-index:10000;background:#f59e0b;color:white;text-align:center;padding:10px 16px;font-size:14px;font-weight:600;align-items:center;justify-content:center;gap:10px;cursor:pointer;}
    #syncBanner.show{display:flex;}
    .table-wrapper{position:relative;}
    #tableLoadingOverlay{display:none;position:absolute;inset:0;background:rgba(255,255,255,0.7);z-index:10;align-items:center;justify-content:center;}
    #tableLoadingOverlay.show{display:flex;}
    .spinner-table{width:36px;height:36px;border:3px solid #e2e8f0;border-top-color:#2563eb;border-radius:50%;animation:spin 0.7s linear infinite;}
  </style>
  <style>
        .stat-card-small{
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(0,0,0,.06);
        height: auto;
        min-height: 90px;
        width: 100%;
        }

        .stat-card-small > div:last-child{
        min-width: 0;
        flex: 1;
        }

        .stat-value-sm,
        .stat-label-sm{
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
        line-height: 1.2;
        }

        .stat-value-sm{
        font-size: 1rem;
        font-weight: 700;
        }

        .stat-label-sm{
        font-size: .85rem;
        opacity: .8;
        }
      </style>
</head>
<body>

<div id="offlineBanner">
  <i class="bi bi-wifi-off"></i>
  Mode hors-ligne actif — les modifications clients seront mises en attente.
</div>
<div id="syncBanner">
  <i class="bi bi-arrow-repeat"></i>
  <span id="syncBannerText">Connexion rétablie — cliquez ici pour synchroniser les clients en attente.</span>
</div>
<div id="toastContainer"></div>

<div class="app-layout">
  <aside class="sidebar" id="sidebar">
    <div class="login-logo">
      <img src="{{ asset('Imgs/Logos/logo_full.png') }}" alt="" width="200" height="90">
    </div>
    <nav class="sidebar-nav">@include('partials.menu')</nav>
    <div class="sidebar-footer">
      <a href="{{ route('deconnexion') }}" class="logout-btn">
        <span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span>Déconnexion
      </a>
    </div>
  </aside>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="main-content">
    <header class="top-header">
      <button class="mobile-toggle" id="mobileToggle"><i class="bi bi-list"></i></button>
      <div class="header-title">
        <div class="page-icon" style="background:var(--success-light);color:var(--success)">
          <i class="bi bi-people-fill"></i>
        </div>
        <div>
          <h1>Gestion des Clients</h1>
          <div style="font-size:12px;color:var(--text-muted);font-weight:400">Clients, historique d'achats et suivi</div>
        </div>
      </div>
      @include('partials.profil')
    </header>

    <div class="page-content">

      <div class="section-card mb-3">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0 8px 0 0;flex-wrap:wrap;">
          <div class="tab-nav" style="border-bottom:none;flex:1">
            <a class="tab-nav-item" href="{{ route('ventes') }}">Ventes</a>
            <a class="tab-nav-item active" href="{{ route('clients') }}">Clients</a>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <button class="btn-success-new {{ $canAdd ? '' : 'disabled-action' }}" id="btnOpenAddClient" type="button">
              <i class="bi bi-plus-lg"></i> Nouveau Client
            </button>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:white"><i class="bi bi-people-fill"></i></div>
            <div>
              <div class="stat-value-sm" id="statTotalClients">{{ number_format($stats['total_clients'] ?? 0, 0, ',', ' ') }}</div>
              <div class="stat-label-sm">Clients Total</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:white"><i class="bi bi-currency-dollar"></i></div>
            <div>
              <div class="stat-value-sm" id="statClientsWithSales">{{ number_format($stats['clients_with_sales_day'] ?? 0, 0, ',', ' ') }}</div>
              <div class="stat-label-sm">Clients actifs</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:white"><i class="bi bi-cash-coin"></i></div>
            <div>
              <div class="stat-value-sm" id="statTotalAmountDay">{{ number_format($stats['total_amount_day'] ?? 0, 0, ',', ' ') }} CDF</div>
              <div class="stat-label-sm">Montant vendu aujourd'hui</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:white"><i class="bi bi-trophy"></i></div>
            <div>
              <div class="stat-value-sm" id="statTopClient">{{ $stats['top_client'] ?? '—' }}</div>
              <div class="stat-label-sm">Client le plus actif</div>
            </div>
          </div>
        </div>
      </div>

      <div class="section-card p-3 mb-3">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-5">
            <div class="search-box position-relative">
              <i class="bi bi-search search-icon"></i>
              <input type="text" class="form-input" placeholder="Rechercher un client..." id="clientsSearch">
            </div>
          </div>
          <div class="col-12 col-md-3 d-flex gap-2">
            <button class="btn-primary-custom" style="padding:9px 14px;font-size:13px" id="btnApplyFilters">
              <i class="bi bi-search"></i> Rechercher
            </button>
          </div>
        </div>
      </div>

      <div class="section-card">
        <div class="section-header">
          <h3>Liste des clients</h3>
        </div>

        <div class="table-wrapper">
          <div id="tableLoadingOverlay">
            <div class="spinner-table"></div>
          </div>

          <div class="table-responsive">
            <table class="data-table" id="clientsTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Numéro client</th>
                  <th>Nom</th>
                  <th>Téléphone</th>
                  <th>Adresse</th>
                  <th>Total achats</th>
                  <th>Dernier achat</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="clientsTableBody">
                @forelse($clients as $client)
                  <tr data-id="{{ $client['id'] }}" data-search="{{ strtolower($client['client_number'].' '.$client['name'].' '.$client['phone'].' '.$client['address']) }}">
                    <td>{{ $client['id'] }}</td>
                    <td>{{ $client['client_number'] }}</td>
                    <td>{{ $client['name'] }}</td>
                    <td>{{ $client['phone'] ?? '—' }}</td>
                    <td>{{ $client['address'] ?? '—' }}</td>
                    <td><strong>{{ number_format($client['total_spent'] ?? 0, 0, ',', ' ') }} CDF</strong></td>
                    <td>{{ $client['last_purchase_at'] ?? '—' }}</td>
                    <td>
                      <div class="d-flex gap-1">
                        <button class="btn-actions btn-client-history" data-client-id="{{ $client['id'] }}" data-client-number="{{ $client['client_number'] }}" data-client-name="{{ e($client['name']) }}" title="Historique">⌛</button>
                        <button class="btn-actions btn-edit-client {{ $canEdit ? '' : 'disabled-action' }}" data-can-edit="{{ $canEdit ? 1 : 0 }}" data-id="{{ $client['id'] }}" data-client-number="{{ $client['client_number'] }}" data-name="{{ e($client['name']) }}" data-phone="{{ e($client['phone'] ?? '') }}" data-address="{{ e($client['address'] ?? '') }}" title="Modifier">✎</button>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="text-center py-4">Aucun client trouvé.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div id="clientsFooterCount" style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--border-light);flex-wrap:wrap;gap:8px">
          <div style="font-size:13px;color:var(--text-muted)">
            Affichage {{ count($clients) }} client(s)
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

{{-- MODAL AJOUT CLIENT --}}
<div class="modal fade" id="addClientModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addClientForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Nouveau Client</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Numéro client</label>
              <input type="text" name="client_number" id="addClientNumber" class="form-control" readonly>
            </div>
            <div class="col-md-8">
              <label class="form-label">Nom</label>
              <input type="text" name="name" id="addClientName" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Téléphone</label>
              <input type="text" name="phone" id="addClientPhone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Adresse</label>
              <input type="text" name="address" id="addClientAddress" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success" id="addClientSubmitBtn">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL MODIFIER CLIENT --}}
<div class="modal fade" id="editClientModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editClientForm">
        @csrf
        <input type="hidden" name="_method" value="PUT">
        <input type="hidden" id="editClientId">
        <div class="modal-header">
          <h5 class="modal-title">Modifier Client</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Numéro client</label>
              <input type="text" name="client_number" id="editClientNumber" class="form-control" readonly>
            </div>
            <div class="col-md-8">
              <label class="form-label">Nom</label>
              <input type="text" name="name" id="editClientName" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Téléphone</label>
              <input type="text" name="phone" id="editClientPhone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Adresse</label>
              <input type="text" name="address" id="editClientAddress" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary" id="editClientSubmitBtn">Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL HISTORIQUE --}}
<div class="modal fade" id="clientHistoryModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="clientHistoryTitle">Historique d'achat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <input type="date" class="form-control" id="historyFrom">
          </div>
          <div class="col-md-3">
            <input type="date" class="form-control" id="historyTo">
          </div>
          <div class="col-md-3">
            <button class="btn btn-primary w-100" id="btnApplyHistoryFilter" type="button">Filtrer</button>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <div class="stat-card-small">
              <div class="stat-icon-sm" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:white"><i class="bi bi-receipt"></i></div>
              <div>
                <div class="stat-value-sm" id="historyTotalSales">0</div>
                <div class="stat-label-sm">Ventes</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-card-small">
              <div class="stat-icon-sm" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:white"><i class="bi bi-cash-coin"></i></div>
              <div>
                <div class="stat-value-sm" id="historyTotalAmount">0 CDF</div>
                <div class="stat-label-sm">Montant total</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-card-small">
              <div class="stat-icon-sm" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:white"><i class="bi bi-box"></i></div>
              <div>
                <div class="stat-value-sm" id="historyTotalItems">0</div>
                <div class="stat-label-sm">Produits achetés</div>
              </div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>Vendeur</th>
                <th>Paiement</th>
                <th>Produits</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody id="historyBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@include('partials.profil1')
<script>
window.CLIENTS_PAGE_INITIAL = {
  clients: @json($clients),
  stats: @json($stats),
  canAdd: @json($canAdd),
  canEdit: @json($canEdit),
  canDelete: false,
  isAdmin: @json($clientsPerms['is_admin'] ?? $isAdmin),
};
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=<?= time() ?>"></script>
<script src="{{ asset('js/clients.js') }}?v=<?= time() ?>"></script>
</body>
</html>
