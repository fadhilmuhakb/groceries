@extends('layouts.app')

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
  <div class="breadcrumb-title pe-3">Report Kasir Bulanan (Penjualan Asli)</div>
</div>

<div class="card mb-3">
  <div class="card-body d-flex gap-2 align-items-center flex-wrap">
    @if($isSuperadmin)
      <div class="d-flex align-items-center gap-2">
        <label for="filter_store" class="mb-0">Toko</label>
        <select id="filter_store" class="form-select" style="min-width:240px">
          <option value="">Semua Toko</option>
          @foreach($stores as $store)
            <option value="{{ $store->id }}" {{ (string)($selectedStoreId ?? '') === (string)$store->id ? 'selected' : '' }}>
              {{ $store->store_name }}
            </option>
          @endforeach
        </select>
      </div>
    @else
      <div><span class="badge bg-secondary">Toko: {{ $currentStoreName ?? '-' }}</span></div>
    @endif

    <div class="vr d-none d-md-block"></div>

    <div class="d-flex align-items-center gap-2">
      <label for="month_from" class="mb-0">Dari</label>
      <input
        type="month"
        id="month_from"
        class="form-control"
        style="min-width:170px"
        value="{{ $defaultMonthFrom }}"
        data-default="{{ $defaultMonthFrom }}"
      >
    </div>

    <div class="d-flex align-items-center gap-2">
      <label for="month_to" class="mb-0">Sampai</label>
      <input
        type="month"
        id="month_to"
        class="form-control"
        style="min-width:170px"
        value="{{ $defaultMonthTo }}"
        data-default="{{ $defaultMonthTo }}"
      >
    </div>

    <button id="reset_filter" class="btn btn-outline-secondary">Reset</button>
    <small id="month_hint" class="text-danger ms-2"></small>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card shadow-none border h-100">
      <div class="card-body">
        <small class="text-muted text-uppercase">Total Omset</small>
        <h4 class="mb-0" id="summary_total_sales">Rp 0</h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-none border h-100">
      <div class="card-body">
        <small class="text-muted text-uppercase">Rata-rata per Kasir per Bulan</small>
        <h4 class="mb-0" id="summary_avg_cashier">Rp 0</h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-none border h-100">
      <div class="card-body">
        <small class="text-muted text-uppercase">Kasir Aktif</small>
        <h4 class="mb-0" id="summary_cashiers">0</h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-none border h-100">
      <div class="card-body">
        <small class="text-muted text-uppercase">Jumlah Bulan</small>
        <h4 class="mb-0" id="summary_months">0</h4>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table id="table_cashier_monthly" class="table table-striped table-bordered w-100">
        <thead>
          <tr>
            <th>No</th>
            <th>Bulan</th>
            <th>Kasir</th>
            <th>Total Omset</th>
            <th>Transaksi</th>
            <th>Hari Aktif</th>
            <th>Rata-rata per Hari</th>
            <th>Growth MoM</th>
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

<script>
$(function () {
  $.fn.dataTable.ext.errMode = 'none';

  const $store = $('#filter_store');
  const $monthFrom = $('#month_from');
  const $monthTo = $('#month_to');
  const $monthHint = $('#month_hint');

  let totalsFromServer = null;
  let dt;

  const formatCurrency = (v) => 'Rp ' + Number(v ?? 0).toLocaleString('id-ID');
  const formatNumber = (v) => Number(v ?? 0).toLocaleString('id-ID');

  function setMonthHint(msg) {
    $monthHint.text(msg || '');
  }

  function updateSummary() {
    const t = totalsFromServer || {};
    $('#summary_total_sales').text(formatCurrency(t.total_sales || 0));
    $('#summary_avg_cashier').text(formatCurrency(t.avg_cashier_month || 0));
    $('#summary_cashiers').text(formatNumber(t.cashier_count || 0));
    $('#summary_months').text(formatNumber(t.months_count || 0));
  }

  function reloadTable() {
    if (dt) {
      dt.ajax.reload(null, false);
    }
  }

  function renderGrowth(data, type) {
    if (type !== 'display' && type !== 'filter') {
      return data;
    }
    if (data === null || data === undefined) {
      return '<span class="text-muted">-</span>';
    }
    const val = Number(data);
    if (!Number.isFinite(val)) {
      return '<span class="text-muted">-</span>';
    }
    const sign = val > 0 ? '+' : '';
    const cls = val >= 0 ? 'text-success' : 'text-danger';
    return `<span class="${cls}">${sign}${val.toFixed(1)}%</span>`;
  }

  function buildTable() {
    dt = $('#table_cashier_monthly').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "{{ route('report.cashier.monthly.data') }}",
        data: function (d) {
          d.store = $store.length ? ($store.val() || '') : '';
          d.month_from = $monthFrom.val() || '';
          d.month_to = $monthTo.val() || '';
        },
        dataSrc: function (json) {
          totalsFromServer = json.totals || null;
          updateSummary();
          return json.data || [];
        }
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
        {
          data: 'sale_month',
          name: 'sale_month',
          render: function (data) {
            return data ? moment(data, 'YYYY-MM').format('MMM YYYY') : '-';
          }
        },
        { data: 'cashier_name', name: 'cashier_name', defaultContent: '-' },
        {
          data: 'total_sales',
          name: 'total_sales',
          className: 'text-end',
          render: function (data, type) {
            if (type === 'display' || type === 'filter') {
              return formatCurrency(data);
            }
            return data;
          }
        },
        {
          data: 'transactions',
          name: 'transactions',
          className: 'text-end',
          render: function (data, type) {
            if (type === 'display' || type === 'filter') {
              return formatNumber(data);
            }
            return data;
          }
        },
        {
          data: 'active_days',
          name: 'active_days',
          className: 'text-end',
          render: function (data, type) {
            if (type === 'display' || type === 'filter') {
              return formatNumber(data);
            }
            return data;
          }
        },
        {
          data: 'avg_daily',
          name: 'avg_daily',
          className: 'text-end',
          render: function (data, type) {
            if (type === 'display' || type === 'filter') {
              return formatCurrency(data);
            }
            return data;
          }
        },
        {
          data: 'mom_growth',
          name: 'mom_growth',
          className: 'text-end',
          render: renderGrowth
        }
      ],
      order: [[1, 'desc'], [2, 'asc']],
      pageLength: 25,
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' }
    });
  }

  buildTable();
  $monthTo.attr('min', $monthFrom.val() || '');

  if ($store.length) {
    $store.on('change', function () {
      setMonthHint('');
      reloadTable();
    });
  }

  $monthFrom.on('change', function () {
    setMonthHint('');
    const from = $monthFrom.val();
    const to = $monthTo.val();

    $monthTo.attr('min', from || '');

    if (!from) {
      $monthTo.val('');
      reloadTable();
      return;
    }

    if (!to) {
      $monthTo.val(from);
    } else if (to < from) {
      $monthTo.val(from);
    }

    reloadTable();
  });

  $monthTo.on('change', function () {
    setMonthHint('');
    const from = $monthFrom.val();
    const to = $monthTo.val();

    if (to && !from) {
      setMonthHint('Isi "Dari" terlebih dahulu sebelum memilih "Sampai".');
      $monthTo.val('');
      $monthFrom.focus();
      return;
    }

    if (from && to && to < from) {
      setMonthHint('"Sampai" tidak boleh sebelum "Dari".');
      $monthTo.val(from);
    }

    reloadTable();
  });

  $('#reset_filter').on('click', function (e) {
    e.preventDefault();
    setMonthHint('');
    $monthFrom.val($monthFrom.data('default') || '');
    $monthTo.val($monthTo.data('default') || '');
    $monthTo.attr('min', $monthFrom.val() || '');
    reloadTable();
  });
});
</script>
@endsection
