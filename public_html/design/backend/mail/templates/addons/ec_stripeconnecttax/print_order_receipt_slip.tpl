<!DOCTYPE html>
<html dir="{$language_direction}">
<head></head>

<body>

{if $order_info}
{literal}
<style media="screen,print">
body,p,div {
    color: #000000;
    font: 12px Arial;
}
body {
    padding: 0;
    margin: 0;
    direction: {$language_direction};
}
a, a:link, a:visited, a:hover, a:active {
    color: #000000;
    text-decoration: underline;
}
a:hover {
    text-decoration: none;
}
</style>
<style media="print">
body {
    background-color: #ffffff;
}
.scissors {
    display: none;
}
td {
    vertical-align: top;
}
</style>
{/literal}
{include file="common/scripts.tpl"}
{if !$company_placement_info}
{assign var="company_placement_info" value=$order_info.company_id|fn_get_company_placement_info:$smarty.const.CART_LANGUAGE}
{/if}

{assign var="company_billing_info" value=$order_info.company_id|fn_get_vendor_profile_field_data:$smarty.const.CART_LANGUAGE}

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="direction: {$language_direction};height: 100%;">
<tr>
    <td align="center" style="width: 100%; height: 100%; padding: 24px 0;">
    <div style="background-color: #ffffff; border: 1px solid #e6e6e6; margin: 0px auto; padding: 0px 44px 0px 46px; width: 510px; text-align: left;">
       
        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="direction: {$language_direction}; padding-top:32px;">
		<!--<tr>
<td align="center" style="padding-bottom: 3px;" valign="middle"><img src="{$logos.mail.image.image_path}" width="{$logos.mail.image.image_x}" height="{$logos.mail.image.image_y}" border="0" alt="{$logos.mail.image.alt}" /></td> 
</tr> -->
<tr><td width="100%" align="center">&nbsp;<img src="{$config.current_location|fn_url}/images/orderreceiptlogo.png" border="0" alt="{$logos.mail.image.alt}" /></td></tr>
<tr><td>&nbsp;</td></tr>
        <tr valign="top">
            <td width="100%" align="left" style="padding-bottom:30px;">				
				 <p style="margin: 2px 0px 3px 0px;font-size:14px;">
                    {__("ec_stripeconnecttax_date_paid")}&nbsp;: <strong>{$order_info.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</strong>
                 </p>
				 <p style="margin: 2px 0px 3px 0px;font-size:14px;">
                    {__("ec_stripeconnecttax_receipt_number")}&nbsp;: <strong>{$order_info.order_id}</strong>
                 </p>                
            </td>
        </tr>       
        </table>

        
        {* Customer info *}

        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="direction: {$language_direction};">
        <tr>
            <td style="width: 50%; padding: 14px 0px 0px 2px;">
			 <h3 style="font:bold 16px Tahoma; margin: 0px 0px 10px 0px;">{__("ec_stripeconnecttax_paid_by")}</h3>
                <h2 style="font: bold 14px Arial; margin: 0px 0px 3px 0px;">{$company_billing_info.vendorcompanyname|ucfirst}</h2>
                {$company_billing_info.vendoraddress1} {$company_billing_info.vendoraddress2}<br />
                {$company_billing_info.vendorcity} ,{$company_billing_info.vendorstate} {$company_billing_info.vendorzipcode}<br />
                {$company_billing_info.vendorcountry}
                <!--<table cellpadding="0" cellspacing="0" border="0" style="direction: {$language_direction};">
                {if $company_placement_info.company_phone}
                <tr valign="top">
                    <td style="font: 12px verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px;    white-space: nowrap;">{__("phone1_label")}:</td>
                    <td width="100%"><span dir="ltr">{$company_placement_info.company_phone}</span></td>
                </tr>
                {/if}
                {if $company_placement_info.company_phone_2}
                <tr valign="top">
                    <td style="font: 12px verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("phone2_label")}:</td>
                    <td width="100%"><span dir="ltr">{$company_placement_info.company_phone_2}</span></td>
                </tr>
                {/if}
                {if $company_placement_info.company_website}
                <tr valign="top">
                    <td style="font: 12px verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("web_site")}:</td>
                    <td width="100%">{$company_placement_info.company_website}</td>
                </tr>
                {/if}
                {if $company_placement_info.company_orders_department}
                <tr valign="top">
                    <td style="font: 12px verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("email")}:</td>
                    <td width="100%"><a href="mailto:{$company_placement_info.company_orders_department}">{$company_placement_info.company_orders_department|replace:",":"<br>"|replace:" ":""}</a></td>
                </tr>
                {/if}
                </table>-->
            </td>
{assign var="country_full_name" value=$settings.Company.company_country|fn_get_country_descriptions:$smarty.const.CART_LANGUAGE}
            <td style="padding-top: 14px;" valign="top">
                 <h3 style="font: bold 16px Tahoma; margin: 0px 0px 10px 0px;">{__("ec_stripeconnecttax_issued_by")}</h3>
                <h2 style="font: bold 14px Arial; margin: 0px 0px 3px 0px;">{$settings.Company.company_name|ucfirst}</h2>
                {$settings.Company.company_address}<br />
                {$settings.Company.company_city} ,{$settings.Company.company_state}  {$settings.Company.company_zipcode}<br />
                {$country_full_name}
            </td>
        </tr>
        </table>

        {* Ordered products *}

        <table width="100%" cellpadding="0" cellspacing="1" style="direction: {$language_direction}; background-color: #dddddd; margin-top:50px;font-size:14px;">
        <tr>
            <th width="100%" style="background-color: #eeeeee; padding: 6px 10px; white-space: nowrap;height:25px;color:grey;font-weight:normal;" colspan="2">{__("ec_stripeconnecttax_orderreceipt_summary")}</th>
        </tr>       
            {if $vendor_payout.payout_id !=""}
			{assign var="orderid" value=$vendor_payout.order_id}  
			{assign var="taxname" value=$vendor_payout.payout_taxname}
			{assign var="taxper" value="`$vendor_payout.payout_taxcommission`%"}  
            <tr>
                <td style="padding: 5px 10px; background-color: #ffffff;height:25px;">{__('vendorinformation_orderreceipt_summary_transactionfee', array('[orderid]' => $orderid))}</td>                
                <td style="padding: 5px 10px; background-color: #ffffff; text-align: center;height:25px;">{include file="common/price.tpl" value=$vendor_payout.marketplace_profit}</td>
            </tr>
			{if $vendor_payout.payout_taxapply =="1"}
			<tr>
                <td style="padding: 5px 10px; background-color: #ffffff;height:25px;">{__('vendorinformation_orderreceipt_summary_tax', array('[taxname]' => $taxname,'[taxper]' => $taxper))}</td>                
                <td style="padding: 5px 10px; background-color: #ffffff; text-align: center;height:25px;">{include file="common/price.tpl" value=$vendor_payout.payout_totalcommission}</td>
            </tr>
			{/if}
			<tr>
                <td style="padding:5px 10px;background-color:#ffffff;height:25px;"><strong>{__('vendorinformation_orderreceipt_summary_amountpaid')}</strong></td>                
                <td style="padding: 5px 10px; background-color: #ffffff; text-align: center;height:25px;"><strong>{include file="common/price.tpl" value=$vendor_payout.commission_amount}</strong></td>
            </tr>
            {/if}       
        </table>

        {* /Ordered products *}

            <table style="direction: {$language_direction};">
                <tr>
                    <td>
                        <div style="padding-top: 20px;">&nbsp;</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="padding-left: 7px; padding-bottom: 15px; overflow-x: auto; clear: both; width: 505px; height: 100%; padding-bottom: 20px; overflow-y: hidden;">{__('vendorinformation_orderreceipt_summary_bottommsg')}</div>
                    </td>
                </tr>
				<tr>
                    <td>
                        <div style="padding-top: 20px;">&nbsp;</div>
                    </td>
                </tr>
            </table>
        
    </div>
    </td>
</tr>
</table>
{/if}

</body>
</html>
