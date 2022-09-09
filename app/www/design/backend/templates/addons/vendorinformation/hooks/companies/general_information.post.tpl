{include file="views/profiles/components/profile_fields.tpl" section="V" default_data_name="company_data" profile_data=$company_data  title=__("vendors_billing_information") subtile=__("vendorbillinginfosubheading")}
{if $auth.user_type =='A'}
{include file="common/subheader.tpl" title=__("vendoraccountapprovalheading")}
<div class="control-group">
    <label for="elm_company_company_golive" class="control-label">{__("vendorinformation_storegoliverequst")}:</label>
<div class="controls">
                    <select id="elm_company_company_golive" name="company_data[company_golive]" class="cm-country cm-location-billing">
                        <option value="">- Select -</option>                        
                        <option {if $company_data.company_golive == "1"}selected="selected"{/if} value="1">Received</option>
			<option {if $company_data.company_golive == "3"}selected="selected"{/if} value="3">Approved</option>
			<option {if $company_data.company_golive == "2"}selected="selected"{/if} value="2">Diapproved</option> 
                    </select>
                </div>

</div>
<div class="control-group">
    <label for="elm_company_disapproved_resason" class="control-label">{__("vendorinfo_disapproved_resason")}:</label>
    <div class="controls">
        <textarea class="input-large" id="elm_company_disapproved_resason" name="company_data[disapproved_resason]" cols="32" rows="3">{$company_data.disapproved_resason}</textarea>
    </div>
</div>

{if $company_data.company_golive != ""}
<div class="control-group">
    <label for="elm_company_addresscountry" class="control-label">{__("vendorinformation_storegoliverequst_date")}:</label>
<div class="controls">{if $company_data.company_golive_date !=''}{$company_data.company_golive_date|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}{/if}</div>
</div>
{/if}
{/if}