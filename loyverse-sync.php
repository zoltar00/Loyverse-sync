<?php
/**
* Plugin Name: Loyverse-Sync
* Plugin URI: https://mammamis.mimlab.ch/
* Description: Synching of Loyverse POS
* Version: 1.0
* Author: Sylvan Laurence
* Author URI: https://slcservices.ch/
**/

defined( 'ABSPATH' ) or die('Unauthorized Access!');

//Action when user logs into admin  panel
add_shortcode('external_data', 'callback_function_name');

function callback_function_name(){

    return 'Call is working';



}
