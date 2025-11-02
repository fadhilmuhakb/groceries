<div class="sidebar-wrapper" data-simplebar="true">
  <div class="sidebar-header">…</div>

  <ul class="metismenu" id="menu">
    @foreach($sidebarMenus as $m)
      @include('partials.sidebar-metis-item', ['menu' => $m])
    @endforeach
  </ul>
</div>
