<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Controller_login;
use App\Http\Controllers\Controller_dashboard;
use App\Http\Controllers\Controller_utilisateurs;
use App\Http\Controllers\Controller_commandes;
use App\Http\Controllers\Controller_stock;
use App\Http\Controllers\Controller_categories;
use App\Http\Controllers\Controller_fournisseurs;
use App\Http\Controllers\Controller_partenaires;
use App\Http\Controllers\Controller_ventes;
use App\Http\Controllers\Controller_paiements;
use App\Http\Controllers\Controller_clients;
use App\Http\Controllers\Controller_ordonnances;
use App\Http\Controllers\Controller_statistiques;
use App\Http\Controllers\Controller_rapports;
use App\Http\Controllers\Controller_utilisateurs_admin;
use App\Http\Controllers\Controller_parametres;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ProfileMediaController;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (session()->has('id')) {
        return redirect()->route('dashboard');
    }

    return app(Controller_login::class)->index();
})->name('login');

Route::get('/inscription', [Controller_login::class, 'inscription']);
Route::post('/register-user', [Controller_login::class, 'register']);
Route::post('/confirmEmail', [Controller_login::class, 'confirmEmail']);
Route::get('/confirmEmail', [Controller_login::class, 'confirmEmail1'])->name('confirmEmail1');
Route::post('/connexion', [Controller_login::class, 'connexion']);
Route::post('/validation-inscription', [Controller_login::class, 'validation_inscription']);
Route::get('/activation/{email}/{id}', [Controller_login::class, 'activationCompte']);
Route::post('/activation-compte', [Controller_login::class, 'enregistrerMotDePasse']);

/*
|--------------------------------------------------------------------------
| Mot de passe oublié
|--------------------------------------------------------------------------
*/

Route::get('/mot_de_passe_oublier', function () {
    return view('login.mot_de_passe_oublier');
})->name('mot_de_passe_oublier');

Route::post('/mot-de-passe-oublier', [Controller_login::class, 'mdpo']);
Route::get('/mot-de-passe-oublier', [Controller_login::class, 'mdpo_'])->name('mdpo_');
Route::post('/validation-inscription-mpo', [Controller_login::class, 'validation_inscription_mpo']);

/*
|--------------------------------------------------------------------------
| Routes protégées
|--------------------------------------------------------------------------
*/

Route::middleware(['session.timeout', 'refresh.permissions'])->group(function () {

    Route::get('/dashboard', [Controller_dashboard::class, 'index'])->name('dashboard');


    Route::get('/utilisateurs', [Controller_utilisateurs::class, 'index'])
        ->middleware('permission:utilisateurs')
        ->name('users.index');

    Route::post('/utilisateurs', [Controller_utilisateurs::class, 'store'])
        ->middleware('permission:utilisateurs')
        ->name('users.store');

    Route::get('/utilisateurs/{user}/json', [Controller_utilisateurs::class, 'showJson'])
        ->middleware('permission:utilisateurs')
        ->name('users.showJson');

    Route::put('/utilisateurs/{user}', [Controller_utilisateurs::class, 'update'])
        ->middleware('permission:utilisateurs')
        ->name('users.update');

    Route::delete('/utilisateurs/{user}', [Controller_utilisateurs::class, 'destroy'])
        ->middleware('permission:utilisateurs')
        ->name('users.destroy');

    Route::get('/commandes', [Controller_commandes::class, 'index'])
        ->middleware('permission:commandes')
        ->name('commandes');

    Route::get('/stock', [Controller_stock::class, 'index'])
        ->middleware('permission:stock')
        ->name('stock');

    Route::post('/stock/products', [Controller_stock::class, 'storeProduct'])
        ->middleware('permission:stock')
        ->name('stock.products.store');

    Route::put('/stock/products/{product}', [Controller_stock::class, 'updateProduct'])
        ->middleware('permission:stock')
        ->name('stock.products.update');

    Route::delete('/stock/products/{product}', [Controller_stock::class, 'destroyProduct'])
        ->middleware('permission:stock')
        ->name('stock.products.destroy');

    Route::post('/stock/receptions', [Controller_stock::class, 'storeReception'])
        ->middleware('permission:stock')
        ->name('stock.receptions.store');

    Route::get('/stock/products/{product}/defaults', [Controller_stock::class, 'productDefaults'])
        ->middleware('permission:stock')
        ->name('stock.products.defaults');

    Route::get('/stock/products/{product}/history', [Controller_stock::class, 'priceHistory'])
        ->middleware('permission:stock')
        ->name('stock.products.history');

    Route::get('/stock/products/{product}/entries', [Controller_stock::class, 'stockEntries'])
        ->middleware('permission:stock')
        ->name('stock.products.entries');

    Route::delete('/stock/entries/{stock}', [Controller_stock::class, 'destroyEntry'])
        ->middleware('permission:stock')
        ->name('stock.entries.destroy');

    Route::get('/stock/data', [Controller_stock::class, 'getData'])
        ->middleware('permission:stock')
        ->name('stock.data');

    Route::get('/stock/list', [Controller_stock::class, 'getList'])
        ->middleware('permission:stock')
        ->name('stock.list');

    Route::get('/stock/list-all', [Controller_stock::class, 'listAll'])
        ->middleware('permission:stock')
        ->name('stock.listAll');

    Route::get('/stock/products/search', [Controller_stock::class, 'searchProducts'])
        ->middleware('permission:stock')
        ->name('stock.products.search');

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    Route::get('/categories', [Controller_categories::class, 'index'])
        ->middleware('permission:categories')
        ->name('categories.index');

    Route::post('/categories', [Controller_categories::class, 'store'])
        ->middleware('permission:categories')
        ->name('categories.store');

    Route::get('/categories/{category}/json', [Controller_categories::class, 'showJson'])
        ->middleware('permission:categories')
        ->name('categories.json');

    Route::put('/categories/{category}', [Controller_categories::class, 'update'])
        ->middleware('permission:categories')
        ->name('categories.update');

    Route::delete('/categories/{category}', [Controller_categories::class, 'destroy'])
        ->middleware('permission:categories')
        ->name('categories.destroy');

    /*
    |--------------------------------------------------------------------------
    | Fournisseurs
    |--------------------------------------------------------------------------
    */

    Route::get('/fournisseurs', [Controller_fournisseurs::class, 'index'])
        ->middleware('permission:fournisseurs')
        ->name('fournisseurs.index');

    Route::post('/fournisseurs', [Controller_fournisseurs::class, 'store'])
        ->middleware('permission:fournisseurs')
        ->name('fournisseurs.store');

    Route::get('/fournisseurs/{provider}/json', [Controller_fournisseurs::class, 'showJson'])
        ->middleware('permission:fournisseurs')
        ->name('fournisseurs.json');

    Route::put('/fournisseurs/{provider}', [Controller_fournisseurs::class, 'update'])
        ->middleware('permission:fournisseurs')
        ->name('fournisseurs.update');

    Route::delete('/fournisseurs/{provider}', [Controller_fournisseurs::class, 'destroy'])
        ->middleware('permission:fournisseurs')
        ->name('fournisseurs.destroy');

    Route::get('/partenaires', [Controller_partenaires::class, 'index'])
        ->middleware('permission:partenaires')
        ->name('partenaires');


     // Ventes
    Route::get('/ventes', [Controller_ventes::class, 'index'])
        ->middleware('permission:ventes')
        ->name('ventes');

    Route::get('/ventes/list-all', [Controller_ventes::class, 'listAll'])
        ->middleware('permission:ventes')
        ->name('ventes.listAll');

    Route::get('/ventes/products/search', [Controller_ventes::class, 'searchProducts'])
        ->middleware('permission:ventes')
        ->name('ventes.products.search');

    Route::get('/ventes/clients/search', [Controller_ventes::class, 'searchClients'])
        ->middleware('permission:ventes')
        ->name('ventes.clients.search');

    Route::get('/ventes/products/{product}/defaults', [Controller_ventes::class, 'productDefaults'])
        ->middleware('permission:ventes')
        ->name('ventes.products.defaults');

    Route::post('/ventes', [Controller_ventes::class, 'storeSale'])
        ->middleware('permission:ventes')
        ->name('ventes.store');

    Route::put('/ventes/{sale}', [Controller_ventes::class, 'updateSale'])
        ->middleware('permission:ventes')
        ->name('ventes.update');

    Route::delete('/ventes/{sale}', [Controller_ventes::class, 'destroySale'])
        ->middleware('permission:ventes')
        ->name('ventes.destroy');

    Route::get('/ventes/report/daily', [Controller_ventes::class, 'dailyReport'])
        ->middleware('permission:ventes')
        ->name('ventes.report.daily');

    // Autres sorties
    Route::get('/sorties/list-all', [Controller_ventes::class, 'listAllOutgoings'])
        ->middleware('permission:ventes')
        ->name('sorties.listAll');

    Route::post('/sorties', [Controller_ventes::class, 'storeOutgoing'])
        ->middleware('permission:ventes')
        ->name('sorties.store');

    Route::put('/sorties/{outgoing}', [Controller_ventes::class, 'updateOutgoing'])
        ->middleware('permission:ventes')
        ->name('sorties.update');

    Route::delete('/sorties/{outgoing}', [Controller_ventes::class, 'destroyOutgoing'])
        ->middleware('permission:ventes')
        ->name('sorties.destroy');



    Route::get('/paiements', [Controller_paiements::class, 'index'])
        ->middleware('permission:paiements')
        ->name('paiements');

    Route::get('/clients', [Controller_clients::class, 'index'])
    ->middleware('permission:clients')
    ->name('clients');

    Route::get('/clients/list-all', [Controller_clients::class, 'listAll'])
        ->middleware('permission:clients')
        ->name('clients.listAll');

    Route::get('/clients/search', [Controller_clients::class, 'searchClients'])
        ->middleware('permission:clients')
        ->name('clients.search');

    Route::post('/clients', [Controller_clients::class, 'store'])
        ->middleware('permission:clients')
        ->name('clients.store');

    Route::put('/clients/{client}', [Controller_clients::class, 'update'])
        ->middleware('permission:clients')
        ->name('clients.update');

    Route::get('/clients/{client}/history', [Controller_clients::class, 'history'])
        ->middleware('permission:clients')
        ->name('clients.history');

    Route::get('/ordonnances', [Controller_ordonnances::class, 'index'])
        ->middleware('permission:ordonnances')
        ->name('ordonnances');

    Route::get('/statistiques', [Controller_statistiques::class, 'index'])
        ->middleware('permission:statistiques')
        ->name('statistiques');

    Route::get('/rapports', [Controller_rapports::class, 'index'])
        ->middleware('permission:rapports')
        ->name('rapports');

    Route::get('/utilisateurs-admin', [Controller_utilisateurs_admin::class, 'index'])
        ->name('utilisateurs.admin');

    Route::get('/parametres', [Controller_parametres::class, 'index'])
        ->name('parametres');

    Route::get('/units', [UnitController::class, 'index'])
        ->name('units');
    Route::get('/units/{unit}/json', [UnitController::class, 'json'])->name('units.json');
    Route::resource('units', UnitController::class)->except(['show', 'edit', 'create']);


    Route::post('/profil/image', [ProfileMediaController::class, 'update'])->name('profile.image.update');
    Route::get('/deconnexion', function () {
        session()->flush();
        return redirect()->route('login');
    })->name('deconnexion');
});
