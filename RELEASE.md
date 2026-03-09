# Release Guide

Automated release process for the unraid-netbird plugin using GitHub Actions.

## Prerequisites

- Git configured with push access to `main` branch
- `vendor/bin/phpstan` and `vendor/bin/php-cs-fixer` available
- Local `main` branch synced with remote

## Automated Release Workflow

The release process is fully automated via GitHub Actions:

1. **Release Drafter** (`.github/workflows/release-drafter.yml`): Automatically creates a draft release with timestamp version on every push to `main`
2. **Release Builder** (`.github/workflows/release.yml`): Builds and publishes the plugin package when you publish the draft release

### Step 1: Validate Code Quality

Before committing changes, ensure code quality checks pass:

```bash
# Run PHPStan static analysis
vendor/bin/phpstan

# Run PHP-CS-Fixer code style check
vendor/bin/php-cs-fixer fix --dry-run
# If changes needed:
vendor/bin/php-cs-fixer fix
```

Both must pass with no errors before proceeding.

### Step 2: Commit and Push Changes

Stage and commit your changes using semantic commit messages:

```bash
# Verify what will be committed
git status --short

# Commit with semantic message (feat:, fix:, chore:, etc.)
git add -A
git commit -m "feat: your changes here"

# Push to main
git push
```

**Important:** Use semantic commit prefixes (`feat:`, `fix:`, `chore:`) - they determine the changelog category.

### Step 3: Wait for Draft Release

The Release Drafter workflow will automatically:
- Generate a timestamp-based version (e.g., `2026.03.09.1745`)
- Create a draft release with that version
- Populate release notes from your commit messages
- Categorize changes (Features, Bug Fixes, Maintenance)

Check the draft:
```bash
# List all releases (including drafts)
gh release list
```

### Step 4: Review and Edit Release Notes

Open the draft release in GitHub:

```bash
# Open releases page in browser
gh release list --web
```

Or visit: `https://github.com/bstaeheli/unraid-netbird/releases`

Edit the draft to:
- Review automatically generated changelog
- Add additional context or breaking changes
- Verify the version number is correct

### Step 5: Publish the Release

Click **"Publish release"** in the GitHub UI (or use CLI):

```bash
# Find the version/tag of the draft
VERSION=$(gh release list --limit 1 | awk '{print $1}')

# Publish the draft
gh release edit $VERSION --draft=false
```

### Step 6: Monitor Automated Build

The Release workflow (`.github/workflows/release.yml`) will automatically:
1. Checkout code
2. Build the `.txz` package with Composer dependencies
3. Generate SHA256 checksum
4. Upload both assets to the release
5. Update `.plg` files with new release information
6. Update `main` and `preview` Git tags

Monitor the workflow:
```bash
# Watch the build in real-time
gh run watch

# Or list recent runs
gh run list --workflow=release.yml --limit 3
```

### Step 7: Verify Release

After the workflow completes successfully:

```bash
# Check release assets
gh release view $VERSION --json assets,url

# Expected assets:
# - unraid-netbird-utils-$VERSION-noarch-1.txz
# - unraid-netbird-utils-$VERSION-noarch-1.txz.sha256
```

## Quick Reference

```bash
# 1. Quality checks
vendor/bin/phpstan && vendor/bin/php-cs-fixer fix --dry-run

# 2. Commit and push
git add -A && git commit -m "feat: your changes" && git push

# 3. Open releases page
gh release list --web

# 4. Publish the draft release in GitHub UI

# 5. Watch the build
gh run watch
```

## Understanding Changelog Categories

The Release Drafter automatically categorizes commits based on their prefix:

- `feat:` → 🚀 Features
- `fix:` → 🐛 Bug Fixes  
- `chore:`, `refactor:` → 🧰 Maintenance

Commits with labels like `docs`, `test`, `ci` are excluded from the changelog.

## Troubleshooting

### Draft Release Not Created

If no draft appears after pushing:
1. Check workflow runs: `gh run list --workflow=release-drafter.yml`
2. Ensure you pushed to the `main` branch
3. Verify `.github/workflows/release-drafter.yml` exists

### Build Workflow Failed

Common causes:
- **Composer errors**: Check PHP dependencies in `composer.json`
- **Permission issues**: Verify `GITHUB_TOKEN` has write access
- **SSH key errors**: Check `DEPLOY_KEY` secret is configured

View error details:
```bash
# Get the failed run ID
RUN_ID=$(gh run list --workflow=release.yml --limit 1 --json databaseId --jq '.[0].databaseId')

# View the log
gh run view $RUN_ID --log-failed
```

### Release Published as Pre-Release

Releases are published as **pre-releases** by default (configured in `.github/release-drafter.yml`).

To convert to a stable release:
```bash
gh release edit $VERSION --prerelease=false --latest
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

### Build Failed with `release not found`

Symptom in logs:
```bash
release not found
Error: Process completed with exit code 1
```

Root cause:
- The release action reads recent releases and resolves them with `gh release view <token>`.
- If the release title contains a `v` prefix (for example `v2026.03.09.1156`) while the tag is `2026.03.09.1156`, token parsing in the action can fail and abort changelog generation.

Fix:
1. Ensure release title equals the tag exactly (no `v` prefix):
```bash
gh release edit 2026.03.09.1234 --title "2026.03.09.1234"
```
2. Re-run the failed workflow:
```bash
gh run rerun <RUN_ID>
gh run watch <RUN_ID>
```

Prevention:
- Always create releases using `--title $VERSION` where `$VERSION` is exactly the tag.
- Prefer `gh release create $VERSION --title $VERSION ...`.

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
