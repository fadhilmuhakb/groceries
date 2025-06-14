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

    <div id="alert-success" class="alert alert-success d-none"></div>

    <form id="stock-form" action="{{ route('inventory.adjustStockBulk') }}" method="POST">
        @csrf

        <table class="table table-bordered table-striped" id="stock-table">
            <thead>
                <tr>
                    <th style="width:40px;">No.</th>
                    <th>Nama Produk</th>
                    <th>Nama Toko</th>
                    <th style="width:120px;" class="text-end">Jumlah Sistem</th>
                    <th style="width:140px;">Jumlah Fisik</th>
                    <th style="width:120px;" class="text-end">Jumlah Minus</th>
                    <th style="width:130px;" class="text-end">Minus Barang (unit)</th>
                    <th style="width:140px;" class="text-end">Total Minus (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($query as $index => $row)
                <tr data-price="{{ $row->purchase_price }}">
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->store_name }}</td>
                    <td class="text-end system-stock">{{ number_format($row->system_stock) }}</td>
                    <td>
                        <input type="hidden" name="product_id[]" value="{{ $row->product_id }}">
                        <input type="hidden" name="store_id[]" value="{{ $row->store_id }}">
                        <input type="number" name="physical_quantity[]" value="{{ $row->system_stock }}" min="0" class="form-control physical-qty" required>
                    </td>
                    <td class="text-end minus-qty">{{ number_format(max(0, $row->system_stock - $row->system_stock)) }}</td>
                    <td class="text-end minus-qty">{{ number_format(max(0, $row->system_stock - $row->system_stock)) }}</td>
                    <td class="text-end minus-value">{{ 'Rp ' . number_format(max(0, ($row->system_stock - $row->system_stock) * $row->purchase_price)) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="5" class="text-end">TOTAL</td>
                    <td class="text-end" id="total-minus-qty">0</td>
                    <td class="text-end" id="total-minus-unit">0</td>
                    <td class="text-end" id="total-minus-value">Rp 0</td>
                </tr>
            </tfoot>
        </table>

        <button type="submit" class="btn btn-primary mt-3">Adjust Semua</button>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('stock-table');
    const form = document.getElementById('stock-form');
    const alertSuccess = document.getElementById('alert-success');

    if (!table || !form) return;

    const totalMinusQtyEl = document.getElementById('total-minus-qty');
    const totalMinusUnitEl = document.getElementById('total-minus-unit');
    const totalMinusValueEl = document.getElementById('total-minus-value');

    function updateTotals() {
        let totalMinusQty = 0;
        let totalMinusUnit = 0;
        let totalMinusValue = 0;

        table.querySelectorAll('tbody tr').forEach(tr => {
            const minusQty = parseInt(tr.querySelectorAll('.minus-qty')[0].textContent.replace(/\./g, '')) || 0;
            const minusUnit = parseInt(tr.querySelectorAll('.minus-qty')[1].textContent.replace(/\./g, '')) || 0;
            const minusValueText = tr.querySelector('.minus-value').textContent.replace(/[^\d]/g, '');
            const minusValue = parseInt(minusValueText) || 0;

            totalMinusQty += minusQty;
            totalMinusUnit += minusUnit;
            totalMinusValue += minusValue;
        });

        totalMinusQtyEl.textContent = totalMinusQty.toLocaleString('id-ID');
        totalMinusUnitEl.textContent = totalMinusUnit.toLocaleString('id-ID');
        totalMinusValueEl.textContent = 'Rp ' + totalMinusValue.toLocaleString('id-ID');
    }

    // Update minus dan total saat input fisik berubah
    table.querySelectorAll('.physical-qty').forEach(input => {
        input.addEventListener('input', (e) => {
            const tr = e.target.closest('tr');
            if (!tr) return;

            const systemStockText = tr.querySelector('.system-stock').textContent.replace(/[^\d]/g, '');
            const systemStock = parseInt(systemStockText) || 0;

            const physicalQty = parseInt(e.target.value) || 0;
            const minusQty = Math.max(0, systemStock - physicalQty);

            const minusQtyCells = tr.querySelectorAll('.minus-qty');
            minusQtyCells.forEach(td => td.textContent = minusQty.toLocaleString('id-ID'));

            const purchasePrice = parseInt(tr.getAttribute('data-price')) || 0;
            const minusValue = minusQty * purchasePrice;
            tr.querySelector('.minus-value').textContent = 'Rp ' + minusValue.toLocaleString('id-ID');

            updateTotals();
        });
    });

    updateTotals();

    // AJAX submit form supaya tidak reload halaman
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alertSuccess.textContent = data.message || 'Stock opname berhasil disimpan.';
            alertSuccess.classList.remove('d-none');

            // Update kolom "Jumlah Sistem" sesuai "Jumlah Fisik" setelah simpan
            table.querySelectorAll('tbody tr').forEach(tr => {
                const physicalQtyInput = tr.querySelector('.physical-qty');
                const physicalQty = parseInt(physicalQtyInput.value) || 0;
                tr.querySelector('.system-stock').textContent = physicalQty.toLocaleString('id-ID');
            });

            updateTotals();
        })
        .catch(err => {
            alert('Gagal menyimpan stok opname.');
            console.error(err);
        });
    });
});
</script>
@endsection
