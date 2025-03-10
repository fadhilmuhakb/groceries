@extends('layouts.app')

@section('css')
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Barang Masuk</div>
        <div class="ms-auto">
            <div class="btn-group">
                <a href="{{ route('incoming-goods.create') }}" class="btn btn-success">+ Tambah</a>
            </div>
        </div>
    </div>

    <h6 class="mb-0 text-uppercase">Barang Masuk</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="table-brand" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode</th>
                            <th>Nama Produk</th>
                            <th>Tipe</th>
                            <th>Merek</th>
                            <th>Satuan</th>
                            <th>Harga Jual</th>
                            <th>Harga Beli</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>


@endsection

@section('scripts')
    <script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>

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
                        url: `/master-product/delete/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: token,
                        },
                        success: function (response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses',
                                text: response.message,
                            });
                            $('#table-brand').DataTable().ajax.reload();
                        },
                        error: function (err) {
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
        $(document).ready(function () {
            $('#table-brand').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url('/incoming-goods') }}",
                columns: [
                    { data: null, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'product_code', name: 'product_code' },
                    { data: 'product_name', name: 'product_name' },
                    { data: 'type_name', name: 'type_name' },
                    { data: 'brand_name', name: 'brand_name' },
                    { data: 'unit_name', name: 'unit_name' },
                    { data: 'purchase_price', name: 'purchase_price' },
                    { data: 'selling_price', name: 'selling_price' },
                    { data: 'description', name: 'description' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });
        });


    </script>
@endsection