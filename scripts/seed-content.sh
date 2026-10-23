#!/usr/bin/env bash
# Seeds Lendly pages, menus and reading settings. Run once on a fresh site:
#   bash scripts/seed-content.sh
set -euo pipefail

wp() { ddev wp "$@" | tr -d '\r'; }

page() { # title, template, excerpt, order, content
  wp post create --post_type=page --post_status=publish \
    --post_title="$1" --post_excerpt="${3:-}" --menu_order="${4:-0}" \
    --post_content="${5:-}" \
    --meta_input="{\"_wp_page_template\":\"${2:-default}\"}" --porcelain
}

LOAN='page-templates/template-loan.php'

HOME_ID=$(page 'Home')
PERSONAL=$(page 'Personal Loans' "$LOAN" 'A fixed-rate personal loan for almost anything, with repayments that fit your budget.' 1 '<!-- wp:paragraph --><p>Borrow $2,000 to $50,000 over 1 to 7 years.</p><!-- /wp:paragraph -->')
CAR=$(page 'Car Loans' "$LOAN" 'Get on the road with a fixed-rate car loan and no balloon payment.' 2)
DEBT=$(page 'Debt Consolidation' "$LOAN" 'Combine credit cards and loans into one simple repayment.' 3)
APPLY=$(page 'Apply' default '' 0 '<!-- wp:shortcode -->[lendly_apply]<!-- /wp:shortcode -->')
HOW=$(page 'How it works')
ABOUT=$(page 'About')
CONTACT=$(page 'Contact')
CREDIT=$(page 'Credit Guide')
BLOG=$(page 'Blog')

# WordPress already made a draft Privacy Policy page; publish it.
PRIVACY=$(wp option get wp_page_for_privacy_policy)
wp post update "$PRIVACY" --post_status=publish

# Remove the sample page if it exists.
SAMPLE=$(wp post list --post_type=page --name=sample-page --field=ID)
[ -n "$SAMPLE" ] && wp post delete "$SAMPLE" --force

# Static front page + blog page.
wp option update show_on_front page
wp option update page_on_front "$HOME_ID"
wp option update page_for_posts "$BLOG"

# Menus.
wp menu create 'Primary'
for id in $PERSONAL $CAR $DEBT $HOW $ABOUT $CONTACT; do wp menu item add-post primary "$id"; done
wp menu location assign primary primary

wp menu create 'Company'
for id in $ABOUT $CONTACT $BLOG; do wp menu item add-post company "$id"; done
wp menu location assign company footer

wp menu create 'Legal'
for id in $PRIVACY $CREDIT; do wp menu item add-post legal "$id"; done
wp menu location assign legal legal

wp rewrite flush --hard
echo "Seeded. Home=$HOME_ID Apply=$APPLY"