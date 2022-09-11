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

use Tygh\Registry;
use Tygh\Tygh;
use Tygh\Enum\UserTypes;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD']	== 'POST') {

if ($mode == 'update') 
	{
	//echo "<pre>";
	//print_r($_REQUEST);
	//exit;
		$lang_code='en';
		$pair_data = fn_attach_image_pairs('vendor_banners_main', 'vendorbanners',trim($_REQUEST['company_id']), $lang_code);	
		if(trim(@$_REQUEST['company_data']['status']) =='A')
		{
		 $vendor_data['company_golive']="3";
		 $company_id = trim($_REQUEST['company_id']);
		 $returnid=db_query('UPDATE ?:companies SET ?u WHERE company_id = ?i', $vendor_data, $company_id);	
		}
		if(trim(@$_REQUEST['company_data']['company_golive']) =='3' && trim(@$_REQUEST['company_data']['status']) =='A')
		{
			//echo "SDFSFDSfs";
			//exit;
		 $vendor_data['status']="A";
		 $company_id = trim($_REQUEST['company_id']);
		 $returnid=db_query('UPDATE ?:companies SET ?u WHERE company_id = ?i', $vendor_data, $company_id);	
		}
   }
}

if ($mode == 'update' || $mode == 'add') {  
	$tabs = Registry::get('navigation.tabs');
    Tygh::$app['view']->assign('states', fn_get_all_states());
    Tygh::$app['view']->assign('countries', fn_get_simple_countries(true, CART_LANGUAGE));
	if ($mode == 'update')
		{
		$lang_code='en';
		$vendor_ids = array(trim($_REQUEST['company_id'])); 
		$vendorid = trim($_REQUEST['company_id']); 
		$vendorbanner_data = fn_get_image_pairs($vendor_ids, 'vendorbanners', 'M', true, false, $lang_code);
		$imageslinks_data = db_get_row("SELECT * FROM ?:images_links WHERE object_id = ?i AND object_type ='vendorbanners' ", $vendorid);
		if(@$imageslinks_data['object_id'] !="")
			{
		     $vendorbanner['main_pair']=$vendorbanner_data[@$imageslinks_data['object_id']][@$imageslinks_data['pair_id']];	
			}
			else
			{
				$vendorbanner['main_pair']="";
			}
		Tygh::$app['view']->assign('vendorbanner', $vendorbanner);	

		$plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_ids);
		$plan_data = $plan->attributes();

		//echo "<pre>";
		//print_r($plan_data);
		//exit;

		}		
		if(trim($auth['user_type']) =='V')
		{
		unset($tabs['addons']);
		}
		//echo "<pre>";
		//print_r($tabs);
		$tabs['detailed']['title']="Business info";
		unset($tabs['description']);
       Registry::set('navigation.tabs', $tabs);
	/**************** Vendor Field value assign *************/
	//Tygh::$app['view']->assign('field_storename','36'); // language variable vendorinformation_text_field_36
	//Tygh::$app['view']->assign('field_emailid','39');  // language variable vendorinformation_text_field_39
	//Tygh::$app['view']->assign('field_vatnumber','50'); // language variable vendorinformation_text_field_50
	//Tygh::$app['view']->assign('field_foodlic','52'); // language variable vendorinformation_text_field_52
	/**************** Vendor Field value assign *************/
} 
if ($mode == 'balance') 
{	
	$auth = Tygh::$app['session']['auth'];
    if ($auth['user_type'] === UserTypes::VENDOR) {
	$tabs = Registry::get('navigation.tabs');
	 unset($tabs['withdrawals']);
       Registry::set('navigation.tabs', $tabs);
	}	
}
