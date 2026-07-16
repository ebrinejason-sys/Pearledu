@php($turnstileSiteKey = app(\App\Services\Security\TurnstileVerifier::class)->siteKey())
@if($turnstileSiteKey)
  <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" style="margin:8px 0 14px"></div>
  @error('cf-turnstile-response')<div class="{{ $errorClass ?? 'vx-err' }}">{{ $message }}</div>@enderror
  @if(empty($GLOBALS['vx_turnstile_script']))
    @php($GLOBALS['vx_turnstile_script'] = true)
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
  @endif
@endif
