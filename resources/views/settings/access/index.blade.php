@extends('layouts.app')
@section('title','Pengaturan Akses Menu')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Pengaturan Akses Menu</h5>
  </div>

  {{-- pilih role --}}
  <form method="GET" class="d-flex gap-2 align-items-center mb-3">
    <label class="mb-0">Role</label>
    <select name="role" class="form-select" style="max-width:260px" onchange="this.form.submit()">
      @foreach($roles as $r)
        <option value="{{ $r }}" @selected($r===$currentRole)>{{ ucfirst($r) }}</option>
      @endforeach
    </select>
    <noscript><button class="btn btn-primary">Pilih</button></noscript>
  </form>

  @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
  @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

  <form method="POST" action="{{ route('settings.access.save') }}">
    @csrf
    <input type="hidden" name="role_name" value="{{ $currentRole }}">

    <div class="mb-2 d-flex gap-2">
      @if($currentRole !== 'superadmin')
        <button class="btn btn-sm btn-outline-success" type="button" id="btn-check-all">Centang Semua</button>
        <button class="btn btn-sm btn-outline-danger"  type="button" id="btn-uncheck-all">Kosongkan Semua</button>
      @endif
      <button class="btn btn-sm btn-outline-secondary" type="button" id="btn-expand">Expand</button>
      <button class="btn btn-sm btn-outline-secondary" type="button" id="btn-collapse">Collapse</button>
    </div>

    <ul class="list-unstyled">
      @include('settings.access.tree', ['nodes' => $nodes, 'lock' => $currentRole === 'superadmin'])
    </ul>

    @if($currentRole === 'superadmin')
      <div class="text-muted mt-2">Superadmin selalu memiliki akses penuh.</div>
      <button class="btn btn-primary mt-3">Re-seed Akses Superadmin</button>
    @else
      <button class="btn btn-primary mt-3">Simpan Akses</button>
    @endif
  </form>
@endsection

@push('scripts')
<script>
(() => {
  const lock = {{ $currentRole === 'superadmin' ? 'true' : 'false' }};

  if (!lock) {
    document.getElementById('btn-check-all')?.addEventListener('click', () => {
      document.querySelectorAll('input[type=checkbox][data-menu]').forEach(cb => cb.checked = true);
    });
    document.getElementById('btn-uncheck-all')?.addEventListener('click', () => {
      document.querySelectorAll('input[type=checkbox][data-menu]').forEach(cb => cb.checked = false);
    });
  }

  const showAll = (disp) => document.querySelectorAll('ul[data-tree]').forEach(ul => ul.style.display = disp);
  document.getElementById('btn-expand')?.addEventListener('click', () => showAll('block'));
  document.getElementById('btn-collapse')?.addEventListener('click', () => showAll('none'));
})();
</script>
@endpush
