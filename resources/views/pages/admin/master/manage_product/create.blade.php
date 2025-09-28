@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
  <div class="breadcrumb-title pe-3">Product</div>
</div>

<div class="row">
  <div class="col-xl-9 mx-auto">
    <h6 class="mb-0 text-uppercase">{{ isset($product) ? 'Edit' : 'Tambah' }} Produk</h6>
    <hr />
    <div class="card">
      <div class="card-body">

        @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ isset($product) ? route('master-product.update', $product->id) : route('master-product.store') }}">
          @csrf
          @if(isset($product)) @method('PUT') @endif

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Kode Produk</label>
              <input name="product_code" class="form-control"
                     value="{{ old('product_code', $product->product_code ?? '') }}" required>
              @error('product_code') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Nama Produk</label>
              <input name="product_name" class="form-control"
                     value="{{ old('product_name', $product->product_name ?? '') }}" required>
              @error('product_name') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Tipe</label>
              <select class="form-select" name="type_id" required>
                <option value="">- pilih -</option>
                @foreach($types as $t)
                  <option value="{{ $t->id }}" @selected(old('type_id', $product->type_id ?? '') == $t->id)>
                    {{ $t->type_name ?? $t->name }}
                  </option>
                @endforeach
              </select>
              @error('type_id') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Merek</label>
              <select class="form-select" name="brand_id" required>
                <option value="">- pilih -</option>
                @foreach($brands as $b)
                  <option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $b->id)>
                    {{ $b->brand_name ?? $b->name }}
                  </option>
                @endforeach
              </select>
              @error('brand_id') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Satuan</label>
              <select class="form-select" name="unit_id" required>
                <option value="">- pilih -</option>
                @foreach($units as $u)
                  <option value="{{ $u->id }}" @selected(old('unit_id', $product->unit_id ?? '') == $u->id)>
                    {{ $u->unit_name ?? $u->name }}
                  </option>
                @endforeach
              </select>
              @error('unit_id') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Harga Beli</label>
              <input type="number" step="0.01" min="0" name="purchase_price" class="form-control"
                     value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" required>
              @error('purchase_price') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Harga Jual (normal)</label>
              <input type="number" step="0.01" min="0" name="selling_price" class="form-control"
                     value="{{ old('selling_price', $product->selling_price ?? 0) }}" required>
              @error('selling_price') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Diskon</label>
              <input type="number" step="0.01" min="0" name="product_discount" class="form-control"
                     value="{{ old('product_discount', $product->product_discount ?? 0) }}">
              @error('product_discount') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
              <label class="form-label">Keterangan</label>
              <textarea name="description" class="form-control">{{ old('description', $product->description ?? '') }}</textarea>
            </div>

            <div class="col-12"><hr></div>

            {{-- ================== HARGA TIER ================== --}}
            <div class="col-12">
              <h6 class="fw-bold">Harga Tier (opsional)</h6>
              <p class="text-muted">
                Contoh: Qty minimal = <b>2</b>, Harga per unit = <b>800</b> dan Qty minimal = <b>3</b>, Harga per unit = <b>700</b>.<br>
                Artinya: beli 1 = harga normal, beli ≥2 = 800, beli ≥3 = 700 (4,5,dst tetap 700).
              </p>

              @php
                $oldTiers = old('tier_prices');
                if(!$oldTiers && isset($product) && is_array($product->tier_prices)) {
                    $oldTiers = [];
                    foreach($product->tier_prices as $q=>$p) { $oldTiers[] = ['qty'=>$q, 'price'=>$p]; }
                }
                if(!$oldTiers) $oldTiers = [['qty'=>'','price'=>'']];
              @endphp

              <div id="tier-rows">
                @foreach($oldTiers as $i => $row)
                  <div class="row g-2 tier-row align-items-end mb-2">
                    <div class="col-md-4">
                      <label class="form-label">Qty minimal</label>
                      <input type="number" min="1" class="form-control"
                             name="tier_prices[{{ $i }}][qty]"
                             value="{{ $row['qty'] ?? '' }}" placeholder="mis. 2">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Harga per Unit</label>
                      <input type="number" min="0" step="0.01" class="form-control"
                             name="tier_prices[{{ $i }}][price]"
                             value="{{ $row['price'] ?? '' }}" placeholder="mis. 800">
                    </div>
                    <div class="col-md-4">
                      <button type="button" class="btn btn-outline-danger remove-tier">Hapus</button>
                    </div>
                  </div>
                @endforeach
              </div>

              <button type="button" id="add-tier" class="btn btn-outline-primary">Tambah Tier</button>

              @error('tier_prices')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
              @error('tier_prices.*.qty')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
              @error('tier_prices.*.price')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
            {{-- ================== END HARGA TIER ================== --}}

            <div class="col-12 text-end mt-3">
              <a href="{{ route('master-product.index') }}" class="btn btn-secondary">Batal</a>
              <button class="btn btn-primary" type="submit">{{ isset($product) ? 'Update' : 'Simpan' }}</button>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@section('scripts')
<script>
(function () {
  const rows = document.getElementById('tier-rows');

  if (!rows) return;
  document.addEventListener('click', function (e) {
    // Tambah Tier
    if (e.target && e.target.id === 'add-tier') {
      const idx = rows.querySelectorAll('.tier-row').length;
      const div = document.createElement('div');
      div.className = 'row g-2 tier-row align-items-end mb-2';
      div.innerHTML = `
        <div class="col-md-4">
          <label class="form-label">Qty minimal</label>
          <input type="number" min="1" class="form-control" name="tier_prices[${idx}][qty]" placeholder="mis. 2">
        </div>
        <div class="col-md-4">
          <label class="form-label">Harga per Unit</label>
          <input type="number" min="0" step="0.01" class="form-control" name="tier_prices[${idx}][price]" placeholder="mis. 800">
        </div>
        <div class="col-md-4">
          <button type="button" class="btn btn-outline-danger remove-tier">Hapus</button>
        </div>
      `;
      rows.appendChild(div);
    }

    // Hapus baris tier
    if (e.target && e.target.classList.contains('remove-tier')) {
      const row = e.target.closest('.tier-row');
      if (row) row.remove();
      reindex();
    }
  });

  function reindex() {
    rows.querySelectorAll('.tier-row').forEach((row, idx) => {
      row.querySelectorAll('input').forEach(inp => {
        if (/\[qty\]/.test(inp.name))   inp.name = `tier_prices[${idx}][qty]`;
        if (/\[price\]/.test(inp.name)) inp.name = `tier_prices[${idx}][price]`;
      });
    });
  }
})();
</script>
@endsection

@endpush
