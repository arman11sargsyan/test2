<?php

namespace Tygh\Addons\ShopifyConnector\Api;

use Tygh\Registry;

class WkShopify
{
    public function authenticate($credentials)
    {
        try {
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['shopify_shared_secret_key'], $credentials['shopify_api_key'], $credentials['shopify_shared_secret_key']);
            $shop = $wkshopify->call('GET', '/admin/shop.json');
            $data = array(
                'shopify_shop_id' => $shop['id'],
                'domain_name' => $credentials['domain_name'],
                'shop_name' => $credentials['shop_name'],
                'api_key' => $credentials['shopify_api_key'],
                'api_secret' => $credentials['shopify_shared_secret_key'],
                'company_id' => $credentials['company_id'],
                'default_cscart_category_id' => $credentials['default_cscart_category_id'],
                'default_payment' => $credentials['default_payment'],
                'default_shipping' => $credentials['default_shipping'],
                'order_close_status' => $credentials['order_close_status'],
                'order_cancel_status' => $credentials['order_cancel_status'],
                'wk_data_for_variaton_one_or_not' => $credentials['shopify_variaton_one_or_not'],
                'wk_shopify_draft_product_import' => $credentials['wk_data_for_shopify_draft_product'],
                'shopify_currency_code' => $credentials['shopify_currency_code'],
            );
            $shopify_data = db_get_row('SELECT * FROM ?:wk_shopify_store WHERE company_id = ?i AND api_key = ?s AND api_secret = ?s', $credentials['company_id'],$credentials['shopify_api_key'],$credentials['shopify_shared_secret_key']);
            if(!empty($shopify_data)){
                $errMsg = __('wkshopifyaccounterror');
                fn_set_notification('E', 'Error', $errMsg);
            }else{
            if ($_REQUEST['id']) {
                db_query('UPDATE ?:wk_shopify_store SET ?u WHERE shop_id = ?i', $_REQUEST['id']);
            } else {
                $data['timestamp'] = TIME;
                $data['status'] = 'A';
                db_query('INSERT INTO ?:wk_shopify_store ?e', $data);
            }
            }
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function getShopDetailById($shopId)
    {
        try {
            $condition = '';
            if (Registry::get('runtime.company_id')) {
                $condition = db_quote(' AND company_id = ?i', Registry::get('runtime.company_id'));
            }
            $shop_data = db_get_row("SELECT * FROM ?:wk_shopify_store WHERE shop_id = ?i $condition", $shopId);
            if (!empty($shop_data['webhook_id'])) {
                $shop_data['webhook_details'] = [];
                $wkshopify = new ShopifyClient($shop_data['domain_name'], $shop_data['api_secret'], $shop_data['api_key'], $shop_data['api_secret']);
                $webhookdata = $wkshopify->call('GET', '/admin/webhooks.json');
                foreach ($webhookdata as $key => $webhook) {
                    $shop_data['webhook_details'][] = [
                        'webhook_url' => $webhook['address'],
                        'topic' => $webhook['topic'],
                    ];
                }
            }

            return $shop_data;
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function getCredentialById($shopId)
    {
        try {
            $credentials = array();
            $credentials = db_get_row('SELECT domain_name , api_key, api_secret FROM ?:wk_shopify_store WHERE shop_id = ?i', $shopId);

            return $credentials;
        } catch (Exception $e) {
            fn_set_notification('E', 'Error', __('request_not_valid'));
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }

        return array();
    }

    public function getShopList($params)
    {
        $condition = '';
        $limit = '';
        $order = '';
        $sortings = array(
            'shopify_shop_id' => '?:wk_shopify_store.shopify_shop_id',
            'domain_name' => '?:wk_shopify_store.domain_name',
            'shop_name' => '?:wk_shopify_store.shop_name',
            'api_key' => '?:wk_shopify_store.api_key',
            'api_secret' => '?:wk_shopify_store.api_secret',
            'company_id' => '?:wk_shopify_store.company_id',
            'status' => '?:wk_shopify_store.status',
            'timestamp' => '?:wk_shopify_store.timestamp',
            'webhook_id' => '?:wk_shopify_store.webhook_id',
            'shopify_currency_code' => '?:wk_shopify_store.shopify_currency_code',
            'default_cscart_category_id' => '?:wk_shopify_store.default_cscart_category_id',
            'default_payment' => '?:wk_shopify_store.default_payment',
            'default_shipping' => '?:wk_shopify_store.default_shipping',
            'order_close_status' => '?:wk_shopify_store.order_close_status',
            'order_cancel_status' => '?:wk_shopify_store.order_cancel_status',
            'shop_id' => '?:wk_shopify_store.shop_id',
        );
        if (isset($_REQUEST['sort_order'])) {
            if ($_REQUEST['sort_order'] == 'asc') {
                $params['sort_order_rev'] = 'desc';
                $params['sort_order'] = $_REQUEST['sort_order'];
            } else {
                $params['sort_order_rev'] = 'asc';
                $params['sort_order'] = $_REQUEST['sort_order'];
            }
        } else {
            $params['sort_order_rev'] = 'asc';
            $params['sort_order'] = 'desc';
        }
        if (isset($params['is_search']) && $params['is_search'] == 'Y') {
            if (isset($params['merchant_id']) && !empty($params['merchant_id'])) {
                $condition .= db_quote(' AND merchant_id LIKE ?l', "{$params['merchant_id']}");
            }
            if (isset($params['status']) && !empty($params['status'])) {
                $condition .= db_quote(' AND status LIKE ?l', "{$params['status']}");
            }
            if (isset($params['vendor']) && !empty($params['vendor']) && $params['vendor']!="all") {
                $condition .= db_quote(' AND company_id = ?i', "{$params['vendor']}");
            }
        }
        if (Registry::get('runtime.company_id')) {
            $condition .= db_quote(' AND company_id = ?i', Registry::get('runtime.company_id'));
        }

        if (empty($params['items_per_page'])) {
            $params['items_per_page'] = Registry::get('settings.Appearance.admin_elements_per_page');
        }
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        $sorting = db_sort($params, $sortings, 'shop_id', 'asc');
        $params['total_items'] = db_get_field("SELECT count(*) FROM ?:wk_shopify_store WHERE 1 $condition $order $limit");
        if (!empty($params['limit'])) {
            $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
        } elseif (!empty($params['items_per_page'])) {
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }
        $merchants = db_get_array("SELECT * FROM ?:wk_shopify_store WHERE 1 $condition $order $sorting $limit");

        return [$merchants, $params];
    }

    public function getCollections($shopId)
    {
        try {
            $suffix = '';
            $collections = array();
            $credentials = self::getCredentialById($shopId);
            if (empty($credentials)) {
                return $collections;
            }

            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            $params['total_items'] = $wkshopify->call('GET', '/admin/smart_collections/count.json/');
            $suffix .= 'limit='.$params['total_items'];

            $test = $wkshopify->call('GET', '/admin/smart_collections.json/?'.$suffix);
            $collections = $smart_collection = $wkshopify->call('GET', '/admin/smart_collections.json/?'.$suffix);
            $custom_collection = $wkshopify->call('GET', '/admin/custom_collections.json');
            foreach ($custom_collection as $key => $value) {
                array_push($collections, $value);
            }

            return $collections;
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function deleteShop($shopId = 0, $delPro = false)
    {
        if (!$shopId) {
            return 0;
        }
        try {
            db_query(
                'DELETE FROM ?:wk_shopify_category_map WHERE account_id = ?i',
                $shopId
            );
            db_query(
                'DELETE FROM ?:wk_shopify_order_map WHERE account_id = ?i',
                $shopId
            );
            db_query(
                'DELETE FROM ?:wk_shopify_store WHERE shop_id = ?i',
                $shopId
            );
        } catch (Exception $e) {
        }
        if ($delPro) {
            try {
                $proIds = db_get_fields(
                    'SELECT product_id FROM ?:products WHERE shopify_account_id='.$shopId
                );
            } catch (Exception $e) {
                $proIds = array();
            }
            foreach ($proIds as $key => $value) {
                fn_delete_product($value);
            }
        }

        return true;
    }

    public function categoryMap($mapData)
    {
        try {
            $data = array(
                'category_id' => $mapData['cs_cart_category'],
                'shopify_collection_id' => $mapData['shopify_category'],
                'shopify_collection' => $mapData['shopify_category_name'],
                'account_id' => $mapData['account_id'],
            );
            db_query('INSERT INTO ?:wk_shopify_category_map ?e', $data);

            return true;
        } catch (Exception $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return false;
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function getMappedCategory($shopId)
    {
        $mappedCat = [];
        try {
            $mappedCat = db_get_array('SELECT * FROM ?:wk_shopify_category_map WHERE account_id = ?i', $shopId);
            return $mappedCat;
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    function deleteHooks($shopId){
        try{
            $credentials = self::getCredentialById($shopId);
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            $shop_domain = $wkshopify->shop_domain;
            $webhook_id = db_get_field('SELECT `webhook_id` FROM ?:wk_shopify_store WHERE `domain_name` =  ?s',$shop_domain);
            $webhook_details = array('webhook' => array(
                'topic' => 'products/update',
                'address' => fn_url('wk_shopify_product.webhookupdate', 'C'),
                'format' => 'json',
                'fields' => array('id'),
            ));
            $wkshopify->call('DELETE', "/admin/api/2021-01/webhooks/$webhook_id.json");
            db_query('UPDATE ?:wk_shopify_store SET webhook_id = "0" WHERE shop_id = ?i',$shopId);
        }catch(ShopifyApiException $e){
            fn_set_notification('E', 'Error', $e->getMessage());
        }catch(ShopifyCurlException $e){
            fn_set_notification('E','Error',$e->getMessage());
        }
    }
    public function registerHooks($shopId)
    {
       
        try {
            $credentials = self::getCredentialById($shopId);
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            $verifyHook = self::checkHookExist('products/update', $credentials);

            if (!$verifyHook) {
                $webhook_details = array('webhook' => array(
                    'topic' => 'products/update',
                    'address' => fn_url('wk_shopify_product.webhookupdate', 'C'),
                    'format' => 'json',
                    'fields' => array('id'),
                ));
                $response = $wkshopify->call('POST', '/admin/webhooks.json', $webhook_details);
                
                db_query('UPDATE ?:wk_shopify_store SET webhook_id = ?s WHERE shop_id = ?i', $response['id'], $shopId);

                return $response['id'];
            } else {
                $verifyHook = self::updateHookExist($verifyHook, $credentials);

                return $verifyHook;
            }
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function updateHookExist($hookId, $cred)
    {
        $webhookId = 0;
        try {
            $webhook_details = array('webhook' => array(
                'address' => fn_url('wk_shopify_product.webkookupdate'),
            ));
            $wkshopify = new ShopifyClient($cred['domain_name'], $cred['api_secret'], $cred['api_key'], $cred['api_secret']);
            $response = $wkshopify->call('PUT', '/admin/webhooks/'.$hookId.'.json', $webhook_details);
            return $response['id'];
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return $webhookId;
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return $webhookId;
        }
    }

    public function checkHookExist($topic, $cred)
    {
        $webhookId = 0;
        try {
            $wkshopify = new ShopifyClient($cred['domain_name'], $cred['api_secret'], $cred['api_key'], $cred['api_secret']);
            $response = $wkshopify->call('GET', '/admin/webhooks.json?topic=', $topic);
            if (count($response) > 0) {
                $webhookId = $response['id'];
            }

            return $webhookId;
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return $webhookId;
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return $webhookId;
        }
    }

    public function getCollectByCollectionId($shopId, $collectionId, $params = array())
    {
        $limit = '';
        $shopifyLimit = Registry::get('settings.Appearance.admin_elements_per_page');
        $product_list = [];

        if (empty($params['items_per_page'])) {
            $params['items_per_page'] = Registry::get('settings.Appearance.admin_elements_per_page');
        }
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        try {
            $credentials = self::getCredentialById($shopId);
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            $params['total_items'] = $wkshopify->call('GET', '/admin/products/count.json?collection_id='.$collectionId);
            $shopifyLimit = $params['total_items'];
            if (!empty($params['limit'])) {
                $limit = $params['limit'];
            } elseif (!empty($params['items_per_page'])) {
                $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
                $shopifyLimit = $params['items_per_page'];
            }
            $shopifyLimit = $params['total_items'];

            $product_list = $wkshopify->call('GET', '/admin/products.json?fields=id,updated_at,title,created_at&collection_id='.$collectionId.'&limit='.$shopifyLimit);
            $shopifyProductIds = array_column($product_list, 'id');
            $cscartProductIds="";
            if (!empty($shopifyProductIds) ) {
                $cscartProductIds = db_get_hash_single_array(
                    'SELECT shopify_product_id, product_id FROM ?:products WHERE shopify_product_id IN (?a) AND shopify_is_parent = ?s',
                    array('shopify_product_id', 'product_id'),
                    $shopifyProductIds,
                    'Y'
                );
            }
            
            $params['items_per_page'] = $params['total_items'];

            return [$product_list, $params, $cscartProductIds];
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return [$product_list, $params];
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return [$product_list, $params];
        }
    }

    public function getCategoryByCollection($collectionId)
    {
        try {
            $category_arr = db_get_fields('SELECT category_id FROM ?:wk_shopify_category_map WHERE shopify_collection_id = ?i', $collectionId);

            return $category_arr;
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function importShopifyProduct($productId, $shopId, $collectionId)
    {
        try {
            $request_data = $_REQUEST;
            list($product_data, $credentials) = self::fetchShopifyProductData($shopId, $productId);

            list($productid, $has_option) = self::createCscartProduct($product_data, $credentials['company_id'], self::getCategoryByCollection($collectionId), $shopId, $credentials['shopify_currency_code'], $credentials['default_cscart_category_id']);

            return [$productid, $product_data, $has_option];
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function fetchShopifyProductData($shopId, $productId)
    {
        try {
            $credentials = self::getShopDetailById($shopId);
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            $product_data = $wkshopify->call('GET', '/admin/products/'.$productId.'.json');

            return [$product_data, $credentials];
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function checkProductHasOption($product_data)
    {
        // fn_print_r($product_data);
        if (count($product_data['options'][0]['values']) == 1 && $product_data['options'][0]['values'][0] == 'Default Title') {
            return array(false, $product_data['variants'][0]['inventory_quantity']);
        }

        return array(true, 0);
    }

    public function createCscartProduct($product_data, $company_id, $category_arr, $shopId, $currency_code = CART_PRIMARY_CURRENCY, $default_category)
    {
        ini_set('user_agent', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36');
        if (empty($category_arr)) {
            $category_arr[0] = $default_category;
        }
        // fn_print_die($product_data);
        list($has_option, $inventory) = self::checkProductHasOption($product_data);
        $data = [
            'product' => $product_data['title'],
            'company_id' => $company_id,
            'product_type' => ($has_option) ? 'C' : 'P',
            'category_ids' => $category_arr,
            'full_description' => $product_data['body_html'],
            'list_price' => 0.00,
            'shopify_product_id' => $product_data['id'],
            'shopify_account_id' => $shopId,
        ];

        $data['amount'] = $product_data['variants'][0]['inventory_quantity'];

        $_REQUEST = [];
        $_REQUEST['product_id'] = 0;
        $_REQUEST['product_data'] = $data;

        if (isset($product_data['images']) && !empty($product_data['images'])) {
            $_REQUEST['product_add_additional_image_data'] = array();
            $count_abhi = 0;
            foreach ($product_data['images'] as $key => $image_data) {
                $_REQUEST['product_add_additional_image_data'][++$key] = array(
                    'type' =>'M',
                    'object_id' => 0,
                    'position' => $image_data['id'],
                    'is_new' => 'Y',
                    'detailed_alt' => '',
                );
                $_REQUEST['type_product_add_additional_image_detailed'][$key] = 'url';
                $_REQUEST['file_product_add_additional_image_detailed'][$key] = $image_data['src'];
                $count_abhi++;
            }
        }
        if(!empty($product_data['variants'][0]['sku'])){
            $sku = $product_data['variants'][0]['sku'];
        }else{
            $sku =  " ";
        }
        $_REQUEST['product_data']['tracking'] = ($product_data['variants'][0]['inventory_management'] == null) ? 'D' : 'B';
        $_REQUEST['product_data']['product_code'] = $sku;
        $_REQUEST['product_data']['weight'] = $product_data['variants'][0]['weight'];
        $_REQUEST['product_data']['price'] = fn_format_price_by_currency($product_data['variants'][0]['price'], $currency_code, CART_PRIMARY_CURRENCY);
        $_REQUEST['product_data']['list_price'] = fn_format_price_by_currency($product_data['variants'][0]['compare_at_price'], $currency_code, CART_PRIMARY_CURRENCY);
        $_REQUEST['object_type'] = 'P';
        $xmodedata = db_get_row("SELECT * FROM ?:wk_shopify_store WHERE shop_id = ?i", $shopId);
        if($product_data['status']  == 'draft'){
        if(isset($xmodedata) && !empty($xmodedata)){
            if($xmodedata['wk_shopify_draft_product_import'] == 'D'){
                $_REQUEST['product_data']['status'] = 'D';
            }
            elseif($xmodedata['wk_shopify_draft_product_import'] == 'H'){
                $_REQUEST['product_data']['status'] = 'H';
            }
            else{
                $_REQUEST['product_data']['status'] = 'A';
            }

        }
        else{
            $_REQUEST['product_data']['status'] = 'A';
        }
        }
        $product_id = fn_update_product($_REQUEST['product_data'], $_REQUEST['product_id'], DESCR_SL);
        if (isset($product_data['variants']) && !empty($product_data['variants'])) {

            db_query('UPDATE ?:images_links  SET type = "A" WHERE object_id = ?i',$product_id);
            db_query('UPDATE ?:images_links  SET type = "M" WHERE position = ?i AND object_id = ?i' ,$product_data['variants'][0]['image_id'],$product_id);
            
        }
        if ($has_option) {
            $_REQUEST['product_main_image_data'] = array();
            $_REQUEST['product_add_additional_image_data'] = array();
            fn_wk_create_product_features($shopId, $product_id, $currency_code, $product_data);

        }

        return [$product_id, $has_option];
    }

    public function fn_save_shopify_product_options_data($product_id, $shopify_product_data)
    {
        if (isset($shopify_product_data['options']) && !empty($shopify_product_data['options'])) {
            foreach ($shopify_product_data['options'] as $key => $op_data) {
                $_REQUEST = array();
                $variant_arr = $image_data = $file_image = $type_image = array();
                foreach ($op_data['values'] as $key => $variant_data) {
                    $variant_arr[$key] = array(
                        'position' => 0,
                        'variant_name' => $variant_data,
                        'modifier' => '',
                        'modifier_type' => 'A',
                        'weight_modifier' => '',
                        'weight_modifier_type' => 'A',
                        'status' => 'A',
                        'point_modifier' => 0.000,
                        'point_modifier_type' => 'A',
                    );
                    $image_data[$key] = array(
                        'pair_id' => '',
                        'type' => 'V',
                        'object_id' => 0,
                        'image_alt' => '',
                    );
                    $file_image[$key] = isset($variant_data['imgUrl']) && !empty($variant_data['imgUrl']) ? $variant_data['imgUrl'] : '';
                    $type_image[$key] = 'url';
                }
                $options = array(
                    'product_id' => $product_id,
                    'option_name' => $op_data['name'],
                    'position' => 0,
                    'inventory' => 'Y',
                    'company_id' => Registry::get('runtime.company_id'),
                    'option_type' => 'S',
                    'description' => '',
                    'comment' => '',
                    'required' => 'N',
                    'regexp' => '',
                    'inner_hint' => '',
                    'incorrect_message' => '',
                    'allowed_extensions' => '',
                    'max_file_size' => '',
                    'multiupload' => 'N',
                    'variants' => $variant_arr,
                    'lang_code' => DESCR_SL,
                );
                $_REQUEST = array(
                    'option_id' => 0,
                    'option_data' => $options,
                    'file_variant_image_image_icon' => $file_image,
                    'type_variant_image_image_icon' => $type_image,
                    'object' => 'product',
                    'variant_image_image_data' => $image_data,
                );
                $option_id = fn_update_product_option($_REQUEST['option_data']);
            }
        }
    }

    public function fetchShippingZone($shopId)
    {
        try {
            $credentials = self::getShopDetailById($shopId['account_id']);
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            $zone_data = $wkshopify->call('GET', '/admin/shipping_zones.json');

            return $zone_data;
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function fetchShopifyImportedProduct($account_id)
    {
        try {
            $params = $_REQUEST;
            $condition = '';
            $limit = '';
            $order = '';
            $join = '';
            if (isset($_REQUEST['sort_order'])) {
                if ($_REQUEST['sort_order'] == 'asc') {
                    $params['sort_order_rev'] = 'desc';
                    $params['sort_order'] = $_REQUEST['sort_order'];
                } else {
                    $params['sort_order_rev'] = 'asc';
                    $params['sort_order'] = $_REQUEST['sort_order'];
                }
            } else {
                $params['sort_order_rev'] = 'asc';
                $params['sort_order'] = 'desc';
            }
            if (isset($params['sort_by'])) {
                $order .= 'ORDER BY '.$params['sort_by'].' '.$params['sort_order'];
            } else {
                $order .= 'ORDER BY product_id DESC';
                $params['sort_by'] = 'product_id';
                $params['sort_order'] = 'desc';
            }
            if (isset($params['account_id']) && !empty($params['account_id'])) {
                $condition .= db_quote(' AND shopify_account_id = ?i', "{$params['account_id']}");
                $condition .= db_quote(' AND shopify_is_parent = ?s', 'Y');
            }

            if (empty($params['items_per_page'])) {
                $params['items_per_page'] = Registry::get('settings.Appearance.admin_elements_per_page');
            }
            if (empty($params['page'])) {
                $params['page'] = 1;
            }
            $join .= 'LEFT JOIN ?:product_prices as prices ON prices.product_id = products.product_id';
            $join .= ' LEFT JOIN ?:product_descriptions as names ON names.product_id = products.product_id';
            $params['total_items'] = db_get_field("SELECT count(*) FROM ?:products WHERE 1 $condition $order $limit");
            if (!empty($params['limit'])) {
                $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
            } elseif (!empty($params['items_per_page'])) {
                $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
            }

            $products = db_get_array("SELECT DISTINCT(products.product_id), products.status, products.shopify_product_id, prices.price, names.product FROM ?:products as products $join WHERE 1 $condition $order $limit");

            return [$products, $params];
        } catch (Exception $e) {
            fn_set_notification('E', 'Error', __('request_not_valid'));

            return [arraay(), arraay()];
        }
    }

    public function updateProductData($product_id, $auth)
    {
        // fn_print_r("update_data",$product_id,$auth);
        try {
            $sfData = db_get_row('SELECT product_id ,shopify_account_id FROM ?:products WHERE shopify_product_id = ?i AND shopify_is_parent = ?s', $product_id, 'Y');
            if (isset($sfData['product_id']) && isset($sfData['shopify_account_id']) && !empty($sfData['shopify_account_id']) && !empty($sfData['product_id'])) {
                list($sfproductData, $cred) = self::fetchShopifyProductData($sfData['shopify_account_id'], $product_id);
                // fn_print_r($sfproductData,$cred);
                // if($cred['status'] )
                $pData = fn_get_product_data($sfData['product_id'], $auth);
                $wk_vendor_status = fn_wk_shopify_data_details($pData['shopify_account_id']);
                // fn_print_r($wk_vendor_status);
                if($wk_vendor_status == 'A'){
                    $pData = [
                        'product' => $sfproductData['title'],
                        'full_description' => $sfproductData['body_html'],
                        'tags' => !empty($sfproductData['tags']) ? explode(',', $sfproductData['tags']) : $sfproductData['tags'],
                    ];
                    list($has_option, $inventory) = self::checkProductHasOption($sfproductData);
                    if (!$has_option) {
                        $pData['amount'] = $inventory;
                        $pData['price'] = $sfproductData['variants'][0]['price'];
                        $pData['list_price'] = $sfproductData['variants'][0]['compare_at_price'];
                        $pData['weight'] = $sfproductData['variants'][0]['weight'];
                    }
                    fn_update_product($pData, $sfData['product_id']);

                    if ($has_option) {
                        $parent_product_id = $sfData['product_id'];
                        foreach ($sfproductData['variants'] as $key => $variant_data) {
    
                            $sfData = db_get_row('SELECT product_id, account_id FROM ?:wk_shopify_products_map WHERE shopify_product_id = ?i AND shopify_variation_id = ?i AND parent_product_id = ?i', $variant_data['product_id'], $variant_data['id'], $parent_product_id);
    
                            if (isset($sfData['product_id']) && isset($sfData['account_id']) && !empty($sfData['account_id']) && !empty($sfData['product_id'])) {
                                $variantPData = fn_get_product_data($sfData['product_id'], $auth);
                                $variantPData = [
    
                                    'price' => $variant_data['price'],
                                    'product_code' => $variant_data['sku'],
                                    'amount' => $variant_data['inventory_quantity'],
                                    'weight' => $variant_data['weight'],
                                    'list_price' => $variant_data['compare_at_price'],
                                ];
                                fn_update_product($variantPData, $sfData['product_id']);
                            }
                        }
                    }

                }
                
                


               
            }
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function createOrder($orderData, $shopId)
    {
        try {
            $credentials = self::getShopDetailById($shopId);
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            file_put_contents('testorder1.json', json_encode($orderData));
            $order = $wkshopify->call('POST', '/admin/orders.json', $orderData);

            return $order['id'];
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return 0;
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return 0;
        }
    }

    public function fetchOrdersByShop($shopId)
    {
        $shopifyorder = $orders = [];
        try {
            $params = $_REQUEST;
            $condition = '';
            $limit = '';
            $order = '';
            $join = '';
            if (isset($_REQUEST['sort_order'])) {
                if ($_REQUEST['sort_order'] == 'asc') {
                    $params['sort_order_rev'] = 'desc';
                    $params['sort_order'] = $_REQUEST['sort_order'];
                } else {
                    $params['sort_order_rev'] = 'asc';
                    $params['sort_order'] = $_REQUEST['sort_order'];
                }
            }

            if (isset($params['sort_by'])) {
                $order .= 'ORDER BY '.$params['sort_by'].' '.$params['sort_order'];
            } else {
                $order .= 'ORDER BY order_id DESC';
                $params['sort_by'] = 'order_id';
                $params['sort_order'] = 'desc';
            }
            if (isset($params['account_id']) && !empty($params['account_id'])) {
                $condition .= db_quote('AND account_id = ?i', "{$shopId}");
            }

            if (empty($params['items_per_page'])) {
                $params['items_per_page'] = Registry::get('settings.Appearance.admin_elements_per_page');
            }
            if (empty($params['page'])) {
                $params['page'] = 1;
            }

            $params['total_items'] = db_get_field("SELECT count(*) FROM ?:wk_shopify_order_map WHERE 1 $condition $order $limit");
            if (!empty($params['limit'])) {
                $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
            } elseif (!empty($params['items_per_page'])) {
                $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
            }            $orders = db_get_array("SELECT * FROM ?:wk_shopify_order_map WHERE 1 $condition $order $limit");
            if (isset($orders) && !empty($orders)) {
                $order_ids = fn_array_column($orders, 'shopify_order_id');
                $ids = implode(',', $order_ids);
                $fields = 'financial_status,total_price,id,currency';
                $credentials = self::getShopDetailById($shopId);
                $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
                $shopifyorder = $wkshopify->call('GET', '/admin/orders.json?ids='.$ids.'&fields='.$fields);
                $shopifyorder = fn_array_value_to_key($shopifyorder, 'id');
            }

            return [$orders, $shopifyorder, $params];
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function getSmartCollectionsToImport($shopId, $params)
    {
        try {
            $suffix = '';
            if (empty($params['page'])) {
                $params['page'] = 1;
            }

            if (empty($params['items_per_page'])) {
                $params['items_per_page'] = Registry::get('settings.Appearance.admin_elements_per_page');
            }

            $credentials = self::getShopDetailById($shopId);
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);

            $params['total_items'] = $wkshopify->call('GET', '/admin/smart_collections/count.json/');
            $suffix .= 'limit='.$params['total_items'].'&fields=id,title';

            $collections = $wkshopify->call('GET', '/admin/smart_collections.json/?'.$suffix);

            $params['items_per_page'] = $params['total_items'];

            return [$collections, $params];
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function getCustomCollectionsToImport($shopId, $params)
    {
        try {
            $suffix = '';
            if (empty($params['page'])) {
                $params['page'] = 1;
            }

            if (empty($params['items_per_page'])) {
                $params['items_per_page'] = Registry::get('settings.Appearance.admin_elements_per_page');
            }

            $credentials = self::getShopDetailById($shopId);
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            
            $raj = $wkshopify->call('GET', '/admin/custom_collections/count.json/');


            $params['total_items'] = $wkshopify->call('GET', '/admin/custom_collections/count.json/');

            if($params['total_items'] >= 250){

                $params['total_items'] = 250;
            }
            $suffix .= 'limit='.$params['total_items'].'&fields=id,title';
            $custom_collection = $wkshopify->call('GET', '/admin/custom_collections.json/?'.$suffix);

            $params['items_per_page'] = $params['total_items'];

            return [$custom_collection, $params];
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());
        }
    }

    public function listOrders($params)
    {
        $orders = [];
        $query = '/admin/orders.json/?';
        if (empty($params['items_per_page'])) {
            $params['items_per_page'] = Registry::get('settings.Appearance.admin_elements_per_page');
        }
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        $query .= 'limit='.$params['items_per_page'].'&fields=id,created_at,order_number,total_price,currency,financial_status,fulfillment_status';
        if (isset($params['fullfillment']) && !empty($params['fullfillment'])) {
            $query .= '&fulfillment_status='.$params['fullfillment'];
        }

        if (isset($params['min_created']) && !empty($params['min_created']) && isset($params['max_created']) && !empty($params['max_created'])) {
            $query .= '&created_at_min'.date('c', fn_parse_date($params['min_created'])).'&created_at_max='.date('c', fn_parse_date($params['max_created']));
        }
        if (isset($params['order_ids']) && !empty($params['order_ids'])) {
            $query .= '&ids='.$params['order_ids'];
        }

        if (isset($params['order_status']) && !empty($params['order_status'])) {
            $query .= '&status='.$params['order_status'];
        }

        try {
            $credentials = self::getShopDetailById($params['account_id']);
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            $params['total_items'] = $wkshopify->call('GET', str_replace('orders.json', 'orders/count.json', $query));
            $orders = $wkshopify->call('GET', $query);

            return [$orders, $params];
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return [$orders, $params];
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return [$orders, $params];
        }
    }

    public function fetchOrderData($shopId, $orderId)
    {
        $orderData = [];

        try {
            $credentials = self::getShopDetailById($shopId);
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            $orderData = $wkshopify->call('GET', '/admin/orders/'.$orderId.'.json');

            return $orderData;
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return $orderData;
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return $orderData;
        } catch (Exception $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return $orderData;
        }
    }

    public function dep_fn_fetch_cscart_product($lineItems, $shopId)
    {
        $credentials = self::getShopDetailById($shopId);
        $cartProducts = [];
        $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
        foreach ($lineItems as $key => $item) {
            $product_id = db_get_field('SELECT product_id FROM ?:products WHERE shopify_product_id = ?i AND shopify_account_id = ?i', $item['product_id'], $shopId);
            if (isset($product_id) && !empty($product_id)) {
                $variantProductId = db_get_field('SELECT product_id FROM ?:products WHERE shopify_product_id = ?i AND shopify_account_id = ?i', $item['variant_id'], $shopId);
                $cartProducts[$variantProductId]['amount'] = $item['quantity'];
            } else {
                $collectionId = self::getCollectionByVariation($item['product_id'], $credentials);
                list($productid, $shopify_product_data) = self::importShopifyProduct($item['product_id'], $shopId, $collectionId);

                $variantProductId = db_get_field('SELECT product_id FROM ?:products WHERE shopify_product_id = ?i AND shopify_account_id = ?i', $item['variant_id'], $shopId);
                $cartProducts[$variantProductId]['amount'] = $item['quantity'];
            }
        }

        return $cartProducts;
    }

    public function getCollectionByVariation($variationId, $credentials)
    {
        $collectionId = 0;
        try {
            $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);
            $collect = $wkshopify->call('GET', '/admin/collects.json/?product_id='.$variationId);
            $collectionId = $collect[0]['collection_id'];

            return $collectionId;
        } catch (ShopifyApiException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return $collectionId;
        } catch (ShopifyCurlException $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return $collectionId;
        } catch (Exception $e) {
            fn_set_notification('E', 'Error', $e->getMessage());

            return $collectionId;
        }
    }
}
