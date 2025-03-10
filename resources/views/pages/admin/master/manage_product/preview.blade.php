@extends('layouts.app')

@section('content')
    <h4 class="mb-3">Preview Import Produk</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Produk</th>
                <th>Jenis</th>
                <th>Merek</th>
                <th>Satuan</th>
                <th>Harga Pokok</th>
                <th>Harga Jual</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($importedProducts as $product)
                <tr>
                    <td>{{ $product['product_code'] }}</td>
                    <td>{{ $product['product_name'] }}</td>
                    <td>
                        {{ \App\Models\tb_types::find($product['type_id'])->type_name ?? '-' }}
                    </td>
                    <td>
                        {{ \App\Models\tb_brands::find($product['brand_id'])->brand_name ?? '-' }}
                    </td>
                    <td>
                        {{ \App\Models\tb_units::find($product['unit_id'])->unit_name ?? '-' }}
                    </td>
                    <td>{{ number_format((float) $product['purchase_price'], 0, ',', '.') }}</td>
                    <td>{{ number_format((float) $product['selling_price'], 0, ',', '.') }}</td>
                    <td>{{ $product['description'] }}</td>


                </tr>
            @endforeach
        </tbody>
    </table>

    <form action="{{ route('master-product.saveImported') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-success">Simpan</button>
    </form>
@endsection