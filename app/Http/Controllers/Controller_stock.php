<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Unit;

class Controller_stock extends Controller
{
    private function stockPermissions(): array
    {
        $isAdmin = session('role') === 'admin';
        $permissions = session('permissions', []);

        return [
            'is_admin' => $isAdmin,
            'view'     => $isAdmin ? true : !empty(data_get($permissions, 'stock.view', 1)),
            'add'      => $isAdmin ? true : !empty(data_get($permissions, 'stock.add')),
            'edit'     => $isAdmin ? true : !empty(data_get($permissions, 'stock.edit')),
            'delete'   => $isAdmin ? true : !empty(data_get($permissions, 'stock.delete')),
        ];
    }

    private function hasAnyStockPermission(): bool
    {
        $perms = $this->stockPermissions();
        return $perms['view'] || $perms['add'] || $perms['edit'] || $perms['delete'];
    }

    private function canStock(string $ability): bool
    {
        $perms = $this->stockPermissions();
        return (bool) ($perms[$ability] ?? false);
    }

    private function denyStockJson(string $ability): \Illuminate\Http\JsonResponse
    {
        $messages = [
            'view'   => "Vous n'avez pas le droit d'accéder au stock.",
            'add'    => "Vous n'avez pas le droit d'ajouter un produit ou une réception.",
            'edit'   => "Vous n'avez pas le droit de modifier un produit.",
            'delete' => "Vous n'avez pas le droit de supprimer un produit ou un lot.",
        ];

        return response()->json([
            'success' => false,
            'message' => $messages[$ability] ?? 'Accès refusé.',
        ], 403);
    }

    public function index()
    {
        if (!$this->hasAnyStockPermission()) {
            abort(403, "Vous n'avez pas le droit d'accéder au module stock.");
        }

        $nom = session('nom');
        $email = session('email');
        $password = session('password');
        $id = session('id');
        $role = session('role');

        $categories = DB::table('categories')->orderBy('name')->get();
        $providers  = DB::table('providers')->orderBy('name')->get();
        $stats      = $this->buildStatsFromDB();
        $products   = $this->paginateProducts(request(), 10);
        $stockPermissions = $this->stockPermissions();

        $units = Unit::all();

        return view('principale.stock.index', compact(
            'products',
            'categories',
            'providers',
            'stats',
            'nom',
            'email',
            'password',
            'id',
            'role',
            'stockPermissions',
            'units'
        ));
    }

    public function getData()
    {
        if (!$this->canStock('view')) {
            return $this->denyStockJson('view');
        }

        return response()->json([
            'success' => true,
            'stats'   => $this->buildStatsFromDB(),
        ]);
    }

    public function getList(Request $request)
    {
        if (!$this->canStock('view')) {
            return $this->denyStockJson('view');
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $products = $this->paginateProducts($request, $perPage);
        $items = $products->getCollection()->map(fn (Product $p) => $this->productToArray($p));

        return response()->json([
            'success'      => true,
            'products'     => $items->values(),
            'total'        => $products->total(),
            'per_page'     => $products->perPage(),
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
            'from'         => $products->firstItem() ?? 0,
            'to'           => $products->lastItem() ?? 0,
            'stats'        => $this->buildStatsFromDB(),
        ]);
    }

    public function searchProducts(Request $request)
    {
        if (!$this->canStock('view')) {
            return $this->denyStockJson('view');
        }

        $q = trim((string) $request->get('q', ''));

        $query = Product::query()
            ->with('category')
            ->orderBy('name');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%");
            });
        }

        $items = $query->limit(20)->get()->map(function (Product $p) {
            return [
                'id'        => $p->id,
                'name'      => $p->name,
                'reference' => $p->reference,
                'category'  => $p->category?->name,
            ];
        });

        return response()->json([
            'success' => true,
            'items'   => $items,
        ]);
    }

    public function storeProduct(Request $request)
    {
        if (!$this->canStock('add')) {
            return $this->denyStockJson('add');
        }

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'category_id'     => ['required', 'exists:categories,id'],
            'unit_name'       => ['required', 'string'],
            'provider_id'     => ['required', 'exists:providers,id'],
            'min_stock'       => ['nullable', 'integer', 'min:0'],
            'dosage'          => ['nullable', 'string', 'max:255'],
            'quantity'        => ['required', 'integer', 'min:1'],
            'purchase_price'  => ['required', 'numeric', 'min:0'],
            'sale_price'      => ['required', 'numeric', 'min:0'],
            'batch_number'    => ['nullable', 'string', 'max:255'],
            'expiration_date' => ['required', 'date_format:Y-m'],
            'age_range'       => ['nullable', 'in:enfant,adulte,senior'],
            'description'     => ['nullable', 'string'],
            'images'          => ['nullable', 'array'],
            'images.*'        => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $product = null;

        DB::transaction(function () use ($request, $validated, &$product) {
            $category = Category::findOrFail($validated['category_id']);
            $reference = $this->generateReference(
                $validated['name'],
                $category->name,
                $validated['dosage']
            );

            $product = Product::create([
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
                'dosage'      => $validated['dosage'] ?? null,
                'reference'   => $reference,
                'category_id' => $validated['category_id'],
                'min_stock'   => $validated['min_stock'] ?? 0,
            ]);

            $unit = ProductUnit::create([
                'product_id' => $product->id,
                'name'       => $validated['unit_name'],
            ]);

            $price = ProductPrice::create([
                'product_id'      => $product->id,
                'product_unit_id' => $unit->id,
                'price_purchase'  => $validated['purchase_price'],
                'price_sale'      => $validated['sale_price'],
                'start_date'      => now()->toDateString(),
                'end_date'        => null,
            ]);

            ProductStock::create([
                'product_id'       => $product->id,
                'product_unit_id'  => $unit->id,
                'product_price_id' => $price->id,
                'provider_id'      => $validated['provider_id'],
                'quantity'         => $validated['quantity'],
                'batch_number'     => $validated['batch_number'] ?? null,
                'expiration_date'  => $this->normalizeExpirationMonth($validated['expiration_date'] ?? null),
                'age_range'        => $validated['age_range'] ?? null,
            ]);

            $this->storeMediaFiles($product, $request->file('images', []));
        });

        if (!$product) {
            throw new \RuntimeException('Le produit n\'a pas été créé.');
        }

        $product->load(['category', 'media', 'units', 'stocks.provider', 'stocks.unit', 'stocks.price']);
        $this->decorateProduct($product);

        return response()->json([
            'success' => true,
            'message' => 'Produit enregistré avec succès.',
            'product' => $this->productToArray($product),
            'stats'   => $this->buildStatsFromDB(),
        ]);
    }

    public function storeReception(Request $request)
    {
        if (!$this->canStock('add')) {
            return $this->denyStockJson('add');
        }

        $validated = $request->validate([
            'product_id'        => ['required', 'exists:products,id'],
            'product_unit_id_1' => ['required', 'exists:product_units,id'],
            'provider_id_1'     => ['required', 'exists:providers,id'],
            'quantity_1'        => ['required', 'integer', 'min:1'],
            'purchase_price_1'  => ['required', 'numeric', 'min:0'],
            'sale_price_1'      => ['required', 'numeric', 'min:0'],
            'batch_number_1'    => ['nullable', 'string', 'max:255'],
            'expiration_date_1' => ['nullable', 'date_format:Y-m'],
            'age_range_1'       => ['nullable', 'in:enfant,adulte,senior'],
        ]);

        $productId = (int) $validated['product_id'];
        $unitId    = (int) $validated['product_unit_id_1'];
        $today     = now()->toDateString();

        DB::transaction(function () use ($validated, $productId, $unitId, $today) {
            $currentPrice = DB::table('product_prices')
                ->where('product_id', $productId)
                ->where('product_unit_id', $unitId)
                ->whereNull('end_date')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();

            $priceChanged = !$currentPrice
                || (float) $currentPrice->price_purchase !== (float) $validated['purchase_price_1']
                || (float) $currentPrice->price_sale !== (float) $validated['sale_price_1'];

            if ($priceChanged) {
                if ($currentPrice) {
                    DB::table('product_prices')
                        ->where('id', $currentPrice->id)
                        ->update(['end_date' => $today]);
                }

                $currentPrice = ProductPrice::create([
                    'product_id'      => $productId,
                    'product_unit_id' => $unitId,
                    'price_purchase'  => $validated['purchase_price_1'],
                    'price_sale'      => $validated['sale_price_1'],
                    'start_date'      => $today,
                    'end_date'        => null,
                ]);
            }

            ProductStock::create([
                'product_id'       => $productId,
                'product_unit_id'  => $unitId,
                'product_price_id' => $currentPrice->id,
                'provider_id'      => $validated['provider_id_1'],
                'quantity'         => $validated['quantity_1'],
                'batch_number'     => $validated['batch_number_1'] ?? null,
                'expiration_date'  => $this->normalizeExpirationMonth($validated['expiration_date_1'] ?? null),
                'age_range'        => $validated['age_range_1'] ?? null,
            ]);
        });

        $product = Product::with(['category', 'media', 'units', 'stocks.provider', 'stocks.unit', 'stocks.price'])
            ->findOrFail($productId);

        $this->decorateProduct($product);

        return response()->json([
            'success' => true,
            'message' => 'Nouvelle réception enregistrée avec succès.',
            'product' => $this->productToArray($product),
            'stats'   => $this->buildStatsFromDB(),
        ]);
    }

    public function updateProduct(Request $request, Product $product)
    {
        if (!$this->canStock('edit')) {
            return $this->denyStockJson('edit');
        }

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'dosage'      => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'min_stock'   => ['nullable', 'integer', 'min:0'],
            'images'      => ['nullable', 'array'],
            'images.*'    => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        DB::transaction(function () use ($request, $validated, $product) {
            $category = DB::table('categories')
                ->where('id', $validated['category_id'])
                ->first();

            $reference = $this->generateReference(
                $validated['name'],
                $category?->name ?? '',
                $validated['dosage'] ?? ''
            );

            $product->update([
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
                'dosage'      => $validated['dosage'] ?? null,
                'category_id' => $validated['category_id'],
                'min_stock'   => $validated['min_stock'] ?? 0,
                'reference'   => $reference,
            ]);

            $this->storeMediaFiles($product, $request->file('images', []));
        });

        $product->load(['category', 'media', 'units', 'stocks.provider', 'stocks.unit', 'stocks.price']);
        $this->decorateProduct($product);

        return response()->json([
            'success' => true,
            'message' => 'Produit modifié avec succès.',
            'product' => $this->productToArray($product),
            'stats'   => $this->buildStatsFromDB(),
        ]);
    }

    public function destroyProduct(Product $product)
    {
        if (!$this->canStock('delete')) {
            return $this->denyStockJson('delete');
        }

        $hasSales = DB::table('sale_items')
            ->where('product_id', $product->id)
            ->exists();

        if ($hasSales) {
            return response()->json([
                'success' => false,
                'message' => "Ce produit ne peut pas être supprimé parce qu'il a déjà fait l'objet d'une vente.",
            ], 422);
        }

        DB::transaction(function () use ($product) {
            foreach ($product->media as $media) {
                $this->deleteMediaFile($media->file_path);
                $media->delete();
            }

            DB::table('product_stocks')->where('product_id', $product->id)->delete();
            DB::table('product_prices')->where('product_id', $product->id)->delete();
            DB::table('product_units')->where('product_id', $product->id)->delete();
            DB::table('products')->where('id', $product->id)->delete();
        });

        return response()->json([
            'success'    => true,
            'message'    => 'Produit supprimé avec succès.',
            'product_id' => $product->id,
            'stats'      => $this->buildStatsFromDB(),
        ]);
    }

    public function productDefaults(Product $product, Request $request)
    {
        if (!$this->canStock('view')) {
            return $this->denyStockJson('view');
        }

        $unitId = $request->integer('unit_id');

        $units = $product->units()
            ->orderBy('id')
            ->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        $unit = $unitId
            ? $product->units()->where('id', $unitId)->first()
            : $product->units()->first();

        $latestPrice = null;

        if ($unit) {
            $latestPrice = DB::table('product_prices')
                ->where('product_id', $product->id)
                ->where('product_unit_id', $unit->id)
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();
        }

        if (!$latestPrice && $product->units()->exists()) {
            $firstUnit = $product->units()->first();
            $latestPrice = DB::table('product_prices')
                ->where('product_id', $product->id)
                ->where('product_unit_id', $firstUnit->id)
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();
        }

        return response()->json([
            'success'        => true,
            'product_id'     => $product->id,
            'name'           => $product->name,
            'reference'      => $product->reference,
            'category_id'    => $product->category_id,
            'category_name'  => $product->category?->name,
            'min_stock'      => $product->min_stock,
            'dosage'         => $product->dosage ?? null,
            'units'          => $units,
            'unit_id'        => $unit?->id,
            'purchase_price' => $latestPrice->price_purchase ?? null,
            'sale_price'     => $latestPrice->price_sale ?? null,
        ]);
    }

    public function priceHistory(Product $product)
    {
        if (!$this->canStock('view')) {
            return $this->denyStockJson('view');
        }

        $history = DB::table('product_prices as pp')
            ->join('product_units as pu', 'pp.product_unit_id', '=', 'pu.id')
            ->where('pp.product_id', $product->id)
            ->orderByDesc('pp.start_date')
            ->orderByDesc('pp.id')
            ->get([
                'pp.id',
                'pu.name as unit',
                'pp.price_purchase as purchase',
                'pp.price_sale as sale',
                'pp.start_date',
                'pp.end_date',
            ]);

        return response()->json(
            $history->map(fn ($row) => [
                'id'         => $row->id,
                'unit'       => $row->unit,
                'purchase'   => $row->purchase,
                'sale'       => $row->sale,
                'start_date' => $row->start_date,
                'end_date'   => $row->end_date,
            ])->values()
        );
    }

    public function stockEntries(Product $product)
    {
        if (!$this->canStock('view')) {
            return $this->denyStockJson('view');
        }

        $currentMonth = $this->currentMonthKey();

        $entries = DB::table('product_stocks as ps')
            ->leftJoin('product_units as pu', 'ps.product_unit_id', '=', 'pu.id')
            ->leftJoin('providers as pr', 'ps.provider_id', '=', 'pr.id')
            ->leftJoin('product_prices as pp', 'ps.product_price_id', '=', 'pp.id')
            ->where('ps.product_id', $product->id)
            ->orderByDesc('ps.id')
            ->get([
                'ps.id',
                'pu.name as unit',
                'ps.quantity',
                'ps.batch_number',
                'pr.name as provider',
                'pp.price_purchase',
                'pp.price_sale',
                'ps.expiration_date',
                'ps.created_at',
            ]);

        $totalQuantity = DB::table('product_stocks')
            ->where('product_id', $product->id)
            ->where(function ($q) use ($currentMonth) {
                $q->whereNull('expiration_date')
                  ->orWhere('expiration_date', '>=', $currentMonth);
            })
            ->sum('quantity');

        return response()->json([
            'success'        => true,
            'total_quantity' => (int) $totalQuantity,
            'entries'        => $entries->map(function ($entry) use ($currentMonth) {
                $purchase = (float) ($entry->price_purchase ?? 0);
                $sale     = (float) ($entry->price_sale ?? 0);
                $qty      = (int) $entry->quantity;

                return [
                    'id'              => $entry->id,
                    'unit'            => $entry->unit,
                    'quantity'        => $qty,
                    'batch_number'    => $entry->batch_number,
                    'provider'        => $entry->provider,
                    'purchase_price'  => $purchase,
                    'sale_price'      => $sale,
                    'benefit'         => max(0, ($sale - $purchase) * $qty),
                    'expiration_date' => $entry->expiration_date,
                    'created_at'      => Carbon::parse($entry->created_at)->format('Y-m-d H:i'),
                    'is_expired'      => !empty($entry->expiration_date) && $entry->expiration_date < $currentMonth,
                ];
            })->values(),
        ]);
    }

    public function destroyEntry($stock)
    {
        if (!$this->canStock('delete')) {
            return $this->denyStockJson('delete');
        }

        $entry = ProductStock::findOrFail($stock);
        $entry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lot supprimé avec succès.',
            'stats'   => $this->buildStatsFromDB(),
        ]);
    }

    public function listAll()
    {
        if (!$this->hasAnyStockPermission()) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'avez pas le droit d'accéder au module stock.",
            ], 403);
        }

        $products = Product::query()
            ->with([
                'category',
                'media',
                'units:id,product_id,name',
                'stocks' => fn ($q) => $q->with([
                    'provider:id,name',
                    'unit:id,name',
                    'price:id,price_purchase,price_sale',
                ]),
            ])
            ->orderByDesc('products.id')
            ->get();

        $items = $products->map(function (Product $product) {
            $this->decorateProduct($product);
            return $this->productToArray($product);
        })->values();

        return response()->json([
            'success' => true,
            'products' => $items,
            'stats'    => $this->buildStatsFromDB(),
        ]);
    }

    private function paginateProducts(Request $request, int $perPage = 10)
    {
        $search   = trim((string) $request->input('search', ''));
        $category = $request->input('category', 'all');
        $status   = $request->input('status', 'all');
        $provider = $request->input('provider', 'all');

        $currentMonth = now()->format('Y-m');
        $almostExpiredThreshold = Carbon::parse($currentMonth . '-01')
            ->addMonthsNoOverflow(2)
            ->format('Y-m');

        $query = Product::query()
            ->with([
                'category',
                'media',
                'units:id,product_id,name',
                'stocks' => fn ($q) => $q->with([
                    'provider:id,name',
                    'unit:id,name',
                    'price:id,price_purchase,price_sale',
                ]),
            ])
            ->latest('products.id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.reference', 'like', "%{$search}%");
            });
        }

        if ($category !== 'all') {
            $query->whereHas('category', fn ($q) => $q->where('name', $category));
        }

        if ($provider !== 'all') {
            $query->whereHas('stocks.provider', fn ($q) => $q->where('name', $provider));
        }

        if ($status === 'expired') {
            $query->whereHas('stocks', fn ($q) =>
                $q->whereNotNull('expiration_date')
                  ->where('expiration_date', '<', $currentMonth)
            );
        } elseif ($status === 'almost_expired') {
            $query->whereHas('stocks', fn ($q) =>
                $q->whereNotNull('expiration_date')
                  ->where('expiration_date', '>=', $currentMonth)
                  ->where('expiration_date', '<=', $almostExpiredThreshold)
            );
        } elseif ($status === 'low') {
            $query->whereRaw(
                '(SELECT COALESCE(SUM(ps.quantity),0) FROM product_stocks ps
                  WHERE ps.product_id = products.id
                  AND (ps.expiration_date IS NULL OR ps.expiration_date >= ?)) <= products.min_stock',
                [$currentMonth]
            );
        } elseif ($status === 'ok') {
            $query->whereRaw(
                '(SELECT COALESCE(SUM(ps.quantity),0) FROM product_stocks ps
                  WHERE ps.product_id = products.id
                  AND (ps.expiration_date IS NULL OR ps.expiration_date >= ?)) > products.min_stock',
                [$currentMonth]
            )->whereDoesntHave('stocks', fn ($q) =>
                $q->whereNotNull('expiration_date')->where('expiration_date', '<', $currentMonth)
            )->whereDoesntHave('stocks', fn ($q) =>
                $q->whereNotNull('expiration_date')
                  ->where('expiration_date', '>=', $currentMonth)
                  ->where('expiration_date', '<=', $almostExpiredThreshold)
            );
        }

        $paginated = $query->paginate($perPage)->withQueryString();
        $paginated->getCollection()->transform(function (Product $p) {
            $this->decorateProduct($p);
            return $p;
        });

        return $paginated;
    }

    private function buildStatsFromDB(): array
    {
        $currentMonth = $this->currentMonthKey();
        $almostExpiredThreshold = Carbon::parse($currentMonth . '-01')
            ->addMonthsNoOverflow(2)
            ->format('Y-m');

        $totalRefs = DB::table('products')->count();

        $values = DB::table('product_stocks as ps')
            ->join('product_prices as pp', 'ps.product_price_id', '=', 'pp.id')
            ->selectRaw('
                COALESCE(SUM(pp.price_purchase * ps.quantity), 0) as stock_value,
                COALESCE(SUM(pp.price_sale * ps.quantity), 0) as turnover_total,
                COALESCE(SUM(GREATEST(pp.price_sale - pp.price_purchase, 0) * ps.quantity), 0) as benefit_total
            ')
            ->first();

        $lowStock = DB::table('products as p')
            ->selectRaw('COUNT(*) as cnt')
            ->whereRaw(
                '(SELECT COALESCE(SUM(ps.quantity),0) FROM product_stocks ps
                WHERE ps.product_id = p.id
                AND (ps.expiration_date IS NULL OR ps.expiration_date >= ?)) <= p.min_stock',
                [$currentMonth]
            )
            ->value('cnt');

        $expired = DB::table('product_stocks')
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', $currentMonth)
            ->distinct('product_id')
            ->count('product_id');

        $almostExpired = DB::table('product_stocks')
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '>=', $currentMonth)
            ->where('expiration_date', '<=', $almostExpiredThreshold)
            ->distinct('product_id')
            ->count('product_id');

        return [
            'total_references' => (int) $totalRefs,
            'stock_value'      => (float) ($values->stock_value ?? 0),
            'turnover_total'    => (float) ($values->turnover_total ?? 0),
            'benefit_total'     => (float) ($values->benefit_total ?? 0),
            'low_stock'         => (int) ($lowStock ?? 0),
            'expired'           => (int) $expired,
            'almost_expired'    => (int) $almostExpired,
        ];
    }

    private function latestPriceForProduct(int $productId, ?int $unitId = null)
    {
        $query = DB::table('product_prices')
            ->where('product_id', $productId);

        if ($unitId) {
            $query->where('product_unit_id', $unitId);
        }

        return $query
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    private function decorateProduct(Product $product): Product
    {
        $currentMonth = $this->currentMonthKey();
        $almostExpiredThreshold = Carbon::parse($currentMonth . '-01')
            ->addMonthsNoOverflow(2)
            ->format('Y-m');

        $availableStock = 0;
        $expiredStock = 0;
        $almostExpiredStock = 0;
        $stockValue = 0;
        $turnoverTotal = 0;
        $benefitTotal = 0;

        foreach ($product->stocks as $stock) {
            $expiration = $stock->expiration_date;
            $qty = (int) $stock->quantity;

            $isExpired = $expiration && $expiration < $currentMonth;
            $isAlmostExpired = $expiration
                && $expiration >= $currentMonth
                && $expiration <= $almostExpiredThreshold;

            if ($isExpired) {
                $expiredStock += $qty;
            } else {
                $availableStock += $qty;

                if ($isAlmostExpired) {
                    $almostExpiredStock += $qty;
                }
            }

            $purchase = (float) ($stock->price->price_purchase ?? 0);
            $sale     = (float) ($stock->price->price_sale ?? 0);

            $stockValue    += $purchase * $qty;
            $turnoverTotal += $sale * $qty;
            $benefitTotal  += max(0, ($sale - $purchase)) * $qty;
        }

        $statuses = [];
        $statusLabels = [];

        if ($expiredStock > 0) {
            $statuses[] = 'expired';
            $statusLabels[] = $availableStock > 0 ? 'Périmé partiel' : 'Périmé';
        }

        if ($almostExpiredStock > 0) {
            $statuses[] = 'almost_expired';
            $statusLabels[] = 'Presque périmé';
        }

        if ($availableStock <= (int) $product->min_stock) {
            $statuses[] = 'low';
            $statusLabels[] = 'Stock faible';
        }

        if (empty($statuses)) {
            $statuses[] = 'ok';
            $statusLabels[] = 'En stock';
        }

        $statuses = array_values(array_unique($statuses));
        $statusLabels = array_values(array_unique($statusLabels));

        $latestStock = $product->stocks->sortByDesc('id')->first();
        $currentPrice = $latestStock?->price;

        $product->setAttribute('available_stock', $availableStock);
        $product->setAttribute('expired_stock', $expiredStock);
        $product->setAttribute('stock_value', $stockValue);
        $product->setAttribute('turnover_total', $turnoverTotal);
        $product->setAttribute('benefit_total', $benefitTotal);
        $product->setAttribute('status_codes', $statuses);
        $product->setAttribute('status_labels', $statusLabels);

        $product->setAttribute('current_purchase_price', $currentPrice?->price_purchase ?? 0);
        $product->setAttribute('current_sale_price', $currentPrice?->price_sale ?? 0);
        $product->setAttribute('current_price_start_date', $currentPrice?->start_date ?? null);
        $product->setAttribute('current_price_end_date', $currentPrice?->end_date ?? null);
        $product->setAttribute('price_product_unit_id', $currentPrice?->product_unit_id ?? null);

        $product->setAttribute('main_provider', $latestStock?->provider?->name ?? '—');
        $product->setAttribute('main_unit', $latestStock?->unit?->name ?? '—');
        $product->setAttribute('unit_id', $currentPrice?->product_unit_id ?? $latestStock?->product_unit_id ?? $product->units->first()?->id ?? null);
        $product->setAttribute('last_expiration_date', $latestStock?->expiration_date);
        $product->setAttribute('last_age_range', $latestStock?->age_range);
        $product->setAttribute('provider_id', $latestStock?->provider?->id ?? null);
        $product->setAttribute('provider_name', $latestStock?->provider?->name ?? null);
        $product->setAttribute('image_url', $this->resolveMediaUrl($product->media->first()?->file_path));

        return $product;
    }

    private function productToArray(Product $p): array
    {
        return [
            'id'                     => $p->id,
            'name'                   => $p->name,
            'reference'              => $p->reference,
            'category_id'            => $p->category_id,
            'category'               => $p->category?->name,
            'main_provider'          => $p->main_provider ?? '—',
            'main_unit'              => $p->main_unit ?? '—',
            'unit_id'                => $p->unit_id ?? null,

            'available_stock'        => (int) ($p->available_stock ?? 0),
            'expired_stock'          => (int) ($p->expired_stock ?? 0),
            'min_stock'              => (int) ($p->min_stock ?? 0),

            'current_purchase_price' => (float) ($p->current_purchase_price ?? 0),
            'current_sale_price'     => (float) ($p->current_sale_price ?? 0),
            'current_price_start_date' => $p->current_price_start_date ?? null,
            'current_price_end_date'   => $p->current_price_end_date ?? null,
            'price_product_unit_id'    => $p->price_product_unit_id ?? null,

            'turnover_total'         => (float) ($p->turnover_total ?? 0),
            'benefit_total'          => (float) ($p->benefit_total ?? 0),
            'status_codes'           => $p->status_codes ?? [],
            'status_labels'          => $p->status_labels ?? [],
            'image_url'              => $p->image_url ?? null,
            'description'            => $p->description ?? null,
            'dosage'                 => $p->dosage ?? null,

            'units' => $p->units?->map(function ($u) {
                return [
                    'id'   => $u->id,
                    'name' => $u->name,
                ];
            })->values() ?? [],

            'last_expiration_date'   => $p->last_expiration_date ?? null,
            'last_age_range'         => $p->last_age_range ?? null,
            'provider_id'            => $p->provider_id ?? null,
            'provider_name'          => $p->provider_name ?? null,
        ];
    }

    private function storeMediaFiles(Product $product, array $files): void
    {
        if (empty($files)) {
            return;
        }

        $directory = $this->webPublicPath('products');

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = 'prod_' . uniqid('', true) . '.' . $extension;

            $file->move($directory, $filename);

            $savedPath = 'products/' . $filename;
            $savedFile = $this->webPublicPath($savedPath);

            Media::create([
                'product_id' => $product->id,
                'file_path'  => $savedPath,
                'mime_type'  => $file->getClientMimeType(),
                'size'       => is_file($savedFile) ? filesize($savedFile) : null,
            ]);
        }
    }

    private function resolveMediaUrl(?string $filePath): ?string
    {
        if (!$filePath) {
            return null;
        }

        $relative = ltrim($filePath, '/');

        $hostingerFile = $this->webPublicPath($relative);
        if (is_file($hostingerFile)) {
            return $this->webPublicUrl($relative);
        }

        if (file_exists(public_path($relative))) {
            return asset($relative);
        }

        if (file_exists(public_path('storage/' . $relative))) {
            return asset('storage/' . $relative);
        }

        return $this->webPublicUrl($relative);
    }

    private function deleteMediaFile(?string $filePath): void
    {
        if (!$filePath) {
            return;
        }

        $relative = ltrim($filePath, '/');

        $paths = [
            $this->webPublicPath($relative),
            public_path($relative),
            public_path('storage/' . $relative),
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
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
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function generateReference(string $name, string $categoryName, string $dosageName): string
    {
        $namePart = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 5));
        $catPart  = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $categoryName), 0, 5));
        $dosagePart = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $dosageName), 0, 20));
        $datePart = now()->format('ymd');
        $randPart = strtoupper(bin2hex(random_bytes(2)));

        return $namePart . $catPart . "-" . $dosagePart . "-" . $datePart . $randPart;
    }
}
