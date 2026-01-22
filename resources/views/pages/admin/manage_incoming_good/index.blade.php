@extends('layouts.app')

@section('css')
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pembelian</div>
        <div class="ms-auto">
            <div class="btn-group">
                <a href="{{ route('purchase.create') }}" class="btn btn-success">+ Tambah</a>
            </div>
        </div>
    </div>

    <h6 class="mb-0 text-uppercase">Data Pembelian</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="table-purchase" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Total Harga</th>
                            <th>Tanggal Bayar</th>
                            <th>Dibuat Pada</th>
                            <th>Diperbarui Pada</th>
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
        $(document).ready(function () {
            $('#table-purchase').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('purchase.index') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'supplier_name', name: 'supplier_name' },
                    { data: 'total_price', name: 'total_price' },
                    { data: 'paid_date', name: 'paid_date' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'updated_at', name: 'updated_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });
        });
    </script>
@endsection
