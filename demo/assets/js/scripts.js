document.addEventListener("DOMContentLoaded", function () {
    /*
    * Each demo has:
    *   localPath  — relative directory to check (mirrors PHP $DIR check)
    *   buyUrl     — external AngelEye purchase link shown when dir is absent
    *
    * Logic mirrors the original PHP:
    *   if (is_dir($DIR))  → <a href="localPath">Launch Demo</a>
    *   else               → <a href="buyUrl">Buy Now</a>
    */

    /* --- Classic API Demos --- */
    const CLASSIC_DEMOS = [
        {
            id: "ec-basic",
            title: "Express Checkout",
            subtitle: "Basic",
            description: "Here we are integrating Express Checkout without any line item details or any extra features. We obtain the user's shipping information so that we can calculate shipping and tax, but otherwise no additional data is included with this checkout demo.",
            localPath: "classic/express-checkout-basic/",
            buyUrl: "https://www.angelleye.com/product/paypal-express-checkout-php-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        }
    ];

    /* --- REST API Demos --- */
    const REST_DEMOS = [
        {
            id: "rest-basic",
            title: "PayPal Checkout",
            subtitle: "Basic",
            description: "Here we are integrating Express Checkout without any line item details or any extra features. We obtain the user's shipping information so that we can calculate shipping and tax, but otherwise no additional data is included with this checkout demo.",
            localPath: "rest/paypal-checkout-basic/",
            buyUrl: "https://www.angelleye.com/product/paypal-checkout-basic-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-line-items",
            title: "PayPal Checkout",
            subtitle: "w/ Line Items",
            description: "Here we expand on our basic Express Checkout demo to add individual order items to the API requests so that the data is available within PayPal's checkout review pages transaction details.",
            localPath: "rest/paypal-checkout-line-items/",
            buyUrl: "https://www.angelleye.com/product/paypal-checkout-line-items-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-donation",
            title: "PayPal Checkout",
            subtitle: "Donation",
            description: "PayPal Donation integration using Web SDK v6, demonstrating a JS-driven payment flow where only pricing is passed to initiate checkout.",
            localPath: "rest/paypal-checkout-donation/",
            buyUrl: "https://www.angelleye.com/product/paypal-checkout-donation-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-vaulting",
            title: "PayPal Checkout",
            subtitle: "Vaulting (Billing Agreement)",
            description: "Learn how to implement Vaulting (Billing Agreement) into PayPal Checkout.",
            localPath: "rest/paypal-checkout-vaulting/",
            buyUrl: "https://www.angelleye.com/product/paypal-checkout-vaulting-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-partner-referral",
            title: "PayPal Partner Referral",
            subtitle: "Merchant Onboarding",
            description: "Learn how to implement Merchant Onboarding into PayPal Partner Referral.",
            localPath: "rest/paypal-partner-referral-onboarding/",
            buyUrl: "https://www.angelleye.com/product/paypal-partner-referral-onboarding-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-multiparty",
            title: "PayPal Checkout Multiparty",
            subtitle: "Multiparty",
            description: "Learn how to implement Multiparty into PayPal Checkout.",
            localPath: "rest/paypal-checkout-multiparty/",
            buyUrl: "https://www.angelleye.com/product/paypal-checkout-multiparty-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-subscriptions",
            title: "PayPal Checkout",
            subtitle: "Subscriptions",
            description: "Learn how to create subscription profiles using the Recurring Payments API.",
            localPath: "rest/paypal-checkout-subscriptions/",
            buyUrl: "https://www.angelleye.com/product/paypal-checkout-subscriptions-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-shipped-sub",
            title: "PayPal Checkout",
            subtitle: "Shipped Items + Subscription",
            description: "Learn how to implement PayPal Checkout with shipped Items and Subscription / Recurring Payments together on a single order.",
            localPath: "rest/paypal-checkout-shipped-items-subscription/",
            buyUrl: "https://www.angelleye.com/product/paypal-checkout-shipped-items-subscription-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-advanced-cc",
            title: "PayPal Checkout Advanced",
            subtitle: "Credit Cards",
            description: "Accept direct credit card payments on your website from buyers who do not have a PayPal account. PayPal processes the payment in the background.",
            localPath: "rest/paypal-checkout-advanced-credit-cards/",
            buyUrl: "https://www.angelleye.com/product/paypal-checkout-advanced-credit-cards-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-shipping-callback",
            title: "PayPal Shipping Callback",
            subtitle: "Shipping Callback Payment",
            description: "PayPal Shipping Callback integration using Web SDK v6, demonstrating a JS-driven payment flow where only pricing is passed to initiate checkout.",
            localPath: "rest/paypal-shipping-callback/",
            buyUrl: "https://www.angelleye.com/product/paypal-shipping-callback-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-pay-later",
            title: "PayPal Pay Later",
            subtitle: "PayLater",
            description: "PayPal Pay Later as a user-friendly payment option, integrated using the Web SDK v6 which allowing users to understand pricing, manage their cart, and complete checkout with minimal cognitive load.",
            localPath: "rest/paypal-pay-later/",
            buyUrl: "https://www.angelleye.com/product/paypal-pay-later-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-multiple-checkout",
            title: "PayPal",
            subtitle: "Multiple Checkout Options",
            description: "Demo of multiple checkout options: PayPal, Guest Checkout, Venmo, Pay Later, and Direct Card Payments.",
            localPath: "rest/paypal-multiple-checkout-options/",
            buyUrl: "https://www.angelleye.com/product/paypal-multiple-checkout-options-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-venmo",
            title: "Venmo Checkout",
            subtitle: "Venmo Checkout Payment",
            description: "Venmo Checkout integration using Web SDK v6, demonstrating a JS-driven payment flow where only pricing is passed to initiate checkout.",
            localPath: "rest/venmo-checkout-basic/",
            buyUrl: "https://www.angelleye.com/product/venmo-checkout-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-google-pay",
            title: "Google Pay",
            subtitle: "Google Pay Payment",
            description: "Google Pay integration using Web SDK v6, demonstrating a JS-driven payment flow where only pricing is passed to initiate checkout.",
            localPath: "rest/google-pay/",
            buyUrl: "https://www.angelleye.com/product/google-pay-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        },
        {
            id: "rest-apple-pay",
            title: "Apple Pay",
            subtitle: "Apple Pay Payment",
            description: "Apple Pay integration using Web SDK v6, demonstrating a JS-driven payment flow where only pricing is passed to initiate checkout.",
            localPath: "rest/apple-pay/",
            buyUrl: "https://www.angelleye.com/product/apple-pay-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
        }
    ];

    /* --- Icon SVGs --- */
    const demosWrap = document.getElementById('demos');
    let ICONS = {};
    if( demosWrap !== undefined && demosWrap !== null ) {
        ICONS = {
            link: demosWrap.dataset.linkIcon
        };
    }

    /* --- Derive image filename from localPath ---
     *   e.g. "classic/express-checkout-basic/"  →  "express-checkout-basic"
     *        "rest/paypal-checkout-basic/"       →  "paypal-checkout-basic"
     */
    function imgSlug(localPath) {
        const parts = localPath.replace(/\/+$/, '').split('/');
        return parts[parts.length - 1];
    }

    /* --- Build a card (button slot starts as "Checking…") --- */
    function buildCard(kit) {
        const slug = imgSlug(kit.localPath);
        const imgSrc = `assets/images/${slug}.jpg`;
        const imgAlt = [kit.title, kit.subtitle].filter(Boolean).join(' ');
        const subtitleHtml = kit.subtitle
            ? `<span class="card-subtitle">${kit.subtitle}</span>`
            : '';

        return `
      <div class="demo-card" id="card-${kit.id}">
        <div class="card-preview">
          <img 
            src="${imgSrc}" 
            alt="${imgAlt}" 
            class="card-preview-img" 
            loading="lazy" 
            onerror="
                this.onerror=null; 
                this.src='https://placehold.co/1024x300/d6d4dd/000000/png?text=${encodeURIComponent(kit.title + ' ' + (kit.subtitle || ''))}';
                this.classList.add('noimg');"
        />
        </div>

        <div class="card-content">
          <h3 class="card-title">${kit.title}${subtitleHtml}</h3>
          <p class="card-desc">${kit.description}</p>
          <div class="card-footer">
            <!-- Placeholder while directory check is in-flight -->
            <span class="btn-buy btn-checking" id="btn-${kit.id}">
              <span class="btn-checking-dot"></span>
              <span class="btn-checking-dot"></span>
              <span class="btn-checking-dot"></span>
            </span>
          </div>
        </div>
      </div>`;
    }

    /*
     * Mirrors PHP: if (is_dir(__DIR__ . $DIR))
     *
     * We send a HEAD request to localPath.
     * - 2xx / 3xx  → directory (or its index) is reachable → Launch Demo
     * - 4xx / 5xx / network error → not present → Buy Now
     */
    async function resolveButton(kit) {
        const slot = document.getElementById('btn-' + kit.id);
        if (!slot) return;

        let dirExists = false;

        try {
            const res = await fetch(kit.localPath, { method: 'HEAD' });
            dirExists = res.ok; /* true for 200-299 */
        } catch (_) {
            dirExists = false;
        }

        if (dirExists) {
            /* Directory found → Launch Demo (blue, opens locally) */
            slot.outerHTML = `
        <a class="btn-buy btn-launch"
           href="${kit.localPath}"
           target="_blank"
           id="btn-${kit.id}">
          Launch Demo
          ${ICONS.link || ''}
        </a>`;
        } else {
            /* Directory missing → Buy Now (gold, opens external purchase page) */
            slot.outerHTML = `
        <a class="btn-buy btn-buynow"
           href="${kit.buyUrl}"
           target="_blank"
           rel="noopener noreferrer"
           id="btn-${kit.id}">
          Buy Now
          ${ICONS.link}
        </a>`;
        }
    }

    /* Render both grids, then resolve all buttons in parallel */
    document.getElementById('classic-grid').innerHTML = CLASSIC_DEMOS.map(buildCard).join('');
    document.getElementById('rest-grid').innerHTML = REST_DEMOS.map(buildCard).join('');

    const allDemos = [...CLASSIC_DEMOS, ...REST_DEMOS];
    allDemos.forEach(kit => resolveButton(kit));

    /* --- Footer year --- */
    document.getElementById('year').textContent = new Date().getFullYear();
});