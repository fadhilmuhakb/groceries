@props(['menu'])

@php
  $isSelfActive = $menu->menu_path ? request()->routeIs($menu->menu_path) : false;
  $childActive  = collect($menu->children ?? [])->contains(fn($c) => $c->menu_path && request()->routeIs($c->menu_path));
  $isActive     = $isSelfActive || $childActive;
  $hasChildren  = $menu->children && $menu->children->isNotEmpty();
@endphp

<li class="{{ $isActive ? 'mm-active' : '' }}">
  @if($hasChildren)
    <a href="javascript:void(0)" class="has-arrow {{ $isActive ? 'aria-expanded' : '' }}">
      <div class="parent-icon"><i class="{{ $menu->menu_icon ?: 'bx bx-folder' }}"></i></div>
      <div class="menu-title">{{ $menu->menu_name }}</div>
    </a>
    <ul class="{{ $isActive ? 'mm-show' : '' }}">
      @foreach($menu->children as $child)
        @include('partials.sidebar-metis-item', ['menu' => $child])
      @endforeach
    </ul>
  @else
   @php
      $targetUrl = ($menu->menu_path && Route::has($menu->menu_path))
        ? route($menu->menu_path)
        : 'javascript:void(0)';
    @endphp
    <a href="{{ $targetUrl }}">
      <div class="parent-icon"><i class="{{ $menu->menu_icon ?: 'bx bx-right-arrow-alt' }}"></i></div>
      <div class="menu-title">{{ $menu->menu_name }}</div>
    </a>
  @endif
</li>
