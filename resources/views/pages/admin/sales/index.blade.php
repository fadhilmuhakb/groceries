@extends('layouts.app')
@section('css')
<link href="{{asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css')}}" rel="stylesheet" />
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
                    <li class="breadcrumb-item"><a href="{{route('master-unit.index')}}"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Index</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
            {{-- <div class="btn-group">
                <a href="{{route('master-unit.create')}}" class="btn btn-success">
                    + Tambah
                </a>

            </div> --}}
        </div>
    </div>
    <h6 class="mb-0 text-uppercase">Sales</h6>
    <hr/>
    <div class="row mb-3">
        <div class="col-8">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div style="font-size: 18px" class="fw-bold">No. Invoice</div>
                            <p style="font-size: 14px">INV-08250001</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="">Tanggal</label>
                            <input type="date" name="" class="form-control">
                        </div>
                    </div>
                    <hr>

                    <div class="row">
                        <div class="col-12">
                            <h4>{{$user->store->store_name ?? '-'}}</h4>
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
                                    <th width="35%">Produk</th>
                                    <th width="15%">Harga</th>
                                    <th width="10%">Qty</th>
                                    <th width="15%">Diskon</th>
                                    <th>Deskripsi</th>
                                    <th width="8%"></th>
                                </tr>
                            </thead>
                            <tbody id="product-list">
                                <tr>
                                    <td>
                                        <select type="text" name="products[0][product_id]" class="form-select product-select2"></select>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][selling_price]" class="form-control" disabled>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][qty]" oninput="qtyChange(0, this.value)" class="form-control">
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][discount]" oninput="discountChange(0, this.value)" class="form-control">
                                    </td>
                                    <td>
                                        <input type="text" name="products[0][description]" class="form-control">
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)" style="font-size: 24px;" onClick="handlePlus(0)"><i class="lni lni-circle-plus"></i></a>
                                        <a href="javascript:void(0)" style="font-size: 24px; color:red" onClick="handleMinus(0)"><i class="lni lni-circle-minus"></i></a>
                                    </td>
                                </tr>
                            </tbody>
                            <tfooter>
                                <tr>
                                    <td colspan="6">
                                        <a href="javascript:void(0)" onClick="handleAdd()">+ Tambah Produk</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold" style="font-size: 14px">SUB TOTAL</td>
                                    <td class="text-end">
                                        <div id="total-discount" class="fw-bold" style="font-size: 14px"></div>
                                    </td>
                                    <td class="text-end">
                                        <div id="sub-total-price" class="fw-bold" style="font-size: 14px"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold" style="font-size: 14px">TOTAL</td>
                                    <td colspan="2" class="text-end">
                                        <div id="total-price" class="fw-bold" style="font-size: 14px"></div>
                                    </td>
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

    {{-- <div class="row">
        <div class="col-8">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table-type" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Unit</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-4">Data Checkout</h6>
                    <div class="overflow-auto" style="max-height: 400px" id="card-sales">

                    </div>

                    <div class="" id="card-detail">

                    </div>

                </div>
            </div>
        </div>
    </div> --}}


@endsection
@section('scripts')
<script src="{{asset('assets/plugins/datatable/js/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js')}}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>

<script>
    $(document).ready(function() {
        select2();
        getFormData();
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

    const formattedPrice = (price) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
    }

    let selectedValues = [];
    let formData = [];
    const select2 = () => {
        $('.product-select2').select2({
            theme: 'bootstrap-5',
            placeholder:'Cari produk...',
            ajax: {
                url: "{{route('options.incoming_goods')}}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search_term: params.term
                    }
                },
                processResults: function(response) {
                    const filteredData = response.data.filter(item => !selectedValues.includes(item.id));
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
            selectedValues.push(selectedValue);
            let selling_price = $(`input[name="products[${index}][selling_price]"]`);
            let qty = $(`input[name="products[${index}][qty]"]`);
            selling_price.val(e.params.data.selling_price);
            qty.val(1);

            getFormData();
        });
    }


    const handleAdd = () => {
        let productIndex = 1;
        $('#product-list').append(
            `
            <tr>
                <td>
                    <select type="text" name="products[${productIndex}][product_id]" class="form-select product-select2"></select>
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

        select2();

        productIndex++;
    }

    const handlePlus = (productIndex) => {

        let qtyField = $(`input[name="products[${productIndex}][qty]"]`);
        qtyField.val(parseInt(qtyField.val() || 0) + 1);
        getFormData();
    }

    const handleMinus = (productIndex) => {
        let qtyField = $(`input[name="products[${productIndex}][qty]"]`);
        if(qtyField.val() > 1) {
            qtyField.val(parseInt(qtyField.val() || 0) - 1);
        } else {
        }
        getFormData();
    }

    const discountChange = debounce((idx, value) => {
        // console.log(idx);
        $(`input[name="products[${idx}][discount]"]`).val(value);
        getFormData();
    },500)

    const qtyChange = debounce((idx, value) => {
        $(`input[name="products[${idx}][qty]"]`).val(value);
        getFormData();
    }, 500);


    $(document).on('click','.delete-item', function() {
        $(this).closest('tr').remove();
        getFormData();
    })


    const getFormData = () => {
        formData = [];
        let productIndex = 0;
        $('#product-list tr').each((index,row) => {
            let product = {
                product_id: $(row).find('select[name^="products"]').val(),
                qty: $(row).find('input[name^="products"][name$="[qty]"]').val(),
                discount: $(row).find('input[name^="products"][name$="[discount]"]').val(),
                description: $(row).find('input[name^="products"][name$="[description]"]').val(),
                selling_price: $(row).find('input[name^="products"][name$="[selling_price]"]').val(),
            }

            formData.push(product);

            productIndex++;
        })

        let totalDiscount = formData.reduce((num, item) => num + Number(item.discount), 0);
        let totalSelling = formData.reduce((num, item) => num + (Number(item.selling_price)*Number(item.qty)), 0);
        $('#total-discount').text(formattedPrice(totalDiscount));
        $('#sub-total-price').text(formattedPrice(totalSelling));
        $('#total-price').text(formattedPrice(totalSelling-totalDiscount));
        // console.log(formData);
    }


    // let dataSales = [];

    // $(document).ready(function() {
    //     $('#table-type').DataTable({
    //         processing: true,
    //         serverSide: true,
    //         ajax: "{{url('/sales')}}",
    //         columns: [
    //             {data:'product.product_code', name:'product.product_code'},
    //             {data:'product.product_name', name:'product.product_name'},
    //             {data:'product.unit_id', name:'product.unit_id'},
    //             {data:'product.selling_price', name:'product.selling_price'},
    //             {data:'stock', name:'stock'},
    //             { data: 'action', name: 'action', orderable: false, searchable: false, className:'text-center' }

    //         ]
    //     })
    // });

    // const handleSelect = (productData) => {
    //     const existingProduct = dataSales.find((data) => data.product_id === productData.product.id);

    //     if (existingProduct) {
    //         existingProduct.qty += 1;
    //         existingProduct.total = parseInt(existingProduct.qty) * parseInt(existingProduct.selling_price);
    //     } else {
    //         dataSales.push({
    //             product_id: productData.product.id,
    //             product_code: productData.product.product_code,
    //             product_name: productData.product.product_name,
    //             unit_id: productData.product.unit_id,
    //             selling_price: productData.product.selling_price,
    //             stock: productData.stock,
    //             qty: 1,
    //             total: productData.product.selling_price
    //         });
    //     }

    //     renderData();
    // }

    // const handlePlus = (id) => {
    //     const dataExist = dataSales.find((data) => data.product_id === id);
    //     if(dataExist) {
    //         dataExist.qty += 1;
    //         dataExist.total = dataExist.qty * dataExist.selling_price;
    //     }
    //     renderData();
    // }

    // const qtyChange = (id, value) => {
    //     const dataExist = dataSales.find((data) => data.product_id === id);
    //     if(dataExist) {
    //         dataExist.qty = parseInt(value,10);
    //         dataExist.total = dataExist.qty * dataExist.selling_price;
    //     }
    //     renderData();
    // }

    // const handleMinus = (id) => {
    //     const dataExist = dataSales.find((data) => data.product_id === id);
    //     if(dataExist) {
    //         if(dataExist.qty <= 1) {
    //             const index = dataSales.findIndex((data) => data.product_id === id);
    //             dataSales.splice(index, 1);
    //         } else {
    //             dataExist.qty -= 1;
    //             dataExist.total = dataExist.qty * dataExist.selling_price;
    //     }
    //         renderData();
    //     }
    // }

    // const handleDelete = (id) => {
    //     const index = dataSales.findIndex((data) => data.product_id === id);
    //     dataSales.splice(index, 1);
    //     renderData();
    // }



    // const renderData = () => {
    //     $('#card-sales').empty();
    //     $('#card-detail').empty();

    //     if(dataSales.length > 0) {
    //         dataSales.forEach((data) => {
    //         $('#card-sales').append(`
    //             <div class="card">
    //             <div class="card-body">

    //                 <div class="row align-items-center">
    //                     <div class="col-5 ">
    //                         <div class="fw-bold">${data.product_name}</div>
    //                         <div>${formattedPrice(data.selling_price)}</div>
    //                     </div>
    //                     <div class="col-5">
    //                         <div class="input-group input-spinner">
    //                             <button class="btn btn-white" type="button" onClick="handlePlus(${data.product_id})"> + </button>
    //                             <input type="number" class="form-control" onchange="qtyChange(${data.product_id}, this.value)" value="${data.qty}">
    //                             <button class="btn btn-white" type="button" onClick="handleMinus(${data.product_id})"> − </button>
    //                         </div>
    //                     </div>
    //                     <div class="col-2">
    //                         <button class="btn btn-sm btn-danger" onClick="handleDelete(${data.product_id})">X</button>
    //                     </div>
    //                 </div>

    //                 </div>
    //             </div>
    //             `);
    //         });
    //     $total = dataSales.reduce((acc, curr) => acc + parseInt(curr.total), 0);
    //     $('#card-detail').append(`
    //         <div class="card">
    //             <div class="card-body">
    //                 <div class="row">
    //                     <div class="col-6">
    //                         <div class="fw-bold">Total</div>
    //                     </div>
    //                     <div class="col-6">
    //                         <div class="fw-bold">${formattedPrice($total)}</div>
    //                     </div>
    //                 </div>
    //             </div>
    //         </div>
    //     `);

    //     } else {
    //         $('#card-sales').append('Silahkan Pilih Produk');
    //     }
    // }

      @if(session('success'))
          Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: '{{ session('success') }}',
          });
      @endif
</script>
@endsection
