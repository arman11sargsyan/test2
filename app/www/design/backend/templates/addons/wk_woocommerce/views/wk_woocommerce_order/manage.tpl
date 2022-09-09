{capture name="mainbox"}
<form action="{""|fn_url}" method="post" enctype="multipart/form-data" class="" name="woocommerce_manage_products_form">
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

        <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=woocommerce_order_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("woocommerce_order_id")}{if $search.sort_by == "woocommerce_order_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

        <th width="20%"><a class="cm-ajax" href="{"`$c_url`&sort_by=woocommerce_order_total&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("woocommerce_order_total")}{if $search.sort_by == "woocommerce_order_total"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

        <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=currency&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("currency")}{if $search.sort_by == "currency"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

        <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=woo_order_status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("status")}{if $search.sort_by == "woo_order_status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

    </tr>
</thead>
<tbody>
{foreach from=$orders item="order_data"}

<tr>
    <td data-th='{__("order_id")}'> 
       <a href="{"orders.details&order_id=`$order_data.order_id`"|fn_url}"> {$order_data.order_id}</a>
    </td>
    <td data-th='{__("woocommerce_order_id")}'>{$order_data.woocommerce_order_id}</td>
    <td class="row-status" data-th='{__("woocommerce_order_total")}'>{$order_data.woocommerce_order_total}</td>
    <td class="row-status" data-th='{__("currency")}'>{$order_data.currency}</td>
    <td class="row-status" data-th='{__("status")}'>{$order_data.woo_order_status}</td>
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

{capture name="sidebar"}
    {if $account_id}
        {include file="addons/wk_woocommerce/common/components/tab.tpl"}
    {/if}
{/capture}

{include file="common/mainbox.tpl" title=__("list_woocommerce_orders") content=$smarty.capture.mainbox title_extra=$smarty.capture.title_extra sidebar=$smarty.capture.sidebar}

