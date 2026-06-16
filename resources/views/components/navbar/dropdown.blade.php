<li class="nav-item dropdown">
    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
        {{ Auth::user()->username }}
        <span class="badge badge-primary ml-1">{{ number_format(Auth::user()->contributionPoints()) }} pts</span>
        <span class="caret"></span>
    </a>

    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
        <div class="dropdown-item-text text-muted small">
            Contribution Points: <strong>{{ number_format(Auth::user()->contributionPoints()) }}</strong>
        </div>

        <div class="dropdown-divider"></div>

        <a class="dropdown-item" href="{{ route('submit.create') }}">
            <i data-feather="plus-circle" class="icon-fw"></i> Submit a Pair
        </a>

        <a class="dropdown-item" href="{{ route('submit.index') }}">
            <i data-feather="folder" class="icon-fw"></i> My Submissions
        </a>

        <a class="dropdown-item" href="{{ route('candidate-edits.index') }}">
            <i data-feather="git-pull-request" class="icon-fw"></i> Candidate Edits
        </a>

        <a class="dropdown-item" href="{{ route('profile') }}">
            <i data-feather="user" class="icon-fw"></i> {{ __('ui.profile') }}
        </a>

        <a class="dropdown-item" href="{{ route('public_wishlist', ['username' => Auth::user()->username]) }}">
            <i data-feather="star" class="icon-fw"></i> {{ __('ui.wishlist.title') }}
        </a>

        <a class="dropdown-item" href="{{ route('public_closet', ['username' => Auth::user()->username]) }}">
            <i data-feather="tag" class="icon-fw"></i> {{ __('ui.closet.title') }}
        </a>

        <a class="dropdown-item" href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i data-feather="log-out" class="icon-fw"></i> {{ __('Logout') }}
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</li>
