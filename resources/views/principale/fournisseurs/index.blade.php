@php
     $profileImage = session('profileImage');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Fournisseurs - Compassion Pharmacie</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=<?= time() ?>">

  @include('partials.header')

  <style>
    .section-card { background:white; border-radius:var(--radius); border:1px solid var(--border); box-shadow:var(--shadow-sm); overflow:hidden; }
    .user-cell { display:flex; align-items:center; gap:10px; }
    .user-avatar { width:38px; height:38px; border-radius:50%; overflow:hidden; background:var(--primary-light); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .user-avatar img { width:100%; height:100%; object-fit:cover; }
    .user-cell-name { font-size:14px; font-weight:600; color:var(--text-dark); line-height:1.2; }
    .user-cell-sub { font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:4px; }
    .filter-bar { display:flex; align-items:center; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
    .filter-dropdown-btn { background:white; border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 14px; font-size:13px; font-weight:500; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; gap:6px; transition:all 0.2s; }
    .filter-dropdown-btn:hover { border-color:var(--primary); color:var(--primary); }
    .btn-add-user { background:var(--primary); color:white; border:none; border-radius:var(--radius-sm); padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; transition:all 0.2s; white-space:nowrap; }
    .btn-add-user:hover { background:var(--primary-dark); transform:translateY(-1px); }
    .btn-modifier { background:var(--primary); color:white; border:none; border-radius:var(--radius-sm); padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.2s; white-space:nowrap; }
    .btn-modifier:hover { background:var(--primary-dark); }
    .btn-suppr { background:var(--danger); color:white; border:none; border-radius:var(--radius-sm); padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.2s; white-space:nowrap; }
    .btn-suppr:hover { background:#dc2626; }
    .actions-cell { display:flex; gap:6px; align-items:center; }
    .pag-bar { display:flex; align-items:center; justify-content:space-between; padding:10px 16px; border-top:1px solid var(--border-light); flex-wrap:wrap; gap:8px; font-size:13px; color:var(--text-muted); }
    .no-results-row td { text-align:center; padding:20px 12px; }
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
        <div class="page-icon"><i class="bi bi-truck"></i></div>
        <h1>Fournisseurs</h1>
      </div>

      @include('partials.profil')
    </header>

    <div class="page-content">
      {{-- FILTRE JS UNIQUEMENT --}}
      <div class="section-card">

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 border-bottom">
            <div>
            <h3 class="mb-0">Fournisseurs</h3>
            <div class="text-muted" style="font-size:13px">Ajout / modification / suppression</div>
            </div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createProviderModal">
            <i class="bi bi-plus-lg"></i> Ajouter
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table">
            <thead class="table-light">
                <tr>
                <th>#</th>
                <th>Nom</th>
                <th>Téléphone</th>
                <th>Email</th>
                <th>Adresse</th>
                <th style="width:160px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($providers as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td style="font-weight:600">{{ $p->name }}</td>
                    <td>{{ $p->phone ?? '-' }}</td>
                    <td>{{ $p->email ?? '-' }}</td>
                    <td>{{ $p->address ?? '-' }}</td>
                    <td>
                    <button class="btn btn-sm btn-primary" data-id="{{ $p->id }}" onclick="openEditProvider(this)">
                        <i class="bi bi-pencil"></i>
                    </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted p-4">Aucun fournisseur</td></tr>
                @endforelse
            </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $providers->links() }}
        </div>

      </div>

    </div>
  </div>
</div>

<!-- MODAL CREATE -->
<div class="modal fade" id="createProviderModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="{{ route('fournisseurs.store') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Ajouter un fournisseur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <div class="mb-2">
          <label class="form-label">Nom</label>
          <input class="form-control" name="name" required>
        </div>

        <div class="mb-2">
          <label class="form-label">Téléphone</label>
          <input class="form-control" name="phone">
        </div>

        <div class="mb-2">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email">
        </div>

        <div class="mb-2">
          <label class="form-label">Adresse</label>
          <input class="form-control" name="address">
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
        <button class="btn btn-success">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="editProviderModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="editProviderForm">
      @csrf @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title">Modifier le fournisseur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <div class="mb-2">
          <label class="form-label">Nom</label>
          <input class="form-control" name="name" id="p_name" required>
        </div>

        <div class="mb-2">
          <label class="form-label">Téléphone</label>
          <input class="form-control" name="phone" id="p_phone">
        </div>

        <div class="mb-2">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" id="p_email">
        </div>

        <div class="mb-2">
          <label class="form-label">Adresse</label>
          <input class="form-control" name="address" id="p_address">
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
        <button class="btn btn-primary">Modifier</button>
      </div>
    </form>
  </div>
</div>
@include('partials.profil1')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=<?= time() ?>"></script>
<script>
async function openEditProvider(btn) {
  const id = btn.dataset.id;

  const res = await fetch(`{{ url('/fournisseurs') }}/${id}/json`);
  const data = await res.json();

  document.getElementById('p_name').value = data.name ?? '';
  document.getElementById('p_phone').value = data.phone ?? '';
  document.getElementById('p_email').value = data.email ?? '';
  document.getElementById('p_address').value = data.address ?? '';

  document.getElementById('editProviderForm').action = `{{ url('/fournisseurs') }}/${id}`;

  const modal = new bootstrap.Modal(document.getElementById('editProviderModal'));
  modal.show();
}
</script>
</body>
</html>
