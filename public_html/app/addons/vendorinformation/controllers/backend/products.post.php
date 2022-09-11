<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

use Tygh\BlockManager\SchemesManager;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\OutOfStockActions;
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\ProductZeroPriceActions;
use Tygh\Enum\YesNo;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Tools\Url;
use Tygh\Enum\UserTypes;


defined('BOOTSTRAP') or die('Access denied');

/**
 * @var string $mode
 * @var array $auth
 */
if ($mode == 'addtype') {
 }

elseif ($mode == 'update' || $mode == 'add') {
	$tabs = Registry::get('navigation.tabs');
	$tabs['detailed']['title']="General";
	Registry::set('navigation.tabs', $tabs);	
 }

 if ($mode === 'manage' || $mode === 'master_products') {    
    $dynamic_sections = Registry::ifGet('navigation.dynamic.sections', []);
    $wk_shopify_connectoraddonstatus = Registry::get('addons.wk_shopify_connector');
    if(trim($wk_shopify_connectoraddonstatus['status']) =='A')
	{
		$dynamic_sections['wk_shopify.add'] = [
			'title' => __('vendorinformation_product_list_add_shopify_link'),
			'href'  => 'wk_shopify.add',
		];
	}
	$wk_woocommerce_addonstatus = Registry::get('addons.wk_shopify_connector');
    if(trim($wk_woocommerce_addonstatus['status']) =='A')
	{
		$dynamic_sections['wk_woocommerce.add'] = [
			'title' => __('vendorinformation_product_list_add_woocommerce_link'),
			'href'  => 'wk_woocommerce.add',
		];
	}
	$dynamic_sections['import_presets.add&object_type=products'] = [
        'title' => __('vendorinformation_product_list_add_xml_link'),
        'href'  => 'import_presets.add&object_type=products',
    ];

    Registry::set('navigation.dynamic.sections', $dynamic_sections);
    
}