@php
  $icons = [
    'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9a1 1 0 0 0 1 1H9.5v-6h5v6h3a1 1 0 0 0 1-1v-9"/>',
    'sms' => '<path d="M4 5.5h16a1 1 0 0 1 1 1V16a1 1 0 0 1-1 1H8l-4 3.5V6.5a1 1 0 0 1 1-1Z"/>',
    'students' => '<circle cx="9" cy="8" r="3.2"/><circle cx="16.5" cy="9" r="2.6"/><path d="M3.5 19.5c.8-3.2 3-5 5.5-5s4.7 1.8 5.5 5"/><path d="M13.2 19.5c.4-2.2 1.8-3.6 3.5-3.6 1.5 0 2.7 1 3.3 2.6"/>',
    'platform' => '<path d="M12 3 3 7.5 12 12l9-4.5L12 3Z"/><path d="M3 12l9 4.5 9-4.5"/><path d="M3 16.5 12 21l9-4.5"/>',
    'dashboard' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/>',
    'schools' => '<path d="M4 21V9l8-5 8 5v12"/><path d="M9 21v-6h6v6"/><path d="M4 21h16"/>',
    'add' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 8v8M8 12h8"/>',
    'account' => '<circle cx="12" cy="8.5" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
    'pricing' => '<path d="M20.6 13.4 12.6 21.4a2 2 0 0 1-2.8 0L2 13.6V4a2 2 0 0 1 2-2h9.6l7 7a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
    'workspace' => '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><path d="M3.5 9.5h17"/><path d="M8 4.5v5"/>',
    'classes' => '<path d="M4 6h16v12H4z"/><path d="M8 6v12M4 10h16"/>',
    'staff' => '<circle cx="9" cy="8" r="3"/><path d="M3.5 19c.7-3 2.8-4.8 5.5-4.8S14 16 14.7 19"/><path d="M16 11h5M18.5 8.5v5"/>',
    'invites' => '<path d="M4 6.5h16v11H4z"/><path d="M4 7l8 6 8-6"/>',
    'dot' => '<circle cx="12" cy="12" r="3"/>',
  ];
  $collapsed = request()->cookie('sidebar_collapsed') === '1';
@endphp

<div class="sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

<aside id="app-sidebar" class="sidebar" aria-label="Section navigation">
  <nav class="sidebar__nav">
    @foreach($nav['sections'] ?? [] as $section)
      <div class="sidebar__section">
        <p class="sidebar__section-label">{{ $section['label'] }}</p>
        <ul class="sidebar__list">
          @foreach($section['items'] as $item)
            @if($item['url'])
              <li>
                <a href="{{ $item['url'] }}"
                   class="sidebar__link {{ $item['active'] ? 'active' : '' }} {{ !empty($item['highlight']) ? 'sidebar__link--cta' : '' }}"
                   title="{{ $item['label'] }}">
                  <span class="sidebar__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$item['icon']] ?? $icons['dot'] !!}</svg>
                  </span>
                  <span class="sidebar__label">{{ $item['label'] }}</span>
                </a>
              </li>
            @endif
          @endforeach
        </ul>
      </div>
    @endforeach
  </nav>

  <div class="sidebar__footer">
    @if($nav['account'] ?? null)
      <a href="{{ $nav['account']['url'] }}"
         class="sidebar__link {{ request()->routeIs('account.*') ? 'active' : '' }}"
         title="{{ $nav['account']['label'] }}">
        <span class="sidebar__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icons['account'] !!}</svg>
        </span>
        <span class="sidebar__label">{{ $nav['account']['label'] }}</span>
      </a>
    @endif

    <button type="button" class="sidebar-toggle sidebar-toggle--desktop" aria-label="Collapse sidebar" onclick="window.toggleSidebarCollapse()">
      <span class="sidebar__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5 8 12l7 7"/></svg>
      </span>
      <span class="sidebar__label">Collapse</span>
    </button>
  </div>
</aside>

<script>
  window.toggleSidebarCollapse = function () {
    var collapsed = document.body.classList.toggle('sidebar-collapsed');
    document.cookie = 'sidebar_collapsed=' + (collapsed ? '1' : '0') + ';path=/;max-age=31536000;SameSite=Lax';
  };
</script>
