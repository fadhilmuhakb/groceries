@extends('layouts.app')

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
  <div class="breadcrumb-title pe-3">Report Kasir</div>
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

  $('#table_kasir').DataTable({
    processing: true,
    serverSide: true,
    ajax: "{{ route('report.index.data') }}",
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
});
</script>
@endsection
