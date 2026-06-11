@php
     $profileImage = session('profileImage');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de Bord - Compassion Pharmacie</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=<?= time() ?>">
  @include('partials.header')

  <style>
    .greeting { font-size:18px; font-weight:700; color:var(--text-dark); margin-bottom:20px; }
    .greeting span { font-size:18px; font-weight:400; color:var(--text-muted); }
    .section-card { background:white; border-radius:var(--radius); border:1px solid var(--border); box-shadow:var(--shadow-sm); }
    .section-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--border-light); }
    .section-header h3 { font-size:15px; font-weight:700; color:var(--text-dark); margin:0; }
    .btn-voir { background:white; border:1px solid var(--border); border-radius:var(--radius-sm); padding:5px 12px; font-size:12px; font-weight:500; color:var(--text-muted); cursor:pointer; transition:all 0.2s; white-space:nowrap; text-decoration:none; }
    .btn-voir:hover { border-color:var(--primary); color:var(--primary); background:var(--primary-light); }
    .chart-dropdown { padding:6px 28px 6px 10px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:13px; color:var(--text-dark); background:white; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2364748b'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 8px center; cursor:pointer; outline:none; }
    .tbl-num { font-weight:700; color:var(--primary); }
    .stock-row td:nth-child(2) { font-weight:700; }
    .dash-card {
      background:#fff;
      border:1px solid var(--border);
      border-radius:18px;
      box-shadow:var(--shadow-sm);
      padding:16px 18px;
      display:flex;
      gap:14px;
      align-items:center;
      transition:.2s;
    }
    .dash-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md);}
    .dash-icon{
      width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;
      color:#fff;font-size:20px;flex-shrink:0;
    }
    .dash-value{font-size:24px;font-weight:800;line-height:1.1;color:var(--text-dark);}
    .dash-label{font-size:12px;color:var(--text-muted);margin-top:2px;}
  </style>
  <style>
    .dash-card{
    display:flex;
    align-items:center;
    gap:12px;
    padding:15px;
    height:100%;
    overflow:hidden;
}

.dash-card > div:last-child{
    flex:1;
    min-width:0;
}

.dash-value{
    font-size:1.4rem;
    font-weight:700;
    line-height:1.2;
    word-break:break-word;
    overflow-wrap:break-word;
    white-space:normal;
}

.dash-label{
    font-size:.9rem;
    color:#6c757d;
    line-height:1.3;
    word-break:break-word;
}

.dash-icon{
    flex-shrink:0;
    width:52px;
    height:52px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
    color:#fff;
    font-size:1.4rem;
}

@media (max-width: 576px){

    .dash-card{
        padding:12px;
        gap:10px;
    }

    .dash-icon{
        width:45px;
        height:45px;
        font-size:1.2rem;
    }

    .dash-value{
        font-size:1rem;
    }

    .dash-label{
        font-size:.75rem;
    }
}

.dash-value{
    font-size:1.4rem;
    font-weight:700;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    font-size: 1rem;
        font-weight: 700;
}
  </style>
</head>
<body>

<div class="app-layout">
  <aside class="sidebar" id="sidebar">
    <div class="login-logo">
      <img src="{{ asset('Imgs/Logos/logo_full.png') }}" alt="" width="200" height="90">
    </div>
    <nav class="sidebar-nav">
      @include('partials.menu')
    </nav>
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
        <div class="page-icon"><i class="bi bi-grid-fill"></i></div>
        <h1>Tableau de Bord</h1>
      </div>
      @include('partials.profil')
    </header>

    <div class="page-content">
      <p class="greeting">
        Bonjour {{ $nom }}, <span>voici un aperçu personnalisé de votre activité</span>
      </p>

      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
          <div class="dash-card">
            <div class="dash-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
              <i class="bi bi-cart-check"></i>
            </div>
            <div>
              <div class="dash-value">{{ number_format($summary['sales_today'] ?? 0, 0, ',', ' ') }}</div>
              <div class="dash-label">Ventes aujourd'hui</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="dash-card">
            <div class="dash-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a)">
              <i class="bi bi-cash-coin"></i>
            </div>
            <div>
              <div class="dash-value">{{ number_format($summary['amount_today'] ?? 0, 0, ',', ' ') }} CDF</div>
              <div class="dash-label">Chiffre du jour</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="dash-card">
            <div class="dash-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
              <i class="bi bi-people"></i>
            </div>
            <div>
              <div class="dash-value">{{ number_format($summary['clients_today'] ?? 0, 0, ',', ' ') }}</div>
              <div class="dash-label">Clients servis</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="dash-card">
            <div class="dash-icon" style="background:linear-gradient(135deg,#a855f7,#7c3aed)">
              <i class="bi bi-box-seam"></i>
            </div>
            <div>
              <div class="dash-value">{{ number_format($summary['items_sold'] ?? 0, 0, ',', ' ') }}</div>
              <div class="dash-label">Produits vendus</div>
            </div>
          </div>
        </div>
      </div>

      @if($isAdmin)
      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
          <div class="dash-card">
            <div class="dash-icon" style="background:linear-gradient(135deg,#0ea5e9,#0284c7)">
              <i class="bi bi-person-badge"></i>
            </div>
            <div>
              <div class="dash-value">{{ number_format($summary['users_count'] ?? 0, 0, ',', ' ') }}</div>
              <div class="dash-label">Utilisateurs</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-4">
          <div class="dash-card">
            <div class="dash-icon" style="background:linear-gradient(135deg,#14b8a6,#0f766e)">
              <i class="bi bi-box"></i>
            </div>
            <div>
              <div class="dash-value">{{ number_format($summary['products_count'] ?? 0, 0, ',', ' ') }}</div>
              <div class="dash-label">Produits</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-4">
          <div class="dash-card">
            <div class="dash-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div>
              <div class="dash-value">{{ number_format($summary['low_stock_count'] ?? 0, 0, ',', ' ') }}</div>
              <div class="dash-label">Stock faible</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-4">
          <div class="dash-card">
            <div class="dash-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed)">
              <i class="bi bi-truck"></i>
            </div>
            <div>
              <div class="dash-value">{{ number_format($summary['suppliers_count'] ?? 0, 0, ',', ' ') }}</div>
              <div class="dash-label">Fournisseurs</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-4">
          <div class="dash-card">
            <div class="dash-icon" style="background:linear-gradient(135deg,#06b6d4,#0891b2)">
              <i class="bi bi-tags"></i>
            </div>
            <div>
              <div class="dash-value">{{ number_format($summary['categories_count'] ?? 0, 0, ',', ' ') }}</div>
              <div class="dash-label">Catégories</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-4">
          <div class="dash-card">
            <div class="dash-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a)">
              <i class="bi bi-people-fill"></i>
            </div>
            <div>
              <div class="dash-value">{{ number_format($summary['clients_count'] ?? 0, 0, ',', ' ') }}</div>
              <div class="dash-label">Clients</div>
            </div>
          </div>
        </div>
      </div>
      @endif

      <div class="row g-3 mb-4">
        <div class="col-12 {{ $isAdmin ? 'col-xl-7' : 'col-xl-8' }}">
          <div class="section-card">
            <div class="section-header">
              <h3>{{ $isAdmin ? 'Commandes Récentes' : 'Mes ventes récentes' }}</h3>
              <a href="{{ route('ventes') }}" class="btn-voir">Voir toutes</a>
            </div>
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Numéro</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($recentSales as $sale)
                    <tr>
                      <td class="tbl-num">#{{ str_pad($sale['id'], 5, '0', STR_PAD_LEFT) }}</td>
                      <td>{{ $sale['client_number'] }}</td>
                      <td>{{ $sale['created_at'] }}</td>
                      <td>
                        <span class="badge-status {{ $sale['status'] === 'paid' ? 'badge-terminee' : 'badge-en-attente' }}">
                          {{ $sale['status'] === 'paid' ? 'Payée' : 'Non payée' }}
                        </span>
                      </td>
                      <td>{{ number_format($sale['total_amount'], 0, ',', ' ') }} CDF</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center py-4">Aucune vente récente.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-12 {{ $isAdmin ? 'col-xl-5' : 'col-xl-4' }}">
          <div class="section-card">
            <div class="section-header">
              <h3>{{ $isAdmin ? 'Stock à Réapprovisionner' : 'Mes produits les plus vendus' }}</h3>
              <a href="{{ route('stock') }}" class="btn-voir">Voir tout</a>
            </div>

            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>{{ $isAdmin ? 'Produit' : 'Produit vendu' }}</th>
                    <th>{{ $isAdmin ? 'Stock Actuel' : 'Qté' }}</th>
                    <th>{{ $isAdmin ? 'Stock Min' : 'Total' }}</th>
                  </tr>
                </thead>
                <tbody class="stock-row">
                  @if($isAdmin)
                    @forelse($lowStockProducts as $product)
                      <tr>
                        <td>{{ $product->name }}</td>
                        <td style="color:var(--danger);font-weight:700">{{ number_format($product->available_stock, 0, ',', ' ') }}</td>
                        <td>{{ number_format($product->min_stock, 0, ',', ' ') }}</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="3" class="text-center py-4">Aucun stock faible.</td>
                      </tr>
                    @endforelse
                  @else
                    @forelse($topProducts as $product)
                      <tr>
                        <td>{{ $product['name'] }}</td>
                        <td style="color:var(--primary);font-weight:700">{{ number_format($product['qty'], 0, ',', ' ') }}</td>
                        <td>—</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="3" class="text-center py-4">Aucune donnée.</td>
                      </tr>
                    @endforelse
                  @endif
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="section-card p-0">
        <div class="section-header">
          <h3>{{ $isAdmin ? 'Statistiques de Ventes Globales' : 'Mes statistiques de ventes' }}</h3>
          <select class="chart-dropdown">
            <option>7 Derniers Jours</option>
            <option>30 Derniers Jours</option>
            <option>6 Derniers Mois</option>
            <option>Année en cours</option>
          </select>
        </div>
        <div style="padding:16px 20px 20px">
          <canvas id="salesChart" height="90"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

@include('partials.profil1')



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=<?= time() ?>"></script>
<script>
  const ctx = document.getElementById('salesChart').getContext('2d');

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: @json($chartLabels),
      datasets: [{
        label: 'Ventes',
        data: @json($chartValues),
        borderColor: '#22c55e',
        backgroundColor: 'rgba(34,197,94,0.08)',
        borderWidth: 2.5,
        fill: true,
        tension: 0.4,
        pointRadius: 5,
        pointBackgroundColor: '#22c55e',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 7
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          labels: {
            usePointStyle: true,
            font: { size: 13 },
            color: '#64748b'
          }
        }
      },
      scales: {
        y: {
          ticks: {
            font: { size: 12 },
            color: '#94a3b8'
          },
          grid: { color: 'rgba(0,0,0,0.04)' }
        },
        x: {
          ticks: {
            font: { size: 12 },
            color: '#94a3b8'
          },
          grid: { display: false }
        }
      }
    }
  });
</script>

</body>
</html>
