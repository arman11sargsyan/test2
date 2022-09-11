<?php
/*
* © 2022 CS-Cart.ie
* 
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  
* IN  THE "LICENSE.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE. 
* 
*  
*/


use Tygh\Registry;
use Tygh\Addons\VendorPlans\ServiceProvider;
use Tygh\Enum\SiteArea;
use Tygh\Addons\PdfDocuments\Pdf;

if (!defined('BOOTSTRAP')) { die('Access denied'); }



function fn_ec_stripeconnecttax_vendor_plans_calculate_commission_for_payout_post(array $order_info,array $company_data,array &$payout_data)
{
	$plan_id=trim($payout_data['plan_id']);
    $plan_data_info=db_get_row('SELECT * FROM ?:vendor_plans WHERE plan_id = ?i',$plan_id);
	if(trim($plan_data_info['plan_taxapply']) =='1')
	{
		$plan_taxcommission=trim($plan_data_info['plan_taxcommission']);
		$plan_taxfixed_commission=trim($plan_data_info['plan_taxfixed_commission']);

		$payout_data['payout_taxapply']=trim($plan_data_info['plan_taxapply']);
		$payout_data['payout_taxname']=trim($plan_data_info['plan_taxname']);
		$payout_data['payout_taxcommission']=trim($plan_taxcommission);
		$payout_data['payout_taxfixed_commission']=trim($plan_taxfixed_commission);
		
		/********** CALCULATE FOR COMMISSION PART START **************/
		$formatter = ServiceProvider::getPriceFormatter();
		$commission_amount=trim($payout_data['commission_amount']);
		$admin_percent_commission = $commission_amount * $plan_taxcommission / 100;

        $admin_percent_commission_round = $formatter->round($admin_percent_commission);
		$totalcommission_admin=($admin_percent_commission_round + $plan_taxfixed_commission);
		$payout_totalcommission = $formatter->round($totalcommission_admin);
		/********** CALCULATE FOR COMMISSION PART START **************/

		$payout_data['payout_totalcommission']=$payout_totalcommission;
		$payout_data['commission_amount'] +=$payout_totalcommission;
	}
	else
	{
		$payout_data['payout_taxapply']="0";
		$payout_data['payout_taxname']="";
		$payout_data['payout_taxcommission']="0.00";
		$payout_data['payout_taxfixed_commission']="0.00";
		$payout_data['payout_totalcommission']="0.00";		
	}
}
function fn_print_order_receipt($order_ids, $params = [])
{
    // Backward compatibility
    if (is_bool($params)) {
        // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
        $args = func_get_args();
        $params = [];

        /**
         * Executes when normalizing parameters for packaging slips printing, allows you to populate additional parameters.
         *
         * @param array<int, string|bool>    $args   Function arguments
         * @param array<string, string|bool> $params Normalized parameters
         */
     

        if (isset($args[2])) {
            $params['lang_code'] = $args[2];
        }
    }

    // Default params
    $params = array_merge(
        [
            'area'           => SiteArea::ADMIN_PANEL,
            'lang_code'      => CART_LANGUAGE,
            'add_page_break' => true,
        ],
        $params
    );

    $order_ids = (array) $order_ids;

    /**
     * Executes before printing order packing slips, allows you to modify parameters passed to the function.
     *
     * @param array<int>                 $order_ids Order IDs to print slips for
     * @param array<string, string|bool> $params    Print parameters
     */
    

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $html = [];

    foreach ($order_ids as $order_id) {
       $order_info = fn_get_order_info($order_id, false, true, false, false);

            if (empty($order_info)) {
                continue;
            }

			$vendor_payouts = db_get_row('SELECT * FROM ?:vendor_payouts WHERE order_id  = ?i AND payout_type=?s ', $order_info['order_id'],'order_placed');
			$view->assign('order_info', $order_info); 
			$view->assign('vendor_payout', $vendor_payouts); 

            $html[] = $view->displayMail('addons/ec_stripeconnecttax/print_order_receipt_slip.tpl', false, $params['area'], $order_info['company_id'], $params['lang_code']);

        if (
            !$params['add_page_break']
            || $order_id === end($order_ids)
        ) {
            continue;
        }

        $html[] = "<div style='page-break-before: always;'>&nbsp;</div>";
    }

    $output = implode(PHP_EOL, $html);

   
   //fn_set_hook('print_order_packing_slips_post', $order_ids, $params, $html, $output);
	$pdf_documents_addon = Registry::get('addons.pdf_documents');
	if(trim(@$pdf_documents_addon['status']) =='A')
	{
	  Pdf::render($html, __('ec_stripeconnecttax_order_receipt_file') . '-' . implode('-', $order_ids));
	}
    return $output;
}

function fn_get_vendor_profile_field_data($vendor_id, $lang_code = DESCR_SL)
{
    $profile_field = db_get_array(
        'SELECT * FROM ?:profile_fields AS pf'
        . ' LEFT JOIN ?:profile_field_descriptions AS pfd ON pf.field_id = pfd.object_id'
        . ' WHERE pfd.lang_code = ?s AND pfd.object_type = ?s AND pf.section = ?s',        
        $lang_code,'F','V');
	$profile_field_data=array();	
   for($y=0;$y<count($profile_field);$y++)
	{
		$profile_value = db_get_row('SELECT value FROM ?:profile_fields_data WHERE field_id = ?i AND object_id = ?s ',$profile_field[$y]['field_id'],$vendor_id);		
		if($profile_field[$y]['field_name'] =='vendorcountry')
		{
			$country_descriptions = db_get_row('SELECT country FROM ?:country_descriptions WHERE code = ?s AND lang_code =?s',trim($profile_value['value']),$lang_code);
		  $profile_field_data[$profile_field[$y]['field_name']]=trim($country_descriptions['country']);
		}
		else
		{
			$profile_field_data[$profile_field[$y]['field_name']]=trim($profile_value['value']);
		}
	}
	/*echo "<pre>";
	print_r($profile_field);
	print_r($profile_field_data);
	exit;
	*/

    return $profile_field_data;
}
function fn_get_country_descriptions($country_code, $lang_code = DESCR_SL)
{
	$country_descriptions = db_get_row('SELECT country FROM ?:country_descriptions WHERE code = ?s AND lang_code =?s',trim($country_code),$lang_code);
	return $country_descriptions['country'];
}
