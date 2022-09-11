{capture name="mainbox"}
<form action="{""|fn_url}" method="post" enctype="multipart/form-data" class="form-horizontal form-edit" name="shopify_import_orders_form">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}
<input type="hidden" class="cm-no-hide-input" value="{$account_id}" name="account_id"/>
<input type="hidden" class="cm-no-hide-input" value="{$search.order_ids}" name="order_ids"/>
<input type="hidden" class="cm-no-hide-input" value="{$search.order_status}" name="order_status"/>
<input type="hidden" class="cm-no-hide-input" value="{$search.fullfillment}" name="fullfillment"/>
<input type="hidden" class="cm-no-hide-input" value="{$search.min_created}" name="min_created"/>
<input type="hidden" class="cm-no-hide-input" value="{$search.max_created}" name="max_created"/>

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{assign var="c_icon" value="<i class=\"icon-`$search.sort_order_rev`\"></i>"}
{assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}
{assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}

{if $orders}
    <div class="table-responsive-wrapper">
        <table class="table table-middle sortable table-responsive">
            <thead>
                <tr>
                    <th class="center" width="1%">{include file="common/check_items.tpl"}</th>
                    {* <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("id")}{if $search.sort_by == "id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th> *}
                    <th width="10%">{__("id")}</th>
                    {* <th width="20%"><a class="cm-ajax" href="{"`$c_url`&sort_by=total_price&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("total_price")}{if $search.sort_by == "total_price"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th> *}
                    <th width="20%">{__("total_price")}</th>
                    <th width="10%">{__("financial_status")}</th>
                    <th width="10%">{__("fullfillment_status")}</th>
                    <th width="10%">{__("order_number")}</th>
                    <th width="10%">{__("created_at")}</th>
                    <th width="5%">&nbsp;</th>
                    
                </tr>
            </thead>
            <tbody>
                {foreach from=$orders item="o"}

                <tr class="cm-row-status-{$product_data.status|lower}" id="{$o.id}">
                    <td class="left mobile-hide" width="1%">
                        {if in_array($o.id,$synced_orders)}
                            --
                        {else}
                            <input type="checkbox" name="map_ids[]" value="{$o.id}" class="checkbox cm-item"/>
                        {/if}
                    </td>
                    <td data-th='{__("id")}'> 
                        {$o.id}
                    </td>
                    <td data-th='{__("total_price")}'>{$o.total_price} {$o.currency}</td>
                   
                    <td class="row-status" data-th='{__("financial_status")}'>
                        {if $o.financial_status}{$o.financial_status}{else}--{/if}
                    </td>
                    <td class="row-status" data-th='{__("fullfillment_status")}'>
                        {if $o.fullfillment_status} {$o.fullfillment_status} {else}--{/if}
                    </td>
                    <td class="row-status" data-th='{__("order_number")}'>
                        {$o.order_number}
                    </td>
                    <td class="row-status" data-th='{__("created_at")}'> {$o.created_at|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
 
                    <td class="nowrap" data-th='{__("tools")}'>
                        <div class="hidden-tools">
                            {capture name="tools_list"}
                                <li>
                                    {if in_array($o.id,$synced_orders)}

                                    {else}
                                        <li>{btn type="list"  text= __("order_import") href="wk_shopify.import_order?order_id=`$o.id`&account_id=`$account_id`" method="POST"}</li>
                                    {/if}
                                </li>
                                
                            {/capture}
                            {dropdown content=$smarty.capture.tools_list}
                        </div>
                    </td>                    
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

{capture name="buttons"}
    {capture name="tools_list"}
        {if $orders}
        <li>{btn type="list" dispatch="dispatch[wk_shopify.m_import_orders]" text=__("import_orders") form="shopify_import_orders_form" }</li>
        {/if}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}
{capture name="adv_buttons"}
    {include file="common/popupbox.tpl"
        act="create"
        text=__("import_orders_from_shopify")
        title=__("import_order")
        id="import_order_search"
        icon="icon-plus"
        content=""
    }
{/capture}

</form>
{capture name="sidebar"}
{include file="addons/wk_shopify_connector/common/components/tab.tpl"}
{/capture}
{/capture}

{include file="common/mainbox.tpl" title=__("list_of_shopify_orders") content=$smarty.capture.mainbox title_extra=$smarty.capture.title_extra tools=$smarty.capture.tools buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons}
{include file="addons/wk_shopify_connector/views/wk_shopify/components/import_order_search.tpl"}