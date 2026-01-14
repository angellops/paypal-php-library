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
    public function renderPayPalButton()
    {
        if ($this->config['PayPalAPIMode'] !== 'rest') {
            echo '<img src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif">';
            return;
        }

        $sdk_url = ( $this->mode === 'sandbox' ) ? "https://www.sandbox.paypal.com/web-sdk/v6/core" : "https://www.paypal.com/web-sdk/v6/core";
        ?>
        
        <!-- PayPal JS SDK v6 Web Core -->
        <script async src="<?php echo $sdk_url; ?>" onload="initPayPalV6()"> </script>

        <paypal-button id="paypalBtn" type="pay" hidden></paypal-button>

        <script>
            async function initPayPalV6() {
                const res = await fetch('../../src/angelleye/PayPal/api/token.php');
                const { token } = await res.json();

                await paypal.createInstance({
                    clientToken: token,
                    components: ['paypal-payments'],
                    pageType: 'checkout'
                });

                document.getElementById('paypalBtn').removeAttribute('hidden');
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
        return !empty($_SESSION['items']) ? $_SESSION['items'] : [];
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
        if (!isset($_SESSION['items'])) {
            $_SESSION['items'] = [];
        }

        if (isset($_SESSION['items'][$id])) {
            $_SESSION['items'][$id]['qty'] += $qty;
        } else {
            $_SESSION['items'][$id] = [
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
            unset($_SESSION['items'][$id]);
        } else {
            $_SESSION['items'][$id]['qty'] = $qty;
        }
    }

    /**
     * Empty the entire cart
     *
     * @return void
     */
    public function empty_cart() {
        unset($_SESSION['items']);
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