@extends('layouts.app')

@section('css')
<!-- Tambahkan CSS jika diperlukan -->
@endsection

@section('content')
  <!-- Breadcrumb -->
  <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Produk</div>
    <div class="ps-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-0">
          <li class="breadcrumb-item"><a href="{{ route('master-product.index') }}"><i class="bx bx-home-alt"></i></a></li>
          <li class="breadcrumb-item active" aria-current="page">{{ isset($product) ? 'Edit' : 'Tambah' }} Produk</li>
        </ol>
      </nav>
    </div>
    <div class="ms-auto"></div>
  </div>

  <div class="row">
    <div class="col-xl-9 mx-auto">
      <h6 class="mb-0 text-uppercase">{{ isset($product) ? 'Edit' : 'Tambah' }} Produk</h6>
      <hr />

      <div class="card">
        <div class="card-body">
          <form action="{{ isset($product) ? route('master-product.update', $product->id) : route('master-product.store') }}" method="POST">
            @csrf
            @if(isset($product))
              @method('PUT')
            @endif

            <div class="row">
              <div class="col-6 mb-3">
                <label for="product_code">Kode Produk</label>
                <input class="form-control" type="text" name="product_code" value="{{ isset($product) ? $product->product_code : old('product_code') }}">
                @error('product_code')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-6 mb-3">
                <label for="product_name">Nama Produk</label>
                <input class="form-control" type="text" name="product_name" value="{{ isset($product) ? $product->product_name : old('product_name') }}">
                @error('product_name')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-4 mb-3">
                <label for="type_id">Tipe Produk</label>
                <select class="form-control" name="type_id">
                  <option value="">-- Pilih Tipe --</option>
                  @foreach($types as $type)
                    <option value="{{ $type->id }}" {{ isset($product) && $product->type_id == $type->id ? 'selected' : '' }}>
                      {{ $type->type_name }}
                    </option>
                  @endforeach
                </select>
                @error('type_id')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-4 mb-3">
                <label for="brand_id">Merek</label>
                <select class="form-control" name="brand_id">
                  <option value="">-- Pilih Merek --</option>
                  @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ isset($product) && $product->brand_id == $brand->id ? 'selected' : '' }}>
                      {{ $brand->brand_name }}
                    </option>
                  @endforeach
                </select>
                @error('brand_id')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-4 mb-3">
                <label for="unit_id">Satuan</label>
                <select class="form-control" name="unit_id">
                  <option value="">-- Pilih Satuan --</option>
                  @foreach($units as $unit)
                    <option value="{{ $unit->id }}" {{ isset($product) && $product->unit_id == $unit->id ? 'selected' : '' }}>
                      {{ $unit->unit_name }}
                    </option>
                  @endforeach
                </select>
                @error('unit_id')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-6 mb-3">
                <label for="purchase_price">Harga Beli</label>
                <input class="form-control" type="number" name="purchase_price" value="{{ isset($product) ? $product->purchase_price : old('purchase_price') }}">
                @error('purchase_price')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-6 mb-3">
                <label for="selling_price">Harga Jual</label>
                <input class="form-control" type="number" name="selling_price" value="{{ isset($product) ? $product->selling_price : old('selling_price') }}">
                @error('selling_price')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12 mb-3">
                <label for="description">Deskripsi</label>
                <textarea class="form-control" name="description">{{ isset($product) ? $product->description : old('description') }}</textarea>
                @error('description')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12 text-end">
                <button class="btn btn-primary" type="submit">{{ isset($product) ? 'Update' : 'Tambah' }}</button>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>

  @section('scripts')
  <script>
    @if(session('success'))
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '{{ session('success') }}',
      });
    @endif

    @if($errors->any())
      Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: 'Form yang Anda inputkan error',
      });
    @endif
  </script>
  @endsection
@endsection
