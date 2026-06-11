@php
    $isAdmin = session('role') === 'admin';
    $permissions = session('permissions', []);
    $stockPerms = $stockPermissions ?? [
        'is_admin' => $isAdmin,
        'view'     => $isAdmin ? true : !empty(data_get($permissions, 'stock.view', 1)),
        'add'      => $isAdmin ? true : !empty(data_get($permissions, 'stock.add')),
        'edit'     => $isAdmin ? true : !empty(data_get($permissions, 'stock.edit')),
        'delete'   => $isAdmin ? true : !empty(data_get($permissions, 'stock.delete')),
    ];

    $canView = $stockPerms['view'];
    $canAdd = $stockPerms['add'];
    $canEdit = $stockPerms['edit'];
    $canDelete = $stockPerms['delete'];
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
  <title>Gestion de Stock - Compassion Pharmacie</title>

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

    .product-badge{display:inline-block;padding:3px 10px;border-radius:50px;font-size:11px;font-weight:500;white-space:nowrap;}
    .badge-analgesique{background:#dbeafe;color:#1d4ed8;}
    .badge-status{display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;font-size:11px;font-weight:700;}
    .badge-en-stock{background:#dcfce7;color:#166534;}
    .badge-stock-faible{background:#fef3c7;color:#92400e;}
    .badge-perime{background:#fee2e2;color:#991b1b;}

    .product-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
    .price-up{font-size:11px;color:var(--success);display:block;}
    .btn-actions{background:white;border:1px solid var(--border);border-radius:var(--radius-sm);width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;color:var(--text-muted);transition:all 0.2s;}
    .btn-actions:hover{background:var(--bg-page);color:var(--primary);}
    .btn-icon-sm{background:white;border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 14px;font-size:13px;font-weight:500;color:var(--text-muted);cursor:pointer;display:flex;align-items:center;gap:6px;transition:all 0.2s;}
    .btn-icon-sm:hover{border-color:var(--primary);color:var(--primary);}
    .btn-success-new{background:var(--success);color:white;border:none;border-radius:var(--radius-sm);padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all 0.2s;white-space:nowrap;}
    .btn-success-new:hover{background:#16a34a;transform:translateY(-1px);}
    .thumb-mini{width:36px;height:36px;border-radius:10px;object-fit:cover;border:1px solid var(--border);background:#f8fafc;}

    .modal-content{border-radius:16px;}
    .modal-dialog-scrollable .modal-body{overflow-y:auto;max-height:calc(100vh - 200px);}
    .loading-field{position:relative;pointer-events:none;opacity:0.75;}
    .loading-field::after{content:'';position:absolute;top:50%;right:12px;width:16px;height:16px;margin-top:-8px;border:2px solid #cbd5e1;border-top-color:#2563eb;border-radius:50%;animation:spin 0.7s linear infinite;}
    @keyframes spin{to{transform:rotate(360deg);}}

    .lot-expired-row{background:#fff1f2 !important;}
    .lot-expired-row td{color:#9f1239 !important;}
    .lot-expired-badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;background:#fecdd3;color:#9f1239;}

    #toastContainer{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;}
    .toast-item{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.15);padding:14px 18px;display:flex;align-items:center;gap:12px;font-size:14px;font-weight:500;min-width:280px;max-width:380px;pointer-events:all;animation:slideIn 0.3s ease;border-left:4px solid #22c55e;}
    .toast-item.toast-error{border-left-color:#ef4444;}
    .toast-item.toast-warning{border-left-color:#f59e0b;}
    .toast-item.toast-info{border-left-color:#3b82f6;}
    .toast-icon{font-size:20px;flex-shrink:0;}
    .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;color:#94a3b8;font-size:16px;padding:0;}
    @keyframes slideIn{from{transform:translateX(120%);opacity:0;}to{transform:translateX(0);opacity:1;}}
    @keyframes slideOut{from{transform:translateX(0);opacity:1;}to{transform:translateX(120%);opacity:0;}}

    #offlineBanner{display:none;position:fixed;top:0;left:0;right:0;z-index:10000;background:#ef4444;color:white;text-align:center;padding:10px 16px;font-size:14px;font-weight:600;align-items:center;justify-content:center;gap:10px;}
    #offlineBanner.show{display:flex;}
    #syncBanner{display:none;position:fixed;top:0;left:0;right:0;z-index:10000;background:#f59e0b;color:white;text-align:center;padding:10px 16px;font-size:14px;font-weight:600;align-items:center;justify-content:center;gap:10px;cursor:pointer;}
    #syncBanner.show{display:flex;}

    .btn-spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.4);border-top-color:white;border-radius:50%;animation:spin 0.7s linear infinite;flex-shrink:0;}
    .btn-disabled-overlay{pointer-events:none;opacity:0.7;}

    #tableLoadingOverlay{display:none;position:absolute;inset:0;background:rgba(255,255,255,0.7);z-index:10;align-items:center;justify-content:center;}
    #tableLoadingOverlay.show{display:flex;}
    .table-wrapper{position:relative;}
    .spinner-table{width:36px;height:36px;border:3px solid #e2e8f0;border-top-color:#2563eb;border-radius:50%;animation:spin 0.7s linear infinite;}

    .search-results-box{
      position:absolute; z-index:2000; left:0; right:0; top:100%;
      background:#fff; border:1px solid #dee2e6; border-radius:12px;
      max-height:260px; overflow:auto; box-shadow:0 12px 35px rgba(0,0,0,.12);
    }
    .search-results-item{
      padding:10px 12px; border-bottom:1px solid #f1f5f9; cursor:pointer;
    }
    .search-results-item:hover{background:#f8fafc;}
    .disabled-action{opacity:.45;cursor:not-allowed;}


  .img-preview-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(110px,1fr));
    gap:12px;
    margin-top:12px;
  }
  .img-preview-card{
    position:relative;
    border:1px solid #e5e7eb;
    border-radius:14px;
    overflow:hidden;
    background:#fff;
    box-shadow:0 4px 14px rgba(0,0,0,.06);
  }
  .img-preview-card img{
    width:100%;
    height:100px;
    object-fit:cover;
    display:block;
  }
  .img-preview-info{
    padding:8px 10px;
    font-size:12px;
    color:#475569;
    line-height:1.2;
    min-height:42px;
  }
  .img-preview-remove{
    position:absolute;
    top:8px;
    right:8px;
    width:26px;
    height:26px;
    border:none;
    border-radius:999px;
    background:rgba(239,68,68,.95);
    color:#fff;
    font-weight:700;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    box-shadow:0 2px 8px rgba(0,0,0,.18);
  }
  .img-preview-remove:hover{
    background:rgba(220,38,38,1);
  }

  #syncLoader {
    position: fixed;
    inset: 0;
    z-index: 99999;
}

.sync-loader-backdrop {
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.85);

    display: flex;
    justify-content: center;
    align-items: center;

    backdrop-filter: blur(3px);
}

.sync-loader-backdrop .card {
    min-width: 320px;
    border-radius: 16px;
}
.badge-almost-expired{background:#ffedd5;color:#9a3412;}

  </style>
</head>
<body>
<div id="syncLoader" class="d-none">
    <div class="sync-loader-backdrop">
        <div class="card shadow border-0 p-4 text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>

            <h6 class="mb-1">Synchronisation des données</h6>
            <small class="text-muted" id="syncLoaderText">
                Synchronisation des données en cours...
            </small>
        </div>
    </div>
</div>

<div id="offlineBanner">
  <i class="bi bi-wifi-off"></i>
  Mode hors-ligne actif — vos modifications sont sauvegardées localement et seront synchronisées à la reconnexion.
</div>
<div id="syncBanner">
  <i class="bi bi-arrow-repeat"></i>
  <span id="syncBannerText">Connexion rétablie — cliquez ici pour synchroniser les données en attente.</span>
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
          <i class="bi bi-box-seam-fill"></i>
        </div>
        <div>
          <h1>Gestion de Stock</h1>
          <div style="font-size:12px;color:var(--text-muted);font-weight:400">Gérez votre inventaire de produits pharmaceutiques</div>
        </div>
      </div>
      @include('partials.profil')
    </header>

    <div class="page-content">

      <div class="section-card mb-3">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0 8px 0 0;flex-wrap:wrap;">
          <div class="tab-nav" style="border-bottom:none;flex:1">
            <a class="tab-nav-item active" href="{{ route('stock') }}">Inventaire</a>
            <a class="tab-nav-item" href="/ventes">Sorties (Ventes)</a>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <button class="btn-success-new {{ $canAdd ? '' : 'disabled-action' }}" id="btnOpenAddProduct" type="button">
              <i class="bi bi-plus-lg"></i> Nouveau Produit
            </button>
            <button class="btn-success-new {{ $canAdd ? '' : 'disabled-action' }}" id="btnOpenReception" type="button">
              <i class="bi bi-box-arrow-in-down"></i> Nouvelle Réception
            </button>
          </div>
        </div>
      </div>

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

      <div class="row g-3 mb-3">
        <div class="col-6 col-lg-4">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:white">
              <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
              <div class="stat-value-sm" id="statLowStock">{{ number_format($stats['low_stock'], 0, ',', ' ') }}</div>
              <div class="stat-label-sm">Stock Faible</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
        <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#f97316,#ea580c);color:white">
            <i class="bi bi-clock-history"></i>
            </div>
            <div>
            <div class="stat-value-sm" id="statAlmostExpired">{{ number_format($stats['almost_expired'] ?? 0, 0, ',', ' ') }}</div>
            <div class="stat-label-sm">Presque périmés</div>
            </div>
         </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:white">
              <i class="bi bi-hourglass-bottom"></i>
            </div>
            <div>
              <div class="stat-value-sm" id="statExpired">{{ number_format($stats['expired'], 0, ',', ' ') }}</div>
              <div class="stat-label-sm">Périmés</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:white">
              <i class="bi bi-box-seam-fill"></i>
            </div>
            <div>
              <div class="stat-value-sm" id="statTotalRefs">{{ number_format($stats['total_references'], 0, ',', ' ') }}</div>
              <div class="stat-label-sm">Références Total</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:white">
              <i class="bi bi-currency-euro"></i>
            </div>
            <div>
              <div class="stat-value-sm" id="statStockValue">{{ number_format($stats['stock_value'], 0, ',', ' ') }} CDF</div>
              <div class="stat-label-sm">Valeur du Stock</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="stat-card-small">
            <div class="stat-icon-sm" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:white">
              <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div>
              <div class="stat-value-sm" id="statTurnoverTotal">{{ number_format($stats['turnover_total'] ?? 0, 0, ',', ' ') }} CDF</div>
              <div class="stat-label-sm">Chiffre d'affaires</div>
            </div>
          </div>
        </div>
      </div>

      <div class="section-card p-3 mb-3">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-4">
            <div class="search-box">
              <i class="bi bi-search search-icon"></i>
              <input type="text" class="form-input" placeholder="Rechercher un produit, code-barres..." id="stockSearch">
            </div>
          </div>
          <div class="col-6 col-md-2">
            <label style="font-size:12px;color:var(--text-muted);margin-bottom:4px;display:block">Catégorie</label>
            <select class="filter-select w-100" id="filterCategory">
              <option value="all">Toutes les catégories</option>
              @foreach($categories as $category)
                <option value="{{ $category->name }}">{{ $category->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label style="font-size:12px;color:var(--text-muted);margin-bottom:4px;display:block">Statut</label>
            <select class="filter-select w-100" id="filterStatus">
              <option value="all">Tous les statuts</option>
              <option value="ok">En Stock</option>
              <option value="low">Stock Faible</option>
              <option value="almost_expired">Presque périmé</option>
              <option value="expired">Périmé</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label style="font-size:12px;color:var(--text-muted);margin-bottom:4px;display:block">Fournisseur</label>
            <select class="filter-select w-100" id="filterProvider">
              <option value="all">Tous les fournisseurs</option>
              @foreach($providers as $provider)
                <option value="{{ $provider->name }}">{{ $provider->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2 d-flex gap-2">
            <button class="btn-primary-custom" style="padding:9px 14px;font-size:13px" id="btnApplyFilters">
              <i class="bi bi-search"></i> Rechercher
            </button>
          </div>
        </div>
      </div>

      <div class="section-card mb-3 p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <div style="font-weight:700;font-size:15px">Marge Potentielle</div>
            <div style="font-size:12px;color:var(--text-muted)">Bénéfice théorique global calculé sur les quantités disponibles</div>
          </div>
          <div id="statBenefitTotal" style="font-size:28px;font-weight:800;color:#16a34a">
            {{ number_format($stats['benefit_total'], 0, ',', ' ') }} CDF
          </div>
        </div>
      </div>

      <div class="section-card">
        <div class="section-header">
          <h3>
            Liste des Produits
            <span id="productsCount" style="color:var(--text-muted);font-weight:400;font-size:13px">
              ({{ number_format($stats['total_references'], 0, ',', ' ') }} références)
            </span>
          </h3>
        </div>

        <div class="table-wrapper">
          <div id="tableLoadingOverlay">
            <div class="spinner-table"></div>
          </div>
          <div class="table-responsive">
            <table class="data-table" id="stockTable">
              <thead>
                <tr>
                  <th>Produit</th>
                  <th>Dosage</th>
                  <th>Catégorie</th>
                  <th>Fournisseur</th>
                  <th>Prix d'Achat</th>
                  <th>Prix de Vente</th>
                  <th>Stock Actuel</th>
                  <th>Stock Min</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="stockTableBody">
                @forelse($products as $product)
                  @php $firstImage = $product->media->first(); @endphp
                  <tr
                    data-id="{{ $product->id }}"
                    data-category="{{ $product->category?->name }}"
                    data-provider="{{ $product->main_provider }}"
                    data-status="{{ implode(',', $product->status_codes ?? []) }}"
                    data-search="{{ strtolower($product->name.' '.$product->reference.' '.$product->category?->name.' '.$product->main_provider) }}"
                  >
                    <td>
                      <div style="display:flex;align-items:center;gap:10px">
                        @if($firstImage)
                          <img src="{{ $product->image_url ?? asset($firstImage->file_path) }}" class="thumb-mini" alt="image">
                        @else
                          <div class="product-icon" style="background:#dcfce7">💊</div>
                        @endif
                        <div>
                          <div style="font-weight:600;font-size:13px">{{ $product->name }}</div>
                          <div style="font-size:11px;color:var(--text-light)">{{ $product->reference }}</div>
                        </div>
                      </div>
                    </td>
                    <td><span class="product-badge badge-analgesique">{{ $product->dosage ?? '—' }}</span></td>
                    <td><span>{{ $product->category?->name ?? '—' }}</span></td>
                    <td>{{ $product->main_provider }}</td>
                    <td>{{ number_format($product->current_purchase_price, 0, ',', ' ') }} CDF</td>
                    <td>
                      <strong>{{ number_format($product->current_sale_price, 0, ',', ' ') }} CDF</strong>
                      <span class="price-up">
                        +{{ $product->current_purchase_price > 0
                            ? number_format((($product->current_sale_price - $product->current_purchase_price) / $product->current_purchase_price) * 100, 1, ',', ' ')
                            : '0' }}%
                      </span>
                    </td>
                    <td>
                      <strong>{{ number_format($product->available_stock, 0, ',', ' ') }}</strong>
                      <div style="font-size:11px;color:var(--text-muted)">
                        {{ $product->main_unit === 'box' ? 'boîtes' : ($product->main_unit === 'blister' ? 'plaquettes' : $product->main_unit) }}
                      </div>
                    </td>
                    <td>{{ number_format($product->min_stock, 0, ',', ' ') }}</td>
                    <td>
                      <div class="d-flex flex-wrap gap-1">
                        @foreach(($product->status_labels ?? []) as $label)
                          @php
                            $lbl = strtolower($label);
                            $cls = match(true){
                                str_contains($lbl,'périmé') => 'badge-perime',
                                str_contains($lbl,'presque') => 'badge-almost-expired',
                                str_contains($lbl,'faible') => 'badge-stock-faible',
                                default => 'badge-en-stock',
                            };
                          @endphp
                          <span class="badge-status {{ $cls }}">{{ $label }}</span>
                        @endforeach
                      </div>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <button class="btn-actions btn-history"
                          data-product-id="{{ $product->id }}"
                          data-product-name="{{ $product->name }}"
                          title="Historique des prix">⌛</button>
                        <button class="btn-actions btn-stock-entries"
                          data-product-id="{{ $product->id }}"
                          data-product-name="{{ $product->name }}"
                          title="Voir les entrées">📦</button>
                        <button class="btn-actions btn-edit-product {{ $canEdit ? '' : 'disabled-action' }}"
                          data-can-edit="{{ $canEdit ? 1 : 0 }}"
                          data-id="{{ $product->id }}"
                          data-name="{{ e($product->name) }}"
                          data-description="{{ e($product->description) }}"
                          data-category-id="{{ $product->category_id }}"
                          data-min-stock="{{ $product->min_stock }}"
                          data-dosage="{{ e($product->dosage ?? '') }}"
                          title="Modifier">✎</button>
                        <button class="btn-actions btn-delete-product {{ $canDelete ? '' : 'disabled-action' }}"
                          data-can-delete="{{ $canDelete ? 1 : 0 }}"
                          data-product-id="{{ $product->id }}"
                          data-product-name="{{ e($product->name) }}"
                          title="Supprimer">🗑</button>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="9" class="text-center py-4">Aucun produit trouvé.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div id="paginationContainer" class="d-flex justify-content-center mt-3 px-3">
          {{ $products->links('pagination::bootstrap-5') }}
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--border-light);flex-wrap:wrap;gap:8px">
          <div id="tableFooterCount" style="font-size:13px;color:var(--text-muted)">
            Affichage {{ $products->firstItem() ?? 0 }} à {{ $products->lastItem() ?? 0 }}
            sur {{ $products->total() }} produits
          </div>
          <div style="display:flex;align-items:center;gap:8px">
            <select class="filter-select" id="perPageSelect" style="padding:6px 28px 6px 8px;font-size:12px">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <span style="font-size:13px;color:var(--text-muted)">par page</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="addProductModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form id="addProductForm" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Nouveau Produit + Stock Initial</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Nom du produit</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Catégorie</label>
              <select name="category_id" class="form-select" required>
                <option value="">Choisir...</option>
                @foreach($categories as $category)
                  <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
            <label class="form-label">Unité</label>
            <select name="unit_name" class="form-select" required>
                @foreach($units as $unit)
                <option value="{{ $unit->name }}">
                    {{ ucfirst($unit->name) }}
                </option>
                @endforeach

            </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Dosage</label>
              <input type="text" name="dosage" class="form-control" placeholder="Ex: 500 mg / 5 ml">
            </div>
            <div class="col-md-4">
              <label class="form-label">Fournisseur</label>
              <select name="provider_id" class="form-select" required>
                <option value="">Choisir...</option>
                @foreach($providers as $provider)
                  <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Stock minimum</label>
              <input type="number" name="min_stock" class="form-control" placeholder="0" value="" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Quantité initiale</label>
              <input type="number" name="quantity" class="form-control" required min="1">
            </div>
            <div class="col-md-4">
              <label class="form-label">Prix d'achat (CDF)</label>
              <input type="number" name="purchase_price" class="form-control" min="0" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Prix de vente (CDF)</label>
              <input type="number" name="sale_price" class="form-control" min="0" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Mois / année d'expiration</label>
              <input type="month" name="expiration_date" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Âge cible</label>
              <select name="age_range" class="form-select">
                <option value="">Aucun</option>
                <option value="enfant">Enfant</option>
                <option value="adulte">Adulte</option>
                <option value="senior">Senior</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-12" id="addImagesField">
            <label class="form-label">Images du produit</label>
            <input type="file" name="images[]" id="addImagesInput" class="form-control" multiple accept="image/*">
            <div id="addImagesPreview" class="img-preview-grid"></div>
            </div>
            <div class="col-12 d-none" id="addImagesOfflineMsg">
              <div class="alert alert-warning mb-0">
                <i class="bi bi-wifi-off me-2"></i>
                Les images ne peuvent pas être téléchargées en mode hors-ligne.
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success" id="addProductSubmitBtn">
            <span class="btn-text">Enregistrer</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="receptionModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="receptionForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Nouvelle Réception de Stock</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6 position-relative">
              <label class="form-label">Produit</label>
              <input type="text" class="form-control" id="receptionProductSearch" placeholder="Écrire pour rechercher un produit...">
              <input type="hidden"  name="product_id" id="receptionProduct" required>
              <div id="receptionProductResults" class="search-results-box d-none"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Unité</label>
              <select name="product_unit_id_1" id="receptionUnit_1"  class="form-select"  required>
                <option value="">Choisir d'abord un produit</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Fournisseur</label>
              <select name="provider_id_1" id="provider_id_1_1" class="form-select" required>
                <option value="">Choisir d'abord un produit</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Quantité</label>
              <input type="number" name="quantity_1" class="form-control" min="1" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Prix d'achat (CDF)</label>
              <input type="number" name="purchase_price_1" id="receptionPurchase_1" class="form-control" min="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Prix de vente (CDF)</label>
              <input type="number" name="sale_price_1" id="receptionSale_1" class="form-control" min="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mois / année d'expiration</label>
              <input type="month" name="expiration_date_1" id="receptionExpiration_1" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Âge cible</label>
              <select name="age_range_1" id="receptionAgeRange_1" class="form-select">
                <option value="">Aucun</option>
                <option value="enfant">Enfant</option>
                <option value="adulte">Adulte</option>
                <option value="senior">Senior</option>
              </select>
            </div>
            <div class="col-md-6">
              <div class="alert alert-info mb-0" style="font-size:13px">
                Quand vous choisissez le produit et l'unité, les prix du dernier stock sont remplis automatiquement.
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success" id="receptionSubmitBtn">
            <span class="btn-text">Enregistrer la réception</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editProductModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editProductForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_method" value="PUT">
        <div class="modal-header">
          <h5 class="modal-title">Modifier le produit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <input type="hidden" id="editProductId">
            <div class="col-md-6">
              <label class="form-label">Nom</label>
              <input type="text" name="name" id="editName" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Catégorie</label>
              <select name="category_id" id="editCategory" class="form-select" required>
                @foreach($categories as $category)
                  <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Stock minimum</label>
              <input type="number" name="min_stock" id="editMinStock" placeholder="0" class="form-control" min="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">Dosage</label>
              <input type="text" name="dosage" id="editDosage" class="form-control" placeholder="Ex: 500 mg / 5 ml">
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
            </div>

            <div class="col-12" id="editImagesField">
              <label class="form-label">Ajouter de nouvelles images</label>
              <input type="file" name="images[]" id="editImagesInput" class="form-control" multiple accept="image/*">
              <div id="editImagesPreview" class="img-preview-grid"></div>
            </div>

            <div class="col-12 d-none" id="editImagesOfflineMsg">
              <div class="alert alert-warning mb-0">
                <i class="bi bi-wifi-off me-2"></i>
                Les images ne peuvent pas être téléchargées en mode hors-ligne.
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary" id="editProductSubmitBtn">
            <span class="btn-text">Mettre à jour</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="historyModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="historyTitle">Historique des prix</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr><th>Unité</th><th>Prix d'achat</th><th>Prix de vente</th><th>Début</th><th>Fin</th></tr>
            </thead>
            <tbody id="historyBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="entriesModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="entriesTitle">Entrées du produit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <strong>Total disponible :</strong> <span id="entriesTotal">0</span>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th>#</th><th>Unité</th><th>Quantité</th><th>Lot</th><th>Fournisseur</th>
                <th>Prix d'achat</th><th>Prix de vente</th><th>Bénéfice</th><th>Expiration</th>
                <th>Date entrée</th><th>Statut</th><th>Action</th>
              </tr>
            </thead>
            <tbody id="entriesBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@include('partials.profil1')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=<?= time() ?>"></script>


<script>
function bindImagePreview(inputId, previewId) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  if (!input || !preview) return;

  let currentFiles = [];

  function syncInput() {
    const dt = new DataTransfer();
    currentFiles.forEach(file => dt.items.add(file));
    input.files = dt.files;
  }

  function render() {
    preview.innerHTML = '';

    if (!currentFiles.length) return;

    currentFiles.forEach((file, index) => {
      const url = URL.createObjectURL(file);

      const card = document.createElement('div');
      card.className = 'img-preview-card';
      card.innerHTML = `
        <button type="button" class="img-preview-remove" title="Retirer">×</button>
        <img src="${url}" alt="${file.name}">
        <div class="img-preview-info">
          <div style="font-weight:600;word-break:break-word">${file.name}</div>
          <div>${Math.round(file.size / 1024)} KB</div>
        </div>
      `;

      const img = card.querySelector('img');
      img.onload = () => {
        URL.revokeObjectURL(url);
      };

      card.querySelector('.img-preview-remove').addEventListener('click', () => {
        currentFiles.splice(index, 1);
        syncInput();
        render();
      });

      preview.appendChild(card);
    });
  }

  input.addEventListener('change', () => {
    currentFiles = Array.from(input.files || []);
    syncInput();
    render();
  });

  input.form?.addEventListener('reset', () => {
    currentFiles = [];
    preview.innerHTML = '';
  });

  return {
    clear() {
      currentFiles = [];
      input.value = '';
      preview.innerHTML = '';
    }
  };
}

document.addEventListener('DOMContentLoaded', () => {
  bindImagePreview('addImagesInput', 'addImagesPreview');
  bindImagePreview('editImagesInput', 'editImagesPreview');
});

const BASE_URL   = '{{ url('/') }}';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
const QUEUE_KEY  = 'pharma_offline_queue_v2';
const STOCK_KEY  = 'pharma_stock_store_v1';
const STOCK_TTL  = 30 * 24 * 60 * 60 * 1000;

const ALL_UNITS = @json($units->values());
const ALL_PROVIDERS = @json($providers->values());

const CAN_ADD    = @json($canAdd);
const CAN_EDIT   = @json($canEdit);
const CAN_DELETE = @json($canDelete);

let stockStore = [];
let stockStats = null;

let currentPage    = 1;
let currentPerPage = 10;
let filterTimer = null;
let receptionSearchTimer = null;

function showToast(message, type = 'success', duration = 4000) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const toast = document.createElement('div');
  toast.className = `toast-item toast-${type}`;
  toast.innerHTML = `
    <span class="toast-icon">${icons[type] || icons.info}</span>
    <span style="flex:1">${message}</span>
    <button class="toast-close" onclick="this.closest('.toast-item').remove()">×</button>
  `;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'slideOut 0.3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

function getQueue() {
  try {
    return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]');
  } catch (e) {
    return [];
  }
}

function saveQueue(q) {
  localStorage.setItem(QUEUE_KEY, JSON.stringify(q));
}

function enqueue(op) {
  const q = getQueue();
  q.push({ ...op, _qid: Date.now() + '_' + Math.random().toString(36).slice(2) });
  saveQueue(q);
  updateSyncBanner();
}

function dequeue(qid) {
  saveQueue(getQueue().filter(o => o._qid !== qid));
  updateSyncBanner();
}

function updateSyncBanner() {
  const q = getQueue();
  const banner = document.getElementById('syncBanner');
  const text = document.getElementById('syncBannerText');

  if (!banner || !text) return;

  if (q.length > 0 && navigator.onLine) {
    text.textContent = `Connexion active — ${q.length} opération(s) en attente. Cliquez ici pour synchroniser.`;
    banner.classList.add('show');
  } else {
    banner.classList.remove('show');
  }
}

async function ajaxRequest(url, method = 'GET', body = null, isFormData = false) {
  const headers = {
    'X-CSRF-TOKEN': CSRF_TOKEN,
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  };

  if (!isFormData && body) headers['Content-Type'] = 'application/json';

  const opts = { method, headers };
  if (body) opts.body = isFormData ? body : JSON.stringify(body);

  const res = await fetch(url, opts);

  let json = null;
  try {
    json = await res.json();
  } catch (e) {
    throw new Error('Réponse invalide du serveur');
  }

  if (!res.ok) throw new Error(json.message || 'Erreur serveur');
  return json;
}

function fmt(n) {
  return Number(n || 0).toLocaleString('fr-FR');
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  }[ch]));
}

function normalizeStatusValue(value) {
  return String(value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, '_')
    .replace(/[^a-z0-9_\-]/g, '');
}

function uniqById(items) {
  const out = [];
  const seen = new Set();
  (Array.isArray(items) ? items : []).forEach((item) => {
    if (!item || item.id === undefined || item.id === null) return;
    const id = String(item.id);
    if (seen.has(id)) return;
    seen.add(id);
    out.push({ id: item.id, name: item.name ?? item.label ?? item.title ?? item.unit_name ?? '' });
  });
  return out;
}

function buildSelectOptions(items, selectedValue, placeholder = 'Choisir...', allowEmpty = true) {
  const list = uniqById(items);
  const options = [];
  if (allowEmpty) options.push(`<option value="">${escapeHtml(placeholder)}</option>`);
  list.forEach((item) => {
    const selected = String(selectedValue ?? '') === String(item.id) ? ' selected' : '';
    options.push(`<option value="${escapeHtml(item.id)}"${selected}>${escapeHtml(item.name)}</option>`);
  });
  return options.join('');
}

function buildProviderOptions(selectedId, selectedName) {
  const merged = [];
  if (selectedId !== null && selectedId !== undefined && selectedId !== '' && selectedName) {
    merged.push({ id: selectedId, name: selectedName });
  }
  merged.push(...ALL_PROVIDERS);
  return buildSelectOptions(merged, selectedId, 'Choisir un fournisseur');
}

function buildUnitOptions(productUnits, selectedId) {
  if (Array.isArray(productUnits) && productUnits.length) {
    return buildSelectOptions(
        productUnits,
        selectedId,
    );
}

return '<option value="">Aucune unité disponible</option>';
}

function normalizeProduct(p) {
  // status_codes: s'assurer que c'est un tableau propre
  let statusCodes = [];
  if (Array.isArray(p.status_codes)) {
    statusCodes = p.status_codes.map(v => normalizeStatusValue(v)).filter(Boolean);
  } else if (typeof p.status_codes === 'string' && p.status_codes) {
    statusCodes = p.status_codes.split(',').map(v => normalizeStatusValue(v)).filter(Boolean);
  }
  statusCodes = Array.from(new Set(statusCodes));

  // status_labels: s'assurer que c'est un tableau propre
  let statusLabels = [];
  if (Array.isArray(p.status_labels)) {
    statusLabels = p.status_labels.map(v => String(v || '').trim()).filter(Boolean);
  } else if (typeof p.status_labels === 'string' && p.status_labels) {
    statusLabels = p.status_labels.split(',').map(v => String(v || '').trim()).filter(Boolean);
  }

  return {
    id: p.id,
    name: p.name || '',
    reference: p.reference || '',
    category_id: p.category_id || null,
    category: p.category || '—',
    main_provider: p.main_provider || '—',
    main_unit: p.main_unit || '—',
    dosage: p.dosage || '—',

    provider_id: p.provider_id ?? null,
    provider_name: p.provider_name ?? null,

    available_stock: Number(p.available_stock || 0),
    expired_stock: Number(p.expired_stock || 0),
    min_stock: Number(p.min_stock || 0),

    current_purchase_price: Number(p.current_purchase_price ?? p.price_purchase ?? p.purchase_price ?? 0),
    current_sale_price: Number(p.current_sale_price ?? p.price_sale ?? p.sale_price ?? 0),

    current_price_start_date: p.current_price_start_date || null,
    current_price_end_date: p.current_price_end_date || null,
    price_product_unit_id: p.price_product_unit_id || null,

    turnover_total: Number(p.turnover_total || 0),
    benefit_total: Number(p.benefit_total || 0),

    status_codes: statusCodes,
    status_labels: statusLabels,

    image_url: p.image_url || null,
    description: p.description || '',
    units: Array.isArray(p.units) ? p.units : [],
    unit_id: p.unit_id || null,
    last_expiration_date: p.last_expiration_date || null,
    last_age_range: p.last_age_range || null,
  };
}


function computeStatsFromStore(products) {
  const total_references = products.length;
  const stock_value = products.reduce((sum, p) => sum + (Number(p.current_purchase_price || 0) * Number(p.available_stock || 0)), 0);
  const benefit_total = products.reduce((sum, p) => {
    const purchase = Number(p.current_purchase_price || 0);
    const sale = Number(p.current_sale_price || 0);
    return sum + Math.max(0, sale - purchase) * Number(p.available_stock || 0);
  }, 0);
  const low_stock = products.filter(p => Number(p.available_stock || 0) <= Number(p.min_stock || 0)).length;
  const expired = products.filter(p => (p.status_codes || []).includes('expired') || Number(p.expired_stock || 0) > 0).length;
  const almost_expired = products.filter(p => (p.status_codes || []).includes('almost_expired')).length;

  return { total_references, stock_value, benefit_total, low_stock, expired, almost_expired };
}

function getStats() {
  return stockStats || computeStatsFromStore(stockStore);
}

function saveStockCache() {
  try {
    localStorage.setItem(STOCK_KEY, JSON.stringify({
      savedAt: Date.now(),
      products: stockStore,
      stats: stockStats
    }));
  } catch (e) {}
}

function loadStockCache() {
  try {
    const raw = localStorage.getItem(STOCK_KEY);
    if (!raw) return null;
    const data = JSON.parse(raw);
    if (!data || !Array.isArray(data.products)) return null;
    if (data.savedAt && (Date.now() - data.savedAt) > STOCK_TTL) return null;
    return data;
  } catch (e) {
    return null;
  }
}

function setStockStore(products, stats = null, render = true) {
  stockStore = (products || []).map(normalizeProduct);
  stockStats = stats || computeStatsFromStore(stockStore);
  saveStockCache();
  if (render) renderCurrentView();
}

function showSyncLoader(message = 'Synchronisation des données en cours...') {
  const loader = document.getElementById('syncLoader');
  const text = document.getElementById('syncLoaderText');
  if (!loader || !text) return;
  text.textContent = message;
  loader.classList.remove('d-none');
}

function hideSyncLoader() {
  document.getElementById('syncLoader')?.classList.add('d-none');
}

async function fetchAllStockPages() {
  const perPage = 100;
  let page = 1;
  let lastPage = 1;
  let allProducts = [];
  let stats = null;

  do {
    const data = await ajaxRequest(`${BASE_URL}/stock/list-all?page=${page}&per_page=${perPage}`);
    allProducts = allProducts.concat(data.products || []);
    stats = data.stats || stats;
    lastPage = Math.max(1, Number(data.last_page || 1));
    page += 1;
  } while (page <= lastPage);

  return { products: allProducts, stats };
}

async function refreshStockFromServer({ silent = false } = {}) {
  if (!navigator.onLine) return false;

  try {
    const data = await fetchAllStockPages();
    setStockStore(data.products || [], data.stats || null, true);
    return true;
  } catch (e) {
    if (!silent) showToast('Erreur de chargement : ' + e.message, 'error');
    return false;
  }
}

function renderProductRow(p) {
  const margin = p.current_purchase_price > 0
    ? (((p.current_sale_price - p.current_purchase_price) / p.current_purchase_price) * 100).toFixed(1).replace('.', ',')
    : '0';

  const unitLabel = p.main_unit === 'box'
    ? 'boîtes'
    : (p.main_unit === 'blister' ? 'plaquettes' : (p.main_unit || ''));

  const badges = (p.status_labels || []).map(label => {
  const l = String(label).toLowerCase();
  // "périmé" sans "presque" = badge périmé (inclut "Périmé partiel")
  const cls =
    (l.includes('péri') && !l.includes('presque')) ? 'badge-perime' :
    l.includes('presque') ? 'badge-almost-expired' :
    l.includes('faible') ? 'badge-stock-faible' :
    'badge-en-stock';

  return `<span class="badge-status ${cls}">${label}</span>`;

  }).join('');

  const imgHtml = p.image_url
    ? `<img src="${p.image_url}" class="thumb-mini" alt="image">`
    : `<div class="product-icon" style="background:#dcfce7">💊</div>`;

  const canEditBtn = CAN_EDIT ? '' : 'disabled-action';
  const canDeleteBtn = CAN_DELETE ? '' : 'disabled-action';

  return `<tr data-id="${p.id}"
              data-category="${p.category || ''}"
              data-provider="${p.main_provider || ''}"
              data-status="${(p.status_codes || []).join(',')}"
              data-search="${(p.name || '') + ' ' + (p.reference || '') + ' ' + (p.category || '') + ' ' + (p.main_provider || '')}">
    <td>
      <div style="display:flex;align-items:center;gap:10px">
        ${imgHtml}
        <div>
          <div style="font-weight:600;font-size:13px">${p.name || ''}</div>
          <div style="font-size:11px;color:var(--text-light)">${p.reference || ''}</div>
        </div>
      </div>
    </td>
    <td><span class="product-badge badge-analgesique">${p.dosage || '—'}</span></td>
    <td><span>${p.category || '—'}</span></td>
    <td>${p.main_provider || '—'}</td>
    <td>${fmt(p.current_purchase_price)} CDF</td>
    <td>
      <strong>${fmt(p.current_sale_price)} CDF</strong>
      <span class="price-up">+${margin}%</span>
    </td>
    <td>
      <strong>${fmt(p.available_stock)}</strong>
      <div style="font-size:11px;color:var(--text-muted)">${unitLabel}</div>
    </td>
    <td>${fmt(p.min_stock)}</td>
    <td><div class="d-flex flex-wrap gap-1">${badges}</div></td>
    <td>
      <div class="d-flex gap-1">
        <button class="btn-actions btn-history"
          data-product-id="${p.id}"
          data-product-name="${(p.name||'').replace(/"/g,'&quot;')}"
          title="Historique des prix">⌛</button>
        <button class="btn-actions btn-stock-entries"
          data-product-id="${p.id}"
          data-product-name="${(p.name||'').replace(/"/g,'&quot;')}"
          title="Voir les entrées">📦</button>
        <button class="btn-actions btn-edit-product ${canEditBtn}"
          data-can-edit="${CAN_EDIT ? 1 : 0}"
          data-id="${p.id}"
          data-name="${(p.name||'').replace(/"/g,'&quot;')}"
          data-description="${(p.description||'').replace(/"/g,'&quot;')}"
          data-category-id="${p.category_id || ''}"
          data-min-stock="${p.min_stock || 0}"
          data-dosage="${p.dosage || 0}"
          title="Modifier">✎</button>
        <button class="btn-actions btn-delete-product ${canDeleteBtn}"
          data-can-delete="${CAN_DELETE ? 1 : 0}"
          data-product-id="${p.id}"
          data-product-name="${(p.name||'').replace(/"/g,'&quot;')}"
          title="Supprimer">🗑</button>
      </div>
    </td>
  </tr>`;
}

function renderTable(products) {
  const tbody = document.getElementById('stockTableBody');
  if (!tbody) return;

  if (!products || !products.length) {
    tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4">Aucun produit trouvé.</td></tr>`;
    return;
  }

  tbody.innerHTML = products.map(renderProductRow).join('');
}

function renderPaginationLocal(page, lastPage) {
  const container = document.getElementById('paginationContainer');
  if (!container) return;

  if (lastPage <= 1) {
    container.innerHTML = '';
    return;
  }

  let pages = '';
  const prev = page - 1;
  const next = page + 1;

  pages += `<li class="page-item${page === 1 ? ' disabled' : ''}">
    <a class="page-link" href="?page=${prev}">«</a></li>`;

  for (let i = 1; i <= lastPage; i++) {
    if (i === 1 || i === lastPage || (i >= page - 2 && i <= page + 2)) {
      pages += `<li class="page-item${i === page ? ' active' : ''}">
        <a class="page-link" href="?page=${i}">${i}</a></li>`;
    } else if (i === page - 3 || i === page + 3) {
      pages += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
    }
  }

  pages += `<li class="page-item${page === lastPage ? ' disabled' : ''}">
    <a class="page-link" href="?page=${next}">»</a></li>`;

  container.innerHTML = `<ul class="pagination">${pages}</ul>`;
}

function renderStats(stats) {
  const ids = {
    statAlmostExpired: stats.almost_expired || 0,
    statTotalRefs: stats.total_references || 0,
    statStockValue: (fmt(stats.stock_value) + ' CDF'),
    statLowStock: stats.low_stock || 0,
    statExpired: stats.expired || 0,
    statTurnoverTotal: (fmt(stats.turnover_total) + ' CDF'),
    statBenefitTotal: (fmt(stats.benefit_total) + ' CDF'),
  };

  Object.entries(ids).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
  });

  const count = document.getElementById('productsCount');
  if (count) count.textContent = `(${fmt(stats.total_references || 0)} références)`;
}

// Remplace applyStockFilters par cette version plus robuste
function applyStockFilters() {
  const search   = normalizeText(document.getElementById('stockSearch')?.value.trim() || '');
  const category = document.getElementById('filterCategory')?.value || 'all';
  const status   = document.getElementById('filterStatus')?.value || 'all';
  const provider = document.getElementById('filterProvider')?.value || 'all';

  return stockStore.filter(p => {
    const haystack = normalizeText(`${p.name || ''} ${p.reference || ''} ${p.category || ''} ${p.main_provider || ''}`);

    const searchOk   = !search || haystack.includes(search);
    const categoryOk = category === 'all' || (p.category || '') === category;
    const providerOk = provider === 'all' || (p.main_provider || '') === provider;

    // Récupérer les codes de statut - s'assurer que c'est bien un tableau
    let codes = [];
    if (Array.isArray(p.status_codes)) {
      codes = p.status_codes.map(v => normalizeStatusValue(v));
    } else if (typeof p.status_codes === 'string' && p.status_codes) {
      codes = p.status_codes.split(',').map(v => normalizeStatusValue(v));
    }

    // Logique de filtrage: un produit est inclus si le statut recherché est dans SES statuts
    let statusOk = status === 'all';

    if (!statusOk) {
      if (status === 'ok') {
        statusOk = codes.includes('ok');
      } else if (status === 'low') {
        statusOk = codes.includes('low');
      } else if (status === 'expired') {
        statusOk = codes.includes('expired');
      } else if (status === 'almost_expired') {
        statusOk = codes.includes('almost_expired');
      }
    }

    return searchOk && categoryOk && providerOk && statusOk;
  });
}


function normalizeText(value) {
  return String(value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}
function renderCurrentView() {
  const filtered = applyStockFilters();
  const total = filtered.length;
  const lastPage = Math.max(1, Math.ceil(total / currentPerPage));

  if (currentPage > lastPage) currentPage = lastPage;
  if (currentPage < 1) currentPage = 1;

  const start = (currentPage - 1) * currentPerPage;
  const pageItems = filtered.slice(start, start + currentPerPage);

  renderTable(pageItems);
  renderPaginationLocal(currentPage, lastPage);

  const from = total === 0 ? 0 : start + 1;
  const to   = Math.min(start + currentPerPage, total);

  const footer = document.getElementById('tableFooterCount');
  if (footer) {
    footer.textContent = `Affichage ${from} à ${to} sur ${total} produits`;
  }

  renderStats(getStats());
}

function upsertProductInStore(product, stats = null) {
  const normalized = normalizeProduct(product);
  const index = stockStore.findIndex(p => String(p.id) === String(normalized.id));

  if (index >= 0) {
    stockStore[index] = normalized;
  } else {
    stockStore.unshift(normalized);
  }

  if (stats) stockStats = stats;
  saveStockCache();
  renderCurrentView();
}

function removeProductFromStore(productId, stats = null) {
  stockStore = stockStore.filter(p => String(p.id) !== String(productId));
  if (stats) stockStats = stats;
  saveStockCache();
  renderCurrentView();
}

async function reloadStoreAfterMutation() {
  await refreshStockFromServer({ silent: true });
}

function loadPage(page = 1) {
  currentPage = page;
  renderCurrentView();
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

document.addEventListener('click', function(e) {
  const link = e.target.closest('#paginationContainer a[href]');
  if (!link) return;
  e.preventDefault();
  const url = new URL(link.href, window.location.origin);
  const page = parseInt(url.searchParams.get('page') || '1', 10);
  loadPage(page);
});

document.getElementById('btnOpenAddProduct')?.addEventListener('click', () => {
  if (!CAN_ADD) {
    showToast("Vous n'avez pas le droit d'ajouter un produit.", 'warning');
    return;
  }
  new bootstrap.Modal(document.getElementById('addProductModal')).show();
});

document.getElementById('btnOpenReception')?.addEventListener('click', () => {
  if (!CAN_ADD) {
    showToast("Vous n'avez pas le droit d'enregistrer une réception.", 'warning');
    return;
  }
  new bootstrap.Modal(document.getElementById('receptionModal')).show();
});

document.getElementById('stockSearch')?.addEventListener('input', () => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    currentPage = 1;
    renderCurrentView();
  }, 200);
});

document.getElementById('filterCategory')?.addEventListener('change', () => {
  currentPage = 1;
  renderCurrentView();
});

document.getElementById('filterStatus')?.addEventListener('change', () => {
  currentPage = 1;
  renderCurrentView();
});

document.getElementById('filterProvider')?.addEventListener('change', () => {
  currentPage = 1;
  renderCurrentView();
});

document.getElementById('btnApplyFilters')?.addEventListener('click', () => {
  currentPage = 1;
  renderCurrentView();
});

document.getElementById('perPageSelect')?.addEventListener('change', function() {
  currentPerPage = parseInt(this.value, 10);
  currentPage = 1;
  renderCurrentView();
});

document.getElementById('addProductForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  if (!CAN_ADD) {
    showToast("Vous n'avez pas le droit d'ajouter un produit.", 'warning');
    return;
  }

  const btn  = document.getElementById('addProductSubmitBtn');
  const form = this;

  if (!navigator.onLine) {
    const data = Object.fromEntries(new FormData(form).entries());
    delete data['images[]'];
    enqueue({ type: 'addProduct', data, url: `${BASE_URL}/stock/products`, method: 'POST' });
    showToast("Produit mis en file d'attente — sera synchronisé à la reconnexion.", 'warning');
    bootstrap.Modal.getInstance(document.getElementById('addProductModal'))?.hide();
    form.reset();
    document.getElementById('addImagesPreview').innerHTML = '';
    document.getElementById('editImagesPreview').innerHTML = '';
    return;
  }

  setBtnLoading(btn, true);
  try {
    const json = await ajaxRequest(`${BASE_URL}/stock/products`, 'POST', new FormData(form), true);
    showToast(json.message, 'success');

    if (json.product) {
      upsertProductInStore(json.product, json.stats || null);
    } else if (json.stats) {
      stockStats = json.stats;
      saveStockCache();
      refreshStockFromServer({ silent: true });
    }

    bootstrap.Modal.getInstance(document.getElementById('addProductModal'))?.hide();
    form.reset();
    document.getElementById('addImagesPreview').innerHTML = '';
    document.getElementById('editImagesPreview').innerHTML = '';
    refreshStockFromServer({ silent: true });
  } catch(err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    setBtnLoading(btn, false);
  }
});

document.getElementById('receptionForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  if (!CAN_ADD) {
    showToast("Vous n'avez pas le droit d'enregistrer une réception.", 'warning');
    return;
  }

  const btn  = document.getElementById('receptionSubmitBtn');
  const form = this;
  const payload = Object.fromEntries(new FormData(form).entries());

  if (!navigator.onLine) {
    enqueue({
      type: 'reception',
      data: payload,
      url: `${BASE_URL}/stock/receptions`,
      method: 'POST'
    });

    showToast("Réception mise en file d'attente — elle sera synchronisée à la reconnexion.", 'warning');
    bootstrap.Modal.getInstance(document.getElementById('receptionModal'))?.hide();
    form.reset();
    document.getElementById('receptionUnit_1').innerHTML = '<option value="">Choisir d\'abord un produit</option>';
    document.getElementById('receptionProductResults').classList.add('d-none');
    document.getElementById('receptionProductSearch').value = '';
    return;
  }

  setBtnLoading(btn, true);
  try {
    const json = await ajaxRequest(`${BASE_URL}/stock/receptions`, 'POST', new FormData(form), true);

    if (json.product) {
      upsertProductInStore(json.product, json.stats || null);
    } else if (json.stats) {
      stockStats = json.stats;
      saveStockCache();
    }

    bootstrap.Modal.getInstance(document.getElementById('receptionModal'))?.hide();
    form.reset();
    document.getElementById('receptionUnit_1').innerHTML = '<option value="">Choisir d\'abord un produit</option>';
    document.getElementById('receptionProductResults').classList.add('d-none');
    document.getElementById('receptionProductSearch').value = '';
    showToast(json.message, 'success');
    refreshStockFromServer({ silent: true });
  } catch(err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    setBtnLoading(btn, false);
  }
});

function stockSetLoadingState(isLoading) {
  ['receptionUnit_1','provider_id_1_1','receptionPurchase_1','receptionSale_1','receptionExpiration_1','receptionAgeRange_1'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.parentElement?.classList.toggle('loading-field', isLoading);
    el.disabled = isLoading;
    if (id !== 'receptionUnit_1') el.readOnly = isLoading;
  });
}

function findProductInStore(productId) {
  return stockStore.find(p => String(p.id) === String(productId)) || null;
}

function formatDateForInput(date) {
  if (!date) return '';
  return String(date).substring(0, 10);
}

async function loadProductDefaults() {
  const productId = document.getElementById('receptionProduct')?.value;
  const unitSel   = document.getElementById('receptionUnit_1');
  const providerSel = document.getElementById('provider_id_1_1');
  const purchase  = document.getElementById('receptionPurchase_1');
  const sale      = document.getElementById('receptionSale_1');
  const expDate   = document.getElementById('receptionExpiration_1');
  const ageRange  = document.getElementById('receptionAgeRange_1');

  stockSetLoadingState(true);

  if (purchase) purchase.value = '';
  if (sale) sale.value = '';
  if (expDate) expDate.value = '';
  if (ageRange) ageRange.value = '';

  if (!productId) {
    if (unitSel) unitSel.innerHTML = buildSelectOptions([], '', 'Choisir d\'abord un produit');
    if (providerSel) providerSel.innerHTML = buildSelectOptions([], '', 'Choisir d\'abord un produit');
    stockSetLoadingState(false);
    return;
  }

  const localProduct = findProductInStore(productId);
  if (localProduct) {
    fillReceptionFormFromProduct(localProduct);
    stockSetLoadingState(false);
    return;
  }

  try {
    const data = await ajaxRequest(`${BASE_URL}/stock/products/${productId}/defaults`);

    if (unitSel) {
      const mergedUnits = Array.isArray(data.units) && data.units.length ? data.units : ALL_UNITS;
      unitSel.innerHTML = buildSelectOptions(mergedUnits, data.unit_id || '', 'Choisir une unité');
      if (data.unit_id) unitSel.value = String(data.unit_id);
    }

    if (providerSel) {
      const selectedProviderId = data.provider_id || '';
      const selectedProviderName = data.provider_name || '';
      providerSel.innerHTML = buildProviderOptions(selectedProviderId, selectedProviderName);
      if (selectedProviderId) providerSel.value = String(selectedProviderId);
    }

    if (purchase && data.purchase_price != null) purchase.value = data.purchase_price;
    if (sale && data.sale_price != null) sale.value = data.sale_price;
    if (expDate && data.expiration_date) expDate.value = formatDateForInput(data.expiration_date);
    if (ageRange && data.age_range) ageRange.value = data.age_range;
  } catch (e) {
    if (unitSel) unitSel.innerHTML = buildSelectOptions([], '', 'Erreur');
    if (providerSel) providerSel.innerHTML = buildSelectOptions([], '', 'Erreur');
  } finally {
    stockSetLoadingState(false);
  }
}

function fillReceptionFormFromProduct(product) {
  const unitSel     = document.getElementById('receptionUnit_1');
  const purchase    = document.getElementById('receptionPurchase_1');
  const sale        = document.getElementById('receptionSale_1');
  const expDate     = document.getElementById('receptionExpiration_1');
  const ageRange    = document.getElementById('receptionAgeRange_1');
  const providerSel = document.getElementById('provider_id_1_1');

  if (!product) {
    if (unitSel) unitSel.innerHTML = buildSelectOptions([], '', 'Choisir d\'abord un produit');
    if (providerSel) providerSel.innerHTML = buildSelectOptions([], '', 'Choisir d\'abord un produit');
    if (purchase) purchase.value = '';
    if (sale) sale.value = '';
    if (expDate) expDate.value = '';
    if (ageRange) ageRange.value = '';
    return;
  }

  const units = Array.isArray(product.units) && product.units.length ? product.units : ALL_UNITS;
  const selectedUnitId = product.unit_id ? String(product.unit_id) : (units[0]?.id ?? '');

  if (unitSel) {
    unitSel.innerHTML = buildUnitOptions(units, selectedUnitId);
    if (selectedUnitId !== '') unitSel.value = String(selectedUnitId);
  }

  if (providerSel) {
    providerSel.innerHTML = buildProviderOptions(product.provider_id || '', product.provider_name || product.main_provider || '');
    if (product.provider_id) providerSel.value = String(product.provider_id);
  }

  if (purchase) purchase.value = product.current_purchase_price != null ? product.current_purchase_price : '';
  if (sale) sale.value = product.current_sale_price != null ? product.current_sale_price : '';
  if (expDate) expDate.value = product.last_expiration_date ? formatDateForInput(product.last_expiration_date) : '';
  if (ageRange) ageRange.value = product.last_age_range ? product.last_age_range : '';
}

async function loadUnitPrices() {
  const productId = document.getElementById('receptionProduct')?.value;
  const unitId    = document.getElementById('receptionUnit_1')?.value;
  const purchase  = document.getElementById('receptionPurchase_1');
  const sale      = document.getElementById('receptionSale_1');

  if (!productId || !unitId) return;

  const localProduct = findProductInStore(productId);

  if (localProduct) {
    const unitMatch = (localProduct.units || []).find(u => String(u.id) === String(unitId)) || null;
    const isDefaultUnit = localProduct.unit_id && String(localProduct.unit_id) === String(unitId);

    if (isDefaultUnit || unitMatch) {
      if (localProduct.current_purchase_price != null) purchase.value = localProduct.current_purchase_price;
      if (localProduct.current_sale_price != null) sale.value = localProduct.current_sale_price;
    }
    return;
  }

  try {
    const data = await ajaxRequest(`${BASE_URL}/stock/products/${productId}/defaults?unit_id=${unitId}`);
    if (purchase && data.purchase_price != null) purchase.value = data.purchase_price;
    if (sale && data.sale_price != null) sale.value = data.sale_price;
  } catch (e) {}
}

document.getElementById('receptionProduct')?.addEventListener('change', loadProductDefaults);
document.getElementById('receptionUnit_1')?.addEventListener('change', loadUnitPrices);

document.getElementById('receptionProductSearch')?.addEventListener('input', function() {
  clearTimeout(receptionSearchTimer);
  const q = this.value.trim();

  if (q.length < 1) {
    document.getElementById('receptionProductResults')?.classList.add('d-none');
    return;
  }

  receptionSearchTimer = setTimeout(() => searchReceptionProducts(q), 120);
});

function searchReceptionProducts(query) {
  const resultsBox = document.getElementById('receptionProductResults');
  if (!resultsBox) return;

  const q = query.toLowerCase();

  const items = stockStore.filter(p => {
    const haystack = `${p.name || ''} ${p.reference || ''} ${p.category || ''} ${p.main_provider || ''}`.toLowerCase();
    return haystack.includes(q);
  }).slice(0, 20);

  if (!items.length) {
    resultsBox.innerHTML = `<div class="search-results-item text-muted">Aucun produit trouvé</div>`;
    resultsBox.classList.remove('d-none');
    return;
  }

  resultsBox.innerHTML = items.map(item => `
    <div class="search-results-item" data-id="${item.id}" data-name="${(item.name || '').replace(/"/g, '&quot;')}">
      <div style="font-weight:600">${item.name}</div>
      <div style="font-size:12px;color:#64748b">${item.reference || ''}${item.category ? ' — ' + item.category : ''}</div>
    </div>
  `).join('');

  resultsBox.classList.remove('d-none');
}

document.addEventListener('click', function(e) {
  const item = e.target.closest('.search-results-item[data-id]');
  const box  = document.getElementById('receptionProductResults');
  const input = document.getElementById('receptionProductSearch');

  if (item) {
    document.getElementById('receptionProduct').value = item.dataset.id;
    if (input) input.value = item.dataset.name;
    box?.classList.add('d-none');
    loadProductDefaults();
    return;
  }

  if (box && input && !box.contains(e.target) && e.target !== input) {
    box.classList.add('d-none');
  }
});

document.addEventListener('click', function(e) {
  const editBtn = e.target.closest('.btn-edit-product');
  if (editBtn) {
    if (editBtn.dataset.canEdit !== '1') {
      showToast("Vous n'avez pas le droit de modifier un produit.", 'warning');
      return;
    }

    document.getElementById('editProductId').value   = editBtn.dataset.id;
    document.getElementById('editName').value        = editBtn.dataset.name || '';
    document.getElementById('editDescription').value = editBtn.dataset.description || '';
    document.getElementById('editCategory').value    = editBtn.dataset.categoryId || '';
    document.getElementById('editMinStock').value    = editBtn.dataset.minStock || 0;
    document.getElementById('editDosage').value      = editBtn.dataset.dosage || 0;
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
    return;
  }

  const delBtn = e.target.closest('.btn-delete-product');
  if (delBtn) {
    if (delBtn.dataset.canDelete !== '1') {
      showToast("Vous n'avez pas le droit de supprimer un produit.", 'warning');
      return;
    }

    const productId = delBtn.dataset.productId;
    const productName = delBtn.dataset.productName;

    Swal.fire({
      title: "Êtes-vous sûr ?",
      text: `Voulez-vous vraiment supprimer le produit "${productName}" ?`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Oui, supprimer",
      cancelButtonText: "Annuler"
      }).then((result) => {
       if (result.isConfirmed) {
        if (!navigator.onLine) {
          enqueue({ type: 'deleteProduct', id: productId, data: productId, url: `${BASE_URL}/stock/products/${productId}`, method: 'DELETE' });
          showToast("Suppression mise en file d'attente.", 'warning');
          return;
        }
        deleteProduct(productId, delBtn);
        Swal.fire({
          title: "Suppression lancée",
          text: "Le produit est en cours de suppression.",
          icon: "success",
          timer: 1500,
          showConfirmButton: false
        });
      }
    });


  }
});

async function deleteProduct(productId, btn) {
  try {
    setBtnLoading(btn, true);

    const res = await fetch(`${BASE_URL}/stock/products/${productId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': CSRF_TOKEN,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      }
    });

    const json = await res.json();
    if (!res.ok) throw new Error(json.message || 'Suppression refusée');

    removeProductFromStore(productId, json.stats || null);
    showToast(json.message || 'Produit supprimé avec succès.', 'success');
  } catch (err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    setBtnLoading(btn, false);
  }
}

document.getElementById('editProductForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  if (!CAN_EDIT) {
    showToast("Vous n'avez pas le droit de modifier un produit.", 'warning');
    return;
  }

  const btn       = document.getElementById('editProductSubmitBtn');
  const productId = document.getElementById('editProductId').value;
  const form      = this;

  if (!navigator.onLine) {
    const payload = {
      name:        form.querySelector('[name=name]').value,
      description: form.querySelector('[name=description]').value,
      category_id: form.querySelector('[name=category_id]').value,
      min_stock:   form.querySelector('[name=min_stock]').value,
      dosage:      form.querySelector('[name=dosage]').value,
    };

    enqueue({
      type: 'updateProduct',
      id: productId,
      data: payload,
      url: `${BASE_URL}/stock/products/${productId}`,
      method: 'PUT'
    });

    showToast("Modification mise en file d'attente.", 'warning');
    bootstrap.Modal.getInstance(document.getElementById('editProductModal'))?.hide();
    return;
  }

  setBtnLoading(btn, true);

  try {
    const fd = new FormData(form);
    fd.append('_method', 'PUT');

    const json = await ajaxRequest(`${BASE_URL}/stock/products/${productId}`, 'POST', fd, true);
    showToast(json.message, 'success');

    if (json.product) {
      upsertProductInStore(json.product, json.stats || null);
    }

    bootstrap.Modal.getInstance(document.getElementById('editProductModal'))?.hide();
    refreshStockFromServer({ silent: true });
  } catch(err) {
    showToast('Erreur : ' + err.message, 'error');
  } finally {
    setBtnLoading(btn, false);
  }
});

const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
document.addEventListener('click', async function(e) {
  const btn = e.target.closest('.btn-history');
  if (!btn) return;

  const productId   = btn.dataset.productId;
  const productName = btn.dataset.productName;

  document.getElementById('historyTitle').textContent = `Historique des prix — ${productName}`;
  document.getElementById('historyBody').innerHTML    = `<tr><td colspan="5" class="text-center">Chargement...</td></tr>`;
  historyModal.show();

  if (!navigator.onLine) {
    document.getElementById('historyBody').innerHTML = `<tr><td colspan="5" class="text-center text-warning">Historique non disponible hors-ligne.</td></tr>`;
    return;
  }

  try {
    const data = await ajaxRequest(`${BASE_URL}/stock/products/${productId}/history`);
    if (!data.length) {
      document.getElementById('historyBody').innerHTML = `<tr><td colspan="5" class="text-center">Aucun historique.</td></tr>`;
      return;
    }

    document.getElementById('historyBody').innerHTML = data.map(item => `
      <tr>
        <td>${item.unit ?? '—'}</td>
        <td>${fmt(item.purchase)} CDF</td>
        <td>${fmt(item.sale)} CDF</td>
        <td>${item.start_date ?? '—'}</td>
        <td>${item.end_date ?? '<span class="badge bg-success">Actif</span>'}</td>
      </tr>
    `).join('');
  } catch(err) {
    document.getElementById('historyBody').innerHTML = `<tr><td colspan="5" class="text-center text-danger">Erreur : ${err.message}</td></tr>`;
  }
});

const entriesModal = new bootstrap.Modal(document.getElementById('entriesModal'));
document.addEventListener('click', async function(e) {
  const btn = e.target.closest('.btn-stock-entries');
  if (!btn) return;

  const productId   = btn.dataset.productId;
  const productName = btn.dataset.productName;

  document.getElementById('entriesTitle').textContent = `Entrées du produit : ${productName}`;
  document.getElementById('entriesBody').innerHTML    = `<tr><td colspan="12" class="text-center">Chargement...</td></tr>`;
  document.getElementById('entriesTotal').textContent = '0';
  entriesModal.show();

  if (!navigator.onLine) {
    document.getElementById('entriesBody').innerHTML = `<tr><td colspan="12" class="text-center text-warning">Entrées non disponibles hors-ligne.</td></tr>`;
    return;
  }

  try {
    const data = await ajaxRequest(`${BASE_URL}/stock/products/${productId}/entries`);
    document.getElementById('entriesTotal').textContent = fmt(data.total_quantity || 0);

    if (!data.entries?.length) {
      document.getElementById('entriesBody').innerHTML = `<tr><td colspan="12" class="text-center">Aucune entrée trouvée.</td></tr>`;
      return;
    }

    document.getElementById('entriesBody').innerHTML = data.entries.map((entry, idx) => `
      <tr class="${entry.is_expired ? 'lot-expired-row' : ''}">
        <td>${idx + 1}</td>
        <td>${entry.unit ?? '—'}</td>
        <td>${fmt(entry.quantity)}</td>
        <td>${entry.batch_number ?? '—'}</td>
        <td>${entry.provider ?? '—'}</td>
        <td>${fmt(entry.purchase_price)} CDF</td>
        <td>${fmt(entry.sale_price)} CDF</td>
        <td>${fmt(entry.benefit)} CDF</td>
        <td>${entry.expiration_date ?? '—'}</td>
        <td>${entry.created_at ?? '—'}</td>
        <td>
          <span class="${entry.is_expired ? 'lot-expired-badge' : 'badge bg-success'}">
            ${entry.is_expired ? 'Périmé' : 'Actif'}
          </span>
        </td>
        <td>
          ${entry.is_expired && CAN_DELETE
            ? `<button class="btn btn-sm btn-danger btn-delete-entry" data-entry-id="${entry.id}">Supprimer</button>`
            : (entry.is_expired && !CAN_DELETE ? `<span class="text-muted">Droit manquant</span>` : '—')
          }
        </td>
      </tr>
    `).join('');
  } catch(err) {
    document.getElementById('entriesBody').innerHTML = `<tr><td colspan="12" class="text-center text-danger">Erreur : ${err.message}</td></tr>`;
  }
});

document.addEventListener('click', async function(e) {
  const btn = e.target.closest('.btn-delete-entry');
  if (!btn) return;

  if (!CAN_DELETE) {
    showToast("Vous n'avez pas le droit de supprimer un lot.", 'warning');
    return;
  }

  const entryId = btn.dataset.entryId;
  if (!confirm('Supprimer ce lot périmé ?')) return;

  try {
    const data = await ajaxRequest(`${BASE_URL}/stock/entries/${entryId}`, 'DELETE');
    if (data.success) {
      showToast(data.message, 'success');
      if (data.stats) stockStats = data.stats;
      refreshStockFromServer({ silent: true });
    } else {
      showToast(data.message, 'error');
    }
  } catch(err) {
    showToast('Erreur : ' + err.message, 'error');
  }
});

async function processQueue() {
  const queue = getQueue();
  if (!queue.length || !navigator.onLine) return;

  showToast(`Synchronisation de ${queue.length} opération(s)...`, 'info', 6000);

  let synced = 0, failed = 0;

  for (const op of queue) {
    try {
      const fd = new FormData();
      Object.entries(op.data || {}).forEach(([k, v]) => fd.append(k, v));

      if (op.type === 'updateProduct') fd.append('_method', 'PUT');
      if (op.type === 'deleteProduct') fd.append('_method', 'DELETE');

      await ajaxRequest(op.url, 'POST', fd, true);
      dequeue(op._qid);
      synced++;
    } catch(err) {
      failed++;
      console.error('Sync failed', op._qid, err);
    }
  }

  if (synced > 0) {
    showToast(`${synced} opération(s) synchronisée(s).`, 'success');
    refreshStockFromServer({ silent: true });
  }

  if (failed > 0) showToast(`${failed} opération(s) non synchronisée(s).`, 'error');
  updateSyncBanner();
}

document.getElementById('syncBanner')?.addEventListener('click', processQueue);

function handleOfflineStatus() {
  const offlineBanner = document.getElementById('offlineBanner');

  const addImagesField = document.getElementById('addImagesField');
  const addImagesOfflineMsg = document.getElementById('addImagesOfflineMsg');

  const editImagesField = document.getElementById('editImagesField');
  const editImagesOfflineMsg = document.getElementById('editImagesOfflineMsg');

  if (!navigator.onLine) {
    offlineBanner?.classList.add('show');

    addImagesField?.classList.add('d-none');
    addImagesOfflineMsg?.classList.remove('d-none');

    editImagesField?.classList.add('d-none');
    editImagesOfflineMsg?.classList.remove('d-none');
  } else {
    offlineBanner?.classList.remove('show');

    addImagesField?.classList.remove('d-none');
    addImagesOfflineMsg?.classList.add('d-none');

    editImagesField?.classList.remove('d-none');
    editImagesOfflineMsg?.classList.add('d-none');

    updateSyncBanner();
  }
}

window.addEventListener('online', async () => {
  handleOfflineStatus();
  showToast('Connexion Internet rétablie !', 'success');
  updateSyncBanner();
  setTimeout(() => { if (getQueue().length > 0) processQueue(); }, 1500);
  refreshStockFromServer({ silent: true });
});

window.addEventListener('offline', () => {
  handleOfflineStatus();
  showToast('Connexion Internet perdue. Mode hors-ligne activé.', 'warning', 6000);
});

async function initStockPage() {
  handleOfflineStatus();
  updateSyncBanner();

  const cached = loadStockCache();
  if (cached) {
    stockStore = (cached.products || []).map(normalizeProduct);
    stockStats = cached.stats || computeStatsFromStore(stockStore);
    renderCurrentView();

    if (navigator.onLine) {
      refreshStockFromServer({ silent: true });
    }
  } else if (navigator.onLine) {
    await refreshStockFromServer({ silent: true });
  } else {
    renderTable([]);
    renderPaginationLocal(1, 1);
    showToast('Aucune donnée en cache disponible hors-ligne.', 'warning');
  }
}

document.addEventListener('DOMContentLoaded', initStockPage);

// Remplace refreshStockFromServer par cette version
async function refreshStockFromServer({ silent = false } = {}) {
  if (!navigator.onLine) {
    const cache = loadStockCache();
    if (cache) {
      setStockStore(cache.products || [], cache.stats || null, true);
      return true;
    }
    return false;
  }

  const firstLoad = stockStore.length === 0;

  if (!silent && firstLoad) {
    showSyncLoader('Synchronisation des données en cours...');
  }

  try {
    // Demande le maximum possible au serveur
    const data = await ajaxRequest(`${BASE_URL}/stock/list-all?per_page=100`);
    setStockStore(data.products || [], data.stats || null, true);
    return true;
  } catch (e) {
    showToast('Erreur de chargement : ' + e.message, 'error');
    return false;
  } finally {
    if (!silent && firstLoad) {
      hideSyncLoader();
    }
  }
}


</script>

</body>
</html>
