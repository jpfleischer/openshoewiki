@if (Auth::check())
    @if (! Auth::user()->wants($item))
        <a class="btn btn-outline-primary" href="{{ route('items.wishlist', $item) }}"
            onclick="event.preventDefault(); document.getElementById('wishlist-form').submit();">
            <i data-feather="star" class="icon-fw"></i>  {{ __('ui.wishlist.add_button') }}
        </a>
    @else
        <a class="btn btn-outline-danger" href="{{ route('items.wishlist', $item) }}"
            onclick="event.preventDefault(); document.getElementById('wishlist-form').submit();">
            <i data-feather="star" class="icon-fw"></i>  {{ __('ui.wishlist.remove') }}
        </a>
    @endif

    <form id="wishlist-form" action="{{ route('items.wishlist', $item) }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="_method" value="put">
    </form>
@endif
