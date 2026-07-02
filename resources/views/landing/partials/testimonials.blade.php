<section class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">Early voices</div>
    <h2 class="vx-h2 vx-sec-head">What future users are saying.</h2>
    @foreach($testimonials as $t)
      <div class="vx-quote">
        <p>&ldquo;{{ $t['quote'] }}&rdquo;</p>
        <cite>{{ $t['name'] }} — {{ $t['role'] }}</cite>
      </div>
    @endforeach
  </div>
</section>
