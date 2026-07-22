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
    'years' => '<rect x="4" y="5" width="16" height="15" rx="1.5"/><path d="M8 3v4M16 3v4M4 10h16"/>',
    'subjects' => '<path d="M5 5h14v14H5z"/><path d="M8 9h8M8 12h8M8 15h5"/>',
    'teaching' => '<path d="M4 19V7l8-3 8 3v12"/><path d="M12 10v9"/>',
    'enrollments' => '<path d="M8 7h11M8 12h11M8 17h7"/><circle cx="5" cy="7" r="1.2"/><circle cx="5" cy="12" r="1.2"/><circle cx="5" cy="17" r="1.2"/>',
    'attendance' => '<path d="M9 12l2.2 2.2L16 9.5"/><rect x="3.5" y="4" width="17" height="16" rx="2"/>',
    'assessment' => '<path d="M8 4h8l3 3v13H5V4h3z"/><path d="M8 4v3h3"/>',
    'broadsheet' => '<path d="M4 6h16M4 12h16M4 18h10"/>',
    'promotions' => '<path d="M7 17V7l5-3 5 3v10"/><path d="M12 10v7"/>',
    'timetable' => '<rect x="3.5" y="5" width="17" height="15" rx="1.5"/><path d="M3.5 9.5h17M9 5v15M15 5v15"/>',
    'fees' => '<circle cx="12" cy="12" r="8"/><path d="M12 7v10M9.5 9.5c.5-1 1.5-1.5 2.5-1.5s2 .6 2 1.8-1 1.7-2.5 2.2-2.5.9-2.5 2.2 1 1.8 2.5 1.8 2-.5 2.5-1.5"/>',
    'announcements' => '<path d="M4 10v4l3 1v3l4-3h2l5 3V5l-5 3H7z"/>',
    'admissions' => '<path d="M12 3v14M7 8l5-5 5 5"/><path d="M5 19h14"/>',
    'emis' => '<path d="M4 18V6h6v12H4zM14 18V9h6v9"/>',
    'lms' => '<path d="M4 6h16v12H4z"/><path d="M8 10h8M8 14h5"/>',
    'cbt' => '<rect x="4" y="5" width="16" height="12" rx="1.5"/><path d="M9 20h6M12 17v3"/>',
    'library' => '<path d="M5 5h4v14H5zM11 5h4v14h-4zM17 5h2v14h-2z"/>',
    'inventory' => '<path d="M4 8l8-4 8 4v10l-8 4-8-4V8z"/><path d="M12 12v10M4 8l8 4 8-4"/>',
    'transport' => '<path d="M4 15h13l3-5H7l-3 5z"/><circle cx="8" cy="16.5" r="1.5"/><circle cx="16" cy="16.5" r="1.5"/>',
    'hostel' => '<path d="M3 20V10l9-6 9 6v10"/><path d="M9 20v-6h6v6"/>',
    'hr' => '<circle cx="12" cy="8" r="3"/><path d="M5 20c1-4 3.5-6 7-6s6 2 7 6"/>',
    'clinic' => '<path d="M9 3h6v5h5v6h-5v5H9v-5H4V8h5V3z"/>',
    'helpdesk' => '<circle cx="12" cy="12" r="8.5"/><path d="M9.5 9.5a2.5 2.5 0 0 1 5 1c0 1.5-2.5 2-2.5 3.5"/><circle cx="12" cy="17" r=".8"/>',
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
