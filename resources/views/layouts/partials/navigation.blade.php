@if(!empty($nav['impersonation']))
  <div class="impersonation-banner" role="status">
    <div class="impersonation-banner__text">
      <strong>Imitation mode</strong> — viewing as {{ $nav['impersonation']['target_name'] }}
      @if($nav['impersonation']['school_name'])
        at {{ $nav['impersonation']['school_name'] }}
      @endif
      <span class="impersonation-banner__meta">Operator: {{ $nav['impersonation']['operator_name'] }}</span>
    </div>
    <form method="post" action="{{ route('impersonation.stop') }}">
      @csrf
      <button type="submit" class="impersonation-banner__btn">End imitation</button>
    </form>
  </div>
@endif

<header class="app-header">
  <div class="app-header__row">
    @include('layouts.partials.brand', [
      'brandHref' => ($nav['zone'] ?? '') === 'platform' ? route('platform.dashboard') : route('app.home'),
    ])

    @if(!empty($nav['school']))
      <span class="context-pill" title="Current school">{{ $nav['school']['name'] }}</span>
    @elseif(($nav['zone'] ?? '') === 'platform')
      <span class="context-pill context-pill--platform">Platform console</span>
    @endif

    <nav class="main-nav" aria-label="Main navigation">
      @foreach($nav['items'] ?? [] as $item)
        @if($item['url'])
          <a href="{{ $item['url'] }}"
             class="{{ $item['active'] ? 'active' : '' }} {{ !empty($item['highlight']) ? 'nav-cta' : '' }}">
            {{ $item['label'] }}
          </a>
        @endif
      @endforeach
    </nav>

    <div class="user-menu">
      <details class="user-menu__details">
        <summary class="user-menu__trigger">
          <span class="user-menu__avatar" aria-hidden="true">{{ $nav['user']['initial'] ?? '?' }}</span>
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
            <a href="{{ route('platform.dashboard') }}" class="user-menu__link">Platform console</a>
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
