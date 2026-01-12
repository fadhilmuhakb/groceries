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
      <form method="POST" action="{{ route('stock-threshold.save') }}" id="threshold-form">
        @csrf
        <input type="hidden" name="store_id" value="{{ $storeId }}">
        <input type="hidden" name="expected_count" value="0">
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
                    <tr data-id="{{ $row->id }}" data-name="{{ strtolower($row->product_name) }}" data-code="{{ strtolower($row->product_code) }}">
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
              <button type="submit" class="btn btn-primary" id="threshold-submit">
                <span class="btn-label">Simpan</span>
                <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
              </button>
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
  const form = document.getElementById('threshold-form');
  const submitButton = document.getElementById('threshold-submit');
  const expectedCountInput = form ? form.querySelector('input[name="expected_count"]') : null;
  let isSubmitting = false;
  if (!searchInput || !body) return;

  const setButtonBusy = (button, busy, busyLabel = 'Menyimpan...') => {
    if (!button) return;
    button.disabled = busy;
    const label = button.querySelector('.btn-label');
    if (label) {
      if (!label.dataset.original) label.dataset.original = label.textContent;
      label.textContent = busy ? busyLabel : label.dataset.original;
    }
    const spinner = button.querySelector('.spinner-border');
    if (spinner) spinner.classList.toggle('d-none', !busy);
  };

  const rows = Array.from(body.querySelectorAll('tr'));
  const dirtyIds = new Set();

  const updateRowDirty = (tr) => {
    const inputs = tr.querySelectorAll('input[type="number"]');
    let dirty = false;
    inputs.forEach(input => {
      if (input.dataset.original === undefined) {
        input.dataset.original = input.value ?? '';
      }
      const current = input.value ?? '';
      if (current !== input.dataset.original) dirty = true;
    });
    const rowId = tr.dataset.id;
    if (!rowId) return;
    if (dirty) {
      dirtyIds.add(rowId);
    } else {
      dirtyIds.delete(rowId);
    }
  };

  rows.forEach(tr => {
    tr.querySelectorAll('input[type="number"]').forEach(input => {
      if (input.dataset.original === undefined) {
        input.dataset.original = input.value ?? '';
      }
      input.addEventListener('input', () => updateRowDirty(tr));
    });
  });
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

  if (form && submitButton) {
    form.addEventListener('submit', (e) => {
      if (isSubmitting) {
        e.preventDefault();
        return;
      }
      if (expectedCountInput) expectedCountInput.value = String(dirtyIds.size);
      if (dirtyIds.size === 0) {
        rows.forEach(tr => {
          tr.querySelectorAll('input[type="number"]').forEach(input => {
            input.disabled = true;
          });
        });
      } else {
        rows.forEach(tr => {
          if (!dirtyIds.has(tr.dataset.id)) {
            tr.querySelectorAll('input[type="number"]').forEach(input => {
              input.disabled = true;
            });
          }
        });
      }
      isSubmitting = true;
      setButtonBusy(submitButton, true);
    });
  }
});
</script>
@endpush
