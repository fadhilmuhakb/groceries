@extends('layouts.app')
@section('css')
@section('content')
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">User</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{route('user.index')}}"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{isset($user) ? 'Edit' : 'Create'}}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
        </div>
    </div>
    <div class="row">
      <div class="col-xl-9 mx-auto">
        <h6 class="mb-0 text-uppercase">{{ isset($user) ? 'Edit' : 'Tambah'}} User</h6>
        <hr/>
        <div class="card">
          <div class="card-body">
            <form action="{{isset($user) ? route('user.update', $user->id) : route('user.store')}}" method="POST">
            <div class="row">
                @csrf
                @if(isset($user))
                  @method('PUT')
                @endif
                <div class="col-6 mb-3">
                  <label for="name">Nama User</label>
                  <input class="form-control"
                  type="text"
                  name="name"
                  value="{{ isset($user) ? $user->name : old('name') }}">

                  @error('name')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-6 mb-3">
                  <label for="name">Email</label>
                  <input class="form-control"
                  type="email"
                  name="email"
                  value="{{ isset($user) ? $user->email : old('email') }}">

                  @error('email')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                @if(!isset($user))
                <div class="col-6 mb-3">
                  <label for="name">Password</label>
                  <input class="form-control"
                  type="password"
                  name="password"
                  value="{{ isset($user) ? $user->password : old('password') }}">

                  @error('password')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-6 mb-3">
                  <label for="name">Retype Password</label>
                  <input class="form-control"
                  type="password"
                  name="password_confirmation"
                  value="{{ isset($user) ? $user->password : old('password_confirmation') }}">

                  @error('password_confirmation')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                @endif
                <div class="col-6 mb-3">
                  <label for="roles">Role</label>
                  <select class="form-select" name="roles">
                    <option value="">Pilih Role</option>
                    <option value="superadmin" {{ (isset($user) && $user->roles == 'superadmin') || old('roles') == 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                    <option value="admin" {{ (isset($user) && $user->roles == 'admin') || old('roles') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="staff" {{ (isset($user) && $user->roles == 'staff') || old('roles') == 'staff' ? 'selected' : '' }}>Staff</option>
                  </select>

                  @error('roles')
                      <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-6 mb-3">
                    <label for="name">Store</label>
                    <select name="store_id" class="form-select">
                      <option value="">Pilih Toko</option>
                      @foreach ($stores as $store)
                        <option value="{{$store->id}}" {{(isset($user) && $user->store_id == $store->id) || old('store_id') == $store->id ? 'selected': ''}}>{{$store->store_name}}</option>
                      @endforeach
                    </select>

                    @error('store_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                  </div>

                <div class="col-12 text-end">
                  <button class="btn btn-primary" type="submit">{{isset($user) ? 'Update' : 'Tambah'}}</button>
                </div>
            </div>
            </form>

          </div>
        </div>
        @if(isset($user))
        <div class="card">
          <div class="card-body">
            <form action="{{route('user.update.password', $user->id)}}" method="POST">
              <div class="row">
              @csrf
              @method('PUT')
              <div class="col-12 mb-3">
                <label for="name">Password</label>
                <input class="form-control"
                type="password"
                name="new_password"
                value="">

                @error('new_password')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12 mb-3">
                <label for="name">Retype Password</label>
                <input class="form-control"
                type="password"
                name="new_password_confirmation"
                value="">

                @error('new_password_confirmation')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12 text-end">
                <button class="btn btn-primary" type="submit">Update Password</button>
              </div>
              </div>
            </form>
          </div>
        </div>
        @endif
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
