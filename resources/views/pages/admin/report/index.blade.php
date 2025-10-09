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
      $isSuperadmin = auth()->user()?->roles === 'superadmin';
    @endphp

    @if($isSuperadmin)
      <div class="d-flex align-items-center gap-2">
        <label for="store_select" class="mb-0">Toko</label>
        <select id="store_select" class="form-select" style="min-width:240px">
          <option value="">-- Pilih Toko Dulu --</option>
          @foreach($stores as $store)
            <option value="{{ $store->id }}" {{ (string)$selectedStoreId === (string)$store->id ? 'selected' : '' }}>
              {{ $store->store_name }}
            </option>
          @endforeach
        </select>
      </div>
      <small id="store_hint" class="text-muted">
        Pilih toko untuk memuat data laporan.
      </small>
    @else
      <div><span class="badge bg-secondary">Toko: {{ $currentStoreName ?? '-' }}</span></div>
    @endif
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
            <th>Total</th>
            <th>Tanggal</th>
            <th>+/-</th>
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

<script>
$(function () {
  $.fn.dataTable.ext.errMode = 'none';
  $('#table_kasir').on('error.dt', function(e, settings, techNote, message) {
    console.error('DataTables error (index):', message);
  });

  const isSuperadmin = @json(auth()->user()?->roles === 'superadmin');
  let dt;

  function buildDataTable() {
    dt = $('#table_kasir').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "{{ route('report.index.data') }}",
        data: function (d) {
          // kirimkan store (untuk superadmin), biar backend bisa filter
          d.store = $('#store_select').val() || '';
        }
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
        { data: 'name', name: 'name' },
        {
          data: 'amount', name: 'amount',
          render: function (data, type) {
            if (type === 'display' || type === 'filter') {
              return 'Rp ' + Number(data ?? 0).toLocaleString('id-ID');
            }
            return data;
          },
          className: 'text-end'
        },
        {
          data: 'date', name: 'date',
          render: function (data) {
            return data ? moment(data).format('DD MMM YYYY') : '-';
          }
        },
        {
          data: 'status', name: 'status', className:'text-end',
          render: function (data, type) {
            const val = Number(data ?? 0);
            if (type === 'display' || type === 'filter') {
              const sign = val >= 0 ? '+' : '−';
              const absVal = Math.abs(val);
              const formatted = 'Rp ' + absVal.toLocaleString('id-ID');
              const colorClass = val >= 0 ? 'text-success' : 'text-danger';
              return `<span class="${colorClass}" title="${val >= 0 ? 'Surplus' : 'Defisit'}">
                        ${sign} ${formatted}
                      </span>`;
            }
            return val;
          }
        },
        { data: 'action', name: 'action', orderable:false, searchable:false, className:'text-center align-self-center' },
      ],
      order: [[3, 'desc']]
    });
  }

  // Inisialisasi:
  if (isSuperadmin) {
    const selected = $('#store_select').val();
    if (selected) {
      buildDataTable();
    } else {
      // Belum pilih toko → tunggu user pilih, tampilkan hint
      $('#store_hint').addClass('text-danger').text('Pilih toko dulu untuk memuat data.');
    }

    // Saat toko dipilih/diubah → (re)load tabel
    $('#store_select').on('change', function () {
      $('#store_hint').removeClass('text-danger').text('Memuat data…');
      if (!dt) {
        buildDataTable();
      } else {
        dt.ajax.reload();
      }
    });
  } else {
    // Bukan superadmin → langsung jalan (store otomatis dari backend)
    buildDataTable();
  }
});
</script>
@endsection
