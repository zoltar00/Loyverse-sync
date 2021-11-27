<?php
/**
* Plugin Name: Loyverse-Sync
* Plugin URI: https://github.com/zoltar00/Loyverse-sync/
* Description: Synching of Loyverse POS to Woocommerce
* Version: 1.0.0
* Author: SLC Services, Sylvan Laurence
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
    
        if ( ! wp_next_scheduled( 'lvs_cron' ) ) {
            wp_schedule_event( time(), 'rsssl_le_five_minutes', 'lvs_cron' );
        }
}
    
function loyversesync_plugin_deactivation() {
        $timestamp = wp_next_scheduled( 'lvs_cron' );
        wp_unschedule_event( $timestamp, 'lvs_cron' );
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
        add_action( 'lvs_cron', array($this,'loyverse_sync' ));

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
        
        add_settings_field('lvs_table','Loyverse Custom Table Name',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_table'));
        register_setting('loyversesyncplugin','lvs_table',array('sanitize_callback' =>'sanitize_text_field','default'=>''));

        add_settings_field('lvs_cat','Synchronize categories',array($this,'checkHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_cat'));
        register_setting('loyversesyncplugin','lvs_cat',array('sanitize_callback' =>'sanitize_text_field','default'=>'1'));
        
        add_settings_field('lvs_item','Synchronize items',array($this,'checkHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_item'));
        register_setting('loyversesyncplugin','lvs_item',array('sanitize_callback' =>'sanitize_text_field','default'=>'1'));

        add_settings_field('lvs_catsync','Number of categories to synchronize',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_catsync'));
        register_setting('loyversesyncplugin','lvs_catsync',array('sanitize_callback' =>'sanitize_text_field','default'=>''));

        add_settings_field('lvs_productsync','Number of products to synchronize',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_productsync'));
        register_setting('loyversesyncplugin','lvs_productsync',array('sanitize_callback' =>'sanitize_text_field','default'=>''));

    }

function inputHTM($args){ ?>

            <input type="text" name="<?php echo $args['theName'] ?>" value="<?php echo esc_attr(get_option($args['theName'])) ?>"></input>

        <?php 
    }
function checkHTM($args){ ?>

    <input type="checkbox" name="<?php echo $args['theName'] ?>" value="1" <?php checked(get_option($args['theName']),'1') ?> ></input>

    <?php 
 }

function Synchschedule(){ 
        
        $schedules = wp_get_schedules();
        ?>

        <select name="lvs_schedule" >

        <?php 
        
        foreach($schedules as $schedule){
            
            ?>
            <option value="<?php  echo $schedule['display'] ?>" <?php selected(get_option('lvs_schedule'), $schedule['display']) ?> ><?php echo  $schedule['display'] ?></option>
            <?php
        }

        ?>
            
        </select>

        <?php 
    }

function adminPage(){

        add_options_page('Loyverse Sync Settings','Loyverse Settings','manage_options','loyverse-sync-settings-page',array($this,'ourHTML'));
        add_management_page('Loyverse sync', 'Loyverse sync', 'manage_options', 'loyverse-sync', array($this,'loyverse_sync'));
        add_management_page('Loyverse sync log', 'Loyverse sync log', 'manage_options', 'loyverse-sync-log', array($this,'loyverse_sync_log'));
    
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
    }

function loyverse_categories_connection($cursor){  


        ?>        
            <pre> Getting categories from Loyverse... </pre>
        <?php  

        $this ->write_to_loyverse_sync_log('Getting categories from Loyverse...');

        $limitcat = get_option('lvs_catsync','50');
        if(strlen($limitcat) == 0){

            $limitcat = 50;

       }

       if(strlen(get_option('lvs_lvtoken')) == 0){

        ?>        
            <pre>Loyverse Token is empty. Please configure it on the <a href='./options-general.php?page=loyverse-sync-settings-page'>settings page.</a> </pre>
        <?php  

        $this ->write_to_loyverse_sync_log('Loyverse Token is empty. Please configure it on the settings page.');
        exit;
   }

        //print_r($limitcat);

            if($cursor == 'null'){

                $caturl = 'https://api.loyverse.com/v1.0/categories?limit='.$limitcat;
            }
            else{

                $caturl = 'https://api.loyverse.com/v1.0/categories?limit='.$limitcat. '&cursor='. $cursor;
            }

        //print_r($caturl);
        $this ->write_to_loyverse_sync_log($caturl);
    
        $responsecategories = wp_remote_retrieve_body(wp_remote_get($caturl, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . get_option('lvs_lvtoken')
            ),
        )));
        $data = json_decode($responsecategories,true);

        //print_r($data);

        return $data;
    
    }
function loyverse_items_connection($cursor){

    ?>        
        <pre> Getting items from Loyverse... </pre>
        <pre> Connecting to Loyverse API...</pre>
    <?php  
   
       /** Connect to Loyverse */
       $this ->write_to_loyverse_sync_log('Getting items from Loyverse...');
       $this ->write_to_loyverse_sync_log('Connecting to Loyverse API...');

       $limititem = get_option('lvs_productsync','50');
       if(strlen($limititem) == 0){

            $limititem = 50;

       }

       $lvstoken = get_option('lvs_lvtoken');

       if(strlen($lvstoken) == 0){

        ?>        
            <pre>Loyverse Token is empty. Please configure it on the <a href='./options-general.php?page=loyverse-sync-settings-page'>settings page.</a> </pre>
        <?php  

        $this ->write_to_loyverse_sync_log('Loyverse Token is empty. Please configure it on the settings page.');
        exit;
   }       

      // print_r($limititem);

      if($cursor == 'null'){

        $itemurl = 'https://api.loyverse.com/v1.0/items?limit='.$limititem;
    }
    else{

        $itemurl = 'https://api.loyverse.com/v1.0/items?limit='.$limititem. '&cursor='. $cursor;
    }


       $resp = wp_remote_retrieve_body(wp_remote_get($itemurl, array(
           'headers' => array(
               'Authorization' => 'Bearer ' . get_option('lvs_lvtoken','1')
           ),
       )));

       ?>        
            <pre> Connected to Loyverse API... </pre>
        <?php           

        $this ->write_to_loyverse_sync_log('Connected to Loyverse API... ');
       $data = json_decode($resp,true);
       return $data;

    }
function get_loyverse_category_by_id($catid){

        /** Connect to loyverse */

        
        $cat_url = 'https://api.loyverse.com/v1.0/categories/'.$catid;

        $responsecat = wp_remote_retrieve_body(wp_remote_get($cat_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . get_option('lvs_lvtoken','1')
            ),
        )));


        
        $datacat = json_decode($responsecat,true);

               
        return $datacat['name'];

    }
function get_loyverse_category_by_id_for_delete($catid){

    /** Connect to loyverse */

    
    
    $cat_url = 'https://api.loyverse.com/v1.0/categories/'.$catid;
    
    $responsecat = wp_remote_retrieve_body(wp_remote_get($cat_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . get_option('lvs_lvtoken','1')
        ),
    )));


    /*print_r($responsecat);*/
    
    $datacat = json_decode($responsecat,true);
     
    return $datacat['deleted_at'];

 }

function get_loyverse_item_by_id($itemid){

    /** Connect to loyverse */

    $item_url = 'https://api.loyverse.com/v1.0/items/'.$itemid;

    $responseitem = wp_remote_retrieve_body(wp_remote_get($item_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . get_option('lvs_lvtoken','1')
        ),
    )));


    
    $datacat = json_decode($responseitem,true);
    
    return $datacat['deleted_at'];

 }
function loyverse_delete_objects(){

        global $wpdb;

        ?>

        <pre> Starting deleted objects check! </pre>

        <?php

        $this ->write_to_loyverse_sync_log('Starting deleted objects check!');

        $tablename = get_option('lvs_table');
        $thedatabase = 'wp_'. str_replace('-', '_', $tablename);
        
        /* For each item in database check if in Loyverse (get_loyverse_category_by_id and get_loyverse_item_by_id). If result is null then delete from Woocommerce and Databse otherwise do nothing. */

        /* Get all items from Databases */
        $databaseresults[] = $wpdb->get_results( "SELECT * FROM ". $thedatabase );
        //$loyversecategories[] = $this->loyverse_categories_connection();
        //$loyverseItems[] =  $this->loyverse_items_connection();
        
        $woocommerce = new Client(
            get_option('siteurl'),
            get_option('lvs_wckey','1'),
            get_option('lvs_wcsecret','1'),
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'verify_ssl' => false
            ]
        );

        $found = 0;

       
    foreach($databaseresults[0] as $dbres){

            $found = 0;

            global $lvid;
            global $lvname;
            global $wcid;
            global $lvsyncid;

            $lvid = $dbres->lv_id;
            $lvname = $dbres->lv_name;
            $wcid = $dbres->wc_id;
            $lvsyncid = $dbres->lv_sync_id;
            $desc = $dbres->lv_desc;

            /*print_r($dbres);*/

           if($desc == 'Category'){

            if(strlen($this->get_loyverse_category_by_id_for_delete($lvid)) == 0){

                    //Categorie exists
                    ?>
        
                    <pre> Category <?php echo $lvname ?> still exists. Skipping...</pre>
                
                    <?php 
                    $this ->write_to_loyverse_sync_log('Category '. $lvname .' still exists. Skipping...');

            }
            else{
                ?>
            
                <pre> Category <?php echo $lvname ?> does not exist anymore. Deleting...</pre>
            
                <?php
                $this ->write_to_loyverse_sync_log('Category '. $lvname .' does not exist anymore. Deleting...');
                /* Delete catagory in Woocommerce */
                $url = 'products/categories/'.$wcid;
                /*print_r($url);*/
                $woocommerce->delete($url, ['force' => true]);
                $wpdb->delete( $thedatabase, array( 'lv_sync_id' => $lvsyncid ) );
                ?>
        
                <pre> Category <?php echo $lvname ?> deleted...</pre>
            
                <?php 
                $this ->write_to_loyverse_sync_log('Category '. $lvname .' deleted...');

            }
        }

            if($desc == 'Item'){
                if(strlen($this->get_loyverse_item_by_id($lvid)) == 0){
                
                    ?>
        
                    <pre> Item <?php echo $lvname ?> still exists. Skipping...</pre>
                
                    <?php  
                    $this ->write_to_loyverse_sync_log('Item '. $lvname .' still exists. Skipping...');                
                
                }
                else{
                    ?>
            
                    <pre> Item <?php echo $lvname ?> does not exist anymore. Deleting...</pre>
                
                    <?php
    
                    $this ->write_to_loyverse_sync_log('Item '. $lvname .' does not exist anymore. Deleting...');
                    /* Delete Item in Woocommerce */
                    $url = 'products/'.$wcid;
                    /*print_r($url);*/
                    $woocommerce->delete($url, ['force' => true]);
                    $wpdb->delete( $thedatabase, array( 'lv_sync_id' => $lvsyncid ) );
            
                    ?>
            
                    <pre> Item <?php echo $lvname ?> deleted...</pre>
                
                    <?php 
                    $this ->write_to_loyverse_sync_log('Item '. $lvname .' deleted...');

                }
            }
    }

 }

function loyverse_sync(){ ?>  

    <div class ="wrap">
        <h1>Loyverse Synchronization Log</h1>
        <br />
        <pre> Starting synchronization ...</pre>
    </div>

    <?php
    $this->init_loyverse_sync_log();
    $this ->write_to_loyverse_sync_log('Starting synchronization ...');
   
    global $wpdb;
    $tablename = get_option('lvs_table','1');
    $thedatabase = 'wp_'. str_replace('-', '_', $tablename);
    
    if($tablename == 1){
       
    ?>        
        <pre> Loyverse Table Name is empty. Please configure it on the <a href='./options-general.php?page=loyverse-sync-settings-page'>settings page.</a></pre>
    <?php 

    $this ->write_to_loyverse_sync_log('Loyverse Table Name is empty. Please configure it on the settings page.');
        exit;

    }
    else
    {
        /* Check if custom table exists */
        $done = 0;

        $sql = "SHOW tables LIKE '". $thedatabase. "';";
        $res = $wpdb->get_results($sql);

        if(empty($res) && strlen($thedatabase) >0 ){

        ?>        
            <pre> Loyverse Table does not exist. Creating <?php echo $tablename ?></pre>
        <?php   

            $this ->write_to_loyverse_sync_log('Loyverse Table does not exist. Creating '. $tablename);

                $ddl =" CREATE TABLE $thedatabase (
                lv_sync_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                lv_id VARCHAR(100),
                lv_name VARCHAR(100),
                wc_id VARCHAR(100),
                lv_desc VARCHAR(100)
                );";
    

            dbDelta($ddl);
            $done =1;

        }
        if(strlen($thedatabase) ==0){
        ?>        
            <pre> Loyverse Table <?php echo $tablename ?> cannot be an empty string. Please update on the <a href='./options-general.php?page=loyverse-sync-settings-page'>settings page.</a></pre>
        <?php 
        
        $this ->write_to_loyverse_sync_log('Loyverse Table '. $tablename . 'cannot be an empty string. Please update on the settings page.');
        exit;

        }
        if($done == 0){
            ?>        
            <pre> Loyverse Table <?php echo $tablename ?> exists. Skipping...</pre>
        <?php  
        $this ->write_to_loyverse_sync_log('Loyverse Table '. $tablename. ' exists. Skipping... ');

        }

    }

    /*Check if custom database exists */

    //$thedatabase = 'wp_'.$tablename;
	

	/**Connect to WooCommerce */
    ?>        
        <pre> Connecting to Woocommerce API... </pre>
    <?php    
    $this ->write_to_loyverse_sync_log('Connecting to Woocommerce API... ');

    if(strlen(get_option('lvs_wckey')) == 0)
    {

        ?>        
        <pre> Woocommerce key does not exist. Please update on the <a href='./options-general.php?page=loyverse-sync-settings-page'>settings page.</a></pre>
    <?php 
    
    $this ->write_to_loyverse_sync_log('Woocommerce key does not exist. Please update on the settings page.');
    exit;

    }
    if(strlen(get_option('lvs_wcsecret')) == 0)
    {

        ?>        
        <pre> Woocommerce secret does not exist. Please update on the <a href='./options-general.php?page=loyverse-sync-settings-page'>settings page.</a></pre>
    <?php 
    
    $this ->write_to_loyverse_sync_log('Woocommerce secret does not exist. Please update on the settings page.');
    exit;

    }    
	$woocommerce = new Client(
		get_option('siteurl'),
		get_option('lvs_wckey'),
		get_option('lvs_wcsecret'),
		[
			'wp_api' => true,
			'version' => 'wc/v3',
            'verify_ssl' => false
		]
	);

    ?>        
        <pre> Connected to Woocommerce API... </pre>
    <?php  

    $this ->write_to_loyverse_sync_log('Connected to Woocommerce API... ');
    /** Connect to Loyverse to get categories*/
    

    //CHeck if categories sync is checked and that sync limit is not empty

    if(get_option('lvs_cat') == 1 && strlen(get_option('lvs_catsync')) < 1){

        ?>        
        <pre> The number of categories to synchronize is not set. Please update on the <a href='./options-general.php?page=loyverse-sync-settings-page'>settings page.</a></pre>
    <?php 
    
    $this ->write_to_loyverse_sync_log('The number of categories to synchronize is not set. Please update on the settings page.');
     exit;   

    }
    
    $cursor = 'null';
    $i=0;


  do{
    //print_r($i);  
    //print_r(get_option('lvs_catsync'));
    if(get_option('lvs_catsync') == $i){

        break;
    }

    
    if(strlen(get_option('lvs_cat')) == 0){
        
        ?>        
            <pre> Categories sync unchecked. </pre>
        <?php  
        $this ->write_to_loyverse_sync_log('Categories sync unchecked. ');
        break;
    }

    $loyverse_categories = [];
    $loyverse_categories[] = $this->loyverse_categories_connection($cursor);

    $cursor = $loyverse_categories[0]['cursor'];

    //print_r($loyverse_categories);
    /** Get all categories from Woocommerce */
    $this ->write_to_loyverse_sync_log('Checking all categories against database... ');

    /** Get data from database */

    $sql = "SELECT * FROM ". $thedatabase;
    $queryresults = $wpdb->get_results($sql);
    
    ?>        
    <pre> Got all categories from database... </pre>
    <?php 
    $this ->write_to_loyverse_sync_log('Got all categories from database... ');

    //print_r(count($loyverse_categories));


    foreach($loyverse_categories[0] as $loyverse_category){

        $found = 0;
        $error = 0;

        foreach($loyverse_category as $category){

            //print($category['name']);
            

            $loyverse_category_id = $category['id'];
  
            foreach($queryresults as $qres){ 
               
                if($qres->lv_id===$loyverse_category_id){ 

                            ?>        
                            <pre> Found category ID: <?php echo  $qres->lv_id ?> </pre>
                            <pre> Woocommerce ID is: <?php echo  $qres->wc_id ?> </pre>
                            <?php 
                
                            $this ->write_to_loyverse_sync_log('Found category ID: ' . $qres->lv_id);
                            $this ->write_to_loyverse_sync_log('Woocommerce ID is: ' . $qres->wc_id);
                            $prod_data = [
                                'name' => $category['name']
                                ];
                            $db_data = [
                                'lv_name' => $category['name']
                                ];
                            $url = 'products/categories/'.$qres->wc_id;
                            /* Add check */
                            try{
                            $woocommerce->put( $url, $prod_data );
                            }
                            catch (Exception $e){

                                //echo $e->getMessage();
                                ?>        
                                    <pre> Category <?php echo $category['name'] ?> does not exist in Woocommerce but exists in the database. Putting it back. </pre>
                                <?php 
                                //$this ->write_to_loyverse_sync_log($e->getMessage());
                                $this ->write_to_loyverse_sync_log('Category ' . $category['name'] . ' does not exist in Woocommerce. Putting it back.');
                                
                                
                                ?>        
                                    <pre> Sending category <?php echo $category['name'] ?> to woocommerce... </pre>
                                <?php    
            
                            $this ->write_to_loyverse_sync_log('Sending category '.$category['name'].' to woocommerce... ');
                            try{
                            $woocommerce->post( 'products/categories', $prod_data );  
                            $wpdb->update( $thedatabase , $db_data, array( 'lv_id' => $category['id'] )); 
                            }
                            catch(Exception $e){
                                ?>        
                                <pre> Category <?php echo $category['name'] ?> already exists in Woocommerce. Skipping. </pre>
                            <?php    
    
                                $this ->write_to_loyverse_sync_log('Category '.$category['name'].' already exists in Woocommerce. Skipping. ');  
                                $error = 1; 
                                break;

                            }
                            ?>        
                                <pre> Category <?php echo $category['name'] ?> sent to woocommerce... </pre>
                            <?php    
    
                                $this ->write_to_loyverse_sync_log('Category '.$category['name'].' sent to woocommerce... ');                            
                            $error = 1;
                            break;
                                
                            }
                            $wpdb->update( $thedatabase , $db_data, array( 'lv_id' => $category['id'] ));

                        ?>        
                            <pre> Category <?php echo $category['name'] ?> updated. </pre>
                        <?php      
                        
                        $this ->write_to_loyverse_sync_log('Category '.$category['name'].' updated. ');

                        $found = $found + 1;

                        break;
                 }

            }
            

            if(($found == 0) && ($error == 0))
            {

            /**   Create stuff for woocommerce category */
                $prod_data = [
                'name' => $category['name']
                ];

                ?>        
                    <pre> Sending category <?php echo $category['name'] ?> to woocommerce... </pre>
                <?php    

                $this ->write_to_loyverse_sync_log('Sending category '.$category['name'].' to woocommerce... ');


                    $woocommerce->post( 'products/categories', $prod_data );

                (array) $thecategory = $woocommerce->get('products/categories',['search' => sanitize_title($category['name'])]);
            
                $data = array(
                    'lv_id' => $category['id'],
                    'lv_name' => $category['name'],
                    'wc_id' => $thecategory[0] ->id,
                    'lv_desc' => 'Category'
                );

                /* Insert into database wp_lv_sync */
                
                    $result = $wpdb->insert($thedatabase,$data, $format=NULL);

                    if($result==1){ ?>

                        <pre> Saved category <?php echo $category['name'] ?> to the database... </pre>

                    <?php 
                    $this ->write_to_loyverse_sync_log('Saved category '.$category['name'].' to the database... ');    
                }
                    else{ ?>
                        
                        <pre> Unable to save category <?php echo $category['name'] ?> to the database... </pre>

                    <?php 
                    $this ->write_to_loyverse_sync_log('Unable to save category '.$category['name'].' to the database... ');    
                }


            }

        }
    
    }    
    $i = 1;
 }  while ($cursor);

 /* Sync Products from Loyverses */
        $i = 0;
        $cursor = 'null';

    //CHeck if categories sync is checked and that sync limit is not empty

    if(get_option('lvs_item') == 1 && strlen(get_option('lvs_productsync')) < 1){

        ?>        
        <pre> The number of products to synchronize is not set. Please update on the <a href='./options-general.php?page=loyverse-sync-settings-page'>settings page.</a></pre>
    <?php 
    
    $this ->write_to_loyverse_sync_log('The number of products to synchronize is not set. Please update on the settings page.');
     exit;   

    }        

        do{
            if(get_option('lvs_productsync') == $i){
                
                break;
            }

            if(strlen(get_option('lvs_item')) == 0){
        
                ?>        
                    <pre> Items sync unchecked </pre>
                <?php  
                $this ->write_to_loyverse_sync_log('Items sync unchecked. ');
                break;
            }
        
            $loyverse_items = [];
            $loyverse_items[] = $this ->loyverse_items_connection($cursor);/** Get items from Loyverse */

        ?>        
            <pre> Got all Items... </pre>
        <?php   
        $this ->write_to_loyverse_sync_log('Got all Items...  ');
        //print_r($loyverse_items);
        
        if(isset($loyverse_items[0]['cursor']))
        {
            $cursor = $loyverse_items[0]['cursor'];
        }
        else{

            ?>        
                <pre> No cursor because single item. </pre>
            <?php 
            $this ->write_to_loyverse_sync_log('No cursor because single item. ');
        }
            
        
        
        //print_r($loyverse_items);
        foreach ($loyverse_items[0] as $loyverse_item) {

                foreach($loyverse_item as $item){

                    $loyverse_item_id = $item['id'];
                    $loyverse_item_slug = sanitize_title($item['item_name']);
                    $loyverse_item_name = $item['item_name'];
                    $loyverse_item_img_url = $item['image_url'];
                    $variant_sku = $item['variants'][0]['sku'];
                    $loyverse_item_price = $item['variants'][0]['default_price'];
                    $loyverse_catname = $this->get_loyverse_category_by_id($item['category_id']); 
                    $loyverse_category_slug = sanitize_title($loyverse_catname);

                    ?>        
                        <pre> Processing item <?php echo $loyverse_item_name ?>.</pre>
                    <?php 
                    $this ->write_to_loyverse_sync_log('Processing item '. $loyverse_item_name . '.');

                    /** Get Woocommerce category id from loyverse category. Get from Dataabase */
                    $sql = "SELECT * FROM ". $thedatabase;
                    $queryresults = $wpdb->get_results($sql);
                    
                    $found1 = 0;

                    foreach($queryresults as $qres){ 
                    
                        if($qres->lv_id===$item['category_id']){
                            
                            $wcid=$qres->wc_id;

                            $found1 = $found1 + 1;

                                break;

                        }
                        

                    }

                    if($found1 ==0){

                        ?>        
                            <pre> Category <?php echo $loyverse_catname ?> is not synced yet. Cannot create product. Please change the values of the category synchronization. </pre>
                        <?php 
                        $this ->write_to_loyverse_sync_log('Category '. $loyverse_catname . ' is not synced yet. Cannot create product. Please change the values of the category synchronization.');

                        break;
                    }

                    /** Check if data already in Woocommerce */
                    $found = 0;
                    $error = 0;

                    foreach($queryresults as $qres){ 
               
                        if($qres->lv_id===$loyverse_item_id){ 

                            $prod_data = [
                                'name'          => $loyverse_item_name,
                                'type'          => 'simple',
                                'description'   => $loyverse_item_slug,
                                'regular_price' => strval($loyverse_item_price),
                                'sku' => $variant_sku,
                                'categories' =>[
                                    [
                                    'id' => (integer)$wcid
                                        
                                    ]
                                    
                                    ],
                                'meta_data' => [
                                        [
                                            'key'=> '_knawatfibu_url',
                                            'value'=> [
                                                'img_url' => $loyverse_item_img_url,
                                                'width'=> '500',
                                                'height'=> '500'
                                            ]
                                        ]
                                ]
                                
                            ];

                            $db_data = [
                                'lv_name' => $loyverse_item_name
                                ];
                            $url = 'products/'.$qres->wc_id;
                            try{
                            $woocommerce->put( $url, $prod_data );
                            }
                            catch(Exception $e) {

                                //echo $e->getMessage();
                                ?>        
                                <pre> Product <?php echo $loyverse_item_name ?> does not exist in Woocommerce. Delete it from the database <?php echo $tablename ?> and try synching again.</pre>
                            <?php 
                                //$this ->write_to_loyverse_sync_log($e->getMessage());
                                $this ->write_to_loyverse_sync_log('Product ' . $loyverse_item_name . ' does not exist in Woocommerce. Delete it from the database '. $tablename . ' and try synching again.');
                                $error = 1;
                                break;
          
                            }
                            $wpdb->update( $thedatabase , $db_data, array( 'lv_id' => $loyverse_item_id));

                        ?>        
                            <pre> Item <?php echo $loyverse_item_name ?> updated. </pre>
                        <?php   

                        $this ->write_to_loyverse_sync_log('Item '. $loyverse_item_name .' updated.');
                        $found = $found + 1;

                        break 3;

                        }
                    
                    }


                            if(($found == 0) && ($error == 0)){
                                /** Create stuff for woocommerce product */
                                $prod_data = [
                                    'name'          => $loyverse_item_name,
                                    'type'          => 'simple',
                                    'description'   => $loyverse_item_slug,
                                    'regular_price' => strval($loyverse_item_price),
                                    'sku' => $variant_sku,
                                    'categories' =>[
                                        [
                                        'id' => (integer)$wcid
                                            
                                        ]
                                        
                                        ],
                                    'meta_data' => [
                                            [
                                                'key'=> '_knawatfibu_url',
                                                'value'=> [
                                                    'img_url' => $loyverse_item_img_url,
                                                    'width'=> '500',
                                                    'height'=> '500'
                                                ]
                                            ]
                                    ]
                                    
                                ];

                                ?>        
                                    <pre> Sending item <?php echo $loyverse_item_slug ?> to woocommerce...</pre>
                                <?php      
                                
                                $this ->write_to_loyverse_sync_log('Sending item '. $loyverse_item_slug .' to woocommerce...');
                                /**Send to WooCommerce */
                                $woocommerce->post( 'products', $prod_data );
                                (array) $theitem = $woocommerce->get('products',['search' => $loyverse_item_slug]);

                                /* Insert into database wp_lv_sync */
                                
                                $db_data = array(
                                    'lv_id' => $loyverse_item_id,
                                    'lv_name' => $loyverse_item_name,
                                    'wc_id' => $theitem[0] ->id,
                                    'lv_desc' => 'Item'
                                );

                                $result = $wpdb->insert($thedatabase,$db_data, $format=NULL);

                                if($result==1){ ?>

                                    <pre> Saved item <?php echo $loyverse_item_name ?> to the database... </pre>

                                <?php 
                                $this ->write_to_loyverse_sync_log('Saved item '. $loyverse_item_name .' to the database...');    
                            }
                                else{ ?>
                                    
                                    <pre> Unable to save item <?php echo $loyverse_item_name?> to the database... </pre>

                                <?php 
                                $this ->write_to_loyverse_sync_log('Unable to save item '. $loyverse_item_name .' to the database...');    
                            }    

                            }

                }
            }
            $i = $i + 1;
        } while($cursor);
            $this ->write_to_loyverse_sync_log('Deleting categories and items that are no longer in Loyverse');
            ?>   
                <pre> Deleting categories and items that are no longer in Loyverse</pre>  <?php $this ->loyverse_delete_objects(); ?>     
                <pre> Done importing!</pre>
            <?php  
            
            $this ->write_to_loyverse_sync_log('Done importing!'); 
        }

    }


$loyverseSyncPlugin = new LoyverseSyncPlugin();

?>