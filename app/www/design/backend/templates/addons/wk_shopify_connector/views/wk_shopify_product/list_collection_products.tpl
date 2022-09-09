{capture name="mainbox"}
<form action="{"wk_shopify_product.m_map_sync"|fn_url}" method="post" enctype="multipart/form-data" class="form-horizontal form-edit cm-ajax cm-ajax-full-render cm-comet" name="shopify_collection_products_form">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}
<input type="hidden" class="cm-no-hide-input" value="{$account_id}" name="account_id"/>
<input type="hidden" class="cm-no-hide-input" value="{$collection_id}" name="collection_id"/>
{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{assign var="c_icon" value="<i class=\"icon-`$search.sort_order_rev`\"></i>"}
{assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}
{assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}
{if $product_arr}
    <div class="table-responsive-wrapper">
        <table class="table table-middle sortable table-responsive">
            <thead>
                <tr>
                    <th class="center" width="1%">{include file="common/check_items.tpl"}</th>
                    <th width="10%">{__("product_id")}</th>
                    <th width="10%">{__("collection_id")}</th>
                    <th width="20%">{__("title")}</th>
                    <th width="10%">{__("store_productId")}</th>
                    <th width="20%">{__("created_at")}</th>
                    <th width="10%">{__("updated_at")}</th>
                    <th width="5%">&nbsp;</th>
                    
                </tr>
            </thead>
            <tbody>
                {foreach from=$product_arr item="product_list" key="collection_id"}
                    {foreach from=$product_list item="product_data"}
                    {assign var="pid" value=$product_data.id}

                    <tr class="cm-row-status-{$product_data.status|lower}" id="{$product_data.id}">
                        <td class="left mobile-hide" width="1%">
                        {if $cscartProductIds[{$pid}]}
                            --
                        {else}
                            <input type="checkbox" name="map_ids[{$collection_id}][]" value="{$product_data.id}" class="checkbox cm-item"/>
                        {/if}
                        </td>
                        <td data-th='{__("product_id")}'> 
                            {$product_data.id}
                        </td>
                        <td data-th='{__("collection_id")}'>{$collection_id}</td>
                        <td data-th='{__("title")}'>{$product_data.title}</td>
                        <td data-th='{__("store_productId")}'>{if $cscartProductIds[{$pid}]} <a href="{"products.update&product_id={$cscartProductIds[{$pid}]}"|fn_url}">{$cscartProductIds[{$pid}]}</a>{else}--{/if}</td>
                    
                        <td class="row-status" data-th='{__("created_at")}'>
                        {* {$product_data.created_at} *}
                        {$product_data.created_at|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                        </td>
                        <td class="row-status" data-th='{__("updated_at")}'> 
                        {* {$product_data.updated_at} *}
                        {$product_data.updated_at|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                        </td>
    
                        <td class="nowrap"> {*  data-th='{__("tools")}' removed *}
                            {if !$cscartProductIds[{$pid}]}
                                {include
                                    file="buttons/button.tpl"
                                    but_text=__("product_mapping")
                                    but_meta="cm-post"
                                    but_role="action"
                                    but_href="wk_shopify_product.map_sync?product_id=`$product_data.id`&account_id=`$account_id`&collection_id={$collection_id}"
                                }
                            {/if}
                            {* <div class="hidden-tools">
                                {capture name="tools_list"}
                                    <li>
                                    {if !$cscartProductIds[{$pid}]}
                                    
                                        <li>{btn type="list"  text= __("product_mapping") href="wk_shopify_product.map_sync?product_id=`$product_data.id`&account_id=`$account_id`&collection_id=`$product_data.collection_id`" method="POST"}</li>
                                    </li>
                                    {/if}
                                {/capture}
                                {dropdown content=$smarty.capture.tools_list}
                            </div> *}
                        </td>
                    </tr>
                    {/foreach}
                {/foreach}
            </tbody>
        </table>
    </div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

{capture name="buttons"}
        {if $product_arr}
            
            {include
                file="buttons/button.tpl"
                but_text=__("product_mapping")
                but_meta="cm-comet cm-process-items"
                but_role="submit-link"
                but_target_form="shopify_collection_products_form"
            }
        {/if}
{* 
    {capture name="tools_list"}
        {if $product_arr}
        <li>{btn type="list" dispatch="dispatch[wk_shopify_product.m_map_sync]" text=__("sync_product") form="shopify_collection_products_form" but_meta="cm-comet"}</li>
        {/if}
    {/capture}
    {dropdown content=$smarty.capture.tools_list} *}
{/capture}

</form>

{/capture}

{include file="common/mainbox.tpl" title=__("list_of_shopify_products") content=$smarty.capture.mainbox title_extra=$smarty.capture.title_extra tools=$smarty.capture.tools buttons=$smarty.capture.buttons}