<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_renders_all_thirteen_sections_and_required_copy_for_guests(): void
    {
        $response = $this->get(route('welcome'));

        $response->assertOk();

        // Section 1: Announcement bar
        $response->assertSee('GhostModsSync is preparing for launch. Join the community and stay updated.', false);

        // Section 2: Sticky Navigation (Guest state shows Login & Sign Up, not Go to Dashboard)
        $response->assertSee(route('login'), false);
        $response->assertSee(route('register'), false);
        $response->assertSee('Sign Up', false);
        $response->assertDontSee('Go to Dashboard', false);

        // Section 3: Hero
        $response->assertSee('One source. Every mod. Always in sync.', false);
        $response->assertSee('Stop wasting hours fixing mismatched mods. GhostModsSync helps your VTC keep everyone on the same mods, the same versions and the same loadout.', false);
        $response->assertSee('Create Your VTC', false);
        $response->assertSee('Centralised mod management', false);
        $response->assertSee('Private group libraries', false);
        $response->assertSee('Desktop synchronisation', false);
        $response->assertSee('Admin-controlled membership', false);

        // Section 4: The Problem
        $response->assertSee('Still fixing mods before every session?', false);
        $response->assertSee('Version mismatches', false);
        $response->assertSee('Manual downloads', false);
        $response->assertSee('Load-order problems', false);
        $response->assertSee('Group inconsistency', false);
        $response->assertSee('Wasted gaming time', false);
        $response->assertSee('Your group should spend its time gaming together, not fixing the same mod problems over and over.', false);

        // Section 5: How It Works
        $response->assertSee('One central library. Four simple steps.', false);
        $response->assertSee('REGISTER YOUR VTC', false);
        $response->assertSee('LEADER UPLOADS MODS', false);
        $response->assertSee('APPROVE YOUR MEMBERS', false);
        $response->assertSee('MEMBERS SYNCHRONISE', false);

        // Section 6: Features
        $response->assertSee('Everything your VTC needs to stay synchronised.', false);
        $response->assertSee('Central Mod Management', false);
        $response->assertSee('Private Group Libraries', false);
        $response->assertSee('Steam + Private Mod Support', false);
        $response->assertSee('Admin-Controlled Membership', false);
        $response->assertSee('Desktop Auto-Synchronisation', false);
        $response->assertSee('Version Consistency', false);
        $response->assertSee('Group Isolation', false);
        $response->assertSee('Easier Mod Management', false);

        // Section 7: For VTC Leaders and Members
        $response->assertSee('Your VTC. Your Mods. Your Control.', false);
        $response->assertSee('Less Setup. More Gaming.', false);
        $response->assertSee('Create Your Account', false);

        // Section 8: Download GhostModsSync Client (Non-fabricated coming soon state)
        $response->assertSee('Your mods. Automatically in sync.', false);
        $response->assertSee('Desktop client coming soon.', false);
        $response->assertSee('An installed GhostModsSync client is required for members to synchronise their group modifications.', false);

        // Section 9: Pricing - Coming Soon
        $response->assertSee('Simple pricing. Built around your VTC.', false);
        $response->assertSee('Pricing Coming Soon', false);
        $response->assertSee('Be the first to know when GhostModsSync pricing goes live.', false);
        $response->assertSee('Notify Me', false);

        // Section 10: Trust and Community
        $response->assertSee('Built for communities that game together.', false);
        $response->assertSee('Join the GhostModsSync community.', false);
        $response->assertSee('Join Our Discord', false);

        // Section 11: FAQ (All 12 questions)
        $response->assertSee('What is GhostModsSync?');
        $response->assertSee('Who can create a VTC?');
        $response->assertSee('Who approves new members?');
        $response->assertSee("Who uploads the group's private mods?");
        $response->assertSee('Can other VTCs access our private mods?');
        $response->assertSee('Do I need to install the desktop client?');
        $response->assertSee('Does GhostModsSync support Steam mods?');
        $response->assertSee('Who pays for the subscription?');
        $response->assertSee('How much does GhostModsSync cost?');
        $response->assertSee('What happens when a mod is updated?');
        $response->assertSee('Can I belong to multiple VTCs?');
        $response->assertSee('Which games are supported?');

        // Section 12: Final CTA & Social Share
        $response->assertSee('Ready to get your VTC in sync?');
        $response->assertSee('Share on Facebook');
        $response->assertSee('Share on X');
        $response->assertSee('Copy Link');

        // Section 13: Footer & Cookie Consent
        $response->assertSee('© '.now()->year.' Ghost Inc Gaming. A JNK Designs company. All rights reserved.');
        $response->assertSee('Cookie & Privacy Preferences');
    }

    public function test_authenticated_users_see_go_to_dashboard_instead_of_login_and_sign_up(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('welcome'));

        $response->assertOk();
        $response->assertSee('Go to Dashboard', false);
        $response->assertSee(route('dashboard'), false);
        $response->assertSee(route('groups.create'), false);
        $response->assertDontSee('>Sign Up<', false);
    }

    public function test_pricing_notification_form_validates_email_and_returns_placeholder_acknowledgment(): void
    {
        // Invalid email via JSON
        $invalidJson = $this->postJson(route('pricing.notify'), [
            'email' => 'not-an-email',
        ]);
        $invalidJson->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Valid email via JSON
        $validJson = $this->postJson(route('pricing.notify'), [
            'email' => 'leader@sfg-vtc.test',
        ]);
        $validJson->assertOk()
            ->assertJsonPath('status', 'placeholder_acknowledged');

        // Standard form POST fallback
        $formPost = $this->post(route('pricing.notify'), [
            'email' => 'founder@sfg-vtc.test',
        ]);
        $formPost->assertRedirect(route('welcome').'#pricing')
            ->assertSessionHas('pricing_notify_status');
    }

    public function test_all_configured_brand_assets_exist_on_disk_and_legal_placeholder_routes_work(): void
    {
        $images = config('landing.images');

        $this->assertFileExists(public_path($images['logo_full']['path']));
        $this->assertFileExists(public_path($images['logo_icon']['path']));
        $this->assertFileExists(public_path($images['favicon']['path']));
        $this->assertFileExists(public_path($images['hero_illustration']['path']));
        $this->assertFileExists(public_path($images['client_mockup']['path']));
        $this->assertFileExists(public_path($images['og_image']['path']));

        foreach ($images['how_it_works'] as $stepAsset) {
            $this->assertFileExists(public_path($stepAsset['path']));
        }

        foreach ($images['game_screenshots'] as $gameAsset) {
            $this->assertFileExists(public_path($gameAsset['path']));
        }

        $this->assertFileExists(base_path('ASSETS_README.md'));
        $this->assertFileExists(public_path('robots.txt'));
        $this->assertFileExists(public_path('sitemap.xml'));

        $this->get(route('legal.privacy'))->assertOk()->assertSee('Privacy Policy');
        $this->get(route('legal.terms'))->assertOk()->assertSee('Terms and Conditions');
        $this->get(route('legal.contact'))->assertOk()->assertSee('Contact GhostModsSync');
    }
}
