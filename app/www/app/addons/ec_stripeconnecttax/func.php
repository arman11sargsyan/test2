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
