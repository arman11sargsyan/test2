{capture name="mainbox"}
<form action="{""|fn_url}" method="post" enctype="multipart/form-data" class="" name="shopify_manage_products_form">
<input type="hidden" class="cm-no-hide-input" name="fake" value="1" />
{include file="common/pagination.tpl" save_current_page=true save_current_url=true}
<input type="hidden" class="cm-no-hide-input" value="{$account_id}" name="account_id"/>
{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{assign var="c_icon" value="<i class=\"icon-`$search.sort_order_rev`\"></i>"}
{assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}

{assign var="return_url" value=$config.current_url|escape:"url"}

{if $orders}
<div class="table-responsive-wrapper">
<table class="table table-middle sortable table-responsive">
<thead>
    <tr>
        <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=order_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("order_id")}{if $search.sort_by == "order_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

        <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=shopify_order_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("shopify_order_id")}{if $search.sort_by == "shopify_order_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

        <th width="20%"><a class="cm-ajax" href="{"`$c_url`&sort_by=shopify_order_total&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("shopify_order_total")}{if $search.sort_by == "shopify_order_total"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

        <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=currency&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("currency")}{if $search.sort_by == "currency"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

        <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=financial_status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("financial_status")}{if $search.sort_by == "financial_status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

    </tr>
</thead>
<tbody>
{foreach from=$orders item="order_data"}

<tr>
    <td data-th='{__("order_id")}'> 
       <a href="{"orders.details&order_id=`$order_data.order_id`"|fn_url}"> {$order_data.order_id}</a>
    </td>
    <td data-th='{__("shopify_order_id")}'>{$order_data.shopify_order_id}</td>
    <td class="row-status" data-th='{__("shopify_order_total")}'>{$shopifyOrders[$order_data.shopify_order_id].total_price}</td>
    <td class="row-status" data-th='{__("currency")}'>{$shopifyOrders[$order_data.shopify_order_id].currency}</td>
    
    <td class="row-status" data-th='{__("financial_status")}'>{$shopifyOrders[$order_data.shopify_order_id].financial_status}</td>
</tr>

{/foreach}
</tbody>
</table>
</div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

</form>
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
{* {capture name="buttons"}
    {capture name="tools_list"}
        <li>{btn type="list" text=__("list_all_orders") href="wk_shopify.list_shopify_orders&account_id=`$account_id`"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture} *}
{capture name="sidebar"}
{include file="addons/wk_shopify_connector/common/components/tab.tpl"}
{/capture}

{include file="common/mainbox.tpl" title=__("s_orders") content=$smarty.capture.mainbox title_extra=$smarty.capture.title_extra sidebar=$smarty.capture.sidebar}


{include file="addons/wk_shopify_connector/views/wk_shopify/components/import_order_search.tpl"}

