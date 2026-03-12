#!/usr/bin/env bash
#
# Build a self-contained plugin zip including vendor/ and built assets for GitHub release.
# Run from the plugin root: ./scripts/build-release.sh [version]
#
# The zip is placed in dist/ and named acf-image-aspect-ratio-crop.zip
# so that it can be attached to a GitHub release. Plugin Update Checker
# will use this asset (enableReleaseAssets with /\.zip$/) instead of the
# auto-generated source zip (which excludes vendor and may miss built assets).
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
DIST_DIR="$PLUGIN_DIR/dist"
BUILD_DIR="$PLUGIN_DIR/build"

VERSION="${1:-$(grep "Version:" "$PLUGIN_DIR/acf-image-aspect-ratio-crop.php" | sed 's/.*: *//' | tr -d ' ')}"
ZIP_NAME="acf-image-aspect-ratio-crop.zip"

cd "$PLUGIN_DIR"

echo "Building $ZIP_NAME (version $VERSION)..."

# Clean previous build
rm -rf "$BUILD_DIR" "$DIST_DIR"
mkdir -p "$BUILD_DIR" "$DIST_DIR"

# Build frontend assets
if [[ -f package.json ]]; then
  echo "Installing npm dependencies and building assets..."
  npm ci 2>/dev/null || npm install --no-audit --no-fund
  # Node 17+ requires legacy OpenSSL provider for older webpack
  export NODE_OPTIONS="${NODE_OPTIONS:+$NODE_OPTIONS }--openssl-legacy-provider"
  npm run build
fi

# Ensure vendor is present (production deps only)
if [[ ! -d vendor ]] || [[ vendor/autoload.php -ot composer.json ]]; then
  echo "Running composer install --no-dev..."
  composer install --no-dev --no-interaction
fi

# Copy plugin files (exclude build artifacts, dev files; see .distignore)
rsync -a \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='build' \
  --exclude='dist' \
  --exclude='.DS_Store' \
  --exclude='*.log' \
  --exclude='node_modules' \
  --exclude='scripts' \
  --exclude='.github' \
  --exclude='tests' \
  --exclude='c3.php' \
  --exclude='codeception.dist.yml' \
  --exclude='.env' \
  --exclude='.editorconfig' \
  --exclude='.prettierrc.js' \
  --exclude='.distignore' \
  --exclude='.env.testing.example' \
  --exclude='composer.lock' \
  --exclude='docker-compose.yml' \
  --exclude='Dockerfile' \
  --exclude='package-lock.json' \
  --exclude='webpack.config.js' \
  --exclude='wp-su.sh' \
  --exclude='.wordpress-org' \
  --exclude='.cursorrules' \
  --exclude='.nvmrc' \
  --exclude='package.json' \
  --exclude='assets/src' \
  . "$BUILD_DIR/acf-image-aspect-ratio-crop/"

# Create zip with plugin directory as root (wp-content/plugins/acf-image-aspect-ratio-crop/)
cd "$BUILD_DIR"
zip -r "$DIST_DIR/$ZIP_NAME" "acf-image-aspect-ratio-crop" -x "*.git*" -x "*.DS_Store"
cd "$PLUGIN_DIR"

# Cleanup
rm -rf "$BUILD_DIR"

echo "Built: $DIST_DIR/$ZIP_NAME"
echo "Attach this file to the v$VERSION GitHub release."
