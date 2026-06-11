<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rapport journalier des ventes</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    :root{
      --primary:#2563eb;
      --success:#16a34a;
      --warning:#f59e0b;
      --danger:#dc2626;
      --text-dark:#0f172a;
      --text-muted:#64748b;
      --border:#e2e8f0;
      --bg:#f8fafc;
      --radius:18px;
      --shadow:0 10px 30px rgba(15,23,42,.08);
    }

    *{box-sizing:border-box}
    body{
      background:var(--bg);
      color:var(--text-dark);
      font-family:Arial, Helvetica, sans-serif;
      padding:24px;
    }

    .report-shell{
      max-width:1200px;
      margin:0 auto;
      background:#fff;
      border:1px solid var(--border);
      border-radius:24px;
      box-shadow:var(--shadow);
      overflow:hidden;
    }

    .report-header{
      padding:24px 28px;
      border-bottom:1px solid var(--border);
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:20px;
      flex-wrap:wrap;
      background:linear-gradient(135deg,#fff,#f8fbff);
    }

    .brand{
      display:flex;
      align-items:center;
      gap:16px;
    }

    .brand img{
      max-height:72px;
      object-fit:contain;
    }

    .brand h1{
      font-size:22px;
      margin:0;
      font-weight:800;
      color:var(--text-dark);
    }

    .brand .sub{
      font-size:13px;
      color:var(--text-muted);
      margin-top:4px;
    }

    .report-meta{
      text-align:right;
      font-size:14px;
      color:var(--text-muted);
    }

    .report-meta strong{
      color:var(--text-dark);
    }

    .toolbar{
      padding:16px 28px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      flex-wrap:wrap;
      border-bottom:1px solid var(--border);
      background:#fff;
    }

    .btn-soft{
      border:1px solid var(--border);
      background:#fff;
      border-radius:12px;
      padding:10px 16px;
      font-weight:600;
      color:var(--text-dark);
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap:8px;
      transition:.2s;
    }
    .btn-soft:hover{
      border-color:var(--primary);
      color:var(--primary);
      transform:translateY(-1px);
    }

    .stats-grid{
      display:grid;
      grid-template-columns:repeat(4,1fr);
      gap:16px;
      padding:20px 28px 10px;
    }

    .stat-card{
      border:1px solid var(--border);
      border-radius:18px;
      padding:18px;
      background:#fff;
      box-shadow:0 6px 18px rgba(15,23,42,.04);
    }

    .stat-label{
      font-size:13px;
      color:var(--text-muted);
      margin-bottom:8px;
    }

    .stat-value{
      font-size:24px;
      font-weight:800;
      line-height:1.1;
    }

    .stat-value.primary{ color:var(--primary); }
    .stat-value.success{ color:var(--success); }
    .stat-value.warning{ color:var(--warning); }
    .stat-value.danger{ color:var(--danger); }

    .table-wrap{
      padding:18px 28px 28px;
    }

    table{
      width:100%;
      border-collapse:collapse;
      background:#fff;
    }

    thead th{
      background:#f8fafc;
      color:var(--text-dark);
      font-size:13px;
      font-weight:700;
      padding:12px 10px;
      border:1px solid var(--border);
      text-align:left;
      vertical-align:middle;
    }

    tbody td{
      font-size:13px;
      padding:12px 10px;
      border:1px solid var(--border);
      vertical-align:top;
    }

    tfoot td{
      font-size:14px;
      font-weight:800;
      padding:12px 10px;
      border:1px solid var(--border);
      background:#f8fafc;
    }

    .badge-soft{
      display:inline-block;
      padding:5px 10px;
      border-radius:999px;
      font-size:11px;
      font-weight:700;
    }
    .badge-paid{ background:#dcfce7; color:#166534; }
    .badge-unpaid{ background:#fef3c7; color:#92400e; }

    .sale-items{
      display:flex;
      flex-direction:column;
      gap:6px;
    }

    .sale-item{
      display:flex;
      justify-content:space-between;
      gap:12px;
      border-bottom:1px dashed #e5e7eb;
      padding-bottom:6px;
    }

    .sale-item:last-child{
      border-bottom:none;
      padding-bottom:0;
    }

    .sale-item .name{
      font-weight:600;
      color:var(--text-dark);
    }

    .sale-item .meta{
      color:var(--text-muted);
      font-size:12px;
    }

    .footer-note{
      padding:0 28px 24px;
      font-size:12px;
      color:var(--text-muted);
    }

    @media print{
      body{
        background:#fff;
        padding:0;
      }
      .report-shell{
        box-shadow:none;
        border:none;
        border-radius:0;
      }
      .toolbar{
        display:none !important;
      }
      .btn-soft{
        display:none !important;
      }
      .report-header{
        border-bottom:1px solid #ccc;
      }
      .stats-grid{
        gap:10px;
      }
      .stat-card, table, thead th, tbody td, tfoot td{
        border-color:#bbb !important;
      }
      .footer-note{
        display:block;
      }
      @page{
        size:A4;
        margin:12mm;
      }
    }
  </style>
</head>
<body>

<div class="report-shell">
  <div class="report-header">
    <div class="brand">
      <img src="{{ asset('Imgs/Logos/logo_full.png') }}" alt="Logo">
      <div>
        <h1>Rapport journalier des ventes</h1>
        <div class="sub">Compassion Pharmacie</div>
      </div>
    </div>

    <div class="report-meta">
      <div><strong>Date :</strong> {{ now()->format('d/m/Y') }}</div>
      <div><strong>Utilisateur :</strong> {{ session('nom') ?? '—' }}</div>
      @if(!empty($userFilter) && $userFilter !== 'all')
        <div><strong>Filtre :</strong> {{ $sales->first()?->user?->name ?? 'Utilisateur filtré' }}</div>
      @endif
    </div>
  </div>

  <div class="toolbar">
    <div class="text-muted">
      Total des ventes et détail des factures du jour
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="javascript:window.print()" class="btn-soft">
        <i class="bi bi-printer"></i> Imprimer
      </a>
      <a href="{{ url()->previous() }}" class="btn-soft">
        <i class="bi bi-arrow-left"></i> Retour
      </a>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Ventes du jour</div>
      <div class="stat-value primary">{{ number_format($stats['total_sales'] ?? 0, 0, ',', ' ') }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Montant total</div>
      <div class="stat-value success">{{ number_format($stats['total_amount'] ?? 0, 0, ',', ' ') }} CDF</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Produit le plus vendu</div>
      <div class="stat-value warning" style="font-size:18px">{{ $stats['top_product'] ?? '—' }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Produit le moins vendu</div>
      <div class="stat-value danger" style="font-size:18px">{{ $stats['low_product'] ?? '—' }}</div>
    </div>
  </div>

  <div class="table-wrap">
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Vendeur</th>
            <th>Client</th>
            <th>Produits vendus</th>
            <th>Paiement</th>
            <th>Statut</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse($sales as $sale)
            <tr>
              <td>{{ $sale->id }}</td>
              <td>{{ $sale->created_at?->format('d/m/Y H:i') }}</td>
              <td>{{ $sale->user?->name ?? '—' }}</td>
              <td>{{ $sale->client_number ?? '—' }}</td>
              <td>
                <div class="sale-items">
                  @foreach($sale->items as $item)
                    <div class="sale-item">
                      <div>
                        <div class="name">{{ $item->product?->name ?? '—' }}</div>
                        <div class="meta">{{ $item->unit?->name ?? '—' }} • Qté: {{ $item->quantity }}</div>
                      </div>
                      <div class="meta">
                        {{ number_format($item->total, 0, ',', ' ') }} CDF
                      </div>
                    </div>
                  @endforeach
                </div>
              </td>
              <td>{{ ucfirst($sale->payment_type) }}</td>
              <td>
                <span class="badge-soft {{ $sale->status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                  {{ $sale->status === 'paid' ? 'Payée' : 'Non payée' }}
                </span>
              </td>
              <td><strong>{{ number_format($sale->total_amount, 0, ',', ' ') }} CDF</strong></td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4">Aucune vente à afficher pour ce jour.</td>
            </tr>
          @endforelse
        </tbody>
        <tfoot>
          <tr>
            <td colspan="7" class="text-end">TOTAL GÉNÉRAL</td>
            <td>{{ number_format($stats['total_amount'] ?? 0, 0, ',', ' ') }} CDF</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="footer-note">
    Rapport généré automatiquement le {{ now()->format('d/m/Y à H:i') }}.
  </div>
</div>

</body>
</html>
