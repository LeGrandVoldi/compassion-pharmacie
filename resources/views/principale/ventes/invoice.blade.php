<div class="container py-4">
  <div class="text-center mb-4">
    <img src="{{ asset('Imgs/Logos/logo_full.png') }}" alt="Logo" style="max-height:90px">
    <h3 class="mt-2">FACTURE DE VENTE</h3>
    <div class="text-muted">Compassion Pharmacie</div>
  </div>

  <div class="row mb-4">
    <div class="col-md-6">
      <strong>Vente :</strong> #{{ $sale->id }}<br>
      <strong>Date :</strong> {{ $sale->created_at }}<br>
      <strong>Vendeur :</strong> {{ $sale->user?->name ?? '—' }}
    </div>
    <div class="col-md-6 text-md-end">
      <strong>Client :</strong> {{ $sale->client_number ?? '—' }}<br>
      <strong>Paiement :</strong> {{ $sale->payment_type }}<br>
      <strong>Statut :</strong> {{ $sale->status }}
    </div>
  </div>

  <table class="table table-bordered align-middle">
    <thead>
      <tr>
        <th>Produit</th>
        <th>Unité</th>
        <th>Qté</th>
        <th>Prix</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sale->items as $item)
        <tr>
          <td>{{ $item->product?->name }}</td>
          <td>{{ $item->unit?->name }}</td>
          <td>{{ $item->quantity }}</td>
          <td>{{ number_format($item->price, 0, ',', ' ') }} CDF</td>
          <td>{{ number_format($item->total, 0, ',', ' ') }} CDF</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th colspan="4" class="text-end">TOTAL</th>
        <th>{{ number_format($sale->total_amount, 0, ',', ' ') }} CDF</th>
      </tr>
    </tfoot>
  </table>
</div>
