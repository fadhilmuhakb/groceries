@extends('layouts.app')

@section('content')
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Order Stock</h4>
        <div class="d-flex gap-2">
          @if(session('success')) <span class="text-success fw-bold">{{ session('success') }}</span> @endif
          @if(session('warning')) <span class="text-warning fw-bold">{{ session('warning') }}</span> @endif
          @if(session('error'))   <span class="text-danger fw-bold">{{ session('error') }}</span> @endif
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <form method="GET" action="{{ route('order-stock.index') }}" class="row g-2 align-items-end">
            @if($isSuperadmin)
              <div class="col-md-6">
                <label class="form-label">Pilih Toko</label>
                <select name="store" class="form-select" onchange="this.form.submit()">
                  <option value="">-- pilih toko --</option>
                  @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected($selected == $store->id)>{{ $store->store_name }}</option>
                  @endforeach
                </select>
              </div>
            @else
              <div class="col-md-6">
                <label class="form-label">Toko</label>
                <input type="text" class="form-control" value="{{ $currentStore ?? '-' }}" disabled>
              </div>
            @endif
          </form>
        </div>
      </div>

      @if(!$selected)
        <div class="alert alert-info">Pilih toko untuk melihat produk yang perlu dipesan.</div>
      @elseif($items->isEmpty())
        <div class="alert alert-success">Semua stok aman.</div>
      @else
      <form method="POST" action="{{ route('order-stock.restock') }}">
        @csrf
        <input type="hidden" name="store_id" value="{{ $selected }}">
        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th style="width:30px"><input type="checkbox" id="check-all"></th>
                    <th>Kode</th>
                    <th>Produk</th>
                    <th>Stok</th>
                    <th>Min</th>
                    <th>Max</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($items as $item)
                    <tr>
                      <td><input type="checkbox" name="items[]" value="{{ $item->id }}" class="row-check"></td>
                      <td>{{ $item->product_code }}</td>
                      <td>{{ $item->product_name }}</td>
                      <td>{{ $item->stock_system }}</td>
                      <td>{{ $item->min_stock ?? '-' }}</td>
                      <td>{{ $item->max_stock ?? '-' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="text-end mt-3">
              <button type="submit" class="btn btn-primary">Checklist &amp; Restock ke Max</button>
            </div>
          </div>
        </div>
      </form>
      @endif
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const checkAll = document.getElementById('check-all');
  if (checkAll) {
    checkAll.addEventListener('change', (e) => {
      document.querySelectorAll('.row-check').forEach(cb => cb.checked = e.target.checked);
    });
  }
</script>
@endsection
