<?php

if (!defined('BOOTSTRAP')) {
    die('Access Denied');
}
use Tygh\Addons\ShopifyConnector\Api\WkShopify;

$wkShopify = new WkShopify();

if ($mode == 'webhookupdate') {
    $data = json_decode(file_get_contents('php://input'), true);

    $wkShopify->updateProductData($data['id'], Tygh::$app['session']['auth']);
    $t=time();

    //fn_print_die($wkShopify->updateProductData('4368220192841', Tygh::$app['session']['auth']));

    file_put_contents('zz.json', json_encode(array($_REQUEST, $data, $t)));
    exit;
}
