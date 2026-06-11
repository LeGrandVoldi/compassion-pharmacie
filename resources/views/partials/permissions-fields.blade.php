@php
    $prefix = $prefix ?? '';
@endphp

<div class="card mb-3">
    <div class="card-header fw-bold">Commandes</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="commandes_ajouter" {{ in_array('commandes_ajouter', $selectedPermissions ?? []) ? 'checked' : '' }}> Ajouter</label><br>
        <label><input type="checkbox" name="permissions[]" value="commandes_modifier" {{ in_array('commandes_modifier', $selectedPermissions ?? []) ? 'checked' : '' }}> Modifier</label>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-bold">Stock</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="stock_ajouter" {{ in_array('stock_ajouter', $selectedPermissions ?? []) ? 'checked' : '' }}> Ajouter</label><br>
        <label><input type="checkbox" name="permissions[]" value="stock_modifier" {{ in_array('stock_modifier', $selectedPermissions ?? []) ? 'checked' : '' }}> Modifier</label><br>
        <label><input type="checkbox" name="permissions[]" value="stock_supprimer" {{ in_array('stock_supprimer', $selectedPermissions ?? []) ? 'checked' : '' }}> Supprimer</label>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-bold">Ventes</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="ventes_ajouter" {{ in_array('ventes_ajouter', $selectedPermissions ?? []) ? 'checked' : '' }}> Ajouter</label><br>
        <label><input type="checkbox" name="permissions[]" value="ventes_modifier" {{ in_array('ventes_modifier', $selectedPermissions ?? []) ? 'checked' : '' }}> Modifier</label><br>
        <label><input type="checkbox" name="permissions[]" value="ventes_supprimer" {{ in_array('ventes_supprimer', $selectedPermissions ?? []) ? 'checked' : '' }}> Supprimer</label>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-bold">Clients</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="clients_ajouter" {{ in_array('clients_ajouter', $selectedPermissions ?? []) ? 'checked' : '' }}> Ajouter</label><br>
        <label><input type="checkbox" name="permissions[]" value="clients_modifier" {{ in_array('clients_modifier', $selectedPermissions ?? []) ? 'checked' : '' }}> Modifier</label><br>
        <label><input type="checkbox" name="permissions[]" value="clients_supprimer" {{ in_array('clients_supprimer', $selectedPermissions ?? []) ? 'checked' : '' }}> Supprimer</label>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-bold">Partenaires</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="partenaires_ajouter" {{ in_array('partenaires_ajouter', $selectedPermissions ?? []) ? 'checked' : '' }}> Ajouter</label><br>
        <label><input type="checkbox" name="permissions[]" value="partenaires_modifier" {{ in_array('partenaires_modifier', $selectedPermissions ?? []) ? 'checked' : '' }}> Modifier</label><br>
        <label><input type="checkbox" name="permissions[]" value="partenaires_supprimer" {{ in_array('partenaires_supprimer', $selectedPermissions ?? []) ? 'checked' : '' }}> Supprimer</label>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-bold">Fournisseurs</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="fournisseurs_ajouter" {{ in_array('fournisseurs_ajouter', $selectedPermissions ?? []) ? 'checked' : '' }}> Ajouter</label><br>
        <label><input type="checkbox" name="permissions[]" value="fournisseurs_modifier" {{ in_array('fournisseurs_modifier', $selectedPermissions ?? []) ? 'checked' : '' }}> Modifier</label><br>
        <label><input type="checkbox" name="permissions[]" value="fournisseurs_supprimer" {{ in_array('fournisseurs_supprimer', $selectedPermissions ?? []) ? 'checked' : '' }}> Supprimer</label>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-bold">Ordonnances</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="ordonnances_ajouter" {{ in_array('ordonnances_ajouter', $selectedPermissions ?? []) ? 'checked' : '' }}> Ajouter</label><br>
        <label><input type="checkbox" name="permissions[]" value="ordonnances_modifier" {{ in_array('ordonnances_modifier', $selectedPermissions ?? []) ? 'checked' : '' }}> Modifier</label><br>
        <label><input type="checkbox" name="permissions[]" value="ordonnances_supprimer" {{ in_array('ordonnances_supprimer', $selectedPermissions ?? []) ? 'checked' : '' }}> Supprimer</label>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-bold">Statistiques</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="statistiques_voir" {{ in_array('statistiques_voir', $selectedPermissions ?? []) ? 'checked' : '' }}> Voir</label>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-bold">Rapports</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="rapports_voir" {{ in_array('rapports_voir', $selectedPermissions ?? []) ? 'checked' : '' }}> Voir</label><br>
        <label><input type="checkbox" name="permissions[]" value="rapports_telecharger" {{ in_array('rapports_telecharger', $selectedPermissions ?? []) ? 'checked' : '' }}> Télécharger</label>
    </div>
</div>


<div class="card mb-3">
    <div class="card-header fw-bold">Catégories</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="categories_ajouter" {{ in_array('categories_ajouter', $selectedPermissions ?? []) ? 'checked' : '' }}> Ajouter</label><br>
        <label><input type="checkbox" name="permissions[]" value="categories_modifier" {{ in_array('categories_modifier', $selectedPermissions ?? []) ? 'checked' : '' }}> Modifier</label>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-bold">Paiements</div>
    <div class="card-body">
        <label><input type="checkbox" name="permissions[]" value="paiements_voir" {{ in_array('paiements_voir', $selectedPermissions ?? []) ? 'checked' : '' }}> Voir</label><br>
        <label><input type="checkbox" name="permissions[]" value="paiements_telecharger" {{ in_array('paiements_telecharger', $selectedPermissions ?? []) ? 'checked' : '' }}> Télécharger</label>
    </div>
</div>
