<section id="team" class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Team</div>
    <div class="vx-grid">
      @foreach($team as $member)
        <div class="vx-card">
          <img src="{{ asset('images/voxsign/'.$member['photo']) }}" alt="{{ $member['name'] }}">
          <h3>{{ $member['name'] }}</h3>
          <p>{{ $member['role'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>
