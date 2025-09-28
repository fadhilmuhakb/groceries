@extends('layouts.app')

@section('content')
<div class="container">
  <h3>Detail Produk</h3>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card">
    <div class="card-body">
      <div class="row mb-2">
        <div class="col-4">Kode</div>
        <div class="col-8"><strong>{{ $product->product_code }}</strong></div>
      </div>
      <div class="row mb-2">
        <div class="col-4">Nama</div>
        <div class="col-8"><strong>{{ $product->product_name }}</strong></div>
      </div>
      <div class="row mb-2">
        <div class="col-4">Harga Jual Dasar</div>
        <div class="col-8"><strong>{{ number_format($product->selling_price,0) }}</strong></div>
      </div>
      <div class="row mb-2">
        <div class="col-4">Tier Prices</div>
        <div class="col-8">
          @php
            $tiers = collect($product->tier_prices ?? [])->mapWithKeys(fn($price,$q)=>[(int)$q => (float)$price])->sortKeys();
          @endphp
          @if($tiers->isEmpty())
            <span class="text-muted">Tidak ada tier</span>
          @else
            <ul class="mb-0">
              @foreach($tiers as $q=>$price)
                <li>Qty ≥ {{ $q }} → {{ number_format($price,0) }}</li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>

      <div class="row mb-2">
        <div class="col-4">Contoh Simulasi</div>
        <div class="col-8">
          @php
            $sim = [1,2,3,4,5];
          @endphp
          <ul class="mb-0">
            @foreach($sim as $q)
              @php $u = $product->unitPriceForQty($q); @endphp
              <li>Beli {{ $q }} → Harga/unit {{ number_format($u,0) }} | Total {{ number_format($u*$q,0) }}</li>
            @endforeach
          </ul>
        </div>
      </div>

      <a href="{{ route('products.edit',$product->id) }}" class="btn btn-warning">Edit</a>
      <a href="{{ route('products.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
  </div>
</div>
@endsection
