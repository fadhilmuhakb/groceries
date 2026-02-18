@extends('layouts.app')

@section('content')
<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Penjualan</div>
        <div class="ms-auto">
            <div class="btn-group">
                <a href="{{ route('sell.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>

    <h6 class="mb-0 text-uppercase">Detail Penjualan (Editable)</h6>
    <hr />

    <form action="{{ route('sell.update', $sell->id) }}" method="POST">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-6">
                    <strong>No. Invoice:</strong> <span class="text-muted">{{ $sell->no_invoice }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Toko:</strong> <span class="text-muted">{{ $sell->store->store_name ?? '-' }}</span>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">
                    <strong>Total Harga Saat Ini:</strong>
                    <span id="current-total" class="text-success fw-bold" data-total="{{ (float) $sell->total_price }}">
                        Rp{{ number_format($sell->total_price, 0, ',', '.') }}
                    </span>
                </div>
                <div class="col-md-6">
                    <label for="payment-amount" class="form-label fw-bold mb-1">Pembayaran</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="number" class="form-control"
                            id="payment-amount" name="payment_amount"
                            value="{{ old('payment_amount', $sell->payment_amount) }}"
                            min="0" step="0.01">
                        @php
                            $autoPay = (float) old('payment_amount', $sell->payment_amount) === (float) $sell->total_price;
                        @endphp
                        <div class="form-check ms-1">
                            <input class="form-check-input" type="checkbox" id="payment-auto" {{ $autoPay ? 'checked' : '' }}>
                            <label class="form-check-label" for="payment-auto">Samakan</label>
                        </div>
                    </div>
                    <div class="form-text">Hilangkan centang untuk ubah manual.</div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4">
                    <label for="date" class="form-label">Tanggal Transaksi</label>
                    <input type="date" class="form-control" id="date" name="date"
                        value="{{ old('date', $sell->date) }}" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
                <div class="alert alert-warning py-2">
                    Jika semua item dihapus, invoice akan terhapus otomatis.
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th>Jumlah</th>
                                <th>Diskon</th>
                                <th>Catatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="item-body">
                            @forelse($outgoingGoods as $item)
                            <tr data-product-id="{{ $item->product_id }}" data-discount="{{ (float) ($item->discount ?? 0) }}">
                                <td>{{ $item->product->product_name ?? 'Produk tidak ditemukan' }}</td>
                                <td style="max-width: 140px;">
                                    <input type="number" class="form-control form-control-sm text-end"
                                        name="items_existing[{{ $item->id }}][qty]"
                                        value="{{ old('items_existing.' . $item->id . '.qty', $item->quantity_out) }}"
                                        min="1" required>
                                </td>
                                <td class="text-end">{{ number_format((float) ($item->discount ?? 0), 0, ',', '.') }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger btn-remove-row">Hapus</button>
                                </td>
                            </tr>
                            @empty
                            <tr data-empty-row="1">
                                <td colspan="5" class="text-center text-muted">Tidak ada barang keluar yang tercatat</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="mb-3">Tambah Item</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">Produk</label>
                                <select id="new-product" class="form-select">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->product_code ? $product->product_code.' - ' : '' }}{{ $product->product_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Qty</label>
                                <input type="number" id="new-qty" class="form-control" value="1" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Catatan</label>
                                <input type="text" id="new-desc" class="form-control" placeholder="Opsional">
                            </div>
                            <div class="col-md-1 d-grid">
                                <button type="button" id="btn-add-item" class="btn btn-success">Tambah</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const priceData = @json($priceData);
        const currencyFormat = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        });

        let newIndex = 0;
        const itemBody = document.getElementById('item-body');
        const btnAdd = document.getElementById('btn-add-item');
        const productSelect = document.getElementById('new-product');
        const qtyInput = document.getElementById('new-qty');
        const descInput = document.getElementById('new-desc');
        const totalEl = document.getElementById('current-total');
        const paymentInput = document.getElementById('payment-amount');
        const paymentAuto = document.getElementById('payment-auto');

        const toNumber = (val) => {
            const num = Number(val);
            return Number.isFinite(num) ? num : 0;
        };

        const formatRupiah = (value) => currencyFormat.format(Math.max(0, toNumber(value)));

        const resolveUnitPrice = (productId, qty) => {
            const info = priceData[String(productId)] || priceData[Number(productId)];
            if (!info) return 0;
            let unit = toNumber(info.base) - toNumber(info.discount);
            const tiers = info.tiers || {};
            const thresholds = Object.keys(tiers).map((t) => Number(t)).filter((t) => !Number.isNaN(t)).sort((a, b) => a - b);
            thresholds.forEach((t) => {
                if (qty >= t) {
                    unit = toNumber(tiers[String(t)] ?? tiers[t]);
                }
            });
            return unit;
        };

        const syncPaymentMode = () => {
            if (!paymentInput || !paymentAuto) return;
            if (paymentAuto.checked) {
                paymentInput.readOnly = true;
                recalcTotal();
            } else {
                paymentInput.readOnly = false;
            }
        };

        const recalcTotal = () => {
            if (!itemBody || !totalEl) return;
            let total = 0;
            const rows = itemBody.querySelectorAll('tr');
            rows.forEach((row) => {
                const productId = row.dataset.productId || row.querySelector('input[name*="[product_id]"]')?.value;
                const qtyField = row.querySelector('input[name*="[qty]"]');
                if (!productId || !qtyField) return;
                const qty = parseInt(qtyField.value || '0', 10);
                if (!qty) return;
                const unitPrice = resolveUnitPrice(productId, qty);
                const discount = toNumber(row.dataset.discount);
                total += (unitPrice * qty) - discount;
            });

            totalEl.dataset.total = String(total);
            totalEl.textContent = formatRupiah(total);
            if (paymentAuto?.checked && paymentInput) {
                paymentInput.value = Number.isInteger(total) ? total : total.toFixed(2);
            }
        };

        const resetNewItem = () => {
            if (!productSelect || !qtyInput || !descInput) return;
            productSelect.value = '';
            qtyInput.value = 1;
            descInput.value = '';
        };

        btnAdd?.addEventListener('click', () => {
            const productId = productSelect.value;
            const qty = parseInt(qtyInput.value || '1', 10);
            if (!productId) {
                alert('Pilih produk terlebih dahulu.');
                return;
            }
            if (Number.isNaN(qty) || qty < 1) {
                alert('Qty minimal 1.');
                return;
            }

            const productText = productSelect.options[productSelect.selectedIndex].text;
            const desc = descInput.value || '';

            const emptyRow = itemBody.querySelector('[data-empty-row="1"]');
            if (emptyRow) {
                emptyRow.remove();
            }

            const row = document.createElement('tr');
            row.dataset.productId = productId;
            row.dataset.discount = '0';
            row.innerHTML = `
                <td>
                    ${productText}
                    <input type="hidden" name="items_new[${newIndex}][product_id]" value="${productId}">
                </td>
                <td style="max-width: 140px;">
                    <input type="number" class="form-control form-control-sm text-end"
                        name="items_new[${newIndex}][qty]" value="${qty}" min="1" required>
                </td>
                <td class="text-end">0</td>
                <td>
                    <input type="text" class="form-control form-control-sm"
                        name="items_new[${newIndex}][description]" value="${desc}">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-row">Hapus</button>
                </td>
            `;
            itemBody.appendChild(row);
            newIndex += 1;
            resetNewItem();
            recalcTotal();
        });

        itemBody?.addEventListener('click', (event) => {
            const btn = event.target.closest('.btn-remove-row');
            if (!btn) return;
            const row = btn.closest('tr');
            if (row) row.remove();
            recalcTotal();
        });

        itemBody?.addEventListener('input', (event) => {
            if (event.target.matches('input[type="number"]')) {
                recalcTotal();
            }
        });

        paymentAuto?.addEventListener('change', syncPaymentMode);

        syncPaymentMode();
        recalcTotal();
    });
</script>
@endsection
