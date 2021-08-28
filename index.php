<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Restaurant Zone
 */

get_header(); ?>

    <div id="skip-content" class="container">
        <div class="row">
            <div id="primary" class="content-area col-md-12 <?php echo is_active_sidebar('sidebar-1') ? "col-lg-9" : "col-lg-12"; ?>">
                <main id="main" class="site-main">
                    <?php 

                        $autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
                        if ( is_readable( $autoloader ) ) {
                            require_once $autoloader;
                        }

                        use Automattic\WooCommerce\Client;

                        	/**Connect to WooCommerce */

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

                        echo '<pre>'; 
                        print_r("Woocommerce products ");
                        echo '</br>';                      
                        print_r($woocommerce->get('products'));

                        echo "---------------------------------------------------";
                        echo '<pre>'; 
                        print_r("Woocommerce categories");
                        echo '</br>';                      
                        print_r($woocommerce->get('products/categories'));                        

                        /** Connect to Loyverse */
                        $token = '8a9f63253d6c41e294e8f67d8ebcadea'; 
                        $response = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/items', array(
                            'headers' => array(
                                'Authorization' => 'Bearer ' . $token
                            ),
                        )));
                        $data = json_decode($response,true);
                        
                        $loyverse_items[] = $data;
                        foreach($loyverse_items[0] as $loyverse_item){
                            
                            foreach($loyverse_item as $item){

                                $loyverse_item_slug = $item['name'];

                                echo '<pre>';
                                print_r("loyverse items");
                                echo '</br>';
                                print_r($item);          
                            }
                        
                        }  

                        echo '<pre>';
                        print_r("---------------------------------------------------");
                        echo '</hr>';

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

                                echo '<pre>';
                                print_r("loyverse categories");
                                echo '</br>';
                                print_r($category);          
                            }
                        
                        }                          

                    ?>
                </main>
            </div>
            <?php get_sidebar(); ?>
        </div>
    </div>

<?php get_footer();