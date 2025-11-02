@props(['nodes','lock' => false])

@foreach($nodes as $n)
  <li class="mb-1">
    <div class="form-check">
      <input class="form-check-input"
             type="checkbox"
             data-menu
             id="m{{ $n->id }}"
             name="menu[{{ $n->id }}]"
             @checked($n->checked)
             @disabled($lock)>
      <label class="form-check-label" for="m{{ $n->id }}">
        {!! $n->icon ? "<i class='{$n->icon}'></i>" : '' !!} {{ $n->name }}
        @if($n->path)<small class="text-muted">(<code>{{ $n->path }}</code>)</small>@endif
      </label>
    </div>

    @if($n->children->isNotEmpty())
      <ul class="ms-4 list-unstyled" data-tree>
        @include('settings.access.tree', ['nodes' => $n->children, 'lock' => $lock])
      </ul>
    @endif
  </li>
@endforeach
