<?php

/*Template Name: Front
*/
get_header(); ?>

<?php 

$token = '8a9f63253d6c41e294e8f67d8ebcadea'; 
$response = wp_remote_get('https://api.loyverse.com/v1.0/items', array(
    'headers' => array(
        'Authorization' => 'Bearer ' . $token
    ),
));

echo '<pre>';
print_r($results);
echo '</pre>';
die();
?>


<?php get_footer();