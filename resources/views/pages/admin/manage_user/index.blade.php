@extends('layouts.app')
@section('css')
<link href="{{asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css')}}" rel="stylesheet" />
@endsection
@section('content')
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">User</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{route('user.index')}}"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Index</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
            <div class="btn-group">
                @if(Auth::user()->roles == 'superadmin')
                <a href="{{route('user.create')}}" class="btn btn-success">
                    + Tambah
                </a>
                @endif
            </div>
        </div>
    </div>
    <h6 class="mb-0 text-uppercase">Kelola User</h6>
    <hr/>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="table-type" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Store</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
<script src="{{asset('assets/plugins/datatable/js/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js')}}"></script>

<script>
    const confirmDelete = (id) => {
    let token = $("meta[name='csrf-token']").attr("content");
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/user/delete/${id}`,
                type: 'DELETE',
                data: {
                    _token: token,
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses',
                        text: response.message,
                    });
                    $('#table-type').DataTable().ajax.reload();
                },
                error: function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: err.responseJSON.message || 'Terjadi kesalahan saat menghapus data!',
                    });
                }
            });
        }
    });
};
    $(document).ready(function() {
        $('#table-type').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{url('/user')}}",
            columns: [
                {data: null,
                    render: function(data, type, row, meta){
                        return meta.row +meta.settings._iDisplayStart + 1;
                    }
                },
                {data:'name', name:'name'},
                {data:'email', name:'email'},
                {data:'roles', name:'roles'},
                {data:'store.store_name', name:'store.store_name', defaultContent: '-',},
                {data: 'action', name: 'action', orderable: false, searchable: false, className:'text-end'}

            ]
        })
    });

      @if(session('success'))
          Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: '{{ session('success') }}',
          });
      @endif
</script>
@endsection
