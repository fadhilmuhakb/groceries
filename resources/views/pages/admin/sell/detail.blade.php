@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="card-title mb-3 text-primary">Detail Penjualan</h4>
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
                    <strong>Tanggal:</strong> <span class="text-muted">{{ $sell->date }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Total Harga:</strong> <span class="text-success fw-bold">Rp{{ number_format($sell->total_price, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">
                    <strong>Pembayaran:</strong> <span class="text-success">Rp{{ number_format($sell->payment_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title text-info mb-3">Barang Keluar</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Diskon</th>
                            <th>Dibuat Oleh</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($outgoingGoods as $item)
                        <tr>
                            <td>{{ $item->product->product_name ?? 'Produk tidak ditemukan' }}</td>
                            <td>{{ $item->date }}</td>
                            <td>{{ $item->quantity_out }}</td>
                            <td>{{ $item->discount ?? 0 }}%</td>
                            <td>{{ $item->recorded_by }}</td>
                            <td>{{ $item->description }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Tidak ada barang keluar yang tercatat</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
