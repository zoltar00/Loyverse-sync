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
                    	/** Connect to Loyverse */
                        $token = '8a9f63253d6c41e294e8f67d8ebcadea'; 
                        $responsecategories = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/categories', array(
                            'headers' => array(
                                'Authorization' => 'Bearer ' . $token
                            ),
                        )));
                        $data = json_decode($responsecategories,true);
                        
                        $loyverse_categories[] = $data;

                        echo 'Hello World!';
                        $data = json_decode($responsecategories,true);
		
                        $loyverse_categories[] = $data;
                        foreach($loyverse_categories[0] as $loyverse_category){
                            
                            echo '<pre>';
                            print_r("Loyverse categories");
                            echo '</br>';
                            print_r($loyverse_category);
                           /**print_r($woocommerce->get('products/categories'));*/
                            echo '</pre>';
                            echo '</br>';

                            foreach($loyverse_category as $category){
                    
                                $loyverse_item_slug = sanitize_title($category['name']);
                      
                        /** Authorization to Woocommerce 
                        *$autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
                        *if ( is_readable( $autoloader ) ) {
                        *    require_once $autoloader;
                        *}
                        
                        *use Automattic\WooCommerce\Client;

                        *$woocommerce = new Client(
                        *    'https://mammamia.mimlab.ch',
                        *    'ck_99b4d2a4d51cad847b882430b5406619528b8922',
                        *    'cs_ce509e4cb542d9dbfcba19c538961df957780290',
                        *    [
                        *        'wp_api' => true,
                        *        'version' => 'wc/v3'
                        *    ]
                        *);*/

                       /**  $args = array(
                        *    'post_type' => 'Loyverse_Item',
						*	'post_status' => 'publish'
                        *  );

                        *$loyverseposts = get_posts( $args);

                        *$prod_data = [
                        *    'name'          => 'A great product',
                        *    'type'          => 'simple',
                        *    'regular_price' => '15.00',
                        *    'sku' => '1000',
                        *    'description'   => 'A very meaningful product description',
                        *    'categories'    => [
                        *        [
                        *            'id' => 17,
                        *        ],
                        *    ],
                        *];*/
                        
                        /**$woocommerce->post( 'products', $prod_data );*/

                        echo '<pre>';
                        print_r("Loyverse Categories slug");
                        echo '</br>';
                        print_r($loyverse_item_slug);
                       /**print_r($woocommerce->get('products/categories'));*/
                        echo '</pre>';
                        echo '</br>';
                        }
                    }
                    ?>
                </main>
            </div>
            <?php get_sidebar(); ?>
        </div>
    </div>

<?php get_footer();