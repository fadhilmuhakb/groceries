@extends('layouts.app')
@section('css')
@section('content')
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Jenis</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{route('master-unit.index')}}"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{isset($unit) ? 'Edit' : 'Create'}}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
        </div>
    </div>
    <div class="row">
      <div class="col-xl-9 mx-auto">
        <h6 class="mb-0 text-uppercase">{{ isset($unit) ? 'Edit' : 'Tambah'}} Jenis</h6>
        <hr/>
        <div class="card">
          <div class="card-body">
            <form action="{{isset($unit) ? route('master-unit.update', $unit->id) : route('master-unit.store')}}" method="POST">
            <div class="row">
                @csrf
                @if(isset($unit))
                  @method('PUT') 
                @endif
                <div class="col-6 mb-3">
                  <label for="name_type">Nama Satuan</label>
                  <input class="form-control form-control" 
                  type="text" 
                  name="unit_name" 
                  value="{{ isset($unit) ? $unit->unit_name : old('unit_name') }}">

                  @error('unit_name')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-6 mb-3">
                  <label for="description">Keterangan</label>
                  <input class="form-control form-control" type="text" name="description" 
                  value="{{ isset($unit) ? $unit->description : old('description') }}">
                </div>

                <div class="col-12 text-end">
                  <button class="btn btn-primary" type="submit">{{isset($unit) ? 'Update' : 'Tambah'}}</button>
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