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

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD']	== 'POST') {
if ($mode == 'golive') {
	
	$companyinfo=Registry::get('runtime.company_data');
	//exit;
	if(trim($_REQUEST['companyid']) !="")
	{
		$vendor_data['company_golive']="1";
		$vendor_data['company_golive_date']=time();
		$company_id = trim($_REQUEST['companyid']);
		$returnid=db_query('UPDATE ?:companies SET ?u WHERE company_id = ?i', $vendor_data, $company_id);	
		if($returnid){
			$company_email = db_get_field("SELECT email FROM ?:companies WHERE company_id = ?i", $company_id);
			$mailer = Tygh::$app['mailer'];
			$mailer->send(array(
                'to' => $company_email,
                'from' => 'company_orders_department',
                'template_code' => 'ec_vendor_request.received',
                'tpl' => 'app/addons/vendorinformation/ec_vendor_request.received.tpl', 
            ), 'A', CART_LANGUAGE);

		}
		fn_set_notification('N', __('notice'), __('vendorinfo_golive_success_msg'));
	}
	else
	{
		fn_set_notification('E', __('error'), __('vendorinfo_golive_error_msg'));
	}	
	fn_redirect('', CONTROLLER_STATUS_OK);
    exit;
 }
}