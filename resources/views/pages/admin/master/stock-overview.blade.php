@extends('layouts.app')

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Stok Produk Per Toko</div>
</div>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
        @if($isSuperadmin)
            <div class="d-flex align-items-center gap-2">
                <label for="store_selector" class="mb-0">Toko</label>
                <select id="store_selector" class="form-select" style="min-width:240px">
                    <option value="">-- Pilih Toko --</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ (string)$selectedStoreId === (string)$store->id ? 'selected' : '' }}>
                            {{ $store->store_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <small id="store_hint" class="text-muted">Pilih toko untuk memuat daftar stok.</small>
        @else
            <span class="badge bg-secondary">Toko: {{ $currentStore ?? '-' }}</span>
        @endif

        <div class="ms-auto">
            <input type="text" id="search_box" class="form-control" placeholder="Cari produk / kode">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="table_stock" class="table table-striped table-bordered w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Produk</th>
                        <th>Nama Produk</th>
                        <th class="text-end">Stok Sistem</th>
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
    $(function () {
        $.fn.dataTable.ext.errMode = 'none';

        const isSuperadmin = @json($isSuperadmin);
        const $storeSelect = $('#store_selector');
        const $storeHint   = $('#store_hint');
        const $searchBox   = $('#search_box');
        let table;
        let currentStoreId = @json($selectedStoreId);

        function buildTable() {
            table = $('#table_stock').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('master-stock.data') }}",
                    data: function (d) {
                        d.store = currentStoreId || '';
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
                    { data: 'product_code', name: 'product_code' },
                    { data: 'product_name', name: 'product_name' },
                    {
                        data: 'stock_system', name: 'stock_system', className: 'text-end',
                        render: (data, type) => type === 'display' || type === 'filter'
                            ? Number(data ?? 0).toLocaleString('id-ID')
                            : data
                    },
                ],
                order: [[2, 'asc']],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' }
            });

            $searchBox.on('keyup', function () {
                table.search(this.value).draw();
            });
        }

        function ensureTable() {
            if (!currentStoreId) {
                $storeHint?.addClass('text-danger').text('Pilih toko terlebih dahulu.');
                return;
            }
            $storeHint?.removeClass('text-danger').text('Memuat data stok...');
            if (table) {
                table.ajax.reload();
            } else {
                buildTable();
            }
        }

        if (isSuperadmin) {
            if (currentStoreId) {
                ensureTable();
            } else {
                $storeHint?.addClass('text-danger').text('Pilih toko terlebih dahulu.');
            }
            $storeSelect.on('change', function () {
                currentStoreId = $(this).val();
                if (!currentStoreId) {
                    $storeHint?.addClass('text-danger').text('Pilih toko terlebih dahulu.');
                    if (table) {
                        table.clear().draw();
                    }
                    return;
                }
                ensureTable();
            });
        } else {
            if (!currentStoreId) {
                currentStoreId = @json($selectedStoreId);
            }
            ensureTable();
        }
    });
</script>
@endsection
