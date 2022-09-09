{capture name="mainbox"}
<form action="{""|fn_url}" method="post" enctype="multipart/form-data" class="form-horizontal form-edit" name="woocommerce_import_orders_form">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}
<input type="hidden" class="cm-no-hide-input" value="{$account_id}" name="account_id"/>

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
                    <th width="10%">{__("id")}</th>
                    <th width="20%">{__("total_price")}</th>
                    <th width="10%">{__("status")}</th>
                    <th width="10%">{__("order_key")}</th>
                    <th width="10%">{__("created_at")}</th>
                    <th width="5%">&nbsp;</th>
                    
                </tr>
            </thead>
            <tbody>
                {foreach from=$orders item="o"}

                <tr class="cm-row-status-{$product_data.status|lower}" id="{$o.id}">
                    <td class="left mobile-hide" width="1%">
                        <input type="checkbox" name="order_ids[]" value="{$o.id}" class="checkbox cm-item"/>
                    </td>
                    <td data-th='{__("id")}'> 
                        {$o.id}
                    </td>
                    <td data-th='{__("total_price")}'>{$o.total} {$o.currency}</td>
                   
                    <td class="row-status" data-th='{__("status")}'>
                        {if $o.status}{$o.status}{else}--{/if}
                    </td>
                    <td class="row-status" data-th='{__("order_key")}'>
                        {$o.order_key}
                    </td>
                    <td class="row-status" data-th='{__("created_at")}'> {$o.date_created|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
 
                    <td class="nowrap" data-th='{__("tools")}'>
                        <div class="hidden-tools">
                            {capture name="tools_list"}
                                <li>
                                    <li>{btn type="list"  text= __("order_import") href="wk_woocommerce_order.import_orders?order_ids=`$o.id`&account_id=`$account_id`" method="POST" class="cm-submit cm-ajax cm-comet"}</li>
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
        <li>{btn type="list" dispatch="dispatch[wk_woocommerce_order.import_orders]" text=__("import_orders") form="woocommerce_import_orders_form" class="cm-ajax cm-comet"}</li>
        {/if}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}
{* {capture name="adv_buttons"}
    {include file="common/popupbox.tpl"
        act="create"
        text=__("import_orders_from_shopify")
        title=__("import_order")
        id="import_order_search"
        icon="icon-plus"
        content=""
    }
{/capture} *}

</form>

{capture name="sidebar"}
    {if $account_id}
        {include file="addons/wk_woocommerce/common/components/tab.tpl"}
    {/if}
{/capture}

{/capture}

{include file="common/mainbox.tpl" title=__("list_of_woocommerce_orders") content=$smarty.capture.mainbox title_extra=$smarty.capture.title_extra tools=$smarty.capture.tools buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons}
{* {include file="addons/wk_woocommerce/views/wk_woocommerce_order/component/import_order_search.tpl"} *}