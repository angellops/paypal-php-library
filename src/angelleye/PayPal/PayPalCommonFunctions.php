<?php
namespace angelleye\PayPal;

/**
 * PayPal CommonFunctions Class
 * Provides common helper methods used across PayPal integrations.
 */
class PayPalCommonFunctions
{
    protected $config;
    protected $mode;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->mode = isset($config['Sandbox']) ? $config['Sandbox'] : 'live';
    }

    /**
     * Render the PayPal payment button
     */
    public function renderPayPalButton($enableVenmo = false)
    {
        if ($this->config['PayPalAPIMode'] !== 'rest') {
            echo '<img src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif">';
            return;
        }

        $sdk_url = ( $this->mode === 'sandbox' ) ? "https://www.sandbox.paypal.com/web-sdk/v6/core" : "https://www.paypal.com/web-sdk/v6/core";
        ?>
        
        <!-- PayPal JS SDK v6 Web Core -->
        <script async src="<?php echo $sdk_url; ?>" onload="initPayPalV6()"> </script>

        <div id="paypalError"></div>
        <?php if ($enableVenmo && $this->config['PayPalAPIMode'] === 'rest'): ?><a href="./SetExpressCheckout.php?paywith=paypal"><?php endif; ?>
            <paypal-button id="paypalBtn" type="pay" hidden></paypal-button>
        <?php if ($enableVenmo && $this->config['PayPalAPIMode'] === 'rest'): ?></a><?php endif; ?>

        <?php if ($enableVenmo): ?>
            <venmo-button id="venmoBtn" type="pay" hidden></venmo-button>
        <?php endif; ?>

        <script>
            async function initPayPalV6() {
                const errorEl = document.getElementById('paypalError');
                const btnEl   = document.getElementById('paypalBtn');
                const venmoEl = document.getElementById('venmoBtn');
                try {
                    const res = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_client_token');
                    if (!res.ok) {
                        throw new Error('Failed to fetch PayPal client token');
                    }

                    const data = await res.json();
                    if (!data.token) {
                        throw new Error(data.message || 'PayPal client token missing');
                    }

                    await paypal.createInstance({
                        clientToken: data.token,
                        components: ['paypal-payments', 'venmo-payments'],
                        pageType: 'checkout'
                    });

                    btnEl.removeAttribute('hidden');
                    if (venmoEl) venmoEl.removeAttribute('hidden');
                } catch (err) {
                    console.error('PayPal init error:', err);

                    // show error message
                    errorEl.textContent = err.message || 'Unable to load PayPal. Please try again.';
                    errorEl.style.display = 'block';
                }
            }
        </script>
        <?php 
    }

    /**
     * Get cart contents from session
     *
     * @return array
     */
    public function get_cart() {
        return !empty($_SESSION['paylater_items']) ? $_SESSION['paylater_items'] : [];
    }

    /**
     * Add an item to the cart
     *
     * @param string|int $id    Product ID
     * @param string     $name  Product name
     * @param float      $price Product price
     * @param int        $qty   Quantity to add
     *
     * @return void
     */
    public function add_to_cart($id, $name, $price, $qty) {
        if (!isset($_SESSION['paylater_items'])) {
            $_SESSION['paylater_items'] = [];
        }

        if (isset($_SESSION['paylater_items'][$id])) {
            $_SESSION['paylater_items'][$id]['qty'] += $qty;
        } else {
            $_SESSION['paylater_items'][$id] = [
                'name'  => $name,
                'price' => (float)$price,
                'qty'   => (int)$qty
            ];
        }
    }

    /**
     * Update cart item quantity
     *
     * @param string|int $id  Product ID
     * @param int        $qty New quantity
     *
     * @return void
     */
    public function update_cart_qty($id, $qty) {
        if ($qty <= 0) {
            unset($_SESSION['paylater_items'][$id]);
        } else {
            $_SESSION['paylater_items'][$id]['qty'] = $qty;
        }
    }

    /**
     * Empty the entire cart
     *
     * @return void
     */
    public function empty_cart() {
        unset($_SESSION['paylater_items']);
    }

    /**
     * Calculate cart total amount
     *
     * @return string Formatted total (2 decimal places)
     */
    public function cart_total() {
        $total = 0;
        foreach ($this->get_cart() as $item) {
            $total += $item['price'] * $item['qty'];
        }
        return number_format($total, 2, '.', '');
    }

    /**
     * Calculate cart subtotal amount
     *
     * @return string Formatted total (2 decimal places)
     */
    public function calculate_subtotal($items) {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }
        return number_format($subtotal, 2, '.', '');
    }
}