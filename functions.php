<?php
/**
 * The Movie Lot Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package The Movie Lot
 * @since 1.0.0
 */
/* Define Constants */
define('CHILD_THEME_THE_MOVIE_LOT_VERSION','1.0.0');
/* Enqueue styles */
function child_enqueue_styles(){
	wp_enqueue_style('the-movie-lot-theme-css',get_stylesheet_directory_uri() . '/style.css',array('astra-theme-css'),CHILD_THEME_THE_MOVIE_LOT_VERSION,'all');
	wp_enqueue_script('the-movie-lot-theme-custom-script',get_stylesheet_directory_uri() . '/script.js',true);
}
add_action('wp_enqueue_scripts','child_enqueue_styles',15);
//Remove Unused Admin Bar Items
add_action('wp_before_admin_bar_render','remove_admin_bar_links',999);
function remove_admin_bar_links(){
	global $wp_admin_bar;
	$wp_admin_bar->remove_node('wp-logo');
	$wp_admin_bar->remove_node('about');
	$wp_admin_bar->remove_node('wporg');
	$wp_admin_bar->remove_node('documentation');
	$wp_admin_bar->remove_node('support-forums');
	$wp_admin_bar->remove_node('feedback');
	$wp_admin_bar->remove_node('comments');
}
//Remove Nag
add_action("admin_head","nagremover");
function nagremover(){echo "<style>.notice.elementor-message{display:none}</style>";}
//Add SVG Upload Support
add_filter('upload_mimes','cc_mime_types');
function cc_mime_types($mimes){
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('wp_update_attachment_metadata','svg_meta_data',10,2);
function svg_meta_data($data,$id){
	$attachment = get_post($id);
	$mime_type = $attachment->post_mime_type;
	if($mime_type == 'image/svg+xml'){
		if(empty($data) || empty($data['width']) || empty($data['height'])){
			$xml = simplexml_load_file(wp_get_attachment_url($id));
			$attr = $xml->attributes();
			$viewbox = explode(' ',$attr->viewBox);
			$data['width'] = isset($attr->width) && preg_match('/\d+/',$attr->width,$value) ? (int) $value[0] :(count($viewbox) == 4 ? (int) $viewbox[2] :null);
			$data['height'] = isset($attr->height) && preg_match('/\d+/',$attr->height,$value) ? (int) $value[0] :(count($viewbox) == 4 ? (int) $viewbox[3] :null);
		}
	}
	return $data;
}
//Add Default SEO Image
$default_opengraph = 'https://themovielot.com/wp-content/uploads/2018/03/The-Movie-Lot-F-Banner.png';
function add_default_opengraph($object){global $default_opengraph; $object->add_image($default_opengraph);}
add_action('wpseo_add_opengraph_images','add_default_opengraph');
function default_opengraph(){global $default_opengraph; return $default_opengraph;}
add_filter('wpseo_twitter_image','default_opengraph');
/*WooCommerce Changes
--------------------*/
/*Hide Prices*/
add_action('init','hide_product_archives_prices');
function hide_product_archives_prices(){
	remove_action('woocommerce_after_shop_loop_item_title','woocommerce_template_loop_price',10);
	remove_action('woocommerce_cart_collaterals','woocommerce_cart_totals',10);
}
add_action('woocommerce_single_product_summary','hide_single_product_prices',1);
function hide_single_product_prices(){
	global $product;
	remove_action('woocommerce_single_product_summary','woocommerce_template_single_price',10);
	remove_action('woocommerce_single_variation','woocommerce_single_variation',10);
}
add_filter('woocommerce_cart_item_price','__return_false');
add_filter('woocommerce_cart_item_subtotal','__return_false');
//Cart Update Message
add_filter('gettext','change_cart_message',10,3);
function change_cart_message($translation,$text,$domain){
	if($domain == 'woocommerce'){if($text == 'Cart updated.'){$translation = 'Quote updated.';}}
	return $translation;
}
add_filter('woocommerce_account_orders_columns','orders_columns');
function orders_columns($columns){
	unset($columns['order-total']);
	unset($columns['order-actions']);
	$columns['order-actions'] = __('View Quote Request','woocommerce');
	return $columns;
}
//Rename Button
add_filter('woocommerce_product_single_add_to_cart_text','custom_cart_button_text');
function custom_cart_button_text(){return __('Request Quote','woocommerce');}
add_filter('woocommerce_product_add_to_cart_text' ,'custom_woocommerce_product_add_to_cart_text');
function custom_woocommerce_product_add_to_cart_text(){
	global $product;
	$product_type = $product->product_type;
	switch($product_type){
	case 'external':
		return __('Request Quote','woocommerce');
	break;
	case 'grouped':
		return __('View products','woocommerce');
	break;
	case 'simple':
		return __('Add to Quote','woocommerce');
	break;
	case 'variable':
		return __('Select options','woocommerce');
	break;
	default:
		return __('Read more','woocommerce');
	}
}
//Change it to a More Info button
function replace_add_to_cart_button($button,$product){return '<a class="button" href="' . $product->get_permalink() . '">' . __("More Information","woocommerce") . '</a>';}
add_filter('woocommerce_loop_add_to_cart_link','replace_add_to_cart_button',10,2);
//Add Additional Date Field
function rental_days_woocommerce_before_add_to_cart_button(){
	global $product;
	echo '<div class="rental-days">
			<label for="rental-time">'. __("Rental Days","woocommerce") .'</label>
			<input type="number" id="rental-time" name="rental-time" title="Rental Days" size="3" pattern="[0-9]*" inputmode="numeric" step="1" value="1" min="1" max="100">
		  </div>';
};
add_action('woocommerce_before_add_to_cart_button','rental_days_woocommerce_before_add_to_cart_button',10,0);
function rental_days_to_cart_item($cart_item_data,$product_id,$variation_id){
	$rental_days = filter_input(INPUT_POST,'rental-time');
	if(empty($rental_days)){return $cart_item_data;}
	$cart_item_data['rental-time'] = $rental_days;
	return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data','rental_days_to_cart_item',10,3);
function rental_days_cart($item_data,$cart_item){
	if(empty($cart_item['rental-time'])){return $item_data;}
	$item_data[] = array(
		'key'	  => __('Rental Days','woocommerce'),
		'value'   => wc_clean($cart_item['rental-time']),
		'display' => '',
	);
	return $item_data;
}
add_filter('woocommerce_get_item_data','rental_days_cart',10,2);
function rental_days_to_order_items($item,$cart_item_key,$values,$order){
	if(empty($values['rental-time'])){return;}
	$item->add_meta_data(__('Rental Days','woocommerce'),$values['rental-time']);
}
add_action('woocommerce_checkout_create_order_line_item','rental_days_to_order_items',10,4);
//Reduce the strength requirement on the woocommerce password
add_filter('woocommerce_min_password_strength','reduce_woocommerce_min_strength_requirement');
function reduce_woocommerce_min_strength_requirement($strength){return 1;}
//Remove Total and Subtotal from mail
add_filter('woocommerce_get_order_item_totals','adjust_woocommerce_get_order_item_totals');
function adjust_woocommerce_get_order_item_totals($totals){
	unset($totals['payment_method']);
	unset($totals['cart_subtotal']);
	unset($totals['order_total']);
	unset($totals['shipping']);
	return $totals;
}
//Remove Default Sorting Dropdown
remove_action('woocommerce_before_shop_loop','woocommerce_catalog_ordering',30);
//Change Return To Shop URL
add_filter('woocommerce_return_to_shop_redirect','wc_empty_cart_redirect_url');
function wc_empty_cart_redirect_url(){return '/product-category/rental-equipment/';}