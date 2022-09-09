
<div id="content_zone_`$zone_id`"> 
<form action="{""|fn_url}" method="post" name="shopify_shipping_mapped_form">
<input type="hidden" name='account_id' value="{$account_id}"/>
<input type="hidden" name='zone_id' value="{$zone_id}"/>
{if $countries}
      <div>
        <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr class="cm-first-sibling">
                    <th width="30%">{__("countries")}</th>
                    <th width="30%">{__("location")}</th>
                    {* <th width="10%">&nbsp;</th> *}
                </tr>
            </thead>
            <tbody>
                {foreach from=$countries key=key item=country}
                <tr>
                    <td>
                        {* <input type='hidden' name='shipping_entry[{$key}][country_code]' value="{$country.code}" /> *}
                        {$country.name} ({$country.code})
                    </td>
                    <td>
                        
                        <select name="shipping_entry[{$country.code}]">
                            {foreach from=$destinations item="destination"}
                                <option value="{$destination.destination_id}">{$destination.destination}</option>
                            {/foreach}
                        </select>
                    </td>
                    {* <td>
                        <input type="text" name="shipping_entry[{$entry.shipping_template_entry_id}][primary_cost]" class="input-medium cm-value-decimal" value="{$entry.primary_cost}"/>
                       
                    </td> *}
                    
                
                </tr>
                {/foreach}
                
                
            </tbody>
        </table>
        </div>
    <!--#content_entries--></div>

{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
<div class="buttons-container">
    {include file="buttons/save_cancel.tpl" but_name="dispatch[wk_shopify.mapshipping]" cancel_action="close" save=$id}
</div>
</form>
</div>
{* {capture name="sidebar"}
{include file="addons/wk_shopify_connector/common/components/tab.tpl"}
{/capture} *}

{* {/capture}

{include file="common/mainbox.tpl" title=__("mapp_shipping_zone_to_method") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons} *}