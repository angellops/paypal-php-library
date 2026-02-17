jQuery(document).ready(function($) {
    $('#venmoBtn').on('click', async function(e) {
        e.preventDefault();
        
        // Show a loading state
        const $btn = $(this);
        $btn.prop('disabled', true).text('Opening Venmo...');

        try {
            const orderData = await $.ajax({
                url: 'SetExpressCheckout.php',
                type: 'GET',
                data: { paywith: 'venmo' },
                dataType: 'json'
            });

            if (orderData.id) {
                const venmo = paypal.Venmo();

                await venmo.confirm({
                    orderID: orderData.id,
                    returnURL: 'GetExpressCheckoutDetails.php',
                    cancelURL: '/'
                });
            } else {
                alert('Error: Could not retrieve Order ID from server.');
                $btn.prop('disabled', false).text('Pay with Venmo');
            }

        } catch (error) {
            console.error("Venmo Error:", error);
            alert('Failed to launch Venmo. Please try again.');
            $btn.prop('disabled', false).text('Pay with Venmo');
        }
    });
});