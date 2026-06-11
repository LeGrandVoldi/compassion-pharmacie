<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Controller_ventes extends Controller
{
    private function salesPermissions(): array
    {
        $isAdmin = session('role') === 'admin';
        $permissions = session('permissions', []);

        return [
            'is_admin' => $isAdmin,
            'view'     => $isAdmin ? true : !empty(data_get($permissions, 'ventes.view', 1)),
            'add'      => $isAdmin ? true : !empty(data_get($permissions, 'ventes.add')),
            'edit'     => $isAdmin ? true : !empty(data_get($permissions, 'ventes.edit')),
            'delete'   => $isAdmin ? true : !empty(data_get($permissions, 'ventes.delete')),
        ];
    }

    private function hasAnySalesPermission(): bool
    {
        $p = $this->salesPermissions();
        return $p['view'] || $p['add'] || $p['edit'] || $p['delete'];
    }

    private function canSales(string $ability): bool
    {
        $p = $this->salesPermissions();
        return (bool) ($p[$ability] ?? false);
    }

    private function denyJson(string $message, int $code = 403)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }

    private function currentMonthKey(): string
    {
        return now()->format('Y-m');
    }

    private function normalizeExpirationMonth(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format('Y-m');
        } catch (Throwable $e) {
            return null;
        }
    }

    private function salesQueryForDay(?string $userId = null)
    {
        $query = Sale::query()
            ->select([
                'id',
                'user_id',
                'client_number',
                'partner_id',
                'total_amount',
                'payment_type',
                'status',
                'extra_expense_amount',
                'extra_expense_description',
                'created_at',
                'updated_at',
            ])
            ->with([
                'items:id,sale_id,product_id,product_unit_id,quantity,price,purchase_price,total,created_at,updated_at',
                'items.product:id,name,reference,category_id,min_stock,description',
                'items.unit:id,product_id,name',
                'user:id,name',
            ])
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->orderByDesc('id');

        if (!$this->salesPermissions()['is_admin']) {
            $query->where('user_id', session('id'));
        } elseif ($userId && $userId !== 'all') {
            $query->where('user_id', (int) $userId);
        }

        return $query;
    }

    private function outflowsQueryForDay(?string $userId = null)
    {
        $query = DB::table('stock_outflows as so')
            ->leftJoin('users as u', 'so.user_id', '=', 'u.id')
            ->leftJoin('products as p', 'so.product_id', '=', 'p.id')
            ->leftJoin('product_units as pu', 'so.product_unit_id', '=', 'pu.id')
            ->whereBetween('so.created_at', [now()->startOfDay(), now()->endOfDay()])
            ->select([
                'so.id',
                'so.user_id',
                'u.name as user_name',
                'so.product_id',
                'p.name as product_name',
                'so.product_unit_id',
                'pu.name as unit_name',
                'so.quantity',
                'so.reason',
                'so.created_at',
                'so.updated_at',
            ])
            ->orderByDesc('so.id');

        if (!$this->salesPermissions()['is_admin']) {
            $query->where('so.user_id', session('id'));
        } elseif ($userId && $userId !== 'all') {
            $query->where('so.user_id', (int) $userId);
        }

        return $query;
    }

    private function activePriceRow(int $productId, int $unitId)
    {
        return DB::table('product_prices')
            ->where('product_id', $productId)
            ->where('product_unit_id', $unitId)
            ->whereNull('end_date')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    private function currentSalePriceForProductUnit(int $productId, int $unitId): array
    {
        $row = $this->activePriceRow($productId, $unitId);

        if (!$row) {
            return [0, 0, null];
        }

        return [
            (float) $row->price_purchase,
            (float) $row->price_sale,
            $row,
        ];
    }

    private function syncPriceOnSaleIfChanged(int $productId, int $unitId, float $newSalePrice): ?int
    {
        [$purchasePrice, $currentSalePrice, $row] = $this->currentSalePriceForProductUnit($productId, $unitId);

        if ($row && (float) $currentSalePrice === (float) $newSalePrice) {
            return (int) $row->id;
        }

        if ($row) {
            DB::table('product_prices')->where('id', $row->id)->update([
                'end_date' => now()->toDateString(),
                'updated_at' => now(),
            ]);
        }

        return DB::table('product_prices')->insertGetId([
            'product_id'      => $productId,
            'product_unit_id' => $unitId,
            'price_purchase'  => $purchasePrice,
            'price_sale'      => $newSalePrice,
            'start_date'      => now()->toDateString(),
            'end_date'        => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    private function availableStockQuery(int $productId, int $unitId)
    {
        return DB::table('product_stocks')
            ->where('product_id', $productId)
            ->where('product_unit_id', $unitId)
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                  ->orWhere('expiration_date', '>=', $this->currentMonthKey());
            })
            ->orderByRaw("CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END, expiration_date ASC, id ASC");
    }

    private function totalAvailableStock(int $productId, int $unitId): int
    {
        return (int) DB::table('product_stocks')
            ->where('product_id', $productId)
            ->where('product_unit_id', $unitId)
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                  ->orWhere('expiration_date', '>=', $this->currentMonthKey());
            })
            ->sum('quantity');
    }

    private function deductStock(int $productId, int $unitId, int $quantity): void
    {
        $remaining = $quantity;
        $rows = $this->availableStockQuery($productId, $unitId)->get();

        foreach ($rows as $row) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((int) $row->quantity, $remaining);
            $newQty = (int) $row->quantity - $take;
            $remaining -= $take;

            if ($newQty > 0) {
                DB::table('product_stocks')->where('id', $row->id)->update([
                    'quantity' => $newQty,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('product_stocks')->where('id', $row->id)->delete();
            }
        }

        if ($remaining > 0) {
            throw new \RuntimeException("Stock insuffisant pour le produit #{$productId}.");
        }
    }

    private function resolveFallbackProviderId(int $productId, int $unitId): int
    {
        $providerId = DB::table('product_stocks')
            ->where('product_id', $productId)
            ->where('product_unit_id', $unitId)
            ->whereNotNull('provider_id')
            ->orderByDesc('id')
            ->value('provider_id');

        if ($providerId) {
            return (int) $providerId;
        }

        $providerId = DB::table('providers')->orderBy('id')->value('id');

        if ($providerId) {
            return (int) $providerId;
        }

        throw new \RuntimeException("Aucun fournisseur disponible pour restaurer le stock du produit #{$productId}.");
    }

    private function restoreStock(int $productId, int $unitId, int $quantity): void
    {
        $currentPriceRow = $this->activePriceRow($productId, $unitId);
        $providerId = $this->resolveFallbackProviderId($productId, $unitId);

        DB::table('product_stocks')->insert([
            'product_id'       => $productId,
            'product_unit_id'   => $unitId,
            'product_price_id'  => $currentPriceRow->id ?? null,
            'provider_id'      => $providerId,
            'quantity'         => $quantity,
            'batch_number'     => null,
            'expiration_date'  => null,
            'age_range'        => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    private function buildProductSnapshots(Collection $products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $productIds = [];
        $unitIds = [];

        foreach ($products as $product) {
            $productIds[] = (int) $product->id;

            foreach ($product->units as $unit) {
                $unitIds[] = (int) $unit->id;
            }
        }

        $productIds = array_values(array_unique($productIds));
        $unitIds = array_values(array_unique($unitIds));
        $currentMonth = $this->currentMonthKey();

        $stockRows = DB::table('product_stocks')
            ->select('product_id', 'product_unit_id', DB::raw('SUM(quantity) as total_quantity'))
            ->whereIn('product_id', $productIds)
            ->where(function ($q) use ($currentMonth) {
                $q->whereNull('expiration_date')
                  ->orWhere('expiration_date', '>=', $currentMonth);
            })
            ->groupBy('product_id', 'product_unit_id')
            ->get();

        $stockMap = [];
        foreach ($stockRows as $row) {
            $stockMap[$row->product_id . '-' . $row->product_unit_id] = (int) $row->total_quantity;
        }

        $priceRows = DB::table('product_prices')
            ->select('id', 'product_id', 'product_unit_id', 'price_purchase', 'price_sale', 'start_date', 'end_date')
            ->whereIn('product_id', $productIds)
            ->whereNull('end_date')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $priceMap = [];
        foreach ($priceRows as $row) {
            $key = $row->product_id . '-' . $row->product_unit_id;
            if (!isset($priceMap[$key])) {
                $priceMap[$key] = $row;
            }
        }

        $latestStockRows = DB::table('product_stocks')
            ->select('id', 'product_id', 'product_unit_id', 'expiration_date', 'age_range')
            ->whereIn('product_id', $productIds)
            ->where(function ($q) use ($currentMonth) {
                $q->whereNull('expiration_date')
                  ->orWhere('expiration_date', '>=', $currentMonth);
            })
            ->orderByDesc('id')
            ->get();

        $latestStockMap = [];
        foreach ($latestStockRows as $row) {
            if (!isset($latestStockMap[$row->product_id])) {
                $latestStockMap[$row->product_id] = $row;
            }
        }

        return $products->map(function (Product $product) use ($stockMap, $priceMap, $latestStockMap) {
            $firstUnit = $product->units->first();
            $unitId = $firstUnit?->id ? (int) $firstUnit->id : 0;

            $available = 0;
            $purchasePrice = 0;
            $salePrice = 0;

            if ($unitId) {
                $key = $product->id . '-' . $unitId;
                $available = (int) ($stockMap[$key] ?? 0);

                if (isset($priceMap[$key])) {
                    $purchasePrice = (float) $priceMap[$key]->price_purchase;
                    $salePrice = (float) $priceMap[$key]->price_sale;
                }
            }

            $latestStock = $latestStockMap[$product->id] ?? null;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'reference' => $product->reference ?? null,
                'category' => $product->category?->name ?? null,
                'category_id' => $product->category_id ?? null,
                'unit_id' => $unitId ?: null,
                'units' => $product->units->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                ])->values(),
                'available_stock' => $available,
                'current_sale_price' => $salePrice,
                'current_purchase_price' => $purchasePrice,
                'status' => $available > 0 ? 'ok' : 'empty',
                'status_labels' => $available <= (int) ($product->min_stock ?? 0) ? ['Stock faible'] : ['En stock'],
                'last_expiration_date' => $latestStock?->expiration_date,
                'last_age_range' => $latestStock?->age_range,
                'description' => $product->description ?? null,
            ];
        })->values()->all();
    }

    private function buildSalesProducts(int $limit = 120): array
    {
        $products = Product::query()
            ->select(['id', 'name', 'reference', 'category_id', 'min_stock', 'description'])
            ->with([
                'category:id,name',
                'units:id,product_id,name',
            ])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return $this->buildProductSnapshots($products);
    }

    private function saleItemsBySaleId(int $saleId)
    {
        return DB::table('sale_items as si')
            ->leftJoin('products as p', 'si.product_id', '=', 'p.id')
            ->leftJoin('product_units as pu', 'si.product_unit_id', '=', 'pu.id')
            ->where('si.sale_id', $saleId)
            ->select([
                'si.id',
                'si.product_id',
                'si.product_unit_id',
                'si.quantity',
                'si.price',
                'si.total',
                'si.purchase_price',
                'p.name as product_name',
                'pu.name as unit_name',
            ])
            ->get();
    }

    private function saleToArray(Sale $sale): array
    {
        $sale->loadMissing(['items.product', 'items.unit', 'user']);

        return [
            'id' => $sale->id,
            'user_id' => $sale->user_id,
            'user_name' => $sale->user?->name ?? '—',
            'client_number' => $sale->client_number,
            'partner_id' => $sale->partner_id,
            'total_amount' => (float) $sale->total_amount,
            'payment_type' => $sale->payment_type,
            'status' => $sale->status,
            'extra_expense_amount' => (float) ($sale->extra_expense_amount ?? 0),
            'extra_expense_description' => $sale->extra_expense_description ?? '',
            'created_at' => $sale->created_at?->format('Y-m-d H:i'),
            'items' => $sale->items->map(function ($it) {
                return [
                    'product_id' => $it->product_id,
                    'product_name' => $it->product?->name ?? '—',
                    'unit_name' => $it->unit?->name ?? '—',
                    'quantity' => (int) $it->quantity,
                    'price' => (float) $it->price,
                    'purchase_price' => (float) ($it->purchase_price ?? 0),
                    'price_purchase' => (float) ($it->purchase_price ?? 0),
                    'total' => (float) $it->total,
                ];
            })->values(),
        ];
    }

    private function buildStatsFromSales($sales): array
    {
        $items = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $items[] = $item;
            }
        }

        $totalSales = $sales->count();
        $totalAmount = (float) $sales->sum('total_amount');

        $byProduct = [];
        foreach ($items as $item) {
            $key = $item->product?->name ?? '—';
            $byProduct[$key] = ($byProduct[$key] ?? 0) + (int) $item->quantity;
        }

        arsort($byProduct);
        $keys = array_keys($byProduct);
        $top = $keys[0] ?? '—';
        $low = $keys ? end($keys) : '—';

        $benefit = 0;
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $benefit += ((float) $item->price - (float) ($item->purchase_price ?? 0)) * (int) $item->quantity;
            }
            $benefit += (float) ($sale->extra_expense_amount ?? 0);
        }

        return [
            'total_sales' => $totalSales,
            'total_amount' => $totalAmount,
            'top_product' => $top,
            'low_product' => $low,
            'benefit_total' => $benefit,
            'benefit_day' => $benefit,
        ];
    }

    private function buildStatsFromDB(): array
    {
        $sales = $this->salesQueryForDay()->get();
        return $this->buildStatsFromSales($sales);
    }

    public function index()
    {
        if (!$this->hasAnySalesPermission()) {
            abort(403, "Vous n'avez pas le droit d'accéder au module ventes.");
        }

        $isAdmin = $this->salesPermissions()['is_admin'];

        $sales = $this->salesQueryForDay()->get();

        // Limité volontairement pour éviter le chargement massif au démarrage
        $products = collect($this->buildSalesProducts(120));

        $clients = DB::table('clients')
            ->select('id', 'client_number', 'name', 'phone', 'address')
            ->orderBy('name')
            ->limit(200)
            ->get();

        $users = $isAdmin
            ? DB::table('users')
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
            : collect();

        $otherOutflows = $this->outflowsQueryForDay()->get()->map(fn ($r) => (array) $r)->values();
        $stats = $this->buildStatsFromSales($sales);

        return view('principale.ventes.index', [
            'nom' => session('nom'),
            'role' => session('role'),
            'isAdmin' => $isAdmin,
            'sales' => $sales,
            'products' => $products,
            'clients' => $clients,
            'users' => $users,
            'otherOutflows' => $otherOutflows,
            'stats' => $stats,
            'salesPermissions' => $this->salesPermissions(),
        ]);
    }

    public function listAll(Request $request)
    {
        if (!$this->hasAnySalesPermission()) {
            return $this->denyJson("Vous n'avez pas le droit d'accéder au module ventes.");
        }

        $userId = $request->query('user_id', 'all');

        $sales = $this->salesQueryForDay($userId)->get();
        $products = collect($this->buildSalesProducts(120));

        $clients = DB::table('clients')
            ->select('id', 'client_number', 'name', 'phone', 'address')
            ->orderBy('name')
            ->limit(200)
            ->get();

        $users = $this->salesPermissions()['is_admin']
            ? DB::table('users')
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
            : collect();

        $outflows = $this->outflowsQueryForDay($userId)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->values();

        return response()->json([
            'success' => true,
            'sales' => $sales->map(fn (Sale $s) => $this->saleToArray($s))->values(),
            'products' => $products,
            'clients' => $clients,
            'users' => $users,
            'outflows' => $outflows,
            'otherOutflows' => $outflows,
            'stats' => $this->buildStatsFromSales($sales),
        ]);
    }

    public function searchProducts(Request $request)
    {
        if (!$this->hasAnySalesPermission()) {
            return $this->denyJson("Accès refusé.");
        }

        $q = trim((string) $request->query('q', ''));

        $query = Product::query()
            ->select(['id', 'name', 'reference', 'category_id', 'min_stock', 'description'])
            ->with([
                'category:id,name',
                'units:id,product_id,name',
            ])
            ->orderBy('name');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%");
            });
        }

        $products = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'products' => $this->buildProductSnapshots($products),
        ]);
    }

    public function searchClients(Request $request)
    {
        if (!$this->hasAnySalesPermission()) {
            return $this->denyJson("Accès refusé.");
        }

        $q = trim((string) $request->query('q', ''));

        $clients = DB::table('clients')
            ->select('id', 'client_number', 'name', 'phone', 'address')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('client_number', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('address', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'clients' => $clients,
        ]);
    }

    public function productDefaults(Product $product)
    {
        if (!$this->hasAnySalesPermission()) {
            return $this->denyJson("Accès refusé.");
        }

        $product->loadMissing(['units:id,product_id,name']);
        $unit = $product->units->first();

        $purchase = 0;
        $sale = 0;

        if ($unit) {
            [$purchase, $sale] = $this->currentSalePriceForProductUnit((int) $product->id, (int) $unit->id);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'unit_id' => $unit?->id,
                'available_stock' => $unit ? $this->totalAvailableStock((int) $product->id, (int) $unit->id) : 0,
                'current_purchase_price' => $purchase,
                'current_sale_price' => $sale,
                'units' => $product->units->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                ])->values(),
            ],
        ]);
    }

    public function storeSale(Request $request)
    {
        if (!$this->canSales('add')) {
            return $this->denyJson("Vous n'avez pas le droit d'ajouter une vente.");
        }

        $validated = $request->validate([
            'client_number' => ['nullable', 'string', 'max:100'],
            'payment_type' => ['required', 'in:cash,mobile_money,partenaire'],
            'status' => ['required', 'in:paid,unpaid'],
            'extra_expense_amount' => ['nullable', 'numeric', 'min:0'],
            'extra_expense_description' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_unit_id' => ['required', 'exists:product_units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.purchase_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $userId = session('id');
        $items = $validated['items'];
        $saleLines = [];
        $grandTotal = 0;
        $benefit = 0;
        $priceUpdates = [];
        $saleId = null;

        DB::transaction(function () use ($validated, $items, $userId, &$saleLines, &$grandTotal, &$benefit, &$priceUpdates, &$saleId) {
            foreach ($items as $line) {
                $productId = (int) $line['product_id'];
                $unitId = (int) $line['product_unit_id'];
                $qty = (int) $line['quantity'];
                $price = (float) $line['price'];

                $purchasePrice = (float) ($line['purchase_price'] ?? 0);
                if ($purchasePrice <= 0) {
                    [$purchasePrice] = $this->currentSalePriceForProductUnit($productId, $unitId);
                }

                $currentRow = $this->activePriceRow($productId, $unitId);
                if (!$currentRow || (float) $currentRow->price_sale !== $price) {
                    $priceId = $this->syncPriceOnSaleIfChanged($productId, $unitId, $price);
                    $priceUpdates[] = [
                        'product_id' => $productId,
                        'product_unit_id' => $unitId,
                        'new_price' => $price,
                        'product_price_id' => $priceId,
                    ];
                }

                $this->deductStock($productId, $unitId, $qty);

                $saleLines[] = [
                    'product_id' => $productId,
                    'product_unit_id' => $unitId,
                    'quantity' => $qty,
                    'price' => $price,
                    'purchase_price' => $purchasePrice,
                    'total' => $qty * $price,
                ];

                $grandTotal += $qty * $price;
                $benefit += ($price - $purchasePrice) * $qty;
            }

            $extraExpense = (float) ($validated['extra_expense_amount'] ?? 0);
            $grandTotal += $extraExpense;
            $benefit += $extraExpense;

            $saleId = DB::table('sales')->insertGetId([
                'user_id' => $userId,
                'client_number' => $validated['client_number'] ?? null,
                'partner_id' => null,
                'total_amount' => $grandTotal,
                'payment_type' => $validated['payment_type'],
                'status' => $validated['status'],
                'extra_expense_amount' => $extraExpense,
                'extra_expense_description' => $validated['extra_expense_description'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($saleLines as $line) {
                DB::table('sale_items')->insert([
                    'sale_id' => $saleId,
                    'product_id' => $line['product_id'],
                    'product_unit_id' => $line['product_unit_id'],
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'purchase_price' => $line['purchase_price'],
                    'total' => $line['total'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (Schema::hasTable('payments')) {
                DB::table('payments')->insert([
                    'sale_id' => $saleId,
                    'client_number' => $validated['client_number'] ?? null,
                    'amount' => $grandTotal,
                    'payment_method' => $validated['payment_type'],
                    'reference' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $sale = Sale::with(['items.product', 'items.unit', 'user'])->findOrFail($saleId);
        $sale->extra_expense_amount = (float) ($validated['extra_expense_amount'] ?? 0);
        $sale->extra_expense_description = $validated['extra_expense_description'] ?? null;

        $affectedIds = array_map(fn ($i) => (int) $i['product_id'], $items);

        return response()->json([
            'success' => true,
            'message' => 'Vente enregistrée avec succès.',
            'sale' => $this->saleToArray($sale),
            'stock_updates' => $this->buildStockUpdatesForIds(array_unique($affectedIds)),
            'price_updates' => $priceUpdates,
            'stats' => $this->buildStatsFromDB(),
        ]);
    }

    private function buildStockUpdatesForIds(array $ids): array
    {
        $products = Product::query()
            ->select(['id', 'name', 'reference', 'category_id', 'min_stock', 'description'])
            ->with([
                'category:id,name',
                'units:id,product_id,name',
            ])
            ->whereIn('id', $ids)
            ->get();

        return collect($this->buildProductSnapshots($products))->map(function (array $snap) {
            return [
                'id' => $snap['id'],
                'available_stock' => $snap['available_stock'],
                'current_sale_price' => $snap['current_sale_price'],
                'current_purchase_price' => $snap['current_purchase_price'],
                'unit_id' => $snap['unit_id'],
                'units' => $snap['units'],
            ];
        })->values()->all();
    }

    public function updateSale(Request $request, Sale $sale)
    {
        if (!$this->canSales('edit')) {
            return $this->denyJson("Vous n'avez pas le droit de modifier une vente.");
        }

        return $this->denyJson("La modification de vente n'est pas encore activée dans cette version.", 422);
    }

    public function destroySale(Sale $sale)
    {
        if (!$this->canSales('delete')) {
            return $this->denyJson("Vous n'avez pas le droit de supprimer une vente.");
        }

        DB::transaction(function () use ($sale) {
            $items = DB::table('sale_items')->where('sale_id', $sale->id)->get();

            foreach ($items as $item) {
                $this->restoreStock((int) $item->product_id, (int) $item->product_unit_id, (int) $item->quantity);
            }

            if (Schema::hasTable('payments')) {
                DB::table('payments')->where('sale_id', $sale->id)->delete();
            }

            DB::table('sale_items')->where('sale_id', $sale->id)->delete();
            DB::table('sales')->where('id', $sale->id)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Vente supprimée avec succès.',
            'stats' => $this->buildStatsFromDB(),
        ]);
    }

    public function invoice(Sale $sale)
    {
        if (!$this->canSales('view')) {
            return $this->denyJson("Vous n'avez pas le droit d'accéder à la facture.");
        }

        return response()->json([
            'success' => true,
            'sale' => $this->saleToArray($sale),
        ]);
    }

    public function dailyReport(Request $request)
    {
        if (!$this->canSales('view')) {
            abort(403, "Vous n'avez pas le droit d'accéder au rapport.");
        }

        $userId = $request->query('user_id', 'all');
        $sales = $this->salesQueryForDay($userId)->get();
        $stats = $this->buildStatsFromSales($sales);

        return view('principale.ventes.report', [
            'sales' => $sales,
            'stats' => $stats,
            'userFilter' => $userId,
        ]);
    }

    public function listAllOutgoings(Request $request)
    {
        if (!$this->hasAnySalesPermission()) {
            return $this->denyJson("Accès refusé.");
        }

        $userId = $request->query('user_id', 'all');

        return response()->json([
            'success' => true,
            'outgoings' => $this->outflowsQueryForDay($userId)->get()->values(),
        ]);
    }

    public function listOutflows(Request $request)
    {
        return $this->listAllOutgoings($request);
    }

    public function storeOutgoing(Request $request)
    {
        if (!$this->canSales('add')) {
            return $this->denyJson("Vous n'avez pas le droit d'ajouter une autre sortie.");
        }

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_unit_id' => ['required', 'exists:product_units,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $outflowId = null;

        DB::transaction(function () use ($validated, &$outflowId) {
            $this->deductStock((int) $validated['product_id'], (int) $validated['product_unit_id'], (int) $validated['quantity']);

            $outflowId = DB::table('stock_outflows')->insertGetId([
                'user_id' => session('id'),
                'product_id' => $validated['product_id'],
                'product_unit_id' => $validated['product_unit_id'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $row = DB::table('stock_outflows as so')
            ->leftJoin('users as u', 'so.user_id', '=', 'u.id')
            ->leftJoin('products as p', 'so.product_id', '=', 'p.id')
            ->leftJoin('product_units as pu', 'so.product_unit_id', '=', 'pu.id')
            ->where('so.id', $outflowId)
            ->select([
                'so.id',
                'so.user_id',
                'u.name as user_name',
                'so.product_id',
                'p.name as product_name',
                'so.product_unit_id',
                'pu.name as unit_name',
                'so.quantity',
                'so.reason',
                'so.created_at',
                'so.updated_at',
            ])
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Autre sortie enregistrée avec succès.',
            'outgoing' => $row,
            'outflow' => $row,
            'stats' => $this->buildStatsFromDB(),
        ]);
    }

    public function storeOutflow(Request $request)
    {
        return $this->storeOutgoing($request);
    }

    public function updateOutgoing(Request $request, $id)
    {
        if (!$this->canSales('edit')) {
            return $this->denyJson("Vous n'avez pas le droit de modifier une autre sortie.");
        }

        $validated = $request->validate([
            'product_id'       => ['nullable', 'exists:products,id'],
            'product_unit_id'  => ['nullable', 'exists:product_units,id'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'reason'           => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:255'],
        ]);

        $row = DB::table('stock_outflows')->where('id', $id)->first();

        if (!$row) {
            return $this->denyJson("Sortie introuvable.", 404);
        }

        DB::transaction(function () use ($id, $row, $validated) {
            $oldProductId = (int) $row->product_id;
            $oldUnitId    = (int) $row->product_unit_id;

            $newProductId = (int) ($validated['product_id'] ?? $oldProductId);
            $newUnitId    = (int) ($validated['product_unit_id'] ?? $oldUnitId);
            $newQuantity   = (int) $validated['quantity'];

            $this->restoreStock($oldProductId, $oldUnitId, (int) $row->quantity);
            $this->deductStock($newProductId, $newUnitId, $newQuantity);

            DB::table('stock_outflows')->where('id', $id)->update([
                'product_id'      => $newProductId,
                'product_unit_id'  => $newUnitId,
                'quantity'         => $newQuantity,
                'reason'          => $validated['reason'],
                'updated_at'      => now(),
            ]);
        });

        $updated = DB::table('stock_outflows as so')
            ->leftJoin('users as u', 'so.user_id', '=', 'u.id')
            ->leftJoin('products as p', 'so.product_id', '=', 'p.id')
            ->leftJoin('product_units as pu', 'so.product_unit_id', '=', 'pu.id')
            ->where('so.id', $id)
            ->select([
                'so.id',
                'so.user_id',
                'u.name as user_name',
                'so.product_id',
                'p.name as product_name',
                'so.product_unit_id',
                'pu.name as unit_name',
                'so.quantity',
                'so.reason',
                'so.created_at',
                'so.updated_at',
            ])
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Autre sortie modifiée avec succès.',
            'outgoing' => $updated,
            'stats' => $this->buildStatsFromDB(),
        ]);
    }

    public function updateOutflow(Request $request, $id)
    {
        return $this->updateOutgoing($request, $id);
    }

    public function destroyOutgoing($outgoing)
    {
        if (!$this->canSales('delete')) {
            return $this->denyJson("Vous n'avez pas le droit de supprimer une autre sortie.");
        }

        $row = DB::table('stock_outflows')->where('id', $outgoing)->first();
        if (!$row) {
            return $this->denyJson("Sortie introuvable.", 404);
        }

        DB::transaction(function () use ($row, $outgoing) {
            $this->restoreStock((int) $row->product_id, (int) $row->product_unit_id, (int) $row->quantity);
            DB::table('stock_outflows')->where('id', $outgoing)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Autre sortie supprimée avec succès.',
            'stats' => $this->buildStatsFromDB(),
        ]);
    }

    public function destroyOutflow($id)
    {
        return $this->destroyOutgoing($id);
    }

    private function webPublicPath(string $path = ''): string
    {
        $base = public_path();
        $hostingerPath = dirname($base) . '/public_html';

        if (is_dir($hostingerPath)) {
            return rtrim($hostingerPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        }

        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    private function webPublicUrl(string $path): string
    {
        return url(trim($path, '/'));
    }
}
