@extends('layouts.app')

@section('css')
  <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Settings</div>
    <div class="ms-auto">
      <div class="btn-group">
        <a href="{{ route('settings.menus.create') }}" class="btn btn-success">+ Tambah</a>
      </div>
    </div>
  </div>

  <h6 class="mb-0 text-uppercase">Kelola Menu</h6>
  <hr />
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table id="table-brand" class="table table-striped table-bordered" style="width:100%">
          <thead>
            <tr>
              <th>No.</th>
              <th>Nama Menu</th>
              <th>Menu Path</th>
              <th>Menu Icon</th>
              <th>Menu Parent</th>
              <th>Status</th>
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
    $(document).ready(function() {
        $('#table-brand').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{url('/settings/menus')}}",
            columns: [
                {data: null,
                    render: function(data, type, row, meta){
                        return meta.row +meta.settings._iDisplayStart + 1;
                    }
                },
                {data:'menu_name', name:'menu_name'},
                {data:'menu_path', name:'menu_path'},
                {data:'menu_icon', name:'menu_icon'},
                {data:'ancestors', name:'ancestors'},
                {data:null, name:'is_active',
                  render: function(data, type, row, meta) {
                    return data.is_active
                          ? '<span class="badge bg-success">Aktif</span>'
                          : '<span class="badge bg-danger">Non Aktif</span>'
                            }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false, className:'text-end' }

            ]
        })
    });
    
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
                    url: `/settings/menus/${id}`,
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
                        $('#table-brand').DataTable().ajax.reload(); 
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
</script>
@endsection