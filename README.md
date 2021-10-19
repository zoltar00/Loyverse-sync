# WooCommeerce-plugin
This plugin Syncs data from Loyverse to Woocommerce.

Prerequistites:
- A free loyverse account (For information about creating an account goto https://loyverse.com)
- An access token in Loyverse to be able to access the API (This is a payable feature, check on the Loyverse Site for pricing)
- Woocommerce version 5.8.0 minimum
- A woocommerce API Token (For intructions: https://docs.woocommerce.com/document/woocommerce-rest-api/)
- The plugin uses automattic for Woocommerce: https://packagist.org/packages/automattic/woocommerce

The plugin will use a custom database to store some information used for the synchronization.
Normally there is no Access to configure. If the database is not present the plugin will create it for you.

The plugin synchronizes Categories and Items from Loyverse to Woocommerce. In Woocommerce only SIMPLE products are created.
The images for the products are not synched they are a link to the images stored in Loyverse.
