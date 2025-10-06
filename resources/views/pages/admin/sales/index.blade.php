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
        .select2-container {
        width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 31px;
            padding: 2px;
        }

        .select2-container .select2-selection__rendered {
            line-height: 24px;
        }

        .select2-container .select2-selection__arrow {
            height: 36px;
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
                                        <select class="form-select form-select-sm form-transaction" id="customer-id">
                                            <option value="">Pilih Pelanggan</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{$customer->id}}">{{$customer->customer_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>
                            @if(Auth::user()->roles === "superadmin")
                            <div class="row mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="date" style="width:24%">Toko</label>
                                    <div style="width: 76%">
                                        {{-- <input type="date" class="form-control" id="invoice-number" value=""> --}}
                                        <select class="form-select form-select-sm form-transaction" id="store-id">
                                            <option value="">Pilih Toko</option>
                                            @foreach ($stores as $store)
                                                <option value="{{$store->id}}">{{$store->store_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>
                            @endif
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
                                {{-- <div class="col-6">
                                    <label for="select-product" class="form-label">Kode Item</label>
                                    <select type="text" name="products[${productIndex}][product_id] " class="form-select product-select2 form-transaction" id="select-product">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label for="transaction_number" class="form-label">Scan Barcode: </label>
                                    <input type="text" class="form-control form-control-sm form-transaction" name="scan_barcode" id="scan-barcode">
                                </div> --}}

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
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="product-list">
                                        <tr>
                                            <td colspan="8" class="text-center"><i class="bx bx-message-alt-error"></i> Data Kosong</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-2">
                            <button class="btn btn-danger w-100" onclick="onCancel(event)"><i class="bx bx-x"></i>Batal</button>
                        </div>
                        <div class="col-2">
                            <button class="btn btn-success w-100" onclick="onClickModalPayment(event)"><i class="bx bx-wallet"></i>Bayar</button>
                        </div>
                        <div class="col-8" style="color: red">
                            * Tekan tombol "End" untuk bayar
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
                                    <th>Diskon</th>
                                    <th>Harga Jual + Diskon</th>
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
                    <input type="text" placeholder="Masukan uang tunai" class="form-control form-control-sm" style="text-align: right" id="customer-money" value="" oninput="customerMoney(this.value)">
                </div>
            </div>

            <div class="d-flex mb-2 align-items-center">
                <div style="width: 20%">Total Bayar:</div>
                <div id="total-payment" style="width: 60%; height: 40px; padding: 0 14px; border: 1px solid #ced4da; text-align: right; font-size: 2rem; display: flex; align-items: center; justify-content: flex-end;">
                    0
                </div>
            </div>
            <div class="d-flex mb-2 align-items-center">
                <div style="width: 20%; font-size:1.8rem; color:red" class="fw-bold">Kembalian:</div>
                <div id="lack" style="width: 80%; height: 80px; padding: 0 14px; border: 1px solid #ced4da; text-align: right; font-size: 3rem; display: flex; align-items: center; justify-content: flex-end;">
                    0
                </div>
            </div>


        </div>
        <div class="modal-footer">
            <button type="button" id="btn-payment-print" class="btn btn-primary" onclick="onPaymentPrint()" >Bayar + Print (F12)</button>
            <button type="button" id="btn-payment" class="btn btn-primary" onclick="onPayment()" >Bayar (F11)</button>
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
        let temporaryItemSelect = {};
        let inputTimer = null;
        let inputString = '';
        let lastKeyTime = Date.now();
        let keyModal = Number(1);
        let onClickedItem = Number(0);


        $(document).ready(function() {
            $('#transaction-date').val(getToday());
        });

        $('#qty-modal').on('keydown', function(e) {

                if(e.key === 'Enter') {
                    e.preventDefault()
                    saveQty();
                }
            })
        // For focusing while modal open
        $('#qty-modal').on('shown.bs.modal', function() {
            $('#qty-item').focus();

        });

        $('#item-modal').on('shown.bs.modal', function () {
            isItemModalOpen = true;
            selectedRow = 0;
            highlightRow(selectedRow);
            // $('#search_term').focus();
            $(document).on('keydown', function(e) {
                if(e.key === 'Escape') {
                    $('#item-modal').modal('hide');
                }
            })
            $('#table-item').DataTable().columns.adjust();
        });

        $('#item-modal').on('hidden.bs.modal', function() {
            $('#item-code').focus();
            onClickedItem = 0;
            $('#table-item').DataTable().destroy();
            isItemModalOpen = false;
        });

        // Qty
        $('#qty').on('keydown', function(e) {
            if(e.key === 'Enter') {
                e.preventDefault();
                $('#item-code').focus();
            }
        })
        

        // Function after fill the quantity
        const saveQty = () => {
            let qtyValue = $('#qty-item').val();
            temporaryItemSelect = {...temporaryItemSelect, qty:qtyValue, discount: 0, total:0}
            if(temporaryItemSelect.current_stock < parseInt(qtyValue)) {
                alert('stock tidak cukup');
            }
            // selectedRowData.push(temporaryItemSelect);
            let findIndex = selectedRowData.findIndex(item => temporaryItemSelect.id == item.id);
            if(findIndex !== -1) {
                selectedRowData[findIndex].qty = parseInt(selectedRowData[findIndex].qty) + parseInt(qtyValue);
            } else {
                selectedRowData.push(temporaryItemSelect);
            }
            handleData();
            $('#qty-item').val(null);
            $('#select-product').val(null).trigger('change');
            $('#select-product').focus();
            $('#select-product').select2('open');
            $('#qty-modal').modal('toggle');
        }

        $(document).on('focus', '#qty', function () {
            $(this).select();
        });

        function highlightRow(index) {
            const $rows = $('#table-item tbody tr');
            $rows.removeClass('selected');

            const $row = $rows.eq(index);
            if (!$row.length) return;

            $row.addClass('selected');

            // pakai kontainer scroll terdekat untuk tabel ini
            const $body = $row.closest('.dataTables_scrollBody');
            if ($body.length) {
                const bodyEl = $body.get(0);
                const bodyHeight = $body.height();
                const rowEl = $row.get(0);

                // posisi absolut baris terhadap konten scroll (bukan relatif seperti .position())
                const rowTop = rowEl.offsetTop;          // jarak dari atas konten
                const rowHeight = $row.outerHeight();
                const curScroll = bodyEl.scrollTop;
                const viewBottom = curScroll + bodyHeight;

                // Jika baris di atas viewport -> scroll ke rowTop
                if (rowTop < curScroll) {
                bodyEl.scrollTop = rowTop;
                }
                // Jika baris di bawah viewport -> scroll hingga baris pas terlihat di bawah
                else if (rowTop + rowHeight > viewBottom) {
                bodyEl.scrollTop = rowTop - bodyHeight + rowHeight;
                }
            } else {
                // fallback kalau tidak pakai DataTables scroll
                $row.get(0).scrollIntoView({ block: 'nearest' });
            }
        }

        $(document).on('keydown', function(e) {
            const formTransaction = $('.form-transaction');
            const currentFormTransaction = $(':focus').closest('.form-transaction');
            const index = formTransaction.index(currentFormTransaction);

            if(e.key === 'End') {
                e.preventDefault();
                onClickModalPayment(event)
            }


            if(isItemModalOpen) {

                const rows = $('#table-item tbody tr');
                if(!rows.length) return;
                if(e.key === 'ArrowDown') {
                    e.preventDefault();
                    keyModal = Math.min(keyModal + 1, 3);
                    // if(keyModal === 2) {
                    //     // $('#search_term').blur();

                    //     // $('#search_type').focus();
                    // } else if(keyModal === 3) {
                    //     // $('#search_type').blur();
                        selectedRow = (selectedRow  + 1) % rows.length;
                        highlightRow(selectedRow);    
                    // }
                    
                } else if(e.key ==='ArrowUp') {
                    e.preventDefault();
                    keyModal = Math.max(keyModal - 1, 3);
                    // if(keyModal === 1) {
                    //     // $('#search_term').focus();
                    //     // $('#search_type').blur();
                    // }
                    // else if(keyModal === 2) {
                    //     // $('#search_type').focus();
                    //     const rows = $('#table-item tbody tr');
                    //     // rows.removeClass('selected');
                        
                    // } else if(keyModal === 3) {
                        selectedRow = (selectedRow - 1 + rows.length) % rows.length;
                        highlightRow(selectedRow);    
                    // }

                    console.log(keyModal);
                } else if(e.key === 'Enter') {
                    e.preventDefault();
                    rows.eq(selectedRow).trigger('click');

                }

            } else {
                if(e.key === 'ArrowDown') {
                e.preventDefault();
                let next =''
                next = formTransaction.eq(index + 1);

                if(next.length) {
                    next.focus();
                }
            }

                if(e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prev = formTransaction.eq(index - 1);
                    if(prev.length) {
                        prev.focus();
                    }
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

        

        $('#payment-modal').on('shown.bs.modal', function(e) {
            $('#customer-money').focus();

        })

        $('#payment-modal').on('keydown', function(e) {
                if(e.key === 'F12') {
                    e.preventDefault();
                    onPaymentPrint();
                }
                if(e.key === 'F11') {
                    e.preventDefault();
                    onPayment();
                }
            })


        $('#item-code').keydown(function(event) {
            const currentTime = Date.now();
            const delta = currentTime - lastKeyTime;
            lastKeyTime = currentTime;

            if(event.key === "Enter") {
                onClickedItem = onClickedItem+1;
                search_term = $(this).val();
                event.preventDefault();
                if(delta < 50) {
                    processBarcode(search_term);
                    resetInput();
                } else {
                    if(onClickedItem <= 1) {
                        $('#qty').focus()
                        return;
                    } else {
                        datatableItem();
                        var myModal = new bootstrap.Modal(document.getElementById('item-modal'));
                        isItemModalOpen = true;
                        myModal.show();
                    }
                }
                inputString = '';
                return;
            }
            inputString += event.key
        });


        const processBarcode = debounce((barcode) => {
            let scanBarcodeVal = $('#scan-barcode').val();
            $.ajax({
                url:`{{ route('options.incoming_goods') }}`,
                method:'GET',
                data: {'search_term': barcode, 'type': 'barcode'},
                success: function(response) {
                    let qty = 1;
                    let data = response.data[0];
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

                    resetInput()

                    handleData();
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
            $('#customer-money').val(0);
            let elTotalPayment = $('#total-payment');
            elTotalPayment.html(formatRupiah(0));
            let elLack = $('#lack');
            elLack.html(formatRupiah(0));
            $('#lack').val(0);
            myModal.show();
        }


        const customerMoney = (value) => {
            formData.customer_money = 0

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
                    "paging": false,
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
                        { "data": "price",
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
                        { "data": "product_discount",
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
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
            if(data) {
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
                handleData();
                resetInput();
                itemModal.hide();
            } else {
                return false; 
            }
            
            
        });

        const resetInput = () => {
            $('#item-code').val('');
            $('#qty').val(1);
            $('#item-code').focus();
        }

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
                    const tiers = item.tier_prices || {};
                    if(item.tier_prices !== null) {
                        const thresholds = Object.keys(item.tier_prices).map(Number).sort((a, b) => b - a);
                        let unitPrice = 0;
                        for (const t of thresholds) {
                            if (item.qty >= t) {
                                item.selling_price = Number(tiers[t]);
                                break;
                            }
                        }

                    }
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
                            <td><button class="btn btn-sm btn-danger" onclick="onDelete(${index})"><i class="bx bx-trash me-0"></i></button></td>
                        </tr>
                    `);
                });

                formData = {
                    'transaction_date': $('#transaction-date').val(),
                    'store_id': $('#store-id').val(),
                    'customer_id': $('#customer-id').val(),
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
                        <td colspan="8" class="text-center"><i class="bx bx-message-alt-error"></i> Data Kosong</td>
                    </tr>
                `);
            }
        };

        const onDelete = (idx) => {
            selectedRowData.splice(idx, 1)
            handleData();
        }

        // API
        const onPayment = () => {
            let token = $("meta[name='csrf-token']").attr("content");
            $('#btn-payment-print').prop('disabled', true);
            $('#btn-payment').prop('disabled', true);
            $.ajax({
                    url: '{{route('sales.store')}}',
                    type: 'POST',
                    data: {
                        _token: token,
                        data: formData
                    },
                    success: function(response) {
                        // btnProcesses.removeAttr("disabled");
                        // btnProcesses.removeAttr("disabled");

                         const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end', 
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                        });

                        Toast.fire({
                            icon: 'success',
                            title: 'Sukses',
                            text: response.message,
                            background: '#28a745',
                            color: '#fff' 
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(err) {
                        if(err.responseJSON) {
                            const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end', 
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                        });

                        Toast.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan pada pengisian form, harap periksa kembali',
                            background: '#f27474', 
                            color: '#fff' 
                        }).then(() => {
                             $('#btn-payment-print').prop('disabled', false);
                            $('#btn-payment').prop('disabled', false);
                        });

                        //     Swal.fire({
                        //     icon:'error',
                        //     title: 'error',
                        //     text: 'Terjadi kesalahan pada pengisian form, harap periksa kembali',
                        //     confirmButtonText: 'OK',
                        //     focusConfirm: true 
                        // });
                        }
                    }
                })
        }

        const onPaymentPrint = () => {
            let token = $("meta[name='csrf-token']").attr("content");
            $('#btn-payment-print').prop('disabled', true);
            $('#btn-payment').prop('disabled', true);

            $.ajax({
                    url: '{{route('sales.store')}}',
                    type: 'POST',
                    data: {
                        _token: token,
                        data: formData
                    },
                    success: function(response) {
                        // btnProcesses.removeAttr("disabled");
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end', 
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                        });

                        Toast.fire({
                            icon: 'success',
                            title: 'Sukses',
                            text: response.message,
                            background: '#28a745',
                            color: '#fff' 
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
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end', 
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                            });
                            Toast.fire({
                            icon:'error',
                            title: 'error',
                            text: 'Terjadi kesalahan pada pengisian form, harap periksa kembali',
                            background: '#f27474', 
                            color: '#fff' 
                            }).then(() => {
                                $('#btn-payment-print').prop('disabled', false);
                                $('#btn-payment').prop('disabled', false);
                            });
                        }

                        // btnProcesses.removeAttr("disabled");
                    }
                })
        }


        // Helper
        const formatRupiah = (number) => {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(number);
        };

        function getToday() {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0'); // bulan 01-12
            const dd = String(today.getDate()).padStart(2, '0');      // tanggal 01-31
            return `${yyyy}-${mm}-${dd}`;
        }

        
    </script>
@endsection

