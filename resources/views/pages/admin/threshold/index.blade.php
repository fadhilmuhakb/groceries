@extends('layouts.app')

@section('content')
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Atur Stok Min / Max per Toko</h4>
        @if(session('success')) <span class="text-success fw-bold">{{ session('success') }}</span> @endif
        @if(session('warning')) <span class="text-warning fw-bold">{{ session('warning') }}</span> @endif
        @if(session('error')) <span class="text-danger fw-bold">{{ session('error') }}</span> @endif
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <form method="GET" action="{{ route('stock-threshold.index') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
              <label class="form-label">Pilih Toko</label>
              <select name="store" class="form-select" onchange="this.form.submit()">
                <option value="">-- pilih toko --</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected($storeId == $store->id)>{{ $store->store_name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Cari Produk</label>
              <div class="input-group">
                <input type="text" id="threshold-search" class="form-control" name="q" value="{{ $search ?? '' }}" placeholder="Scan barcode / ketik nama atau kode">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      @if(!$storeId)
        <div class="alert alert-info">Pilih toko untuk mengatur batas stok.</div>
      @else
      <form method="POST" action="{{ route('stock-threshold.save') }}">
        @csrf
        <input type="hidden" name="store_id" value="{{ $storeId }}">
        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Kode</th>
                    <th>Produk</th>
                    <th>Stok</th>
                    <th>Stok Min</th>
                    <th>Stok Max</th>
                  </tr>
                </thead>
                <tbody id="threshold-table-body">
                  @foreach($rows as $row)
                    <tr data-name="{{ strtolower($row->product_name) }}" data-code="{{ strtolower($row->product_code) }}">
                      <td>{{ $row->product_code }}</td>
                      <td>{{ $row->product_name }}</td>
                      <td>{{ $row->stock_system }}</td>
                      <td style="max-width:150px">
                        <input type="number" min="0" class="form-control" name="items[{{ $row->id }}][min_stock]" value="{{ $row->min_stock }}">
                      </td>
                      <td style="max-width:150px">
                        <input type="number" min="0" class="form-control" name="items[{{ $row->id }}][max_stock]" value="{{ $row->max_stock }}">
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="text-end mt-3">
              <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
          </div>
        </div>
      </form>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('threshold-search');
  const body = document.getElementById('threshold-table-body');
  if (!searchInput || !body) return;

  const rows = Array.from(body.querySelectorAll('tr'));
  const filter = (term) => {
    const keyword = (term || '').toLowerCase().trim();
    let firstMatch = null;
    rows.forEach(tr => {
      const name = tr.dataset.name || '';
      const code = tr.dataset.code || '';
      const show = !keyword || name.includes(keyword) || code.includes(keyword);
      tr.style.display = show ? '' : 'none';
      if (show && !firstMatch) firstMatch = tr;
    });
    if (keyword && firstMatch) {
      firstMatch.classList.add('table-warning');
      firstMatch.scrollIntoView({behavior:'smooth', block:'center'});
      setTimeout(() => firstMatch.classList.remove('table-warning'), 900);
    }
  };

  searchInput.addEventListener('input', () => filter(searchInput.value));
  filter(searchInput.value);
  searchInput.focus();
});
</script>
@endpush
