@php
    $isAdmin = session('role') === 'admin';
    $permissions = session('permissions', []);

    $moduleVisible = function (string $module) use ($isAdmin, $permissions) {
        if ($isAdmin) {
            return true;
        }

        if (!isset($permissions[$module])) {
            return false;
        }

        foreach ($permissions[$module] as $value) {
            if (!empty($value)) {
                return true;
            }
        }

        return false;
    };

    $permValue = function (string $module, string $key) use ($isAdmin, $permissions) {
        if ($isAdmin) {
            return 1;
        }

        return !empty($permissions[$module][$key]) ? 1 : 0;
    };
@endphp

<ul>
    <li>
        <a href="/dashboard" class="{{ request()->is('dashboard') ? 'active' : '' }}">
            <span class="nav-icon">
                <i class="bi bi-grid-fill"></i>
            </span>
            Tableau de Bord
        </a>
    </li>

    <!--@if($moduleVisible('commandes'))-->
    <!--<li>-->
    <!--    <a href="/commandes" class="{{ request()->is('commandes*') ? 'active' : '' }}">-->
    <!--        <span class="nav-icon">-->
    <!--            <i class="bi bi-cart-fill"></i>-->
    <!--        </span>-->
    <!--        Commandes-->
    <!--        <span class="nav-badge">5</span>-->
    <!--    </a>-->
    <!--    <span class="d-none"-->
    <!--          data-module="commandes"-->
    <!--          data-add="{{ $permValue('commandes', 'add') }}"-->
    <!--          data-edit="{{ $permValue('commandes', 'edit') }}"-->
    <!--          data-delete="{{ $permValue('commandes', 'delete') }}"></span>-->
    <!--</li>-->
    <!--@endif-->

    @if($moduleVisible('stock'))
    <li>
        <a href="/stock" class="{{ request()->is('stock*') ? 'active' : '' }}">
            <span class="nav-icon">
                <i class="bi bi-box-seam-fill"></i>
            </span>
            Stock
        </a>
        <span class="d-none"
              data-module="stock"
              data-add="{{ $permValue('stock', 'add') }}"
              data-edit="{{ $permValue('stock', 'edit') }}"
              data-delete="{{ $permValue('stock', 'delete') }}"></span>
    </li>
    @endif

    @if($moduleVisible('categories'))
    <li>
        <a href="/categories" class="{{ request()->is('categories*') ? 'active' : '' }}">
            <span class="nav-icon">
                <i class="bi bi-tags-fill"></i>
            </span>
            Catégories
        </a>
        <span class="d-none"
              data-module="categories"
              data-add="{{ $permValue('categories', 'add') }}"
              data-edit="{{ $permValue('categories', 'edit') }}"></span>
    </li>
    @endif

    @if($moduleVisible('units'))
    <li>
        <a href="/units" class="{{ request()->is('units*') ? 'active' : '' }}">
            <span class="nav-icon">
                <i class="bi bi-box-seam"></i>
            </span>
            Unités produit
        </a>
        <span class="d-none"
              data-module="units"
              data-add="{{ $permValue('categories', 'add') }}"
              data-edit="{{ $permValue('categories', 'edit') }}"></span>
    </li>
    @endif

    @if($moduleVisible('fournisseurs'))
    <li>
        <a href="/fournisseurs" class="{{ request()->is('fournisseurs*') ? 'active' : '' }}">
            <span class="nav-icon">
                <i class="bi bi-truck"></i>
            </span>
            Fournisseurs
        </a>
        <span class="d-none"
              data-module="fournisseurs"
              data-add="{{ $permValue('fournisseurs', 'add') }}"
              data-edit="{{ $permValue('fournisseurs', 'edit') }}"></span>
    </li>
    @endif

    <!--@if($moduleVisible('partenaires'))-->
    <!--<li>-->
    <!--    <a href="/partenaires" class="{{ request()->is('partenaires*') ? 'active' : '' }}">-->
    <!--        <span class="nav-icon">-->
    <!--            <i class="bi bi-building"></i>-->
    <!--        </span>-->
    <!--        Partenaires-->
    <!--    </a>-->
    <!--    <span class="d-none"-->
    <!--          data-module="partenaires"-->
    <!--          data-add="{{ $permValue('partenaires', 'add') }}"-->
    <!--          data-edit="{{ $permValue('partenaires', 'edit') }}"-->
    <!--          data-delete="{{ $permValue('partenaires', 'delete') }}"></span>-->
    <!--</li>-->
    <!--@endif-->

    @if($moduleVisible('ventes'))
    <li>
        <a href="/ventes" class="{{ request()->is('ventes*') ? 'active' : '' }}">
            <span class="nav-icon">
                <i class="bi bi-bar-chart-fill"></i>
            </span>
            Ventes
        </a>
        <span class="d-none"
              data-module="ventes"
              data-add="{{ $permValue('ventes', 'add') }}"
              data-edit="{{ $permValue('ventes', 'edit') }}"
              data-delete="{{ $permValue('ventes', 'delete') }}"></span>
    </li>
    @endif

    <!--@if($moduleVisible('paiements'))-->
    <!--<li>-->
    <!--    <a href="/paiements" class="{{ request()->is('paiements*') ? 'active' : '' }}">-->
    <!--        <span class="nav-icon">-->
    <!--            <i class="bi bi-credit-card-fill"></i>-->
    <!--        </span>-->
    <!--        Paiements-->
    <!--    </a>-->
    <!--    <span class="d-none"-->
    <!--          data-module="paiements"-->
    <!--          data-view="{{ $permValue('paiements', 'view') }}"-->
    <!--          data-download="{{ $permValue('paiements', 'download') }}"></span>-->
    <!--</li>-->
    <!--@endif-->

    @if($moduleVisible('clients'))
    <li>
        <a href="/clients" class="{{ request()->is('clients*') ? 'active' : '' }}">
            <span class="nav-icon">
                <i class="bi bi-people-fill"></i>
            </span>
            Clients
        </a>
        <span class="d-none"
              data-module="clients"
              data-add="{{ $permValue('clients', 'add') }}"
              data-edit="{{ $permValue('clients', 'edit') }}"
              data-delete="{{ $permValue('clients', 'delete') }}"></span>
    </li>
    @endif

    <!--@if($moduleVisible('ordonnances'))-->
    <!--<li>-->
    <!--    <a href="/ordonnances" class="{{ request()->is('ordonnances*') ? 'active' : '' }}">-->
    <!--        <span class="nav-icon">-->
    <!--            <i class="bi bi-file-medical-fill"></i>-->
    <!--        </span>-->
    <!--        Ordonnances-->
    <!--    </a>-->
    <!--    <span class="d-none"-->
    <!--          data-module="ordonnances"-->
    <!--          data-add="{{ $permValue('ordonnances', 'add') }}"-->
    <!--          data-edit="{{ $permValue('ordonnances', 'edit') }}"-->
    <!--          data-delete="{{ $permValue('ordonnances', 'delete') }}"></span>-->
    <!--</li>-->
    <!--@endif-->

    <!--@if($moduleVisible('statistiques'))-->
    <!--<li>-->
    <!--    <a href="/statistiques" class="{{ request()->is('statistiques*') ? 'active' : '' }}">-->
    <!--        <span class="nav-icon">-->
    <!--            <i class="bi bi-graph-up-arrow"></i>-->
    <!--        </span>-->
    <!--        Statistiques-->
    <!--    </a>-->
    <!--    <span class="d-none"-->
    <!--          data-module="statistiques"-->
    <!--          data-view="{{ $permValue('statistiques', 'view') }}"></span>-->
    <!--</li>-->
    <!--@endif-->

    <!--@if($moduleVisible('rapports'))-->
    <!--<li>-->
    <!--    <a href="/rapports" class="{{ request()->is('rapports*') ? 'active' : '' }}">-->
    <!--        <span class="nav-icon">-->
    <!--            <i class="bi bi-file-earmark-bar-graph-fill"></i>-->
    <!--        </span>-->
    <!--        Rapports-->
    <!--    </a>-->
    <!--    <span class="d-none"-->
    <!--          data-module="rapports"-->
    <!--          data-view="{{ $permValue('rapports', 'view') }}"-->
    <!--          data-download="{{ $permValue('rapports', 'download') }}"></span>-->
    <!--</li>-->
    <!--@endif-->

    @if($moduleVisible('utilisateurs'))
    <li>
        <a href="/utilisateurs" class="{{ request()->is('utilisateurs') || request()->is('utilisateurs/*') ? 'active' : '' }}">
            <span class="nav-icon">
                <i class="bi bi-people-fill"></i>
            </span>
            Utilisateurs
        </a>
        <span class="d-none"
              data-module="utilisateurs"
              data-add="{{ $permValue('utilisateurs', 'add') }}"
              data-edit="{{ $permValue('utilisateurs', 'edit') }}"
              data-delete="{{ $permValue('utilisateurs', 'delete') }}"></span>
    </li>
    @endif

    <!--@if($isAdmin)-->
    <!--<li>-->
    <!--    <a href="/parametres" class="{{ request()->is('parametres*') ? 'active' : '' }}">-->
    <!--        <span class="nav-icon">-->
    <!--            <i class="bi bi-gear-fill"></i>-->
    <!--        </span>-->
    <!--        Paramètres-->
    <!--    </a>-->
    <!--    <span class="d-none" data-module="parametres" data-all="1"></span>-->
    <!--</li>-->
    <!--@endif-->
</ul>
