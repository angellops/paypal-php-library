<?php

namespace angelleye\PayPal;

/**
 *  An open source PHP library written to easily work with PayPal's API's
 *
 *  Email:  service@angelleye.com
 *  Facebook: angelleyeconsulting
 *  Twitter: angelleye
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @package         paypal-php-library
 * @author          Andrew Angell <service@angelleye.com>
 * @link            https://github.com/angelleye/paypal-php-library/
 * @website         http://www.angelleye.com
 * @support         http://www.angelleye.com/product/premium-support/
 * @version         v3.0.5
 * @filesource
*/

/**
 * Primary Abstract PayPal Class
 *
 * This is the parent PayPal class that all child classes extend.
 *
 * @package         paypal-php-library
 * @author          Andrew Angell <service@angelleye.com>
 */
class PayPal
{
    protected array $config;
    protected string $mode;
    protected string $payPalAPIMode; // 'classic' or 'rest'
    protected bool $Sandbox = true;
    protected ?string $LogPath = null;

    public function __construct(array $config = [])
    {
	$this->config = $config;
        $this->mode = isset($config['Sandbox']) ? $config['Sandbox'] : 'live';
        $this->payPalAPIMode = isset($config['PayPalAPIMode']) ? $config['PayPalAPIMode'] : 'classic';
        $this->Sandbox = ($this->mode === 'sandbox') ? true : false;
        $this->LogPath = isset($config['LogPath']) ? $config['LogPath'] : $_SERVER['DOCUMENT_ROOT'].'/logs/';
    }

    /**
     * Factory method to instantiate the correct API mode
     */
    public static function init(array $config = [])
    {
        $apiMode = isset($config['api_mode']) ? $config['api_mode'] : 'classic';

        switch (strtolower($apiMode)) {
            case 'rest':
                return new PayPalREST($config);
            case 'classic':
                return new PayPalClassic($config);
            default:
                throw new \InvalidArgumentException("Invalid PayPal API mode: $apiMode");
        }
    }

    /**
     * Common interface that both Classic and REST must implement
     */
//     abstract public function SetExpressCheckout(array $params);
//     abstract public function GetExpressCheckoutDetails($token);
//     abstract public function DoExpressCheckoutPayment(array $params);
//     abstract public function DoCapture(array $params);
//     abstract public function DoAuthorization(array $params);
//     abstract public function DoVoid(array $params);
//     abstract public function DoDirectPayment(array $params);
//     abstract public function RefundTransaction(array $params);
//     abstract public function orderData(array $params);
//     abstract public function getOrder(int $orderID);
//     abstract public function authorizeOrder(int $orderID);
//     abstract public function captureOrder(int $orderID);
}