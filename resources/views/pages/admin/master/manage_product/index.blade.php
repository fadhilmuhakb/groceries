@extends('layouts.app')

@section('css')
  <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
  <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Product</div>
    <div class="ms-auto">
      <div class="btn-group">
        <a href="{{ route('master-product.create') }}" class="btn btn-success">+ Tambah</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">Import</button>
      </div>
    </div>
  </div>

  <h6 class="mb-0 text-uppercase">Kelola Product</h6>
  <hr />
  <div class="card">
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label">Cari cepat (kode / nama)</label>
          <input type="text" id="product-search" class="form-control" placeholder="Scan barcode atau ketik kode/nama produk">
        </div>
      </div>
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
              <th>Harga Beli</th>     {{-- diperbaiki urutannya --}}
              <th>Harga Jual</th>
              <th>Harga Tier</th>     {{-- kolom baru --}}
              <th>Keterangan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal Import -->
  <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="importModalLabel">Import Produk dari Excel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="{{ route('master-product.import') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-body">
            <input type="file" name="file" class="form-control" required>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Upload</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    const idr = new Intl.NumberFormat('id-ID');

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
            data: { _token: token },
            success: function (response) {
              Swal.fire({ icon: 'success', title: 'Sukses', text: response.message });
              $('#table-brand').DataTable().ajax.reload();
            },
            error: function (err) {
              Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: (err.responseJSON && (err.responseJSON.message || err.responseJSON.error)) || 'Terjadi kesalahan saat menghapus data!'
              });
            }
          });
        }
      });
    };

    $(document).ready(function () {
  const table = $('#table-brand').DataTable({
    processing: true,
    serverSide: false,        // <— ganti ke false
    ajax: {
      url: "{{ url('/master-product') }}",
      type: 'GET',
      dataSrc: 'data'      // <— pakai ini jika respons { data: [...] }
    },
    columns: [
      { data: null, render: (d,t,r,m) => m.row + m.settings._iDisplayStart + 1 },
      { data: 'product_code' },
      { data: 'product_name' },
      { data: 'type_name' },
      { data: 'brand_name' },
      { data: 'unit_name' },
      { data: 'purchase_price', render: v => v!=null ? idr.format(v) : '-' },
      { data: 'selling_price',  render: v => v!=null ? idr.format(v) : '-' },
      {
        data: 'tier_prices', orderable:false, searchable:false,
        render: function (value) {
          if (!value) return '<em>-</em>';
          let tiers = typeof value === 'string' ? (()=>{try{return JSON.parse(value)}catch(e){return null}})() : value;
          if (!tiers || typeof tiers !== 'object') return '<em>-</em>';
          const keys = Object.keys(tiers).map(k=>parseInt(k,10)).sort((a,b)=>a-b);
          if (!keys.length) return '<em>-</em>';
          return keys.map(q => `&ge; ${q} : ${idr.format(tiers[q])}`).join('<br>');
        }
      },
      { data: 'product_discount', render: v => v!=null ? idr.format(v) : '-' },
      {
        data: 'action', orderable:false, searchable:false,
        render: function (html, type, row) {
          if (html) return html;
          return `
            <div class="btn-group">
              <a href="/master-product/edit/${row.id}" class="btn btn-sm btn-warning">Edit</a>
              <button onclick="confirmDelete(${row.id})" class="btn btn-sm btn-danger">Hapus</button>
            </div>
          `;
        }
      }
    ]
  });

  // Quick filter for scanner / manual entry on product_code or name
  const searchInput = document.getElementById('product-search');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      table.search(searchInput.value).draw();
    });
    searchInput.focus();
  }
});

  </script>
@endsection
