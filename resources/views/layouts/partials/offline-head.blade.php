<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="PearlEdu">
@auth
<meta name="offline-user" content="{{ auth()->id() }}">
<meta name="offline-school" content="{{ session(\App\Services\Tenancy\TenantContext::SESSION_SCHOOL_ID) }}">
@endauth
