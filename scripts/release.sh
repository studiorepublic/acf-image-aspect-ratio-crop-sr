#!/usr/bin/env bash
#
# Bump version, commit, tag, and push to trigger a GitHub release.
# Run from the plugin root: ./scripts/release.sh <version>
#
# Example: ./scripts/release.sh 6.0.6
#
# This script is excluded from release zips (scripts/ is not in the dist).
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

if [[ -z "$1" ]]; then
  echo "Usage: $0 <version>"
  echo "Example: $0 6.0.6"
  exit 1
fi

VERSION="$1"
DATE="$(date +%Y-%m-%d)"

cd "$PLUGIN_DIR"

# Ensure clean working tree (allow untracked files)
if [[ -n "$(git status --porcelain | grep -v '^??')" ]]; then
  echo "Working tree has uncommitted changes. Commit or stash first."
  exit 1
fi

echo "Releasing v$VERSION..."

# Update plugin version in main file
sed -i.bak "s/Version: [0-9.]*/Version: $VERSION/" acf-image-aspect-ratio-crop.php
rm -f acf-image-aspect-ratio-crop.php.bak

# Update readme.txt
sed -i.bak "s/Stable Tag: [0-9.]*/Stable Tag: $VERSION/" readme.txt
rm -f readme.txt.bak

# Add changelog entry to readme.txt (insert after == Changelog ==)
if [[ -f readme.txt ]]; then
  perl -i -0pe "s/(== Changelog ==\n\n)/\1= $VERSION ($DATE) =\n* Release\n\n/" readme.txt
fi

# Promote [Unreleased] to new version in CHANGELOG.md (if exists)
if [[ -f CHANGELOG.md ]]; then
  perl -i -0pe "s/## \[Unreleased\]/## [Unreleased]\n\n## [$VERSION] - $DATE/" CHANGELOG.md
  git add CHANGELOG.md
fi

git add acf-image-aspect-ratio-crop.php readme.txt
git commit -m "v$VERSION: Release"
git tag -a "v$VERSION" -m "Release $VERSION"
git push origin master
git push origin "v$VERSION"

echo "Done. Release workflow: https://github.com/studiorepublic/acf-image-aspect-ratio-crop/actions"
