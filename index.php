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
                        $response = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/modifiers', array(
                            'headers' => array(
                                'Authorization' => 'Bearer ' . $token
                            ),
                        )));
                        $data = json_decode($response,true);
                        
                        $loyverse_modifiers[] = $data;

                        //echo 'Hello World!';
                        		
                    foreach($loyverse_modifiers[0] as $loyverse_modifier){
                            
                            echo '<pre>';
                           print_r("Loyverse modifiers");
                            echo '</br>';
                            print_r($loyverse_modifier);
                           /**print_r($woocommerce->get('products/categories'));*/
                            echo '</pre>';
                            echo '</br>';

                            foreach($loyverse_modifier as $modifier){
                    
                                $loyverse_modifier_slug = sanitize_title($modifier['name']);

                                echo '<pre>';
                                print_r($loyverse_modifier_slug);
                                echo '</br>';

                                foreach($modifier['modifier_options'] as $modifier_option){
/** 
 *                                   echo '<pre>';
 *                                   print_r("Loyverse Modifier options");
 *                                   echo '</br>';
 *                                   print_r($modifier_option['name']);
 *                                   echo '</br>';
 *                                   print_r($modifier_option['price']);
 *                                   echo '</br>';
 *                                  print_r($woocommerce->get('products/categories'));
 *                                   print_r($loyverse_modifier_slug);
 *                                  echo '</pre>';
 *                                  echo '</br>';
 */
                                        $attribute_name = $loyverse_modifier_slug;
 /**                                       echo '<pre>';
 *                                       print_r("attribute name");
 *                                      echo '</br>';
 *                                     print_r($attribute_name);
*/
                                        $terms = wc_get_attribute_taxonomies($attribute_name);
                                        
                                        if(!empty($terms))
                                        {
                                       /**  for ( $i=0; $i < count($terms); $i++ )
                                        *    {*/
                                            
                                        /** Create Term */
                                        

                                        
                                                echo '<pre>';
                                                print_r("Get object by slug");
                                                echo '</br>';
                                                print_r($terms['']);

                                                                                            
                                         /**   }*/
                                        
                                        }                                  
                                    

                                }
                            }
                    }



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
                    
                    ?>
                </main>
            </div>
            <?php get_sidebar(); ?>
        </div>
    </div>

<?php get_footer();