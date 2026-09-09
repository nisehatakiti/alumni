#!/usr/bin/env bash
#
# Builds WordPress-installable ZIPs for Alumni Core and Alumni Theme.
#
# Usage: scripts/build-release-zips.sh [output-dir]
#   output-dir defaults to ./dist
#
# Uses Python's standard-library zipfile module so the build does not depend
# on the runner having an external `zip` command installed.

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

	# Strip VCS/dev-only files that should not ship in the release.
	find "$stage" -type f \( -name ".gitkeep" -o -name ".DS_Store" -o -name ".git*" \) -delete

	rm -f "$zip_path"

	# Python is available on GitHub-hosted Ubuntu runners and zipfile is part
	# of the standard library, avoiding a dependency on the external zip CLI.
	python3 - "$work_dir" "$slug" "$zip_path" <<'PY'
import os
import sys
import zipfile

work_dir, slug, zip_path = sys.argv[1:]
source_root = os.path.join(work_dir, slug)

with zipfile.ZipFile(
    zip_path,
    "w",
    compression=zipfile.ZIP_DEFLATED,
    compresslevel=9,
) as archive:
    for root, dirs, files in os.walk(source_root):
        dirs.sort()
        files.sort()
        for filename in files:
            full_path = os.path.join(root, filename)
            arcname = os.path.relpath(full_path, work_dir)
            archive.write(full_path, arcname)

print(f"built: {zip_path}")
PY
}

build_zip "alumni-core"
build_zip "alumni-theme"
