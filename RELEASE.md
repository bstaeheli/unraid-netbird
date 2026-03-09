# Release Guide

This guide describes how to create a new release for the unraid-netbird plugin.

## Prerequisites

- `gh` CLI installed and authenticated
- Git configured with push access to the repository
- Local `main` branch up to date

## Release Process

### 1. Prepare Local Changes

Check for uncommitted changes:
```bash
git status --short
```

If there are changes, commit them:
```bash
git add .
git commit -m "fix: your changes here"
```

### 2. Sync with Remote

Pull latest changes and push your commits:
```bash
git pull --rebase origin main
git push origin refs/heads/main
```

**Note:** Always use `refs/heads/main` when pushing due to the `main` tag conflict.

### 3. Generate Version Number

Use the standard timestamp-based version format:
```bash
date +%Y.%m.%d.%H%M
```

Example output: `2026.03.09.1234`

### 4. Create GitHub Release

Create a **full release** (not a pre-release or draft):
```bash
gh release create 2026.03.09.1234 \
  --target main \
  --title 2026.03.09.1234 \
  --notes "Release 2026.03.09.1234

- Brief description of changes
- Additional notes if needed"
```

**Important:** Do not use `--prerelease` or `--draft` flags for production releases.

### 5. Monitor Build Pipeline

List recent workflow runs:
```bash
gh run list --workflow release.yml --limit 5
```

Watch the build in real-time:
```bash
gh run watch <RUN_ID>
```

### 6. Verify Release

Check release status and assets:
```bash
gh release view 2026.03.09.1234 --json isDraft,isPrerelease,assets,url
```

Expected output:
- `isDraft: false`
- `isPrerelease: false`
- Assets present:
  - `unraid-netbird-utils-2026.03.09.1234-noarch-1.txz`
  - `unraid-netbird-utils-2026.03.09.1234-noarch-1.txz.sha256`

### 7. Verify Tags (Optional)

Check that tracking tags were updated:
```bash
git ls-remote --tags origin refs/tags/main refs/tags/preview refs/tags/2026.03.09.1234
```

Expected behavior:
- `refs/tags/2026.03.09.1234` points to the release commit
- `refs/tags/preview` points to the build commit (updated for all releases)
- `refs/tags/main` points to the build commit (updated only for non-prerelease)

## Troubleshooting

### Accidentally Created a Pre-Release

If you used `--prerelease` by mistake, convert it to a full release:

1. Get the release database ID:
```bash
gh release view 2026.03.09.1234 --json databaseId
```

2. Update the release:
```bash
gh api repos/bstaeheli/unraid-netbird/releases/<DATABASE_ID> \
  -X PATCH \
  -f prerelease=false \
  -f draft=false
```

### Push Rejected Due to Tag Conflict

If `git push origin main` fails with "src refspec main matches more than one":
```bash
git push origin refs/heads/main
```

### Build Pipeline Failed

Check the workflow logs:
```bash
gh run view <RUN_ID> --log
```

Common issues:
- Missing or incorrect SHA256 in `plugin/plugin.json`
- PHP syntax errors (run `vendor/bin/php-cs-fixer fix` and `vendor/bin/phpstan`)
- Git tag push conflicts (fixed in release.yml as of 2026.03.09)

## Quick Reference

Complete release in 5 commands:
```bash
# 1. Sync
git pull --rebase origin main && git push origin refs/heads/main

# 2. Get version
VERSION=$(date +%Y.%m.%d.%H%M) && echo "Version: $VERSION"

# 3. Create release
gh release create $VERSION --target main --title $VERSION --notes "Release $VERSION"

# 4. Monitor
gh run list --workflow release.yml --limit 3

# 5. Verify
gh release view $VERSION --json isPrerelease,assets
```

## Notes

- The build pipeline automatically:
  - Builds the `.txz` package from `src/`
  - Generates SHA256 checksums
  - Updates plugin `.plg` files in the `main` branch
  - Uploads artifacts to the GitHub release
  - Updates `preview` and `main` tracking tags

- Pre-releases are useful for testing but should not be used for production deployments.

- The `main` branch always contains the latest stable release after the pipeline completes.
