<?php
/**
* Plugin Name: Loyverse-Sync
* Plugin URI: https://mammami1.mimlab.ch/
* Description: Synching of Loyverse POS
* Version: 1.1
* Author: Sylvan Laurence
* Author URI: https://slcservices.ch/
**/

if (! defined('ABSPATH')) {
    die('Don\'t call this file directly.');
}
$autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( is_readable( $autoloader ) ) {
	require_once $autoloader;
}

use Automattic\WooCommerce\Client;

function lvsync_create_menu() {

    add_management_page('Loyverse sync', 'Loyverse sync', 'manage_options', 'loyverse-sync', 'loyverse_sync');
}

add_action('admin_menu', 'lvsync_create_menu');


function loyverse_sync(){

    echo "Starting sync...";

	$loyverse_items = [];

	/**Connect to WooCommerce */

    echo "Connecting to Woocommerce API...";

	$woocommerce = new Client(
		'https://mammamia.mimlab.ch',
		'ck_99b4d2a4d51cad847b882430b5406619528b8922',
		'cs_ce509e4cb542d9dbfcba19c538961df957780290',
		[
			'wp_api' => true,
			'version' => 'wc/v3'
		]
	);

   echo "Connected to Woocommerce API...";

 echo "";

 echo "Getting categories from Loyverse...";
 echo "";

 /** Connect to Loyverse */
 $token = '8a9f63253d6c41e294e8f67d8ebcadea'; 
 $responsecategories = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/categories', array(
     'headers' => array(
         'Authorization' => 'Bearer ' . $token
     ),
 )));
 $data = json_decode($responsecategories,true);
 
 $loyverse_categories[] = $data;
 foreach($loyverse_categories[0] as $loyverse_category){
     
     foreach($loyverse_category as $category){

         $loyverse_category_slug = $category['name'];

         /** Create stuff for woocommerce product */
         $prod_data = [
             'name' => $loyverse_category_slug
         ];

         $msg = "Sending category ". $loyverse_category_slug ." to woocommerce...";
         echo $msg;

         $woocommerce->post( 'products/categories', $prod_data );
     }
 
 }    
    echo "Connecting to Loyverse API...";

	/** Connect to Loyverse */
	$token = '8a9f63253d6c41e294e8f67d8ebcadea'; 
	$response = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/items', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $token
		),
	)));

   echo "Connected to Loyverse API...";

	$data = json_decode($response,true);

	if( ! is_array($data) || empty($data)){

		return false;
		error_log ("Not an Array!");
	}

	$loyverse_items[] = $data;

   echo "Got all Items...";

	foreach($loyverse_items[0] as $loyverse_item){

		foreach($loyverse_item as $item){

			$loyverse_item_slug = sanitize_title($item['item_name']); 

			foreach($item['variants'] as $variants){
						
                /** Create stuff for woocommerce product */
            $prod_data = [
                    'name'          => $loyverse_item_slug,
                    'type'          => 'simple',
                    'regular_price' => $price,
                    'sku' => $variants['sku'],
                    'description'   => $loyverse_item_slug,
                    'categories'    => [
                        [
                            'id' => 17,
                        ],
                    ],
                ];

               $msg = "Sending item ". $loyverse_item_slug ." to woocommerce...";
               echo $msg;
               
               /**Send to WooCommerce */
                $woocommerce->post( 'products', $prod_data );
            }
        }
    }



    echo "Done importing!";
}

?>