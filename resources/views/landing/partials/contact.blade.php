<section id="contact" class="vx-section vx-band" style="border-bottom:0">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">Get in touch</div>
    <h2 class="vx-h2" style="color:#fff;margin-bottom:12px">Talk to us.</h2>
    <p class="vx-lead" style="margin-bottom:24px">
      +256 770 680769 &middot; info@voxsign.co.ug
    </p>
    <form method="post" action="{{ route('contact') }}" class="vx-form-card">
      @csrf
      <div style="position:absolute;left:-9999px"><input name="website" tabindex="-1" autocomplete="off"></div>
      <label class="vx-label">Name</label>
      <input class="vx-input" name="name" required placeholder="Your name">
      @error('name')<div class="vx-err">{{ $message }}</div>@enderror
      <label class="vx-label">Email</label>
      <input class="vx-input" name="email" type="email" required placeholder="you@example.com">
      @error('email')<div class="vx-err">{{ $message }}</div>@enderror
      <label class="vx-label">Message</label>
      <textarea class="vx-input" name="message" rows="4" required placeholder="How can we help?"></textarea>
      @error('message')<div class="vx-err">{{ $message }}</div>@enderror
      <button class="vx-btn-grad" type="submit" style="width:100%;justify-content:center">Send message</button>
    </form>
  </div>
</section>
