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
use Automattic\WooCommerce\HttpClient\HttpClientException;

function lvsync_create_menu() {

    add_management_page('Loyverse sync', 'Loyverse sync', 'manage_options', 'loyverse-sync', 'loyverse_sync');
}

add_action('admin_menu', 'lvsync_create_menu');


function loyverse_sync(){

    $msg="Starting sync...";
    echo '<pre>'; 
    print_r($msg);
    echo '</br>'; 

	$loyverse_items = [];

	/**Connect to WooCommerce */

    $msg="Connecting to Woocommerce API...";
    echo '<pre>'; 
    print_r($msg);
    echo '</br>'; 

	$woocommerce = new Client(
		'https://mammamia.mimlab.ch',
		'ck_99b4d2a4d51cad847b882430b5406619528b8922',
		'cs_ce509e4cb542d9dbfcba19c538961df957780290',
		[
			'wp_api' => true,
			'version' => 'wc/v3',
            'verify_ssl' => false
		]
	);

   $msg="Connected to Woocommerce API...";
   echo '<pre>'; 
   print_r($msg);
   echo '</br>';    

 $msg="Getting categories from Loyverse...";
 echo '<pre>'; 
 print_r($msg);
 echo '</br>'; 

 /** Connect to Loyverse */
 $token = '8a9f63253d6c41e294e8f67d8ebcadea'; 
 $responsecategories = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/categories', array(
     'headers' => array(
         'Authorization' => 'Bearer ' . $token
     ),
 )));
 $data = json_decode($responsecategories,true);
 
 $loyverse_categories[] = $data;


 $all_categories = $woocommerce->get('products/categories');
 $woocat = (array) $all_categories;
  
 foreach($loyverse_categories[0] as $loyverse_category){
     
     foreach($loyverse_category as $category){

         $loyverse_category_slug = sanitize_title($category['name']);

         /** Check if data already in Woocommerce */
         $found = 0;

         foreach($woocat as $cat){

 
                    if($cat->slug===$loyverse_category_slug){

                    $msg = "Category ". $category['name'] ." already exists.";
                    echo '<pre>'; 
                    print_r($msg);
                    echo '</br>';                      
                    $found = $found + 1;

                    break;
            }

         }
         

         if($found == 0)
         {

           /**   Create stuff for woocommerce product */
            $prod_data = [
               'name' => $category['name']
            ];

             $msg = "Sending category ". $category['name'] ." to woocommerce...";
             echo '<pre>'; 
             print_r($msg);
             echo '</br>'; 

            $woocommerce->post( 'products/categories', $prod_data );

         }

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

   $msg="Connected to Loyverse API...";
   echo '<pre>'; 
   print_r($msg);
   echo '</br>';    

	$data = json_decode($response,true);

	if( ! is_array($data) || empty($data)){

		return false;
		error_log ("Not an Array!");
	}

	$loyverse_items[] = $data;

   $msg="Got all Items...";
   echo '<pre>'; 
   print_r($msg);
   echo '</br>'; 
/**
*	foreach($loyverse_items[0] as $loyverse_item){

*		foreach($loyverse_item as $item){

*			$loyverse_item_slug = sanitize_title($item['item_name']); 

*			foreach($item['variants'] as $variants){
						
 *               /** Create stuff for woocommerce product */
  /**           $prod_data = [
  *                  'name'          => $loyverse_item_slug,
  *                  'type'          => 'simple',
  *                  'regular_price' => ['price'],
  *                  'sku' => $variants['sku'],
  *                  'description'   => $loyverse_item_slug,
  *                  'categories'    => [
  *                      [
  *                          'id' => 17,
  *                      ],
  *                  ],
   *             ];

   *            $msg = "Sending item ". $loyverse_item_slug ." to woocommerce...";
   *            echo $msg;
   *            echo '<pre>'; 
   *            print_r($msg);
   *            echo '</br>'; 

   *            /**Send to WooCommerce 
   *             $woocommerce->post( 'products', $prod_data );*/
   /**          }
    *    }
    *}*/



    $msg= "Done importing!";
    echo $msg;
    echo '<pre>'; 
    print_r($msg);
    echo '</br>'; 
}

?>