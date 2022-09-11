<?php
/*
* © 2022 CS-Cart.ie
* 
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  
* IN  THE "LICENSE.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE. 
* 
*  
*/


if ( !defined('BOOTSTRAP') ) { die('Access denied'); }


use Tygh\Registry;

function fn_vendorinformation_logo_types(&$types, $for_company)
{
    
    $image = fn_get_theme_path('[themes]/[theme]/media/images/addons/vendorinformation/gift_cert_logo.png', 'A', null, false);

    $types['vendorbanner'] = [
        'text'        => 'text_gift_certificate_logo55555555',
        'image'       => $image,
        'single_logo' => true,
    ];

    return true;
}

function fn_vendorinformation_update_company($company_data, $company_id, $lang_code, $action)
{
   $lang_code='en';
  $pair_data = fn_attach_image_pairs('vendor_banners_main', 'vendorbanners',trim($company_id), $lang_code);	
}
/**
 * Gets products count by companies
 *
 * @param int[] $company_ids Company IDs, allows to limit query by specified companies, all allowed company will be get by default
 *
 * @return array<int, int> Companies and their products count list
 */
function fn_get_vendorinformation_companies_active_products_count($company_id = "0")
{
	$company_array=array($company_id);
    $company_ids = array_filter($company_array);
   
    $products_condition = (empty($company_ids)) ? '' : db_quote(' AND products.company_id IN (?n)', $company_ids);
	//$products_condition = (empty($company_ids)) ? '' : db_quote(' AND products.company_id =?i', $company_id);
    $params = [
        'only_short_fields' => true,
        'extend'            => ['companies', 'sharing'],
        'status'            => 'A',
        'get_conditions'    => true,
        'only_for_counting' => true
    ];

    list(, $joins, $conditions) = fn_get_products($params);

    $conditions .= $products_condition;
    $fields = [
        'company_id'     => 'products.company_id',
        'products_count' => 'COUNT(DISTINCT products.product_id) as products_count'
    ];

    $result = db_get_hash_single_array(
        'SELECT ?p'
        . ' FROM ?:products as products ?p'
        . ' WHERE 1=1?p'
        . ' GROUP BY products.company_id',
        ['company_id', 'products_count'],
        implode(', ', $fields),
        $joins,
        $conditions
    );

    foreach ($company_ids as $company_id) {
        $company_id = (int) $company_id;

        if (isset($result[$company_id])) {
            continue;
        }

        $result[$company_id] = 0;
    }

    return $result;
}
