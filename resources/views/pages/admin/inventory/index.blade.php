@extends('layouts.app')

@section('content')
<style>
  /* Buat kolom jumlah fisik cukup lebar untuk layar kecil */
  #stock-table input.physical-qty {
    min-width: 110px;
    text-align: right;
  }

  @media (max-width: 768px) {
    #stock-table td, #stock-table th {
      white-space: nowrap;
    }
  }
</style>
<div class="container">
    <h4 class="mb-4">Kelola Stok Fisik Produk Per Toko</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(Auth::user()->roles == 'superadmin')
    <form method="GET" action="{{ route('inventory.index') }}" class="mb-3 w-auto">
        <select name="store_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Pilih Toko --</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" {{ (int)request('store_id') === $store->id ? 'selected' : '' }}>
                    {{ $store->store_name }}
                </option>
            @endforeach
        </select>
    </form>
    @endif

    @if(!$query->isEmpty())
    <div class="mb-3">
        <input type="text" id="search-product" class="form-control" placeholder="Scan barcode atau ketik nama/kode produk...">
        <small class="text-muted">Arahkan scanner ke input ini untuk langsung memfilter item sesuai barcode/kode.</small>
    </div>
    @endif

    <form id="stock-form" action="{{ route('inventory.adjustStockPreview') }}" method="POST">
        @csrf

        <table class="table table-bordered table-striped" id="stock-table">
            <thead>
                <tr>
                    <th style="width:40px;">No.</th>
                    <th>Nama Produk</th>
                    <th>Nama Toko</th>
                    <th class="text-end">Harga Beli (toko)</th>
                    <th class="text-end">Harga Jual (toko)</th>
                    <th class="text-end">Jumlah Sistem</th>
                    <th>Jumlah Fisik</th>
                    <th class="text-end">Jumlah Minus</th>
                    <th class="text-end">Minus Barang (unit)</th>
                    <th class="text-end">Total Minus (Rp)</th>
                    <th class="text-end">Plus Barang (unit)</th>
                    <th class="text-end">Total Plus (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($query as $index => $row)
                <tr data-price="{{ $row->purchase_price }}"
                    data-code="{{ strtolower($row->product_code ?? '') }}"
                    data-name="{{ strtolower($row->product_name ?? '') }}">
                    <td>{{ $index + 1 }}</td>
                    <td class="product-name">
                        <div class="fw-semibold">{{ $row->product_name }}</div>
                        @if(!empty($row->product_code))
                            <div class="text-muted small">Kode: {{ $row->product_code }}</div>
                        @endif
                    </td>
                    <td>{{ $row->store_name }}</td>
                    <td class="text-end">{{ number_format((float)$row->purchase_price, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format((float)$row->selling_price, 2, ',', '.') }}</td>

                    {{-- GUNAKAN system_stock_raw untuk tampilan stok sistem --}}
                    <td class="text-end system-stock">{{ number_format((int)$row->system_stock_raw) }}</td>

                    <td>
                        <input type="hidden" name="product_id[]" value="{{ $row->product_id }}">
                        <input type="hidden" name="store_id[]" value="{{ $row->store_id }}">

                        {{-- Prefill fisik = nilai SO jika ada; kalau null pakai stok sistem --}}
                        <input type="number" name="physical_quantity[]"
                               value="{{ is_null($row->physical_quantity) ? (int)$row->system_stock_raw : (int)$row->physical_quantity }}"
                               min="0" class="form-control physical-qty" required>
                    </td>

                    <td class="text-end minus-qty">0</td>
                    <td class="text-end minus-qty">0</td>
                    <td class="text-end minus-value">Rp 0</td>
                    <td class="text-end plus-qty">0</td>
                    <td class="text-end plus-value">Rp 0</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="7" class="text-end">TOTAL</td>
                    <td class="text-end" id="total-minus-qty">0</td>
                    <td class="text-end" id="total-minus-unit">0</td>
                    <td class="text-end" id="total-minus-value">Rp 0</td>
                    <td class="text-end" id="total-plus-unit">0</td>
                    <td class="text-end" id="total-plus-value">Rp 0</td>
                </tr>
                <tr class="fw-bold">
                    <td colspan="11" class="text-end">TOTAL PLUS - MINUS</td>
                    <td class="text-end" id="total-plus-minus">Rp 0</td>
                </tr>
            </tfoot>
        </table>

        <button type="submit" class="btn btn-primary mt-3" id="adjustAllBtn">
            <span class="btn-label">Simpan</span>
            <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
        </button>
    </form>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('stock-table');
    const form = document.getElementById('stock-form');
    const adjustAllBtn = document.getElementById('adjustAllBtn');
    let isSubmitting = false;

    const totalMinusQtyEl = document.getElementById('total-minus-qty');
    const totalMinusUnitEl = document.getElementById('total-minus-unit');
    const totalMinusValueEl = document.getElementById('total-minus-value');
    const totalPlusUnitEl = document.getElementById('total-plus-unit');
    const totalPlusValueEl = document.getElementById('total-plus-value');
    const totalPlusMinusEl = document.getElementById('total-plus-minus');
    let highlightTimeout = null;

    function updateTotals() {
        let totalMinusQty = 0, totalMinusUnit = 0, totalMinusValue = 0, totalPlusUnit = 0, totalPlusValue = 0;
        table.querySelectorAll('tbody tr').forEach(tr => {
            const minusQty = parseInt(tr.querySelectorAll('.minus-qty')[0].textContent.replace(/\./g, '')) || 0;
            const minusUnit = parseInt(tr.querySelectorAll('.minus-qty')[1].textContent.replace(/\./g, '')) || 0;
            const minusValue = parseInt(tr.querySelector('.minus-value').textContent.replace(/[^\d]/g, '')) || 0;
            const plusQty = parseInt(tr.querySelector('.plus-qty').textContent.replace(/\./g, '')) || 0;
            const plusValue = parseInt(tr.querySelector('.plus-value').textContent.replace(/[^\d]/g, '')) || 0;
            totalMinusQty += minusQty; totalMinusUnit += minusUnit; totalMinusValue += minusValue;
            totalPlusUnit += plusQty; totalPlusValue += plusValue;
        });
        totalMinusQtyEl.textContent = totalMinusQty.toLocaleString('id-ID');
        totalMinusUnitEl.textContent = totalMinusUnit.toLocaleString('id-ID');
        totalMinusValueEl.textContent = 'Rp ' + totalMinusValue.toLocaleString('id-ID');
        totalPlusUnitEl.textContent = totalPlusUnit.toLocaleString('id-ID');
        totalPlusValueEl.textContent = 'Rp ' + totalPlusValue.toLocaleString('id-ID');
        totalPlusMinusEl.textContent = 'Rp ' + (totalPlusValue - totalMinusValue).toLocaleString('id-ID');
    }

    table.querySelectorAll('.physical-qty').forEach(input => {
        input.addEventListener('input', (e) => {
            const tr = e.target.closest('tr');
            const systemStock = parseInt(tr.querySelector('.system-stock').textContent.replace(/[^\d]/g, '')) || 0;
            const physicalQty = parseInt(e.target.value) || 0;
            const purchasePrice = parseInt(tr.getAttribute('data-price')) || 0;

            const minusQty = Math.max(0, systemStock - physicalQty);
            const plusQty  = Math.max(0, physicalQty - systemStock);

            tr.querySelectorAll('.minus-qty').forEach(td => td.textContent = minusQty.toLocaleString('id-ID'));
            tr.querySelector('.minus-value').textContent = 'Rp ' + (minusQty * purchasePrice).toLocaleString('id-ID');
            tr.querySelector('.plus-qty').textContent = plusQty.toLocaleString('id-ID');
            tr.querySelector('.plus-value').textContent = 'Rp ' + (plusQty * purchasePrice).toLocaleString('id-ID');

            updateTotals();
        });
    });

    updateTotals();

    const setButtonBusy = (button, busy, busyLabel = 'Memproses...') => {
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

    const setSubmitting = (busy) => {
        isSubmitting = busy;
        setButtonBusy(adjustAllBtn, busy);
    };

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        if (isSubmitting) return;
        submitForm();
    });

    function readCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        const hidden = document.querySelector('#stock-form input[name="_token"]');
        return hidden ? hidden.value : '';
    }

    function submitForm() {
        if (isSubmitting) return;
        setSubmitting(true);
        // KIRIM JSON ke endpoint preview
        const items = [];
        table.querySelectorAll('tbody tr').forEach(tr => {
            const productId = parseInt(tr.querySelector('input[name="product_id[]"]').value);
            const storeId   = parseInt(tr.querySelector('input[name="store_id[]"]').value);
            const physical  = parseInt(tr.querySelector('.physical-qty').value) || 0;
            items.push({ product_id: productId, store_id: storeId, physical_quantity: physical });
        });

        const csrfToken = readCsrfToken();
        const xsrfCookie = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const xsrfToken = xsrfCookie ? decodeURIComponent(xsrfCookie[1]) : csrfToken;

        fetch(form.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-XSRF-TOKEN': xsrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ items, _token: csrfToken })
        }).then(async res => {
            const ct = res.headers.get('content-type') || '';
            const payload = ct.includes('application/json') ? await res.json().catch(() => ({})) : await res.text();
            if (!res.ok) {
                if (res.status === 419) {
                    throw new Error('Sesi berakhir/CSRF token kedaluwarsa. Mohon refresh halaman lalu kirim lagi.');
                }
                const msg = typeof payload === 'string' ? payload : (payload.message || JSON.stringify(payload));
                throw new Error(msg || `HTTP ${res.status}`);
            }
            return payload;
        }).then(data => {
            if (data && data.redirect_url) {
                window.location.assign(data.redirect_url);
                return;
            }
            throw new Error('Redirect ringkasan tidak tersedia.');
        }).catch(err => {
            setSubmitting(false);
            alert('Gagal menyiapkan ringkasan.\n' + err.message);
        });
    }

    // SEARCH
    const searchInput = document.getElementById('search-product');
    if (searchInput) {
        const rows = table.querySelectorAll('tbody tr');

        const filterRows = (term) => {
            const keyword = (term || '').trim().toLowerCase();
            let firstMatch = null;

            rows.forEach(tr => {
                const name = (tr.dataset.name || tr.querySelector('.product-name').textContent || '').toLowerCase();
                const code = (tr.dataset.code || '').toLowerCase();
                const matches = !keyword || name.includes(keyword) || code.includes(keyword);
                tr.style.display = matches ? '' : 'none';
                if (matches && !firstMatch) {
                    firstMatch = tr;
                }
            });

            // Highlight dan scroll ke hasil pertama untuk scanner
            table.querySelectorAll('tbody tr.table-warning').forEach(row => row.classList.remove('table-warning'));
            if (keyword && firstMatch) {
                firstMatch.classList.add('table-warning');
                firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
                clearTimeout(highlightTimeout);
                highlightTimeout = setTimeout(() => {
                    firstMatch.classList.remove('table-warning');
                }, 1200);
            }
        };

        searchInput.addEventListener('input', () => filterRows(searchInput.value));
        filterRows(searchInput.value);
        searchInput.focus();
    }
});
</script>
@endsection
