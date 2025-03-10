@extends('layouts.app')
@section('css')
@section('content')
  <!--breadcrumb-->

  <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Toko</div>
    <div class="ps-3">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 p-0">
      <li class="breadcrumb-item"><a href="{{route('store.index')}}"><i class="bx bx-home-alt"></i></a>
      </li>
      <li class="breadcrumb-item active" aria-current="page">{{isset($stores) ? 'Edit' : 'Create'}}</li>
      </ol>
    </nav>
    </div>
    <div class="ms-auto">
    </div>
  </div>
  <div class="row">
    <div class="col-xl-9 mx-auto">
    <h6 class="mb-0 text-uppercase">{{ isset($stores) ? 'Edit' : 'Tambah'}} Toko</h6>
    <hr />
    <div class="card">
      <div class="card-body">
      <form action="{{ isset($stores) ? route('store.update', $stores->id) : route('store.store') }}" method="POST">
        @csrf
        @if(isset($stores))
      @method('PUT') 

    @endif
        <div class="row">

        <div class="col-6 mb-3">
          <label for="code">Alamat Toko</label>
          <input class="form-control form-control" type="text" name="store_address"
          value="{{ isset($stores) ? $stores->store_address : old('store_address') }}">

          @error('store_address')
        <div class="text-danger">{{ $message }}</div>
      @enderror
        </div>

        <div class="col-6 mb-3">
          <label for="name">Nama Toko</label>
          <input class="form-control form-control" type="text" name="store_name"
          value="{{ isset($stores) ? $stores->store_name : old('store_name') }}">

          @error('store_name')
        <div class="text-danger">{{ $message}}</div>
      @enderror
        </div>
        <div class="col-12 text-end">
          <button class="btn btn-primary" type="submit">{{isset($stores) ? 'Update' : 'Tambah'}}</button>
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
    text: 'Form yang anda inputkan error', // Menampilkan pesan error pertama
    });
  @endif
    </script>
  @endsection
@endsection