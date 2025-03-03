@extends('layouts.app')
@section('css')
@section('content')
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Jenis</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{route('master-types.index')}}"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
        </div>
    </div>
    <div class="row">
      <div class="col-xl-9 mx-auto">
        <h6 class="mb-0 text-uppercase">Tambah Jenis</h6>
        <hr/>
        <div class="card">
          <div class="card-body">
            <form action="{{route('master-type.store')}}" method="POST">
            <div class="row">
                @csrf
                <div class="col-6">
                  <label for="name_type">Nama Jenis</label>
                  <input class="form-control form-control mb-3" type="text" name="type_name" value="{{ old('type_name') }}">

                  @error('name_type')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-6">
                  <label for="description">Keterangan</label>
                  <input class="form-control form-control mb-3" type="text" name="description" value="{{ old('description') }}">
                </div>

                <div class="col-12 text-end">
                  <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </div>
            </form>
            
            

          </div>
        </div>
      </div>
    </div>
@endsection