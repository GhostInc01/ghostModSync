<?php

declare(strict_types=1);

return [
    'name' => 'GhostModsSync',
    'company' => 'Ghost Inc Gaming',
    'parent_company' => 'JNK Designs',
    'tagline' => 'One source. Every mod. Always in sync.',
    'supporting_tagline' => 'Less time fixing mods. More time gaming together.',

    'seo' => [
        'title' => 'GhostModsSync — One Source. Every Mod. Always in Sync.',
        'description' => 'GhostModsSync is a subscription-based mod synchronisation platform for Virtual Trucking Companies (VTCs) and gaming communities. Manage your private mod library from one central source and keep members in sync.',
        'theme_color' => '#080B12',
        'robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        'og_type' => 'website',
        'twitter_card' => 'summary_large_image',
    ],

    'supported_games' => [
        [
            'code' => 'ETS2',
            'name' => 'Euro Truck Simulator 2',
            'status' => 'Confirmed Support',
        ],
        [
            'code' => 'ATS',
            'name' => 'American Truck Simulator',
            'status' => 'Confirmed Support',
        ],
    ],

    'client' => [
        'available' => false,
        // TODO: Connect the official client release/download URL when the Windows desktop installer is published.
        'download_url' => null,
        'version_placeholder' => 'Pre-Release Build (Pending Official Public Release)',
        'supported_os' => 'Windows 10 / Windows 11 (64-bit)',
        'minimum_requirements' => '64-bit Windows OS, .NET Desktop Runtime, active internet connection, and local game mod directory access.',
        'release_notes_placeholder' => 'Official release notes and SHA-256 installer checksums will be published here upon beta launch.',
    ],

    'structured_data' => [
        'organization' => [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Ghost Inc Gaming',
            'parentOrganization' => [
                '@type' => 'Organization',
                'name' => 'JNK Designs',
            ],
            'brand' => [
                '@type' => 'Brand',
                'name' => 'GhostModsSync',
                'slogan' => 'One source. Every mod. Always in sync.',
            ],
        ],
        'software_application' => [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'GhostModsSync',
            'operatingSystem' => 'Windows',
            'applicationCategory' => 'GameApplication',
            'description' => 'Centralised mod management and desktop synchronisation platform for Virtual Trucking Companies (VTCs) and organised gaming communities.',
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Ghost Inc Gaming (JNK Designs)',
            ],
        ],
    ],
];
