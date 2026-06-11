@php
    $isAdmin = session('role') === 'admin';
    $permissions = session('permissions', []);

    $salesPerms = $salesPermissions ?? [
        'is_admin' => $isAdmin,
        'view'     => $isAdmin ? true : !empty(data_get($permissions, 'ventes.view', 1)),
        'add'      => $isAdmin ? true : !empty(data_get($permissions, 'ventes.add')),
        'edit'     => $isAdmin ? true : !empty(data_get($permissions, 'ventes.edit')),
        'delete'   => $isAdmin ? true : !empty(data_get($permissions, 'ventes.delete')),
    ];

    $canAdd = $salesPerms['add'];
    $canEdit = $salesPerms['edit'];
    $canDelete = $salesPerms['delete'];

    $salesInitial = collect($sales ?? [])->map(function ($s) {
        return [
            'id' => data_get($s, 'id'),
            'user_id' => data_get($s, 'user_id'),
            'user_name' => data_get($s, 'user.name', '—'),
            'client_number' => data_get($s, 'client_number'),
            'total_amount' => (float) data_get($s, 'total_amount', 0),
            'extra_expense_amount' => (float) data_get($s, 'extra_expense_amount', 0),
            'extra_expense_description' => data_get($s, 'extra_expense_description', ''),
            'payment_type' => data_get($s, 'payment_type'),
            'status' => data_get($s, 'status'),
            'created_at' => data_get($s, 'created_at') ? \Carbon\Carbon::parse(data_get($s, 'created_at'))->format('Y-m-d H:i') : null,
            'items' => collect(data_get($s, 'items', []))->map(function ($it) {
                return [
                    'product_id' => data_get($it, 'product_id'),
                    'product_name' => data_get($it, 'product.name', '—'),
                    'quantity' => (int) data_get($it, 'quantity', 0),
                    'price' => (float) data_get($it, 'price', 0),
                    'total' => (float) data_get($it, 'total', 0),
                    'unit_name' => data_get($it, 'unit.name', '—'),
                    'price_purchase' => (float) data_get($it, 'purchase_price', data_get($it, 'price_purchase', 0)),
                ];
            })->values(),
        ];
    })->values();

    $outgoingsSource = collect($otherOutflows ?? $outgoings ?? []);

    $outgoingsInitial = $outgoingsSource->map(function ($o) {
        return [
            'id' => data_get($o, 'id'),
            'user_id' => data_get($o, 'user_id'),
            'user_name' => data_get($o, 'user.name', data_get($o, 'user_name', '—')),
            'product_id' => data_get($o, 'product_id'),
            'product_name' => data_get($o, 'product.name', data_get($o, 'product_name', '—')),
            'product_unit_id' => data_get($o, 'product_unit_id'),
            'unit_name' => data_get($o, 'unit.name', data_get($o, 'unit_name', '—')),
            'quantity' => (int) data_get($o, 'quantity', 0),
            'reason' => data_get($o, 'reason', ''),
            'description' => data_get($o, 'description', ''),
            'created_at' => data_get($o, 'created_at')
                ? \Carbon\Carbon::parse(data_get($o, 'created_at'))->format('Y-m-d H:i')
                : null,
        ];
    })->values();
@endphp

@php
     $profileImage = session('profileImage');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Ventes - Compassion Pharmacie</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=<?= time() ?>">
  @include('partials.header')

  <style>
    .section-card{background:#fff;border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow-sm);overflow:hidden;}
    .stat-card-small{background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:14px 16px;display:flex;align-items:center;gap:12px;box-shadow:var(--shadow-sm);min-height:90px;}
    .stat-card-small:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);}
    .stat-icon-sm{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
    .stat-value-sm{font-size:1rem;font-weight:700;color:var(--text-dark);line-height:1.2;word-break:break-word;}
    .stat-label-sm{font-size:.85rem;color:var(--text-muted);line-height:1.2;word-break:break-word;}

    .tab-nav{display:flex;border-bottom:1px solid var(--border);overflow:auto;}
    .tab-nav-item{padding:12px 20px;font-size:14px;font-weight:500;color:var(--text-muted);cursor:pointer;border-bottom:2px solid transparent;white-space:nowrap;text-decoration:none;}
    .tab-nav-item:hover,.tab-nav-item.active{color:var(--primary);}
    .tab-nav-item.active{border-bottom-color:var(--primary);font-weight:600;}

    .section-header{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-light);flex-wrap:wrap;gap:10px;}
    .section-header h3{font-size:15px;font-weight:700;color:var(--text-dark);margin:0;}

    .btn-actions{background:#fff;border:1px solid var(--border);border-radius:var(--radius-sm);width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;color:var(--text-muted);transition:all .2s;}
    .btn-actions:hover{background:var(--bg-page);color:var(--primary);}
    .btn-success-new{background:var(--success);color:#fff;border:none;border-radius:var(--radius-sm);padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .2s;white-space:nowrap;}
    .btn-success-new:hover{background:#16a34a;transform:translateY(-1px);}
    .disabled-action{opacity:.45;cursor:not-allowed;pointer-events:none;}

    .search-results-box{
      position:absolute;z-index:2000;left:0;right:0;top:100%;
      background:#fff;border:1px solid #dee2e6;border-radius:12px;
      max-height:260px;overflow:auto;box-shadow:0 12px 35px rgba(0,0,0,.12);
    }
    .search-results-item{padding:10px 12px;border-bottom:1px solid #f1f5f9;cursor:pointer;}
    .search-results-item:hover{background:#f8fafc;}

    .line-item-card{border:1px solid #e5e7eb;border-radius:14px;padding:12px;background:#fff;}
    .line-item-grid{display:grid;grid-template-columns:1.5fr .8fr .8fr .8fr 40px;gap:10px;align-items:end;}

    #toastContainer{position:fixed;top:20px;right:20px;z-index:20000;display:flex;flex-direction:column;gap:10px;pointer-events:none;}
    .toast-item{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.15);padding:14px 18px;display:flex;align-items:center;gap:12px;font-size:14px;font-weight:500;min-width:280px;max-width:380px;pointer-events:all;border-left:4px solid #22c55e;}
    .toast-item.toast-error{border-left-color:#ef4444;}
    .toast-item.toast-warning{border-left-color:#f59e0b;}
    .toast-item.toast-info{border-left-color:#3b82f6;}
    .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;color:#94a3b8;font-size:16px;padding:0;}

    #offlineBanner{display:none;position:fixed;top:0;left:0;right:0;z-index:10000;background:#ef4444;color:#fff;text-align:center;padding:10px 16px;font-size:14px;font-weight:600;align-items:center;justify-content:center;gap:10px;}
    #offlineBanner.show{display:flex;}
    #syncBanner{display:none;position:fixed;top:0;left:0;right:0;z-index:10000;background:#f59e0b;color:#fff;text-align:center;padding:10px 16px;font-size:14px;font-weight:600;align-items:center;justify-content:center;gap:10px;cursor:pointer;}
    #syncBanner.show{display:flex;}

    .table-wrapper{position:relative;}
    #tableLoadingOverlay{display:none;position:absolute;inset:0;background:rgba(255,255,255,.7);z-index:10;align-items:center;justify-content:center;}
    #tableLoadingOverlay.show{display:flex;}
    .spinner-table{width:36px;height:36px;border:3px solid #e2e8f0;border-top-color:#2563eb;border-radius:50%;animation:spin .7s linear infinite;}

    .btn-spinner{width:16px;height:16px;border:2px solid #ccc;border-top:2px solid #000;border-radius:50%;display:inline-block;margin-right:8px;animation:spin .7s linear infinite;}
    .btn-disabled-overlay{pointer-events:none;opacity:.7;}

    @keyframes spin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}

    #saleModal .modal-body,
    #outflowModal .modal-body,
    #editOutgoingModal .modal-body {max-height:70vh;overflow-y:auto;}
  </style>
</head>
<body>

<input type="hidden" id="currentUserName" value="{{ $nom ?? '' }}">

<div id="offlineBanner"><i class="bi bi-wifi-off"></i> Mode hors-ligne actif — les opérations seront mises en attente.</div>
<div id="syncBanner"><i class="bi bi-arrow-repeat"></i><span id="syncBannerText">Connexion rétablie — cliquez ici pour synchroniser les opérations en attente.</span></div>
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
        <div class="page-icon" style="background:var(--success-light);color:var(--success)"><i class="bi bi-cart-check-fill"></i></div>
        <div>
          <h1>Gestion des Ventes</h1>
          <div style="font-size:12px;color:var(--text-muted);font-weight:400">Ventes, facturation et suivi journalier</div>
        </div>
      </div>
      @include('partials.profil')
    </header>

    <div class="page-content">

      <div class="section-card mb-3">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0 8px 0 0;flex-wrap:wrap;gap:10px;">
          <div class="tab-nav" style="border-bottom:none;flex:1">
            <a class="tab-nav-item active" href="{{ route('ventes') }}">Ventes</a>
            <a class="tab-nav-item" href="{{ route('clients') }}">Clients</a>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <button class="btn-success-new {{ $canAdd ? '' : 'disabled-action' }}" id="btnOpenSale" type="button">
              <i class="bi bi-plus-lg"></i> Nouvelle Vente
            </button>
            <button class="btn-success-new {{ $canAdd ? '' : 'disabled-action' }}" id="btnOpenOutflow" type="button">
              <i class="bi bi-box-arrow-right"></i> Autre sortie
            </button>
            <button class="btn-success-new" id="btnPrintDailyReport" type="button">
              <i class="bi bi-printer"></i> Rapport journalier
            </button>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff"><i class="bi bi-receipt"></i></div>
            <div>
              <div class="stat-value-sm" id="statTotalSales">{{ number_format($stats['total_sales'] ?? 0, 0, ',', ' ') }}</div>
              <div class="stat-label-sm">Ventes du jour</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff"><i class="bi bi-cash-coin"></i></div>
            <div>
              <div class="stat-value-sm" id="statTotalAmount">{{ number_format($stats['total_amount'] ?? 0, 0, ',', ' ') }} CDF</div>
              <div class="stat-label-sm">Montant total</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#a855f7,#7c3aed);color:#fff"><i class="bi bi-graph-up-arrow"></i></div>
            <div>
              <div class="stat-value-sm" id="statBenefitDay">{{ number_format($stats['benefit_day'] ?? 0, 0, ',', ' ') }} CDF</div>
              <div class="stat-label-sm">Bénéfice du jour</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff"><i class="bi bi-trophy"></i></div>
            <div>
              <div class="stat-value-sm" id="statTopProduct">{{ $stats['top_product'] ?? '—' }}</div>
              <div class="stat-label-sm">Produit le plus vendu</div>
            </div>
          </div>
        </div>
      </div>

      <div class="section-card p-3 mb-3">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-4">
            <label class="form-label">Recherche</label>
            <div class="search-box">
              <i class="bi bi-search search-icon"></i>
              <input type="text" class="form-control" placeholder="Rechercher une vente, client, produit..." id="salesSearch">
            </div>
          </div>

          @if($isAdmin)
          <div class="col-12 col-md-3">
            <label class="form-label">Utilisateur</label>
            <select class="form-select" id="filterUser">
              <option value="all">Tous les utilisateurs</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
          @endif

          <div class="col-12 col-md-2">
            <label class="form-label">Paiement</label>
            <select class="form-select" id="filterPayment">
              <option value="all">Tous</option>
              <option value="cash">Cash</option>
              <option value="mobile_money">Mobile Money</option>
              <option value="partenaire">Partenaire</option>
            </select>
          </div>

          <div class="col-12 col-md-3 d-flex gap-2">
            <button class="btn btn-primary w-100" id="btnApplyFilters" type="button"><i class="bi bi-search"></i> Rechercher</button>
          </div>
        </div>
      </div>

      <div class="section-card">
        <div class="section-header">
          <h3>Liste des ventes du jour</h3>
        </div>

        <div class="table-wrapper">
          <div id="tableLoadingOverlay"><div class="spinner-table"></div></div>
          <div class="table-responsive">
            <table class="data-table" id="salesTable">
              <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Vendeur</th>
                    <th>Client</th>
                    <th>Produits</th>
                    <th>Dépense supplémentaire</th>
                    <th>Total</th>
                    <th>Paiement</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
              </thead>
              <tbody id="salesTableBody">
                @forelse($sales as $sale)
                  <tr data-id="{{ $sale->id }}">
                    <td>{{ $sale->id }}</td>
                    <td>{{ $sale->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $sale->user?->name ?? '—' }}</td>
                    <td>{{ $sale->client_number ?? '—' }}</td>
                    <td>
                      <div class="d-flex flex-column gap-1">
                        @foreach($sale->items as $item)
                          <span class="badge bg-light text-dark border">{{ $item->product?->name }} x{{ $item->quantity }}</span>
                        @endforeach
                      </div>
                    </td>
                    <td><strong>{{ number_format($sale->extra_expense_amount ?? 0, 0, ',', ' ') }} CDF</strong></td>
                    <td><strong>{{ number_format($sale->total_amount, 0, ',', ' ') }} CDF</strong></td>
                    <td>{{ $sale->payment_type }}</td>
                    <td><span class="badge {{ $sale->status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $sale->status }}</span></td>
                    <td>
                      <div class="d-flex gap-1">
                        <button class="btn-actions btn-view-invoice" data-sale-id="{{ $sale->id }}" title="Facture">🧾</button>
                        <button class="btn-actions btn-delete-sale {{ $canDelete ? '' : 'disabled-action' }}" data-sale-id="{{ $sale->id }}" title="Supprimer">🗑</button>
                      </div>
                    </td>
                  </tr>
                @empty
                <tr><td colspan="10" class="text-center py-4">Aucune vente pour aujourd'hui.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--border-light);flex-wrap:wrap;gap:8px">
          <div id="salesFooterCount" style="font-size:13px;color:var(--text-muted)">Affichage {{ $sales->count() }} vente(s)</div>
        </div>
        <div class="d-flex align-items-center justify-content-between px-3 pb-3 pt-2 flex-wrap gap-2">
            <div id="salesFooterCount" style="font-size:13px;color:var(--text-muted)">
                Affichage {{ $sales->count() }} vente(s)
            </div>
            <div id="salesPagination" class="d-flex gap-2 flex-wrap"></div>
        </div>
      </div>

      <div class="section-card mt-3">
        <div class="section-header">
          <h3>Autres sorties du jour</h3>
        </div>
        <div class="table-wrapper">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Date</th>
                  <th>Utilisateur</th>
                  <th>Produit</th>
                  <th>Unité</th>
                  <th>Qté</th>
                  <th>Raison</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="outflowTableBodyInline"></tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

{{-- MODAL VENTE --}}
<div class="modal fade" id="saleModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form id="saleForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Nouvelle Vente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6 position-relative">
              <label class="form-label">Client (optionnel)</label>
              <input type="text" class="form-control" id="clientSearch" placeholder="Rechercher client par nom, numéro, téléphone...">
              <input type="hidden" id="clientNumber">
              <div id="clientSearchResults" class="search-results-box d-none"></div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Type de paiement</label>
              <select id="paymentType" class="form-select" required>
                <option value="cash">Cash</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="partenaire">Partenaire</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Statut</label>
              <select id="saleStatus" class="form-select" required>
                <option value="paid">Payée</option>
              </select>
            </div>

            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <strong>Produits vendus</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddSaleLine">+ Ajouter un produit</button>
              </div>
              <div id="saleLines" class="d-flex flex-column gap-3"></div>
            </div>

            <div class="col-12">
              <div class="alert alert-info mb-0">
                Les quantités ne peuvent pas dépasser le stock disponible.
              </div>
            </div>

            <div class="col-12">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Dépense supplémentaire (CDF)</label>
                  <input type="number" class="form-control" id="extraExpenseAmount" placeholder="0" min="0" >
                </div>
                <div class="col-md-8">
                  <label class="form-label">Motif de la dépense supplémentaire</label>
                  <input type="text" class="form-control" id="extraExpenseDescription" placeholder="Ex: frais de livraison">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <div>
            <strong>Total :</strong> <span id="saleGrandTotal">0 CDF</span>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-success" id="saleSubmitBtn">Enregistrer la vente</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL AUTRE SORTIE --}}
<div class="modal fade" id="outflowModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form id="outflowForm">
        @csrf
        <input type="hidden" id="outflowId" name="id">
        <input type="hidden" id="outflowMethod" value="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="outflowModalTitle">Autre sortie</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6 position-relative">
              <label class="form-label">Produit</label>
              <input type="text" class="form-control" id="outflowProductSearch" placeholder="Rechercher un produit disponible...">
              <input type="hidden" id="outflowProductId" required>
              <div id="outflowProductResults" class="search-results-box d-none"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Unité</label>
              <select id="outflowUnitId" class="form-select" required>
                <option value="">Choisir d'abord un produit</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Quantité</label>
              <input type="number" id="outflowQuantity" class="form-control" min="1" required>
            </div>
            <div class="col-md-6">
            <label class="form-label">Stock disponible</label>
            <input type="text" id="outflowStockView" class="form-control" readonly value="">
            </div>

            <div class="col-md-8">
              <label class="form-label">Raison / Description</label>
              <input type="text" id="outflowReason" class="form-control" placeholder="Ex: cassé, utilisé en interne..." required>
            </div>
          </div>

          <div class="mt-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <strong>Liste des autres sorties</strong>
              <div class="d-flex gap-2 flex-wrap">
                <div class="position-relative">
                  <input type="text" class="form-control" id="outflowSearch" placeholder="Recherche..." style="min-width:220px">
                </div>
                @if($isAdmin)
                <select class="form-select" id="filterOutflowUser" style="min-width:220px">
                  <option value="all">Tous les utilisateurs</option>
                  @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                  @endforeach
                </select>
                @endif
              </div>
            </div>
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Produit</th>
                    <th>Unité</th>
                    <th>Qté</th>
                    <th>Raison</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="outflowTableBody"></tbody>
              </table>
            </div>
            <div id="outflowFooterCount" class="mt-2" style="font-size:13px;color:var(--text-muted)"></div>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <div><strong>Total sorties :</strong> <span id="outflowTotalCount">0</span></div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
            <button type="submit" class="btn btn-success" id="outflowSubmitBtn">Enregistrer la sortie</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL MODIFIER AUTRE SORTIE --}}
<div class="modal fade" id="editOutgoingModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editOutgoingForm">
        @csrf
        <input type="hidden" id="editOutgoingId">
        <div class="modal-header">
          <h5 class="modal-title">Modifier une autre sortie</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Raison</label>
              <input type="text" class="form-control" id="editOutgoingReason" required>
            </div>
            <div class="col-md-12">
              <label class="form-label">Description</label>
              <input type="text" class="form-control" id="editOutgoingDescription">
            </div>
            <div class="col-md-4">
              <label class="form-label">Quantité</label>
              <input type="number" class="form-control" id="editOutgoingQuantity" min="1" required>
            </div>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL FACTURE --}}
<div class="modal fade" id="invoiceModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Facture</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="invoiceBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
        <button type="button" class="btn btn-primary" id="btnPrintInvoice">Imprimer</button>
        <button type="button" class="btn btn-success" id="btnDownloadInvoicePdf">
          <i class="bi bi-file-earmark-pdf"></i> PDF
        </button>
      </div>
    </div>
  </div>
</div>

@include('partials.profil1')

<script>
window.SALES_PAGE_INITIAL = {
  sales: @json($salesInitial),
  products: @json($products),
  clients: @json($clients),
  outgoings: @json($outgoingsInitial),
  stats: @json($stats),
  isAdmin: @json($isAdmin),
  canAdd: @json($canAdd),
  canEdit: @json($canEdit),
  canDelete: @json($canDelete),
  users: @json($users),
  userId: @json(session('user_id')),
  currentUserName: @json($nom ?? '')
};
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=<?= time() ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="{{ asset('js/ventes.js') }}?v=<?= time() ?>"></script>


<script>
document.addEventListener('hidden.bs.modal', function (event) {
  const modal = event.target;
  const form = modal.querySelector('form');

  if (form) form.reset();

  modal.querySelectorAll('input[type="hidden"]').forEach(input => {
    if (input.id === 'outflowMethod') {
      input.value = 'POST';
      return;
    }
    if (input.id === 'editOutgoingId') return;
    input.value = '';
  });

  modal.querySelectorAll('.search-results-box').forEach(box => {
    box.classList.add('d-none');
    box.innerHTML = '';
  });

  if (modal.id === 'saleModal') {
    const saleLines = document.getElementById('saleLines');
    if (saleLines) saleLines.innerHTML = '';
  }

  if (modal.id === 'outflowModal') {
    const stockView = document.getElementById('outflowStockView');
    if (stockView) stockView.value = '';
  }

  if (modal.id === 'invoiceModal') {
    const invoiceBody = document.getElementById('invoiceBody');
    if (invoiceBody) invoiceBody.innerHTML = '';
  }
});
</script>
</body>
</html>
