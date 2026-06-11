@php
     $profileImage = session('profileImage');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Utilisateurs - Compassion Pharmacie</title>

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
        <div class="page-icon"><i class="bi bi-person-badge-fill"></i></div>
        <h1>Utilisateurs</h1>
      </div>

      @include('partials.profil')
    </header>

    <div class="page-content">
      {{-- FILTRE JS UNIQUEMENT --}}
      <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <div class="search-box flex-grow-1" style="min-width:200px">
          <i class="bi bi-search search-icon"></i>
          <input type="text"
                 class="form-input"
                 id="searchInput"
                 placeholder="Recherche...">
        </div>

        <select id="statusSelect" class="filter-dropdown-btn" style="height:42px;">
          <option value="all">Tous les statuts</option>
          <option value="active">Actif</option>
          <option value="inactive">Inactif</option>
        </select>

        <button type="button" id="resetFilters" class="btn-outline-custom" style="padding:8px 14px;font-size:13px">
          Réinitialiser
        </button>

        <button type="button" class="btn-add-user" data-bs-toggle="modal" data-bs-target="#addUserModal">
          <i class="bi bi-plus-lg"></i> Ajouter Utilisateur
        </button>
      </div>

      <div class="section-card">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 border-bottom">
            <div class="d-flex flex-wrap gap-2">
            <span class="badge border bg-light text-dark">
                Total utilisateurs : <span id="totalUsersCount">{{ $paginated->total() }}</span>
            </span>

            <span class="badge border bg-light text-dark">
                Actifs : <span id="activeUsersCount">
                {{ $paginated->getCollection()->filter(fn ($u) => $u->status_label === 'Actif')->count() }}
                </span>
            </span>

            <span class="badge border bg-light text-dark">
                Inactifs : <span id="inactiveUsersCount">
                {{ $paginated->getCollection()->filter(fn ($u) => $u->status_label === 'Inactif')->count() }}
                </span>
            </span>

            <span class="badge border bg-light text-dark">
                Affichés : <span id="visibleUsersCount">{{ $paginated->count() }}</span>
            </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table" id="userTable">
            <thead>
                <tr>
                <th style="width:40px">#</th>
                <th>Utilisateur</th>
                <th>Date de Création</th>
                <th>Statut</th>
                <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($paginated as $user)
                <tr data-user-row="1"
                    data-status="{{ $user->status_label === 'Actif' ? 'active' : 'inactive' }}"
                    data-search="{{ $user->name }} {{ $user->email }}">
                    <td>{{ $paginated->firstItem() + $loop->index }}</td>

                    <td>
                    <div class="user-cell">
                        <div class="user-avatar">
                        <i class="bi bi-person-fill"></i>
                        </div>
                        <div>
                        <div class="user-cell-name">{{ $user->name }}</div>
                        <div class="user-cell-sub">{{ $user->email }}</div>
                        </div>
                    </div>
                    </td>

                    <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>

                    <td>
                    @if($user->status_label === 'Inactif')
                        <span class="badge-status badge-inactif">Inactif</span>
                    @else
                        <span class="badge-status badge-actif">Actif</span>
                    @endif
                    </td>

                    <td>
                    <div class="actions-cell">
                        <button class="btn-modifier" onclick="ouvrirModification(this, {{ $user->id }})">Modifier</button>
                        {{-- <button class="btn-suppr" onclick="supprimerUtilisateur({{ $user->id }})">Supprimer</button> --}}
                    </div>
                    </td>
                </tr>
                @empty
                <tr class="no-results-row" id="initialEmptyRow">
                    <td colspan="5">Aucun utilisateur trouvé.</td>
                </tr>
                @endforelse

                <tr class="no-results-row" id="noResultsRow" style="display:none;">
                <td colspan="5">Aucun utilisateur ne correspond à votre recherche.</td>
                </tr>
            </tbody>
            </table>
        </div>

        <div class="pag-bar">
            <div style="display:flex;align-items:center;gap:8px">
            <i class="bi bi-file-earmark"></i> 10 / page
            </div>

            <div>
            {{ $paginated->links('pagination::bootstrap-5') }}
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- MODAL AJOUT --}}
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-person-plus-fill"></i> Ajouter un utilisateur
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="addUserForm">
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Nom complet</label>
              <input type="text" class="form-control" id="nom">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">Fonction</label>
                <input type="text" class="form-control" id="role">
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Adresse Email</label>
              <input type="email" class="form-control" id="email">
            </div>

            <div class="col-md-6">
              <label class="form-label">Confirmer Email</label>
              <input type="email" class="form-control" id="confirmEmail">
            </div>
          </div>

          <div id="emailError" class="alert alert-danger" style="display:none;"></div>

          <hr>

          <h6 class="mb-3">Permissions de l'utilisateur</h6>

          <div class="mb-3">
            <button type="button" class="btn btn-success check-all-permissions">
              <i class="bi bi-check2-square"></i> Donner tous les droits
            </button>
          </div>

          @include('partials.permissions-fields')
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button class="btn btn-primary" id="saveUserBtn">Ajouter l'utilisateur</button>
      </div>
    </div>
  </div>
</div>

{{-- MODAL MODIFICATION --}}
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-pencil-square"></i> Modifier un utilisateur
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="editLoadingBox" class="text-center py-5" style="display:none;">
          <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;">
            <span class="visually-hidden">Chargement...</span>
          </div>
          <div class="mt-3 fw-semibold">Chargement des informations...</div>
        </div>

        <form id="editUserForm">
          <input type="hidden" id="edit_user_id">

          <div id="editFormContent">
            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Nom complet</label>
                <input type="text" class="form-control" id="edit_nom">
              </div>
            </div>
            <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">Fonction</label>
                <input type="text" class="form-control" id="edit_role">
            </div>
            </div>

            <div class="row mb-3" hidden>
              <div class="col-md-12">
                <label class="form-label">Adresse Email</label>
                <input type="email" class="form-control" id="edit_email" readonly>
              </div>
            </div>

            <hr>

            <h6 class="mb-3">Permissions de l'utilisateur</h6>

            <div class="mb-3">
              <button type="button" class="btn btn-success check-all-permissions">
                <i class="bi bi-check2-square"></i> Donner tous les droits
              </button>
            </div>

            @include('partials.permissions-fields', ['selectedPermissions' => []])
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button class="btn btn-primary" id="updateUserBtn">Enregistrer</button>
      </div>

    </div>
  </div>
</div>
@include('partials.profil1')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=<?= time() ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const statusSelect = document.getElementById('statusSelect');
    const resetBtn = document.getElementById('resetFilters');

    const totalUsersCount = document.getElementById('totalUsersCount');
    const activeUsersCount = document.getElementById('activeUsersCount');
    const inactiveUsersCount = document.getElementById('inactiveUsersCount');
    const visibleUsersCount = document.getElementById('visibleUsersCount');

    const dataRows = document.querySelectorAll('#userTable tbody tr[data-user-row="1"]');
    const noResultsRow = document.getElementById('noResultsRow');
    const initialEmptyRow = document.getElementById('initialEmptyRow');

    function applyFilters() {
        const searchValue = searchInput.value.trim().toLowerCase();
        const statusValue = statusSelect.value;

        let visibleCount = 0;
        let activeCount = 0;
        let inactiveCount = 0;

        dataRows.forEach(row => {
        const rowSearch = (row.dataset.search || '').toLowerCase();
        const rowStatus = row.dataset.status || 'all';

        const matchSearch = rowSearch.includes(searchValue);
        const matchStatus = statusValue === 'all' || rowStatus === statusValue;

        const show = matchSearch && matchStatus;
        row.style.display = show ? '' : 'none';

        if (rowStatus === 'active') activeCount++;
        if (rowStatus === 'inactive') inactiveCount++;

        if (show) visibleCount++;
        });

        if (visibleUsersCount) visibleUsersCount.textContent = visibleCount;
        if (activeUsersCount) activeUsersCount.textContent = activeCount;
        if (inactiveUsersCount) inactiveUsersCount.textContent = inactiveCount;

        if (totalUsersCount) {
        if (statusValue === 'active') {
            totalUsersCount.textContent = activeCount;
        } else if (statusValue === 'inactive') {
            totalUsersCount.textContent = inactiveCount;
        } else {
            totalUsersCount.textContent = {{ $paginated->total() }};
        }
        }

        if (noResultsRow) {
        noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
        }

        if (initialEmptyRow) {
        initialEmptyRow.style.display = dataRows.length === 0 ? '' : 'none';
        }
    }

    searchInput.addEventListener('input', applyFilters);
    statusSelect.addEventListener('change', applyFilters);

    resetBtn.addEventListener('click', function () {
        searchInput.value = '';
        statusSelect.value = 'all';
        applyFilters();
        searchInput.focus();
    });

    applyFilters();
    });
</script>

<script>
  const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
  const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
  const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));

  document.querySelectorAll('.check-all-permissions').forEach(btn => {
        btn.addEventListener('click', function () {

            const modal = this.closest('.modal');
            const checkboxes = modal.querySelectorAll('input[name="permissions[]"]');

            // Vérifie si toutes les cases sont déjà cochées
            const allChecked = [...checkboxes].every(cb => cb.checked);

            // Inverse l'état
            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
            });

        });
  });

  document.getElementById('saveUserBtn').addEventListener('click', async function () {
    const nom = document.getElementById('nom').value.trim();
    const email = document.getElementById('email').value.trim();
    const confirmEmail = document.getElementById('confirmEmail').value.trim();
    const role = document.getElementById('role').value.trim();

    if (!nom || !email || !confirmEmail || !role) {
        Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            }).fire({
            icon: "info",
            title: "Remplis tous les champs."
        });
      return;
    }

    if (email !== confirmEmail) {
        Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            }).fire({
            icon: "error",
            title: "Les adresses email ne correspondent pas."
        });
      return;
    }

    const permissions = [];
    document.querySelectorAll('#addUserModal input[name="permissions[]"]:checked').forEach(cb => permissions.push(cb.value));

    const btn = this;
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement...';

    try {
      const response = await fetch('{{ route("users.store") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ nom, role, email, permissions })
      });

      const data = await response.json();

      if (data.status) {
        Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            }).fire({
            icon: "success",
            title: data.message
        });
        location.reload();
      } else {
         Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            }).fire({
            icon: "error",
            title: data.message || 'Erreur.'
        });

      }
    } catch (error) {
        Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            }).fire({
            icon: "error",
            title: "Erreur serveur"
        });
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  });

  async function ouvrirModification(btn, id) {
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Chargement...';

    const loadingBox = document.getElementById('editLoadingBox');
    const formContent = document.getElementById('editFormContent');

    if (loadingBox) loadingBox.style.display = 'block';
    if (formContent) formContent.style.display = 'none';

    editUserModal.show();

    try {
      const response = await fetch(`/utilisateurs/${id}/json`, {
        headers: { 'Accept': 'application/json' }
      });

      const data = await response.json();

      document.getElementById('edit_user_id').value = data.id;
      document.getElementById('edit_nom').value = data.name;
      document.getElementById('edit_email').value = data.email;
      document.getElementById('edit_role').value = data.role;

      const permissions = data.permissions || [];

      document.querySelectorAll('#editUserModal input[name="permissions[]"]').forEach(cb => {
        cb.checked = permissions.includes(cb.value);
      });

      if (loadingBox) loadingBox.style.display = 'none';
      if (formContent) formContent.style.display = 'block';
    } catch (error) {
        Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            }).fire({
            icon: "error",
            title: "Impossible de charger les données de l’utilisateur."
        });
      editUserModal.hide();
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  }

  document.getElementById('updateUserBtn').addEventListener('click', async function () {
    const id = document.getElementById('edit_user_id').value;
    const nom = document.getElementById('edit_nom').value.trim();
    const role = document.getElementById('edit_role').value.trim();

    if (!nom) {
        Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            }).fire({
            icon: "info",
            title: "Le nom est obligatoire."
        });
      return;
    }

    const permissions = [];
    document.querySelectorAll('#editUserModal input[name="permissions[]"]:checked').forEach(cb => permissions.push(cb.value));

    const btn = this;
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sauvegarde...';

    try {
      const response = await fetch(`/utilisateurs/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ nom,role, permissions })
      });

      const data = await response.json();

      if (data.status) {
        Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            }).fire({
            icon: "success",
            title: data.message
        });
        location.reload();
      } else {
        Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            }).fire({
            icon: "error",
            title: data.message || 'Erreur.'
        });
      }
    } catch (error) {
        Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            }).fire({
            icon: "error",
            title: "Erreur serveur"
        });

    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  });

  async function supprimerUtilisateur(id) {
    const result = await Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Tu es sûr de vouloir supprimer cet utilisateur ?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Oui, supprime-le !",
        cancelButtonText: "Annuler"
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(`/utilisateurs/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.status) {

            await Swal.fire({
                title: "Supprimé !",
                text: data.message || "Utilisateur supprimé avec succès.",
                icon: "success",
                confirmButtonColor: "#3085d6"
            });

            location.reload();

        } else {

            Swal.fire({
                title: "Erreur",
                text: data.message || "Une erreur est survenue.",
                icon: "error",
                confirmButtonColor: "#3085d6"
            });

        }

    } catch (error) {

        Swal.fire({
            title: "Erreur serveur",
            text: "Une erreur est survenue lors de la suppression.",
            icon: "error",
            confirmButtonColor: "#3085d6"
        });

    }

  }

  // FILTRE JS UNIQUEMENT, SANS CONTROLLER
  document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const statusSelect = document.getElementById('statusSelect');
    const resetBtn = document.getElementById('resetFilters');
    const dataRows = document.querySelectorAll('#userTable tbody tr[data-user-row="1"]');
    const noResultsRow = document.getElementById('noResultsRow');
    const initialEmptyRow = document.getElementById('initialEmptyRow');

    function applyFilters() {
      const searchValue = searchInput.value.trim().toLowerCase();
      const statusValue = statusSelect.value;

      let visibleCount = 0;

      dataRows.forEach(row => {
        const rowSearch = (row.dataset.search || '').toLowerCase();
        const rowStatus = row.dataset.status || 'all';

        const matchSearch = rowSearch.includes(searchValue);
        const matchStatus = (statusValue === 'all') || (rowStatus === statusValue);

        const show = matchSearch && matchStatus;
        row.style.display = show ? '' : 'none';

        if (show) visibleCount++;
      });

      if (noResultsRow) {
        noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
      }

      if (initialEmptyRow) {
        initialEmptyRow.style.display = dataRows.length === 0 ? '' : 'none';
      }
    }

    searchInput.addEventListener('input', applyFilters);
    statusSelect.addEventListener('change', applyFilters);

    resetBtn.addEventListener('click', function () {
      searchInput.value = '';
      statusSelect.value = 'all';
      applyFilters();
      searchInput.focus();
    });

    applyFilters();
  });
</script>
</body>
</html>
