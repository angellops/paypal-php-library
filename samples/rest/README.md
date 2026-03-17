# PayPal REST API PHP Samples

This directory contains PHP sample scripts demonstrating how to use various PayPal API operations through the REST SDK.  
Each method is a standalone example showing how to make authenticated requests, send payloads, and handle responses for different PayPal services such as payments, invoicing, recurring billing, and account management.

**Note:**  
All required arguments, headers, and payload data are defined and passed within each respective file.  
You can inspect each file individually to view the parameters and payload structure used in that API call.

---

### **AddBankAccount.php**
Demonstrates how to link a bank account to a PayPal account.

---

### **AddPaymentCard.php**
Shows how to add a credit or debit card to a PayPal account.

---

### **BMButtonSearch.php**
Retrieves a list of hosted PayPal buttons based on specific search criteria.

---

### **BMCreateButton.php**
Creates a new PayPal payment button using the Button Manager API.

---

### **BMGetButtonDetails.php**
Fetches detailed information for a specific hosted PayPal button.

---

### **BMGetInventory.php**
Retrieves inventory details associated with a PayPal hosted button.

---

### **BMManageButtonStatus.php**
Updates the status (e.g., activate or delete) of a PayPal hosted button.

---

### **BMSetInventory.php**
Sets or updates inventory tracking for hosted button items.

---

### **BMUpdateButton.php**
Updates configuration or parameters of an existing hosted button.

---

### **CancelInvoice.php**
Cancels an existing PayPal invoice that has not been paid.

---

### **ConvertCurrency.php**
Converts an amount between two currencies using PayPal’s exchange rate.

---

### **CreateAccount.php**
Creates a new PayPal personal or business account via the API.

---

### **CreateAndSendInvoice.php**
Creates an invoice and sends it immediately to the customer.

---

### **CreateInvoice.php**
Creates a draft invoice that can be sent later.

---

### **CreateRecurringPaymentsProfile.php**
Creates a recurring billing profile for automatic payments.

---

### **DeleteInvoice.php**
Deletes a draft invoice from the merchant’s account.

---

### **DoDirectPayment.php**
Performs a direct payment using credit card details (no PayPal redirection).

---

### **DoExpressCheckoutPayment.php**
Completes the Express Checkout process after buyer approval.

---

### **DoExpressCheckoutPayment-Callback.php**
Handles callback responses after an Express Checkout payment is completed.

---

### **DoExpressCheckoutPayment-RedeemedOffers.php**
Processes Express Checkout payments that include redeemed offers.

---

### **DoReferenceTransaction.php**
Performs a payment using a stored billing agreement or reference transaction ID.

---

### **ExecutePayment.php**
Executes an approved PayPal payment after user authorization.

---

### **FinancingBannerEnrollment.php**
Handles merchant enrollment for PayPal’s financing banner program.

---

### **GetAccessToken.php**
Retrieves an OAuth 2.0 access token for authenticating API calls.

---

### **GetAdvancedPersonalData.php**
Retrieves advanced account or personal data for an authenticated user.

---

### **GetBalance.php**
Fetches the current account balance across all available currencies.

---

### **GetBasicPersonalData.php**
Retrieves basic PayPal account information for an authenticated user.

---

### **GetInvoiceDetails.php**
Retrieves details and line items for a specific invoice.

---

### **GetPalDetails.php**
Fetches account details related to a PayPal merchant or business.

---

### **GetPaymentOptions.php**
Retrieves available payment options for a given transaction or checkout.

---

### **GetRecurringPaymentsProfileDetails.php**
Retrieves information about an existing recurring payment profile.

---

### **GetShippingAddresses.php**
Retrieves saved shipping addresses associated with a PayPal buyer.

---

### **GetTransactionDetails.php**
Retrieves full details for a specific PayPal transaction.

---

### **GetVerifiedStatus.php**
Checks whether a PayPal account is verified or unverified.

---

### **ManageRecurringPaymentsProfileStatus.php**
Suspends, cancels, or reactivates a recurring payment profile.

---

### **MarkInvoiceAsPaid.php**
Marks an invoice as paid (for manual/offline payments).

---

### **MarkInvoiceAsRefunded.php**
Marks an invoice as refunded.

---

### **MarkInvoiceAsUnpaid.php**
Marks a previously paid invoice as unpaid.

---

### **MassPay.php**
Sends payments to multiple recipients in a single API request.

---

### **Pay-Chained.php**
Executes a chained payment where multiple receivers receive portions of the total amount.

---

### **Pay-Preapproval.php**
Processes a payment using an existing preapproval agreement.

---

### **Pay.php**
Executes a direct PayPal payment between sender and receiver.

---

### **PayFlowTransaction.php**
Handles payment transactions through the PayFlow gateway system.

---

### **PaymentDetails.php**
Retrieves details of a specific PayPal payment transaction.

---

### **PayWithOptions.php**
Executes a payment with multiple funding options (PayPal, card, etc.).

---

### **Preapproval.php**
Creates a preapproval agreement to authorize future payments automatically.

---

### **PreapprovalDetails.php**
Retrieves details of a specific preapproval agreement.

---

### **Refund.php**
Performs a refund for a specific PayPal payment or transaction.

---

### **RefundTransaction.php**
Issues a refund for a completed transaction using its transaction ID.

---

### **RemindInvoice.php**
Sends a payment reminder email for an outstanding invoice.

---

### **RequestPermissions.php**
Requests permission from a PayPal user to access specific account data or actions.

---

### **SearchInvoices.php**
Searches invoices based on filters such as status or date range.

---

### **SendInvoice.php**
Sends an already created invoice to a recipient.

---

### **SetCustomerBillingAgreement.php**
Creates a billing agreement between a customer and merchant.

---

### **SetExpressCheckout-Callback.php**
Handles callback after setting up an Express Checkout session.

---

### **SetExpressCheckout.php**
Initiates the Express Checkout flow for a PayPal payment.

---

### **SetMobileCheckout.php**
Starts a mobile-friendly checkout session.

---

### **SetPaymentOptions.php**
Sets additional payment-related options, such as shipping or recurring details.

---

### **TransactionSearch.php**
Searches for transactions within a specific time period using various filters.

---

### **UpdateAuthorization.php**
Updates or reauthorizes a previously held payment authorization.

---

### **UpdateInvoice.php**
Updates details for an existing PayPal invoice.

---

### **UpdateRecurringPaymentsProfile.php**
Updates fields or parameters of an existing recurring payment profile.

---

## Notes

- All samples are located in the `/samples/rest/` directory.  
- Each file demonstrates one complete PayPal API flow.  
- **All required arguments, headers, and payload data are passed directly in the sample files.**  
- The examples can be adapted for sandbox or live environments by changing credentials in your configuration.  
- These methods are intended for developers to test, learn, and integrate PayPal REST APIs.

---

**Directory Path:**  
`samples/rest/`