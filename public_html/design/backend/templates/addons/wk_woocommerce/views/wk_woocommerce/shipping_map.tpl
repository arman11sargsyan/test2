
{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="wk_woocommerce_shipping_form" class="" enctype="multipart/form-data">
<input type="hidden" name="fake" value="1" />
<input type="hidden" name="account_id" value="{$account_id}" />
<input type="hidden" name="result_id" value="pagination_contents_shipping" />

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

{assign var="rev" value=$smarty.request.content_id|default:"pagination_contents_shipping"}
{assign var="c_icon" value="<i class=\"icon-`$search.sort_order_rev`\"></i>"}
{assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}
{include file="common/pagination.tpl" div_id="pagination_contents_shipping" save_current_page=true save_current_url=true}

{if $wk_woocommerce_shippings}
<div class="table-responsive-wrapper">
    <table class="table table-middle table-responsive">
    <thead>
    <tr>
        {*<th width="1%" class="left mobile-hide">
            {include file="common/check_items.tpl" class="cm-no-hide-input"}</th>*}
        <th width="8%"><a class="cm-ajax" href="{"`$c_url`&sort_by=id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("id")}{if $search.sort_by == "id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
        <th width="8%"><a class="cm-ajax" href="{"`$c_url`&sort_by=woocommerce_shipping_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("woocommerce_shipping_id")}{if $search.sort_by == "woocommerce_shipping_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
        <th width="20%"><a class="cm-ajax" href="{"`$c_url`&sort_by=woocommerce_shipping&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("woocommerce_shipping")}{if $search.sort_by == "woocommerce_shipping"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
        <th width="25%"><a class="cm-ajax" href="{"`$c_url`&sort_by=shipping_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("shipping")}{if $search.sort_by == "shipping_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
        <th width="6%" class="mobile-hide">&nbsp;</th>

    </tr>
    </thead>

    {foreach from=$wk_woocommerce_shippings item=wk_woocommerce_shipping}
    <tr class="cm-row-status-{$wk_woocommerce_shipping.status|lower}">
        {assign var="allow_save" value=$wk_woocommerce_shipping|fn_allow_save_object:"wk_woocommerce"}

        {if $allow_save}
            {assign var="no_hide_input" value="cm-no-hide-input"}
        {else}
            {assign var="no_hide_input" value=""}
        {/if}

        {*<td class="left mobile-hide">
            <input type="checkbox" name="ids[]" value="{$wk_woocommerce_shipping.id}" class="cm-item {$no_hide_input}" /></td>*}
        <td data-th="{__("id")}">
            {$wk_woocommerce_shipping.id}
        </td>
         <td data-th="{__("woocommerce_shipping_id")}">
            {$wk_woocommerce_shipping.woocommerce_shipping_id}
        </td>
        <td data-th="{__("woocommerce_shipping")}">
            {$wk_woocommerce_shipping.woocommerce_shipping}
        </td>
        <td class="" data-th="{__("shipping")}">
            {*{include file="pickers/categories/picker.tpl" radio_input_name="category_map[`$wk_woocommerce_shipping.id`]" input_name="category_map[`$wk_woocommerce_shipping.id`]" item_ids=$wk_woocommerce_shipping.category_id data_id="wk_cateory_map_`$wk_woocommerce_shipping.id`" }*}

            <div class="object-selector">
                <select id="shipping_ids_{$wk_woocommerce_shipping.id}"
                        class="cm-object-selector"
                        name="shipping_map[{$wk_woocommerce_shipping.id}]"
                        single
                        data-ca-placeholder="{__("search")}"
                        data-ca-enable-search="true"
                        data-ca-enable-images="false"
                        data-ca-close-on-select="true"
                        data-ca-load-via-ajax="false"
                        data-ca-allow-clear="true">
                    <option value="">-{__("none")}-</option>
                    {foreach from=$shippings item="shipping"}
                        <option value="{$shipping.shipping_id}" {if $wk_woocommerce_shipping.shipping_id == $shipping.shipping_id}selected="selected" {/if}>{$shipping.shipping}</option>
                    {/foreach}
                </select>
            </div>
            
        </td>
        <td class="mobile-hide">
            {capture name="tools_list"}
                {if $allow_save}
                    <li>{btn type="list" text=__("delete") href="wk_woocommerce.delete_shipping_map?id=`$wk_woocommerce_shipping.id`&account_id=`$account_id`" class="cm-confirm cm-post"}</li>
                {/if}
            {/capture}
            <div class="hidden-tools">
                {dropdown content=$smarty.capture.tools_list}
            </div>
        </td>
    </tr>
    {/foreach}
    </table>
</div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
{include file="common/pagination.tpl" div_id="pagination_contents_shipping"}
{capture name="buttons"}
    {capture name="tools_list"}
        <li>{btn type="list" dispatch="dispatch[wk_woocommerce.get_shippings]" text=__("wk_woocommerce_import_shippings") form="wk_woocommerce_shipping_form" class="cm-submit cm-ajax cm-comet"}</li>
    {/capture}
    {if $wk_woocommerce_shippings}
        {include file="buttons/save.tpl" but_name="dispatch[wk_woocommerce.shipping_map]" but_role="action" but_target_form="wk_woocommerce_shipping_form" but_meta="cm-submit btn-primary"}
    {/if}
    {dropdown content=$smarty.capture.tools_list}

{/capture}
</form>

{capture name="sidebar"}
    {if $account_id}
        {include file="addons/wk_woocommerce/common/components/tab.tpl"}
    {/if}
{/capture}

{/capture}

{include file="common/mainbox.tpl" title=__("wk_woocommerce_shippings") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons select_languages=true sidebar=$smarty.capture.sidebar}
