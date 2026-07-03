<section id="contact" class="vx-section vx-band" style="border-bottom:0">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">Get in touch</div>
    <h2 class="vx-h2" style="color:#fff;margin-bottom:12px">Talk to us.</h2>
    <p class="vx-lead" style="margin-bottom:24px">
      +256 770 680769 &middot; voxsign3@gmail.com &middot; Makerere Innovation and Incubation Centre
    </p>
    <form method="post" action="{{ route('contact') }}" style="max-width:480px;margin:0 auto;background:#fff;border-radius:20px;padding:26px">
      @csrf
      <div style="position:absolute;left:-9999px"><input name="website" tabindex="-1" autocomplete="off"></div>
      <label class="vx-label">Name</label>
      <input class="vx-input" name="name" required>
      @error('name')<div class="vx-err">{{ $message }}</div>@enderror
      <label class="vx-label">Email</label>
      <input class="vx-input" name="email" type="email" required>
      @error('email')<div class="vx-err">{{ $message }}</div>@enderror
      <label class="vx-label">Message</label>
      <textarea class="vx-input" name="message" rows="4" required></textarea>
      @error('message')<div class="vx-err">{{ $message }}</div>@enderror
      <button class="vx-btn" type="submit" style="width:100%;justify-content:center">Send message</button>
    </form>
  </div>
</section>
