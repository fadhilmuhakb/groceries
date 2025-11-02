@extends('layouts.app')
@section('css')
@section('content')
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Menu</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{route('settings.menus.index')}}"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{isset($menu) ? 'Edit' : 'Create'}}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
        </div>
    </div>
    <div class="row">
      <div class="col-xl-9 mx-auto">
        <h6 class="mb-0 text-uppercase">{{ isset($menu) ? 'Edit' : 'Tambah'}} Menu</h6>
        <hr/>
        <div class="card">
          <div class="card-body">
            <form action="{{isset($menu) ? route('settings.menus.update', $menu->id) : route('settings.menus.store')}}" method="POST">
            <div class="row">
                @csrf
                @if(isset($menu))
                  @method('PUT') 
                @endif
                <div class="col-6 mb-3">
                  <label for="name_type">Nama Menu</label>
                  <input class="form-control form-control" 
                  type="text" 
                  name="menu_name" 
                  value="{{ isset($menu) ? $menu->menu_name : old('menu_name') }}">

                  @error('type_name')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-6 mb-3">
                  <label for="description">Menu Path</label>
                  <input class="form-control form-control" type="text" name="menu_path" 
                  value="{{ isset($menu) ? $menu->menu_path : old('menu_path') }}">
                  @error('menu_path')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-6 mb-3">
                  <label for="description">Menu Icon</label>
                  <input class="form-control form-control" type="text" name="menu_icon" 
                  value="{{ isset($menu) ? $menu->menu_icon : old('menu_icon') }}">
                  @error('menu_icon')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-6 mb-3">
                  <label for="description">Parent</label>
                  {{-- <input class="form-control form-control" type="text" name="parent_id" 
                  value="{{ isset($menu) ? $menu->parent_id : old('parent_id') }}"> --}}
                  <select class="form-select" name="parent_id">
                    <option value="">Pilih Parent</option>
                    @foreach ($menus as $item)
                        <option value="{{$item->id}}" {{ (isset($menu) && $menu->parent_id == $item->id) || old('parent_id') == 1 ? 'selected' : '' }}>{{$item->menu_name}}</option>
                    @endforeach
                  </select>
                  @error('parent_id')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                @if(isset($menu))
                <div class="col-6 mb-3">
                  <label for="description">Status</label>
                  <select class="form-select" name="is_active">
                    <option value="1" {{ ($menu->is_active == 1) || old('is_active') == '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ ($menu->is_active == 0) || old('is_active') == '0' ? 'selected' : '' }}>Non Aktif</option>
                  </select>
                  @error('is_active')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                @endif

                <div class="col-12 text-end">
                  <button class="btn btn-primary" menu="submit">{{isset($menu) ? 'Update' : 'Tambah'}}</button>
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