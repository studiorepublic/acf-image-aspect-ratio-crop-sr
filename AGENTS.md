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

## Release Workflow

1. Add changes under `## [Unreleased]` in `CHANGELOG.md`
2. Run `./scripts/release.sh X.Y.Z` (e.g. `./scripts/release.sh 6.0.6`)
3. Script bumps version in `acf-image-aspect-ratio-crop.php` and `readme.txt`, commits, tags, and pushes
4. GitHub Action (`.github/workflows/release.yml`) builds the zip and creates the release on tag push

Manual build: `./scripts/build-release.sh [version]` — output in `dist/acf-image-aspect-ratio-crop.zip`

## Key Files

- `acf-image-aspect-ratio-crop.php` — Main bootstrap, update checker, REST API
- `fields/class-npx-acf-field-image-aspect-ratio-crop-v5.php` — ACF field class
- `assets/src/input.js` — Field UI, Cropper.js integration

## Conventions

- **Text domain**: `acf-image-aspect-ratio-crop`
- **REST API**: `aiarc/v1`
- **Meta keys**: `acf_image_aspect_ratio_crop_` prefix

See `.cursorrules` for coding style and project structure.
