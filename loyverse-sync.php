<?php
/**
* Plugin Name: Loyverse-Sync
* Plugin URI: https://github.com/zoltar00/Loyvserse-sync/
* Description: Synching of Loyverse POS
* Version: 1.2
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

/*Schedule loyverse-sync */
register_activation_hook( __FILE__, 'loyversesync_plugin_activation' );
register_deactivation_hook( __FILE__, 'loyversesync_plugin_deactivation' );

function loyversesync_plugin_activation() {
    if ( ! wp_next_scheduled( 'loyverse_sync' ) ) {
        wp_schedule_event( time(), 'every_minute', 'loyverse_sync' );
    }
}

function loyversesync_plugin_deactivation() {
    wp_clear_scheduled_hook( 'loyverse_sync' );
}

/*Auto-update plugin */

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/zoltar00/Loyvserse-sync/',
	__FILE__, //Full path to the main plugin file or functions.php.
	'loyverse-sync'
);
$myUpdateChecker->setAuthentication('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');
$myUpdateChecker->setBranch('main');

/*Update database requirement */

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

Class LoyverseSyncPlugin {

function __construct(){

        add_action('admin_menu', array($this,'adminPage'));
        add_action( 'admin_init', array($this,'settings'));
        /*add_action('admin_menu', 'lvsync_create_menu');*/

    }

function settings(){

        add_settings_section('lsp_first_section',null,null,'loyverse-sync-settings-page');
        
        add_settings_field('lvs_lvtoken','Loyverse API Token',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_lvtoken'));
        register_setting('loyversesyncplugin','lvs_lvtoken',array('sanitize_callback' =>'sanitize_text_field','default'=>'Loyverse API Token'));

        add_settings_field('lvs_wckey','Woocommerce API Key',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_wckey'));
        register_setting('loyversesyncplugin','lvs_wckey',array('sanitize_callback' =>'sanitize_text_field','default'=>'Woocommerce API Key'));

        add_settings_field('lvs_wcsecret','Woocommerce API Secret',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_wcsecret'));
        register_setting('loyversesyncplugin','lvs_wcsecret',array('sanitize_callback' =>'sanitize_text_field','default'=>'Woocommerce API Secret'));
        
        add_settings_field('lvs_table','Loyverse Custom Table Name',array($this,'inputHTM'),'loyverse-sync-settings-page','lsp_first_section', array('theName' => 'lvs_table'));
        register_setting('loyversesyncplugin','lvs_table',array('sanitize_callback' =>'sanitize_text_field','default'=>'Loyverse Custom Table Name')); 

    }

 function inputHTM($args){ ?>

        <input type="text" name="<?php echo $args['theName'] ?>" value="<?php echo esc_attr(get_option($args['theName'])) ?>"></input>

    <?php }

function adminPage(){

        add_options_page('Loyverse Sync Settings','Loyverse Settings','manage_options','loyverse-sync-settings-page',array($this,'ourHTML'));
        add_management_page('Loyverse sync', 'Loyverse sync', 'manage_options', 'loyverse-sync', array($this,'loyverse_sync'));
    
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

function loyverse_categories_connection(){

    ?>        
        <pre> Getting categories from Loyverse... </pre>
    <?php  

    
    $responsecategories = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/categories', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . get_option('lvs_lvtoken','1')
        ),
    )));
    $data = json_decode($responsecategories,true);

    return $data;
    
}
function loyverse_items_connection(){

    ?>        
        <pre> Getting items from Loyverse... </pre>
        <pre> Connecting to Loyverse API...</pre>
    <?php  
   
       /** Connect to Loyverse */
       
       $resp = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/items', array(
           'headers' => array(
               'Authorization' => 'Bearer ' . get_option('lvs_lvtoken','1')
           ),
       )));

       ?>        
            <pre> Connected to Loyverse API... </pre>
        <?php           

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
function loyverse_delete_objects(){

    global $wpdb;

    ?>

    <pre> Starting deleted objects check! </pre>

    <?php

    /* Get all items from Databases */
    $databaseresults[] = $wpdb->get_results( "SELECT * FROM wp_lv_sync" );
    $loyversecategories[] = $this->loyverse_categories_connection();
    $loyverseItems[] =  $this->loyverse_items_connection();
    
    $woocommerce = new Client(
		'https://mammamia.mimlab.ch',
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

            foreach($loyversecategories[0] as $lv_cat){

                foreach($lv_cat as $category){   
        
                    $loyverse_cat_id = $category['id'];
    
                    if($lvid ===$loyverse_cat_id ){
    
                        ?>
    
                        <pre> Category <?php echo $lvname ?> still exists. Skipping...</pre>
                    
                    <?php  
                        $found = 1;
                        break;
                    }
                      
                }
            }

        }

        if($found == 0 && $desc == 'Category'){

            ?>
        
            <pre> Category <?php echo $lvname ?> does not exist anymore. Deleting...</pre>
        
            <?php
            /* Delete catagory in Woocommerce */
            $url = 'products/categories/'.$wcid;
            /*print_r($url);*/
            $woocommerce->delete($url, ['force' => true]);
            $wpdb->delete( 'wp_lv_sync', array( 'lv_sync_id' => $lvsyncid ) );
    
            ?>
    
            <pre> Category <?php echo $lvname ?> deleted...</pre>
        
            <?php            
            $found = 0;

        }

        if($desc == 'Item'){

            foreach($loyverseItems[0] as $lv_item){

                foreach($lv_item as $item){   
        
                    $loyverse_item_id = $item['id'];
    
                    if($lvid ===$loyverse_item_id ){
    
                        ?>
    
                        <pre> Item <?php echo $lvname ?> still exists. Skipping...</pre>
                    
                    <?php  

                        $found = 1;
                        break;
                    }
                                          
                }
            }

        }       

        if($found == 0 && $desc == 'Item'){

            ?>
        
            <pre> Item <?php echo $lvname ?> does not exist anymore. Deleting...</pre>
        
            <?php
            /* Delete Item in Woocommerce */
            $url = 'products/'.$wcid;
            /*print_r($url);*/
            $woocommerce->delete($url, ['force' => true]);
            $wpdb->delete( 'wp_lv_sync', array( 'lv_sync_id' => $lvsyncid ) );
    
            ?>
    
            <pre> Item <?php echo $lvname ?> deleted...</pre>
        
            <?php            
            $found = 0;

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
  
    global $wpdb;
    $tablename = get_option('lvs_table','1');

    if($tablename == 1){
       
    ?>        
        <pre> Loyverse Table Name is empty. Please configure it on the <a href='./options-general.php?page=loyverse-sync-settings-page'>settings page.</a></pre>
    <?php   

        exit;

    }
    else
    {
        /* Check if custom table exists */

        $sql = "SHOW tables LIKE 'wp_". $tablename. "';";
        $res = $wpdb->query($sql);

        if($res == 0 && strlen($tablename) >0 ){

        ?>        
            <pre> Loyverse Table does not exist. Creating <?php echo $tablename ?></pre>
        <?php   

                $ddl =" CREATE TABLE wp_$tablename (
                lv_sync_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                lv_id VARCHAR(100),
                lv_name VARCHAR(100),
                wc_id VARCHAR(100),
                lv_desc VARCHAR(100)
                );";
    

            dbDelta($ddl);
            

        }
        if(strlen($tablename) ==0){
        ?>        
            <pre> Loyverse Table <?php echo $tablename ?> cannot be an empty string. Please update on the <a href='./options-general.php?page=loyverse-sync-settings-page'>settings page.</a></pre>
        <?php  
        exit;

        }
        else{
            ?>        
            <pre> Loyverse Table <?php echo $tablename ?> exists. Skipping...</pre>
        <?php  

        }

    }

    /*Check if custom database exists */

    $thedatabase = 'wp_'.$tablename;
	$loyverse_items = [];

	/**Connect to WooCommerce */
    ?>        
        <pre> Connecting to Woocommerce API... </pre>
    <?php    

	$woocommerce = new Client(
		'https://mammamia.mimlab.ch',
		get_option('lvs_wckey','1'),
		get_option('lvs_wcsecret','1'),
		[
			'wp_api' => true,
			'version' => 'wc/v3',
            'verify_ssl' => false
		]
	);

    ?>        
        <pre> Connected to Woocommerce API... </pre>
    <?php  

    /** Connect to Loyverse to get categories*/
    

    $loyverse_categories[] = $this->loyverse_categories_connection();

    /** Get all categories from Woocommerce */
    
    foreach($loyverse_categories[0] as $loyverse_category){
        
        foreach($loyverse_category as $category){

            $loyverse_category_id = $category['id'];
            
            /** Get data from database */

            $sql = "SELECT * FROM wp_". get_option('lvs_table');
            $queryresults = $wpdb->get_results($sql);
            $found = 0;
 
            foreach($queryresults as $qres){ 
               
                if($qres->lv_id===$loyverse_category_id){ 
                
                            $prod_data = [
                                'name' => $category['name']
                                ];
                            $db_data = [
                                'lv_name' => $category['name']
                                ];
                            $url = 'products/categories/'.$qres->wc_id;
                            $woocommerce->put( $url, $prod_data );

                            $wpdb->update( $thedatabase , $db_data, array( 'lv_id' => $category['id'] ));

                        ?>        
                            <pre> Category <?php echo $category['name'] ?> updated. </pre>
                        <?php      
                        

                        $found = $found + 1;

                        break;
                 }

            }
            

            if($found == 0)
            {

            /**   Create stuff for woocommerce category */
                $prod_data = [
                'name' => $category['name']
                ];

                ?>        
                    <pre> Sending category <?php echo $category['name'] ?> to woocommerce... </pre>
                <?php    

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

                    <?php }
                    else{ ?>
                        
                        <pre> Unable to save category <?php echo $category['name'] ?> to the database... </pre>

                    <?php }


            }

        }
    
    }    

        /* Sync Products from Loyverses */

            $loyverse_items[] = $this ->loyverse_items_connection();/** Get items from Loyverse */

        ?>        
            <pre> Got all Items... </pre>
        <?php   



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

                    /** Get Woocommerce category id from loyverse category. Get from Dataabase */
                    $queryresults = $wpdb->get_results( "SELECT * FROM wp_lv_sync" );
                    
                      
                    foreach($queryresults as $qres){ 
                    

                    $found = 0;

                    if($qres->lv_id===$item['category_id']){
                        
                        $wcid=$qres->wc_id;

                        $found = $found + 1;

                            break;

                        }

                    }

                    /** Check if data already in Woocommerce */
                    $found = 0;

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
                            $woocommerce->put( $url, $prod_data );

                            $wpdb->update( $thedatabase , $db_data, array( 'lv_id' => $loyverse_item_id));

                        ?>        
                            <pre> Item <?php echo $loyverse_item_name ?> updated. </pre>
                        <?php     

                        $found = $found + 1;

                        break;

                        }
                    
                    }


                            if($found == 0){
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
                                    <pre> Sending item <?php echo $loyverse_item_slug ?> to woocommerce..</pre>
                                <?php      
                                
                    
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

                                <?php }
                                else{ ?>
                                    
                                    <pre> Unable to save item <?php echo $loyverse_item_name?> to the database... </pre>

                                <?php }    

                            }

                }
            }

            ?>   
                <pre> Deleting categories and items that are no longer in Loyverse</pre>  <?php $this ->loyverse_delete_objects(); ?>     
                <pre> Done importing!</pre>
            <?php   
        }

}


$loyverseSyncPlugin = new LoyverseSyncPlugin();

?>