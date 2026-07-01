<section id="partners" class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Partners</div>
    <div class="vx-partners">
      @foreach($partners as $partner)
        @if($partner['logo'])
          <img src="{{ asset('images/voxsign/'.$partner['logo']) }}" alt="{{ $partner['name'] }}">
        @else
          <span class="vx-partner-text">{{ $partner['name'] }}</span>
        @endif
      @endforeach
    </div>
  </div>
</section>
