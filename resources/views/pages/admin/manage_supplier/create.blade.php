@extends('layouts.app')
@section('css')
@section('content')
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Supplier</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{route('supplier.index')}}"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{isset($supplier) ? 'Edit' : 'Create'}}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
        </div>
    </div>
    <div class="row">
      <div class="col-xl-9 mx-auto">
        <h6 class="mb-0 text-uppercase">{{ isset($supplier) ? 'Edit' : 'Tambah'}} Merek</h6>
        <hr/>
        <div class="card">
          <div class="card-body">
            <form action="{{isset($supplier) ? route('supplier.update', $supplier->id) : route('supplier.store')}}" method="POST">
            <div class="row">
                @csrf
                @if(isset($supplier))
                  @method('PUT') 
                @endif
                <div class="col-6 mb-3">
                  <label for="code">Kode Supplier</label>
                  <input class="form-control form-control" 
                  type="text" 
                  name="code" 
                  value="{{ isset($supplier) ? $supplier->code : old('code') }}">

                  @error('code')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-6 mb-3">
                  <label for="name">Nama Supplier</label>
                  <input class="form-control form-control" 
                  type="text" 
                  name="name" 
                  value="{{ isset($supplier) ? $supplier->name : old('name') }}">

                  @error('name')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-6 mb-3">
                  <label for="address">Alamat Supplier</label>
                  <textarea class="form-control form-control" 
                  name="address">{{ isset($supplier) ? $supplier->address : old('address') }}</textarea>
                  @error('address')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="col-6 mb-3">
                  <label for="city">City</label>
                  <input class="form-control form-control" 
                  name="city"
                  type="text" 
                  value="{{ isset($supplier) ? $supplier->city : old('city') }}">
                  @error('city')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="col-6 mb-3">
                  <label for="province">Provinsi</label>
                  <input class="form-control form-control" 
                  name="province"
                  type="text" 
                  value="{{ isset($supplier) ? $supplier->province : old('province') }}">
                  @error('province')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="col-6 mb-3">
                  <label for="phone_number">Nomer Telephone</label>
                  <input class="form-control form-control" 
                  name="phone_number"
                  type="number" 
                  value="{{ isset($supplier) ? $supplier->phone_number : old('phone_number') }}">
                  @error('phone_number')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="col-12 text-end">
                  <button class="btn btn-primary" type="submit">{{isset($supplier) ? 'Update' : 'Tambah'}}</button>
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