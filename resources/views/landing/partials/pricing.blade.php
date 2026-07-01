<section id="pricing" class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Pricing</div>
    <table class="vx-table">
      <thead>
        <tr><th>Plan</th><th>Cost</th><th>Key features</th></tr>
      </thead>
      <tbody>
        @foreach($pricingTiers as $tier)
          <tr>
            <td>{{ $tier['name'] }}</td>
            <td>{{ $tier['price'] }}</td>
            <td>{{ implode(', ', $tier['features']) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>
