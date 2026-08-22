<style>
  .offline-banner{display:none;position:sticky;top:0;z-index:45;padding:10px 16px;text-align:center;font-size:13px;font-weight:600}
  .offline-banner:not([hidden]){display:block}
  .offline-banner[data-state="offline"]{background:var(--warning-soft, #fff4e5);color:var(--accent-ink, #7a4b00)}
  .offline-banner[data-state="queued"]{background:var(--brand-soft, #e8f4f8);color:var(--brand, #053F5C)}
  .offline-banner[data-state="error"]{background:var(--danger-soft, #fde8e8);color:var(--danger, #b42318)}
</style>
<div id="offline-banner" class="offline-banner" hidden role="status"></div>
<script src="{{ asset('js/offline-first.js') }}" defer></script>
