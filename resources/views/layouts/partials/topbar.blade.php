@if(!empty($nav['impersonation']))
  <div class="impersonation-banner" role="status">
    <div class="impersonation-banner__text">
      <strong>Imitation mode{{ !empty($nav['impersonation']['read_only']) ? ' (read-only)' : ' (elevated write)' }}</strong>
      — viewing as {{ $nav['impersonation']['target_name'] }}
      @if($nav['impersonation']['school_name'])
        at {{ $nav['impersonation']['school_name'] }}
      @endif
      <span class="impersonation-banner__meta">
        Operator: {{ $nav['impersonation']['operator_name'] }}
        @if(!empty($nav['impersonation']['reason']))
          · Reason: {{ $nav['impersonation']['reason'] }}
        @endif
      </span>
    </div>
    <form method="post" action="{{ route('impersonation.stop') }}">
      @csrf
      <button type="submit" class="impersonation-banner__btn">End imitation</button>
    </form>
  </div>
@endif

<header class="app-header">
  <div class="app-header__row">
    <button type="button" class="sidebar-toggle sidebar-toggle--mobile" aria-label="Open menu" aria-expanded="false" aria-controls="app-sidebar" id="sidebar-open-btn">
      <span aria-hidden="true">☰</span>
      <span class="visually-hidden" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Menu</span>
    </button>

    @include('layouts.partials.brand', [
      'brandHref' => ($nav['zone'] ?? '') === 'platform' ? route('platform.dashboard') : route('app.home'),
    ])

    @if(!empty($nav['school']) && ($nav['zone'] ?? '') === 'platform')
      <span class="context-pill" title="School workspace">Workspace · {{ $nav['school']['name'] }}</span>
    @elseif(!empty($nav['school']))
      <span class="context-pill" title="Current school">{{ $nav['school']['name'] }}</span>
      @if(!empty($nav['school']['term_label']))
        <span class="context-pill context-pill--platform" title="Current term">{{ $nav['school']['term_label'] }}</span>
      @endif
    @elseif(($nav['zone'] ?? '') === 'platform')
      <span class="context-pill context-pill--platform">Platform console</span>
    @endif

    <div class="user-menu">
      <details class="user-menu__details">
        <summary class="user-menu__trigger">
          @if(!empty($nav['user']['avatar_url']))
            <img src="{{ $nav['user']['avatar_url'] }}" alt="" class="user-menu__avatar-img" width="36" height="36">
          @else
            <span class="user-menu__avatar" aria-hidden="true">{{ $nav['user']['initial'] ?? '?' }}</span>
          @endif
          <span class="user-menu__meta">
            <span class="user-menu__name">{{ $nav['user']['name'] ?? '' }}</span>
            <span class="user-menu__role">
              @if(!empty($nav['user']['roles']))
                {{ implode(' · ', $nav['user']['roles']) }}
              @elseif(!empty($nav['user']['is_platform']))
                Platform admin
              @else
                School account
              @endif
            </span>
          </span>
        </summary>
        <div class="user-menu__panel">
          <p class="user-menu__email">{{ $nav['user']['email'] ?? '' }}</p>
          <a href="{{ route('account.settings') }}" class="user-menu__link {{ request()->routeIs('account.*') ? 'active' : '' }}">Account settings</a>
          @if(!empty($nav['user']['is_platform']))
            <a href="{{ route('platform.dashboard') }}" class="user-menu__link">PearlEdu admin</a>
          @endif
          @if(!empty($nav['impersonation']))
            <form method="post" action="{{ route('impersonation.stop') }}">
              @csrf
              <button type="submit" class="user-menu__link user-menu__link--btn">End imitation</button>
            </form>
          @elseif(!empty($nav['user']['is_platform']) && session('platform.entered_school_id'))
            <form method="post" action="{{ route('platform.schools.leave') }}">
              @csrf
              <button type="submit" class="user-menu__link user-menu__link--btn">Exit school view</button>
            </form>
          @endif
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="user-menu__link user-menu__link--btn user-menu__link--danger">Sign out</button>
          </form>
        </div>
      </details>
    </div>
  </div>
</header>
