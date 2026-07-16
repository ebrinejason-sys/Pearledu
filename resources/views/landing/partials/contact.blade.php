<section id="contact" class="vx-section vx-contact">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">Get in touch</div>
    <h2 class="vx-h2" style="margin-bottom:12px">Talk to us.</h2>
    <p class="vx-lead" style="margin-bottom:24px">
      +256 770 680769 &middot; info@voxsign.co.ug
    </p>
    <form method="post" action="{{ route('contact') }}" class="vx-form-card">
      @csrf
      <div style="position:absolute;left:-9999px" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
      <label class="vx-label" for="contact-name">Name</label>
      <input id="contact-name" class="vx-input" name="name" required placeholder="Your name" autocomplete="name" value="{{ old('name') }}">
      @error('name')<div class="vx-err">{{ $message }}</div>@enderror
      <label class="vx-label" for="contact-email">Email</label>
      <input id="contact-email" class="vx-input" name="email" type="email" required placeholder="you@example.com" autocomplete="email" value="{{ old('email') }}">
      @error('email')<div class="vx-err">{{ $message }}</div>@enderror
      <label class="vx-label" for="contact-message">Message</label>
      <textarea id="contact-message" class="vx-input" name="message" rows="4" required placeholder="How can we help?">{{ old('message') }}</textarea>
      @error('message')<div class="vx-err">{{ $message }}</div>@enderror
      @include('partials.turnstile', ['errorClass' => 'vx-err'])
      <button class="vx-btn-grad" type="submit" style="width:100%;justify-content:center">Send message</button>
    </form>
  </div>
</section>
