#!/usr/bin/env bash
# Creates one GitHub issue per plan file under docs/v4-plans/.
# Writes the resulting issue numbers to scripts/v4-issue-map.tsv (TSV: plan_path<TAB>issue_number).
# Idempotent-ish: re-running creates duplicate issues — only run once or clean up after.
#
# Prerequisites:
#  - gh CLI authenticated
#  - v4.0 milestone exists
#  - All 14 phase/scope labels exist
#  - docs/v4-plans/ tree present on the working branch

set -euo pipefail

MILESTONE="v4.0"
OUT_MAP="scripts/v4-issue-map.tsv"

declare -A PHASE_NAMES=(
  ["0"]="Cleanup & Foundation"
  ["1"]="Core REST Plumbing"
  ["2"]="REST Resource Handlers"
  ["3"]="Legacy Adapter & Upgrade Path"
  ["4"]="Templates / Samples / Demos / Helper"
  ["5"]="Documentation"
  ["6"]="Verification & RC Bake"
  ["7"]="Release & Brand Cutover"
)

# Plan-file path (relative to docs/v4-plans/) → scope labels (comma-separated; empty = no scope).
declare -A SCOPE_LABELS=(
  ["phase-0/01-clean-dead-code.md"]="support"
  ["phase-0/02-paypal-php-modernization.md"]="support"
  ["phase-0/03-composer-and-autoload.md"]="support"
  ["phase-0/04-test-and-ci-setup.md"]="ci"
  ["phase-0/05-phase-0-verification.md"]="ci"
  ["phase-1/01-config-and-exceptions.md"]="rest"
  ["phase-1/02-http-layer.md"]="rest"
  ["phase-1/03-auth-and-tokens.md"]="rest"
  ["phase-1/04-base-classes-and-facade.md"]="rest"
  ["phase-1/05-support-utilities.md"]="rest,support"
  ["phase-1/06-phase-1-integration-test.md"]="rest"
  ["phase-2/01-orders.md"]="rest"
  ["phase-2/02-payments.md"]="rest"
  ["phase-2/03-webhooks.md"]="rest"
  ["phase-2/04-webhook-verifier.md"]="rest"
  ["phase-2/05-subscriptions.md"]="rest"
  ["phase-2/06-plans.md"]="rest"
  ["phase-2/07-catalog-products.md"]="rest"
  ["phase-2/08-invoicing.md"]="rest"
  ["phase-2/09-payouts.md"]="rest"
  ["phase-2/10-disputes.md"]="rest"
  ["phase-2/11-vault.md"]="rest"
  ["phase-2/12-partner-referrals.md"]="rest"
  ["phase-2/13-identity.md"]="rest"
  ["phase-2/14-reports.md"]="rest"
  ["phase-3/01-legacy-foundation.md"]="legacy"
  ["phase-3/02-ec-token-bridge.md"]="legacy"
  ["phase-3/03-express-checkout-mappers.md"]="legacy"
  ["phase-3/04-auth-capture-mappers.md"]="legacy"
  ["phase-3/05-refund-search-mappers.md"]="legacy"
  ["phase-3/06-direct-mass-mappers.md"]="legacy"
  ["phase-3/07-recurring-mappers.md"]="legacy"
  ["phase-3/08-billing-agreement-mappers.md"]="legacy"
  ["phase-3/09-invoicing-mappers.md"]="legacy"
  ["phase-3/10-other-mappers.md"]="legacy"
  ["phase-3/11-paypal-php-dispatch-hook.md"]="legacy"
  ["phase-3/12-upgrade-check-cli.md"]="legacy"
  ["phase-3/13-legacy-exceptions.md"]="legacy"
  ["phase-4/01-templates-rest-wipe-rebuild.md"]="rest"
  ["phase-4/02-samples-rest-wipe-rebuild.md"]="rest"
  ["phase-4/03-demo-rest-checkout-standard.md"]="rest,docs"
  ["phase-4/04-demo-rest-checkout-redirect.md"]="rest,docs"
  ["phase-4/05-support-button-helper.md"]="support"
  ["phase-4/06-config-sample-update.md"]="support"
  ["phase-5/01-upgrade-from-classic-doc.md"]="docs"
  ["phase-5/02-rest-resource-docs.md"]="docs"
  ["phase-5/03-js-sdk-and-webhooks-docs.md"]="docs"
  ["phase-5/04-readme-changelog-migration.md"]="docs,brand"
  ["phase-6/01-manual-demo-verifications.md"]=""
  ["phase-6/02-rc-tag-and-bake.md"]=""
  ["phase-7/01-ga-cutover.md"]="brand"
  ["phase-7/02-oob-followups.md"]="brand"
)

> "$OUT_MAP"  # truncate

count=0
for rel_path in "${!SCOPE_LABELS[@]}"; do : ; done  # ensure assoc-array iteration is supported

# Iterate in a stable order (sorted by phase + file name).
for full_path in $(find docs/v4-plans -name '*.md' ! -name 'README.md' | sort); do
  rel_path=${full_path#docs/v4-plans/}        # e.g. phase-0/01-clean-dead-code.md
  phase_dir=${rel_path%%/*}                    # phase-0
  phase_num=${phase_dir#phase-}                # 0

  title=$(head -1 "$full_path" | sed 's/^# //')
  scope=${SCOPE_LABELS[$rel_path]:-}

  labels="$phase_dir"
  if [ -n "$scope" ]; then
    labels="$labels,$scope"
  fi

  body=$(cat <<EOFBODY
**Plan file:** [\`docs/v4-plans/$rel_path\`](docs/v4-plans/$rel_path)

**PRD:** [\`docs/PRD.md\`](docs/PRD.md)

This issue tracks execution of the plan file above. The plan file is the source of truth for scope, acceptance criteria, and verification — pick this issue up when ready to take the iteration, execute against the plan, and close when the acceptance criteria are met.

**Phase $phase_num — ${PHASE_NAMES[$phase_num]}**

> Links above are relative paths. They resolve on \`main\` after [PR #285](https://github.com/angellops/paypal-php-library/pull/285) merges (the PR adds both the revised PRD and the \`docs/v4-plans/\` tree).
EOFBODY
)

  echo "[$((++count))/52] $rel_path → labels=$labels"
  url=$(gh issue create \
    --title "$title" \
    --body "$body" \
    --label "$labels" \
    --milestone "$MILESTONE")
  num="${url##*/}"
  printf '%s\t%s\n' "$rel_path" "$num" >> "$OUT_MAP"
done

echo "Done. Issue map at $OUT_MAP."
