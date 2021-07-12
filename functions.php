<?php
/**
 * Restaurant Zone functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Restaurant Zone
 */

define( 'WP_DEBUG', true );

include get_theme_file_path( 'vendor/wptrt/autoload/src/Restaurant_Zone_Loader.php' );

$restaurant_zone_loader = new \WPTRT\Autoload\Restaurant_Zone_Loader();

$restaurant_zone_loader->restaurant_zone_add( 'WPTRT\\Customize\\Section', get_theme_file_path( 'vendor/wptrt/customize-section-button/src' ) );

$restaurant_zone_loader->restaurant_zone_register();

$autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( is_readable( $autoloader ) ) {
	require_once $autoloader;
}

use Automattic\WooCommerce\Client;

if ( ! function_exists( 'restaurant_zone_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function restaurant_zone_setup() {

		add_theme_support( 'woocommerce' );
		
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

        add_image_size('restaurant-zone-featured-header-image', 2000, 660, true);

        // This theme uses wp_nav_menu() in one location.
        register_nav_menus( array(
            'primary' => esc_html__( 'Primary','restaurant-zone' ),
	        'footer'=> esc_html__( 'Footer Menu','restaurant-zone' ),
        ) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'restaurant_zone_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			'height'      => 50,
			'width'       => 50,
			'flex-width'  => true,
		) );

		add_editor_style( array( '/editor-style.css' ) );

		add_theme_support( 'wp-block-styles' );

		add_theme_support( 'align-wide' );
	}
endif;
add_action( 'after_setup_theme', 'restaurant_zone_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function restaurant_zone_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'restaurant_zone_content_width', 1170 );
}
add_action( 'after_setup_theme', 'restaurant_zone_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function restaurant_zone_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'restaurant-zone' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'restaurant-zone' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h5 class="widget-title">',
		'after_title'   => '</h5>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 1', 'restaurant-zone' ),
		'id'            => 'restaurant-zone-footer1',
		'description'   => '',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5 class="footer-column-widget-title">',
		'after_title'   => '</h5>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 2', 'restaurant-zone' ),
		'id'            => 'restaurant-zone-footer2',
		'description'   => '',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5 class="footer-column-widget-title">',
		'after_title'   => '</h5>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 3', 'restaurant-zone' ),
		'id'            => 'restaurant-zone-footer3',
		'description'   => '',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5 class="footer-column-widget-title">',
		'after_title'   => '</h5>',
	) );
}
add_action( 'widgets_init', 'restaurant_zone_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function restaurant_zone_scripts() {

	wp_enqueue_style('restaurant-zone-font', restaurant_zone_font_url(), array());

	wp_enqueue_style( 'restaurant-zone-block-editor-style', get_theme_file_uri('/assets/css/block-editor-style.css') );

	// load bootstrap css
    wp_enqueue_style( 'flatly-css', esc_url(get_template_directory_uri()) . '/assets/css/flatly.css');

	wp_enqueue_style( 'restaurant-zone-style', get_stylesheet_uri() );

	wp_style_add_data('restaurant-zone-style', 'rtl', 'replace');

	// fontawesome
	wp_enqueue_style( 'fontawesome-css', esc_url(get_template_directory_uri()).'/assets/css/fontawesome/css/all.css' );

	wp_enqueue_style( 'owl.carousel-css', esc_url(get_template_directory_uri()).'/assets/css/owl.carousel.css' );

    wp_enqueue_script('owl.carousel-js', esc_url(get_template_directory_uri()) . '/assets/js/owl.carousel.js', array('jquery'), '', true );

    wp_enqueue_script('restaurant-zone-theme-js', esc_url(get_template_directory_uri()) . '/assets/js/theme-script.js', array('jquery'), '', true );
    
    wp_enqueue_script('restaurant-zone-skip-link-focus-fix', esc_url(get_template_directory_uri()) . '/assets/js/skip-link-focus-fix.js', array(), '20151215', true);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'restaurant_zone_scripts' );

/**
 * Enqueue theme color style.
 */
function restaurant_zone_theme_color() {

    $theme_color_css = '';
    $restaurant_zone_theme_color = get_theme_mod('restaurant_zone_theme_color');

	$theme_color_css = '
		.sticky .entry-title::before,.main-navigation .menu > li > a:hover,.main-navigation .sub-menu,#button,.sidebar input[type="submit"],.comment-respond input#submit,.post-navigation .nav-previous a:hover, .post-navigation .nav-next a:hover, .posts-navigation .nav-previous a:hover, .posts-navigation .nav-next a:hover,.woocommerce .woocommerce-ordering select,.woocommerce ul.products li.product .onsale, .woocommerce span.onsale,.pro-button a, .woocommerce #respond input#submit, .woocommerce a.button, .woocommerce button.button, .woocommerce input.button, .woocommerce #respond input#submit.alt, .woocommerce a.button.alt, .woocommerce button.button.alt, .woocommerce input.button.alt,.wp-block-button__link,.woocommerce-account .woocommerce-MyAccount-navigation ul li,.btn-primary,.reservation-btn a,.slide-btn a:hover,.toggle-nav button {
			background: '.esc_attr($restaurant_zone_theme_color).';
		}
		a,.sidebar a:hover,#colophon a:hover, #colophon a:focus,p.price, .woocommerce ul.products li.product .price, .woocommerce div.product p.price, .woocommerce div.product span.price,.woocommerce-message::before, .woocommerce-info::before,.navbar-brand p,.sidebar p a, .entry-content a, .entry-summary a, .comment-content a,.top-info i,.slider-inner-box h2,#items-section h3 {
			color: '.esc_attr($restaurant_zone_theme_color).';
		}
		.woocommerce-message, .woocommerce-info,.wp-block-pullquote,.wp-block-quote, .wp-block-quote:not(.is-large):not(.is-style-large), .wp-block-pullquote,.btn-primary,.slide-btn a:hover,.post-navigation .nav-previous a:hover, .post-navigation .nav-next a:hover, .posts-navigation .nav-previous a:hover, .posts-navigation .nav-next a:hover{
			border-color: '.esc_attr($restaurant_zone_theme_color).';
		}
	';
    wp_add_inline_style( 'restaurant-zone-style',$theme_color_css );

}
add_action( 'wp_enqueue_scripts', 'restaurant_zone_theme_color' );

function restaurant_zone_font_url(){
	$font_url = '';
	$lobster = _x('on','Cookie:on or off','restaurant-zone');
	$lato = _x('on','Lato:on or off','restaurant-zone');
	$lora = _x('on','Lora:on or off','restaurant-zone');
	
	if('off' !== $lobster ){
		$font_family = array();
		if('off' !== $lobster){
			$font_family[] = 'Cookie';
		}	
		if('off' !== $lato){
			$font_family[] = 'Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900';
		}
		if('off' !== $lora){
			$font_family[] = 'Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700';
		}
		$query_args = array(
			'family'	=> urlencode(implode('|',$font_family)),
		);
		$font_url = add_query_arg($query_args,'//fonts.googleapis.com/css');
	}
	return $font_url;
}

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';


/*dropdown page sanitization*/
function restaurant_zone_sanitize_dropdown_pages( $page_id, $setting ) {
	// Ensure $input is an absolute integer.
	$page_id = absint( $page_id );
	// If $page_id is an ID of a published page, return it; otherwise, return the default.
	return ( 'publish' == get_post_status( $page_id ) ? $page_id : $setting->default );
}

/*radio button sanitization*/
function restaurant_zone_sanitize_choices( $input, $setting ) {
    global $wp_customize; 
    $control = $wp_customize->get_control( $setting->id ); 
    if ( array_key_exists( $input, $control->choices ) ) {
        return $input;
    } else {
        return $setting->default;
    }
}

function restaurant_zone_sanitize_phone_number( $phone ) {
	return preg_replace( '/[^\d+]/', '', $phone );
}

function restaurant_zone_string_limit_words($string, $word_limit) {
	$words = explode(' ', $string, ($word_limit + 1));
	if(count($words) > $word_limit)
	array_pop($words);
	return implode(' ', $words);
}

/**
 * Fix skip link focus in IE11.
 *
 * This does not enqueue the script because it is tiny and because it is only for IE11,
 * thus it does not warrant having an entire dedicated blocking script being loaded.
 *
 * @link https://git.io/vWdr2
 */
function restaurant_zone_skip_link_focus_fix() {
	?>
	<script>
	/(trident|msie)/i.test(navigator.userAgent)&&document.getElementById&&window.addEventListener&&window.addEventListener("hashchange",function(){var t,e=location.hash.substring(1);/^[A-z0-9_-]+$/.test(e)&&(t=document.getElementById(e))&&(/^(?:a|select|input|button|textarea)$/i.test(t.tagName)||(t.tabIndex=-1),t.focus())},!1);
	</script>
	<?php
}
add_action( 'wp_print_footer_scripts', 'restaurant_zone_skip_link_focus_fix' );

add_action('init','register_loyverse_items');
ini_set( 'error_log', WP_CONTENT_DIR . '/debug.log' );

function register_loyverse_items(){

	register_post_type('Loyverse_Item',[

		'label' => 'Loyverse Items',
		'public' => true,
		'capability_type' => 'post'
	]);
}
 /** Functions addins */
 add_action('wp_ajax_nopriv_get_modifiers_from_loyverse','get_modifiers_from_loyverse');
 add_action('wp_ajax_get_modifiers_from_loyverse','get_modifiers_from_loyverse');

 function get_modifiers_from_loyverse(){
	/**Connect to WooCommerce */

	$woocommerce = new Client(
		'https://mammamia.mimlab.ch',
		'ck_99b4d2a4d51cad847b882430b5406619528b8922',
		'cs_ce509e4cb542d9dbfcba19c538961df957780290',
		[
			'wp_api' => true,
			'version' => 'wc/v3'
		]
	);


	/** Connect to Loyverse */
	$token = '8a9f63253d6c41e294e8f67d8ebcadea'; 
	$response = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/modifiers', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $token
		),
	)));

	$data = json_decode($response,true);

	if( ! is_array($data) || empty($data)){

		return false;
		error_log ("Not an Array!");
	}

	$loyverse_modifiers[] = $data;

	foreach($loyverse_modifiers[0] as $loyverse_modifier){

		foreach($loyverse_modifier as $modifier){
                    
			$loyverse_modifier_slug = sanitize_title($modifier['name']);

			/** Create stuff for woocommerce product */
			$prod_data = [
				'name'          => $modifier['name'],
				'slug'          => $loyverse_modifier_slug
			];

			/**Send to WooCommerce */
			$woocommerce->post( 'products/attributes', $prod_data );	
			
		}
	}


 }

add_action('wp_ajax_nopriv_get_items_from_loyverse','get_items_from_loyverse');
add_action('wp_ajax_get_items_from_loyverse','get_items_from_loyverse');

// Return total count and values found in array	

function get_items_from_loyverse(){

	//delete all posts of Loyverse items first

	$allposts= get_posts( array('post_type'=>'Loyverse_Item','numberposts'=>-1) );
	foreach ($allposts as $eachpost) {
  		wp_delete_post( $eachpost->ID, true );
	}

	$loyverse_items = [];

	/**Connect to WooCommerce */

	$woocommerce = new Client(
		'https://mammamia.mimlab.ch',
		'ck_99b4d2a4d51cad847b882430b5406619528b8922',
		'cs_ce509e4cb542d9dbfcba19c538961df957780290',
		[
			'wp_api' => true,
			'version' => 'wc/v3'
		]
	);


	/** Connect to Loyverse */
	$token = '8a9f63253d6c41e294e8f67d8ebcadea'; 
	$response = wp_remote_retrieve_body(wp_remote_get('https://api.loyverse.com/v1.0/items', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $token
		),
	)));

	$data = json_decode($response,true);

	if( ! is_array($data) || empty($data)){

		return false;
		error_log ("Not an Array!");
	}

	$loyverse_items[] = $data;

	foreach($loyverse_items[0] as $loyverse_item){

		foreach($loyverse_item as $item){

			$loyverse_item_slug = sanitize_title($item['item_name']); 

			 	$args = array(
					'name'        => $loyverse_item_slug,
					'post_type'   => 'Loyverse_Item',
					'post_status' => 'publish'
				);
				$my_posts = get_posts($args);
				
                 if(! $my_posts ){
					 
					foreach($item['variants'] as $variants){
						$inserted_item = wp_insert_post([

								'post_name' => $loyverse_item_slug,
								'post_title' => $loyverse_item_slug,
								'post_type' => 'Loyverse_Item',
								'post_status' => 'publish'

							]);

							if(is_wp_error($inserted_item)){

								continue;
							}
							$price = $variants['stores']['0']['price'];
							$price = $price.".00";
						
							update_field('field_60dcd92525b70',$item['item_name'], $inserted_item);
							update_field('field_60dcd93b25b71',$variants['sku'], $inserted_item);
							update_field('field_60e5e9af1e83f',$variants['option1_value'],$inserted_item);
							update_field('field_60dcd94325b72',$price, $inserted_item);
							update_field('field_60ea31a3a1da6',$item['category_id'], $inserted_item);

							/** Create stuff for woocommerce product */
							$prod_data = [
								'name'          => $loyverse_item_slug,
								'type'          => 'simple',
								'regular_price' => $price,
								'sku' => $variants['sku'],
								'description'   => $loyverse_item_slug,
								'categories'    => [
									[
										'id' => 17,
									],
								],
							];

							/**Send to WooCommerce */
							$woocommerce->post( 'products', $prod_data );
					}
				}
		}	
	}

	/** Retreive categories */
		
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
	
				$loyverse_item_slug = $category['name'];

				/** Create stuff for woocommerce product */
				$prod_data = [
					'name' => $loyverse_item_slug
				];
				$woocommerce->post( 'products/categories', $prod_data );
			}
		
		}
}