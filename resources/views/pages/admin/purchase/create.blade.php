@extends('layouts.app')

@section('css')
    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 38px;
            padding: 6px;
        }

        .select2-container .select2-selection__rendered {
            line-height: 24px;
        }

        .select2-container .select2-selection__arrow {
            height: 36px;
        }

        td {
            min-width: 200px;
        }
    </style>
@endsection

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pembelian</div>
        <div class="ms-auto">
            <div class="btn-group">
                <a href="{{ route('purchase.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>

    <h6 class="mb-0 text-uppercase">Tambah Pembelian</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('purchase.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="supplier_id" class="form-label">Pilih Supplier</label>
                        <select class="form-control select2" name="supplier_id" id="supplier_id" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="store_id" class="form-label">Toko (Store ID)</label>
                        <select class="form-control select2" name="store_id" id="store_id" required>
                            <option value="">-- Pilih Toko --</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="total_price" class="form-label">Total Harga</label>
                        <input type="number" id="total_price_display" class="form-control" value="0" readonly>
                        <input type="hidden" name="total_price" id="total_price" value="0">
                    </div>

                </div>

                <h5 class="mt-4">Daftar Produk</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="products-table">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th>Stock</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="product-list">
                            <tr>
                                <td>
                                    <select name="products[0][product_id]" class="form-control select2 product-select"
                                        required>
                                        <option value="">Pilih Produk</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}">
                                                {{ $product->product_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="products[0][stock]" class="form-control stock-input" min="1"
                                        value="1" required></td>
                                <td><input type="text" name="products[0][description]" class="form-control"></td>
                                <td>
                                    <button type="button" class="btn btn-danger remove-product">-</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" id="add-product" class="btn btn-primary mt-3">+ Tambah Produk</button>

                <button type="submit" class="btn btn-success mt-3">Simpan</button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script>
  $(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    let productIndex = 1;

    $('#add-product').click(function () {
        let row = `
        <tr>
            <td>
                <select name="products[${productIndex}][product_id]" class="form-control select2 product-select" required>
                    <option value="">Pilih Produk</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}">
                            {{ $product->product_name }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="products[${productIndex}][stock]" class="form-control stock-input" min="1" value="1" required></td>
            <td><input type="text" name="products[${productIndex}][description]" class="form-control"></td>
            <td>
                <button type="button" class="btn btn-danger remove-product">-</button>
            </td>
        </tr>
        `;
        $('#product-list').append(row);
        $('#product-list tr:last-child .select2').select2({ width: '100%' });
        productIndex++;
    });

    $(document).on('change', '.product-select, .stock-input', function () {
        updateTotalPrice();
    });

    $(document).on('click', '.remove-product', function () {
        $(this).closest('tr').remove();
        updateTotalPrice();
    });

    function updateTotalPrice() {
        let totalPrice = 0;
        $('#product-list tr').each(function () {
            let selectedProduct = $(this).find('.product-select option:selected');
            let price = parseFloat(selectedProduct.attr('data-price')) || 0;
            let qty = parseInt($(this).find('.stock-input').val()) || 0;

            totalPrice += price * qty;
        });
        $('#total_price_display').val(totalPrice); // Update tampilan
        $('#total_price').val(totalPrice); // Update hidden input yang dikirim ke backend
    }

    $('form').submit(function (e) {
        updateTotalPrice(); // Pastikan total price diperbarui sebelum submit
    });
});


    </script>
@endsection