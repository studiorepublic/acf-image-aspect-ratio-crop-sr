# Changelog

All notable changes to ACF Image Aspect Ratio Crop are documented in this file.

## [Unreleased]

### Added

- **GitHub release updates** — Plugin can now receive updates from a GitHub repository via [yahnis-elsts/plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker). Default repo: `https://github.com/studiorepublic/acf-image-aspect-ratio-crop`. Use `aiarc_update_repo_url` filter to override.
- **Release scripts** — `scripts/build-release.sh` and `scripts/release.sh` for building self-contained release zips and tagging releases. GitHub Actions workflow creates releases automatically on tag push.

## [6.0.5] - 2025-10-05

### Fixed

- Fix deployment to WordPress.org
