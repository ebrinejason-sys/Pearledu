<style>
  .password-field{position:relative;display:block;max-width:100%}
  .password-field input{width:100%;padding-right:4.5rem;box-sizing:border-box}
  .password-field__toggle{
    position:absolute;right:8px;top:50%;transform:translateY(-50%);
    border:0;background:transparent;color:var(--brand,#053F5C);font:inherit;font-size:12px;font-weight:700;
    cursor:pointer;padding:4px 6px;border-radius:6px
  }
  .password-field__toggle:hover{background:color-mix(in srgb, var(--brand,#053F5C) 10%, transparent)}
  .vx-auth-card .password-field__toggle{color:var(--sidebar-ink,#9FE7F5)}
</style>
<script>
document.addEventListener('click', function (e) {
  var btn = e.target.closest('[data-password-toggle]');
  if (!btn) return;
  var wrap = btn.closest('[data-password-field]');
  var input = wrap && wrap.querySelector('input');
  if (!input) return;
  var show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.setAttribute('aria-pressed', show ? 'true' : 'false');
  btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  btn.textContent = show ? 'Hide' : 'Show';
});
</script>
