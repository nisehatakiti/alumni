#!/usr/bin/env bash
#
# Builds WordPress-installable ZIPs for Alumni Core and Alumni Theme.
#
# Copies alumni-core/ and alumni-theme/ into a clean staging directory
# (stripping dev-only files such as .gitkeep), then zips each with the
# plugin/theme folder itself as the top-level entry, e.g.:
#
#   alumni-core.zip
#   └ alumni-core/
#      ├ alumni-core.php
#      ├ includes/
#      ├ admin/
#      └ public/
#
# This is the format WordPress expects from
# "プラグイン > 新規追加 > プラグインのアップロード" /
# "外観 > テーマ > 新規追加 > テーマのアップロード".
#
# Usage: scripts/build-release-zips.sh [output-dir]
#   output-dir defaults to ./dist

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
out_dir="${1:-"$repo_root/dist"}"

mkdir -p "$out_dir"
work_dir="$(mktemp -d)"
trap 'rm -rf "$work_dir"' EXIT

build_zip() {
	local slug="$1"
	local src="$repo_root/$slug"
	local stage="$work_dir/$slug"
	local zip_path="$out_dir/$slug.zip"

	if [ ! -d "$src" ]; then
		echo "error: $src not found" >&2
		exit 1
	fi

	rm -rf "$stage"
	mkdir -p "$stage"
	cp -a "$src"/. "$stage"/

	# Strip VCS/dev-only files that shouldn't ship in the release (e.g.
	# .gitkeep placeholders for otherwise-empty asset directories).
	find "$stage" -type f \( -name ".gitkeep" -o -name ".DS_Store" -o -name ".git*" \) -delete

	rm -f "$zip_path"
	( cd "$work_dir" && zip -rq -X "$zip_path" "$slug" )

	echo "built: $zip_path"
}

build_zip "alumni-core"
build_zip "alumni-theme"
