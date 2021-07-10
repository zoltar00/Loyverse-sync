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
                    echo 'Curl: ', function_exists('curl_version') ? 'Enabled' . "\xA" : 'Disabled' . "\xA";
                    ?>
                    <?php 

                        echo 'Hello World!';
                        
                        /** Authorization to Woocommerce */
                        $autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
                        if ( is_readable( $autoloader ) ) {
                            require_once $autoloader;
                        }
                        
                        use Automattic\WooCommerce\Client;

                        $woocommerce = new Client(
                            'https://mammamia.mimlab.ch',
                            'ck_99b4d2a4d51cad847b882430b5406619528b8922',
                            'cs_ce509e4cb542d9dbfcba19c538961df957780290',
                            [
                                'wp_api' => true,
                                'version' => 'wc/v3'
                            ]
                        );

                        $args = array(
                            'post_type' => 'Loyverse_Item',
							'post_status' => 'publish'
                          );

                        $loyverseposts = get_posts( $args);

                        $prod_data = [
                            'name'          => 'A great product',
                            'type'          => 'simple',
                            'regular_price' => '15.00',
                            'sku' => '1000',
                            'description'   => 'A very meaningful product description',
                            'categories'    => [
                                [
                                    'id' => 17,
                                ],
                            ],
                        ];
                        
                        /**$woocommerce->post( 'products', $prod_data );*/

                        echo '<pre>';
                        print_r("Woocommerce Products");
                        echo '</br>';
                        print_r($loyverseposts);
                       /** print_r($woocommerce->get('products'));  */
                        echo '</pre>';
                        echo '</br>';

                    ?>
                </main>
            </div>
            <?php get_sidebar(); ?>
        </div>
    </div>

<?php get_footer();