<section id="team" class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">Team</div>
    <h2 class="vx-h2 vx-sec-head">The people behind VoxSign.</h2>
    <div class="vx-grid vx-grid-team">
      @foreach($team as $member)
        <div class="vx-card">
          @if($member['photo'])
            <img src="{{ asset('images/voxsign/'.$member['photo']) }}" alt="{{ $member['name'] }}">
          @else
            <div class="vx-avatar-initials" aria-hidden="true" style="aspect-ratio:1;border-radius:12px;margin-bottom:12px;background:#E8F7FA;display:grid;place-items:center;font-family:var(--display);font-weight:800;font-size:36px;color:var(--sign)">
              {{ collect(explode(' ', $member['name']))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
            </div>
          @endif
          <h3>{{ $member['name'] }}</h3>
          <p>{{ $member['role'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>
