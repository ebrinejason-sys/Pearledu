<?php

namespace Tests\Unit;

use Tests\TestCase;

class ThemePaletteContractTest extends TestCase
{
    public function test_every_theme_defines_the_full_token_contract(): void
    {
        $required = config('themes.required_tokens');
        $themes = config('themes.themes');

        $this->assertNotEmpty($required);
        $this->assertNotEmpty($themes);

        foreach ($themes as $key => $theme) {
            $this->assertArrayHasKey('label', $theme, $key);
            $this->assertArrayHasKey('description', $theme, $key);
            $this->assertArrayHasKey('font_url', $theme, $key);
            $this->assertArrayHasKey('tokens', $theme, $key);

            foreach ($required as $token) {
                $this->assertArrayHasKey($token, $theme['tokens'], "Theme [$key] missing token [$token]");
                $this->assertNotSame('', $theme['tokens'][$token], "Theme [$key] empty token [$token]");
            }
        }
    }

    public function test_default_theme_exists(): void
    {
        $default = config('themes.default');
        $this->assertArrayHasKey($default, config('themes.themes'));
    }
}
