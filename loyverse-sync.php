<?php
/**
* Plugin Name: Loyverse-Sync
* Plugin URI: https://github.com/zoltar00/Loyverse-sync/
* Description: Synching of Loyverse POS to Woocommerce
* Version: 2.0.7
* Author: Galaxeos SàRL
* Author URI: https://galaxeos.net/
**/
error_reporting (E_ALL ^ E_NOTICE);

if (! defined('ABSPATH')) {
    die('Don\'t call this file directly.');
}
$autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( is_readable( $autoloader ) ) {
	require_once $autoloader;
}

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

require_once( ABSPATH . 'wp-admin/includes/plugin.php');
/*Check if Plugin is active */
if( is_plugin_active( 'featured-image-by-url/featured-image-by-url.php' ) ) {
	// Plugin is active
 }
 else
 {

    ?>        
        <pre> Plugin Featured Image by URL is not installed or active. Please activate it or install it. Instructions are <a href='https://wordpress.org/plugins/featured-image-by-url/'>here.</a></pre>
    <?php  
    exit;
 }

if( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	// Plugin is active
 }
 else
 {

    ?>        
        <pre> Plugin WooCommerce is not installed or active. Please activate it or install it. Instructions are <a href='https://wordpress.org/plugins/woocommerce/'>here.</a></pre>
    <?php 
    exit;

 }

/*Auto-update plugin */

require_once('plugin-update-checker/plugin-update-checker.php');
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/zoltar00/Loyverse-sync/',
	__FILE__, //Full path to the main plugin file or functions.php.
	'loyverse-sync'
);
//$myUpdateChecker->setAuthentication('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');
$myUpdateChecker->setBranch('release');

/*Update database requirement */

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

Class LoyverseSyncPlugin {

function __construct(){

        add_action('admin_menu', array($this,'adminPage'));
        add_action( 'admin_init', array($this,'settings'));
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_settings_link' ) );
       // add_action( 'lvs_cron', array($this,'loyverse_sync' ));

        /*add_action('admin_menu', 'lvsync_create_menu');*/

    }

function plugin_settings_link($links) {
        $url = get_admin_url() . 'options-general.php?page=loyverse-sync-settings-page';
        $settings_link = '<a href="'.$url.'">' . __( 'Settings', 'textdomain' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

function settings(){

        add_settings_section('lsp_first_section','Connectivity',null,'loyverse-sync-settings-page');
        add_settings_section('lsp_second_section','Synchronization',null,'loyverse-sync-settings-page');
        add_settings_section('lsp_third_section','Licensing',null,'loyverse-sync-settings-page');
        add_settings_section('lvp_first_section',null,null,'loyverse-sync-log');
        
        add_settings_field('lvs_lvtoken','Loyverse API Token',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_lvtoken'));
        register_setting('loyversesyncplugin','lvs_lvtoken',array('sanitize_callback' =>'sanitize_text_field','default'=>''));

        add_settings_field('lvs_wckey','Woocommerce API Key',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_wckey'));
        register_setting('loyversesyncplugin','lvs_wckey',array('sanitize_callback' =>'sanitize_text_field','default'=>''));

        add_settings_field('lvs_wcsecret','Woocommerce API Secret',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_wcsecret'));
        register_setting('loyversesyncplugin','lvs_wcsecret',array('sanitize_callback' =>'sanitize_text_field','default'=>''));

        add_settings_field('lvs_license','License key',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_third_section', array('theName' => 'lvs_license'));
        register_setting('loyversesyncplugin','lvs_license',array('sanitize_callback' =>'sanitize_text_field','default'=>''));
        
        add_settings_field('lvs_cat','Synchronize categories',array($this,'checkHTM'),'loyverse-sync-settings-page','lsp_second_section', array('theName' => 'lvs_cat'));
        register_setting('loyversesyncplugin','lvs_cat',array('sanitize_callback' =>'sanitize_text_field','default'=>'0'));

        add_settings_field('lvs_log_prod','Products',array($this,'checkHTM'),'loyverse-sync-log','lvp_first_section', array('theName' => 'lvs_log_prod'));
        register_setting('loyversesyncplugin','lvs_log_prod',array('sanitize_callback' =>'sanitize_text_field','default'=>'0'));

        add_settings_field('lvs_log_cat','Categories',array($this,'checkHTM'),'loyverse-sync-log','lvp_first_section', array('theName' => 'lvs_log_cat'));
        register_setting('loyversesyncplugin','lvs_log_cat',array('sanitize_callback' =>'sanitize_text_field','default'=>'0'));

    }

function inputHTM($args){ ?>

            <input type="text" name="<?php echo $args['theName'] ?>" value="<?php echo esc_attr(get_option($args['theName'])) ?>"></input>

        <?php 
    }
function checkHTM($args){ ?>

    <input type="checkbox" name="<?php echo $args['theName'] ?>" value="1" <?php checked(get_option($args['theName']),'1') ?> ></input>

    <?php 
 }
function adminPage(){

       add_options_page('Loyverse Sync Settings','Loyverse Settings','manage_options','loyverse-sync-settings-page',array($this,'ourHTML'));
       // add_management_page('Loyverse sync', 'Loyverse sync', 'manage_options', 'loyverse-sync', array($this,'loyverse_sync'));
        add_management_page('Loyverse sync log', 'Loyverse sync log', 'manage_options', 'loyverse-sync-log', array($this,'loyverse_sync_log'));
    
    }

function loyverse_sync_log(){
    
        global $wpdb;

        //Put checkboxes on page
        $lvs_lvtoken=get_option('lvs_lvtoken');
        $lvs_wckey=get_option('lvs_wckey');
        $lvs_wcsecret=get_option('lvs_wcsecret');
        $lvs_cat=get_option('lvs_cat');
        $lvs_license=get_option('lvs_license');

        ?>

        <div class ="wrap">
            <h1>Loyverse Sync Logs</h1>
            <pre>Please select which kind of logs you would like to get. Check products or categories, select a date and press "Search Logs".</pre>
            <form action="options.php" method="POST"> 
            <input type="hidden" name="lvs_lvtoken" value="<?php echo $lvs_lvtoken ?>"></input> 
            <input type="hidden" name="lvs_wckey" value="<?php echo $lvs_wckey ?>"></input>
            <input type="hidden" name="lvs_wcsecret" value="<?php echo $lvs_wcsecret ?>"></input>
            <input type="hidden" name="lvs_cat" value="<?php echo $lvs_cat ?>"></input>
            <input type="hidden" name="lvs_license" value="<?php echo $lvs_license ?>"></input>
                <?php
                    settings_fields('loyversesyncplugin');
                    do_settings_sections('loyverse-sync-log');
                    submit_button("Search logs");
            ?>
            </form>
        </div>
            
            <pre><table>
            <tr><th style="text-align: center; vertical-align: middle;">Time Stamp</th><th style="text-align: center; vertical-align: middle;">Script</th><th style="text-align: center; vertical-align: middle;">Description</th></tr>
            
        <?php    
  
        $prods=get_option('lvs_log_prod');
        $cat=get_option('lvs_log_cat');

        if(($prods == '1') OR ($cat == '1')){
        $merchant_id = get_transient( 'Merchant_id' );
    
        if ( false === $merchant_id ) {        
        //Get merchant Id from Loyverse
        $merchurl = 'https://api.loyverse.com/v1.0/merchant/';
        $response = wp_remote_retrieve_body(wp_remote_get($merchurl, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . get_option('lvs_lvtoken')
            ),
        )));
        $data = json_decode($response,true);
        if($data['errors']['0']['code'] == "UNAUTHORIZED" ){

            ?>    
                <pre> Cannot retrieve Merchant ID from Loyverse. Please verify the Loyverse API Token.</pre>
                <pre><strong>Error Message: <?php echo $data['errors']['0']['code'] ?> ,<?php echo $data['errors']['0']['details'] ?></strong></pre>    
        <?php 
        exit();
        }else{

            $merchant_id = $data['id'];
            set_transient( 'Merchant_id', $merchant_id, 60 * 60 );

        }
    }
        # Connect to Azure Function apilogs
        $logsurl = 'https://sync.galaxeos.net/api/apilogs';
        $FunctionKey = "8joGg9qSqtyjCvG6rZb6-Iw4uKfZJgi0Ile-C9kRAOVyAzFu_wwRpw==";
        //Get all logs
        $body = array(
            'merchant_id'   => $merchant_id
        );
    
        $args = array(
            'headers' => array(
                'x-function-key'=> $FunctionKey,
                'Content-Type' => 'application/json'
            ),
            'body'        => json_encode($body),
            'timeout' => 60,
        );
    
        $response = wp_remote_post($logsurl,$args);
        
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo '<pre>';
            echo "Something went wrong: $error_message";
            echo '</pre>';
            exit();
         } else {
            
            $data = json_decode(wp_remote_retrieve_body($response), true);
            //print_r($data);

        //Filter on products of categories
        
        
        if(filter_has_var(INPUT_POST,'products')) {
            foreach($data as $item){
                $time = $item['TableTimestamp'];
                $script = $item['script'];
                $description = $item['description'];
                
                if(($script == "products") && ($time == filter_has_var(INPUT_POST,'logdate'))){
                    ?><tr><td style="text-align: center; vertical-align: middle;">
                    <?php
                    echo $time;
                    ?></td>
                    <td style="text-align: center; vertical-align: middle;">
                    <?php
                    echo $script;
                    ?></td><td style="text-align: center; vertical-align: middle;">
                    <?php
                    echo $description;
                    ?></td></tr>
                    <?php

                } // End IF

            } // End Foreach                    

        }elseif(filter_has_var(INPUT_POST,'categories')){

            foreach($data as $item){
                $time = $item['TableTimestamp'];
                $script = $item['script'];
                $description = $item['description'];
                
                if(($script == "categories") && ($time == filter_has_var(INPUT_POST,'logdate'))){
                    ?><tr><td style="text-align: center; vertical-align: middle;">
                    <?php
                    echo $time;
                    ?></td>
                    <td style="text-align: center; vertical-align: middle;">
                    <?php
                    echo $script;
                    ?></td><td style="text-align: center; vertical-align: middle;">
                    <?php
                    echo $description;
                    ?></td></tr>
                    <?php

                } //End IF

            } //End Foreach

        }

        ?>
                </table></pre>
                
        <?php   
  
        if($data == ""){
            ?>
                <pre><strong>No logs to show!</strong></pre>
            <?php 
        } //End if
         }
    } // End Else if
        
 } //End function
 
function ourHTML(){ ?>
    
        <div class ="wrap">
            <h1>Loyverse Sync Settings</h1>
            <form action="options.php" method="POST">
                <?php              
                    settings_fields('loyversesyncplugin');
                    do_settings_sections('loyverse-sync-settings-page');
                    submit_button();
                ?>

            </form>
        </div>
        <?php 

        if ( $_GET['settings-updated'] == 'true' ) { 
            ?>    
            <pre> Checking Azure.</pre>    
    <?php              

        $this->CallAzure();

  }

 }
 function CallAzure(){

    $url = site_url();
    // $wcwebhookurl="https://sync.galaxeos.net/api/WCItems";
     
     $lvitemswebhook ="https://sync.galaxeos.net/api/products";
     $lvstockwebhook = "https://sync.galaxeos.net/api/stock";
 
    // $bodywcwh = array(
     //    'name'    => 'Create Product',
    //     'topic'   => 'product.created',
    //     'delivery_url' => $wcwebhookurl
    // );
     $bodylvwhitems = array(
         'url'    => $lvitemswebhook,
         'type'   => 'items.update',
         'status' => 'ENABLED'
     );
     $bodylvwhstock = array(
         'url'    => $lvstockwebhook,
         'type'   => 'inventory_levels.update',
         'status' => 'ENABLED'
     );
 
     
     //print_r($url);
 
     global $wpdb;
 
     $loyverse_token = get_option('lvs_lvtoken','1'); 
     $WC_user = get_option('lvs_wckey','1');
     $WC_Secret = get_option('lvs_wcsecret','1');
     $sync_cat = get_option('lvs_cat');
     $license = get_option('lvs_license','1');
 
     ?>    
              <pre> Getting Merchant Id from Loyverse.</pre>    
     <?php 
 
     //Get cached merchant_id
     $merchurl = 'https://api.loyverse.com/v1.0/merchant/';
     
     $merchant_id = get_transient( 'Merchant_id' );
     
     if ( false === $merchant_id ) {
         // Transient expired, refresh the data
         $response = wp_remote_retrieve_body(wp_remote_get($merchurl, array(
             'headers' => array(
                 'Authorization' => 'Bearer ' . get_option('lvs_lvtoken')
             ),
         )));
         $data = json_decode($response,true);
         if($data['errors']['0']['code'] == "UNAUTHORIZED" ){
     
             ?>    
                  <pre> Cannot retrieve Merchant ID from Loyverse. Please verify the Loyverse API Token.</pre>
                  <pre><strong>Error Message: <?php echo $data['errors']['0']['code'] ?> ,<?php echo $data['errors']['0']['details'] ?></strong></pre>    
         <?php 
         exit();
         }else{
     
             $merchant_id = $data['id'];
            
             set_transient( 'Merchant_id', $merchant_id, 60 * 60 );
 
         }
         
     }
 
     $settingsurl = 'https://sync.galaxeos.net/api/settings';
     $FunctionKey = "iEHg3VSq8yHM0n_J4lPNXnmAnRqSH2oHvX-IO5o6gBiIAzFu0KLgkA==";
  
     // Check if already exists in Azure
     $body = array(
         'operation'    => 'read',
         'merchant_id'   => $merchant_id,
         'license' => $license,
     );
 
     $args = array(
         'headers' => array(
             'x-function-key'=> $FunctionKey,
             'Content-Type' => 'application/json'
         ),
         'body'        => json_encode($body),
         'timeout' => 60,
     );
 
 
     #Send Merchant to Azure
     $response = wp_remote_post($settingsurl,$args);
         
         if ( is_wp_error( $response ) ) {
             $error_message = $response->get_error_message();
             echo '<pre>';
             echo "Something went wrong: $error_message";
             echo '</pre>';
             exit();
         } else {
             
             $data = wp_remote_retrieve_body($response); 
    

             if(($data == "License is not valid. Please enter a valid license.") || ($data == "License is empty. Please enter a license.") || ($data == "License has expired. Please renew")){
                ?>
                <pre> <?php echo $data ?></pre>
    
                 <?php    

                exit();
             }else{

                $merchant_settings = $data;
             }
         }
     
      # if there is a merchant
     if($merchant_settings){
          
  
         ?>    
              <pre> Merchant already exists in Azure. Updating information.</pre> 
              <pre><strong>If needed refresh page to update the values.</strong></pre>
          <?php 
 
         $body = array(
             'operation'    => 'update',
             'merchant_id'   => $merchant_id,
             'loyverse_secret' => $loyverse_token,
             'wc_username' => $WC_user,
             'wc_secret' => $WC_Secret,
             'sync_cat' => $sync_cat,
             'site_url' => $url,
             'license' => $license,
         );
 
         $args = array(
             'headers' => array(
                 'x-function-key'=> $FunctionKey,
                 'Content-Type' => 'application/json'
             ),
             'body'        => json_encode($body),
             'timeout' => 60,
         );
     
         $response = wp_remote_post($settingsurl,$args);
 
         if ( is_wp_error( $response ) ) {
             $error_message = $response->get_error_message();
             echo '<pre>';
             echo "Something went wrong: $error_message";
             echo '</pre>';
             exit();
         } else {
  
             $data = json_decode(wp_remote_retrieve_body($response), true);
         
      }
 
     }
     else{
 
         ?>    
              <pre> No Merchant found. Saving information.</pre>
          <?php 
 
     // Send to Azure information
     $body = array(
         'operation'    => 'insert',
         'merchant_id'   => $merchant_id,
         'loyverse_secret' => $loyverse_token,
         'wc_username' => $WC_user,
         'wc_secret' => $WC_Secret,
         'sync_cat' => $sync_cat,
         'site_url' => $url,
         'license' => $license,
     );
 
     $args = array(
         'headers' => array(
             'x-function-key'=> $FunctionKey,
             'Content-Type' => 'application/json'
         ),
         'body'        => json_encode($body),
         'timeout' => 60,
     );
 
     $response = wp_remote_post($settingsurl,$args);
     if ( is_wp_error( $response ) ) {
         $error_message = $response->get_error_message();
         echo '<pre>';
         echo "Something went wrong: $error_message";
         echo "Please try again to save!";
         echo '</pre>';
         exit();
     }
     else{
 
     ?>    
              <pre> Information saved.</pre>
          <?php 
 
             # Configure Webhooks
             ?>    
                 <pre> Configuring Webhooks.</pre>
             <?php 
         
             $woocommerce = new Client(
                 $url,
                 $WC_user,
                 $WC_Secret,
                 [
                     'wp_api' => true,
                     'version' => 'wc/v3'
                 ]
             );
 
             # Items Webhook
 
             ?>    
                 <pre> Configuring Loyverse Items Webhook.</pre>
             <?php 
 
             $responseitemswebhooks = wp_remote_post('https://api.loyverse.com/v1.0/webhooks/', array(
                 'headers' => array(
                     'Authorization' => 'Bearer ' . $loyverse_token,
                     'Content-Type' => 'application/json'
                 ),
                 'blocking' => true,
                 'body'        => json_encode($bodylvwhitems),
                 'timeout' => 60,
                 
             ));
             
             $response = json_decode(wp_remote_retrieve_body($responseitemswebhooks), TRUE);
             
             
             //print_r($responseitemswebhooks);
 
             if ( is_wp_error( $response ) ) {
                 $error_message = $response->get_error_message();
                 echo '<pre>';
                 echo "Something went wrong: $error_message";
                 echo "Please try again to save!";
                 echo '</pre>';
                 exit();
             }
             else{
         
             ?>    
                      <pre> Loyverse Items Webhook Created.</pre>
                  <?php 
             }
 
             # Stock Webhook
             
             ?>    
                 <pre> Configuring Loyverse Stock Webhook.</pre>
             <?php 
 
             $responsestockwebhooks = wp_remote_post('https://api.loyverse.com/v1.0/webhooks/', array(
                 'headers' => array(
                     'Authorization' => 'Bearer ' . $loyverse_token,
                     'Content-Type' => 'application/json'
                 ),
                 'blocking' => true,
                 'body'        => json_encode($bodylvwhstock),
                 'timeout' => 60,
                 
             ));
             
             $response = json_decode(wp_remote_retrieve_body($responsestockwebhooks), TRUE);
             
             
             //print_r($responseitemswebhooks);
 
             if ( is_wp_error( $response ) ) {
                 $error_message = $response->get_error_message();
                 echo '<pre>';
                 echo "Something went wrong: $error_message";
                 echo "Please try again to save!";
                 echo '</pre>';
                 exit();
             }
             else{
         
             ?>    
                      <pre> Loyverse Stock Webhook Created.</pre>
                  <?php 
             }
             ?>    
             <pre> All Webhooks configured.</pre>
         <?php 
 
 
     }
   }
 
  }

}
$loyverseSyncPlugin = new LoyverseSyncPlugin();

?>