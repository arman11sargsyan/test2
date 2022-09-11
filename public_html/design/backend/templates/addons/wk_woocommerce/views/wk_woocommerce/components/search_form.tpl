<style type="text/css">
    #company_search_ajax_select_object{
        margin-right : -80%;
    }
</style>
<div class="sidebar-row">
<h6>{__("search")}</h6>

<form action="{""|fn_url}" name="woocommerce_merchant_search_form" method="get">
{capture name="simple_search"}

<div class="sidebar-field ajax-select">
    <label>{__("vendor")}</label>
    {if !$runtime.company_id}
        <input type="hidden" name="vendor" id="search_hidden_vendor" value="{$search.vendor|default:'all'}" />
        {include file="common/ajax_select_object.tpl" data_url="companies.get_companies_list?show_all=Y" text=$search.vendor|fn_get_company_name|default:__("all_vendors") result_elm="search_hidden_vendor" id="company_search"}
    {else}
        {$search.vendor|fn_get_company_name}
    {/if}
</div>

<div class="sidebar-field">
    <label>{__("status")}</label>
    <select name="status" class="input-text">
        <option value="">{__("all")}</option>
        <option value="A" {if $search.status eq 'A'}selected{/if}>{__("active")}</option>
        <option value="D" {if $search.status eq 'D'}selected{/if}>{__("disable")}</option>
    </select> 
</div>

{/capture}

{include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search dispatch=$dispatch view_type="wk_woocommerce" in_popup=$in_popup advanced_search=false}

</form>
</div>