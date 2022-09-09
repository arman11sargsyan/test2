<?php
use Tygh\Registry;
use Tygh\Languages\Languages;
use Tygh\Enum\ProductTracking;
use Tygh\Addons\ProductVariations\Product\Manager as ProductManager;
use Tygh\Addons\ProductVariations\ServiceProvider;

if (!defined('BOOTSTRAP'))
{
    die('Access denied');
}

require __DIR__ . '/lib/vendor/autoload.php';

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

function fn_wk_woocommerce_install()
{
    $addon_name = fn_get_lang_var('wk_woocommerce');

    if (Registry::get('addons.vendor_privileges.status') == 'A')
    {
        $usergroup_id = db_get_field('SELECT usergroup_id FROM ?:usergroups WHERE status = ?s AND type = ?s', 'A', 'V');
        db_query("REPLACE INTO ?:usergroup_privileges (usergroup_id, privilege) VALUES ('" . $usergroup_id . "', 'manage_wk_woocommerce'), ('" . $usergroup_id . "', 'view_wk_woocommerce')");
    }

    fn_set_notification('S', __('well_done') , __('wk_woocommerce_webkul_user_guide_content', array(
        '[support_link]' => 'https://webkul.uvdesk.com/en/customer/create-ticket/',
        '[user_guide]' => 'https://webkul.com/blog/cs-cart-woocommerce-connector/',
        '[addon_name]' => $addon_name,
    )));

}

/**
 *  Get all merchant accounts list
 *
 * @param   array   $params       Search Params
 * 
 */

function fn_get_wk_woocommerce_account_list($params = array())
{
    $params = array_merge(array(
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page') ,
        'page' => 1
    ) , $params);

    $sortings = array(
        'shop_id' => 'shop_id',
        'app_name' => 'app_name',
        'company_id' => 'company_id',
        'timestamp' => 'timestamp',
        'status' => 'status'
    );

    $condition = $limit = $join = '';

    if (Registry::get('runtime.company_id'))
    {
        $condition = db_quote(" AND company_id = ?i", Registry::get('runtime.company_id'));
    }

    $sorting = db_sort($params, $sortings, 'shop_id', 'desc');

    if (isset($params['is_search']) && $params['is_search'] == "Y")
    {
        if (isset($params['status']) && !empty($params['status']))
        {
            $condition .= db_quote(" AND status LIKE ?l", "{$params['status']}");
        }
        if (isset($params['vendor']) && !empty($params['vendor']) && $params['vendor'] != 'all')
        {
            $condition .= db_quote(" AND company_id = ?i", "{$params['vendor']}");
        }
    }

    if (!empty($params['items_per_page']))
    {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:wk_woocommerce_store WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $accounts = db_get_array("SELECT * FROM ?:wk_woocommerce_store WHERE 1 $condition $sorting $limit");

    return array(
        $accounts,
        $params
    );
}

/**
 * Process product delete (run after product is deleted)
 *
 * @param int  $product_id      Product identifier
 * @param bool $product_deleted True if product was deleted successfully, false otherwise
 *
 * @return mixed
 */
function Fn_wk_woocommerce_delete_product_post($product_id, $product_deleted)
{
    if ($product_deleted && $product_id)
    {
        db_query("DELETE FROM ?:wk_woocommerce_product_map WHERE product_id = ?i", $product_id);
        db_query("DELETE FROM ?:wk_woocommerce_product_variation WHERE product_id = ?i OR parent_product_id = ?i", $product_id, $product_id);
    }
}

/**
 * Hook Handler to delete mapped orders
 *
 * @param [type] $order_id
 *
 * @return void
 */
function Fn_wk_woocommerce_delete_order($order_id)
{
    db_query("DELETE FROM ?:wk_woocommerce_order_map WHERE order_id = ?i", $order_id);
}


/**
 * Create Store order at woocommerce
 *
 * @param $order
 *
 */
function Fn_wk_woocommerce_create_order($order)
{
    if (Registry::get('runtime.mode') != 'order_webhook')
    {
        $line_items = [];
        foreach ($order['products'] as $key => $product_data)
        {
            $product_id = $product_data['product_id'];
            if (isset($product_data['product_type']) && $product_data['product_type'] == 'C')
            {
                $product_id = $product_data['extra']['variation_product_id'];
                $woocommerce_details = db_get_row('SELECT woocommerce_product_id, woocommerce_variation_id, account_id  FROM ?:wk_woocommerce_product_map WHERE product_id = ?i', $product_id);
                if (isset($woocommerce_details['woocommerce_product_id']) && !empty($woocommerce_details['woocommerce_product_id']) && isset($woocommerce_details['account_id']) && !empty($woocommerce_details['account_id']) && isset($woocommerce_details['variation_id']) && !empty($woocommerce_details['variation_id']))
                {
                    $line_items[$woocommerce_details['account_id']][] = ['product_id' => $woocommerce_details['woocommerce_product_id'], 'variation_id' => $woocommerce_details['variation_id'], 'quantity' => $product_data['amount'], ];
                }
            }
            else
            {
                //  If Normal Product
                $woocommerce_details = db_get_row('SELECT woocommerce_product_id , woocommerce_variation_id, account_id  FROM ?:wk_woocommerce_product_map WHERE product_id = ?i', $product_id);
                if (isset($woocommerce_details['woocommerce_product_id']) && !empty($woocommerce_details['woocommerce_product_id']) && isset($woocommerce_details['account_id']) && !empty($woocommerce_details['account_id']) && empty($woocommerce_details['woocommerce_variation_id']) )
                {
                    $line_items[$woocommerce_details['account_id']][] = ['product_id' => $woocommerce_details['woocommerce_product_id'], 'quantity' => $product_data['amount'], ];
                }
                else{
                    // If variation Product
                    $woocommerce_details = db_get_row('SELECT woocommerce_product_id, parent_product_id, woocommerce_variation_id, account_id  FROM ?:wk_woocommerce_product_map WHERE product_id = ?i', $product_id);
                    $woo_pro_od = db_get_field("SELECT woocommerce_product_id from ?:wk_woocommerce_product_map WHERE product_id = ?i", $woocommerce_details['parent_product_id'] );
                    $line_items[$woocommerce_details['account_id']][] = [
                        'product_id' => $woo_pro_od, 
                        'variation_id' => $woocommerce_details['woocommerce_variation_id'], 
                        'quantity' => $product_data['amount'], 
                    ];
                }
            }
        }
        if (count($line_items) > 0)
        {
            $cscart_order_id = db_get_next_auto_increment_id('orders');

            foreach ($line_items as $account_id => $item_arr)
            {

                list($mapped_payment_data,) = woocommerce_get_mapped_payment_methods($account_id, array() , true);
                $payment_method = $payment_method_title = '';
                if (!empty($mapped_payment_data) && isset($mapped_payment_data[$order['payment_id']]))
                {
                    $payment_method = $mapped_payment_data[$order['payment_id']]['woocommerce_payment_id'];
                    $payment_method_title = $mapped_payment_data[$order['payment_id']]['woocommerce_payment'];
                }

                list($mapped_shipping_data,) = woocommerce_get_mapped_shipping($account_id, array() , true);
                $method_id = $method_title = '';
                if (!empty($mapped_shipping_data) && isset($mapped_shipping_data[$order['shipping_ids']]))
                {
                    $method_id = $mapped_shipping_data[$order['shipping_ids']]['woocommerce_shipping_id'];
                    $method_title = $mapped_shipping_data[$order['shipping_ids']]['woocommerce_shipping'];
                }

                $data = ['payment_method' => $payment_method, 'payment_method_title' => $payment_method_title, 'set_paid' => true, 'billing' => ['first_name' => !empty($order['b_firstname']) ? $order['b_firstname'] : '', 'last_name' => !empty($order['b_lastname']) ? $order['b_lastname'] : '', 'address_1' => !empty($order['b_address']) ? $order['b_address'] : '', 'address_2' => '', 'city' => !empty($order['b_city']) ? $order['b_city'] : '', 'state' => !empty($order['b_state']) ? $order['b_state'] : '', 'postcode' => !empty($order['b_zipcode']) ? $order['b_zipcode'] : '', 'country' => !empty($order['b_country']) ? $order['b_country'] : '', 'email' => $order['email'],'phone' => !empty($order['b_phone']) ? $order['b_phone'] : ''],'shipping' => ['first_name' => !empty($order['s_firstname']) ? $order['s_firstname'] : '', 'last_name' => !empty($order['s_lastname']) ? $order['s_lastname'] : '', 'address_1' => !empty($order['s_address']) ? $order['s_address'] : '', 'address_2' => '', 'city' => !empty($order['s_city']) ? $order['s_city'] : '', 'state' => !empty($order['s_state']) ? $order['s_state'] : '', 'postcode' => !empty($order['s_zipcode']) ? $order['s_zipcode'] : '', 'country' => !empty($order['s_country']) ? $order['s_country'] : ''], 'line_items' => $item_arr, 'shipping_lines' => [['method_id' => $method_id, 'method_title' => $method_title, 'total' => (string)$order['shipping_cost']]]];

                $credentials = fn_get_wk_woocommerce_account_data($account_id);
                $params = array();
                if (!empty($credentials))
                {
                    $woo_orders_data = wk_woocommerce_api_call($credentials, 'POST', 'orders', $params, $data);

                    if (!empty($woo_orders_data))
                    {
                        $data = array(
                            'account_id' => $account_id,
                            'order_id' => $cscart_order_id,
                            'woocommerce_order_id' => $woo_orders_data['id'],
                            'currency' => $woo_orders_data['currency'],
                            'woo_order_status' => $woo_orders_data['status'],
                            'woocommerce_order_total' => $woo_orders_data['total']
                        );
                        $id = db_query("INSERT INTO ?:wk_woocommerce_order_map ?e", $data);
                    }
                }
            }
        }
    }
}


/**
 * Function to get Webhook Data
 *
 * @param $account_data
 *
 */
function fn_wk_woocoomerce_get_webhook_data(&$account_data)
{
    $webhook_ids = array();
    $webhook_type = array();
    if (isset($account_data['order_webhook_id']) && !empty($account_data['order_webhook_id']))
    {
        $webhook_ids[] = $account_data['order_webhook_id'];
        $webhook_type[$account_data['order_webhook_id']] = 'order_create';
    }
    if (isset($account_data['product_create_webhook_id']) && !empty($account_data['product_create_webhook_id']))
    {
        $webhook_ids[] = $account_data['product_create_webhook_id'];
        $webhook_type[$account_data['product_create_webhook_id']] = 'product_create';
    }
    if (isset($account_data['product_update_webhook_id']) && !empty($account_data['product_update_webhook_id']))
    {
        $webhook_ids[] = $account_data['product_update_webhook_id'];
        $webhook_type[$account_data['product_update_webhook_id']] = 'product_update';
    }
    if (!empty($webhook_ids))
    {
        $params = array(
            'include' => $webhook_ids
        );

        $webhooks_data = wk_woocommerce_api_call($account_data, 'GET', 'webhooks', $params);

        foreach ($webhooks_data as $webhook_data)
        {
            $account_data['webhook_data'][] = array(
                'id' => $webhook_data['id'],
                'type' => $webhook_type[$webhook_data['id']],
                'name' => $webhook_data['name'],
                'status' => $webhook_data['status'],
                'topic' => $webhook_data['topic'],
                'delivery_url' => $webhook_data['delivery_url']
            );
        }
    }
}

/**
 * Function to get woocommerce account Data
 *
 * @param $account_id
 *
 */
function fn_get_wk_woocommerce_account_data($account_id = 0)
{
    $condition = '';
    if (Registry::get('runtime.company_id'))
    {
        $condition = db_quote(" AND company_id = ?i", Registry::get('runtime.company_id'));
    }
    $account_data = db_get_row("SELECT * FROM ?:wk_woocommerce_store WHERE shop_id = ?i $condition", $account_id);

    return $account_data;
}


/**
 * Function to Delete woocommerce merchant account
 *
 * @param $account_id
 *
 */
function fn_wk_delete_woocommerce_merchant_account($account_id)
{
    $credentials = fn_get_wk_woocommerce_account_data($account_id);
    if ($credentials)
    {
        $webhooks = db_get_row("SELECT order_webhook_id, product_create_webhook_id, product_update_webhook_id FROM ?:wk_woocommerce_store WHERE shop_id = ?i", $account_id);

        foreach ($webhooks as $webhook_id)
        {
            if ($webhook_id)
            {
                $params['force'] = true;
                wk_woocommerce_api_call($credentials, 'DELETE', 'webhooks/' . $webhook_id, $params);
            }
        }

        db_query("DELETE FROM ?:wk_woocommerce_attribute_map WHERE account_id = ?i", $account_id);
        db_query("DELETE FROM ?:wk_woocommerce_order_map WHERE account_id = ?i", $account_id);
        db_query("DELETE FROM ?:wk_woocommerce_category_map WHERE account_id = ?i", $account_id);
        db_query("DELETE FROM ?:wk_woocommerce_payment_map WHERE account_id = ?i", $account_id);
        db_query("DELETE FROM ?:wk_woocommerce_product_map WHERE account_id = ?i", $account_id);
        db_query("DELETE FROM ?:wk_woocommerce_product_variation WHERE account_id = ?i", $account_id);
        db_query("DELETE FROM ?:wk_woocommerce_shipping_map WHERE account_id = ?i", $account_id);
        db_query("DELETE FROM ?:wk_woocommerce_store WHERE shop_id = ?i", $account_id);
    }
}

/**
 * Function to get woocommerce credentials using shop_id
 *
 * @param $account_id
 *
 */
function woocommerce_get_credential_by_id($shop_id)
{
    try
    {
        $credentials = array();
        $credentials = db_get_row('SELECT store_url , consumer_key, consumer_secret FROM ?:wk_woocommerce_store WHERE shop_id = ?i', $shop_id);
        return $credentials;
    }
    catch(Exception $e)
    {
        fn_set_notification('E', 'error', __('request_not_valid'));
    }
    catch(WoocommerceCurlException $e)
    {
        fn_set_notification('E', 'error', $e->getMessage());
    }
    return array();
}

/**
 * Function to Hit woocommerce api
 *
 * @param $account_id
 *
 */
function wk_woocommerce_api_call($credentials, $method, $path, $params = array() , $data = array())
{

    $response = array();
    $baseurl = $credentials['store_url'];

    try
    {
        $woocommerce = new Client($baseurl, $credentials['consumer_key'], $credentials['consumer_secret'], ['wp_api' => true, 'version' => 'wc/v2']);
        if ($method == 'GET')
        {
            $response = $woocommerce->get($path, $params);
        }
        elseif ($method == 'POST')
        {
            $response = $woocommerce->post($path, $data);
        }
        elseif ($method == 'PUT')
        {
            $response = $woocommerce->put($path, $data);
        }
        elseif ($method == 'DELETE')
        {
            $response = $woocommerce->delete($path, $params);
        }

    }
    catch(HttpClientException $e)
    {
        fn_set_notification('E', 'error', $e->getMessage());
    }
    $response = json_decode(json_encode($response) , true);
    return $response;

}

/**
 * Function to get mapped Category
 *
 * @param $account_id
 *
 */
function woocommerce_get_mapped_category($account_id = 0, $params = array())
{
    if ($account_id == 0)
    {
        return;
    }
    $params = array_merge(array(
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page') ,
        'page' => 1
    ) , $params);

    $sortings = array(
        'id' => 'id',
        'account_id' => 'account_id',
        'category_id' => 'category_id',
        'woocommerce_category_id' => 'woocommerce_category_id',
        'woocommerce_category' => 'woocommerce_category'
    );

    $condition = $limit = $join = '';

    $join .= " LEFT JOIN ?:wk_woocommerce_store ON ?:wk_woocommerce_category_map.account_id = ?:wk_woocommerce_store.shop_id";
    if (Registry::get('runtime.company_id'))
    {
        $condition .= db_quote(" AND ?:wk_woocommerce_store.company_id = ?i", Registry::get('runtime.company_id'));
    }

    $condition .= db_quote(" AND account_id = ?i", $account_id);

    if (!empty($params['category_id']))
    {
        $condition .= db_quote(" AND category_id = ?i", $params['category_id']);
    }

    if (!empty($params['woocommerce_category_id']))
    {
        $condition .= db_quote(" AND woocommerce_category_id = ?i", $params['woocommerce_category_id']);
    }

    if (!empty($params['woocommerce_category']))
    {
        $condition .= db_quote(" AND woocommerce_category = ?s", $params['woocommerce_category']);
    }

    $sorting = db_sort($params, $sortings, 'id', 'desc');

    if (!empty($params['items_per_page']))
    {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:wk_woocommerce_category_map $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    // $accounts = db_get_array("SELECT * FROM ?:wk_woocommerce_category_map WHERE 1 $condition $sorting $limit");
    $accounts = db_get_hash_array("SELECT * FROM ?:wk_woocommerce_category_map $join WHERE 1 $condition $sorting $limit", 'woocommerce_category_id');

    return array(
        $accounts,
        $params
    );
}

/**
 * Function to get mapped Shipping
 *
 * @param $account_id
 *
 */
function woocommerce_get_mapped_shipping($account_id = 0, $params = array() , $wk_order = false)
{
    if ($account_id == 0)
    {
        return;
    }
    $params = array_merge(array(
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page') ,
        'page' => 1
    ) , $params);

    $sortings = array(
        'id' => 'id',
        'account_id' => 'account_id',
        'shipping_id' => 'shipping_id',
        'woocommerce_shipping_id' => 'woocommerce_shipping_id',
        'woocommerce_shipping' => 'woocommerce_shipping'
    );

    $condition = $limit = $join = '';

    $join .= " LEFT JOIN ?:wk_woocommerce_store ON ?:wk_woocommerce_shipping_map.account_id = ?:wk_woocommerce_store.shop_id";
    if (Registry::get('runtime.company_id'))
    {
        $condition .= db_quote(" AND ?:wk_woocommerce_store.company_id = ?i", Registry::get('runtime.company_id'));
    }

    $condition .= db_quote(" AND account_id = ?i", $account_id);

    if (!empty($params['shipping_id']))
    {
        $condition .= db_quote(" AND shipping_id = ?i", $params['shipping_id']);
    }

    if (!empty($params['woocommerce_shipping_id']))
    {
        $condition .= db_quote(" AND woocommerce_shipping_id = ?i", $params['woocommerce_shipping_id']);
    }

    if (!empty($params['woocommerce_shipping']))
    {
        $condition .= db_quote(" AND woocommerce_shipping = ?s", $params['woocommerce_shipping']);
    }

    $sorting = db_sort($params, $sortings, 'id', 'desc');

    if (!empty($params['items_per_page']))
    {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:wk_woocommerce_shipping_map $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    // $accounts = db_get_array("SELECT * FROM ?:wk_woocommerce_shipping_map WHERE 1 $condition $sorting $limit");
    if ($wk_order) $accounts = db_get_hash_array("SELECT * FROM ?:wk_woocommerce_shipping_map $join WHERE 1 $condition $sorting $limit", 'shipping_id');
    else $accounts = db_get_hash_array("SELECT * FROM ?:wk_woocommerce_shipping_map $join WHERE 1 $condition $sorting $limit", 'woocommerce_shipping_id');

    return array(
        $accounts,
        $params
    );
}

/**
 * Function to get mapped Payment Methods
 *
 * @param $account_id
 *
 */
function woocommerce_get_mapped_payment_methods($account_id = 0, $params = array() , $wk_order = false)
{
    if ($account_id == 0)
    {
        return;
    }
    $params = array_merge(array(
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page') ,
        'page' => 1
    ) , $params);

    $sortings = array(
        'id' => 'id',
        'account_id' => 'account_id',
        'payment_id' => 'payment_id',
        'woocommerce_payment_id' => 'woocommerce_payment_id',
        'woocommerce_payment' => 'woocommerce_payment'
    );

    $condition = $limit = $join = '';

    $join .= " LEFT JOIN ?:wk_woocommerce_store ON ?:wk_woocommerce_payment_map.account_id = ?:wk_woocommerce_store.shop_id";
    if (Registry::get('runtime.company_id'))
    {
        $condition .= db_quote(" AND ?:wk_woocommerce_store.company_id = ?i", Registry::get('runtime.company_id'));
    }

    $condition .= db_quote(" AND account_id = ?i", $account_id);

    if (!empty($params['payment_id']))
    {
        $condition .= db_quote(" AND payment_id = ?i", $params['payment_id']);
    }

    if (!empty($params['woocommerce_payment_id']))
    {
        $condition .= db_quote(" AND woocommerce_payment_id = ?i", $params['woocommerce_payment_id']);
    }

    if (!empty($params['woocommerce_payment']))
    {
        $condition .= db_quote(" AND woocommerce_payment = ?s", $params['woocommerce_payment']);
    }

    $sorting = db_sort($params, $sortings, 'id', 'desc');

    if (!empty($params['items_per_page']))
    {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:wk_woocommerce_payment_map $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    // $accounts = db_get_array("SELECT * FROM ?:wk_woocommerce_payment_map WHERE 1 $condition $sorting $limit");
    if ($wk_order) $accounts = db_get_hash_array("SELECT * FROM ?:wk_woocommerce_payment_map $join WHERE 1 $condition $sorting $limit", 'payment_id');
    else $accounts = db_get_hash_array("SELECT * FROM ?:wk_woocommerce_payment_map $join WHERE 1 $condition $sorting $limit", 'woocommerce_payment_id');

    return array(
        $accounts,
        $params
    );
}

/**
 * Function to get mapped Product Attributes
 *
 * @param $account_id
 *
 */
function woocommerce_get_mapped_product_attributes($account_id = 0, $params = array())
{
    if ($account_id == 0)
    {
        return;
    }
    $params = array_merge(array(
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page') ,
        'page' => 1
    ) , $params);

    $sortings = array(
        'id' => 'id',
        'account_id' => 'account_id',
        'feature_id' => 'feature_id',
        'woocommerce_attribute_id' => 'woocommerce_attribute_id',
        'woocommerce_attribute' => 'woocommerce_attribute'
    );

    $condition = $limit = $join = '';

    $join .= " LEFT JOIN ?:wk_woocommerce_store ON ?:wk_woocommerce_attribute_map.account_id = ?:wk_woocommerce_store.shop_id";
    if (Registry::get('runtime.company_id'))
    {
        $condition .= db_quote(" AND ?:wk_woocommerce_store.company_id = ?i", Registry::get('runtime.company_id'));
    }

    $condition .= db_quote(" AND account_id = ?i", $account_id);

    if (!empty($params['feature_id']))
    {
        $condition .= db_quote(" AND feature_id = ?i", $params['feature_id']);
    }

    if (!empty($params['woocommerce_attribute_id']))
    {
        $condition .= db_quote(" AND woocommerce_attribute_id = ?i", $params['woocommerce_attribute_id']);
    }

    if (!empty($params['woocommerce_attribute']))
    {
        $condition .= db_quote(" AND woocommerce_attribute = ?s", $params['woocommerce_attribute']);
    }

    $sorting = db_sort($params, $sortings, 'id', 'desc');

    if (!empty($params['items_per_page']))
    {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:wk_woocommerce_attribute_map $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    // $accounts = db_get_array("SELECT * FROM ?:wk_woocommerce_attribute_map WHERE 1 $condition $sorting $limit");
    $accounts = db_get_hash_array("SELECT * FROM ?:wk_woocommerce_attribute_map $join WHERE 1 $condition $sorting $limit", 'woocommerce_attribute_id');

    return array(
        $accounts,
        $params
    );
}

function woocommerce_get_collections($shop_id)
{
    try
    {
        $collections = array();
        $credentials = fn_get_wk_woocommerce_account_data($shop_id);
        if (empty($credentials))
        {
            return $collections;
        }

        // $collections = wk_woocommerce_api_call($credentials, 'GET', 'products/categories');
        $params = array(
            'page' => 1,
            'per_page' => 100
        );
        do
        {
            $data = wk_woocommerce_api_call($credentials, 'GET', 'products/categories', $params);
            $collections = array_merge($collections, $data);
            $params['page'] += 1;
        }
        while (count($data) > 0);

        return $collections;

    }
    catch(HttpClientException $e)
    {
        fn_set_notification('E', 'error', $e->getMessage());
    }
}

/**
 * Function to get Wocommerce Shippings
 *
 * @param $account_id
 *
 */
function woocommerce_get_shippings($shop_id)
{
    try
    {
        $shippings = array();
        $credentials = fn_get_wk_woocommerce_account_data($shop_id);
        if (empty($credentials))
        {
            return $shippings;
        }

        $shippings = wk_woocommerce_api_call($credentials, 'GET', 'shipping_methods');
        // $params['email'] = 'test@webkul.com';
        // $params['role'] = 'subscriber';
        // $customers = wk_woocommerce_api_call($credentials, 'GET', 'customers', $params);
        // fn_print_r($customers);
        // exit;
        return $shippings;

    }
    catch(HttpClientException $e)
    {
        fn_set_notification('E', 'error', $e->getMessage());
    }
}

/**
 * Function to get Wocommerce Payment Methods
 *
 * @param $account_id
 *
 */
function woocommerce_get_payments($shop_id)
{
    try
    {
        $payments = array();
        $credentials = fn_get_wk_woocommerce_account_data($shop_id);
        if (empty($credentials))
        {
            return $payments;
        }

        $payments = wk_woocommerce_api_call($credentials, 'GET', 'payment_gateways');

        return $payments;

    }
    catch(HttpClientException $e)
    {
        fn_set_notification('E', 'error', $e->getMessage());
    }
}

/**
 * Function to get Wocommerce Attributes
 *
 * @param $account_id
 *
 */
function woocommerce_get_attributes($shop_id)
{
    try
    {
        $attributes = array();
        $credentials = fn_get_wk_woocommerce_account_data($shop_id);
        if (empty($credentials))
        {
            return $attributes;
        }
        $attributes = wk_woocommerce_api_call($credentials, 'GET', 'products/attributes');

        return $attributes;

    }
    catch(HttpClientException $e)
    {
        fn_set_notification('E', 'error', $e->getMessage());
    }
}

/**
 * Function to Map Categories
 *
 * @param $account_id
 *
 */
function fn_map_new_woocommerce_category($params = array())
{
    $account_id = $_REQUEST['account_id'];
    $category_id = $_REQUEST['cs_cart_category'];
    $woocommerce_category_id = $_REQUEST['woocommerce_category'];
    $woocommerce_category_name = $_REQUEST['woocommerce_category_name'];
    $woocommerce_check = db_get_field("SELECT category_id FROM ?:wk_woocommerce_category_map WHERE woocommerce_category_id = ?i AND account_id = ?i", $woocommerce_category_id, $account_id);
    if ($woocommerce_check && $woocommerce_check != $category_id)
    {
        fn_set_notification("E", __("error") , $woocommerce_category_name . __("wk_already_mapped_with_store_category") . '&nbsp;' . fn_get_category_name($category_id));
        return false;
    }
    if ($woocommerce_check == $category_id)
    {
        return true;
    }
    $cscart_check = db_get_row("SELECT woocommerce_category_id, woocommerce_category FROM ?:wk_woocommerce_category_map WHERE category_id = ?i AND account_id = ?i", $category_id, $account_id);

    if ($cscart_check)
    {
        if ($cscart_check['woocommerce_category_id'] != $woocommerce_category_id)
        {
            fn_set_notification("E", __("error") , fn_get_category_name($category_id) . __("already_mapped_with_another_woocommerce_category") . '&nbsp;' . $cscart_check['woocommerce_category']);
            return false;
        }
        else
        {
            return true;
        }
    }

    $data = array(
        'category_id' => $category_id,
        'woocommerce_category' => $woocommerce_category_name,
        'woocommerce_category_id' => $woocommerce_category_id,
        'account_id' => $account_id
    );
    db_query("INSERT INTO ?:wk_woocommerce_category_map ?e", $data);
    return true;
}

/**
 *  WooCommerce Order Mapping Data
 *
 * @param   int     $account_id   WooCommerce Seller Account Identifier
 * @param   array   $params       Search Params
 * @param   string  $lang_code    langcode
 */
function fn_get_woocommerce_orders_list($account_id = 0, $params = array() , $lang_code = DESCR_SL)
{
    if ($account_id == 0)
    {
        return;
    }
    $order_list = array();
    $params = array_merge(array(
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page') ,
        'page' => 1
    ) , $params);

    $sortings = array(
        'woocommerce_order_id' => '?:wk_woocommerce_order_map.woocommerce_order_id',
        'currency' => '?:wk_woocommerce_order_map.currency',
        'woo_order_status' => '?:wk_woocommerce_order_map.woo_order_status',
        'woocommerce_order_total' => '?:wk_woocommerce_order_map.woocommerce_order_total',
        'order_id' => '?:wk_woocommerce_order_map.order_id',
    );

    $condition = $limit = $join = '';

    $join .= " LEFT JOIN ?:wk_woocommerce_store ON ?:wk_woocommerce_order_map.account_id = ?:wk_woocommerce_store.shop_id";
    if (Registry::get('runtime.company_id'))
    {
        $condition .= db_quote(" AND ?:wk_woocommerce_store.company_id = ?i", Registry::get('runtime.company_id'));
    }

    $sorting = db_sort($params, $sortings, 'order_id', 'desc');

    $condition .= db_quote(" AND ?:wk_woocommerce_order_map.account_id = ?i", $account_id);

    $fields = array(
        '?:wk_woocommerce_order_map.order_id',
        '?:wk_woocommerce_order_map.woocommerce_order_id',
        '?:wk_woocommerce_order_map.woocommerce_order_total',
        '?:wk_woocommerce_order_map.woo_order_status',
        '?:wk_woocommerce_order_map.currency',
        '?:wk_woocommerce_order_map.account_id',
    );

    if (!empty($params['items_per_page']))
    {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:wk_woocommerce_order_map $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    $order_list = db_get_array("SELECT ?p FROM ?:wk_woocommerce_order_map $join WHERE 1 $condition $sorting $limit", implode(", ", $fields));

    return array(
        $order_list,
        $params
    );
}

/**
 *  WooCommerce Product Mapping Data
 *
 * @param   int     $account_id   WooCommerce Seller Account Identifier
 * @param   array   $params       Search Params
 * @param   string  $lang_code    langcode of product
 */
function fn_get_woocommerce_products_list($account_id = 0, $params = array() , $lang_code = DESCR_SL)
{
    if ($account_id == 0)
    {
        return;
    }
    $products_list = array();
    $params = array_merge(array(
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page') ,
        'page' => 1
    ) , $params);

    $sortings = array(
        'woocommerce_product_id' => '?:wk_woocommerce_product_map.woocommerce_product_id',
        'product_id' => '?:wk_woocommerce_product_map.product_id',
        'product' => '?:product_descriptions.product',
        'price' => '?:product_prices.price',
        'quantity' => '?:products.amount',
        'id' => '?:wk_woocommerce_product_map.id',
        'status' => '?:wk_woocommerce_product_map.status',
    );

    $condition = $limit = $join = '';

    $join .= " LEFT JOIN ?:products ON ?:wk_woocommerce_product_map.product_id = ?:products.product_id";
    $join .= " LEFT JOIN ?:product_descriptions ON ?:wk_woocommerce_product_map.product_id = ?:product_descriptions.product_id";
    $join .= " LEFT JOIN ?:product_prices ON ?:wk_woocommerce_product_map.product_id = ?:product_prices.product_id";
    $join .= " LEFT JOIN ?:wk_woocommerce_store ON ?:wk_woocommerce_product_map.account_id = ?:wk_woocommerce_store.shop_id";

    $sorting = db_sort($params, $sortings, 'id', 'asc');

    $condition .= db_quote(" AND ?:product_descriptions.lang_code = ?s", $lang_code);
    $condition .= db_quote(" AND ?:wk_woocommerce_product_map.account_id = ?i", $account_id);

    if (Registry::get('runtime.company_id'))
    {
        $condition .= db_quote(" AND ?:wk_woocommerce_store.company_id = ?i", Registry::get('runtime.company_id'));
    }

    if (isset($params['product_id']) && !empty($params['product_id']))
    {
        $condition .= db_quote(' AND ?:wk_woocommerce_product_map.product_id = ?i', $params['product_id']);
    }

    if (isset($params['product']) && !empty($params['product']))
    {
        $piece = '%' . $params['product'] . '%';
        $condition .= db_quote(' AND ?:product_descriptions.product LIKE ?l', $piece);
    }

    if (isset($params['woocommerce_product_id']) && !empty($params['woocommerce_product_id']))
    {
        $condition .= db_quote(' AND ?:wk_woocommerce_product_map.woocommerce_product_id = ?s', $params['woocommerce_product_id']);
    }

    if (isset($params['quantity']) && !empty($params['quantity']))
    {
        $condition .= db_quote(' AND ?:products.amount = ?i', $params['quantity']);
    }

    $fields = array(
        '?:wk_woocommerce_product_map.product_id',
        '?:wk_woocommerce_product_map.woocommerce_product_id',
        '?:wk_woocommerce_product_map.account_id',
        '?:products.amount',
        '?:product_prices.price',
        '?:product_descriptions.product',
        '?:product_descriptions.lang_code',
        '?:wk_woocommerce_product_map.status',
        '?:wk_woocommerce_product_map.id',
    );

    if (!empty($params['items_per_page']))
    {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:wk_woocommerce_product_map $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    $products_list = db_get_array("SELECT ?p FROM ?:wk_woocommerce_product_map $join WHERE 1 $condition $sorting $limit", implode(", ", $fields));

    return array(
        $products_list,
        $params
    );
}

/**
 *  Getting WooCommerce product Data From WooCommerce site
 *
 * @param   array   $account_data           WooCommerce Seller Account Data
 * @param   string  $string_product_ids     WooCommerce Product Ids by comma seperated
 * @param   string  $status                 status of product data
 */
function fn_import_products_from_woocommerce($account_data = array() , $string_product_ids = null, $status = 'any')
{
    $listing_ids = array();
    $result = array();
    $product_id = 0;
    try
    {
        if (Registry::get('runtime.action') != 'cron') fn_set_progress('echo', __('fetching_product_data_from_woocommerce'));
        if ($string_product_ids)
        {
            $product_ids = array_map('intval', explode(',', $string_product_ids));
            fn_set_progress('parts', count($product_ids));
            foreach ($product_ids as $product_id)
            {
                if (Registry::get('runtime.action') != 'cron') fn_set_progress('echo', __('fetching_product_data_from_woocommerce_for_id', array(
                    '[product_id]' => $product_id
                )));
                try
                {
                    $data = wk_woocommerce_api_call($account_data, 'GET', 'products/' . $product_id);
                    if ($data)
                    {
                        $result[] = $data;
                    }
                }
                catch(HttpClientException $e)
                {
                    fn_set_notification('E', __("error") , $e->getMessage());
                }
            }
        }
        else
        {
            $shop_id = $account_data['shop_id'];

            // $params = array('limit' => 100,
            //     'offset'=>0,
            //     'status' => $status
            // );
            $params = array(
                'page' => 1,
                'per_page' => 100,
                'status' => $status
            );

            fn_set_progress('echo', __("fetching_product_data_from_woocommerce"));

            $result = fn_import_products_from_woocommerce_by_status($account_data, $status, $params);

        }
        // fn_print_r(count($result), $result);
        // exit;
        if (!empty($result))
        {
            $count = count($result);
            fn_set_progress('parts', $count);
            $ii = 1;

            list($mapped_category, $search_params) = woocommerce_get_mapped_category($account_data['shop_id']);
            list($mapped_feature,) = woocommerce_get_mapped_product_attributes($account_data['shop_id']);

            foreach ($result as $product_data)
            {
                fn_set_progress('echo', $ii++ . ' of ' . $count . ' <b>' . $product_data['name'] . '</b>&nbsp;' . __('creating_on_store'));

                $check = fn_check_woocommerce_product_availability($product_data['id'], $account_data['shop_id']);
                $product_id = isset($check['product_id'])?$check['product_id']:0;
                if(empty($product_id)){
                    $product_id = fn_create_woocommerce_product_on_store($product_data, $product_id, $account_data, $mapped_category, $mapped_feature);
                }
                
            }

            fn_set_notification("N", __("notice") , count($result) . '&nbsp;' . __("products_created_updated_on_store") , 'S');
        }
        else
        {
            // $data = !empty($listing_ids) ?' Listing Ids:'.json_encode($listing_ids):' Listing Status:'.$status;
            fn_set_notification("N", __("notice") , __("no_products_found_to_import") , 'I');
        }
    }
    catch(Etsy\HttpClientException $e)
    {
        fn_set_notification('E', __("error") , $e->getMessage());
    }

    return $product_id;
}

/**
 *  Getting WooCommerce product Data From WooCommerce site on the basis of status
 *
 * @param   array   $account_data           WooCommerce Seller Account Data
 * @param   string  $status                 WooCommerce Product Status
 * @param   array   $params                 Search Params
 * @param   string  $result                 
 */
function fn_import_products_from_woocommerce_by_status($account_data, $status = 'publish', $params = array() , $result = array())
{
    try
    {
        do
        {
            $data = wk_woocommerce_api_call($account_data, 'GET', 'products/', $params);
            $result = array_merge($result, $data);
            $params['page'] += 1;
        }
        while (count($data) > 0);

    }
    catch(Exception $e)
    {
        fn_set_notification('E', __("error") , $e->getMessage());
        return $result;
    }
    return $result;
}

/**
 *  Checking WooCommerce products Mapping with Cs-Cart Store product
 *
 * @param   int     $woo_product_id Woocommerce Product Id
 * @param   int     $account_id     Woocommerce Account Id
 * @return  boolean $result         true or false
 */
function fn_check_woocommerce_product_availability($woo_product_id, $account_id, $product_id = 0)
{
    $result = array();
    if ($product_id)
    {
        $result = db_get_row("SELECT * FROM ?:wk_woocommerce_product_map WHERE product_id = ?i AND account_id = ?i", $product_id, $account_id);
    }
    else
    {
        $result = db_get_row("SELECT * FROM ?:wk_woocommerce_product_map WHERE woocommerce_product_id = ?i AND account_id = ?i", $woo_product_id, $account_id);
        if (empty($result))
        {
            $result = db_get_row("SELECT * FROM ?:wk_woocommerce_product_map WHERE woocommerce_variation_id = ?i AND account_id = ?i", $woo_product_id, $account_id);
            if (!empty($result))
            {
                $result['is_variation_product'] = true;
            }
        }
    }

    return $result;
}

/**
 * Download Categories from WooCommerce shop function
 *
 * @param array $account_id account Id from where categories need to download
 *
 * @return bool
 */
function Fn_Download_woocommerce_categories($account_id = 0)
{
    $result = array(
        'total' => 0,
        'updated' => 0,
        'new' => 0
    );
    try
    {
        $categories = woocommerce_get_collections($account_id);
        if (!empty($categories))
        {
            $mapped_categoy_ids = db_get_fields("SELECT woocommerce_category_id FROM ?:wk_woocommerce_category_map WHERE account_id = ?i", $account_id);
            foreach ($categories as $category)
            {
                $result['total']++;
                $data = array(
                    'woocommerce_category_id' => $category['id'],
                    'woocommerce_category' => $category['name']
                );
                if (in_array($category['id'], $mapped_categoy_ids))
                {
                    $result['updated']++;
                    db_query("UPDATE ?:wk_woocommerce_category_map SET ?u WHERE account_id = ?i AND woocommerce_category_id = ?i", $data, $account_id, $category['id']);
                }
                else
                {
                    $result['new']++;
                    $data['account_id'] = $account_id;
                    db_query("INSERT INTO ?:wk_woocommerce_category_map ?e", $data);
                }
            }
            $final_import_notification = __('text_category_data_imported', array(
                '[new]' => $result['new'],
                '[exist]' => $result['updated'],
                '[total]' => $result['total']
            ));
            fn_set_notification('N', __('success') , $final_import_notification);
            return true;
        }
    }
    catch(Exception $e)
    {
        fn_set_notification('E', __("error") . $e->getMessage());
    }
    return false;
}

/**
 * Download shipping methods from WooCommerce shop
 *
 * @param array $account_id account Id from where shipping methods need to download
 *
 * @return bool
 */
function Fn_Download_woocommerce_shippings($account_id = 0)
{
    $result = array(
        'total' => 0,
        'updated' => 0,
        'new' => 0
    );
    try
    {
        $shippings = woocommerce_get_shippings($account_id);
        if (!empty($shippings))
        {
            $mapped_shipping_ids = db_get_fields("SELECT woocommerce_shipping_id FROM ?:wk_woocommerce_shipping_map WHERE account_id = ?i", $account_id);

            foreach ($shippings as $shipping)
            {
                $result['total']++;
                $data = array(
                    'woocommerce_shipping_id' => $shipping['id'],
                    'woocommerce_shipping' => $shipping['title']
                );
                if (in_array($shipping['id'], $mapped_shipping_ids))
                {
                    $result['updated']++;
                    db_query("UPDATE ?:wk_woocommerce_shipping_map SET ?u WHERE account_id = ?i AND woocommerce_shipping_id = ?s", $data, $account_id, $shipping['id']);
                }
                else
                {
                    $result['new']++;
                    $data['account_id'] = $account_id;
                    db_query("INSERT INTO ?:wk_woocommerce_shipping_map ?e", $data);
                }
            }
            $final_import_notification = __('text_shipping_data_imported', array(
                '[new]' => $result['new'],
                '[exist]' => $result['updated'],
                '[total]' => $result['total']
            ));
            fn_set_notification('N', __('success') , $final_import_notification);
            return true;
        }
    }
    catch(Exception $e)
    {
        fn_set_notification('E', __("error") . $e->getMessage());
    }
    return false;
}

/**
 * Download payment methods from WooCommerce shop
 *
 * @param array $account_id account Id from where payment methods need to download
 *
 * @return bool
 */
function Fn_Download_woocommerce_payments($account_id = 0)
{
    $result = array(
        'total' => 0,
        'updated' => 0,
        'new' => 0
    );
    try
    {
        $payments = woocommerce_get_payments($account_id);
        if (!empty($payments))
        {
            $mapped_payment_ids = db_get_fields("SELECT woocommerce_payment_id FROM ?:wk_woocommerce_payment_map WHERE account_id = ?i", $account_id);

            foreach ($payments as $payment)
            {
                $result['total']++;
                $data = array(
                    'woocommerce_payment_id' => $payment['id'],
                    'woocommerce_payment' => $payment['title']
                );
                if (in_array($payment['id'], $mapped_payment_ids))
                {
                    $result['updated']++;
                    db_query("UPDATE ?:wk_woocommerce_payment_map SET ?u WHERE account_id = ?i AND woocommerce_payment_id = ?s", $data, $account_id, $payment['id']);
                }
                else
                {
                    $result['new']++;
                    $data['account_id'] = $account_id;
                    db_query("INSERT INTO ?:wk_woocommerce_payment_map ?e", $data);
                }
            }
            $final_import_notification = __('text_payment_data_imported', array(
                '[new]' => $result['new'],
                '[exist]' => $result['updated'],
                '[total]' => $result['total']
            ));
            fn_set_notification('N', __('success') , $final_import_notification);
            return true;
        }
    }
    catch(Exception $e)
    {
        fn_set_notification('E', __("error") . $e->getMessage());
    }
    return false;
}

/**
 * Download product attributes from WooCommerce shop
 *
 * @param array $account_id account Id from where product attributes need to download
 *
 * @return bool
 */
function Fn_Download_woocommerce_attributes($account_id = 0)
{
    $result = array(
        'total' => 0,
        'updated' => 0,
        'new' => 0
    );
    try
    {
        $attributes = woocommerce_get_attributes($account_id);
        if (!empty($attributes))
        {
            $mapped_attribute_ids = db_get_fields("SELECT woocommerce_attribute_id FROM ?:wk_woocommerce_attribute_map WHERE account_id = ?i", $account_id);

            foreach ($attributes as $attribute)
            {
                $result['total']++;
                $data = array(
                    'woocommerce_attribute_id' => $attribute['id'],
                    'woocommerce_attribute' => $attribute['name']
                );
                if (in_array($attribute['id'], $mapped_attribute_ids))
                {
                    $result['updated']++;
                    db_query("UPDATE ?:wk_woocommerce_attribute_map SET ?u WHERE account_id = ?i AND woocommerce_attribute_id = ?s", $data, $account_id, $attribute['id']);
                }
                else
                {
                    $result['new']++;
                    $data['account_id'] = $account_id;
                    db_query("INSERT INTO ?:wk_woocommerce_attribute_map ?e", $data);
                }
            }
            $final_import_notification = __('text_attribute_data_imported', array(
                '[new]' => $result['new'],
                '[exist]' => $result['updated'],
                '[total]' => $result['total']
            ));
            fn_set_notification('N', __('success') , $final_import_notification);
            return true;
        }
    }
    catch(Exception $e)
    {
        fn_set_notification('E', __("error") . $e->getMessage());
    }
    return false;
}

/**
 * Creating Woocommerce Product On CS-Cart Store
 *
 * @param   array   $product_data       Woocommerce Product Data
 * @param   int     $_product_id        Product Id
 * @param   array   $account_data       Merchant Account Data
 * @param   array   $mapped_category    Mapped Categories for store
 * @param   array   $mapped_feature     Mapped Faetures
 * @param   bool    $is_variation       true or false
 * @return  int     $product_id         Product Id    
 *
 * @return int
 */
function fn_create_woocommerce_product_on_store($product_data = array() , $_product_id = 0, $account_data = array() , $mapped_category = array() , $mapped_feature = array() , $is_variation = false)
{

    $category_arr = array();
    if (!empty($product_data['categories']))
    {
        foreach ($product_data['categories'] as $k => $category)
        {
            if (isset($mapped_category[$category['id']]) && $mapped_category[$category['id']]['category_id']) $category_arr[] = $mapped_category[$category['id']]['category_id'];
        }
        if (!empty($category_arr))
        {
            $category_arr = array_unique($category_arr);
        }
    }
    if (empty($category_arr) && $account_data['default_cscart_category_id'])
    {
        $category_arr[] = $account_data['default_cscart_category_id'];
    }

    if (empty($category_arr))
    {
        return $_product_id;
    }

    $data = ['product' => $product_data['name'], 'company_id' => $account_data['company_id'], 'product_type' => ($product_data['type'] == 'variable') ? 'C' : 'P', 'category_ids' => $category_arr, 'product_code' => isset($product_data['sku']) ? $product_data['sku'] : '', 'full_description' => isset($product_data['description']) ? $product_data['description'] : '', 'short_description' => isset($product_data['short_description']) ? $product_data['short_description'] : '', 'price' => fn_format_price_by_currency((int)$product_data['price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) , 'list_price' => fn_format_price_by_currency((int)$product_data['regular_price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) , 'amount' => (isset($product_data['stock_quantity']) && $product_data['stock_quantity']) ? $product_data['stock_quantity'] : 0, 'box_length' => isset($product_data['dimensions']['length']) ? $product_data['dimensions']['length'] : 0, 'box_width' => isset($product_data['dimensions']['width']) ? $product_data['dimensions']['width'] : 0, 'box_height' => isset($product_data['dimensions']['height']) ? $product_data['dimensions']['height'] : 0, 'weight' => isset($product_data['weight']) ? (int)$product_data['weight'] * 1000 * Registry::get('settings.General.weight_symbol_grams') : 0, 'tags' => $product_data['tags']

    ];

    if ($product_data['type'] != 'variable')
    {
        if (!empty($product_data['attributes']))
        {
            foreach ($product_data['attributes'] as $attribute)
            {
                $feature_id = isset($mapped_feature[$attribute['id']]) ? $mapped_feature[$attribute['id']]['feature_id'] : 0;
                if ($feature_id)
                {
                    $product_data['add_new_variant'][$feature_id]['variant'] = $attribute['options'];
                }
            }
        }
    }

    $_REQUEST = [];
    // $_REQUEST['product_id'] = 5773;
    $_REQUEST['product_data'] = $data;

    // add main and additional images
    ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 6.0)');
    if (isset($product_data['images']) && !empty($product_data['images']) && !$_product_id)
    {
        $_REQUEST['product_add_additional_image_data'] = array();
        foreach ($product_data['images'] as $key => $image_data)
        {
            if ($key == 0)
            {
                $_REQUEST['type_product_main_image_detailed'] = array(
                    'url'
                );
                $_REQUEST['file_product_main_image_detailed'] = array(
                    $image_data['src']
                );
                $_REQUEST['product_main_image_data'] = array(
                    array(
                        'detailed_alt' => '',
                        'type' => 'M',
                        'object_id' => 0,
                        'position' => 0,
                        'is_new' => 'Y',
                    ) ,
                );
            }
            else
            {
                $_REQUEST['product_add_additional_image_data'][] = array(
                    'type' => 'A',
                    'object_id' => 0,
                    'position' => $key,
                    'is_new' => 'Y',
                    'detailed_alt' => '',
                );
                $_REQUEST['type_product_add_additional_image_detailed'][] = 'url';
                $_REQUEST['file_product_add_additional_image_detailed'][] = $image_data['src'];
            }

        }
    }

    //update product
    $product_id = fn_update_product($_REQUEST['product_data'], $_product_id, DESCR_SL);
    if ($product_id)
    {
        $map_data = array(
            'product_id' => $product_id,
            'parent_product_id' => $product_id,
            'woocommerce_product_id' => $product_data['id'],
            'account_id' => $account_data['shop_id'],
            'price' => fn_format_price_by_currency((int)$product_data['price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) ,
            'list_price' => fn_format_price_by_currency((int)$product_data['regular_price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) ,
            'amount' => (isset($product_data['stock_quantity']) && $product_data['stock_quantity']) ? $product_data['stock_quantity'] : 0,
            'status' => $product_data['status']
        );
        $map_id = db_get_field("SELECT id FROM ?:wk_woocommerce_product_map WHERE product_id = ?i AND woocommerce_product_id = ?i AND account_id = ?i", $product_id, $product_data['id'], $account_data['shop_id']);
        if ($map_id) db_query("UPDATE ?:wk_woocommerce_product_map SET ?u WHERE id = ?i", $map_data, $map_id);
        else db_query("INSERT INTO ?:wk_woocommerce_product_map ?e", $map_data);

        if (!$_product_id && $product_data['type'] == 'variable')
        {
            fn_update_product_variations_from_woocommerce_store($product_data, $product_id, $account_data);
        }

        // elseif($_product_id && $product_data['type'] == 'variable'){
        //     fn_wk_update_imported_variation_product_data($product_data, $product_id, $account_data);
        // }
        
    }
    // fn_save_woooc_product_options_data($product_id, $product_data);
    // exit;
    return $product_id;
}

/**
 * Creating Features For Variation Product & Variation Product On CS-Cart Store 
 *
 * @param   array   $woo_product_data       Woocommerce Product Data
 * @param   int     $product_id             Product Id
 * @param   array   $account_data           Merchant Account Data
 *
 * @return bool
 */

function fn_update_product_variations_from_woocommerce_store($woo_product_data = array() , $product_id, $account_data)
{
    //Create Feature If not exists
    $option_feature_array = array();
    $insert_feature_map = true;
    $feature_map_data = db_get_hash_array("SELECT * FROM ?:wk_woocommerce_attribute_map WHERE product_id = ?i AND woocommerce_product_id = ?i AND account_id = ?i", 'woocommerce_attribute_id', $product_id, $woo_product_data['id'], $account_data['shop_id']);

    foreach ($woo_product_data['attributes'] as $k => $option)
    {
        $feature_id = 0;
        if (!empty($feature_map_data) && isset($feature_map_data[$option['id']]))
        {
            $feature_id = $feature_map_data[$option['id']]['feature_id'];
            $insert_feature_map = false;
        }
        else
        {
            $feature_id = db_get_field("SELECT ?:product_features.feature_id FROM ?:product_features LEFT JOIN ?:product_features_descriptions ON ?:product_features.feature_id = ?:product_features_descriptions.feature_id WHERE ?:product_features_descriptions.description = ?s AND ?:product_features.purpose = ?s AND ?:product_features.filter_style = ?s AND ?:product_features.feature_style = ?s AND ?:product_features.feature_type = ?s AND ?:product_features_descriptions.lang_code = ?s", $option['name'], 'group_catalog_item', 'checkbox', 'dropdown', 'S', DESCR_SL);
        }

        if ($feature_id)
        {
            $feature_data = fn_get_product_feature_data($feature_id, true, false, DESCR_SL);
            foreach ($option['options'] as $variant_value)
            {
                $add = true;
                if (isset($feature_data['variants']) && !empty($feature_data['variants']))
                {
                    $_feature_data['variants'] = $feature_data['variants'];
                    foreach ($_feature_data['variants'] as $feature_variant)
                    {
                        if (trim($feature_variant['variant']) == trim($variant_value))
                        {
                            $add = false;
                            break;
                        }
                    }
                }
                if ($add)
                {
                    $feature_data['variants'][] = array(
                        'variant' => $variant_value,
                    );
                }
            }

        }
        else
        {
            $new = true;
            $feature_data = array(
                'description' => $option['name'],
                'purpose' => 'group_catalog_item',
                'feature_style' => 'dropdown',
                'feature_type' => 'S',
                'filter_style' => 'checkbox',
                'status' => 'A',
            );
            foreach ($option['options'] as $feature_variants)
            {
                $feature_data['variants'][] = array(
                    'variant' => $feature_variants,
                );
            }
        }

        $feature_id = fn_update_product_feature($feature_data, $feature_id, DESCR_SL);
        if (fn_allowed_for('ULTIMATE') && $new) {
            $data = array(
                'share_company_id' => $account_data['company_id'],
                'share_object_type' => 'product_features',
                'share_object_id' => $feature_id,
            );
            db_query('INSERT INTO ?:ult_objects_sharing ?e', $data);
        }

        if ($insert_feature_map)
        {
            $feature_map_data = array(
                'account_id' => $account_data['shop_id'],
                'product_id' => $product_id,
                'woocommerce_product_id' => $woo_product_data['id'],
                'woocommerce_attribute_id' => $option['id'],
                'woocommerce_attribute' => $option['name'],
                'feature_id' => $feature_id,
            );
            db_query("INSERT INTO ?:wk_woocommerce_attribute_map ?e", $feature_map_data);
        }

        $option_feature_array['option' . ($k + 1) ] = $feature_id;
    }

    if (isset($woo_product_data['attributes']) && !empty($woo_product_data['attributes']) && !empty($option_feature_array))
    {
        $parent_product_feature_data = array();
        if (Registry::get('addons.product_variations') && Registry::get('addons.product_variations.status') == 'A')
        {

            $params = array(
                'page' => 1,
                'per_page' => 100
            );

            $variation_product_data = array();
            do
            {
                $vdata = wk_woocommerce_api_call($account_data, 'GET', 'products/' . $woo_product_data['id'] . '/variations', $params);
                $variation_product_data = array_merge($variation_product_data, $vdata);
                $params['page'] += 1;
            }
            while (count($vdata) > 0);

            $parent_product_feature_data = array();
            $p_id = db_get_field("SELECT product_id FROM ?:wk_woocommerce_product_map WHERE account_id = ?i AND woocommerce_product_id = ?i AND woocommerce_variation_id = ?i", $account_data['shop_id'], $woo_product_data['id'], $variation_product_data[0]['id']);

            if (!$p_id)
            {
                foreach ($variation_product_data[0]['attributes'] as $key => $value)
                {

                    $feature_id = db_get_field("SELECT feature_id FROM ?:wk_woocommerce_attribute_map WHERE woocommerce_attribute_id = ?i AND woocommerce_attribute = ?s AND account_id = ?i AND product_id = ?i", $value['id'], $value['name'], $account_data['shop_id'], $product_id);

                    $variant_id = db_get_field("SELECT ?:product_feature_variants.variant_id FROM ?:product_feature_variants LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variants.variant_id = ?:product_feature_variant_descriptions.variant_id WHERE ?:product_feature_variant_descriptions.variant = ?s AND ?:product_feature_variants.feature_id = ?i AND ?:product_feature_variant_descriptions.lang_code = ?s", $value['option'], $feature_id, DESCR_SL);

                    if ($variant_id)
                    {
                        $parent_product_feature_data[$feature_id] = $variant_id;
                    }
                }

                fn_update_product_features_value($product_id, $parent_product_feature_data, array() , DESCR_SL);

                $v_id = array_values($parent_product_feature_data);
                $comination_key = fn_wk_generate_woocommerce_combination_id($v_id);

                $product_map_data = array(
                    'woocommerce_variation_id' => $variation_product_data[0]['id'],
                    'combination_key' => $comination_key,
                );

                db_query("UPDATE ?:wk_woocommerce_product_map SET ?u WHERE product_id = ?i", $product_map_data, $product_id);

                // Update the inventory and price for first variant
                $p_data = array(
                    'price' => fn_format_price_by_currency((int)$variation_product_data[0]['price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) ,
                    'list_price' => fn_format_price_by_currency((int)$variation_product_data[0]['regular_price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) ,
                    'amount' => $variation_product_data[0]['stock_quantity']
                );
                fn_update_product($p_data, $product_id, DESCR_SL);
            }
        }

    }

    if ($product_id)
    {

        $selected_variants_all = array();

        $woocommerce_variation_ids = db_get_hash_array("SELECT * FROM ?:wk_woocommerce_product_map WHERE product_id = ?i", 'woocommerce_variation_id', $product_id);

        foreach ($variation_product_data as $k => $variant_product_data)
        {
            $selected_variants = array();
            if ($k > 0)
            {
                foreach ($variant_product_data['attributes'] as $key => $value)
                {
                    if (!array_key_exists($variant_product_data['id'], $woocommerce_variation_ids))
                    {

                        $feature_id = db_get_field("SELECT feature_id FROM ?:wk_woocommerce_attribute_map WHERE woocommerce_attribute_id = ?i AND woocommerce_attribute = ?s AND account_id = ?i AND product_id = ?i", $value['id'], $value['name'], $account_data['shop_id'], $product_id);

                        $variant_id = db_get_field("SELECT ?:product_feature_variants.variant_id FROM ?:product_feature_variants LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variants.variant_id = ?:product_feature_variant_descriptions.variant_id WHERE ?:product_feature_variant_descriptions.variant = ?s AND ?:product_feature_variants.feature_id = ?i AND ?:product_feature_variant_descriptions.lang_code = ?s", $value['option'], $feature_id, DESCR_SL);
                        if ($variant_id)
                        {
                            $selected_variants[$feature_id] = $variant_id;
                        }
                    }
                }
                $variant_ids = array_values($selected_variants);
                $comination_keys = fn_wk_generate_woocommerce_combination_id($variant_ids);
                $selected_variants_all[$comination_keys] = $variant_product_data;
            }
        }
        // creating the variation form the combination //
        if (!empty($selected_variants_all))
        {
            $group_repository = ServiceProvider::getGroupRepository();
            $service = ServiceProvider::getService();
            $service->selected_variants_all = $selected_variants_all;
            $service->woocommerce_currency_code = CART_PRIMARY_CURRENCY;

            $group_id = $group_repository->findGroupIdByProductId($product_id);

            if ($group_id)
            {
                $result = $service->generateProductsAndAttachToGroup($group_id, $product_id, array_keys($selected_variants_all));
            }
            else
            {
                $result = $service->generateProductsAndCreateGroup($product_id, array_keys($selected_variants_all));
            }
        }
        $data = $result->getData();
        if (!empty($data))
        {
            $variation_product_data = $data['group']->getProducts()
                ->getProducts();
            if (!empty($variation_product_data))
            {
                foreach ($variation_product_data as $k => $vproduct_data)
                {
                    $comination_key = $vproduct_data->getCombinationId();
                    if (isset($selected_variants_all[$comination_key]))
                    {
                        // fn_print_r($selected_variants_all);
                        $product_map_data = array(
                            'account_id' => $account_data['shop_id'],
                            'parent_product_id' => $product_id,
                            'product_id' => $k,
                            'woocommerce_variation_id' => $selected_variants_all[$comination_key]['id'],
                            'combination_key' => $comination_key,
                        );

                        db_query("INSERT INTO ?:wk_woocommerce_product_map ?e", $product_map_data);

                        //updating product image and amount of variation products//
                        $p_data = array(
                            'product_id' => $k,
                            'price' => fn_format_price_by_currency((int)$selected_variants_all[$comination_key]['price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) ,
                            'list_price' => fn_format_price_by_currency((int)$selected_variants_all[$comination_key]['regular_price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) ,
                            'amount' => $selected_variants_all[$comination_key]['stock_quantity']
                        );
                        fn_update_product($p_data, $k, DESCR_SL);
                    }
                }
            }
        }
    }

    return true;
}

function fn_woocommerce_import_generate($product_id, $combinations, array $options_ids)
{
    /** @var ProductManager $product_manager */
    $product_manager = Tygh::$app['addons.product_variations.product.manager'];

    if (!empty($combinations) && !empty($options_ids))
    {
        $languages = Languages::getAll();
        $product_row = db_get_row('SELECT * FROM ?:products WHERE product_id = ?i', $product_id);
        $default_product_variation = $product_manager->getDefaultVariationOptions($product_id);

        foreach ($combinations as $variation_code => $combination)
        {
            $combination['is_default_variation'] = ($default_product_variation) ? 'N' : 'Y';
            fn_woocommerce_import_save_variation($product_row, $combination, $languages);

            $default_product_variation = true;
        }

        $product_manager->changeProductTypeToConfigurable($product_id, array_values($options_ids));
    }
}

/**
 * Saves product variation by product combination.
 *
 * @param array $parent_product_data Parent product data
 * @param array $combination         Product combination data
 * @param array $languages           List of languages
 *
 * @return int
 */
function fn_woocommerce_import_save_variation($parent_product_data, array $combination, $languages)
{
    $data = array_merge($parent_product_data, array(
        'product_id' => null,
        'tracking' => ProductTracking::TRACK_WITHOUT_OPTIONS,
        'product_type' => ProductManager::PRODUCT_TYPE_VARIATION,
        'parent_product_id' => $parent_product_data['product_id'],
        'variation_code' => $combination['variation'],
        'variation_options' => json_encode($combination['selected_options']) ,
        'timestamp' => TIME,
        'updated_timestamp' => TIME,
        'list_price' => isset($combination['list_price']) && !empty($combination['list_price']) ? $combination['list_price'] : 0.00,
        'price' => isset($combination['price']) && !empty($combination['price']) ? $combination['price'] : 0.00,
        'weight' => isset($combination['weight']) && !empty($combination['weight']) ? $combination['weight'] : 0.00,
        'box_length' => isset($combination['box_length']) && !empty($combination['box_length']) ? $combination['box_length'] : 0.00,
        'box_width' => isset($combination['box_width']) && !empty($combination['box_width']) ? $combination['box_width'] : 0.00,
        'box_height' => isset($combination['box_height']) && !empty($combination['box_height']) ? $combination['box_height'] : 0.00,
        'amount' => isset($combination['amount']) ? $combination['amount'] : 1,
        'product_code' => $combination['code'],
        'is_default_variation' => empty($combination['is_default_variation']) ? 'N' : $combination['is_default_variation'],
    ));
    $product_variation_id = db_query('INSERT INTO ?:products ?e', $data);

    if (isset($combination['image']) && !empty($combination['image']))
    {
        $_REQUEST['type_product_main_image_detailed'] = array(
            'url'
        );
        $_REQUEST['file_product_main_image_detailed'] = array(
            $combination['image']['src']
        );
        $_REQUEST['product_main_image_data'] = array(
            array(
                'detailed_alt' => '',
                'type' => 'M',
                'object_id' => 0,
                'position' => 0,
                'is_new' => 'Y',
            ) ,
        );

        fn_attach_image_pairs('product_main', 'product', $product_variation_id, DESCR_SL);
    }
    fn_update_product_prices($product_variation_id, array(
        'price' => isset($combination['price']) && !empty($combination['price']) ? $combination['price'] : 0.00,
        'prices' => array() ,
    ));

    foreach ($languages as $lang_code => $lang)
    {
        $description_data = array(
            'product_id' => $product_variation_id,
            'company_id' => $data['company_id'],
            'lang_code' => $lang_code,
            'product' => $combination['name'],
        );

        db_query('INSERT INTO ?:product_descriptions ?e', $description_data);
    }

    /** @var ProductManager $product_manager */
    $product_manager = Tygh::$app['addons.product_variations.product.manager'];

    $product_manager->cloneProductCategories($parent_product_data['product_id'], $product_variation_id);

    $variation_product_data = array(
        'product_id' => $product_variation_id,
        'parent_product_id' => $parent_product_data['product_id'],
        'woocommerce_product_id' => $combination['woocommerce_product_id'],
        'variation_id' => $combination['woocommerce_variant_id'],
        'account_id' => $combination['account_id'],
        'price' => $data['price'],
        'list_price' => $data['list_price'],
        'amount' => $data['amount']
    );
    db_query("REPLACE INTO ?:wk_woocommerce_product_variation ?e", $variation_product_data);

    return $product_variation_id;
}

/**
 * Saves product variation by product combination.
 *
 * @param   array     $product_data          Product data
 * @param   int       $product_id            Product Id
 * @param   array     $account_data          Merchant Account Data
 *
 */
function fn_wk_update_imported_variation_product_data($product_data, $product_id, $account_data)
{
    if (Registry::get('addons.product_variations') && Registry::get('addons.product_variations.status') == 'A')
    {
        // $variation_product_data = wk_woocommerce_api_call($account_data, 'GET', 'products/'.$product_data['id'].'/variations', $params);
        $params = array(
            'page' => 1,
            'per_page' => 100
        );
        $variation_product_data = array();
        do
        {
            $vdata = wk_woocommerce_api_call($account_data, 'GET', 'products/' . $product_data['id'] . '/variations', $params);
            $variation_product_data = array_merge($variation_product_data, $vdata);
            $params['page'] += 1;
        }
        while (count($vdata) > 0);

        if ($variation_product_data)
        {
            foreach ($variation_product_data as $v_data)
            {
                $cscart_variation_product_id = db_get_field("SELECT product_id FROM ?:wk_woocommerce_product_map WHERE woocommerce_variation_id = ?i AND woocommerce_product_id = ?i AND account_id = ?i", $v_data['id'], $product_data['id'], $account_data['shop_id']);

                if ($cscart_variation_product_id)
                {
                    $p_data = array(
                        'price' => fn_format_price_by_currency((int)$v_data['price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) ,
                        'list_price' => fn_format_price_by_currency((int)$v_data['regular_price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) ,
                        'full_description' => $v_data['description'],
                        'amount' => (isset($v_data['stock_quantity']) && $v_data['stock_quantity']) ? $v_data['stock_quantity'] : 0,
                        'box_length' => isset($v_data['dimensions']['length']) ? $v_data['dimensions']['length'] : 0,
                        'box_width' => isset($v_data['dimensions']['width']) ? $v_data['dimensions']['width'] : 0,
                        'box_height' => isset($v_data['dimensions']['height']) ? $v_data['dimensions']['height'] : 0,
                        'weight' => isset($v_data['weight']) ? $v_data['weight'] * 1000 * Registry::get('settings.General.weight_symbol_grams') : 0,
                        'product_code' => $v_data['sku']
                    );
                    // fn_print_r($cscart_variation_product_id, $p_data);
                    // exit;
                    $product_id = fn_update_product($p_data, $cscart_variation_product_id, DESCR_SL);

                    $v_product_data = array(
                        'price' => $p_data['price'],
                        'list_price' => $p_data['list_price'],
                        'amount' => $p_data['amount']
                    );
                    db_query("UPDATE ?:wk_woocommerce_product_map SET ?u WHERE product_id = ?i", $v_product_data, $cscart_variation_product_id);
                }
            }
        }
    }
}

/**
 * Listing All woocommerce Orders
 *
 * @param   array     $account_data          Account data
 * @param   int       $product_id            Product Id
 * @return  array    
 *
 */
function wk_list_woocommerce_orders($account_data, $request)
{

    $order_data = array();
    $params = array(
        'page' => 1,
        'per_page' => 100
    );
    if (!empty($account_data))
    {
        if (isset($request['min_created']) && !empty($request['min_created'])) $params['after'] = date('c', fn_parse_date($request['min_created'], true));

        if (isset($request['max_created']) && !empty($request['max_created'])) $params['before'] = date('c', fn_parse_date($request['max_created'], true));

        if (isset($request['order_ids']) && !empty($request['order_ids'])) $params['order_ids'] = $product_ids = array_map('intval', explode(',', $request['order_ids']));

        $params['status'] = isset($request['order_status']) ? $request['order_status'] : 'any';

        // $order_data = wk_woocommerce_api_call($account_data, 'GET', 'orders', $params);
        do
        {
            $data = wk_woocommerce_api_call($account_data, 'GET', 'orders', $params);
            $order_data = array_merge($order_data, $data);
            $params['page'] += 1;
        }
        while (count($data) > 0);

    }

    return array(
        $order_data,
        $request
    );
}

function fn_import_orders_from_woocommerce($account_data, $request)
{
    if (isset($request['min_created']) && !empty($request['min_created'])) $params['after'] = date('c', fn_parse_date($request['min_created'], true));
    if (isset($request['max_created']) && !empty($request['max_created'])) $params['before'] = date('c', fn_parse_date($request['max_created'], true));
    if (isset($request['order_ids']) && !empty($request['order_ids']))
    {
        if (is_array($request['order_ids']))
        {
            $params['include'] = $request['order_ids'];
        }
        else
        {
            $params['include'] = $product_ids = array_map('intval', explode(',', $request['order_ids']));
        }

    }
    $params['status'] = isset($request['order_status']) ? $request['order_status'] : 'any';

    $order_ids = array();
    $woo_orders_data = wk_woocommerce_api_call($account_data, 'GET', 'orders', $params);

    foreach ($woo_orders_data as $woo_order_data)
    {

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
            $user_data = ['b_address' => !empty($woo_order_data['billing']['address_1']) ? $woo_order_data['billing']['address_1'] : '', 'b_address_2' => !empty($woo_order_data['billing']['address_2']) ? $woo_order_data['billing']['address_2'] : '', 'b_city' => !empty($woo_order_data['billing']['city']) ? $woo_order_data['billing']['city'] : '', 'b_country' => !empty($woo_order_data['billing']['country']) ? $woo_order_data['billing']['country'] : '', 'b_firstname' => !empty($woo_order_data['billing']['first_name']) ? $woo_order_data['billing']['first_name'] : '', 'b_lastname' => !empty($woo_order_data['billing']['last_name']) ? $woo_order_data['billing']['last_name'] : '', 'b_phone' => !empty($woo_order_data['billing']['phone']) ? $woo_order_data['billing']['phone'] : '', 'b_state' => !empty($woo_order_data['billing']['province']) ? $woo_order_data['billing']['province'] : '', 'b_zipcode' => !empty($woo_order_data['billing']['zip']) ? $woo_order_data['billing']['zip'] : '', 'email' => !empty($woo_order_data['billing']['email']) ? $woo_order_data['billing']['email'] : '', 's_address' => !empty($woo_order_data['shipping']['address_1']) ? $woo_order_data['shipping']['address_1'] : '', 's_address_2' => !empty($woo_order_data['shipping']['address_2']) ? $woo_order_data['shipping']['address_2'] : '', 's_city' => !empty($woo_order_data['shipping']['city']) ? $woo_order_data['shipping']['city'] : '', 's_country' => !empty($woo_order_data['shipping']['country']) ? $woo_order_data['shipping']['country'] : '', 's_firstname' => !empty($woo_order_data['shipping']['first_name']) ? $woo_order_data['shipping']['first_name'] : '', 's_lastname' => !empty($woo_order_data['shipping']['last_name']) ? $woo_order_data['shipping']['last_name'] : '', 's_phone' => !empty($woo_order_data['shipping']['phone']) ? $woo_order_data['shipping']['phone'] : '', 's_state' => !empty($woo_order_data['shipping']['province']) ? $woo_order_data['shipping']['province'] : '', 's_zipcode' => !empty($woo_order_data['shipping']['zip']) ? $woo_order_data['shipping']['zip'] : '', ];
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

                $order_ids[] = $order_id;
            }
        }
    }

    return $order_ids;
}

function fn_woocommerce_fetch_cscart_product($lineItems, $account_data)
{   
    if(!empty($account_data)){
        $cartProducts = [];
        $shopId = $account_data['shop_id'];
        if(!empty($lineItems)){
            foreach ($lineItems as $key => $item) {
                $_product_id = db_get_field('SELECT product_id FROM ?:wk_woocommerce_product_map WHERE woocommerce_product_id = ?i AND account_id = ?i', $item['product_id'], $shopId);
                
                if (empty($_product_id)) {
                    $_product_id = fn_import_products_from_woocommerce($account_data, $item['product_id']);
                }

                if (isset($_product_id) && !empty($_product_id)) {
                    if (isset($item['variation_id']) && $item['variation_id']) {
                        $_product_id = db_get_field('SELECT product_id FROM ?:wk_woocommerce_product_variation WHERE woocommerce_product_id = ?i AND variation_id = ?i AND account_id = ?i', $item['product_id'], $item['variation_id'], $shopId);
                    }
                    $cartProducts[$_product_id]['amount'] = $item['quantity'];
                }
            }
        }
    }
    return $cartProducts;
}

function fn_wk_generate_woocommerce_combination_id($variant_ids = array())
{
    sort($variant_ids);
    return implode('_', $variant_ids);
}

function fn_wk_woocommerce_variation_group_create_products_by_combinations_item($this1, $parent_product_id, $combination_id, $combination, &$product_data)
{
    if (!empty($this1->selected_variants_all))
    {
        $selected_variants_all = $this1->selected_variants_all;
        if (isset($selected_variants_all[$combination_id]))
        {

            $product_data['product_code'] = $selected_variants_all[$combination_id]['sku'];
            $product_data['amount'] = $selected_variants_all[$combination_id]['stock_quantity'];
            $product_data['weight'] = $selected_variants_all[$combination_id]['weight'];
            $product_data['price'] = fn_format_price_by_currency($selected_variants_all[$combination_id]['price'], $this1->woocommerce_currency_code, CART_PRIMARY_CURRENCY);
        }
    }
}

/**
 * Updating Woocommerce Products On CS-Cart store On webhook Hit
 *
 * @param   array   $product_data       Woocommerce Product Data
 * @param   int     $_product_id        Product Id
 * @param   array   $account_data       Merchant Account Data
 * @param   array   $mapped_category    Mapped Categories for store
 * @param   array   $mapped_feature     Mapped Faetures
 * @param   bool    $is_variation       true or false
 * @return  int     $product_id         Product Id     
 *
 */
function fn_update_woocommerce_product_on_store($product_data = array() , $_product_id, $account_data = array() , $mapped_category = array() , $mapped_feature = array() , $is_variation = false)
{

    if (!empty($_product_id))
    {
        $category_arr = array();
        if (!empty($product_data['categories']))
        {
            foreach ($product_data['categories'] as $k => $category)
            {
                if (isset($mapped_category[$category['id']]) && $mapped_category[$category['id']]['category_id']) $category_arr[] = $mapped_category[$category['id']]['category_id'];
            }
            if (!empty($category_arr))
            {
                $category_arr = array_unique($category_arr);
            }
        }
        if (empty($category_arr) && $account_data['default_cscart_category_id'])
        {
            $category_arr[] = $account_data['default_cscart_category_id'];
        }

        if (empty($category_arr))
        {
            return $_product_id;
        }
        $data = [
            'company_id' => $account_data['company_id'], 
            'product_type' => ($product_data['type'] == 'variable') ? 'C' : 'P', 
            'category_ids' => $category_arr, 
            'product_code' => isset($product_data['sku']) ? $product_data['sku'] : '', 'full_description' => isset($product_data['description']) ? $product_data['description'] : '', 
            'short_description' => isset($product_data['short_description']) ? $product_data['short_description'] : '', 
            'price' => fn_format_price_by_currency((int)$product_data['price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) , 
            'list_price' => fn_format_price_by_currency((int)$product_data['regular_price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) , 
            'amount' => (isset($product_data['stock_quantity']) && $product_data['stock_quantity']) ? $product_data['stock_quantity'] : 0, 
            'box_length' => isset($product_data['dimensions']['length']) ? $product_data['dimensions']['length'] : 0, 
            'box_width' => isset($product_data['dimensions']['width']) ? $product_data['dimensions']['width'] : 0, 
            'box_height' => isset($product_data['dimensions']['height']) ? $product_data['dimensions']['height'] : 0, 
            'weight' => isset($product_data['weight']) ? (int)$product_data['weight'] * 1000 * Registry::get('settings.General.weight_symbol_grams') : 0, 
            'tags' => $product_data['tags']
        ];

        if ($product_data['type'] != 'variable')
        {
            if (!empty($product_data['attributes']))
            {
                foreach ($product_data['attributes'] as $attribute)
                {
                    $feature_id = isset($mapped_feature[$attribute['id']]) ? $mapped_feature[$attribute['id']]['feature_id'] : 0;
                    if ($feature_id)
                    {
                        $product_data['add_new_variant'][$feature_id]['variant'] = $attribute['options'];
                    }
                }
            }
        }

        $_REQUEST = [];
        $_REQUEST['product_data'] = $data;
        //update product
        $product_id = fn_update_product($_REQUEST['product_data'], $_product_id, DESCR_SL);

        if ($product_id)
        {
            $map_data = array(
                'parent_product_id' => $product_id,
                'account_id' => $account_data['shop_id'],
                'price' => fn_format_price_by_currency((int)$product_data['price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) ,
                'list_price' => fn_format_price_by_currency((int)$product_data['regular_price'], $account_data['default_currency_code'], CART_PRIMARY_CURRENCY) ,
                'amount' => (isset($product_data['stock_quantity']) && $product_data['stock_quantity']) ? $product_data['stock_quantity'] : 0,
            );

            db_query("UPDATE ?:wk_woocommerce_product_map SET ?u WHERE product_id = ?i", $map_data, $product_id);
        }
    }
}