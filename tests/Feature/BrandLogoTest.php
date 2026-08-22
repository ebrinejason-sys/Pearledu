<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandLogoTest extends TestCase
{
    public function test_canonical_logo_partial_is_the_twenty_one_chord_sphere(): void
    {
        $html = view('layouts.partials.logo', [
            'height' => 28,
            'color' => '#053F5C',
            'label' => 'PearlEdu',
        ])->render();

        $this->assertStringContainsString('viewBox="30 30 340 340"', $html);
        $this->assertStringContainsString('class="vx-logo"', $html);
        $this->assertSame(21, substr_count($html, '<path '));
        $this->assertStringNotContainsString('<line', $html);
        $this->assertStringNotContainsString('data-index', $html);
    }

    public function test_indexed_logo_exposes_scanline_indexes_for_the_preloader(): void
    {
        $html = view('layouts.partials.logo', [
            'height' => 160,
            'color' => 'currentColor',
            'label' => 'PearlEdu',
            'indexed' => true,
        ])->render();

        $this->assertStringContainsString('data-index="-10"', $html);
        $this->assertStringContainsString('data-index="0"', $html);
        $this->assertStringContainsString('data-index="10"', $html);
        $this->assertSame(21, substr_count($html, 'data-index='));
    }

    public function test_static_brand_assets_are_the_same_sphere_mark(): void
    {
        $logoSvg = (string) file_get_contents(public_path('images/brand/logo.svg'));
        $faviconSvg = (string) file_get_contents(public_path('favicon.svg'));

        $this->assertStringContainsString('viewBox="30 30 340 340"', $logoSvg);
        $this->assertStringContainsString('viewBox="30 30 340 340"', $faviconSvg);
        $this->assertSame(21, substr_count($logoSvg, '<path '));
        $this->assertSame(21, substr_count($faviconSvg, '<path '));
        $this->assertStringContainsString('M50.00,194.50', $logoSvg);
        $this->assertStringContainsString('M50.00,194.50', $faviconSvg);
    }

    public function test_email_layout_inlines_the_sphere_mark(): void
    {
        $html = view('emails.layout', ['subject' => 'Hello'])->render();

        $this->assertStringContainsString('viewBox="30 30 340 340"', $html);
        $this->assertStringContainsString('class="vx-logo"', $html);
        $this->assertSame(21, substr_count($html, '<path '));
    }

    public function test_sidebar_brand_uses_sphere_and_voxsign_tagline(): void
    {
        $html = view('layouts.partials.brand', ['showTagline' => true])->render();

        $this->assertStringContainsString('viewBox="30 30 340 340"', $html);
        $this->assertStringContainsString('developed by Voxsign Technologies', $html);
        $this->assertStringContainsString('class="vx-logo"', $html);
        $this->assertSame(21, substr_count($html, '<path '));
        $this->assertStringNotContainsString('voxsign-logo.svg', $html);
    }

    public function test_brand_outside_sidebar_omits_the_tagline(): void
    {
        $html = view('layouts.partials.brand')->render();

        $this->assertStringContainsString('Pearl', $html);
        $this->assertStringNotContainsString('developed by Voxsign Technologies', $html);
    }
}
