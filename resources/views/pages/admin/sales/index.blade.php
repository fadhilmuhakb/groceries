@extends('layouts.app')
@section('css')
<link href="{{asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css')}}" rel="stylesheet" />
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
    <h6 class="mb-0 text-uppercase">Kelola Jenis</h6>
    <hr/>
    <div class="row">
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
                    Data Checkout
                </div>
            </div>
        </div>
    </div>
    

@endsection
@section('scripts')
<script src="{{asset('assets/plugins/datatable/js/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js')}}"></script>

<script>
//     const confirmDelete = (id) => {
//     let token = $("meta[name='csrf-token']").attr("content");
//     Swal.fire({
//         title: 'Apakah Anda yakin?',
//         text: "Data akan dihapus permanen!",
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonColor: '#3085d6',
//         cancelButtonColor: '#d33',
//         confirmButtonText: 'Ya, hapus!',
//         cancelButtonText: 'Batal'
//     }).then((result) => {
//         if (result.isConfirmed) {
//             $.ajax({
//                 url: `/master-unit/delete/${id}`,
//                 type: 'DELETE',
//                 data: {
//                     _token: token, 
//                 },
//                 success: function(response) {
//                     Swal.fire({
//                         icon: 'success',
//                         title: 'Sukses',
//                         text: response.message,
//                     });
//                     $('#table-type').DataTable().ajax.reload(); 
//                 },
//                 error: function(err) {
//                     Swal.fire({
//                         icon: 'error',
//                         title: 'Oops...',
//                         text: err.responseJSON.message || 'Terjadi kesalahan saat menghapus data!',
//                     });
//                 }
//             });
//         }
//     });
// };
    // $(document).ready(function() {
    //     $('#table-type').DataTable({
    //         processing: true,
    //         serverSide: true,
    //         ajax: "{{url('/master-unit')}}",
    //         columns: [
    //             {data: null,
    //                 render: function(data, type, row, meta){
    //                     return meta.row +meta.settings._iDisplayStart + 1;
    //                 }
    //             },
    //             {data:'unit_name', name:'unit_name'},
    //             {data:'description', name:'description'},
    //             { data: 'action', name: 'action', orderable: false, searchable: false, className:'text-end' }

    //         ]
    //     })
    // });

      @if(session('success'))
          Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: '{{ session('success') }}',
          });
      @endif
</script>
@endsection
