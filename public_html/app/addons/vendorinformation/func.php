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

function fn_vendorinformation_update_company_pre(&$company_data, $company_id, $lang_code, $can_update){
    if(isset($company_data['company_golive']) && $company_id){
        $company_old_status = db_get_field("SELECT company_golive FROM ?:companies WHERE company_id = ?i", $company_id);
        if($company_old_status != $company_data['company_golive']){
            $company_data['ec_changed'] = true;
        }
    }
}

function fn_vendorinformation_update_company($company_data, $company_id, $lang_code, $action)
{
    $lang_code='en';
    $pair_data = fn_attach_image_pairs('vendor_banners_main', 'vendorbanners',trim($company_id), $lang_code);	

    if(!empty($company_data['ec_changed']) && !empty($company_data['email']) && isset($company_data['company_golive'])){
        if($company_data['company_golive'] == '3'){
			$mailer = Tygh::$app['mailer'];
            // fn_prinr_die($company_data['email']);

			return $mailer->send(array(
                'to' => $company_data['email'],
                'from' => 'company_orders_department',
                'template_code' => 'ec_vendor_request.approved',
                'tpl' => 'app/addons/vendorinformation/ec_vendor_request.approved.tpl', 
            ), 'A', CART_LANGUAGE);
        } elseif($company_data['company_golive'] == '2'){
			$mailer = Tygh::$app['mailer'];
            $disapproved_text = !empty($company_data['disapproved_resason'])?$company_data['disapproved_resason']:'';
			return $mailer->send(array(
                'to' => $company_data['email'],
                'from' => 'company_orders_department',
                'data' => array(
                    'disapproved_text' => $disapproved_text,
                ),
                'template_code' => 'ec_vendor_request.disapproved',
                'tpl' => 'app/addons/vendorinformation/ec_vendor_request.disapproved.tpl', 
            ), 'A', CART_LANGUAGE);   
        }
    }
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
