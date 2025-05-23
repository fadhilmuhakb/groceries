@extends('layouts.app')

@section('css')
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />

@endsection

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Penjualan</div>
        <div class="ms-auto">
            {{-- <div class="btn-group">
                <a href="{{ route('purchase.create') }}" class="btn btn-success">+ Tambah</a>
            </div> --}}
        </div>
    </div>

    <h6 class="mb-0 text-uppercase">Data Penjualan</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="table-sell" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Invoice</th>
                            <th>Toko</th>
                            <th>Tanggal Transaksi</th>
                            <th>Total Pembelian</th>
                            <th>Uang Dibayarkan</th>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.11.5/dataRender/datetime.js"></script>

    <script>
        $(document).ready(function () {
            $('#table-sell').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('sell.index') }}",
                columns: [
                    {
                        data: null,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'no_invoice', name: 'no_invoice'
                    },
                    { data: 'store.store_name', name: 'store.store_name' },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function (data) {
                            return moment(data).format('DD MMM YYYY');
                        }
                    },
                    {   
                        data: 'total_price', 
                        name: 'total_price',
                        render: function(data) {
                            return formattedPrice(data)
                        }
                     },
                    { 
                        data: 'payment_amount', 
                        name: 'payment_amount',
                        render: function(data) {
                            return formattedPrice(data)
                        }
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center align-self-center' }
                ]
            });
        });

        const formattedPrice = (price) => {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(price);
        };
    </script>
@endsection

{{-- sesuaikan untuk createnya pula --}}