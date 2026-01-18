@extends('layouts.app')

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
  <div class="breadcrumb-title pe-3">Report Kasir</div>
</div>

{{-- Filter bar --}}
<div class="card mb-3">
  <div class="card-body d-flex gap-2 align-items-center flex-wrap">
    @php
      $canSelectStore = store_access_can_select(auth()->user());
    @endphp

    @if($canSelectStore)
      <div class="d-flex align-items-center gap-2">
        <label for="store_select" class="mb-0">Toko</label>
        <select id="store_select" class="form-select" style="min-width:240px">
          <option value="">-- Pilih Toko Dulu --</option>
          @foreach($stores as $store)
            <option value="{{ $store->id }}" {{ (string)($selectedStoreId ?? '') === (string)$store->id ? 'selected' : '' }}>
              {{ $store->store_name }}
            </option>
          @endforeach
        </select>
      </div>
      <small id="store_hint" class="text-muted">Pilih toko untuk memuat data laporan.</small>
    @else
      <div><span class="badge bg-secondary">Toko: {{ $currentStoreName ?? '-' }}</span></div>
    @endif

    <div class="vr d-none d-md-block"></div>

    {{-- Filter tanggal --}}
    <div class="d-flex align-items-center gap-2">
      <label for="date_from" class="mb-0">Dari</label>
      <input type="date" id="date_from" class="form-control" style="min-width:170px">
    </div>

    <div class="d-flex align-items-center gap-2">
      <label for="date_to" class="mb-0">Sampai</label>
      <input type="date" id="date_to" class="form-control" style="min-width:170px">
    </div>

    <button id="reset_filter" class="btn btn-outline-secondary">Reset</button>

    {{-- Pesan validasi ringan --}}
    <small id="date_hint" class="text-danger ms-2"></small>
  </div>
</div>

<h6 class="mb-0 text-uppercase">Data Pembelian</h6>
<hr />

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table id="table_kasir" class="table table-striped table-bordered w-100">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Input Kasir</th>
            <th>Pendapatan Omset</th>
            <th>Tanggal</th>
            <th>+/-</th>
            <th>Aksi</th>
          </tr>
        </thead>

        {{-- FOOTER untuk total --}}
        <tfoot>
          <tr>
            <th></th>
            <th class="text-end">Total</th>
            <th class="text-end" id="footer_total_amount">Rp 0</th>
            <th class="text-end" id="footer_total_omset">Rp 0</th>
            <th></th>
            <th class="text-end" id="footer_total_status">Rp 0</th>
            <th></th>
          </tr>
        </tfoot>

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
  $('#table_kasir').on('error.dt', function(e, settings, techNote, message) {
    console.error('DataTables error (index):', message);
  });

  const isSuperadmin = @json(store_access_can_select(auth()->user()));
  let dt;

  const formatCurrency = (v) => 'Rp ' + Number(v ?? 0).toLocaleString('id-ID');
  const renderCurrencyCell = (data, type) => {
    if (type === 'display' || type === 'filter') {
      return formatCurrency(data);
    }
    return data;
  };
  const renderStatusCell = (data, type) => {
    const val = Number(data ?? 0);
    if (type === 'display' || type === 'filter') {
      const sign = val >= 0 ? '+' : '−';
      const absVal = Math.abs(val);
      const colorClass = val >= 0 ? 'text-success' : 'text-danger';
      return `<span class="${colorClass}" title="${val >= 0 ? 'Surplus' : 'Defisit'}">
                ${sign} Rp ${absVal.toLocaleString('id-ID')}
              </span>`;
    }
    return val;
  };

  function todayStr() {
    const d = new Date(); // Asia/Jakarta di sisi client
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  function setDateHint(msg) {
    $('#date_hint').text(msg || '');
  }

  function reloadIfReady() {
    // Superadmin wajib pilih toko dulu
    if (isSuperadmin && !$('#store_select').val()) {
      $('#store_hint').addClass('text-danger').text('Pilih toko dulu untuk memuat data.');
      return;
    }
    if (dt) dt.ajax.reload(null, false);
  }

  function buildDataTable() {
    let serverTotals = null;

    dt = $('#table_kasir').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "{{ route('report.index.data') }}",
        type: 'GET',
        cache: false,
        data: function (d) {
          d.store     = $('#store_select').val() || '';
          d.date_from = $('#date_from').val()    || '';
          d.date_to   = $('#date_to').val()      || '';
        },
        dataSrc: function (json) {
          serverTotals = json.totals || null;
          return json.data || [];
        }
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
        { data: 'name', name: 'name' },
        { data: 'amount', name: 'amount', className: 'text-end', render: renderCurrencyCell },
        { data: 'omset',  name: 'omset',  className: 'text-end', render: renderCurrencyCell },
        {
          data: 'date', name: 'date',
          render: function (data) {
            return data ? moment(data).format('DD MMM YYYY') : '-';
          }
        },
        { data: 'status', name: 'status', className:'text-end', render: renderStatusCell },
        { data: 'action', name: 'action', orderable:false, searchable:false, className:'text-center' },
      ],
      order: [[4, 'desc']],
      pageLength: 25,
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' },
      footerCallback: function () {
        const api = this.api();
        const pageSum = (i) =>
          api.column(i, { page: 'current' })
             .data()
             .reduce((a, b) => Number(a) + Number(b ?? 0), 0);

        const amountPage = pageSum(2);
        const omsetPage  = pageSum(3);
        const statusPage = pageSum(5);

        let amountHtml = formatCurrency(amountPage);
        let omsetHtml  = formatCurrency(omsetPage);
        let statusHtml = renderStatusCell(statusPage, 'display');

        if (serverTotals) {
          amountHtml += `<br><strong>${formatCurrency(serverTotals.amount ?? 0)}</strong> <small class="text-muted">(filter)</small>`;
          omsetHtml  += `<br><strong>${formatCurrency(serverTotals.omset ?? 0)}</strong> <small class="text-muted">(filter)</small>`;
          statusHtml += `<br><strong>${renderStatusCell(serverTotals.status ?? 0, 'display')}</strong> <small class="text-muted">(filter)</small>`;
        }

        $('#footer_total_amount').html(amountHtml);
        $('#footer_total_omset').html(omsetHtml);
        $('#footer_total_status').html(statusHtml);
      }
    });
  }

  // Inisialisasi
  if (isSuperadmin) {
    const selected = $('#store_select').val();
    if (selected) {
      buildDataTable();
    } else {
      $('#store_hint').addClass('text-danger').text('Pilih toko dulu untuk memuat data.');
    }
    $('#store_select').on('change', function () {
      $('#store_hint').removeClass('text-danger').text('Memuat data…');
      setDateHint('');
      if (!dt) buildDataTable(); else reloadIfReady();
    });
  } else {
    buildDataTable();
  }

  // ==== Auto apply tanggal (tanpa tombol) ====

  // Jika user ubah date_from:
  $('#date_from').on('change', function () {
    setDateHint('');
    const from = $('#date_from').val();
    const to   = $('#date_to').val();

    // Atur batas minimal date_to
    $('#date_to').attr('min', from || '');

    if (!from) {
      // Jika from dikosongkan: kosongkan to dan reload -> default server = hari ini
      $('#date_to').val('');
      reloadIfReady();
      return;
    }

    if (!to) {
      // Jika hanya from diisi -> set to = hari ini dan reload
      $('#date_to').val(todayStr());
    }
    reloadIfReady();
  });

  // Jika user ubah date_to:
  $('#date_to').on('change', function () {
    setDateHint('');
    const from = $('#date_from').val();
    const to   = $('#date_to').val();

    if (to && !from) {
      setDateHint('Isi "Dari" terlebih dahulu sebelum memilih "Sampai".');
      $('#date_to').val('');
      $('#date_from').focus();
      return;
    }

    if (from && to) {
      // Pastikan min terpenuhi
      $('#date_to').attr('min', from);
      if (to < from) {
        setDateHint('"Sampai" tidak boleh sebelum "Dari".');
        $('#date_to').val(from);
      }
      reloadIfReady();
    }
  });

  // Reset filter
  $('#reset_filter').on('click', function (e) {
    e.preventDefault();
    setDateHint('');
    $('#date_from').val('');
    $('#date_to').val('');
    $('#date_to').attr('min', '');
    reloadIfReady();
  });
});
</script>
@endsection
