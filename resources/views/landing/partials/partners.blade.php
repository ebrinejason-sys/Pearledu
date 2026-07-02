<div id="partners" style="padding:26px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
  <div class="vx-wrap" style="margin-bottom:10px">
    <div class="vx-eyebrow" style="text-align:center">Working with and supported by</div>
  </div>
  <div class="vx-marquee">
    <div class="vx-marquee-track">
      @for($i = 0; $i < 2; $i++)
        <div class="vx-marquee-group">
          @foreach($partners as $partner)
            @if($partner['logo'])
              <img src="{{ asset('images/voxsign/'.$partner['logo']) }}" alt="{{ $partner['name'] }}">
            @else
              <span class="vx-partner-text">{{ $partner['name'] }}</span>
            @endif
          @endforeach
        </div>
      @endfor
    </div>
  </div>
</div>
<style>
  .vx-marquee{overflow:hidden;width:100%}
  .vx-marquee-track{display:flex;align-items:center;width:max-content;animation:vx-scroll 32s linear infinite}
  .vx-marquee-group{display:flex;align-items:center;gap:44px}
  .vx-marquee-track img{height:38px;width:auto;opacity:.8;filter:grayscale(1);transition:opacity .2s,filter .2s;flex:0 0 auto}
  .vx-marquee:hover .vx-marquee-track{animation-play-state:paused}
  .vx-marquee-track img:hover{opacity:1;filter:none}
  @keyframes vx-scroll{from{transform:translateX(0)}to{transform:translateX(-50%)}}
  @media(prefers-reduced-motion:reduce){
    .vx-marquee-track{animation:none;flex-wrap:wrap;justify-content:center}
    .vx-marquee-track .vx-marquee-group:nth-child(2){display:none}
  }
</style>
