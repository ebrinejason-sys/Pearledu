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
        if ($user && $user->preferred_theme && isset($themes[$user->preferred_theme])) return $user->preferred_theme;
        $school = $this->context->school();
        if ($school && isset($themes[$school->theme])) return $school->theme;
        return config('themes.default', 'pearledu');
    }

    public function tokens(): array {
        $key = $this->activeKey();
        return config("themes.themes.$key.tokens", []);
    }

    /** Render the active palette as a CSS :root variable block. */
    public function cssVariables(): string {
        $css = ':root{';
        foreach ($this->tokens() as $k => $v) $css .= '--'.$k.':'.$v.';';
        return $css.'}';
    }
}
