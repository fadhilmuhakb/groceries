@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Penjualan</div>
    <div class="ms-auto">
        <div class="btn-group">
            <a href="{{ route('sell.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>

<h6 class="mb-0 text-uppercase">Edit Penjualan</h6>
<hr />

<div class="card mb-3">
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row mb-2">
            <div class="col-md-6">
                <strong>No. Invoice:</strong> <span class="text-muted">{{ $sell->no_invoice }}</span>
            </div>
            <div class="col-md-6">
                <strong>Toko:</strong> <span class="text-muted">{{ $sell->store->store_name ?? '-' }}</span>
            </div>
        </div>

        <form action="{{ route('sell.update', $sell->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="date" class="form-label">Tanggal Transaksi</label>
                    <input type="date" class="form-control" id="date" name="date"
                        value="{{ old('date', $sell->date) }}" required>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Diskon</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($outgoingGoods as $item)
                        <tr>
                            <td>{{ $item->product->product_name ?? 'Produk tidak ditemukan' }}</td>
                            <td style="max-width: 140px;">
                                <input type="number" class="form-control form-control-sm text-end"
                                    name="items[{{ $item->id }}][qty]"
                                    value="{{ old('items.' . $item->id . '.qty', $item->quantity_out) }}"
                                    min="1" required>
                            </td>
                            <td class="text-end">{{ number_format((float) ($item->discount ?? 0), 0, ',', '.') }}</td>
                            <td>{{ $item->description }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Tidak ada barang keluar yang tercatat</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
