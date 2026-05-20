# Changelog

All notable changes to ACF Image Aspect Ratio Crop are documented in this file.

## [Unreleased]

## [1.1.3] - 2026-05-20

### Fixed

- **Release zip missing admin assets** — `build-release.sh` used `rsync --exclude='dist'`, which also excluded `assets/dist/`. Exclusion is now `/dist` only. Release build fails if required built files are missing.
- **`md5_file` warnings** — Asset versioning no longer calls `md5_file()` when built CSS/JS files are absent (e.g. incomplete install).

## [1.1.2] - 2026-05-20

## [1.1.1] - 2026-05-20

## [1.1.1] - 2026-05-20

## [1.1.1] - 2026-05-20

## [1.1.0] - 2026-05-20

### Added

- **Focal point** — Draggable marker inside the crop box (default center). Saved as `crop.focal_point` percentages (0–100) relative to the crop rectangle.
- **`aiarc_get_focal_point()`** / **`aiarc_get_focal_gravity()`** — Read focal metadata; convert to Cloudflare `gravity` (e.g. `0.5x0.5`).
- **`aiarc_cloudflare_recrop_url()`** — Trim to stored crop, then `fit=cover` (or `crop`) to a target size using focal gravity.

## [1.0.0] - 2026-03-13

### Added

- **Cloudflare Image Transformations** — Optional setting to serve cropped images via Cloudflare's `/cdn-cgi/image/` transform URL. Requires site to be behind Cloudflare proxy (orange cloud) and Image Resizing enabled. If enabling fails (no CF headers), the setting reverts with an error message.

## [0.2.0] - 2026-03-12

### Fixed

- **Meta not saved** — JSON crop metadata from the form was received with WordPress slashes (`\"`), causing `json_decode` to fail. Apply `wp_unslash()` before decoding so meta persists correctly on save.
- **Memory error on save** — Removed redundant `acf_update_value()` calls from `update_value` filter. The filter should only return the value; ACF persists it. Calling `acf_update_value` from within the filter caused recursion/double-processing leading to memory exhaustion.

### Changed (Breaking)

- **Metadata-only storage** — The plugin no longer creates or saves cropped image files. Instead, it stores crop metadata (attachment_id, original_url, crop coordinates, aspect_ratio) in the ACF field. Crops are generated on-the-fly by Timber or via the `aiarc_crop_url()` helper.
- **Legacy migration** — Existing fields with cropped attachment IDs are automatically migrated to the new array format on load.

### Added

- **`aiarc_crop_url()`** — PHP helper to generate cropped image URLs from metadata. Use with Timber or any PHP template.
- **`|aiarc_crop` Twig filter** — When Timber is active, use `{{ hero_image|aiarc_crop }}` or `{{ hero_image|aiarc_crop(800) }}` for responsive crops.
- **Preview endpoint** — `GET /aiarc/v1/preview` for admin crop preview (no file save).

### Removed

- **Cropped attachments** — No more cropped image files in the media library.
- **acf/save_post cleanup** — Logic for temp attachments and unused cropped image deletion.

## [0.1.0] - 2026-03-12

### Added

- **GitHub release updates** — Plugin can now receive updates from a GitHub repository via [yahnis-elsts/plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker). Default repo: `https://github.com/studiorepublic/acf-image-aspect-ratio-crop`. Use `aiarc_update_repo_url` filter to override.
- **Release scripts** — `scripts/build-release.sh` and `scripts/release.sh` for building self-contained release zips and tagging releases. GitHub Actions workflow creates releases automatically on tag push.

## [6.0.5] - 2025-10-05

### Fixed

- Fix deployment to WordPress.org
