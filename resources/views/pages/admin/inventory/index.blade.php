@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">Kelola Stok Fisik Produk Per Toko</h4>

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
        <input type="text" id="search-product" class="form-control" placeholder="Cari Produk...">
    </div>
    @endif

    <div id="alert-success" class="alert alert-success d-none"></div>

    <form id="stock-form" action="{{ route('inventory.adjustStockBulk') }}" method="POST">
        @csrf

        <table class="table table-bordered table-striped" id="stock-table">
            <thead>
                <tr>
                    <th style="width:40px;">No.</th>
                    <th>Nama Produk</th>
                    <th>Nama Toko</th>
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
                <tr data-price="{{ $row->purchase_price }}">
                    <td>{{ $index + 1 }}</td>
                    <td class="product-name">{{ $row->product_name }}</td>
                    <td>{{ $row->store_name }}</td>
                    <td class="text-end system-stock">{{ number_format($row->system_stock) }}</td>
                    <td>
                        <input type="hidden" name="product_id[]" value="{{ $row->product_id }}">
                        <input type="hidden" name="store_id[]" value="{{ $row->store_id }}">
                        <input type="number" name="physical_quantity[]" value="{{ $row->system_stock }}" min="0"
                            class="form-control physical-qty" required>
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
                    <td colspan="5" class="text-end">TOTAL</td>
                    <td class="text-end" id="total-minus-qty">0</td>
                    <td class="text-end" id="total-minus-unit">0</td>
                    <td class="text-end" id="total-minus-value">Rp 0</td>
                    <td class="text-end" id="total-plus-unit">0</td>
                    <td class="text-end" id="total-plus-value">Rp 0</td>
                </tr>
                <tr class="fw-bold">
                    <td colspan="9" class="text-end">TOTAL PLUS - MINUS</td>
                    <td class="text-end" id="total-plus-minus">Rp 0</td>
                </tr>
            </tfoot>
        </table>

        <button type="submit" class="btn btn-primary mt-3">Adjust Semua</button>
    </form>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Apakah Anda yakin ingin melakukan penyesuaian stock?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="confirmSubmitBtn">Ya, Lanjutkan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Download Excel -->
<div class="modal fade" id="downloadModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Download Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Apakah Anda ingin mendownload hasil stock opname ke Excel?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
        <button type="button" class="btn btn-success" id="downloadExcelBtn">Ya, Download</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('stock-table');
    const form = document.getElementById('stock-form');
    const alertSuccess = document.getElementById('alert-success');
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const downloadModal = new bootstrap.Modal(document.getElementById('downloadModal'));

    const totalMinusQtyEl = document.getElementById('total-minus-qty');
    const totalMinusUnitEl = document.getElementById('total-minus-unit');
    const totalMinusValueEl = document.getElementById('total-minus-value');
    const totalPlusUnitEl = document.getElementById('total-plus-unit');
    const totalPlusValueEl = document.getElementById('total-plus-value');
    const totalPlusMinusEl = document.getElementById('total-plus-minus');

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
            const plusQty = Math.max(0, physicalQty - systemStock);
            tr.querySelectorAll('.minus-qty').forEach(td => td.textContent = minusQty.toLocaleString('id-ID'));
            tr.querySelector('.minus-value').textContent = 'Rp ' + (minusQty * purchasePrice).toLocaleString('id-ID');
            tr.querySelector('.plus-qty').textContent = plusQty.toLocaleString('id-ID');
            tr.querySelector('.plus-value').textContent = 'Rp ' + (plusQty * purchasePrice).toLocaleString('id-ID');
            updateTotals();
        });
    });

    updateTotals();

    form.addEventListener('submit', (e) => {
        e.preventDefault(); modal.show();
    });

    document.getElementById('confirmSubmitBtn').addEventListener('click', () => {
        modal.hide(); submitForm();
    });

    function submitForm() {
        const formData = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: formData
        }).then(async res => {
            if (!res.ok) throw new Error(await res.text());
            return res.json();
        }).then(data => {
            alertSuccess.textContent = data.message;
            alertSuccess.classList.remove('d-none');
            table.querySelectorAll('tbody tr').forEach(tr => {
                const physicalQty = parseInt(tr.querySelector('.physical-qty').value) || 0;
                tr.querySelector('.system-stock').textContent = physicalQty.toLocaleString('id-ID');
            });
            updateTotals();
            downloadModal.show();
        }).catch(err => {
            alert('Gagal menyimpan stok opname.\n' + err.message);
        });
    }

  document.getElementById('downloadExcelBtn').addEventListener('click', () => {
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(table);
    XLSX.utils.book_append_sheet(wb, ws, 'Stock Opname');
    XLSX.writeFile(wb, 'Stock_Opname_' + new Date().toISOString().slice(0,10) + '.xlsx');

    setTimeout(() => {
        location.reload();
    }, 1000);
});

document.querySelector('#downloadModal .btn-secondary').addEventListener('click', () => {
    location.reload();
});


    // SEARCH LOGIC FIXED
    const searchInput = document.getElementById('search-product');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(tr => {
                const productName = tr.querySelector('.product-name').textContent.toLowerCase();
                tr.style.display = productName.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>
@endsection
