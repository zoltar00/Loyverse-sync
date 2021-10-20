# WooCommeerce-plugin
This plugin Syncs data from Loyverse to Woocommerce.

Prerequistites:
- A free loyverse account (For information about creating an account goto https://loyverse.com)
- An access token in Loyverse to be able to access the API (This is a payable feature, check on the Loyverse Site for pricing)
- Woocommerce version 5.8.0 minimum (The plugin will not activate if Woocommerce is not installed and activated)
- A woocommerce API Token (For intructions: https://docs.woocommerce.com/document/woocommerce-rest-api/)
- The plugin uses automattic for Woocommerce: https://packagist.org/packages/automattic/woocommerce
- Featured Image by URL (This must be installed and activated. The plugin will not activate if this plugin is nto active. More details on the plugin: https://wordpress.org/plugins/featured-image-by-url/)

The plugin will use a custom database to store some information used for the synchronization.
Normally there is no Access to configure. If the database is not present the plugin will create it for you.

The plugin synchronizes Categories and Items from Loyverse to Woocommerce. In Woocommerce only SIMPLE products are created.
The images for the products are not synched they are a link to the images stored in Loyverse.

Installation:
- Import the zip file in Wordpress.
- Activate the plugin prerequisites
- Activate the plugin
- Configure the plugin on the settings page.

Operation:
There are 3 main parts.
1) A main Plugin on the plugins page
2) A Log viewer on the Tools menu
3) A manual trigger on the Tools Menu

The Plugin is scheduled to run every minute. You can change the frequency on the cron events page.
The schedule is automatically activated when you actgivate the pluging it also deactivates if you deactivate the plugin.
There is a log viewer (Tools -> Loyverse Sync Log) which refreshes at every sync run. 

You can manually trigger a synchronization by going to Tools -> Loyverse Sync
