<?php
use Tygh\Registry;
use Tygh\Settings;

if (!defined('BOOTSTRAP'))
{
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{

    if ($mode == 'webhookauth')
    {
        
        $response = file_get_contents("php://input");
        $response = json_decode($response, true);
        if (!empty($response))
        {
            $data = array(
                'consumer_key' => $response['consumer_key'],
                'consumer_secret' => $response['consumer_secret'],
                'key_id' => $response['key_id']
            );

            if ($response['user_id'])
            {
                db_query('UPDATE ?:wk_woocommerce_store SET ?u WHERE shop_id = ?i', $data, $response['user_id']);
            }
        }

        exit;
    }

    if ($mode == 'order_webhook')
    {
        $response = file_get_contents("php://input");
        // file_put_contents('temp_file/order_webhook_' . TIME . '.txt', $response);
        // file_put_contents('temp_file/order_webhook_req_' . TIME . '.txt', json_encode($_REQUEST));
        // exit;
        

        if (isset($_REQUEST['id']) && !empty($_REQUEST['id']))
        {
            $account_id = $_REQUEST['id'];
            $account_data = fn_get_wk_woocommerce_account_data($account_id);

            $woo_order_data = json_decode($response, true);

            $order_exist = db_get_field("SELECT id FROM ?:wk_woocommerce_order_map WHERE woocommerce_order_id = ?i", $woo_order_data['id']);
            if (!$order_exist)
            {
                // Tygh::$app['session']['cart'] = isset(Tygh::$app['session']['cart']) ? Tygh::$app['session']['cart'] : array();
                Tygh::$app['session']['cart'] = array();

                $cart = & Tygh::$app['session']['cart'];

                Tygh::$app['session']['customer_auth'] = isset(Tygh::$app['session']['customer_auth']) ? Tygh::$app['session']['customer_auth'] : array();
                $customer_auth = & Tygh::$app['session']['customer_auth'];

                Tygh::$app['session']['shipping_rates'] = isset(Tygh::$app['session']['shipping_rates']) ? Tygh::$app['session']['shipping_rates'] : array();
                $shipping_rates = & Tygh::$app['session']['shipping_rates'];

                if (empty($customer_auth))
                {
                    $customer_auth = fn_fill_auth(array() , array() , false, 'C');
                }
                $user_data = [
                    'b_address' => !empty($woo_order_data['billing']['address_1']) ? $woo_order_data['billing']['address_1'] : '', 
                    'b_address_2' => !empty($woo_order_data['billing']['address_2']) ? $woo_order_data['billing']['address_2'] : '', 
                    'b_city' => !empty($woo_order_data['billing']['city']) ? $woo_order_data['billing']['city'] : '', 
                    'b_country' => !empty($woo_order_data['billing']['country']) ? $woo_order_data['billing']['country'] : '', 
                    'b_firstname' => !empty($woo_order_data['billing']['first_name']) ? $woo_order_data['billing']['first_name'] : '', 
                    'b_lastname' => !empty($woo_order_data['billing']['last_name']) ? $woo_order_data['billing']['last_name'] : '', 
                    'b_phone' => !empty($woo_order_data['billing']['phone']) ? $woo_order_data['billing']['phone'] : '', 
                    'b_state' => !empty($woo_order_data['billing']['province']) ? $woo_order_data['billing']['province'] : '', 
                    'b_zipcode' => !empty($woo_order_data['billing']['zip']) ? $woo_order_data['billing']['zip'] : '', 
                    'email' => !empty($woo_order_data['billing']['email']) ? $woo_order_data['billing']['email'] : '', 
                    's_address' => !empty($woo_order_data['shipping']['address_1']) ? $woo_order_data['shipping']['address_1'] : '', 
                    's_address_2' => !empty($woo_order_data['shipping']['address_2']) ? $woo_order_data['shipping']['address_2'] : '', 
                    's_city' => !empty($woo_order_data['shipping']['city']) ? $woo_order_data['shipping']['city'] : '', 
                    's_country' => !empty($woo_order_data['shipping']['country']) ? $woo_order_data['shipping']['country'] : '', 
                    's_firstname' => !empty($woo_order_data['shipping']['first_name']) ? $woo_order_data['shipping']['first_name'] : '', 
                    's_lastname' => !empty($woo_order_data['shipping']['last_name']) ? $woo_order_data['shipping']['last_name'] : '', 
                    's_phone' => !empty($woo_order_data['shipping']['phone']) ? $woo_order_data['shipping']['phone'] : '', 
                    's_state' => !empty($woo_order_data['shipping']['province']) ? $woo_order_data['shipping']['province'] : '', 
                    's_zipcode' => !empty($woo_order_data['shipping']['zip']) ? $woo_order_data['shipping']['zip'] : '', 
                ];
                fn_add_user_data_descriptions($user_data);
                $cart['user_data'] = $user_data;
                $cart['ship_to_another'] = 1;
                if (empty($cart['order_id']) && (Registry::get('settings.Checkout.disable_anonymous_checkout') == 'Y' && !empty($user_data['password1'])))
                {
                    $cart['profile_registration_attempt'] = true;
                    list($user_id) = fn_update_user(0, $cart['user_data'], $customer_auth, !empty($_REQUEST['ship_to_another']) , true);

                    if ($user_id == false)
                    {
                        $action = '';
                    }
                    else
                    {
                        $cart['user_id'] = $user_id;
                        $u_data = db_get_row('SELECT user_id, tax_exempt, user_type FROM ?:users WHERE user_id = ?i', $cart['user_id']);
                        $customer_auth = fn_fill_auth($u_data, array() , false, 'C');
                        $cart['user_data'] = array();
                    }
                }

                $newCartProd = fn_woocommerce_fetch_cscart_product($woo_order_data['line_items'], $account_data);
                fn_add_product_to_cart($newCartProd, $cart, $customer_auth);
                fn_update_cart_by_data($cart, array() , $customer_auth);
                $cart['notes'] = !empty($woo_order_data['customer_note']) ? $woo_order_data['customer_note'] : '';
                // Set Payment ID
                $payemnt_id = db_get_field("SELECT payment_id FROM ?:wk_woocommerce_payment_map WHERE `woocommerce_payment_id`=?s AND `account_id`=?i", $woo_order_data['payment_method'], $account_data['shop_id']);

                if (!empty($payemnt_id))
                {
                    $cart['payment_id'] = $payemnt_id;
                }
                else
                {
                    $cart['payment_id'] = $account_data['default_payment'];
                }

                // Set Shipping ID
                $cart['shipping_ids'] = array(
                    $account_data['default_shipping']
                );
                if (!empty($cart['shipping_ids']))
                {
                    fn_checkout_update_shipping($cart, $cart['shipping_ids']);
                }

                list($cart_products, $product_groups) = fn_calculate_cart_content($cart, $customer_auth);
                $_REQUEST['dispatch'] = Registry::get('runtime.controller') . '.' . Registry::get('runtime.mode');

                list($order_id, $process_payment) = fn_place_order($cart, $customer_auth, 'save', Tygh::$app['session']['auth']['user_id']);

                if ($order_id)
                {
                    $data = array(
                        'account_id' => $account_data['shop_id'],
                        'order_id' => $order_id,
                        'woocommerce_order_total' => $woo_order_data['total'],
                        'woo_order_status' => $woo_order_data['status'],
                        'currency' => $woo_order_data['currency'],
                        'woocommerce_order_id' => $woo_order_data['id'],
                    );
                    db_query("INSERT INTO ?:wk_woocommerce_order_map ?e", $data);
                }
            }
        }
        exit;
    }

    if ($mode == 'product_update_webhook')
    {
        $response = file_get_contents("php://input");
        // file_put_contents('product_update_webhook_'.rand().'.txt', $response);
        // file_put_contents('product_update_webhook_req_'.rand().'.txt', json_encode($_REQUEST));
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id']))
        {
            $account_id = $_REQUEST['id'];
            $account_data = fn_get_wk_woocommerce_account_data($account_id);
            $status = $_REQUEST['woocommerce_product_status']??'any';
            if ($account_data)
            {
                list($mapped_category, $search_params) = woocommerce_get_mapped_category($account_data['shop_id']);
                list($mapped_feature,) = woocommerce_get_mapped_product_attributes($account_data['shop_id']);

                $product_data = json_decode($response, true);
                // die();
                $check = fn_check_woocommerce_product_availability($product_data['id'], $account_data['shop_id']);
                if (!empty($check))
                {
                    if (isset($check['product_id']))
                    {
                        $product_id = $check['product_id'];
                        // if (!empty($product_data['sale_price']))
                        // {
                            $is_variation = "";
                            fn_update_woocommerce_product_on_store($product_data, $product_id, $account_data, $mapped_category, $mapped_feature, $is_variation);
                        // }
                        
                    }
                    else
                    {
                        $product_id = 0;
                    }

                }
            }
        }
        exit;
    }
    if ($mode == 'product_create_webhook')
    {
        $response = file_get_contents("php://input");
        // file_put_contents('product_update_webhook_'.rand().'.txt', $response);
        // file_put_contents('product_update_webhook_req_'.rand().'.txt', json_encode($_REQUEST));
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id']))
        {
            $account_id = $_REQUEST['id'];
            $account_data = fn_get_wk_woocommerce_account_data($account_id);
            $status = $_REQUEST['woocommerce_product_status']??'any';
            if ($account_data)
            {
                list($mapped_category, $search_params) = woocommerce_get_mapped_category($account_data['shop_id']);
                list($mapped_feature,) = woocommerce_get_mapped_product_attributes($account_data['shop_id']);

                $product_data = json_decode($response, true);
                    $is_variation = "";
                    fn_create_woocommerce_product_on_store($product_data, 0, $account_data, $mapped_category, $mapped_feature, $is_variation);
            }
        }
        exit;
    }
}

