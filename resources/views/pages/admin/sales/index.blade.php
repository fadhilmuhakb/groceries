@extends('layouts.app')

@section('css')
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" />

    <style>
        #table-item tbody tr {
            cursor: pointer;
        }

        #table-item tbody tr:hover {
            background-color: #f1f1f1;
        }

        #table-item tbody tr.selected {
            background-color: #d3e0f5;
        }
    </style>
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
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-lg-5 col-md-8">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <label for="transaction_number" style="width:24%">
                                    No. Transaksi:
                                </label>

                                <div>
                                    <input type="text" class="form-control form-control-sm form-transaction" id="invoice-number" value="{{$invoice_number}}">

                                </div>
                
                                <div class="form-control form-control-sm" style="width: 30%">{{Auth::user()->name}}

                                </div>

                            </div>
                            <div class="row mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="date" style="width:24%">Tanggal:</label>
                                    <div style="width: 76%">
                                        <input type="date" class="form-control form-control-sm form-transaction" id="transaction-date" onchange="onChangeDate()">
                                    </div>
                                </div>
                               
                            </div>

                            <div class="row mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="date" style="width:24%">Pelanggan:</label>
                                    <div style="width: 76%">
                                        {{-- <input type="date" class="form-control" id="invoice-number" value=""> --}}
                                        <select class="form-select form-select-sm form-transaction">
                                            <option value="">Pilih Pelanggan</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{$customer->id}}">{{$customer->customer_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                            </div>
                        </div>

                        <div class="col-lg-7 col-md-4">
                            <div style="width: 100%; height:90%; border:1px solid #ced4da; border-radius:10px; text-align:right; display:flex; align-items:center; justify-content:flex-end; padding:0 14px">
                                <p style="font-size: 50px;" class="fw-bold" id="total-price">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="row mb-3">
                                <div class="col-3">
                                    <label for="transaction_number" class="form-label">Jumlah: </label>
                                    <input type="number" class="form-control form-control-sm form-transaction" id="qty" value="1">
                                </div>
                                <div class="col-4">
                                    <label for="transaction_number" class="form-label">Kode Item: </label>
                                    <input type="text" class="form-control form-control-sm form-transaction" name="item_code" id="item-code">
                                </div>
                                <div class="col-4">
                                    <label for="transaction_number" class="form-label">Scan Barcode: </label>
                                    <input type="text" class="form-control form-control-sm form-transaction" name="scan_barcode" id="scan-barcode">
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive mb-4" style="height: 400px;">
                                <table class="table mb-0">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th>No</th>
                                            <th>Kode Item</th>
                                            <th width="30%">Nama Item</th>
                                            <th>Jumlah</th>
                                            <th>Harga</th>
                                            <th>Potongan</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="product-list">
                                        <tr>
                                            <td colspan="7" class="text-center"><i class="bx bx-message-alt-error"></i> Data Kosong</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-2">
                            <button class="btn btn-primary w-100"><i class="bx bx-save"></i>Simpan</button>
                        </div>
                        <div class="col-2">
                            <button class="btn btn-danger w-100" onclick="onCancel(event)"><i class="bx bx-x"></i>Batal</button>
                        </div>
                        <div class="col-2">
                            <button class="btn btn-success w-100" onclick="onClickModalPayment(event)"><i class="bx bx-wallet"></i>Bayar</button>
                        </div>



                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Modal --}}
    <div class="modal fade" id="item-modal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="paymentModalLabel">Barang</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-6">
                    <div class="row mb-3">
                        <label for="search_term" class="col-md-3 col-lg-4 col-form-label">Kata Kunci 1:</label>
                        <div class="col-md-7 col-lg-8">
                            <input type="text" class="form-control form-control-sm" id="search_term" value="" onkeydown="handleKeyDownItemSearch(event)">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="row mb-3">
                        <label for="search_type" class="col-md-3 col-lg-4 col-form-label">Kata Kunci 2:</label>
                        <div class="col-md-7 col-lg-8">
                            <input type="text" class="form-control form-control-sm" id="search_type" value="">
                        </div>
                    </div>
                </div>
                <div class="col-6 col-form-label">
                    <label for="">Jenis / Satuan</label>

                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class=" mb-4">
                        <table class="table mb-0 w-100" id="table-item">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Kode Item</th>
                                    <th>Nama Item</th>
                                    <th>Stock</th>
                                    <th>Satuan</th>
                                    <th>Jenis</th>
                                    <th>Harga Jual</th>
                                    <th>Merek</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


        </div>

        </div>
    </div>
    </div>

    <div class="modal fade" id="payment-modal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="paymentModalLabel">Pembayaran</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
            <div class="d-flex justify-content-between mb-3 align-items-center">
                <div style="width: 20%; font-size:1.8rem" class="fw-bold">Total:</div>
                <div id="total-price2" style="width: 80%; height: 80px; padding: 0 14px; border: 1px solid #ced4da; text-align: right; font-size: 3rem; display: flex; align-items: center; justify-content: flex-end;">
                    0
                </div>
            </div>
            <div class="d-flex mb-5 align-items-center">
                <div style="width: 20%">Tunai:</div>
                <div style="width: 40%">
                    <input type="text" class="form-control form-control-sm" style="text-align: right" id="customer-money" value="" oninput="customerMoney(this.value)">
                </div>
            </div>

            <div class="d-flex mb-2 align-items-center">
                <div style="width: 20%">Total Bayar:</div>
                <div id="total-payment" style="width: 60%; height: 40px; padding: 0 14px; border: 1px solid #ced4da; text-align: right; font-size: 2rem; display: flex; align-items: center; justify-content: flex-end;">
                    0
                </div>
            </div>
            <div class="d-flex mb-2 align-items-center">
                <div style="width: 20%">Kekurangan:</div>
                <div id="lack" style="width: 60%; height: 40px; padding: 0 14px; border: 1px solid #ced4da; text-align: right; font-size: 2rem; display: flex; align-items: center; justify-content: flex-end;">
                    0
                </div>
            </div>


        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary">Bayar + Print</button>
            <button type="button" class="btn btn-primary" onclick="onPayment()">Bayar </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
        let search_term = '';
        let selectedRowData = [];
        let formData = {};
        let isItemModalOpen = false;
        let selectedRow = 0;


        function highlightRow(index) {
            const rows = $('#table-item tbody tr');
            rows.removeClass('selected');
            if (rows.eq(index).length) {
                rows.eq(index).addClass('selected');
            }
        }


        $(document).on('keydown', function(e) {
            const formTransaction = $('.form-transaction');
            const currentFormTransaction = formTransaction.filter(':focus');
            const index = formTransaction.index(currentFormTransaction);


            if(isItemModalOpen) {

                const rows = $('#table-item tbody tr');
                if(!rows.length) return;

                if(e.key === 'PageDown') {
                    e.preventDefault();
                    $('#search_term').blur();

                    selectedRow = (selectedRow  + 1) % rows.length;
                    highlightRow(selectedRow);
                } else if(e.key ==='PageUp') {
                    e.preventDefault();
                    $('#search_term').blur();

                    selectedRow = (selectedRow - 1 + rows.length) % rows.length;
                    highlightRow(selectedRow);
                } else if(e.key === 'Enter') {
                    e.preventDefault();
                    rows.eq(selectedRow).trigger('click');

                }

            } else {
                if(e.key === 'PageDown') {
                e.preventDefault();
                const next = formTransaction.eq(index + 1);
                if(next.length) next.focus();
            }

                if(e.key === 'PageUp') {
                    e.preventDefault();
                    const prev = formTransaction.eq(index - 1);
                    if(prev.length) prev.focus();
                }
            }

        })

        const debounce = (callback, wait) => {
            let timeoutId = null;
            return (...args) => {
                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(() => {
                    callback(...args);
                }, wait);
            };
        }

        $('#item-modal').on('hidden.bs.modal', function (e) {
            $('#table-item').DataTable().destroy();
            $('#qty').val(1);
            isItemModalOpen = false;
        })

        $('#item-modal').on('shown.bs.modal', function (e) {
            isItemModalOpen = true;
            selectedRow = 0;
            highlightRow(selectedRow);
            $('#search_term').focus();
            $(document).on('keydown', function(e) {
                if(e.key === 'Escape') {
                    $('#item-modal').modal('hide');
                }
            })
        })

        $('#item-code').keydown(function(event) {
            if(event.key === "Enter") {
                search_term = $(this).val();
                event.preventDefault();
                if($('#qty').val() < 0 || $('#qty').val() == '') {
                    $('#qty').focus()
                    return;
                } else {
                    datatableItem();
                    var myModal = new bootstrap.Modal(document.getElementById('item-modal'));
                    isItemModalOpen = true;
                    myModal.show();
                }

            }
        });
        

        $('#scan-barcode').keydown(function(event) {

             // handle barcode
            let lastKeyTime = 0;
            let barcode = ""

            if($('#qty').val() < 0 || $('#qty').val() == '') {
                $('#qty').focus()
                return;
                
            } else {
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
            }
        });

        const processBarcode = debounce((barcode) => {
            let scanBarcodeVal = $('#scan-barcode').val();
            console.log(scanBarcodeVal);
            $.ajax({
                url:`{{ route('options.incoming_goods') }}`,
                method:'GET',
                data: {'search_term': scanBarcodeVal, 'type': 'barcode'},
                success: function(response) {
                    let qty = $('#qty').val();
                    let data = response.data[0];
                    console.log(data.current_stock);
                    if(data.current_stock < qty) {
                        alert('Stok Tidak Cukup');
                        return;
                    }

                    data = {...data, qty: qty, discount: 0, total:0};
                    let findIndex = selectedRowData.findIndex(item => data.id == item.id);
                    if(findIndex !== -1) {
                        selectedRowData[findIndex].qty = parseInt(selectedRowData[findIndex].qty) + 1;
                    } else {
                        selectedRowData.push(data);
                    }

                    $('#qty').val(1);
                    handleData();
                    $('#scan-barcode').val(null);
                }, error: function(err) {
                    console.log(err)
                }
            })
        }, 500)

        // Modal payment open
        const onClickModalPayment = () => {
            let elTotalPrice2 = $('#total-price2');
            elTotalPrice2.html(formatRupiah(formData.total_price ?? 0));
            var myModal = new bootstrap.Modal(document.getElementById('payment-modal'));
            myModal.show();
        }
        

        const customerMoney = (value) => {
            formData.customer_money = value;
            let elTotalPayment = $('#total-payment');
            elTotalPayment.html(formatRupiah(value));
            let elLack = $('#lack');
            elLack.html(formatRupiah(value - formData.total_price));
            formData.customer_money = value;
        }

        handleKeyDownItemSearch = (event) => {
            if(event.key === "Enter") {
                event.preventDefault();
                search_term = $('#search_term').val();
                $('#table-item').DataTable().destroy();
                datatableItem();

            }
        }


        const datatableItem = () => {
            $('#table-item').DataTable({
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        "url": "{{ route('options.incoming_goods') }}",
                        "data": function(d) {
                            d.search_term = search_term;
                            d.sear_type = $('#search_type').val();
                            d.type = 'table';
                        }
                    },
                    "columnDefs": [{ visible: false, targets: 0 }, {targets: 6, className: 'dt-right'}],
                    "columns": [
                        { "data": "id"},
                        { "data": "product_code" },
                        { "data": "product_name" },
                        { "data": "current_stock" },
                        { "data": "unit_name" },
                        { "data": "type_name" },
                        { "data": "selling_price",
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
                        { "data": "brand_name" }
                    ],
                    "scrollY": "300px",
                    "scrollCollapse": true,
                    "searching": false,
                    "info": false,
                    "ordering": false,
                    "dom": 'rtip'
                });
        };

        $('#table-item').on('click', 'tbody tr', function() {
            let data = $('#table-item').DataTable().row(this).data();
            let qty = $('#qty').val();
            if(data.current_stock < qty) {
                alert('Stok Tidak Cukup');
                return;
            }
            data = {...data, qty: $('#qty').val(), discount: 0, total:0};
            let findIndex = selectedRowData.findIndex(item => data.id == item.id);
            if(findIndex !== -1) {
                selectedRowData[findIndex].qty = parseInt(selectedRowData[findIndex].qty) + parseInt(qty);
            } else {
                selectedRowData.push(data);
            }

            var itemModal = bootstrap.Modal.getInstance(document.getElementById('item-modal'));
            $('#qty').val(1);
            itemModal.hide();
            handleData();
        });

        const onQtyChange = debounce((index, value)=> {
            selectedRowData[index].qty = value;
            handleData();
        }, 500);

        const onDiscountChange = debounce((index, value)=> {
            selectedRowData[index].discount = value;
            handleData();
        }, 500);

        const onChangeDate = () => {
            formData.transaction_date = $('#transaction-date').val();
            handleData();
        }

        const onCancel = (e) => {
            e.preventDefault();
            selectedRowData = [];
            formData = {'transaction_date': '', 'no_invoice': $('#invoice-number').val(), 'customer_money': 0, 'total_price':0, 'products': []};
            handleData();
        }

        const handleData = () => {
            $('#product-list').empty();

            let productList = $('#product-list');
            if(selectedRowData.length > 0) {
                selectedRowData.forEach((item, index) => {
                    item.total = ((item.selling_price * item.qty) - item.discount);
                    productList.append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.product_code}</td>
                            <td>${item.product_name}</td>
                            <td><input type="number" name="products[${index}][qty]" style="width:50px ;text-align:right; border:1px solid #ced4da" value="${item.qty}" oninput="onQtyChange(${index}, this.value)"></td>
                            <td style="text-align:right">${formatRupiah(item.selling_price)}</td>
                            <td><input type="number" name="products[${index}][discount]" style="text-align:right; border:1px solid #ced4da" value="${item.discount}" oninput="onDiscountChange(${index}, this.value)"></td>
                            <td style="text-align:right">${formatRupiah(item.total)}</td>
                        </tr>
                    `);
                });

                formData = {
                    'transaction_date': $('#transaction-date').val(),
                    'no_invoice': $('#invoice-number').val(),
                    'customer_money': 0,
                    'total_price': selectedRowData.reduce((acc, item) => acc + ((item.selling_price * item.qty) - item.discount), 0),
                    'products': selectedRowData
                }

                const elTotalPrice = $('#total-price');
                elTotalPrice.html(formatRupiah(formData.total_price));

            } else {
                productList.append(`
                    <tr>
                        <td colspan="7" class="text-center"><i class="bx bx-message-alt-error"></i> Data Kosong</td>
                    </tr>
                `);
            }
        };

        // API
        const onPayment = () => {
            // console.log(formData);
            let token = $("meta[name='csrf-token']").attr("content");

            console.log(formData)
            $.ajax({
                    url: '{{route('sales.store')}}',
                    type: 'POST',
                    data: {
                        _token: token,
                        data: formData
                    },
                    success: function(response) {
                        // btnProcesses.removeAttr("disabled");
                        Swal.fire({
                            'icon': 'success',
                            'title': 'Sukses',
                            'text': response.message
                        }).then(() => {
                            // Buat HTML struk di dalam div tersembunyi
                            let receiptHtml = `
                                <div id="print-area" style="display: none;">
                                    <div style="width: 58mm; font-family: monospace; font-size: 12px;">
                                        <div style="text-align: center;">
                                            <strong>TOKO MAJU JAYA</strong><br>
                                            Jl. Contoh No.123<br>
                                            Telp: 0812-3456-7890 <br>
                                            No Invoice: ${formData.no_invoice}
                                        </div>
                                        <hr>
                                        <div>
                                            Tanggal: ${formData.transaction_date}<br>
                                            Kasir: 
                                        </div>
                                        <hr>
                                        ${formData.products.map(item => `
                                        <div>${item.product_name} (${item.qty} x ${formatRupiah(item.selling_price)})</div>
                                        <div style="text-align:right;">${formatRupiah(item.total)}</div>
                                    `).join('')}
                                        <hr>
                                        <div>Total: ${formatRupiah(formData.total_price)}</div>
                                        <div>Bayar: ${formatRupiah(formData.customer_money)}</div>
                                        <div>Kembali: ${formatRupiah(parseInt(formData.customer_money) - parseInt(formData.total_price))}</div>
                                        <hr>
                                        <div style="text-align: center;">Terima kasih!</div>
                                    </div>
                                </div>
                            `;

                            $('body').append(receiptHtml);

                            // Cetak hanya area tersebut
                            let printContents = document.getElementById('print-area').innerHTML;
                            let originalContents = document.body.innerHTML;
                            document.body.innerHTML = printContents;
                            window.print();
                            document.body.innerHTML = originalContents;
                            location.reload(); // refresh agar tampilan balik lagi

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

                        // btnProcesses.removeAttr("disabled");
                    }
                })
        }

        const onPaymentPrint = () => {
            console.log(formData);
        }


        // Helper
        const formatRupiah = (number) => {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(number);
        };
    </script>
@endsection

{{-- @extends('layouts.app')
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

            let existingProductIndex = formData.products.findIndex(item => item.product_code == barcode);
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
@endsection --}}
