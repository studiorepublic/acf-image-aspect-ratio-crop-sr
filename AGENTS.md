# Agent Notes – ACF Image Aspect Ratio Crop

## Overview

WordPress plugin extending Advanced Custom Fields with an image field for aspect-ratio cropping. Supports three modes: aspect ratio, pixel size, and free crop. Uses Cropper.js for the UI.

## Updates from GitHub

The plugin uses [yahnis-elsts/plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) to receive updates from a GitHub repository. Default repo: `https://github.com/studiorepublic/acf-image-aspect-ratio-crop`. Override via the `aiarc_update_repo_url` filter:

```php
add_filter('aiarc_update_repo_url', function ($url) {
    return 'https://github.com/your-org/acf-image-aspect-ratio-crop';
});
```

Release zips are built via `scripts/build-release.sh` and attached to GitHub releases. Plugin Update Checker uses `enableReleaseAssets('/\.zip$/')` to serve the zip (not the source archive).

## Branch Protection

The `main` branch is protected. All updates must go through pull requests. Create a feature branch, push changes, open a PR, and merge after review. Do not push directly to `main`.

## Release Workflow

1. Add changes under `## [Unreleased]` in `CHANGELOG.md`
2. Run `./scripts/release.sh X.Y.Z` (e.g. `./scripts/release.sh 6.0.6`)
3. Script bumps version in `acf-image-aspect-ratio-crop.php` and `readme.txt`, commits, tags, and pushes
4. GitHub Action (`.github/workflows/release.yml`) builds the zip and creates the release on tag push

Manual build: `./scripts/build-release.sh [version]` — output in `dist/acf-image-aspect-ratio-crop-sr.zip`. Runs `npm run build` and includes `assets/dist/` in the zip (rsync must use `--exclude='/dist'` only for the plugin `dist/` folder, not `assets/dist`).

## Key Files

- `acf-image-aspect-ratio-crop.php` — Main bootstrap, update checker, REST API, `build_crop_metadata`, `aiarc_crop_url`, `aiarc_cloudflare_crop_url`, `aiarc_cloudflare_recrop_url`, `aiarc_get_focal_point`, `aiarc_get_focal_gravity`, `aiarc_is_cloudflare_proxy`, preview endpoint
- `fields/class-npx-acf-field-image-aspect-ratio-crop-v5.php` — ACF field class (load_value, update_value, format_value, render_field)
- `assets/src/input.js` — Field UI, Cropper.js integration

## Stored Value Format

The field stores an array (no cropped image file):

```php
[
    'attachment_id' => 123,
    'original_url' => '...',
    'crop' => [
        'x' => 120,
        'y' => 80,
        'width' => 1600,
        'height' => 900,
        'focal_point' => ['x' => 50.0, 'y' => 50.0],
    ],
    'aspect_ratio' => '16:9',
]
```

`focal_point` is optional on legacy values; `aiarc_get_focal_point()` defaults to 50, 50.

## Cloudflare Images

When "Use Cloudflare Image Transformations" is enabled, `aiarc_use_cloudflare_transforms()` (settings + `CF-Ray`/`CF-Connecting-IP`) gates Cloudflare URLs. `aiarc_crop_url()` calls `aiarc_cloudflare_recrop_url()` when both max width and height are set (focal via `gravity`), otherwise `aiarc_cloudflare_crop_url()` (trim + optional `scale-down`). `aiarc_get_preview_url` uses the same gate for admin preview.

Non-Cloudflare sites: use `crop.focal_point` with CSS `object-position: {x}% {y}%` and `object-fit: cover`.

## Timber Usage

- `aiarc_crop_url($image, $max_width, $max_height)` — PHP helper; accepts crop metadata, ACF image array, attachment ID, URL, or Timber image
- `aiarc_object_position_style($crop_data, $enabled)` — Inline `style="object-position: …"` attribute from focal point; empty when disabled or not crop data
- `aiarc_is_crop_data($value)` — True when value is AIARC crop metadata (not a standard ACF image array)
- `{{ hero_image|aiarc_crop }}` or `{{ hero_image|aiarc_crop(800) }}` — Twig filter (when Timber active); same input types as `aiarc_crop_url`
- `{{ hero_image|aiarc_object_position_style(true) }}` — Twig filter for object-position style attribute

## Conventions

- **Text domain**: `acf-image-aspect-ratio-crop`
- **REST API**: `aiarc/v1` (upload, crop, get, preview)
- **Meta keys**: Field value is the array; no attachment meta for crops

See `.cursorrules` for coding style and project structure.
