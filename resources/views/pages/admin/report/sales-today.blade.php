@extends('layouts.app')

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Penjualan Harian</div>
</div>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
        @if($isSuperadmin)
            <div class="d-flex align-items-center gap-2">
                <label for="filter_store" class="mb-0">Toko</label>
                <select id="filter_store" class="form-select" style="min-width:220px">
                    <option value="">Semua Toko</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ (string)$selectedStoreId === (string)$store->id ? 'selected' : '' }}>
                            {{ $store->store_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            <div>
                <span class="badge bg-secondary">
                    Toko: {{ $currentStoreName ?? 'Tidak diketahui' }}
                </span>
            </div>
        @endif

        <div class="vr d-none d-md-block"></div>

        <div class="d-flex align-items-center gap-2">
            <strong class="mb-0">Tanggal</strong>
            <span>{{ \Carbon\Carbon::parse($defaultDate)->translatedFormat('d M Y') }} (otomatis)</span>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <strong>Filter Kasir:</strong>
            <div id="cashier_filters" class="btn-group flex-wrap" role="group" aria-label="Filter kasir">
                <button type="button" class="btn btn-outline-secondary btn-sm active" data-cashier-filter="">Semua Kasir</button>
                @foreach($cashiers as $cashier)
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-cashier-filter="{{ $cashier }}">
                        {{ $cashier }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="filter_cashier" value="">

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-none border h-100">
            <div class="card-body">
                <small class="text-muted text-uppercase">Total Item</small>
                <h4 class="mb-0" id="summary_items">0</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-none border h-100">
            <div class="card-body">
                <small class="text-muted text-uppercase">Total Qty</small>
                <h4 class="mb-0" id="summary_quantity">0</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-none border h-100">
            <div class="card-body">
                <small class="text-muted text-uppercase">Total Penjualan</small>
                <h4 class="mb-0" id="summary_sales">Rp 0</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-none border h-100">
            <div class="card-body">
                <small class="text-muted text-uppercase">Total Diskon</small>
                <h4 class="mb-0" id="summary_discount">Rp 0</h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="table_daily_sales" class="table table-striped table-bordered w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No Invoice</th>
                        <th>Toko</th>
                        <th>Kasir</th>
                        <th>Pelanggan</th>
                        <th>Produk</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Diskon</th>
                        <th>Total</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th class="text-end">Total</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th class="text-end" id="footer_total_qty">0</th>
                        <th></th>
                        <th class="text-end" id="footer_total_discount">Rp 0</th>
                        <th class="text-end" id="footer_total_sales">Rp 0</th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<script>
    $(function () {
        $.fn.dataTable.ext.errMode = 'none';

        const $store   = $('#filter_store');
        const $cashier = $('#filter_cashier');
        const $cashierFilters = $('#cashier_filters');

        let totalsFromServer = null;

        const table = $('#table_daily_sales').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('report.sales.today.data') }}",
                data: function (d) {
                    d.store   = $store.length ? $store.val() : '';
                    d.cashier = $cashier.val() || '';
                },
                dataSrc: function (json) {
                    totalsFromServer = json?.totals || null;
                    updateSummary();
                    renderCashierButtons(json?.cashiers || []);
                    return json.data || [];
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'no_invoice', name: 'no_invoice' },
                { data: 'store_name', name: 'store_id', defaultContent: '-' },
                { data: 'recorded_by', name: 'recorded_by', defaultContent: '-' },
                { data: 'customer_name', name: 'customer_id', defaultContent: '-' },
                { data: 'product_name', name: 'product_name', defaultContent: '-' },
                {
                    data: 'quantity_out',
                    name: 'quantity_out',
                    className: 'text-end',
                    render: (data, type) => type === 'display' || type === 'filter'
                        ? Number(data || 0).toLocaleString('id-ID')
                        : data
                },
                {
                    data: 'unit_price',
                    name: 'unit_price',
                    className: 'text-end',
                    render: (data, type) => renderCurrency(data, type)
                },
                {
                    data: 'line_discount',
                    name: 'line_discount',
                    className: 'text-end',
                    render: (data, type) => renderCurrency(data, type)
                },
                {
                    data: 'line_total',
                    name: 'line_total',
                    className: 'text-end',
                    render: (data, type) => renderCurrency(data, type)
                },
                {
                    data: 'date',
                    name: 'date',
                    render: (data) => data ? moment(data).format('DD MMM YYYY') : '-'
                },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[9, 'desc']],
            pageLength: 25,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
            },
            footerCallback: function () {
                const api = this.api();
                const data = api.rows({ page: 'current' }).data();

                let pageQty = 0;
                let pageDiscount = 0;
                let pageSales = 0;

                data.each(function (row) {
                    pageQty      += Number(row.quantity_out || 0);
                    pageDiscount += Number(row.line_discount || 0);
                    pageSales    += Number(row.line_total || 0);
                });

                $('#footer_total_qty').text(Number(pageQty).toLocaleString('id-ID'));
                $('#footer_total_discount').text(formatCurrency(pageDiscount));
                $('#footer_total_sales').text(formatCurrency(pageSales));
            }
        });

        $store.on('change', reloadTable);
        $cashierFilters.on('click', 'button[data-cashier-filter]', function () {
            const cashierVal = $(this).data('cashier-filter') ?? '';
            $cashier.val(cashierVal);
            setActiveCashierButton(cashierVal);
            reloadTable();
        });

        function reloadTable() {
            table.ajax.reload(null, false);
        }

        function updateSummary() {
            const totals = totalsFromServer || {};
            $('#summary_items').text(Number(totals.items || 0).toLocaleString('id-ID'));
            $('#summary_quantity').text(Number(totals.quantity || 0).toLocaleString('id-ID'));
            $('#summary_sales').text(formatCurrency(totals.sales || 0));
            $('#summary_discount').text(formatCurrency(totals.discount || 0));
        }

        function renderCurrency(value, type) {
            if (type === 'display' || type === 'filter') {
                return formatCurrency(value);
            }
            return value;
        }

        function formatCurrency(value) {
            const number = Number(value || 0);
            const sign = number < 0 ? '- ' : '';
            return `${sign}Rp ${Math.abs(number).toLocaleString('id-ID')}`;
        }

        function renderCashierButtons(list) {
            const current = $cashier.val() || '';
            let buttonsHtml = `<button type="button" class="btn btn-outline-secondary btn-sm${current === '' ? ' active' : ''}" data-cashier-filter="">Semua Kasir</button>`;
            list.forEach(name => {
                const safeName = name ?? '-';
                const active = current === safeName ? ' active' : '';
                buttonsHtml += `<button type="button" class="btn btn-outline-secondary btn-sm${active}" data-cashier-filter="${safeName}">${safeName}</button>`;
            });
            $cashierFilters.html(buttonsHtml);
        }

        function setActiveCashierButton(value) {
            $cashierFilters.find('button[data-cashier-filter]').each(function () {
                const btnVal = $(this).data('cashier-filter') ?? '';
                $(this).toggleClass('active', btnVal === value);
            });
        }
    });
</script>
@endsection
