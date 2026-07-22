<?php
namespace App\Services\Theme;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;

class ThemeManager {
    public function __construct(private TenantContext $context) {}

    /** user preference > school theme > default. */
    public function activeKey(): string {
        $themes = config('themes.themes', []);
        $user = Auth::user();
        if ($user && $user->preferred_theme && isset($themes[$user->preferred_theme])) {
            return $user->preferred_theme;
        }
        $school = $this->context->school();
        if ($school && isset($themes[$school->theme])) {
            return $school->theme;
        }
        return config('themes.default', 'pearledu');
    }

    public function definition(): array {
        return config('themes.themes.'.$this->activeKey(), []);
    }

    public function tokens(): array {
        return $this->definition()['tokens'] ?? [];
    }

    public function fontUrl(): ?string {
        $url = $this->definition()['font_url'] ?? null;
        return is_string($url) && $url !== '' ? $url : null;
    }

    /** Browser chrome / PWA theme-color (uses brand). */
    public function themeColor(): string {
        return $this->tokens()['brand'] ?? '#053F5C';
    }

    /** Render the active palette as a CSS :root variable block. */
    public function cssVariables(): string {
        $css = ':root{';
        foreach ($this->tokens() as $k => $v) {
            $css .= '--'.$k.':'.$v.';';
        }
        return $css.'}';
    }
}
