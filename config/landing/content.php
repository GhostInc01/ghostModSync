<?php

declare(strict_types=1);

return [
    // SECTION 1: ANNOUNCEMENT BAR
    'announcement' => [
        'enabled' => true,
        // TODO: Replace announcement text when the official beta launch date is confirmed.
        'badge' => 'Launch Update',
        'text' => 'GhostModsSync is preparing for launch. Join the community and stay updated.',
        'discord_cta' => 'Join Discord',
        'dismiss_label' => 'Dismiss announcement',
        'storage_key' => 'gms_announcement_dismissed_v1',
    ],

    // SECTION 2: STICKY NAVIGATION
    'navigation' => [
        'login_cta' => 'Login',
        'signup_cta' => 'Sign Up',
        'dashboard_cta' => 'Go to Dashboard',
        'mobile_open_label' => 'Open navigation menu',
        'mobile_close_label' => 'Close navigation menu',
        'skip_to_content' => 'Skip to main content',
    ],

    // SECTION 3: HERO
    'hero' => [
        'eyebrow' => 'Ghost Inc Gaming • VTC & Community Mod Synchronisation',
        'headline' => 'One source. Every mod. Always in sync.',
        'supporting_headline' => 'Stop wasting hours fixing mismatched mods. GhostModsSync helps your VTC keep everyone on the same mods, the same versions and the same loadout.',
        'supporting_copy' => "Manage your group's private mod library from one central platform. Approve your members, let them connect through the desktop client, and keep your community synchronised.",
        'primary_cta' => 'Create Your VTC',
        'secondary_cta' => 'Login',
        'authenticated_secondary_cta' => 'Go to Dashboard',
        'indicators' => [
            'Centralised mod management',
            'Private group libraries',
            'Desktop synchronisation',
            'Admin-controlled membership',
        ],
        'architecture_pillars' => [
            [
                'code' => '01 / WEB PLATFORM',
                'title' => 'GhostModsSync Web Platform',
                'summary' => 'VTC leaders register their group, upload private mods, manage versions, and approve or reject member access.',
            ],
            [
                'code' => '02 / SYNC INFRASTRUCTURE',
                'title' => 'Synchronisation Infrastructure',
                'summary' => 'Coordinates authentication, group isolation, mod manifests, version metadata, and authorised download permissions.',
            ],
            [
                'code' => '03 / DESKTOP CLIENT',
                'title' => 'GhostModsSync Desktop Client',
                'summary' => 'Authenticates approved members, checks local files against expected versions, and syncs mods to the local mod folder.',
            ],
        ],
    ],

    // SECTION 4: THE PROBLEM
    'problem' => [
        'eyebrow' => 'The Pre-Session Frustration',
        'heading' => 'Still fixing mods before every session?',
        'intro' => 'Every convoy leader and community admin knows the frustration of spending pre-departure time troubleshooting broken archives and mismatched folders.',
        'items' => [
            [
                'title' => 'Version mismatches',
                'description' => 'Members use different versions of the same modification.',
                'icon' => 'version-mismatch',
            ],
            [
                'title' => 'Manual downloads',
                'description' => 'Everyone spends time downloading and replacing files.',
                'icon' => 'manual-downloads',
            ],
            [
                'title' => 'Load-order problems',
                'description' => 'Members accidentally configure different mod arrangements.',
                'icon' => 'load-order',
            ],
            [
                'title' => 'Group inconsistency',
                'description' => 'Leaders struggle to ensure everyone has the correct modifications.',
                'icon' => 'group-inconsistency',
            ],
            [
                'title' => 'Wasted gaming time',
                'description' => 'Instead of enjoying the game, members spend time troubleshooting.',
                'icon' => 'wasted-time',
            ],
        ],
        'closing' => 'Your group should spend its time gaming together, not fixing the same mod problems over and over.',
    ],

    // SECTION 5: HOW IT WORKS
    'how_it_works' => [
        'eyebrow' => 'End-to-End Synchronisation Workflow',
        'heading' => 'One central library. Four simple steps.',
        'subtitle' => 'A seamless workflow connecting VTC leadership on the Web Platform with approved drivers using the Desktop Client.',
        'flow_caption' => 'VTC Leader (Web Platform) → GhostModsSync Central Source → Approved Members (Desktop Client)',
        'steps' => [
            [
                'number' => 'STEP 1',
                'title' => 'REGISTER YOUR VTC',
                'environment' => 'Web Platform',
                'description' => 'The group leader creates an account and registers their VTC on GhostModsSync.',
                'image_key' => 'step_1',
            ],
            [
                'number' => 'STEP 2',
                'title' => 'LEADER UPLOADS MODS',
                'environment' => 'Web Platform',
                'description' => "The authorised leader uploads, organises and manages the group's private modifications.",
                'image_key' => 'step_2',
            ],
            [
                'number' => 'STEP 3',
                'title' => 'APPROVE YOUR MEMBERS',
                'environment' => 'Web Platform (Leader Only)',
                'description' => 'The VTC Admin/Main Leader approves members who are allowed to access the group.',
                'image_key' => 'step_3',
            ],
            [
                'number' => 'STEP 4',
                'title' => 'MEMBERS SYNCHRONISE',
                'environment' => 'Desktop Client',
                'description' => 'Approved members install the GhostModsSync desktop client, log in and synchronise their authorised mods into the configured mod manager folder.',
                'image_key' => 'step_4',
            ],
        ],
    ],

    // SECTION 6: FEATURES
    'features' => [
        'eyebrow' => 'Platform Capabilities',
        'heading' => 'Everything your VTC needs to stay synchronised.',
        'subtitle' => 'Purpose-built infrastructure for Virtual Trucking Companies and organised simulation communities.',
        'cards' => [
            [
                'title' => 'Central Mod Management',
                'description' => "Manage your group's modifications from one central location.",
                'icon' => 'central-hub',
            ],
            [
                'title' => 'Private Group Libraries',
                'description' => 'Keep private group uploads accessible only to authorised members of the relevant group.',
                'icon' => 'shield-lock',
            ],
            [
                'title' => 'Steam + Private Mod Support',
                'description' => 'Bring supported Steam modifications and private group modifications together through the client.',
                'icon' => 'hybrid-sources',
            ],
            [
                'title' => 'Admin-Controlled Membership',
                'description' => 'The authorised VTC leader controls who joins the group and accesses its library.',
                'icon' => 'user-check',
            ],
            [
                'title' => 'Desktop Auto-Synchronisation',
                'description' => 'Let the desktop client handle supported mod downloads and file synchronisation.',
                'icon' => 'desktop-sync',
            ],
            [
                'title' => 'Version Consistency',
                'description' => 'Use centralised mod metadata and version information to help members maintain matching files.',
                'icon' => 'version-pin',
            ],
            [
                'title' => 'Group Isolation',
                'description' => "Keep each VTC's private library separate from other groups.",
                'icon' => 'tenant-isolation',
            ],
            [
                'title' => 'Easier Mod Management',
                'description' => 'Reduce repetitive manual downloading, replacing and organising of modifications.',
                'icon' => 'sparkles-folder',
            ],
        ],
    ],

    // SECTION 7: FOR VTC LEADERS AND MEMBERS
    'roles' => [
        'eyebrow' => 'Tailored For Every Role',
        'section_heading' => 'Built for VTC Leaders and Approved Members',
        'leader_panel' => [
            'id' => 'for-leaders',
            'badge' => 'PANEL A • FOR VTC LEADERS',
            'heading' => 'Your VTC. Your Mods. Your Control.',
            'intro' => 'Only the authorised VTC Admin/Main Leader controls membership approval and private mod library access.',
            'points' => [
                'Register and manage your VTC.',
                'Upload private modifications.',
                'Organise your group library.',
                'Approve or reject membership.',
                "Manage the group's mod collection.",
                'Maintain control over private group content.',
                "Pay for the group's subscription when pricing becomes available.",
            ],
            'cta' => 'Create Your VTC',
        ],
        'member_panel' => [
            'id' => 'for-members',
            'badge' => 'PANEL B • FOR MEMBERS',
            'heading' => 'Less Setup. More Gaming.',
            'intro' => 'Once approved by your VTC leader, connect the desktop client and keep your local mod directory synchronised.',
            'points' => [
                'Join through an invitation or the supported group registration process.',
                'Wait for leader approval.',
                'Install the GhostModsSync client.',
                'Log in using your account.',
                'Connect to your authorised group.',
                'Synchronise the required mods.',
                'Spend less time manually managing modifications.',
            ],
            'cta' => 'Create Your Account',
            'authenticated_cta' => 'Go to Dashboard',
        ],
    ],

    // SECTION 8: DOWNLOAD GHOSTMODSYNC CLIENT
    'client_download' => [
        'eyebrow' => 'GhostModsSync Desktop Software',
        'heading' => 'Your mods. Automatically in sync.',
        'description' => 'The GhostModsSync desktop client connects the central web platform directly to each approved member’s local computer—verifying existing files, avoiding unnecessary re-downloads, and placing authorised mods straight into your configured mod manager directory.',
        'button_label' => 'Download Client',
        'coming_soon_badge' => 'Desktop client coming soon.',
        'required_note' => 'An installed GhostModsSync client is required for members to synchronise their group modifications.',
        'specs' => [
            [
                'label' => 'Current Client Version',
                'value' => 'Placeholder — Official version number will be published at beta release.',
            ],
            [
                'label' => 'Supported Operating Systems',
                'value' => 'Placeholder — Windows 10 & Windows 11 (64-bit) desktop environments.',
            ],
            [
                'label' => 'Minimum System Requirements',
                'value' => 'Placeholder — 64-bit Windows OS, broadband connection, and local game mod directory storage.',
            ],
            [
                'label' => 'Installation Instructions',
                'value' => 'Placeholder — Run the signed Windows installer, sign in with your GhostModsSync account, and select your local ETS2/ATS mod folder.',
            ],
            [
                'label' => 'Release Notes',
                'value' => 'Placeholder — Full changelog and SHA-256 verification hashes will accompany the official client build.',
            ],
        ],
    ],

    // SECTION 9: PRICING – COMING SOON
    'pricing' => [
        'eyebrow' => 'Transparent VTC Licensing',
        'heading' => 'Simple pricing. Built around your VTC.',
        'status_badge' => 'Pricing Coming Soon',
        'description' => 'GhostModsSync is designed around a straightforward group-level subscription model so your members can join convoys without individual paywalls.',
        'model_points' => [
            'Subscription-based platform model.',
            'Subscription is paid by the VTC/group leader.',
            'Subscription applies per registered VTC/group.',
            'Members do not individually purchase subscriptions under the intended model.',
            'Pricing is still being finalised.',
        ],
        'notify_form' => [
            'heading' => 'Be the first to know when GhostModsSync pricing goes live.',
            'email_label' => 'Email address',
            'email_placeholder' => 'leader@your-vtc.com',
            'button' => 'Notify Me',
            'disclaimer' => 'Placeholder notification registration: Your interest is validated locally while the official mailing-list integration is being finalised.',
            'success_message' => 'Thank you! Your email address has been validated. Please note: the live mailing-list backend is not yet connected (placeholder handler active).',
            'error_invalid_email' => 'Please enter a valid email address so we can notify you when pricing is announced.',
        ],
    ],

    // SECTION 10: TRUST AND COMMUNITY
    'trust_community' => [
        'eyebrow' => 'Ecosystem & Community',
        'heading' => 'Built for communities that game together.',
        'subtitle' => 'We believe in transparency. The sections below are reserved for verified VTC partners, real community feedback, and live platform metrics as GhostModsSync rolls out.',
        'placeholders' => [
            [
                'category' => 'Partner VTC Logos',
                'status' => 'Placeholder — Reserved for Official Partner VTCs',
                'description' => 'Verified Virtual Trucking Company emblems and launch partner badges will be displayed here with partner permission.',
            ],
            [
                'category' => 'Community Testimonials',
                'status' => 'Placeholder — Reserved for Verified Leader & Driver Reviews',
                'description' => 'Authentic feedback from VTC founders, convoy dispatchers, and members will appear here following beta testing.',
            ],
            [
                'category' => 'Platform Statistics',
                'status' => 'Placeholder — Reserved for Live Telemetry',
                'description' => 'Real metrics for registered VTCs, synchronised mod packages, and verified desktop sessions will be published post-launch.',
            ],
            [
                'category' => 'Featured Gaming Groups',
                'status' => 'Placeholder — Reserved for Community Spotlights',
                'description' => 'Featured ETS2 and ATS Virtual Trucking Communities using GhostModsSync will be showcased in this slot.',
            ],
        ],
        'discord' => [
            'heading' => 'Join the GhostModsSync community.',
            'copy' => 'Connect with other VTC leaders, members and GhostModsSync supporters.',
            'button' => 'Join Our Discord',
            'todo_note' => 'Official Discord invite link will be connected prior to launch.',
        ],
        'video_demo' => [
            'heading' => 'Product Demonstration & Trailer',
            'badge' => 'Official Demo Video Coming Soon',
            'description' => 'An end-to-end walkthrough of VTC registration, private mod uploads, member approval, and desktop synchronisation will be embedded here.',
        ],
    ],

    // SECTION 11: FAQ
    'faq' => [
        'eyebrow' => 'Frequently Asked Questions',
        'heading' => 'Everything you need to know about GhostModsSync',
        'subtitle' => 'Clear answers about how VTC leadership, member approvals, private mod libraries, and the desktop client work.',
        'items' => [
            [
                'question' => 'What is GhostModsSync?',
                'answer' => 'GhostModsSync is a platform designed to help VTCs and gaming groups manage and synchronise their modifications through a central source.',
            ],
            [
                'question' => 'Who can create a VTC?',
                'answer' => "A user can register a group through the platform's supported registration process.",
            ],
            [
                'question' => 'Who approves new members?',
                'answer' => 'Only the authorised VTC Admin/Main Leader approves or rejects members.',
            ],
            [
                'question' => "Who uploads the group's private mods?",
                'answer' => 'The authorised group leader manages and uploads private modifications.',
            ],
            [
                'question' => 'Can other VTCs access our private mods?',
                'answer' => 'No. Private group libraries are intended to remain isolated and accessible only to authorised members.',
            ],
            [
                'question' => 'Do I need to install the desktop client?',
                'answer' => 'Yes. Members need the GhostModsSync desktop client to synchronise modifications to their local mod manager folder.',
            ],
            [
                'question' => 'Does GhostModsSync support Steam mods?',
                'answer' => 'The platform is designed to support synchronisation of supported Steam modifications alongside private group modifications. Exact compatibility depends on the supported game and mod types.',
            ],
            [
                'question' => 'Who pays for the subscription?',
                'answer' => 'The subscription is intended to be paid by the VTC/group leader rather than individual members.',
            ],
            [
                'question' => 'How much does GhostModsSync cost?',
                'answer' => 'Pricing has not yet been finalised. Official pricing will be announced when available.',
            ],
            [
                'question' => 'What happens when a mod is updated?',
                'answer' => 'The central platform maintains mod information and versions. The desktop client is intended to identify relevant changes and synchronise supported updates.',
            ],
            [
                'question' => 'Can I belong to multiple VTCs?',
                'answer' => 'Yes. The GhostModsSync platform architecture supports belonging to multiple groups with independent membership status and roles in each VTC.',
            ],
            [
                'question' => 'Which games are supported?',
                'answer' => 'GhostModsSync officially supports Euro Truck Simulator 2 (ETS2) and American Truck Simulator (ATS).',
            ],
        ],
    ],

    // SECTION 12: FINAL CALL TO ACTION
    'final_cta' => [
        'eyebrow' => 'Start Your VTC Synchronisation Hub',
        'heading' => 'Ready to get your VTC in sync?',
        'supporting_text' => "Bring your group's mod management together and spend more time doing what you enjoy.",
        'primary_cta' => 'Create Your VTC',
        'secondary_cta' => 'Login',
        'authenticated_secondary_cta' => 'Go to Dashboard',
    ],

    // SOCIAL SHARE
    'social_share' => [
        'label' => 'Share GhostModsSync with your VTC:',
        'facebook_label' => 'Share on Facebook',
        'x_label' => 'Share on X',
        'native_label' => 'Share',
        'copy_label' => 'Copy Link',
        'copied_feedback' => 'Link copied to clipboard!',
    ],

    // SECTION 13: FOOTER
    'footer' => [
        'description' => 'GhostModsSync by Ghost Inc Gaming (a JNK Designs company) is the central mod management and desktop synchronisation platform built for Virtual Trucking Companies and simulation communities.',
        'platform_heading' => 'Platform',
        'legal_heading' => 'Legal & Support',
        'community_heading' => 'Community & Socials',
        'copyright_template' => '© :year Ghost Inc Gaming. A JNK Designs company. All rights reserved.',
    ],

    // COOKIE CONSENT
    'cookie_consent' => [
        'title' => 'Cookie & Privacy Preferences',
        'description' => 'GhostModsSync currently uses only strictly necessary session and CSRF security cookies required for authentication. No non-essential analytics or advertising tracking scripts are active. You may save your preference for future optional cookies below.',
        'accept_all' => 'Accept Optional Cookies',
        'essential_only' => 'Essential Cookies Only',
        'privacy_link_label' => 'Privacy Policy',
        'storage_key' => 'gms_cookie_consent_v1',
    ],
];
