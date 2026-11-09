# GhostModsSync — Brand & Marketing Assets Reference (`ASSETS_README.md`)

> **TODO:** Replace generated placeholder branding with final GhostModsSync brand assets from Ghost Inc Gaming / JNK Designs before public launch.

All landing page visual assets are stored in [`public/images/brand/`](file:///c:/xampp/htdocs/ghostModSync/public/images/brand) and referenced centrally through [`config/landing/images.php`](file:///c:/xampp/htdocs/ghostModSync/config/landing/images.php). No image paths are hardcoded inside individual Blade components.

---

## Asset Inventory

| Asset Key (`config/landing/images.php`) | Filename (`public/images/brand/`) | Dimensions | Format | Purpose | Section Used |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `logo_full` | `logo-full.svg` | `240 × 44` | `SVG` | Primary horizontal GhostModsSync brand logo with Ghost Inc Gaming subtitle | Sticky Navigation (`Section 2`) & Footer (`Section 13`) |
| `logo_icon` | `logo-icon.svg` | `44 × 44` | `SVG` | Standalone GhostModsSync synchronisation emblem | Mobile header / compact branding |
| `favicon` | `favicon.svg` | `64 × 64` | `SVG` | Browser tab vector icon | HTML `<head>` metadata |
| `hero_illustration` | `hero-sync-network.svg` | `880 × 620` | `SVG` | Architectural hero diagram showing VTC Leader → Central GhostModsSync Hub → Desktop Client & Connected Member PCs | Hero (`Section 3`, loaded eagerly) |
| `how_it_works.step_1` | `step-1-register-vtc.svg` | `320 × 200` | `SVG` | Step 1 visual: VTC Leader registering a VTC on the web platform | How It Works (`Section 5`, lazy loaded) |
| `how_it_works.step_2` | `step-2-upload-mods.svg` | `320 × 200` | `SVG` | Step 2 visual: Uploading and organising private group mods | How It Works (`Section 5`, lazy loaded) |
| `how_it_works.step_3` | `step-3-approve-members.svg` | `320 × 200` | `SVG` | Step 3 visual: VTC Admin/Main Leader approving members | How It Works (`Section 5`, lazy loaded) |
| `how_it_works.step_4` | `step-4-desktop-sync.svg` | `320 × 200` | `SVG` | Step 4 visual: Approved members synchronising via desktop client | How It Works (`Section 5`, lazy loaded) |
| `client_mockup` | `client-mockup.svg` | `960 × 600` | `SVG` | Windows Desktop Client interface mockup showing VTC selection, SHA-256 file verification, and mod folder sync | Download Client (`Section 8`, lazy loaded) |
| `og_image` | `og-ghostmodsync.svg` | `1200 × 630` | `SVG` | Social sharing card banner for Open Graph and X/Twitter Cards | HTML `<head>` Open Graph & Twitter metadata |
| `game_screenshots.hero_convoy` | `../games/hero-convoy-ets2-ats.jpg` | `1280 × 720` | `JPG` | In-game style joint ETS2 & ATS multiplayer VTC convoy scene at dusk | Hero (`Section 3`) & Final CTA (`Section 12`) |
| `game_screenshots.ets2_convoy` | `../games/ets2-european-convoy.jpg` | `1280 × 720` | `JPG` | In-game style Euro Truck Simulator 2 European Alpine highway VTC convoy | Hero (`Section 3`), Roles (`Section 7`), Trust & Community (`Section 10`) |
| `game_screenshots.ats_convoy` | `../games/ats-american-convoy.jpg` | `1280 × 720` | `JPG` | In-game style American Truck Simulator desert interstate VTC convoy | Hero (`Section 3`), Problem (`Section 4`), Trust & Community (`Section 10`) |
| `game_screenshots.mod_garage` | `../games/convoy-mod-garage.jpg` | `1280 × 720` | `JPG` | In-game style VTC customization garage bay with modded show truck | Roles — For VTC Leaders (`Section 7`) |

> **Note on Feature & Problem Icons:** Feature icons (`Section 6`), Problem icons (`Section 4`), and Social platform icons (`Section 13`) are implemented via the reusable [`<x-landing.icon>`](file:///c:/xampp/htdocs/ghostModSync/resources/views/components/landing/icon.blade.php) SVG icon set component with `aria-hidden="true"` and accessible labels on interactive controls.

---

## Replacement Instructions

1. **Drop Final Assets into `public/images/brand/`:**
   - Use **SVG** for vector logos, icons, and crisp interface diagrams.
   - Use **WebP** or **AVIF** for photographic or complex raster screenshots (e.g., if replacing `client-mockup.svg` with a real WPF desktop client screenshot or `og-ghostmodsync.svg` with a raster `1200×630` `.webp`/`.png` Open Graph card).
2. **Update [`config/landing/images.php`](file:///c:/xampp/htdocs/ghostModSync/config/landing/images.php):**
   - Update the `path`, `width`, `height`, `format`, and `alt` fields for any replaced file.
   - Because all Blade section components read from `config('landing.images.*')`, no template edits are required when swapping filenames or formats.
3. **Verify Asset Integrity:**
   - Run `php vendor/phpunit/phpunit/phpunit --filter=WelcomeLandingPageTest` to confirm all configured asset files exist on disk and render with explicit dimensions and `alt` attributes.
