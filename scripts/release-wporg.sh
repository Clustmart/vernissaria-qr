#!/usr/bin/env bash
# Prepare a wordpress.org release of the Vernissaria QR plugin. See RELEASING.md.
#
#   scripts/release-wporg.sh 1.4.0
#
# 1. checks the version is the same in the plugin header, the constant and
#    readme.txt "Stable tag"
# 2. builds dist/vernissaria-qr-<version>.zip from git HEAD (committed files only,
#    no .DS_Store, no *.map) — upload this to a staging site to test
# 3. checks out / updates the wordpress.org SVN repo next to this repo
#    (../wporg-svn), syncs trunk/ to the zip's content, svn add/rm, and copies
#    trunk to tags/<version>
#
# It does NOT commit to wordpress.org. It prints the final `svn ci` command.
set -euo pipefail

VERSION="${1:?usage: scripts/release-wporg.sh <version>, e.g. 1.4.0}"
REPO="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN=vernissaria-qr
SVN_URL="https://plugins.svn.wordpress.org/$PLUGIN"
SVN_DIR="$REPO/../wporg-svn"
DIST="$REPO/dist"
ZIP="$DIST/$PLUGIN-$VERSION.zip"

cd "$REPO"

# --- 1. version consistency -----------------------------------------------
header=$(git show "HEAD:$PLUGIN/$PLUGIN.php" | sed -nE 's/^ \* Version: *([^ ]+).*/\1/p')
constant=$(git show "HEAD:$PLUGIN/$PLUGIN.php" | sed -nE "s/.*define\('VERNISSARIA_QR_VERSION', '([^']+)'\).*/\1/p")
stable=$(git show "HEAD:$PLUGIN/readme.txt" | sed -nE 's/^Stable tag: *([^ ]+).*/\1/p')
echo "header=$header constant=$constant stable_tag=$stable"
for v in "$header" "$constant" "$stable"; do
  [ "$v" = "$VERSION" ] || { echo "✗ Version mismatch: expected $VERSION everywhere (committed on HEAD)"; exit 1; }
done
git show "HEAD:$PLUGIN/readme.txt" | grep -q "^= $VERSION =" \
  || { echo "✗ readme.txt has no changelog entry '= $VERSION ='"; exit 1; }
[ -z "$(git status --porcelain -- "$PLUGIN")" ] \
  || echo "⚠ Uncommitted changes in $PLUGIN/ are NOT included (the zip is built from HEAD)."

# --- 2. zip from git HEAD ---------------------------------------------------
mkdir -p "$DIST"
rm -f "$ZIP"
git archive --format=zip --prefix="$PLUGIN/" -o "$ZIP" "HEAD:$PLUGIN"
zip -q -d "$ZIP" "*.map" "*.DS_Store" >/dev/null 2>&1 || true
echo "✓ $ZIP ($(unzip -l "$ZIP" | tail -1 | awk '{print $2}') files)"

# --- 3. SVN working copy ------------------------------------------------------
if svn ls "$SVN_URL/tags/$VERSION" >/dev/null 2>&1; then
  echo "✗ $VERSION is already published on wordpress.org (tags/$VERSION) — bump the version."; exit 1
fi
if [ ! -d "$SVN_DIR/.svn" ]; then
  svn checkout -q --depth immediates "$SVN_URL" "$SVN_DIR"
fi
# Start clean, so the script can be re-run after a fix (drops earlier local prep).
svn revert -q -R "$SVN_DIR"
rm -rf "$SVN_DIR/tags/$VERSION"
svn update -q --set-depth infinity "$SVN_DIR/trunk"
svn update -q --set-depth immediates "$SVN_DIR/tags"

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
unzip -q "$ZIP" -d "$STAGE"
rsync -a --delete --exclude ".svn" "$STAGE/$PLUGIN/" "$SVN_DIR/trunk/"

cd "$SVN_DIR"
svn add -q --force trunk
missing=$(svn status trunk | awk '/^!/ {print $2}')
[ -z "$missing" ] || echo "$missing" | xargs svn rm -q
svn cp -q trunk "tags/$VERSION"

echo
echo "Changes to trunk/ (A = added, M = modified, D = deleted):"
svn status trunk
echo "+ tags/$VERSION (copy of trunk)"
echo
echo "✓ Ready. Review the list above, then publish with:"
echo "    cd \"$SVN_DIR\" && svn ci -m \"Release $VERSION\" --username vernissaria"
echo "  (password: the SVN password from your wordpress.org profile → Account & Security)"
