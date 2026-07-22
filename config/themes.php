<?php

/**
 * School white-label themes for the app / auth chrome.
 * Marketing & PearlEdu landing keep their own layout tokens (separate product surfaces).
 *
 * Token contract (all themes must define every key):
 *   brand, brand-600, brand-soft, accent, accent-soft, accent-ink,
 *   bg, surface, surface-2, ink, muted, line,
 *   sidebar, sidebar-ink, sidebar-hover, sidebar-active,
 *   on-brand, success, success-soft, warning, warning-soft, danger, danger-soft,
 *   focus, shadow, radius, radius-sm, font, font-display
 */
return [
    'default' => 'pearledu',

    'required_tokens' => [
        'brand', 'brand-600', 'brand-soft', 'accent', 'accent-soft', 'accent-ink',
        'bg', 'surface', 'surface-2', 'ink', 'muted', 'line',
        'sidebar', 'sidebar-ink', 'sidebar-hover', 'sidebar-active',
        'on-brand', 'success', 'success-soft', 'warning', 'warning-soft', 'danger', 'danger-soft',
        'focus', 'shadow', 'radius', 'radius-sm', 'font', 'font-display',
    ],

    'themes' => [
        /*
         | PearlEdu / VoxSign — deep lagoon navy + restrained amber.
         | Calm institutional MIS with a warm signal color for CTAs.
         */
        'pearledu' => [
            'label' => 'PearlEdu (VoxSign)',
            'description' => 'Deep lagoon navy with amber accents — PearlEdu’s native look.',
            'font_url' => 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
            'tokens' => [
                'brand'           => '#053F5C',
                'brand-600'       => '#032F45',
                'brand-soft'      => '#E6F3F8',
                'accent'          => '#E09A12',
                'accent-soft'     => '#FBF3E0',
                'accent-ink'      => '#3D2A05',
                'bg'              => '#F3F7F9',
                'surface'         => '#FFFFFF',
                'surface-2'       => '#EAF2F6',
                'ink'             => '#0B2C3D',
                'muted'           => '#5A7180',
                'line'            => '#C9DCE4',
                'sidebar'         => '#042F45',
                'sidebar-ink'     => '#9FE7F5',
                'sidebar-hover'   => 'rgba(255,255,255,.08)',
                'sidebar-active'  => 'rgba(255,255,255,.15)',
                'on-brand'        => '#FFFFFF',
                'success'         => '#1B7A4A',
                'success-soft'    => '#E3F5EB',
                'warning'         => '#B7791F',
                'warning-soft'    => '#FFF4DE',
                'danger'          => '#B42318',
                'danger-soft'     => '#FCEBEA',
                'focus'           => '#429EBD',
                'shadow'          => '0 12px 32px rgba(5,63,92,.10)',
                'radius'          => '12px',
                'radius-sm'       => '8px',
                'font'            => "'Plus Jakarta Sans', 'Segoe UI', sans-serif",
                'font-display'    => "'Plus Jakarta Sans', 'Segoe UI', sans-serif",
            ],
        ],

        /*
         | Moodle Boost–inspired — academic blue + pumpkin orange.
         | Familiar LMS energy without copying Boost chrome 1:1.
         */
        'moodle' => [
            'label' => 'Moodle (Boost)',
            'description' => 'Academic blue and Boost orange — familiar LMS feel for schools migrating from Moodle.',
            'font_url' => 'https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700;800&display=swap',
            'tokens' => [
                'brand'           => '#0F6CBF',
                'brand-600'       => '#0A5497',
                'brand-soft'      => '#E7F1FA',
                'accent'          => '#ED7A0E',
                'accent-soft'     => '#FFF0E0',
                'accent-ink'      => '#3A2205',
                'bg'              => '#F0F2F5',
                'surface'         => '#FFFFFF',
                'surface-2'       => '#E8ECF1',
                'ink'             => '#1D2125',
                'muted'           => '#5F6B76',
                'line'            => '#D5DBE3',
                'sidebar'         => '#1A2330',
                'sidebar-ink'     => '#C5D0DC',
                'sidebar-hover'   => 'rgba(255,255,255,.07)',
                'sidebar-active'  => 'rgba(15,108,191,.35)',
                'on-brand'        => '#FFFFFF',
                'success'         => '#1F7A3F',
                'success-soft'    => '#E4F5EA',
                'warning'         => '#C77700',
                'warning-soft'    => '#FFF3DF',
                'danger'          => '#C62828',
                'danger-soft'     => '#FDECEA',
                'focus'           => '#0F6CBF',
                'shadow'          => '0 12px 28px rgba(26,35,48,.12)',
                'radius'          => '8px',
                'radius-sm'       => '6px',
                'font'            => "'Nunito Sans', 'Segoe UI', sans-serif",
                'font-display'    => "'Nunito Sans', 'Segoe UI', sans-serif",
            ],
        ],

        /*
         | EMIS / government — formal navy + official gold.
         | Tight radii and sober neutrals for ministry / district use.
         */
        'emis' => [
            'label' => 'EMIS (Government)',
            'description' => 'Formal navy and official gold — suited to government and district EMIS deployments.',
            'font_url' => 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap',
            'tokens' => [
                'brand'           => '#0B4DA2',
                'brand-600'       => '#083C7E',
                'brand-soft'      => '#E8EEF8',
                'accent'          => '#C9A227',
                'accent-soft'     => '#F8F1D6',
                'accent-ink'      => '#3A2F08',
                'bg'              => '#EEF1F6',
                'surface'         => '#FFFFFF',
                'surface-2'       => '#E4EAF2',
                'ink'             => '#10243E',
                'muted'           => '#5B6B80',
                'line'            => '#C9D3E0',
                'sidebar'         => '#0A3D82',
                'sidebar-ink'     => '#C9DAF0',
                'sidebar-hover'   => 'rgba(255,255,255,.08)',
                'sidebar-active'  => 'rgba(255,255,255,.16)',
                'on-brand'        => '#FFFFFF',
                'success'         => '#1A6B3C',
                'success-soft'    => '#E2F3E9',
                'warning'         => '#A67C00',
                'warning-soft'    => '#F8F0D4',
                'danger'          => '#9B1C1C',
                'danger-soft'     => '#F9E8E8',
                'focus'           => '#0B4DA2',
                'shadow'          => '0 10px 24px rgba(16,36,62,.10)',
                'radius'          => '6px',
                'radius-sm'       => '4px',
                'font'            => "'IBM Plex Sans', 'Segoe UI', sans-serif",
                'font-display'    => "'IBM Plex Sans', 'Segoe UI', sans-serif",
            ],
        ],
    ],
];
