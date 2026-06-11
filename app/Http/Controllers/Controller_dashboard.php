<?php

namespace App\Http\Controllers;


use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Controller_dashboard extends Controller
{
    public function index()
    {
        $isAdmin = session('role') === 'admin';
        $userId   = session('id');
        $userName = session('nom');
        $role     = session('role');
        $profileImage     = session('profileImage');

        $todayStart = now()->startOfDay();
        $todayEnd   = now()->endOfDay();

        $salesQuery = Sale::query()
            ->with(['items.product', 'items.unit', 'user'])
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->orderByDesc('id');

        if (!$isAdmin) {
            $salesQuery->where('user_id', $userId);
        }

        $salesToday = $salesQuery->get();

        $decoratedProducts = $this->decorateProductsForDashboard();

        $lowStockProducts = $decoratedProducts
            ->filter(fn ($p) => (int) $p->available_stock <= (int) $p->min_stock)
            ->sortBy('available_stock')
            ->take(8)
            ->values();

        $recentSales = $salesToday->take(5)->map(function ($sale) {
            return [
                'id'            => $sale->id,
                'created_at'    => $sale->created_at?->format('d/m/Y H:i'),
                'user_name'     => $sale->user?->name ?? '—',
                'client_number' => $sale->client_number ?? '—',
                'payment_type'  => $sale->payment_type,
                'status'        => $sale->status,
                'total_amount'  => (float) $sale->total_amount,
                'items_count'   => $sale->items->sum('quantity'),
            ];
        })->values();

        $topProducts = $salesToday
            ->flatMap(fn ($sale) => $sale->items)
            ->groupBy(fn ($item) => $item->product?->name ?? '—')
            ->map(fn ($items) => $items->sum('quantity'))
            ->sortDesc()
            ->take(5)
            ->map(fn ($qty, $name) => [
                'name' => $name,
                'qty'  => $qty,
            ])
            ->values();

        $chart = $this->buildChartData($isAdmin, $userId);

        $summary = $this->buildSummary($isAdmin, $salesToday, $decoratedProducts);

        return view('principale.dashboard.index', [
            'nom'              => $userName,
            'role'             => $role,
            'isAdmin'          => $isAdmin,
            'summary'          => $summary,
            'recentSales'      => $recentSales,
            'lowStockProducts' => $lowStockProducts,
            'topProducts'      => $topProducts,
            'chartLabels'      => $chart['labels'],
            'chartValues'      => $chart['values'],
            'profileImage' => $profileImage,
        ]);
    }

    private function buildSummary(bool $isAdmin, $salesToday, $decoratedProducts): array
    {
        $totalSalesToday = $salesToday->count();
        $totalAmountToday = (float) $salesToday->sum('total_amount');
        $clientsToday = $salesToday->pluck('client_number')->filter()->unique()->count();
        $itemsSoldToday = (int) $salesToday->flatMap(fn ($sale) => $sale->items)->sum('quantity');

        $summary = [
            'sales_today'   => $totalSalesToday,
            'amount_today'  => $totalAmountToday,
            'clients_today' => $clientsToday,
            'items_sold'    => $itemsSoldToday,
        ];

        if ($isAdmin) {
            $summary['users_count'] = DB::table('users')->count();

            $summary['clients_count'] = DB::table('clients')->count();

            $summary['products_count'] = DB::table('products')->count();

            $summary['suppliers_count'] = Schema::hasTable('providers')
                ? DB::table('providers')->count()
                : 0;

            $summary['categories_count'] = Schema::hasTable('categories')
                ? DB::table('categories')->count()
                : 0;

            $summary['low_stock_count'] = collect($decoratedProducts)
                ->filter(fn ($p) => (int) $p->available_stock <= (int) $p->min_stock)
                ->count();
        } else {
            $summary['products_count'] = DB::table('products')->count();

            $summary['low_stock_count'] = $decoratedProducts
                ->filter(fn ($p) => (int) $p->available_stock <= (int) $p->min_stock)
                ->count();
        }

        return $summary;
    }

    private function buildChartData(bool $isAdmin, $userId): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());

        $query = DB::table('sales')
                    ->whereBetween('created_at', [
                        now()->subDays(6)->startOfDay(),
                        now()->endOfDay()
                    ]);

        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }

        $sales = $query->get();

        $byDay = $sales->groupBy(function ($sale) {
    return substr($sale->created_at, 0, 10);
})->map(fn ($group) => (float) $group->sum('total_amount'));

        $labels = $days->map(fn ($day) => $day->format('d/m'))->values();
        $values = $days->map(fn ($day) => (float) ($byDay[$day->format('Y-m-d')] ?? 0))->values();

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function decorateProductsForDashboard()
    {
        $today = now()->toDateString();

        $products = Product::query()
            ->with([
                'category',
                'stocks' => fn ($q) => $q->with([
                    'unit:id,name',
                    'provider:id,name',
                    'price:id,price_purchase,price_sale',
                ])->orderByDesc('id'),
            ])
            ->get();

        return $products->map(function (Product $product) use ($today) {
            $availableStock = 0;
            $expiredStock = 0;

            foreach ($product->stocks as $stock) {
                $isExpired = $stock->expiration_date && Carbon::parse($stock->expiration_date)->lt($today);

                if ($isExpired) {
                    $expiredStock += (int) $stock->quantity;
                } else {
                    $availableStock += (int) $stock->quantity;
                }
            }

            $latestStock = $product->stocks->sortByDesc('id')->first();
            $currentPrice = $latestStock?->price;

            $product->setAttribute('available_stock', $availableStock);
            $product->setAttribute('expired_stock', $expiredStock);
            $product->setAttribute('current_purchase_price', (float) ($currentPrice?->price_purchase ?? 0));
            $product->setAttribute('current_sale_price', (float) ($currentPrice?->price_sale ?? 0));
            $product->setAttribute('main_provider', $latestStock?->provider?->name ?? '—');
            $product->setAttribute('main_unit', $latestStock?->unit?->name ?? '—');

            return $product;
        });
    }
}
