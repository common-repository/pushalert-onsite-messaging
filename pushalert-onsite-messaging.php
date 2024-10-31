<?php
/*
 * Plugin Name: Onsite Messaging by PushAlert
 * Plugin URI: https://wordpress.org/plugins/pushalert-onsite-messaging/
 * Description: Build your email marketing list with PushAlert OnSite Messaging. Keep users engaged with personalized on-site popups, automate cart abandonment recovery and browse abandonment with Exit Intent technology.
 * Author: PushAlert
 * Author URI: https://pushalert.co/onsite-messaging
 * Version: 1.1.1
 */

add_action('admin_init', 'pa_onsite_messaging_admin_init');
add_action('admin_notices', 'pa_onsite_messaging_warn_onactivate');
add_action('wp_footer', 'pa_onsite_messaging_append_js');

register_activation_hook( __FILE__, 'pa_onsite_messaging_init_options' );
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pa_onsite_messaging_settings_link');

if (pa_onsite_messaging_woocommerce_enabled()) {
	add_action('wp_footer', 'get_pa_onsite_cart_info');
}
/*if (pa_onsite_messaging_woocommerce_version_check()) {
    if(get_option('_onsite_messaging_report_sales', 0)){

    }
}*/
add_action('woocommerce_thankyou', 'pa_onsite_messaging_on_order_placed', 10, 1);
add_action('admin_menu', 'pa_onsite_messaging_register_normal_menu_page');


function pa_onsite_messaging_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=pushalert-onsite-messaging') . '">' . __('Settings', 'pa_onsite_messaging') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

function pa_onsite_messaging_warn_onactivate() {
    if (is_admin()) {
        $pa_onsite_messaging_web_id = get_option('pa_onsite_messaging_web_id');

        if (!$pa_onsite_messaging_web_id) {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>Onsite Messaging:</strong> Website ID is required. Update <a href="' . admin_url('admin.php?page=pushalert-onsite-messaging') . '">' . __('settings', 'pa_onsite_messaging') . '</a> now!</p></div>';
        }
    }
}

function pa_onsite_messaging_admin_init() {
    //If there any changes on version update
    /*$pa_version = 1;
    if(get_option('_pa_onsite_messaging_version', 1)!=$pa_version){
        //if there any changes
    }*/

    register_setting(
        'pa_onsite_messaging', 'pa_onsite_messaging_web_id'
    );
}


function pa_onsite_messaging_replace_footer_admin () {

    echo 'If you like <strong>Onsite Messaging by PushAlert</strong> please leave us a <a href="https://wordpress.org/support/view/plugin-reviews/pushalert-onsite-messaging?filter=5#postform" target="_blank" class="wc-rating-link" data-rated="Thanks :)">★★★★★</a> rating. A huge thanks in advance!';

}

function pa_onsite_messaging_append_js() {
    $pa_onsite_messaging_web_id = get_option('pa_onsite_messaging_web_id');
    if ($pa_onsite_messaging_web_id) {
        ?>
        <!-- Onsite Messaging 1.0.0 -->
        <script type="text/javascript">
            (function (d, t) {
                var g = d.createElement(t),
                    s = d.getElementsByTagName(t)[0];
                g.src = "//cdn.inwebr.com/inwebr_<?php echo esc_html($pa_onsite_messaging_web_id) ?>.js";
                s.parentNode.insertBefore(g, s);
            }(document, "script"));
        </script>
        <?php
    }
}

function pa_onsite_messaging_report_conversion($name, $value) {

    ?>
    <!-- Onsite Messaging 1.0.0 -->
    <script type="text/javascript">
        (onsitemessagingbypa = window.onsitemessagingbypa || []).push(['conversion', '<?php echo esc_html($name)?>', '<?php echo esc_html($value)?>']);
    </script>
    <?php
}

function pa_onsite_messaging_sanitize_text_field($str) {
    $filtered = wp_check_invalid_utf8($str); //html tags are fine
    $filtered = trim(preg_replace('/[\r\n\t ]+/', ' ', $filtered));

    $found = false;
    while (preg_match('/%[a-f0-9]{2}/i', $filtered, $match)) {
        $filtered = str_replace($match[0], '', $filtered);
        $found = true;
    }

    if ($found) {
        // Strip out the whitespace that may now exist after removing the octets.
        $filtered = trim(preg_replace('/ +/', ' ', $filtered));
    }

    return $filtered;
}

function pa_onsite_messaging_init_options() {
    add_option( 'pa_onsite_messaging_plugin_activation','just-activated' );
}

function pa_onsite_messaging_register_normal_menu_page() {
    add_menu_page('Onsite Messaging by PushAlert', 'Onsite Messaging', 'manage_options', 'pushalert-onsite-messaging', 'pa_onsite_messaging_general_settings_callback', 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI0LjEuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAzOTIuMiAzOTIuMiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgMzkyLjIgMzkyLjI7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4KCS5zdDB7ZmlsbDojQTdBQUFEO30KPC9zdHlsZT4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTM0Ni4xLDQ2LjFINDYuMmMtMTMuOCwwLTI1LDExLjEtMjUuMSwyNC45YzAsMCwwLDAuMSwwLDAuMXYyNTBjMCwxMy44LDExLjIsMjUsMjUsMjVjMCwwLDAuMSwwLDAuMSwwaDI5OS45CgljMTMuOCwwLDI1LTExLjIsMjUtMjV2LTI1MEMzNzEuMSw1Ny4zLDM2MCw0Ni4xLDM0Ni4xLDQ2LjFDMzQ2LjEsNDYuMSwzNDYuMSw0Ni4xLDM0Ni4xLDQ2LjF6IE0xNDAuNyw2NC45CgljNi45LDAsMTIuNSw1LjYsMTIuNSwxMi41YzAsNi45LTUuNiwxMi41LTEyLjUsMTIuNXMtMTIuNS01LjYtMTIuNS0xMi41QzEyOC4yLDcwLjUsMTMzLjgsNjQuOSwxNDAuNyw2NC45eiBNMTAyLjQsNjQuOQoJYzYuOSwwLDEyLjUsNS42LDEyLjUsMTIuNWMwLDYuOS01LjYsMTIuNS0xMi41LDEyLjVzLTEyLjUtNS42LTEyLjUtMTIuNUM4OS45LDcwLjUsOTUuNSw2NC45LDEwMi40LDY0LjlMMTAyLjQsNjQuOXogTTY0LjgsNjQuOQoJYzYuOSwwLDEyLjUsNS42LDEyLjUsMTIuNWMwLDYuOS01LjYsMTIuNS0xMi41LDEyLjVzLTEyLjUtNS42LTEyLjUtMTIuNUM1Mi4zLDcwLjUsNTcuOSw2NC45LDY0LjgsNjQuOXogTTM0Ni4xLDMyMS4xSDQ2LjJWMTA4LjkKCWgyOTkuOVYzMjEuMXoiLz4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTg4LjIsMTY1LjlIMjE4YzcuNCwwLDEzLjQtNiwxMy40LTEzLjRjMC03LjQtNi0xMy40LTEzLjQtMTMuNGwwLDBIODguMmMtNy40LDAtMTMuNCw2LTEzLjQsMTMuNAoJQzc0LjgsMTU5LjksODAuOCwxNjUuOSw4OC4yLDE2NS45eiIvPgo8cGF0aCBjbGFzcz0ic3QwIiBkPSJNODguMiwyMjguNGg2Ny40YzcuNCwwLDEzLjQtNiwxMy40LTEzLjRzLTYtMTMuNC0xMy40LTEzLjRIODguMmMtNy40LDAtMTMuNCw2LTEzLjQsMTMuNAoJUzgwLjgsMjI4LjQsODguMiwyMjguNHoiLz4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTg4LjIsMjkwLjhIMjE4YzcuNCwwLDEzLjQtNiwxMy40LTEzLjRzLTYtMTMuNC0xMy40LTEzLjRsMCwwSDg4LjJjLTcuNCwwLTEzLjQsNi0xMy40LDEzLjQKCVM4MC44LDI5MC44LDg4LjIsMjkwLjh6Ii8+Cjwvc3ZnPgo=', 30);
}

function pa_onsite_messaging_general_settings_callback(){

    global $title;

    echo "<h2>".esc_html($title)."</h2>";
    ?>

    <?php
    if(isset($_POST['pa-save-changes'])){
        if (!isset($_POST['pa-onsite-messaging-submenu-page-save-nonce']) || (!wp_verify_nonce($_POST['pa-onsite-messaging-submenu-page-save-nonce'], plugin_basename(__FILE__)))){
            echo '<div class="error"><p>Something went wrong!</p></div>';
        }
        else{
            $success = true;
            $pa_web_id = pa_onsite_messaging_sanitize_text_field(filter_input(INPUT_POST, 'pa_onsite_messaging_web_id'));
            update_option('pa_onsite_messaging_web_id', $pa_web_id);

            echo '<div class="updated"><p>Changes saved successfully!</p></div>';

        }
    }
    ?>
    <p>Configure options for Onsite Messaging, you can get website ID from your Onsite Messaging Settings page. If you're not registered, signup for FREE at <a target="_blank" href="https://pushalert.co/onsite-messaging">https://pushalert.co/onsite-messaging</a>.</p>

    <form method="post" action="">
        <?php settings_fields('pa_onsite_messaging'); ?>
        <table class="form-table">
            <tr><th scope="row"><h3>Website Settings</h3></th></tr>
            <tr>
                <th scope="row">Website ID</th>
                <td><input type="text" required name="pa_onsite_messaging_web_id" size="64" value="<?php echo esc_attr(get_option('pa_onsite_messaging_web_id')); ?>" placeholder="Website ID" /></td>
            </tr>
        </table>
        <?php
        submit_button( 'Save Changes', 'primary', 'pa-save-changes' );
        wp_nonce_field( plugin_basename(__FILE__), 'pa-onsite-messaging-submenu-page-save-nonce' );
        ?>
    </form>
    <?php

    add_filter('admin_footer_text', 'pa_onsite_messaging_replace_footer_admin');
}

function pa_onsite_messaging_woocommerce_enabled(){
    $is_enable = in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option('active_plugins')));
    return $is_enable;
}

function get_pa_onsite_cart_info(){
	if(pa_onsite_messaging_woocommerce_enabled()){
		$pa_onsite_cart_info = [];
		
		$item_details = WC()->cart->cart_contents;
		$product_ids = []; 
		$product_variant_ids = []; 
		foreach($item_details as $key=>$data){
			$product_ids[] = $data['product_id'];
			if($data['variation_id']!=0){
				$product_variant_ids[] = $data['variation_id'];				
			}
			else{
			    $product_variant_ids[] = $data['product_id'];
			}
		}
		
		$total_items = WC()->cart->get_cart_contents_count();
		$pa_onsite_cart_info['cti'] = WC()->cart->get_cart_contents_count();
		$pa_onsite_cart_info['ctp'] = WC()->cart->get_cart_contents_total();
		$pa_onsite_cart_info['cd'] = WC()->cart->get_cart_discount_total();
		$pa_onsite_cart_info['pic'] = implode(",", $product_ids);
		$pa_onsite_cart_info['pvic'] = implode(",", $product_variant_ids);
		
		$curr_user_id = get_current_user_id();
		if(!$curr_user_id){
			$pa_onsite_cart_info['li'] = false;
			$pa_onsite_cart_info['oc'] = 0;
			$pa_onsite_cart_info['ts'] = 0;
			$pa_onsite_cart_info['ms'] = true;
			$pa_onsite_cart_info['ct'] = "";
		}
		else{
			$customer = new WC_Customer( $curr_user_id );
			$pa_onsite_cart_info['li'] = true;
			$pa_onsite_cart_info['oc'] = $customer->get_order_count();
			$pa_onsite_cart_info['ts'] = $customer->get_total_spent();
			$pa_onsite_cart_info['ms'] = true;
			$pa_onsite_cart_info['ct'] = "";
		}
		
		if(is_product()){
			global $product;
			if(pa_onsite_messaging_woocommerce_version_check()){
				$product_id = $product->get_id();
			}
			else{
				$product_id = $product->id;
			}
			$cart_url = apply_filters( 'woocommerce_get_cart_url', wc_get_page_permalink( 'cart' ) );
			$pa_onsite_product_info = 
				array(
					'pi'=>$product_id,
					'pa' => $product->is_in_stock(),
					"pp" => $product->get_display_price(),
					'pfi' => wp_get_attachment_url($product->get_image_id()),
					'pt' =>strip_tags($product->get_categories()),
					'pta'=>strip_tags($product->get_tags()),
					'pve'=>"",
					'ptype' => $product->get_type()
				);
			if ( $product->is_type( 'variable' ) ) {

				// Get variations
				$product_variations = new WC_Product_Variable( $product_id );
				$variations = $product_variations->get_available_variations();
				
				$variations_data = [];
				// Loop through each variation
				foreach ($variations as $variation ) {
					$variations_data[] = array(
						"pi" => $variation['variation_id'],
						"vp" => $variation['display_price'],
						"vimg" => wp_get_attachment_url( $variation['image_id']),
						"va" => $variation['is_in_stock'],
					);
				}
				$pa_onsite_product_info['pv'] = $variations_data;
			}
			else{
				$pa_onsite_product_info['pv'] =[
				    [
				        "pi" => $product_id,
						"vp" => $product->get_display_price(),
						"vimg" => wp_get_attachment_url($product->get_image_id()),
						"va" => $product->is_in_stock(),
			        ]
			    ];
			}
			$pa_onsite_cart_info = array_merge($pa_onsite_cart_info, $pa_onsite_product_info);
		}
		
		echo '<script>var pa_onsite_ecomm_type = "woocommerce";var pa_onsite_cart_info = '.json_encode($pa_onsite_cart_info).';</script>';
	}
}

function pa_onsite_messaging_woocommerce_version_check( $version = '2.6' ) {
    global $woocommerce;
    if( version_compare( $woocommerce->version, $version, ">=" ) ) {
        return true;
    }
    else{
        return false;
    }
}

function pa_onsite_messaging_on_order_placed($order_id) { //When user places an order

    //create an order instance
    $order = wc_get_order($order_id);

    $orderTotal = $order->get_total();
    $orderStatus = $order->get_status();

    if ($orderStatus == 'completed' || $orderStatus == 'processing' || $orderStatus == 'on-hold') {
        pa_onsite_messaging_report_conversion("woo_".$order_id, $orderTotal);
    }
}

add_filter('woocommerce_rest_prepare_product_object', 'custom_change_product_response', 20, 3);
//add_filter('woocommerce_rest_prepare_product_variation_object', 'custom_change_product_response', 20, 3);

function custom_change_product_response($response, $object, $request) {
    $variations = $response->data['variations'];
    
    $variations_array = array();
    if (!empty($variations) && is_array($variations)) {
        foreach ($variations as $variation) {
            $variations_res = array();
            $variation_id = $variation;
            $variation = new WC_Product_Variation($variation_id);
            $variations_res['id'] = $variation_id;
            $variations_res['on_sale'] = $variation->is_on_sale();
            $variations_res['regular_price'] = (float)$variation->get_regular_price();
            $variations_res['sale_price'] = (float)$variation->get_sale_price();
            $variations_res['price'] = (float)$variation->get_price();
            $variations_res['sku'] = $variation->get_sku();
            $variations_res['quantity'] = $variation->get_stock_quantity();
            if ($variations_res['quantity'] == null) {
                $variations_res['quantity'] = '';
            }
            $variations_res['stock_status'] = $variation->get_stock_status();
            
            $variations_res['image'] = wp_get_attachment_url($variation->get_image_id());
            $variations_res['name'] = $variation->get_name();

            $attributes = array();
            // variation attributes
            foreach ( $variation->get_variation_attributes() as $attribute_name => $attribute ) {
                // taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
                $attributes[] = array(
                    'name'   => wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ), $variation ),
                    'slug'   => str_replace( 'attribute_', '', wc_attribute_taxonomy_slug( $attribute_name ) ),
                    'option' => $attribute,
                );
            }

            $variations_res['attributes'] = $attributes;
            $variations_array[] = $variations_res;
        }
    }
    $response->data['product_variations'] = $variations_array;

    return $response;
}
?>
