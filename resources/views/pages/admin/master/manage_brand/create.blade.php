@extends('layouts.app')
@section('css')
@section('content')
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Merek</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{route('master-brand.index')}}"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{isset($brand) ? 'Edit' : 'Create'}}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
        </div>
    </div>
    <div class="row">
      <div class="col-xl-9 mx-auto">
        <h6 class="mb-0 text-uppercase">{{ isset($brand) ? 'Edit' : 'Tambah'}} Merek</h6>
        <hr/>
        <div class="card">
          <div class="card-body">
            <form action="{{isset($brand) ? route('master-brand.update', $brand->id) : route('master-brand.store')}}" method="POST">
            <div class="row">
                @csrf
                @if(isset($brand))
                  @method('PUT') 
                @endif
                <div class="col-6 mb-3">
                  <label for="name_type">Nama Merek</label>
                  <input class="form-control form-control" 
                  type="text" 
                  name="brand_name" 
                  value="{{ isset($brand) ? $brand->brand_name : old('brand_name') }}">

                  @error('type_name')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-6 mb-3">
                  <label for="description">Keterangan</label>
                  <input class="form-control form-control" type="text" name="description" 
                  value="{{ isset($brand) ? $brand->description : old('description') }}">
                </div>

                <div class="col-12 text-end">
                  <button class="btn btn-primary" type="submit">{{isset($brand) ? 'Update' : 'Tambah'}}</button>
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