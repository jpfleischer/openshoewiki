@if (Auth::check())
    @if (! Auth::user()->owns($item))
        <a class="btn btn-outline-primary" href="{{ route('items.closet', $item) }}"
            onclick="event.preventDefault(); document.getElementById('closet-form').submit();">
            <i data-feather="shopping-bag" class="icon-fw"></i>  {{ __('ui.closet.add_button') }}
        </a>
    @else
        <a class="btn btn-outline-danger" href="{{ route('items.closet', $item) }}"
            onclick="event.preventDefault(); document.getElementById('closet-form').submit();">
            <i data-feather="shopping-bag" class="icon-fw"></i>  {{ __('ui.closet.remove') }}
        </a>
    @endif

    <form id="closet-form" action="{{ route('items.closet', $item) }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="_method" value="put">
    </form>
@endif
