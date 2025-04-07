@extends('layouts.app')
@section('css')
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" />
@endsection
@section('content')
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Sales</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('master-unit.index') }}"><i
                                class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Index</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
        </div>
    </div>
    <h6 class="mb-0 text-uppercase">Sales</h6>
    <hr />
    <div class="row mb-3">
        <div class="col-8">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div style="font-size: 18px" class="fw-bold">No. Invoice</div>
                            <p style="font-size: 14px">{{$invoice_number}} <input type="hidden" id="invoice-number" value="{{$invoice_number}}"></p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="">Tanggal</label>
                            <input type="date" id="transaction-date" class="form-control" onchange="getFormData()">
                        </div>
                    </div>
                    <hr>

                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $user->store->store_name ?? '-' }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th width="28%">Produk</th>
                                    <th width="8%">Stock</th>
                                    <th width="15%">Harga</th>
                                    <th width="9%">Qty</th>
                                    <th width="15%">Diskon</th>
                                    <th>Deskripsi</th>
                                    <th width="8%"></th>
                                </tr>
                            </thead>
                            <tbody id="product-list">
                                <tr>
                                    <td>
                                        <input type="hidden" name="products[0][product_code]">
                                        <select type="text" name="products[0][product_id]"
                                            class="form-select product-select2"></select>
                                    </td>
                                    <td>
                                        <input type="text" name="products[0][current_stock]" class="form-control" disabled>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][selling_price]" class="form-control"
                                            disabled>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][qty]" oninput="qtyChange(0, this.value)"
                                            class="form-control">
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][discount]"
                                            oninput="discountChange(0, this.value)" class="form-control">
                                    </td>
                                    <td>
                                        <input type="text" name="products[0][description]" class="form-control">
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)" style="font-size: 24px;" onClick="handlePlus(0)"><i
                                                class="lni lni-circle-plus"></i></a>
                                        <a href="javascript:void(0)" style="font-size: 24px; color:red"
                                            onClick="handleMinus(0)"><i class="lni lni-circle-minus"></i></a>
                                    </td>
                                </tr>
                            </tbody>
                            <tfooter>
                                <tr>
                                    <td colspan="7">
                                        <a href="javascript:void(0)" onClick="handleAdd('')">+ Tambah Produk</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold" style="font-size: 14px">SUB TOTAL</td>
                                    <td class="text-end">
                                        <div id="total-discount" class="fw-bold" style="font-size: 14px"></div>
                                    </td>
                                    <td class="text-end">
                                        <div id="sub-total-price" class="fw-bold" style="font-size: 14px"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold" style="font-size: 14px">TOTAL</td>
                                    <td colspan="2" class="text-end">
                                        <div id="total-price" class="fw-bold" style="font-size: 14px"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold" style="vertical-align:middle;">JUMLAH UANG
                                        DIBAYAR</td>
                                    <td colspan="2"><input type="number" oninput="customerMoney(this.value)"
                                            id="customer-money" name="customer_money" style="text-align: right"
                                            class="form-control" placeholder="Masukan uang yang diterima"></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold" style="vertical-align:middle;">UANG
                                        KEMBALI</td>
                                    <td colspan="2" class="text-end">
                                        <div id="money-back" class="fw-bold" style="font-size: 14px"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td colspan="7" style="text-align: right"><button class="btn btn-primary"
                                            style="width: 200px" id="btn-processes">Proses Pembayaran</button> </td>
                                </tr>
                            </tfooter>
                        </table>
                        <div class="row">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>

    <script>
        const btnProcesses = $('#btn-processes');
        $(document).ready(function() {
            getFormData();
            select2('', 0);
            btnProcesses.on('click', function() {
                btnProcesses.attr('disabled', 'disabled');
                Swal.fire({
                    title: 'Pilih langkah selanjutnya',
                    html: `
                        <div class="swal3-buttons">
                            <button class="swal-button swal-button--print btn btn-success">Simpan, lalu print</button>
                            <button class="swal-button swal-button--noprint btn btn-primary">Simpan, tanpa print</button>
                            <button class="swal-button swal-button--cancel btn btn-danger">Batal</button>
                        </div>
                    `,
                    text: '',
                    icon: 'warning',
                    showConfirmButton: false, // Nonaktifkan tombol default
                    showCancelButton: false,  // Nonaktifkan tombol default
                    allowOutsideClick: false,
                    didOpen: () => {
                        $('.swal-button--cancel').on('click', () => {
                            Swal.close();
                            btnProcesses.removeAttr("disabled");
                        })

                        $('.swal-button--print').on('click', () => {
                            console.log('with print');
                            // saveProduct('print');
                        });

                        $('.swal-button--noprint').on('click', () => {
                            saveProduct('noprint');
                        })
                    }
                })
                
            })
        })

        const saveProduct = (type) => {
            let token = $("meta[name='csrf-token']").attr("content");
            

            $.ajax({
                    url: '{{route('sales.store')}}',
                    type: 'POST',
                    data: {
                        _token: token,
                        data: formData
                    },
                    success: function(response) {
                        btnProcesses.removeAttr("disabled");
                        Swal.fire({
                            'icon': 'success',
                            'title': 'Sukses',
                            'text': response.message
                        })
                    },
                    error: function(err) {
                        if(err.responseJSON) {
                            Swal.fire({
                            icon:'error',
                            title: 'error',
                            text: 'Terjadi kesalahan pada pengisian form, harap periksa kembali'
                        });
                        }
                        
                        btnProcesses.removeAttr("disabled");
                    }
                })
        }

        const debounce = (callback, wait) => {
            let timeoutId = null;
            return (...args) => {
                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(() => {
                    callback(...args);
                }, wait);
            };
        }

        const formattedPrice = (price) => {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(price);
        }

        let formData = {
            transaction_date: '',
            no_invoice: $('#invoice-number').val(),
            customer_money: 0,
            total_price:0,
            products: []
        };
        let formCustomerMoney = {};
        let productIndex = 1;

        // For call select2 every single append new row
        const select2 = (barcode, id) => {
            let select2Id = $('#select2')
            const $selectElement = $(`select[name="products[${id}][product_id]"]`);
            $selectElement.select2({
                theme: 'bootstrap-5',
                placeholder: 'Cari produk...',
                ajax: {
                    url: "{{ route('options.incoming_goods') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search_term: barcode ? barcode : params.term
                        }
                    },
                    processResults: function(response) {
                        let filteredData;
                        if (barcode !== '') {
                            const product = response.data[0];

                            if (response.data.length === 1) {
                                setTimeout(() => {

                                    getFormData();
                                }, 100);

                                filteredData = response.data;
                            } else if (response.data.length === 0) {
                                alert('Produk dengan barcode ini tidak ditemukan');
                            }

                        } else {
                            filteredData = response.data.filter(item =>
                                !formData.products.some(data => data.product_id == item.id)
                            );
                        }

                        return {
                            results: filteredData,
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
            })


            $('.product-select2').on('select2:select', function(e) {
                var selectName = $(this).attr('name');
                var index = Number(selectName.match(/\[(\d+)\]/)[1]);
                var selectedValue = e.params.data.id;
                let selling_price = $(`input[name="products[${index}][selling_price]"]`);
                let current_stock = $(`input[name="products[${index}][current_stock]"]`);
                let qty = $(`input[name="products[${index}][qty]"]`);
                let product_code = $(`input[name="products[${index}][product_code]"]`);
                selling_price.val(e.params.data.selling_price);
                current_stock.val(e.params.data.current_stock);
                qty.val(1);
                product_code.val(e.params.data.product_code);

                getFormData();
            });
        }

        // For hide the focus after using barcode
        function hideSelect2Keyboard(e) {
            $('.product-select2, :focus,input').prop('focus', false).blur();
        }

        // Adding new row
        const handleAdd = (barcode) => {
            let checkValueProduct = $(`select[name="products[${productIndex-1}][product_id]"]`).val();

            if (checkValueProduct !== null) {
                $('#product-list').append(
                    `
            <tr>
                <td>
                    <input type="hidden" name="products[${productIndex}][product_code]" class="form-control">
                    <select type="text" name="products[${productIndex}][product_id]" class="form-select product-select2" id="select2"></select>
                </td>
                <td>
                    <input type="number" name="products[${productIndex}][current_stock]" class="form-control" disabled>
                </td>
                <td>
                    <input type="number" name="products[${productIndex}][selling_price]" class="form-control" disabled>
                </td>
                <td>
                    <input type="number" name="products[${productIndex}][qty]" oninput="qtyChange(${productIndex}, this.value)" class="form-control">
                </td>
                <td>
                    <input type="number" name="products[${productIndex}][discount]" oninput="discountChange(${productIndex}, this.value)" class="form-control">
                </td>
                <td>
                    <input type="text" name="products[${productIndex}][description]" class="form-control">
                </td>
                <td>
                    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center">
                        <div>
                            <a href="javascript:void(0)" style="font-size: 24px;" onClick="handlePlus(${productIndex})"><i class="lni lni-circle-plus"></i></a>
                            <a href="javascript:void(0)" style="font-size: 24px; color:red" onClick="handleMinus(${productIndex})"><i class="lni lni-circle-minus"></i></a>
                        </div>
                        <div>
                            <a href="javascript:void(0)" class="delete-item" style="font-size: 20px; color:red"><i class="lni lni-trash"></i></a>
                        </div>
                    </div>
                </td>
            </tr>
            `
                )
            } else {
                productIndex = productIndex - 1;
            }

            if (barcode !== '') {
                select2(barcode, productIndex);
                const product_id = $(`select[name="products[${productIndex}][product_id]"]`)
                product_id.select2('open');
                $('.select2-search__field').val(barcode).trigger('input');

                let $options = $('.select2-results__option');
                if ($options.length > 0) {
                    setTimeout(function() {
                        $('.select2-results__option').first().trigger('mouseup');
                        setTimeout(function() {
                            $("select").select2({
                                theme: "bootstrap-5"
                            }).on("select2-open", hideSelect2Keyboard);

                            product_id.select2('close');
                        }, 200);

                    }, 500);
                }
            } else {
                select2(barcode, productIndex);
            }

            productIndex++;
        }

        // Increase qty row selected
        const handlePlus = (productIndex) => {

            let qtyField = $(`input[name="products[${productIndex}][qty]"]`);
            let stockField = $(`input[name="products[${productIndex}][current_stock]"]`);
            if(parseInt(qtyField.val()) < parseInt(stockField.val())) {
                qtyField.val(parseInt(qtyField.val() || 0) + 1);
            }
            getFormData();
        }

        // Decreas qrt every row selected
        const handleMinus = (productIndex) => {
            let qtyField = $(`input[name="products[${productIndex}][qty]"]`);
            if (qtyField.val() > 1) {
                qtyField.val(parseInt(qtyField.val() || 0) - 1);
            } else {}
            getFormData();
        }

        // Input discount every single row
        const discountChange = debounce((idx, value) => {
            $(`input[name="products[${idx}][discount]"]`).val(value);
            getFormData();
        }, 500)

        // custom input qty every single row
        const qtyChange = debounce((idx, value) => {
            let stockField = $(`input[name="products[${idx}][current_stock]"]`);
            if(value > parseInt(stockField.val())) {
                $(`input[name="products[${idx}][qty]"]`).val(stockField.val());
                
            } else {
                $(`input[name="products[${idx}][qty]"]`).val(value);
            }
            getFormData();
        }, 500);

        // input customer money
        const customerMoney = debounce((value) => {
            $('#customer-money').val(value);

            getFormData();
        }, 500)

        // Delete every single row
        $(document).on('click', '.delete-item', function() {
            $(this).closest('tr').remove();
            getFormData();
        })


        // handle barcode
        let lastKeyTime = 0;
        let barcode = ""
        $(document).keypress(function(e) {
            const currentTime = new Date().getTime();
            if (currentTime - lastKeyTime > 100) {
                barcode = "";
            }

            if (e.key === "Enter") {
                if (barcode.length > 3) {
                    processBarcode(barcode);
                }

                barcode = ""
            } else {
                barcode += e.key;
            }

            lastKeyTime = currentTime;
        })


        // Moving barcode to ui
        const processBarcode = (barcode) => {

            let existingProductIndex = formData.findIndex(item => item.product_code == barcode);
            if (existingProductIndex !== -1) {
                let qtyField = $(`input[name="products[${existingProductIndex}][qty]"]`);
                qtyField.val(parseInt(qtyField.val()) + 1);
            } else {
                handleAdd(barcode);
            }

            getFormData();

        }

        // every single row add to formData
        const getFormData = () => {
            let transaction_date = $('#transaction-date').val();
            let customer_money = $('#customer-money').val();
            formData = {
                'transaction_date': transaction_date,
                'no_invoice': $('#invoice-number').val(),
                'customer_money': customer_money ?? 0,
                'total_price' : 0,
                'products': []
            };
            formCustomerMoney = {};
            let productIndex = 0;
            $('#product-list tr').each((index, row) => {
                let product = {
                    idx: index,
                    product_id: $(row).find('select[name^="products"]').val(),
                    product_code: $(row).find('input[name^="products"][name$="[product_code]"]').val(),
                    qty: $(row).find('input[name^="products"][name$="[qty]"]').val(),
                    discount: $(row).find('input[name^="products"][name$="[discount]"]').val(),
                    description: $(row).find('input[name^="products"][name$="[description]"]').val(),
                    selling_price: $(row).find('input[name^="products"][name$="[selling_price]"]').val(),
                }

                formData.products.push(product);

                productIndex++;
            })
            let totalDiscount = formData.products.reduce((num, item) => num + Number(item.discount), 0);
            let totalSelling = formData.products.reduce((num, item) => num + (Number(item.selling_price) * Number(item
                .qty)), 0);

            formCustomerMoney = {
                customer_money: $('#customer-money').val(),
                customer_money_back: Number($('#customer-money').val()) - (totalSelling - totalDiscount),
            }

            $('#total-discount').text(formattedPrice(totalDiscount));
            $('#sub-total-price').text(formattedPrice(totalSelling));
            $('#total-price').text(formattedPrice(totalSelling - totalDiscount));
            $('#money-back').text(formattedPrice(formCustomerMoney.customer_money_back));

            formData.total_price = totalSelling-totalDiscount;

        }

        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
            });
        @endif
    </script>
@endsection
