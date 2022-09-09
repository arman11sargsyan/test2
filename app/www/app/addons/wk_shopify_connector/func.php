<?php
use Tygh\Addons\ProductVariations\Product\Manager as ProductManager;
use Tygh\Addons\ShopifyConnector\Api\WkShopify;
use Tygh\Enum\ProductTracking;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Addons\ProductVariations\Form\GenerateVariationsForm;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Request\GenerateProductsAndAttachToGroupRequest;
use Tygh\Addons\ProductVariations\Request\GenerateProductsAndCreateGroupRequest;
use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Type\Type;
use Tygh\Enum\ObjectStatuses;
use Illuminate\Support\Collection;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

function fn_wk_shopify_install()
{
    $addon_name = fn_get_lang_var('wk_shopify_connector');

    if (Registry::get('addons.vendor_privileges.status') == 'A') {
        $usergroup_id = db_get_field('SELECT usergroup_id FROM ?:usergroups WHERE status = ?s AND type = ?s', 'A', 'V');
        db_query("REPLACE INTO ?:usergroup_privileges (usergroup_id, privilege) VALUES ('".$usergroup_id."', 'manage_wk_shopify_connector'), ('".$usergroup_id."', 'view_wk_shopify_connector')");
    }
   

    fn_set_notification(
        'S',
        __('well_done'),
        __(
            'wk_shopify_webkul_user_guide_content',
            array(
                '[support_link]' => 'https://webkul.uvdesk.com/en/customer/create-ticket/',
                '[user_guide]' => 'https://webkul.com/blog/cs-cart-shopify-connector/',
                '[addon_name]' => $addon_name,
            )
        )
    );
}

function fn_create_variation_of_shopify_product($product_id, $shopify_product_data)
{
    $product_manager = Tygh::$app['addons.product_variations.product.manager'];
    $options_result = fn_product_variations_get_available_options($product_id);

    $product_data = fn_get_product_data($product_id, $auth, CART_LANGUAGE, '', false, false, false, false, false, false, false, false);
    if ($options_result->isSuccess()) {
        $product_options = $options_result->getData();
        $combinations = fn_product_variations_get_options_combinations($product_data, $product_options);
    } else {
        $options_result->showNotifications();
    }

    $variations = array();
    $index = fn_product_variations_get_last_product_code_index($product_data['product_id']);
    $variation_codes = array_keys($combinations);
    foreach ($variation_codes as $variation_code) {
        if (isset($combinations[$variation_code]) && empty($combinations[$variation_code]['exists'])) {
            ++$index;
            $combination = $combinations[$variation_code];

            $variations[$variation_code] = fn_product_variations_get_variation_by_selected_options(
                $product_data,
                $product_options,
                $combination['selected_options'],
                $index
            );
        }
    }

    foreach ($variations as $key => $value) {
        // $variants = explode('_', ltrim($key, '_'));
        $shopify_variantname = [];
        foreach ($value['selected_options'] as $optionId => $variantId) {
            // $optionId = array_search($variantId, $value['selected_options']);
            $shopify_variantname[] = $value['options'][$optionId]['variants'][$variantId]['variant_name'];
        }
        list($shopifyVariantId, $price, $inventory) = fn_wk_shopify_search_variantid($shopify_variantname, $shopify_product_data);
        $variations[$key]['shopify_variant_id'] = $shopifyVariantId;
        $variations[$key]['amount'] = $inventory;
        $variations[$key]['price'] = $price;
        // $variations[$key]['shopify_variant_id'] = fn_wk_shopify_search_variantid($shopify_variantname, $shopify_product_data);
    }

    fn_shopify_import_generate($product_id, $variations, array_keys($product_options));
    $product_manager->actualizeConfigurableProductAmount((array) $product_id);
    // return array(CONTROLLER_STATUS_REDIRECT, "products.update?product_id={$product_id}");
}
function fn_wk_shopify_search_variantid($shopify_variantname, $shopify_product_data)
{
    $price = $inventory = $variantId = 0;
    foreach ($shopify_product_data['variants'] as $key => $variant_data) {
        unset($variant_data['title']);

        if (count(array_intersect($variant_data, $shopify_variantname)) == count($shopify_variantname)) {
            $variantId = $variant_data['id'];
            $price = $variant_data['price'];
            $inventory = $variant_data['inventory_quantity'];
            break;
        }
    }

    return [$variantId, $price, $inventory];
}

function fn_shopify_import_generate($product_id, $combinations, array $options_ids)
{
    /** @var ProductManager $product_manager */
    $product_manager = Tygh::$app['addons.product_variations.product.manager'];

    if (!empty($combinations) && !empty($options_ids)) {
        $languages = Languages::getAll();
        $product_row = db_get_row('SELECT * FROM ?:products WHERE product_id = ?i', $product_id);
        $default_product_variation = $product_manager->getDefaultVariationOptions($product_id);

        foreach ($combinations as $variation_code => $combination) {
            $combination['is_default_variation'] = ($default_product_variation) ? 'N' : 'Y';
            fn_shopify_import_save_variation($product_row, $combination, $languages);

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
function fn_shopify_import_save_variation($parent_product_data, array $combination, $languages)
{
    if (!$combination['shopify_variant_id']) {
        return 0;
    }

    $data = array_merge($parent_product_data, array(
        'product_id' => null,
        'tracking' => ProductTracking::TRACK_WITHOUT_OPTIONS,
        'product_type' => ProductManager::PRODUCT_TYPE_VARIATION,
        'parent_product_id' => $parent_product_data['product_id'],
        'variation_code' => $combination['variation'],
        'variation_options' => json_encode($combination['selected_options']),
        'timestamp' => TIME,
        'updated_timestamp' => TIME,
        'list_price' => isset($combination['list_price']) && !empty($combination['list_price']) ? $combination['list_price'] : 0.00,
        'weight' => isset($combination['weight']) && !empty($combination['weight']) ? $combination['weight'] : 0.00,
        'amount' => isset($combination['amount']) ? $combination['amount'] : 1,
        'product_code' => $combination['code'],
        'is_default_variation' => empty($combination['is_default_variation']) ? 'N' : $combination['is_default_variation'],
        'shopify_product_id' => $combination['shopify_variant_id'],
        'shopify_account_id' => $parent_product_data['shopify_account_id'],
    ));
    $product_variation_id = db_query('INSERT INTO ?:products ?e', $data);

    fn_update_product_prices($product_variation_id, array(
        'price' => $combination['price'],
        'prices' => array(),
    ));

    foreach ($languages as $lang_code => $lang) {
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

    return $product_variation_id;
}
function fn_shopify_filter_shipping_zone_data($shipping_data)
{
    $filterShipping = [];
    $country_arr = [];
    foreach ($shipping_data as $key => $data) {
        $cscart_shippindetail = db_get_row('SELECT ?:shippings.shipping_id, ?:shipping_descriptions.shipping  FROM ?:shippings LEFT JOIN ?:shipping_descriptions ON ?:shippings.shipping_id = ?:shipping_descriptions.shipping_id  WHERE ?:shippings.shopify_zone_id = ?i', $data['id']);

        foreach ($data['countries'] as $k => $country_data) {
            $country_arr[$key][] = [
                'name' => $country_data['name'],
                'code' => $country_data['code'],
                'tax_rate' => $country_data['tax'],
                'tax_name' => $country_data['tax_name'],
            ];
        }
        $filterShipping[$data['id']] = [
            'zone_id' => $data['id'],
            'name' => $data['name'],
            'weight_based_shipping_rates' => $data['weight_based_shipping_rates'],
            'price_based_shipping_rates' => $data['price_based_shipping_rates'],
            'carrier_shipping_rate_providers' => $data['carrier_shipping_rate_providers'],
            'country' => $country_arr[$key],
            'cscart_shipingId' => isset($cscart_shippindetail['shipping_id']) && !empty($cscart_shippindetail['shipping_id']) ? $cscart_shippindetail['shipping_id'] : 0,
            'cscart_shipingName' => isset($cscart_shippindetail['shipping']) && !empty($cscart_shippindetail['shipping']) ? $cscart_shippindetail['shipping'] : '',
        ];
    }

    return $filterShipping;
}
function fn_shopify_map_shipping($shipping_detail, $params, $destinations)
{
    $_REQUEST = $weight_arr = $price_arr = $rates = $item_arr = [];
    $companyId = db_get_field('SELECT company_id FROM ?:wk_shopify_store WHERE shop_id = ?i', $params['account_id']);
    $req_Data = [
        'shipping_id' => 0,
        'shipping_data' => [
            'shipping' => $shipping_detail['name'],
            'rate_calculation' => 'M',
            'status' => 'A',
            'company_id' => $companyId,
            'shopify_zone_id' => $shipping_detail['zone_id'],
        ],
    ];
    $price_arr[] = [
        'range_from_value' => 0,
        'range_to_value' => 0,
        'value' => 0,
        'type' => 'F',
    ];
    $weight_arr[] = [
        'range_from_value' => 0,
        'range_to_value' => 0,
        'value' => 0,
        'type' => 'F',
        'per_unit' => 'N',
    ];
    $item_arr[] = [
        'range_from_value' => 0,
        'range_to_value' => 0,
        'value' => 0,
        'type' => 'F',
        'per_unit' => 'N',
    ];
    foreach ($destinations as $key => $value) {
        $rates[$value['destination_id']]['rate_value'] = [
            'C' => $price_arr,
            'W' => $weight_arr,
            'I' => $item_arr,
        ];
    }
    $price_arr = $weight_arr = [];
    foreach ($shipping_detail['weight_based_shipping_rates'] as $key => $value) {
        $weight_arr[] = [
            'range_from_value' => isset($value['weight_low'])?$value['weight_low']:"",
            'range_to_value' => isset($value['weight_high'])?$value['weight_high']:"",
            'value' => $value['price'],
            'type' => 'F',
            'per_unit' => 'N',
        ];
    }
    foreach ($shipping_detail['price_based_shipping_rates'] as $key => $value) {

        $price_arr[] = [
            'range_from_value' => isset($value['min_order_subtotal'])?$value['min_order_subtotal']:"",
            'range_to_value' => isset($value['max_order_subtotal'])?$value['max_order_subtotal']:"",
            'value' => $value['price'],
            'type' => 'F',
        ];
    }
    foreach ($shipping_detail['country'] as $key => $value) {
        $rates[$params['shipping_entry'][$value['code']]]['rate_value'] = [
            'C' => !empty($price_arr) ? $price_arr : [
                [
                    'range_from_value' => 0,
                    'range_to_value' => 0,
                    'value' => 0,
                    'type' => 'F',
                ],
            ],
            'W' => !empty($weight_arr) ? $weight_arr : [
                [
                    'range_from_value' => 0,
                    'range_to_value' => 0,
                    'value' => 0,
                    'type' => 'F',
                    'per_unit' => 'N'],
            ],
            'I' => $item_arr,
        ];
    }
    $req_Data['shipping_id'] = $shipping_detail['cscart_shipingId'];
    $_REQUEST = $req_Data;
    fn_set_company_id($_REQUEST['shipping_data']);
    $_REQUEST['shipping_id'] = fn_update_shipping($_REQUEST['shipping_data'], $_REQUEST['shipping_id']);
    $_REQUEST['shipping_data']['rates'] = $rates;

    fn_update_shipping($_REQUEST['shipping_data'], $_REQUEST['shipping_id']);
}
function fn_wk_shopify_connector_create_order($order)
{
    
}

function fn_wk_shopify_connector_order_placement_routines(&$order_id, &$force_notification, &$order_info, &$_error)
{
    if (!empty($order_id)) {
        $status_info = $order_info['status'];
        $order_ids = Registry::get('addons.wk_shopify_connector.order_status_to_stop_order');
        $order_array = @array_keys($order_ids);
        $order_array[] = 'N';
        $order_sync_data_in_cscart_and_shopify = db_get_field('SELECT * FROM ?:wk_shopify_order_map WHERE order_id = ?i', $order_id);
        if(isset($order_sync_data_in_cscart_and_shopify) && !empty($order_sync_data_in_cscart_and_shopify)){
            $order_sync_data_in_cscart_to_get_shopify_id = db_get_row('SELECT * FROM ?:wk_shopify_order_map WHERE order_id = ?i', $order_id);
        }
        else{
        if (!in_array($status_info, $order_array)) {
            if (Registry::get('runtime.mode') != 'import_order' && $order_info['is_parent_order'] != 'Y') {
                $wkShopify = new WkShopify();
                $line_items = [];
                foreach ($order_info['products'] as $key => $product_data) {
                    $product_id = $product_data['product_id'];
                    $shopify_details = array();
                    $shopify_details = db_get_row('SELECT shopify_product_id , shopify_account_id, shopify_is_parent FROM ?:products WHERE product_id = ?i', $product_id);

                    if (!empty($shopify_details)) {
                        if ($shopify_details['shopify_is_parent'] == 'N') {
                            $shopify_details = db_get_row('SELECT shopify_variation_id, account_id FROM ?:wk_shopify_products_map WHERE product_id = ?i', $product_id);

                            if (isset($shopify_details['shopify_variation_id']) && !empty($shopify_details['shopify_variation_id']) && isset($shopify_details['account_id']) && !empty($shopify_details['account_id'])) {
                                $line_items[$shopify_details['account_id']][] = [
                                    'variant_id' => $shopify_details['shopify_variation_id'],
                                    'quantity' => $product_data['amount'],
                                ];
                            }
                        } elseif ($shopify_details['shopify_is_parent'] == 'Y') {
                            if (isset($shopify_details['shopify_product_id']) && !empty($shopify_details['shopify_product_id']) && isset($shopify_details['shopify_account_id']) && !empty($shopify_details['shopify_account_id'])) {
                                list($wk_product_data, $credentials) = $wkShopify->fetchShopifyProductData($shopify_details['shopify_account_id'], $shopify_details['shopify_product_id']);

                                if (!empty($wk_product_data)) {
                                    $line_items[$shopify_details['shopify_account_id']][] = [
                                        'variant_id' => $wk_product_data['variants'][0]['id'],
                                        'quantity' => $product_data['amount'],
                                    ];
                                }
                            }
                        }
                    }
                }
                $order_info['s_firstname'] = !empty($order_info['s_firstname']) ? $order_info['s_firstname'] : $order_info['firstname'];

                $order_info['s_lastname'] = !empty($order_info['s_lastname']) ? $order_info['s_lastname'] : $order_info['lastname'];

                $order_info['b_firstname'] = !empty($order_info['b_firstname']) ? $order_info['b_firstname'] : $order_info['firstname'];

                $order_info['b_lastname'] = !empty($order_info['b_lastname']) ? $order_info['b_lastname'] : $order_info['lastname'];

                $tax_include_or_not_in_shopify = true;
                if($order_info['tax_subtotal'] != 0){
                    $tax_include_or_not_in_shopify = false;
                }
                if($order_info['taxes']){
                    $shopify_tax_data_to_send = [];
                    $shopify_tax_data_to_send_final = [];
                    $count=0;
                foreach($order_info['taxes'] as $key=>$raj){
                    if($raj['rate_type'] == 'F'){
                        $shopify_tax_data_to_send =
                            [
                              "price"=> $raj['tax_subtotal'],
                              "title"=> $raj['description'],
                            ];
                    }
                    else{
                        $shopify_tax_data_to_send =
                        [
                          "rate"=> $raj['rate_value']/100,
                          "price"=> $raj['tax_subtotal'],
                          "title"=> $raj['description'],
                        ];
                    }
                    $shopify_tax_data_to_send_final[$count] = $shopify_tax_data_to_send;
                    $count++;

                }
                }

                if (count($line_items) > 0) {
                    $cscart_order_id = $order_id;
                    foreach ($line_items as $shopify_accountId => $item_arr) {
                        $order_data = [
                            'order' => [
                                'line_items' => $item_arr,
                                'inventory_behaviour' => 'decrement_obeying_policy',
                                'note' => 'Order from Cs-Cart',
                                'tags' => 'Order from Cs-Cart',
                                "taxable"=> true,
                                "tax_lines"=>  $shopify_tax_data_to_send_final,
                                "taxes_included"=> $tax_include_or_not_in_shopify,
                                "total_tax" => $order_info['tax_subtotal'],
                                'customer' => [
                                    'first_name' => $order_info['firstname'],
                                    'last_name' => $order_info['lastname'],
                                    'email' => $order_info['email'],
                                ],
                                'billing_address' => [
                                    'first_name' => $order_info['b_firstname'],
                                    'last_name' => $order_info['b_lastname'],
                                    'address1' => $order_info['b_address'],
                                    'phone' => $order_info['b_phone'],
                                    'city' => $order_info['b_city'],
                                    'country' => $order_info['b_country'],
                                    'zip' => $order_info['b_zipcode'],
                                ],
                                'shipping_address' => [
                                    'first_name' => $order_info['s_firstname'],
                                    'last_name' => $order_info['s_lastname'],
                                    'address1' => $order_info['s_address'],
                                    'phone' => $order_info['s_phone'],
                                    'city' => $order_info['s_city'],
                                    'country' => $order_info['s_country'],
                                    'zip' => $order_info['s_zipcode'],
                                ],
                                'email' => $order_info['email'],
                                'financial_status' => 'paid',
                            ],
                        ];
                        $shopifyOrderId = $wkShopify->createOrder($order_data, $shopify_accountId);
                        if (!empty($shopifyOrderId)) {
                            $data = array(
                                'account_id' => $shopify_accountId,
                                'order_id' => $cscart_order_id,
                                'shopify_order_id' => $shopifyOrderId,
                            );
                            $id = db_query('INSERT INTO ?:wk_shopify_order_map ?e', $data);
                        }
                    }
                }
            }
            elseif(Registry::get('runtime.mode') != 'import_order' && $order_info['is_parent_order'] == 'Y'){
                $orders123 = db_get_array('SELECT order_id FROM ?:orders WHERE parent_order_id = ?i', $order_id);
                foreach($orders123 as $raj){
                $order_info11 = fn_get_order_info($raj['order_id']);
                $order_id11 = $raj['order_id'];
                $wkShopify = new WkShopify();
                $line_items = [];
                foreach ($order_info11['products'] as $key => $product_data) {
                    $product_id = $product_data['product_id'];
                    $shopify_details = array();
                    $shopify_details = db_get_row('SELECT shopify_product_id , shopify_account_id, shopify_is_parent FROM ?:products WHERE product_id = ?i', $product_id);

                    if (!empty($shopify_details)) {
                        if ($shopify_details['shopify_is_parent'] == 'N') {
                            $shopify_details = db_get_row('SELECT shopify_variation_id, account_id FROM ?:wk_shopify_products_map WHERE product_id = ?i', $product_id);

                            if (isset($shopify_details['shopify_variation_id']) && !empty($shopify_details['shopify_variation_id']) && isset($shopify_details['account_id']) && !empty($shopify_details['account_id'])) {
                                $line_items[$shopify_details['account_id']][] = [
                                    'variant_id' => $shopify_details['shopify_variation_id'],
                                    'quantity' => $product_data['amount'],
                                ];
                            }
                        } elseif ($shopify_details['shopify_is_parent'] == 'Y') {
                            if (isset($shopify_details['shopify_product_id']) && !empty($shopify_details['shopify_product_id']) && isset($shopify_details['shopify_account_id']) && !empty($shopify_details['shopify_account_id'])) {
                                list($wk_product_data, $credentials) = $wkShopify->fetchShopifyProductData($shopify_details['shopify_account_id'], $shopify_details['shopify_product_id']);

                                if (!empty($wk_product_data)) {
                                    $line_items[$shopify_details['shopify_account_id']][] = [
                                        'variant_id' => $wk_product_data['variants'][0]['id'],
                                        'quantity' => $product_data['amount'],
                                    ];
                                }
                            }
                        }
                    }
                }
                $order_info11['s_firstname'] = !empty($order_info11['s_firstname']) ? $order_info11['s_firstname'] : $order_info11['firstname'];

                $order_info11['s_lastname'] = !empty($order_info11['s_lastname']) ? $order_info11['s_lastname'] : $order_info11['lastname'];

                $order_info11['b_firstname'] = !empty($order_info11['b_firstname']) ? $order_info11['b_firstname'] : $order_info11['firstname'];

                $order_info11['b_lastname'] = !empty($order_info11['b_lastname']) ? $order_info11['b_lastname'] : $order_info11['lastname'];

                $tax_include_or_not_in_shopify = true;
                if($order_info11['tax_subtotal'] != 0){
                    $tax_include_or_not_in_shopify = false;
                }
                if($order_info11['taxes']){
                    $shopify_tax_data_to_send = [];
                    $shopify_tax_data_to_send_final = [];
                    $count=0;
                foreach($order_info11['taxes'] as $key=>$raj){
                    if($raj['rate_type'] == 'F'){
                        $shopify_tax_data_to_send =
                            [
                              "price"=> $raj['tax_subtotal'],
                              "title"=> $raj['description'],
                            ];
                    }
                    else{
                        $shopify_tax_data_to_send =
                        [
                          "rate"=> $raj['rate_value']/100,
                          "price"=> $raj['tax_subtotal'],
                          "title"=> $raj['description'],
                        ];
                    }
                    $shopify_tax_data_to_send_final[$count] = $shopify_tax_data_to_send;
                    $count++;

                }
                }
                if (count($line_items) > 0) {
                    $cscart_order_id = $order_id;
                    foreach ($line_items as $shopify_accountId => $item_arr) {
                        $order_data = [
                            'order' => [
                                'line_items' => $item_arr,
                                'inventory_behaviour' => 'decrement_obeying_policy',
                                'note' => 'Order from Cs-Cart',
                                'tags' => 'Order from Cs-Cart',
                                "taxable"=> true,
                                "tax_lines"=>  $shopify_tax_data_to_send_final,
                                "taxes_included"=> $tax_include_or_not_in_shopify,
                                "total_tax" => $order_info11['tax_subtotal'],
                                'customer' => [
                                    'first_name' => $order_info11['firstname'],
                                    'last_name' => $order_info11['lastname'],
                                    'email' => $order_info11['email'],
                                ],
                                'billing_address' => [
                                    'first_name' => $order_info11['b_firstname'],
                                    'last_name' => $order_info11['b_lastname'],
                                    'address1' => $order_info11['b_address'],
                                    'phone' => $order_info11['b_phone'],
                                    'city' => $order_info11['b_city'],
                                    'country' => $order_info11['b_country'],
                                    'zip' => $order_info11['b_zipcode'],
                                ],
                                'shipping_address' => [
                                    'first_name' => $order_info11['s_firstname'],
                                    'last_name' => $order_info11['s_lastname'],
                                    'address1' => $order_info11['s_address'],
                                    'phone' => $order_info11['s_phone'],
                                    'city' => $order_info11['s_city'],
                                    'country' => $order_info11['s_country'],
                                    'zip' => $order_info11['s_zipcode'],
                                ],
                                'email' => $order_info11['email'],
                                'financial_status' => 'paid',
                            ],
                        ];
                        
                        $shopifyOrderId = $wkShopify->createOrder($order_data, $shopify_accountId);
                        if (!empty($shopifyOrderId)) {
                            $data = array(
                                'account_id' => $shopify_accountId,
                                'order_id' => $cscart_order_id,
                                'shopify_order_id' => $shopifyOrderId,
                            );
                            $id = db_query('INSERT INTO ?:wk_shopify_order_map ?e', $data);
                        }
                    }
                }
            }
            
        }
        }


















    }
    }
}

function fn_wk_shopify_create_cscart_order($shopId, $shopifyOrderId)
{
    $wkShopify = new WkShopify();
    $shopifyOrderData = $wkShopify->fetchOrderData($shopId, $shopifyOrderId);
    Tygh::$app['session']['cart'] = isset(Tygh::$app['session']['cart']) ? Tygh::$app['session']['cart'] : array();
    $cart = &Tygh::$app['session']['cart'];

    Tygh::$app['session']['customer_auth'] = isset(Tygh::$app['session']['customer_auth']) ? Tygh::$app['session']['customer_auth'] : array();
    $customer_auth = &Tygh::$app['session']['customer_auth'];

    Tygh::$app['session']['shipping_rates'] = isset(Tygh::$app['session']['shipping_rates']) ? Tygh::$app['session']['shipping_rates'] : array();
    $shipping_rates = &Tygh::$app['session']['shipping_rates'];

    if (empty($customer_auth)) {
        $customer_auth = fn_fill_auth(array(), array(), false, 'C');
    }
    $user_data = [
        'b_address' => $shopifyOrderData['billing_address']['address1'] ?? '',
        'b_address_2' => $shopifyOrderData['billing_address']['address2'] ?? '',
        'b_city' => $shopifyOrderData['billing_address']['city'] ?? '',
        'b_country' => $shopifyOrderData['billing_address']['country'] ?? '',
        'b_firstname' => $shopifyOrderData['billing_address']['first_name'] ?? '',
        'b_lastname' => $shopifyOrderData['billing_address']['last_name'] ?? '',
        'b_phone' => $shopifyOrderData['billing_address']['phone'] ?? '',
        'b_state' => $shopifyOrderData['billing_address']['province'] ?? '',
        'b_zipcode' => $shopifyOrderData['billing_address']['zip'] ?? '',
        'email' => $shopifyOrderData['billing_address']['email'] ?? '',
        's_address' => $shopifyOrderData['shipping_address']['address1'] ?? '',
        's_address_2' => $shopifyOrderData['shipping_address']['address2'] ?? '',
        's_city' => $shopifyOrderData['shipping_address']['city'] ?? '',
        's_country' => $shopifyOrderData['shipping_address']['country'] ?? '',
        's_firstname' => $shopifyOrderData['shipping_address']['first_name'] ?? '',
        's_lastname' => $shopifyOrderData['shipping_address']['last_name'] ?? '',
        's_phone' => $shopifyOrderData['shipping_address']['phone'] ?? '',
        's_state' => $shopifyOrderData['shipping_address']['province'] ?? '',
        's_zipcode' => $shopifyOrderData['shipping_address']['zip'] ?? '',
    ];
    fn_add_user_data_descriptions($user_data);
    $cart['user_data'] = $user_data;
    $cart['ship_to_another'] = 1;
    if (empty($cart['order_id'])
        && (
            Registry::get('settings.Checkout.disable_anonymous_checkout') == 'Y'
            && !empty($user_data['password1'])
        )
    ) {
        $cart['profile_registration_attempt'] = true;
        list($user_id) = fn_update_user(0, $cart['user_data'], $customer_auth, !empty($_REQUEST['ship_to_another']), true);

        if ($user_id == false) {
            $action = '';
        } else {
            $cart['user_id'] = $user_id;
            $u_data = db_get_row('SELECT user_id, tax_exempt, user_type FROM ?:users WHERE user_id = ?i', $cart['user_id']);
            $customer_auth = fn_fill_auth($u_data, array(), false, 'C');
            $cart['user_data'] = array();
        }
    }
    $newCartProd = fn_fetch_cscart_product($shopifyOrderData['line_items'], $shopId);
    if ($newCartProd) {
        fn_add_product_to_cart($newCartProd, $cart, $customer_auth);
        if(!empty($cart['products'])){
            fn_update_cart_by_data($cart, array(), $customer_auth);
        }
        $cart['notes'] = $shopifyOrderData['note'];
        $store_def_setting = db_get_row('SELECT default_payment, default_shipping FROM ?:wk_shopify_store WHERE shop_id = ?i', $shopId);
        $cart['payment_id'] = $store_def_setting['default_payment'];
        $cart['shipping_ids'] = array($store_def_setting['default_shipping']);
        if (!empty($cart['shipping_ids'])) {
            fn_checkout_update_shipping($cart, $cart['shipping_ids']);
        }

        if(!empty($cart['user_data']['b_address'])){
            list($cart_products, $product_groups) = fn_calculate_cart_content($cart, $customer_auth, 'S');
        }
        $_REQUEST['dispatch'] = Registry::get('runtime.controller').'.'.Registry::get('runtime.mode');
        if((PRODUCT_VERSION == "4.10.4.SP1") || (PRODUCT_VERSION == "4.9.3")){
            $cart['shipping_failed']="";
            $cart['company_shipping_failed']="";
        }
        
        if(isset($cart['subtotal'])){
            list($order_id, $process_payment) = fn_place_order($cart, $customer_auth, 'save', Tygh::$app['session']['auth']['user_id']);
            return $order_id;
        
        }else{
            fn_set_notification('E', 'Error', __("sorry_this_order_is_not_created"));
            return false;
        }
    }
}

function fn_fetch_cscart_product($lineItems, $shopId)
{
    $cartProducts = [];
    foreach ($lineItems as $key => $item) {
        $product_id = db_get_field('SELECT product_id FROM ?:products WHERE shopify_product_id = ?i AND shopify_account_id = ?i AND shopify_is_parent = ?s', $item['product_id'], $shopId, 'Y');

        if (isset($product_id) && !empty($product_id)) {
            // $variantProductId = db_get_field('SELECT product_id FROM ?:products WHERE shopify_product_id = ?i AND shopify_account_id = ?i', $item['variant_id'], $shopId);

            $variantProductId = db_get_field('SELECT product_id FROM ?:wk_shopify_products_map WHERE account_id = ?i AND shopify_product_id = ?i AND shopify_variation_id = ?i', $shopId, $item['product_id'], $item['variant_id']);

            if (!$variantProductId) {
                $variantProductId = $product_id;
            }

            $cartProducts[$variantProductId]['amount'] = $item['quantity'];
        } else {
            $wkshopify = new WkShopify();
            $credentials = $wkshopify->getShopDetailById($shopId);
            $collectionId = $wkshopify->getCollectionByVariation($item['product_id'], $credentials);
            list($productid, $shopify_product_data) = $wkshopify->importShopifyProduct($item['product_id'], $shopId, $collectionId);

            $variantProductId = db_get_field('SELECT product_id FROM ?:wk_shopify_products_map WHERE account_id = ?i AND shopify_product_id = ?i AND shopify_variation_id = ?i', $shopId, $item['product_id'], $item['variant_id']);

            if (!$variantProductId) {
                $variantProductId = $productid;
            }

            $cartProducts[$variantProductId]['amount'] = $item['quantity'];
        }
    }

    return $cartProducts;
}


function Fn_wk_shopify_connector_delete_product_post($product_id, $product_deleted)
{
    if ($product_deleted && $product_id) {
        db_query('DELETE FROM ?:wk_shopify_feature_map WHERE product_id = ?i', $product_id);
        db_query('DELETE FROM ?:wk_shopify_products_map WHERE product_id = ?i OR parent_product_id = ?i', $product_id, $product_id);
    }
}

function fn_wk_create_product_features($shopId, $product_id = 0, $currency_code = CART_PRIMARY_CURRENCY, $product_data = array())
{
    $xmodedata = db_get_row("SELECT * FROM ?:wk_shopify_store WHERE shop_id = ?i", $shopId);
    $wk_variations_as_one_product = 'group_catalog_item';
    if(($xmodedata['wk_data_for_variaton_one_or_not']) && $xmodedata['wk_data_for_variaton_one_or_not'] == 'Y'){
        $wk_variations_as_one_product = 'group_variation_catalog_item';
    }
    if ($product_id && !empty($product_data)) {
        $option_feature_array = array();
        $insert_feature_map = true;
        $feature_map_data = db_get_hash_array('SELECT * FROM ?:wk_shopify_feature_map WHERE product_id = ?i AND shopify_product_id = ?i AND account_id = ?i', 'shopify_option_id', $product_id, $product_data['id'], $shopId);

        foreach ($product_data['options'] as $k => $option) {
            $feature_id = 0;
            if (!empty($feature_map_data) && isset($feature_map_data[$option['id']])) {
                $feature_id = $feature_map_data[$option['id']]['csart_feature_id'];
                $insert_feature_map = false;
            } else {
                $feature_id = db_get_field('SELECT ?:product_features.feature_id FROM ?:product_features LEFT JOIN ?:product_features_descriptions ON ?:product_features.feature_id = ?:product_features_descriptions.feature_id WHERE ?:product_features_descriptions.description = ?s AND ?:product_features.purpose = ?s AND ?:product_features.filter_style = ?s AND ?:product_features.feature_style = ?s AND ?:product_features_descriptions.lang_code = ?s', $option['name'], $wk_variations_as_one_product, 'checkbox', 'dropdown', DESCR_SL);
            }

            if ($feature_id) {
                $feature_data = fn_get_product_feature_data($feature_id, true, false, DESCR_SL);

                foreach ($option['values'] as $variant_value) {
                    $add = true;
                    if (isset($feature_data['variants']) && !empty($feature_data['variants'])) {
                        $_feature_data['variants'] = $feature_data['variants'];
                        foreach ($_feature_data['variants'] as $feature_variant) {
                            if (trim($feature_variant['variant']) == trim($variant_value)) {
                                $add = false;
                                break;
                            }
                        }
                    }
                    if ($add) {
                        $feature_data['variants'][] = array(
                            'variant' => $variant_value,
                        );
                    }
                }
            } else {
                $feature_data = array(
                    'description' => $option['name'],
                    'purpose' => $wk_variations_as_one_product,
                    'feature_style' => 'dropdown',
                    'feature_type' => 'S',
                    'filter_style' => 'checkbox',
                    'status' => 'A',
                );
                foreach ($option['values'] as $feature_variants) {
                    $feature_data['variants'][] = array(
                        'variant' => $feature_variants,
                    );
                }
            }

            $feature_id = fn_update_product_feature($feature_data, $feature_id, DESCR_SL);

            if ($insert_feature_map) {
                $feature_map_data = array(
                    'account_id' => $shopId,
                    'product_id' => $product_id,
                    'shopify_product_id' => $product_data['id'],
                    'shopify_option_id' => $option['id'],
                    'csart_feature_id' => $feature_id,
                );
                db_query('INSERT INTO ?:wk_shopify_feature_map ?e', $feature_map_data);
            }

            $option_feature_array['option'.($k + 1)] = $feature_id;
        }

        if (isset($product_data['variants']) && !empty($product_data['variants']) && !empty($option_feature_array)) {
            $parent_product_feature_data = array();

            $p_id = db_get_field('SELECT product_id FROM ?:wk_shopify_products_map WHERE account_id = ?i AND shopify_product_id = ?i AND shopify_variation_id = ?i', $shopId, $product_data['variants'][0]['product_id'], $product_data['variants'][0]['id']);

            if (!$p_id) {
                if (!empty($product_data['variants'][0]['option1'])) {
                    $variant_id1 = db_get_field('SELECT ?:product_feature_variants.variant_id FROM ?:product_feature_variants LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variants.variant_id = ?:product_feature_variant_descriptions.variant_id WHERE ?:product_feature_variant_descriptions.variant = ?s AND ?:product_feature_variants.feature_id = ?i AND ?:product_feature_variant_descriptions.lang_code = ?s', $product_data['variants'][0]['option1'], $option_feature_array['option1'], DESCR_SL);

                    if ($variant_id1) {
                        $parent_product_feature_data[$option_feature_array['option1']] = $variant_id1;
                    }
                }

                if (!empty($product_data['variants'][0]['option2'])) {
                    $variant_id2 = db_get_field('SELECT ?:product_feature_variants.variant_id FROM ?:product_feature_variants LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variants.variant_id = ?:product_feature_variant_descriptions.variant_id WHERE ?:product_feature_variant_descriptions.variant = ?s AND ?:product_feature_variants.feature_id = ?i', $product_data['variants'][0]['option2'], $option_feature_array['option2']);
                    if ($variant_id2) {
                        $parent_product_feature_data[$option_feature_array['option2']] = $variant_id2;
                    }
                }
                if (!empty($product_data['variants'][0]['option3'])) {
                    $variant_id3 = db_get_field('SELECT ?:product_feature_variants.variant_id FROM ?:product_feature_variants LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variants.variant_id = ?:product_feature_variant_descriptions.variant_id WHERE ?:product_feature_variant_descriptions.variant = ?s AND ?:product_feature_variants.feature_id = ?i', $product_data['variants'][0]['option3'], $option_feature_array['option3']);
                    if ($variant_id3) {
                        $parent_product_feature_data[$option_feature_array['option3']] = $variant_id3;
                    }
                }
                fn_update_product_features_value($product_id, $parent_product_feature_data, array(), DESCR_SL);

                $v_id = array_values($parent_product_feature_data);
                $comination_key = fn_wk_generate_combination_id($v_id);

                $product_map_data = array(
                    'account_id' => $shopId,
                    'parent_product_id' => $product_id,
                    'product_id' => $product_id,
                    'shopify_product_id' => $product_data['variants'][0]['product_id'],
                    'shopify_variation_id' => $product_data['variants'][0]['id'],
                    'combination_key' => $comination_key,
                );
                db_query('INSERT INTO ?:wk_shopify_products_map ?e', $product_map_data);
            }
        }

        fn_wk_create_product_variation($shopId, $product_id, $currency_code, $product_data, $option_feature_array);
    }
}

function fn_wk_create_product_variation($shopId, $product_id = 0, $currency_code = CART_PRIMARY_CURRENCY, $product_data = array(), $option_feature_array = array())
{
    $raj_new_product_data_for_image = $product_data;
    if ($product_id) {
        $selected_variants_all = array();

        $shopify_variation_ids = db_get_hash_array('SELECT * FROM ?:wk_shopify_products_map WHERE parent_product_id = ?i', 'shopify_variation_id', $product_id);

        foreach ($product_data['variants'] as $k => $variant_product_data) {
            $selected_variants = array();
            if ($k > 0) {
                if (!array_key_exists($variant_product_data['id'], $shopify_variation_ids)) {
                    if (!empty($variant_product_data['option1'])) {
                        $variant_id1 = db_get_field('SELECT ?:product_feature_variants.variant_id FROM ?:product_feature_variants LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variants.variant_id = ?:product_feature_variant_descriptions.variant_id WHERE ?:product_feature_variant_descriptions.variant = ?s AND ?:product_feature_variants.feature_id = ?i AND ?:product_feature_variant_descriptions.lang_code = ?s', $variant_product_data['option1'], $option_feature_array['option1'], DESCR_SL);
                        if ($variant_id1) {
                            $selected_variants[$option_feature_array['option1']] = $variant_id1;
                        }
                    }

                    if (!empty($variant_product_data['option2'])) {
                        $variant_id2 = db_get_field('SELECT ?:product_feature_variants.variant_id FROM ?:product_feature_variants LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variants.variant_id = ?:product_feature_variant_descriptions.variant_id WHERE ?:product_feature_variant_descriptions.variant = ?s AND ?:product_feature_variants.feature_id = ?i', $variant_product_data['option2'], $option_feature_array['option2']);
                        if ($variant_id2) {
                            $selected_variants[$option_feature_array['option2']] = $variant_id2;
                        }
                    }
                    if (!empty($variant_product_data['option3'])) {
                        $variant_id3 = db_get_field('SELECT ?:product_feature_variants.variant_id FROM ?:product_feature_variants LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variants.variant_id = ?:product_feature_variant_descriptions.variant_id WHERE ?:product_feature_variant_descriptions.variant = ?s AND ?:product_feature_variants.feature_id = ?i', $variant_product_data['option3'], $option_feature_array['option3']);
                        if ($variant_id3) {
                            $selected_variants[$option_feature_array['option3']] = $variant_id3;
                        }
                    }
                
                    $variant_ids = array_values($selected_variants);
                    $comination_keys = fn_wk_generate_combination_id($variant_ids);
                    $selected_variants_all[$comination_keys] = $variant_product_data;
                }
            }
        }
        
        $mystring = strval($comination_keys);
        $pos = strpos($mystring, '_');

        if(false === $pos) {
            $request_data=[];
    
            $request_data['product_id']=$product_id;
            $request_data['feature_ids']=["0"=>$option_feature_array['option1']];
            $request_data['features_variants_ids']=[$option_feature_array['option1']=>[]];
            foreach($selected_variants_all as $gg=>$gg_data){
                $request_data['features_variants_ids'][$option_feature_array['option1']][] = $gg;
            }
    
            $request_data['check_all']='Y';
            $request_data['combinations_data']=[];
            $combination_hash = fn_generate_cart_id($product_id,$request_data['features_variants_ids']);
            $request_data['security_hash']=$combination_hash;
            $request_data['dispatch']='wk_shopify_product.manage';
            foreach($selected_variants_all as $pp=>$pp_data){
                if(isset($pp_data['sku']) && !empty($pp_data['sku'])){
                    $raj = $pp_data['sku'];
                }
                else{
                    $qq='QUES';
                    $qqq='LPT';
                    $man=strval(rand(100000,999999999));
                    $raj=$qq.$man.$qqq;
                }
                $request_data['combinations_data'][$pp]=['active'=>'1','product_code'=>$raj,'product_price'=>$pp_data['price'],'product_amount'=>$pp_data['inventory_quantity']];
    
            }
            $generation_form = GenerateVariationsForm::create($product_id, $request_data);
        $product_data = $generation_form->getProductData();

        if (!$product_data) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $group_repository = ServiceProvider::getGroupRepository();
        $service = ServiceProvider::getService();

        $group_id = $group_repository->findGroupIdByProductId($product_id);

        if ($group_id) {
            $request = new GenerateProductsAndAttachToGroupRequest(
                $group_id,
                $product_id,
                $generation_form->getCombinationsData()
            );
            $request->setFeaturesVariantsMap($generation_form->getFeaturesVariantsMap());
            $result = $service->generateProductsAndAttachToGroup($request);
        } else {
            $request = new GenerateProductsAndCreateGroupRequest(
                $product_id,
                $generation_form->getCombinationsData(),
                $generation_form->getFeatureCollection()
            );
            $request->setFeaturesVariantsMap($generation_form->getFeaturesVariantsMap());
            $result = $service->generateProductsAndCreateGroup($request);
        }

        $data = $result->getData();
        }
        else {
            if (!empty($selected_variants_all)) {
                $group_repository = ServiceProvider::getGroupRepository();
                $service = ServiceProvider::getService();
                $service->selected_variants_all = $selected_variants_all;
                $service->shopify_currency_code = $currency_code;
    
                $group_id = $group_repository->findGroupIdByProductId($product_id);
    
                if ($group_id) {
                    $result = $service->generateProductsAndAttachToGroup($group_id, $product_id, array_keys($selected_variants_all));
                } else {
                    $result = $service->generateProductsAndCreateGroup($product_id, array_keys($selected_variants_all));
                }
            }
            $data = $result->getData();
        }
        $asdfdata = $data['group']->getProducts()->getProducts();
        $match_product_id = [];
        foreach($asdfdata as $key=>$value){
            $getfeaturedata = $value->getFeatureValues();
            foreach($getfeaturedata as $key2=>$value2){
                $match_product_id[$key][$key2] = $value2->getVariantId();    
            } 
        }
        if (!empty($data)) {
            $variation_product_data = $data['group']->getProducts()->getProducts();
            if (!empty($variation_product_data)) {
                $variant_count=1;
                foreach ($variation_product_data as $k => $vproduct_data) {
                    $comination_key = $vproduct_data->getCombinationId();
                    if (isset($selected_variants_all[$comination_key])) {
                        $product_map_data = array(
                            'account_id' => $shopId,
                            'parent_product_id' => $product_id,
                            'product_id' => $k,
                            'shopify_product_id' => $selected_variants_all[$comination_key]['product_id'],
                            'shopify_variation_id' => $selected_variants_all[$comination_key]['id'],
                            'combination_key' => $comination_key,
                        );
                        db_query('INSERT INTO ?:wk_shopify_products_map ?e', $product_map_data);

                        $p_data = array(
                            'product_id' => $k,
                            'shopify_is_parent' => 'N',
                            'amount' => $selected_variants_all[$comination_key]['inventory_quantity'],
                            'list_price' => $selected_variants_all[$comination_key]['compare_at_price'],
                        );
                        $_REQUEST = array();
                        fn_update_product($p_data, $k, DESCR_SL);
                        if (isset($raj_new_product_data_for_image['variants']) && !empty($raj_new_product_data_for_image['variants'])) {
                                foreach($raj_new_product_data_for_image['variants'] as $key=>$value){
                                    $cs_cart_feature_map_data = $match_product_id;
                                    $shopify_feature_map_data = $value;

                                            foreach($cs_cart_feature_map_data as $rrkey=>$rrvalue){
                                                
                                                $map_data_for_shopify = [];
                                                foreach($rrvalue as $rrrkey=>$rrrvalue){
                                                    $feature_data1 = fn_get_product_feature_data($rrrkey, true, false, DESCR_SL);
                                                    $map_data_for_shopify[]= $feature_data1['variants'][$rrrvalue]['variant'];
                                                }

                                                $gu_product_id = $rrkey;
                                                $result123r123 = array_intersect($map_data_for_shopify, $shopify_feature_map_data);
                                                if(count($result123r123) == count($map_data_for_shopify)){
                                                    db_query('UPDATE ?:images_links  SET type = "A" WHERE object_id = ?i',$gu_product_id);
                                                    db_query('UPDATE ?:images_links  SET type = "M" WHERE position = ?i AND object_id = ?i',$shopify_feature_map_data['image_id'],$gu_product_id);   
                                                }
                                            }
                                }
                                
                                $variant_count++;

                        }
                    }
                }
                
            }
        }
    }
}

function fn_wk_generate_combination_id($variant_ids = array())
{
    sort($variant_ids);

    return implode('_', $variant_ids);
}

function fn_wk_shopify_data_details(&$accountId)
{
    $status = db_get_field('SELECT `status` FROM ?:wk_shopify_store WHERE shop_id = ?i', $accountId);
    return $status;
}

function fn_wk_shopify_connector_delete_order(&$order_id)
{
    $delete_data = db_query('DELETE FROM ?:wk_shopify_order_map WHERE order_id = ?i', $order_id);
}

function fn_wk_shopify_connector_variation_group_create_products_by_combinations_item($this1, $parent_product_id, $combination_id, $combination, &$product_data)
{
    $controller = Registry::get('runtime.controller');
    if($controller == 'wk_shopify_product'){
        if(isset($this1->selected_variants_all)){
        if ($this1->selected_variants_all) {
            $selected_variants_all = $this1->selected_variants_all;
            if (isset($selected_variants_all[$combination_id])) {
                $product_data['product_code'] = $selected_variants_all[$combination_id]['sku'];
                $product_data['amount'] = $selected_variants_all[$combination_id]['inventory_quantity'];
                $product_data['weight'] = $selected_variants_all[$combination_id]['weight'];
                $product_data['price'] = fn_format_price_by_currency($selected_variants_all[$combination_id]['price'], $this1->shopify_currency_code, CART_PRIMARY_CURRENCY);
                $product_data['list_price'] = fn_format_price_by_currency($selected_variants_all[$combination_id]['compare_at_price'], $this1->shopify_currency_code, CART_PRIMARY_CURRENCY);

                $product_data['shopify_is_parent'] = 'N';
            }
        }
    }
    }
}
function fn_wk_shopify_export_order($order_id = 0)
{
    $order_sync_data_in_cscart_and_shopify = db_get_field('SELECT * FROM ?:wk_shopify_order_map WHERE order_id = ?i', $order_id);
    $match_order_flag = true;
    if(isset($order_sync_data_in_cscart_and_shopify) && !empty($order_sync_data_in_cscart_and_shopify)){
        return 'done';
    }
    else{
    if (!empty($order_id)) {
        $order_info = fn_get_order_info($order_id);
        $status_info = $order_info['status'];
        $order_ids = Registry::get('addons.wk_shopify_connector.order_status_to_stop_order');
        $order_array = @array_keys($order_ids);
        $order_array[] = 'N';
        if (!in_array($status_info, $order_array)) {
            if (Registry::get('runtime.mode') != 'import_order' && $order_info['is_parent_order'] != 'Y') {
                $wkShopify = new WkShopify();
                $line_items = [];
                foreach ($order_info['products'] as $key => $product_data) {
                    $product_id = $product_data['product_id'];
                    $shopify_details = [];
                    $shopify_details = db_get_row('SELECT shopify_product_id , shopify_account_id, shopify_is_parent FROM ?:products WHERE product_id = ?i', $product_id);

                    if (!empty($shopify_details)) {
                        if ($shopify_details['shopify_is_parent'] == 'N') {
                            $shopify_details = db_get_row('SELECT shopify_variation_id, account_id FROM ?:wk_shopify_products_map WHERE product_id = ?i', $product_id);

                            if (isset($shopify_details['shopify_variation_id']) && !empty($shopify_details['shopify_variation_id']) && isset($shopify_details['account_id']) && !empty($shopify_details['account_id'])) {
                                $line_items[$shopify_details['account_id']][] = [
                                    'variant_id' => $shopify_details['shopify_variation_id'],
                                    'quantity' => $product_data['amount'],
                                ];
                            }
                        } elseif ($shopify_details['shopify_is_parent'] == 'Y') {
                            if (isset($shopify_details['shopify_product_id']) && !empty($shopify_details['shopify_product_id']) && isset($shopify_details['shopify_account_id']) && !empty($shopify_details['shopify_account_id'])) {
                                list($wk_product_data, $credentials) = $wkShopify->fetchShopifyProductData($shopify_details['shopify_account_id'], $shopify_details['shopify_product_id']);

                                if (!empty($wk_product_data)) {
                                    $line_items[$shopify_details['shopify_account_id']][] = [
                                        'variant_id' => $wk_product_data['variants'][0]['id'],
                                        'quantity' => $product_data['amount'],
                                    ];
                                }
                            }
                        }
                    }
                }
                $tax_lines = [];
                if (!empty($order_info['taxes'])) {
                    foreach ($order_info['taxes'] as $key => $value) {
                        if ($value['price_includes_tax'] == 'N') {
                            $tax_lines[] = [
                                'price' => $value['tax_subtotal'],
                                'rate' => $value['rate_type'] == 'P' ? ($value['rate_value'] / 100) : '',
                                'title' => $value['description'],
                              ];
                        }
                    }
                }
                $order_info['s_firstname'] = !empty($order_info['s_firstname']) ? $order_info['s_firstname'] : $order_info['firstname'];

                $order_info['s_lastname'] = !empty($order_info['s_lastname']) ? $order_info['s_lastname'] : $order_info['lastname'];

                $order_info['b_firstname'] = !empty($order_info['b_firstname']) ? $order_info['b_firstname'] : $order_info['firstname'];

                $order_info['b_lastname'] = !empty($order_info['b_lastname']) ? $order_info['b_lastname'] : $order_info['lastname'];


                if (count($line_items) > 0) {
                    $cscart_order_id = $order_id;
                    foreach ($line_items as $shopify_accountId => $item_arr) {
                        $order_data = [
                            'order' => [
                                'line_items' => $item_arr,
                                'tax_lines' => $tax_lines,
                                'tax_subtotal' => $order_info['tax_subtotal'],
                                'inventory_behaviour' => 'decrement_obeying_policy',
                                'customer' => [
                                    'first_name' => $order_info['firstname'],
                                    'last_name' => $order_info['lastname'],
                                    'email' => $order_info['email'],
                                ],
                                'billing_address' => [
                                    'first_name' => $order_info['b_firstname'],
                                    'last_name' => $order_info['b_lastname'],
                                    'address1' => $order_info['b_address'],
                                    'phone' => $order_info['b_phone'],
                                    'city' => $order_info['b_city'],
                                    'country' => $order_info['b_country'],
                                    'zip' => $order_info['b_zipcode'],
                                ],
                                'shipping_address' => [
                                    'first_name' => $order_info['s_firstname'],
                                    'last_name' => $order_info['s_lastname'],
                                    'address1' => $order_info['s_address'],
                                    'phone' => $order_info['s_phone'],
                                    'city' => $order_info['s_city'],
                                    'country' => $order_info['s_country'],
                                    'zip' => $order_info['s_zipcode'],
                                ],
                                'email' => $order_info['email'],
                                'financial_status' => 'paid',
                            ],
                        ];
                        $shopifyOrderId = $wkShopify->createOrder($order_data, $shopify_accountId);
                        if (!empty($shopifyOrderId)) {
                            $data = [
                                'account_id' => $shopify_accountId,
                                'order_id' => $cscart_order_id,
                                'shopify_order_id' => $shopifyOrderId,
                            ];
                            $id = db_query('INSERT INTO ?:wk_shopify_order_map ?e', $data);

                            return 'true';
                        }
                    }
                }
            }
        }
    }
}
}