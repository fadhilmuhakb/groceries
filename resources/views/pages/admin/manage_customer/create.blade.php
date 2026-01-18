@extends('layouts.app')
@section('css')
@section('content')
  <!--breadcrumb-->

  <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Customer</div>
    <div class="ps-3">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 p-0">
      <li class="breadcrumb-item"><a href="{{route('customer.index')}}"><i class="bx bx-home-alt"></i></a>
      </li>
      <li class="breadcrumb-item active" aria-current="page">{{isset($customer) ? 'Edit' : 'Create'}}</li>
      </ol>
    </nav>
    </div>
    <div class="ms-auto">
    </div>
  </div>
  <div class="row">
    <div class="col-xl-9 mx-auto">
    <h6 class="mb-0 text-uppercase">{{ isset($customer) ? 'Edit' : 'Tambah'}} Customer</h6>
    <hr />
    <div class="card">
      <div class="card-body">
      <form action="{{ isset($customer) ? route('customer.update', $customer->id) : route('customer.store') }}" method="POST">
        @csrf
        @if(isset($customer))
      @method('PUT') 

    @endif
        <div class="row">

        <div class="col-6 mb-3">
          <label for="code">Nama Customer</label>
          <input class="form-control form-control" type="text" name="customer_name"
          value="{{ isset($customer) ? $customer->customer_name : old('customer_name') }}">

          @error('customer_name')
        <div class="text-danger">{{ $message }}</div>
      @enderror
        </div>

        <div class="col-6 mb-3">
          <label for="name">Nomer Telephone</label>
          <input class="form-control form-control" type="text" name="phone_number"
          value="{{ isset($customer) ? $customer->phone_number : old('phone_number') }}">

        @error('phone_number')
        <div class="text-danger">{{ $message}}</div>
        @enderror
        </div>
        @if(store_access_can_select(Auth::user()))
          <div class="col-6 mb-3">
            <label for="store">Store</label>
            <select name="store_id" class="form-select">
              <option value="">Pilih Toko</option>
              @foreach ($stores as $store)
                <option value="{{$store->id}}"
                  {{ (int) old('store_id', $customer->store_id ?? 0) === (int) $store->id ? 'selected' : '' }} 
                  >{{$store->store_name}}</option>
              @endforeach
            </select>
          </div>
        @endif

        <div class="col-12 text-end">
          <button class="btn btn-primary" type="submit">{{isset($customer) ? 'Update' : 'Tambah'}}</button>
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
