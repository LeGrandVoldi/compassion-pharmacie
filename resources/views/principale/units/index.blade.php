@php
     $profileImage = session('profileImage');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Unités - Compassion Pharmacie</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=<?= time() ?>">

  @include('partials.header')

  <style>
    .section-card { background:white; border-radius:var(--radius); border:1px solid var(--border); box-shadow:var(--shadow-sm); overflow:hidden; }
    .filter-bar { display:flex; align-items:center; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
    .btn-add-user { background:var(--primary); color:white; border:none; border-radius:var(--radius-sm); padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; transition:all 0.2s; white-space:nowrap; }
    .btn-add-user:hover { background:var(--primary-dark); transform:translateY(-1px); }
    .btn-modifier { background:var(--primary); color:white; border:none; border-radius:var(--radius-sm); padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.2s; white-space:nowrap; }
    .btn-modifier:hover { background:var(--primary-dark); }
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
        <div class="page-icon"><i class="bi bi-box-seam"></i></div>
        <h1>Unités produit</h1>
      </div>

      @include('partials.profil')
    </header>

    <div class="page-content">
      <div class="section-card">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 border-bottom">
          <div>
            <h3 class="mb-0">Unités</h3>
            <div class="text-muted" style="font-size:13px">Ajout / modification</div>
          </div>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Ajouter
          </button>
        </div>

        @if(session('success'))
          <div class="alert alert-success m-3">{{ session('success') }}</div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger m-3">
            <ul class="mb-0">
              @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Nom</th>
                <th style="width:160px">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($units as $unit)
                <tr>
                  <td>{{ $unit->id }}</td>
                  <td style="font-weight:600">{{ $unit->name }}</td>
                  <td>
                    <button class="btn btn-sm btn-primary"
                            data-id="{{ $unit->id }}"
                            onclick="openEditUnit(this)">
                      <i class="bi bi-pencil"></i>
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-muted p-4">Aucune unité</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="p-3">
          {{ $units->links() }}
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CREATE -->
<div class="modal fade" id="createModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="{{ route('units.store') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Ajouter une unité</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Nom</label>
        <input class="form-control" name="name" required placeholder="Ex : box, blister, flacon...">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
        <button class="btn btn-success">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="editForm">
      @csrf
      @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title">Modifier l'unité</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Nom</label>
        <input class="form-control" name="name" id="editName" required>
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
async function openEditUnit(btn) {
  const id = btn.dataset.id;

  const res = await fetch(`{{ url('/units') }}/${id}/json`);
  const data = await res.json();

  document.getElementById('editName').value = data.name;
  document.getElementById('editForm').action = `{{ url('/units') }}/${id}`;

  const modal = new bootstrap.Modal(document.getElementById('editModal'));
  modal.show();
}
</script>
</body>
</html>
