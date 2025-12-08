@extends('layouts.app')

@section('css')
    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <style>
        .select2-container { width: 100% !important; }
    </style>
@endsection

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Edit Pembelian</div>
        <div class="ms-auto">
            <div class="btn-group">
                <a href="{{ route('purchase.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>

    <h6 class="mb-0 text-uppercase">Edit Pembelian</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            <form action="{{ route('purchase.update', $purchase->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pilih Supplier</label>
                        <select class="form-control select2" name="supplier_id" required>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" 
                                    {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Toko</label>
                        <select class="form-control select2" name="store_id" required>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" 
                                    {{ old('store_id', $purchase->store_id) == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Total Harga</label>
                        <input type="number" name="total_price" id="total_price" class="form-control"
                               value="{{ old('total_price', $purchase->total_price) }}" readonly>
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
                            @foreach($purchase->incomingGoods as $key => $item)
                                <tr>
                                    <td>
                                        <select name="products[{{ $key }}][product_id]" class="form-control select2 product-select" required>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}" data-code="{{ $product->product_code }}"
                                                    {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                    [{{ $product->product_code }}] {{ $product->product_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="products[{{ $key }}][stock]" class="form-control stock-input" 
                                               min="1" value="{{ $item->stock }}" required></td>
                                    <td><input type="text" name="products[{{ $key }}][description]" 
                                               class="form-control" value="{{ $item->description }}"></td>
                                    <td>
                                        <button type="button" class="btn btn-danger remove-product">-</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" id="add-product" class="btn btn-primary mt-3">+ Tambah Produk</button>
                <button type="submit" class="btn btn-success mt-3">Update</button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            const productMatcher = function (params, data) {
                if ($.trim(params.term) === '') {
                    return data;
                }
                const term = params.term.toLowerCase();
                const text = (data.text || '').toLowerCase();
                const code = ($(data.element).data('code') || '').toString().toLowerCase();
                if (text.includes(term) || code.includes(term)) {
                    return data;
                }
                return null;
            };

            $('.select2').select2({ width: '100%', matcher: productMatcher });

            let productIndex = $('#product-list tr').length; // Hitung jumlah row

            $('#add-product').click(function () {
                let row = `
                <tr>
                    <td>
                        <select name="products[${productIndex}][product_id]" class="form-control select2 product-select" required>
                            <option value="">Pilih Produk</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}" data-code="{{ $product->product_code }}">
                                    [{{ $product->product_code }}] {{ $product->product_name }}
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
                $('#product-list tr:last-child .select2').select2({ width: '100%', matcher: productMatcher });
                productIndex++;
                updateTotalPrice();
            });

            $(document).on('click', '.remove-product', function () {
                $(this).closest('tr').remove();
                updateTotalPrice();
            });

            $(document).on('change', '.product-select, .stock-input', function () {
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

                $('#total_price').val(totalPrice);
            }

            $('form').submit(function () {
                updateTotalPrice();
            });
        });
    </script>
@endsection
