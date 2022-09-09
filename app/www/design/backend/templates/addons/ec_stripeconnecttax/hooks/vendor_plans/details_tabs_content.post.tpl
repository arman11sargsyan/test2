<div id="content_stripeconnecttax_tax_{$id}" class="hidden">
<div class="control-group">
            <label class="control-label" for="elm_tax_apply_{$id}">{__("ec_stripeconnecttax_label_tax_apply")} :</label>
            <div class="controls">
                <select id="elm_tax_apply_{$id}" name="plan_data[plan_taxapply]" onchange="fn_tax_apply_type(this.value);">
				<option Value="0" {if $plan.plan_taxapply == "0"}selected="selected"{/if}>No</option>
				<option value="1" {if $plan.plan_taxapply == "1"}selected="selected"{/if}>Yes</option>
				</select>
    </div>
<div class="control-group">
            <label class="control-label" for="elm_tax_name_{$id}">{__("ec_stripeconnecttax_label_tax_name")} :</label>
            <div class="controls" style="margin-top:8px;">
                <input id="elm_tax_name_{$id}" type="hidden" name="plan_data[plan_taxname]" class="input-mini" value="{__("ec_stripeconnecttax_tax_name_value")}" size="10"><strong>{__("ec_stripeconnecttax_tax_name_value")}</strong></div>
    </div>
    <div class="control-group">
            <label class="control-label" for="elm_taxcommission_{$id}">{__("ec_stripeconnecttax_label_transaction_fee")} :</label>
            <div class="controls">
                <input id="elm_taxcommission_{$id}" type="text" name="plan_data[plan_taxcommission]" class="input-mini taxcommissionval" value="{$plan.plan_taxcommission}" size="4"> % + <input type="text" name="plan_data[plan_taxfixed_commission]" value="{$plan.plan_taxfixed_commission}" class="input-mini taxfixedcommissionval" size="4"> {$currencies.$primary_currency.symbol nofilter}
				<p class="muted description">{__("ec_stripeconnecttax_tax_label_info")}</p></div>
    </div>
    </div>
{literal}
<script>
function fn_tax_apply_type(value)
{	
   if(value =='0')
	{		
		$('.taxcommissionval').val("0.00");
		$('.taxfixedcommissionval').val("0.00");
	}
}
</script>
{/literal}
<!--content_stripeconnecttax_tax_{$id}--></div>