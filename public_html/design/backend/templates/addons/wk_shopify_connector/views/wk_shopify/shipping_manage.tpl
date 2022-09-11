
{capture name="mainbox"}
<form action="{""|fn_url}" method="post" enctype="multipart/form-data" name="shopify_shipping_mapped_form">
{if $shipping_zones}
    <table width="100%" class="table table-middle">
        <thead>
        <tr>
            <th class="left">{include file="common/check_items.tpl"}</th>
            <th width="5%">
                <span id="on_st"
                        alt="{__("expand_collapse_list")}"
                        title="{__("expand_collapse_list")}"
                        class=" hand cm-combinations-visitors">
                    <span class="icon-caret-right"></span>
                </span>
                <span id="off_st"
                        alt="{__("expand_collapse_list")}"
                        title="{__("expand_collapse_list")}"
                        class="hand hidden cm-combinations-visitors">
                    <span class="icon-caret-down"></span>
                </span>
            </th>
            <th width="10%">{__("zone_id")}</th>
            <th>
              {__("zone_name")}
            </th>
            <th>{__("cscart_shipping_id")}</th>
            <th>{__("cscart_shipping_name")}</th>
            
            <th class="center" width="5%">&nbsp;</th>
            
        </tr>
        </thead>
        {foreach name="shipping_zones" from=$shipping_zones item=shipping}        
            <tr>
                <td class="left">
                    <input type="checkbox" name="zone_ids[]" value="{$shipping.zone_id}" class="cm-item"/>
                </td>
                <td class="left approval-status-{$payout.approval_status|lower}">
                    <span name="plus_minus"
                            id="on_zone_detail_{$smarty.foreach.shipping_zones.iteration}"
                            alt="{__("expand_collapse_list")}"
                            title="{__("expand_collapse_list")}"
                            class="hand cm-combination-visitors">
                        <span class="icon-caret-right"></span>
                    </span>
                    <span name="minus_plus"
                            id="off_zone_detail_{$smarty.foreach.shipping_zones.iteration}"
                            alt="{__("expand_collapse_list")}"
                            title="{__("expand_collapse_list")}"
                            class="hand hidden cm-combination-visitors">
                        <span class="icon-caret-down"></span>
                    </span>
                </td>
                <td class="nowrap">
                   {$shipping.zone_id}
                </td>
                <td>
                    {$shipping.name}
                </td>
                <td>
                    {if $shipping.cscart_shipingId}{$shipping.cscart_shipingId}{else}--{/if}
                </td>
                <td>
                    {if $shipping.cscart_shipingName}<a href="{"shippings.update?shipping_id=`$shipping.cscart_shipingId`"|fn_url}">{$shipping.cscart_shipingName}</a>{else}--{/if}
                </td>
                
                <td class="center nowrap">
                    {if !$hide_controls}
                        <div class="hidden-tools">
                            {capture name="tools_list"}
                                {if $shipping.cscart_shipingId}
                                    <li>{btn type="list" class="cm-confirm" text=__("delete") href="companies.payout_delete?payout_id=`$payout.payout_id`&redirect_url={$c_url|rawurlencode}" method="POST"}</li>
                                {/if}
                                <li>
                                {include file="common/popupbox.tpl" id="zone_`$shipping.zone_id`" title_start=__("shopify_shipping.map_shipping") title_end=$shipping.name link_text=__("edit") act="link" href="wk_shopify.shipping_map?zone_id=`$shipping.zone_id`&account_id=`$account_id`"}
                                </li>
                            {/capture}
                            {dropdown content=$smarty.capture.tools_list}
                        </div>
                    {/if}
                </td>
                
            </tr>
            <tr id="zone_detail_{$smarty.foreach.shipping_zones.iteration}"
                class="row-more {if $hide_extra_button != "Y"}hidden{/if}">
                <td colspan="8" class="row-more-body top row-gray">
                    <div class="control-group">
                        <label class="control-label"
                                for="shipping_comments_{$shipping.shipping_id}">
                            {__("additional_detail")}
                        </label>
                        <div class="controls">
                           <p>
                                <b>{__('weight')}->  </b> 
                                {foreach from=$shipping.weight_based_shipping_rates item=item key=key name=name}
                                  {if $key != 0}|{/if}  <span>{$item.weight_low}-{$item.weight_high} : {$item.price}</span> 
                                {/foreach}
                           </p>
                           <p>
                                <b>{__('price')} -> </b> 
                                {foreach from=$shipping.price_based_shipping_rates item=item key=key name=name}
                                  {if $key != 0}|{/if}  <span>{$item.min_order_subtotal}-{$item.max_order_subtotal} : {$item.price}</span> 
                                {/foreach}
                           </p>
                           <p>
                                <b> {__('carrier_shipping_rate_providers')} -></b> 
                                {foreach from=$shipping.carrier_shipping_rate_providers item=item key=key name=name}
                                    {if $key != 0}|{/if}<span>{$item.weight_low}-{$item.weight_high} : {$item.price}</span> 
                                {/foreach}
                           </p>
                           <p>
                                <b> {__('countries')} -></b> 
                                {foreach from=$shipping.country item=item key=key name=name}
                                    {if $key != 0}|{/if} <span>{$item.name} ({$item.code})</span> 
                                {/foreach}
                           </p>
                        </div>
                    </div>
                </td>
            </tr>
        {/foreach}
    <!--payouts_list--></table>

{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
{* {capture name="adv_buttons"}
	{include file="common/popupbox.tpl"
		act="create"
		text=__("map_shipping_zone")
		title=__("map_shipping_zone")
		id="shopify_shipping_zone"
        icon="icon-plus"
		content=""
	}
{/capture} *}
</form>
{capture name="sidebar"}
{include file="addons/wk_shopify_connector/common/components/tab.tpl"}
{/capture}

{/capture}

{include file="common/mainbox.tpl" title=__("manage_shipping_zone_mapping") content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar buttons=$smarty.capture.buttons}

<div id="content_shipping_map"> 
<form action="{""|fn_url}" method="post" name="shopify_shipping_mapped_form">
{if $shipping_zones}
      <div id="content_general" class="hidden">
        <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr class="cm-first-sibling">
                    <th width="30%">{__("countries")}</th>
                    <th width="30%">{__("location")}({{$currency_data.symbol}})</th>
                    <th width="10%">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$countries key=key item=country}
                <tr>
                    <td>
                        <input >
                    </td>
                    <td>
                        <input type="hidden" name="shipping_entry[{$entry.shipping_template_entry_id}][shipping_template_entry_id]" value="{$entry.shipping_template_entry_id}"/>
                        <select name="shipping_entry[{$entry.shipping_template_entry_id}][destination_country_id]">
                            {if !$entry.destination_country_id}
                            <option value="" selected>{__("every_where_else")}</option>
                            {/if}
                            {foreach from=$destinations item="destination"}
                                <option value="{$destination.destination_id}">{$destination.destination}</option>
                            {/foreach}
                        </select>
                    </td>
                    <td>
                        <input type="text" name="shipping_entry[{$entry.shipping_template_entry_id}][primary_cost]" class="input-medium cm-value-decimal" value="{$entry.primary_cost}"/>
                       
                    </td>
                    
                
                </tr>
                {/foreach}
                
                
            </tbody>
        </table>
        </div>
    </div>

{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

</form>
<!--#content_shipping_map--></div>