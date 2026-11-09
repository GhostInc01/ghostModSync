<?php

declare(strict_types=1);

return [
    'header' => [
        ['label' => 'Features', 'href' => '#features'],
        ['label' => 'How It Works', 'href' => '#how-it-works'],
        ['label' => 'For Leaders', 'href' => '#for-leaders'],
        ['label' => 'For Members', 'href' => '#for-members'],
        ['label' => 'Pricing', 'href' => '#pricing'],
        ['label' => 'FAQ', 'href' => '#faq'],
    ],

    'footer_platform' => [
        ['label' => 'Features', 'href' => '#features'],
        ['label' => 'How It Works', 'href' => '#how-it-works'],
        ['label' => 'For Leaders', 'href' => '#for-leaders'],
        ['label' => 'For Members', 'href' => '#for-members'],
        ['label' => 'Desktop Client', 'href' => '#download-client'],
        ['label' => 'Pricing', 'href' => '#pricing'],
        ['label' => 'FAQ', 'href' => '#faq'],
    ],

    'footer_legal' => [
        // TODO: Implement and connect the official legal documents before public launch.
        ['label' => 'Privacy Policy', 'route' => 'legal.privacy'],
        ['label' => 'Terms and Conditions', 'route' => 'legal.terms'],
        ['label' => 'Contact', 'route' => 'legal.contact'],
    ],
];
