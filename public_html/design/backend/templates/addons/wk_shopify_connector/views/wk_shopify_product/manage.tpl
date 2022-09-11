{capture name="mainbox"}

<div id="content_imported_product_list">
    <form action="{""|fn_url}" method="post" enctype="multipart/form-data" class="" name="shopify_manage_products_form">
    <input type="hidden" class="cm-no-hide-input" name="fake" value="1" />
    {include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id="pagination_imported_product_list" search=$search1}
    <input type="hidden" class="cm-no-hide-input" value="{$account_id}" name="account_id"/>
    {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {assign var="c_icon" value="<i class=\"icon-`$search1.sort_order_rev`\"></i>"}
    {assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}
    {assign var="rev" value="pagination_imported_product_list"|default:"pagination_contents"}

    {if $product_list}
        <div class="table-responsive-wrapper">
            <table class="table table-middle sortable table-responsive">
            <thead>
                <tr>
                    <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=product_id&sort_order=`$search1.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("product_id")}{if $search1.sort_by == "product_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=shopify_product_id&sort_order=`$search1.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("shopify_product_id")}{if $search1.sort_by == "shopify_product_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="20%">{__("product_name")}</th>
                    <th width="10%">{__("price")}</th>
                    <th width="10%">{__("status")}</th>
                </tr>
            </thead>
            <tbody>
            {foreach from=$product_list item="product_data"}

            <tr class="cm-row-status-{$product_data.status|lower}" id="{$product_data.listing_id}">
                <td data-th='{__("product_id")}'> 
                <a href="{"products.update&product_id=`$product_data.product_id`"|fn_url}"> {$product_data.product_id}</a>
                </td>
                <td data-th='{__("shopify_product_id")}'>{$product_data.shopify_product_id}</td>
                <td class="row-status" data-th='{__("product_name")}'> <a href="{"products.update&product_id=`$product_data.product_id`"|fn_url}">{$product_data.product}</a></td>
                <td class="row-status" data-th='{__("price")}'>{include file="common/price.tpl" value=$product_data.price}</td>
                <td class="row-status" data-th='{__("status")}'>{if $product_data.status == 'A'}{__('active')}{elseif  $product_data.status == 'D'}{__('disable')}{elseif  $product_data.status == 'H'}{__('hidden')}{else}--{/if}</td>
            </tr>

            {/foreach}
            </tbody>
            </table>
        </div>
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}

    {include file="common/pagination.tpl" div_id="pagination_imported_product_list"}
    </form>

<!--content_imported_product_list--></div>

    {capture name="buttons"}
        {include file="common/popupbox.tpl"
            act="general"
            text=__("import_products_from_shopify")
            link_text=__("import_product")
            id="product_import"
            content=""
            link_class="btn-primary"
        }
        {dropdown content=$smarty.capture.tools_list}
    {/capture}
    {capture name="sidebar"}
        {include file="addons/wk_shopify_connector/common/components/tab.tpl"}
    {/capture}
{/capture}

{include file="common/mainbox.tpl" title=__("manage_shopify_products") content=$smarty.capture.mainbox title_extra=$smarty.capture.title_extra tools=$smarty.capture.tools buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar}

{include file="addons/wk_shopify_connector/views/wk_shopify_product/collection.tpl"}