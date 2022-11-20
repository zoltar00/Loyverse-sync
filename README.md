# Loyverse Sync
This plugin Syncs data from Loyverse to Woocommerce.

Prerequistites:
- A free loyverse account (For information about creating an account goto https://loyverse.com)
- An access token in Loyverse to be able to access the API (This is a payable feature, check on the Loyverse Site for pricing)
- Woocommerce version 5.8.0 minimum (The plugin will not activate if Woocommerce is not installed and activated)
- A woocommerce API Token (For intructions: https://docs.woocommerce.com/document/woocommerce-rest-api/). Make sure that the permissions are read/write

![image](https://user-images.githubusercontent.com/32526436/138573861-b4eab01e-9cb2-44c0-a8dc-3d36535dc600.png)

- The plugin uses automattic for Woocommerce: https://packagist.org/packages/automattic/woocommerce
- Featured Image by URL (This must be installed and activated. The plugin will not activate if this plugin is not active. More details on the plugin: https://wordpress.org/plugins/featured-image-by-url/)

The plugin only sets the confgiuration for the API's tokens,...
All the synchronization is done in Azure and is shared amoung customers.
The plugin uses the native webhooks in Loyverse and Woocommerce to send items and to update stock
These webhooks are automatically created when you save the settings in Woocommerce

The process of synchronization is as follows
whenever an item is created, modified or deleted in Loyverse it is automatically (usually in 1 minute) the item will be created updated or deleted in Woocommerce. ATTN: if you modifiy anything in woocommerce it will be overwritten if you change it in Loyverse.

For the categories there is no webhook however you can choose to synchronize them to Woocommerce by checking the "Synchronize categories" checkbox on the settings page.
The synchronization is on a timer which executes every 5 minutes, so do not be worried if the categories are not synched straight away. However it is a good practive to uncheck "Synchronize categories" when done, in order not to overload the APIs.

The plugin synchronizes Categories and Items from Loyverse to Woocommerce. In Woocommerce only SIMPLE products are created.
The images for the products are not synched they are a link to the images stored in Loyverse.

Installation:
- Goto the releases page (https://github.com/zoltar00/Loyvserse-sync/releases) and download the lastest release)
- Import the zip file in Wordpress.
- Activate the plugin prerequisites
- Activate the plugin
- Configure the plugin on the settings page.


The Synchronization is done for now only in one way: Loyverse to Woocommerce. The bidirectionnal synchronization will be done in a futur release.
The synchronization checks if the item/category has been deleted from Woocommerce and if this is the case puts it back. Also the synchronization checks whether the item (product) is i nthe Woocommerce trash, if it is then the item will be restored.


