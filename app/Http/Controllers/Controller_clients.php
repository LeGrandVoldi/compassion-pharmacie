<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Controller_clients extends Controller
{
    private function clientsPermissions(): array
    {
        $isAdmin = session('role') === 'admin';
        $permissions = session('permissions', []);

        return [
            'is_admin' => $isAdmin,
            'view'     => $isAdmin ? true : !empty(data_get($permissions, 'clients.view', 1)),
            'add'      => $isAdmin ? true : !empty(data_get($permissions, 'clients.add')),
            'edit'     => $isAdmin ? true : !empty(data_get($permissions, 'clients.edit')),
            'delete'   => false,
        ];
    }

    private function hasAnyClientsPermission(): bool
    {
        $p = $this->clientsPermissions();
        return $p['view'] || $p['add'] || $p['edit'];
    }

    private function denyJson(string $message, int $code = 403)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }

    private function generateClientNumber(): string
    {
        do {
            $number = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (DB::table('clients')->where('client_number', $number)->exists());

        return $number;
    }

    private function clientSalesAggregates()
    {
        return DB::table('sales')
            ->select('client_number')
            ->selectRaw('COUNT(*) as total_sales')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_spent')
            ->selectRaw('MAX(created_at) as last_purchase_at')
            ->whereNotNull('client_number')
            ->groupBy('client_number')
            ->get()
            ->keyBy('client_number');
    }

    private function clientToArray($client, $agg = null): array
    {
        return [
            'id'               => $client->id,
            'client_number'    => $client->client_number,
            'name'             => $client->name,
            'phone'            => $client->phone,
            'address'          => $client->address,
            'total_sales'      => (int) ($agg->total_sales ?? 0),
            'total_spent'      => (float) ($agg->total_spent ?? 0),
            'last_purchase_at' => $agg->last_purchase_at ?? null,
        ];
    }

    private function buildClientsStats(): array
    {
        $today = now()->toDateString();

        $salesToday = DB::table('sales')
            ->whereDate('created_at', $today)
            ->whereNotNull('client_number')
            ->pluck('client_number', 'total_amount')
            ->all();

        $aggregates = $this->clientSalesAggregates();

        $topClientNumber = $aggregates
            ->sortByDesc(fn($row) => (float) $row->total_spent)
            ->keys()
            ->first();

        $topClient = $topClientNumber
            ? DB::table('clients')
                ->where('client_number', $topClientNumber)
                ->first(['id', 'name', 'client_number'])
            : null;

        return [
            'total_clients'         => DB::table('clients')->count(),
            'clients_with_sales_day' => collect($salesToday)->filter()->unique()->count(),
            'total_amount_day'      => (float) DB::table('sales')
                ->whereDate('created_at', $today)
                ->whereNotNull('client_number')
                ->sum('total_amount'),
            'top_client'            => $topClient
                ? $topClient->name . ' (' . $topClient->client_number . ')'
                : '—',
        ];
    }

    public function index()
    {
        if (!$this->hasAnyClientsPermission()) {
            abort(403, "Vous n'avez pas le droit d'accéder au module clients.");
        }

        $clients = DB::table('clients')
            ->orderBy('name')
            ->get();

        $salesAgg = $this->clientSalesAggregates();

        $clientsData = $clients->map(fn($client) =>
            $this->clientToArray($client, $salesAgg->get($client->client_number))
        )->values();

        return view('principale.clients.index', [
            'clients'              => $clientsData,
            'stats'                => $this->buildClientsStats(),
            'clientsPermissions'   => $this->clientsPermissions(),
            'nom'                  => session('nom'),
            'email'                => session('email'),
            'role'                 => session('role'),
        ]);
    }

    public function listAll(Request $request)
    {
        if (!$this->hasAnyClientsPermission()) {
            return $this->denyJson("Vous n'avez pas le droit d'accéder au module clients.");
        }

        $q = trim((string) $request->input('q', ''));

        $query = DB::table('clients')->orderBy('name');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('client_number', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $clients = $query->get();
        $salesAgg = $this->clientSalesAggregates();

        return response()->json([
            'success' => true,
            'clients' => $clients->map(fn($client) =>
                $this->clientToArray($client, $salesAgg->get($client->client_number))
            )->values(),
            'stats'   => $this->buildClientsStats(),
        ]);
    }

    public function searchClients(Request $request)
    {
        if (!$this->hasAnyClientsPermission()) {
            return $this->denyJson("Accès refusé.");
        }

        $q = trim((string) $request->input('q', ''));

        $query = DB::table('clients')->orderBy('name');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('client_number', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $items = $query->limit(20)->get()->map(fn($client) => [
            'id'            => $client->id,
            'client_number' => $client->client_number,
            'name'          => $client->name,
            'phone'         => $client->phone,
            'address'       => $client->address,
        ]);

        return response()->json([
            'success' => true,
            'items'   => $items,
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->clientsPermissions()['add']) {
            return $this->denyJson("Vous n'avez pas le droit d'ajouter un client.");
        }

        $validated = $request->validate([
            'client_number' => ['nullable', 'string', 'max:20', 'unique:clients,client_number'],
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'address'       => ['nullable', 'string', 'max:255'],
        ]);

        $id = DB::table('clients')->insertGetId([
            'client_number' => $validated['client_number'] ?? $this->generateClientNumber(),
            'name'          => $validated['name'],
            'phone'         => $validated['phone'] ?? null,
            'address'       => $validated['address'] ?? null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $client = DB::table('clients')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Client ajouté avec succès.',
            'client'  => $client,
        ]);
    }

    public function update(Request $request, $clientId)
    {
        if (!$this->clientsPermissions()['edit']) {
            return $this->denyJson("Vous n'avez pas le droit de modifier un client.");
        }

        $client = DB::table('clients')->where('id', $clientId)->first();
        if (!$client) {
            return $this->denyJson("Client non trouvé.", 404);
        }

        $validated = $request->validate([
            'client_number' => ['nullable', 'string', 'max:20', 'unique:clients,client_number,' . $clientId],
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'address'       => ['nullable', 'string', 'max:255'],
        ]);

        DB::table('clients')->where('id', $clientId)->update([
            'client_number' => $validated['client_number'] ?? $client->client_number,
            'name'          => $validated['name'],
            'phone'         => $validated['phone'] ?? null,
            'address'       => $validated['address'] ?? null,
            'updated_at'    => now(),
        ]);

        $updated = DB::table('clients')->where('id', $clientId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Client modifié avec succès.',
            'client'  => $updated,
        ]);
    }

    public function history($clientId, Request $request)
    {
        if (!$this->hasAnyClientsPermission()) {
            return $this->denyJson("Accès refusé.");
        }

        $client = DB::table('clients')->where('id', $clientId)->first();

        if (!$client) {
            return $this->denyJson("Client non trouvé.", 404);
        }

        $from = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : null;

        $to = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : null;

        $salesQuery = DB::table('sales')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->leftJoin('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('product_units', 'sale_items.product_unit_id', '=', 'product_units.id')
            ->where('sales.client_number', $client->client_number)
            ->select(
                'sales.id',
                'sales.created_at',
                'sales.payment_type',
                'sales.status',
                'sales.total_amount',
                'users.name as user_name',
                DB::raw("
                    COALESCE(
                        JSON_ARRAYAGG(
                            JSON_OBJECT(
                                'product_name', products.name,
                                'quantity', sale_items.quantity,
                                'price', sale_items.price,
                                'total', sale_items.total
                            )
                        ),
                        JSON_ARRAY()
                    ) as items
                ")
            )
            ->groupBy(
                'sales.id',
                'sales.created_at',
                'sales.payment_type',
                'sales.status',
                'sales.total_amount',
                'users.name'
            )
            ->orderByDesc('sales.created_at');

        if ($from) {
            $salesQuery->where('sales.created_at', '>=', $from);
        }

        if ($to) {
            $salesQuery->where('sales.created_at', '<=', $to);
        }

        $sales = $salesQuery->get();

        $totalAmount = (float) $sales->sum('total_amount');

        $totalItems = $sales->flatMap(function ($sale) {
            return json_decode($sale->items, true);
        })->sum('quantity');

        return response()->json([
            'success' => true,

            'client' => [
                'id'            => $client->id,
                'client_number' => $client->client_number,
                'name'          => $client->name,
                'phone'         => $client->phone,
                'address'       => $client->address,
            ],

            'summary' => [
                'total_sales' => $sales->count(),
                'total_amount' => $totalAmount,
                'total_items'  => (int) $totalItems,
                'first_date'   => optional($sales->last())?->created_at,
                'last_date'    => optional($sales->first())?->created_at,
            ],

            'sales' => $sales->map(function ($sale) {
                return [
                    'id'           => $sale->id,
                    'created_at'   => $sale->created_at,
                    'user_name'    => $sale->user_name ?? '—',
                    'payment_type' => $sale->payment_type,
                    'status'       => $sale->status,
                    'total_amount' => (float) $sale->total_amount,
                    'items'        => json_decode($sale->items, true) ?? [],
                ];
            })->values(),
        ]);
    }
}
