<section class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Early feedback</div>
    @foreach($testimonials as $t)
      <div class="vx-quote">
        <p>&ldquo;{{ $t['quote'] }}&rdquo;</p>
        <cite>{{ $t['name'] }} — {{ $t['role'] }}</cite>
      </div>
    @endforeach
  </div>
</section>
