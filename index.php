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

                        echo 'Hello World!';
                        $token = '8a9f63253d6c41e294e8f67d8ebcadea'; 
                        $response = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/items', array(
                            'headers' => array(
                                'Authorization' => 'Bearer ' . $token
                            ),
                        )));

                        echo '<pre>';
                        print_r($response);
                        echo '</pre>';
                        die();

                    ?>
                </main>
            </div>
            <?php get_sidebar(); ?>
        </div>
    </div>

<?php get_footer();