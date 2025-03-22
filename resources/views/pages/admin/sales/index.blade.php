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
                        <div class="row">
                            <div class="col-3">
                                <select type="text" name="" class="form-select product-select2"></select>
                            </div>
                            <div class="col-2">
                                <input type="text" name="" class="form-control">
                            </div>
                            <div class="col-2">
                                <input type="text" name="" class="form-control">
                            </div>
                            <div class="col-3">
                                <input type="text" name="" class="form-control">
                            </div>
                            <div class="col-2">
                                <a href="javascript:void(0)" style="font-size: 24px; "><i class="lni lni-circle-plus"></i></a>
                                <a href="javascript:void(0)" style="font-size: 24px; color:red"><i class="lni lni-circle-minus"></i></a>
                            </div>
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
                return {
                    results: response.data,
                };
            },
            cache: true
            },
            minimumInputLength: 1,
        })


        $('.product-select2').on('select2:select', function(e) {
            var selectedValue = e.params.data.id;
            var selectedText = e.params.data.text;
            console.log('Selected Value:', selectedValue);
            console.log('Selected Text:', selectedText);
        });
    })
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

    // const formattedPrice = (price) => {
    //     return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
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
