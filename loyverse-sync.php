<?php
/**
* Plugin Name: Loyverse-Sync
* Plugin URI: https://github.com/zoltar00/Loyverse-sync/
* Description: Synching of Loyverse POS to Woocommerce
* Version: 2.0.3
* Author: Galaxeos SàRL
* Author URI: https://galaxeos.net/
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

/*Schedule loyverse-sync */
register_activation_hook( __FILE__, 'loyversesync_plugin_activation' );
register_deactivation_hook( __FILE__, 'loyversesync_plugin_deactivation' );

function loyversesync_plugin_activation() {
 /*   
        if ( ! wp_next_scheduled( 'lvs_cron' ) ) {
            wp_schedule_event( time(), 'rsssl_le_five_minutes', 'lvs_cron' );
        }*/
}
    
function loyversesync_plugin_deactivation() {
  /*      $timestamp = wp_next_scheduled( 'lvs_cron' );
        wp_unschedule_event( $timestamp, 'lvs_cron' );*/
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

        add_settings_section('lsp_first_section',null,null,'loyverse-sync-settings-page');
        
        add_settings_field('lvs_lvtoken','Loyverse API Token',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_lvtoken'));
        register_setting('loyversesyncplugin','lvs_lvtoken',array('sanitize_callback' =>'sanitize_text_field','default'=>''));

        add_settings_field('lvs_wckey','Woocommerce API Key',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_wckey'));
        register_setting('loyversesyncplugin','lvs_wckey',array('sanitize_callback' =>'sanitize_text_field','default'=>''));

        add_settings_field('lvs_wcsecret','Woocommerce API Secret',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_wcsecret'));
        register_setting('loyversesyncplugin','lvs_wcsecret',array('sanitize_callback' =>'sanitize_text_field','default'=>''));
        
        add_settings_field('lvs_cat','Synchronize categories',array($this,'checkHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_cat'));
        register_setting('loyversesyncplugin','lvs_cat',array('sanitize_callback' =>'sanitize_text_field','default'=>'0'));

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
      //  add_management_page('Loyverse sync log', 'Loyverse sync log', 'manage_options', 'loyverse-sync-log', array($this,'loyverse_sync_log'));
    
    }
function init_loyverse_sync_log(){

        $file = plugin_dir_path( __FILE__ ) . '/lvs_log.txt';
        if(!unlink($file)){
            echo 'Unable to delete file!';
        }
        else{
            $txt = ""; 
            $open = fopen( $file, "w" ); 
            $write = fputs( $open, $txt );
            fclose($open);
        }
    }

function write_to_loyverse_sync_log($msg){
    
    $file = plugin_dir_path( __FILE__ ) . '/lvs_log.txt';
        date_default_timezone_set("Europe/Zurich");
        $time = date( "d/m/Y h:i a", time());
        $txt = "#$time: $msg\r\n"; 
        $open = fopen( $file, "a" ); 
        $write = fputs( $open, $txt );
        fclose($open);
    
    }

function loyverse_sync_log(){

        $logFile = plugin_dir_path( __FILE__ ) . '/lvs_log.txt';
        echo '<pre class="log">';
        $myfile = fopen($logFile, 'r') or die(__('Unable to open log file!', 'loyverse_sync_log'));
        echo fread($myfile, filesize($logFile));
        fclose($myfile);
        echo '</pre>';

    }

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

    //Get merchant Id from Loyverse
    $merchurl = 'https://api.loyverse.com/v1.0/merchant/';
    $response = wp_remote_retrieve_body(wp_remote_get($merchurl, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . get_option('lvs_lvtoken')
        ),
    )));
    $data = json_decode($response,true);

    $merchant_id = $data['id'];

    $settingsurl = 'https://sync.galaxeos.net/api/settings';
    $FunctionKey = "iEHg3VSq8yHM0n_J4lPNXnmAnRqSH2oHvX-IO5o6gBiIAzFu0KLgkA==";

    // Check if already exists in Azure
    $body = array(
        'operation'    => 'read',
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
    
     # if there is a merchant
    if($data){
          
        $loyverse_token = get_option('lvs_lvtoken','1'); 
        $WC_user = get_option('lvs_wckey','1');
        $WC_Secret = get_option('lvs_wcsecret','1');
        $sync_cat = get_option('lvs_cat');
 
        ?>    
             <pre> Merchant already exists in Azure. Updating information.</pre>    
             <br>
             <pre><strong>If needed refresh page to update the values.</strong></pre>
         <?php 

        $body = array(
            'operation'    => 'update',
            'merchant_id'   => $merchant_id,
            'loyverse_secret' => $loyverse_token,
            'wc_username' => $WC_user,
            'wc_secret' => $WC_Secret,
            'sync_cat' => $sync_cat,
            'site_url' => $url
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
        
        $loyverse_token = get_option('lvs_lvtoken','1'); 
        $WC_user = get_option('lvs_wckey','1');
        $WC_Secret = get_option('lvs_wcsecret','1');
        $sync_cat = get_option('lvs_cat','1');

    // Send to Azure information
    $body = array(
        'operation'    => 'insert',
        'merchant_id'   => $merchant_id,
        'loyverse_secret' => $loyverse_token,
        'wc_username' => $WC_user,
        'wc_secret' => $WC_Secret,
        'sync_cat' => $sync_cat,
        'site_url' => $url,
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