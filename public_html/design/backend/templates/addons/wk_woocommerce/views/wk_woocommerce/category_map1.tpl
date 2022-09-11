{capture name="mainbox"}
<style>
    .category_container{
        width: 45%;
        padding: 5px;
    }
    .inner_category_container{
        margin:5px;
    }
    #categories {
        margin-right:5%;
    }
    #content_woocommerce_categories_map label input{
        vertical-align:top;
        margin-right:5px;
    }
    .select_box_container{
        display:block;
      
    }
</style>

<form action="{""|fn_url}" method="post" enctype="multipart/form-data" name="woocommerce_category_mapped_form">
{if $categories_mapped}
    {include file="common/pagination.tpl" save_current_url=true}
    {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {assign var="c_icon" value="<i class=\"icon-`$search.sort_order_rev`\"></i>"}
    {assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}
    {assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}
    <input type="hidden" name="id"  value="{$merchant_data.id}" />
    <div class="table-responsive-wrapper">
        <table class="table table-middle sortable table-responsive">
        <thead>
            <tr>
                {*<th class="left" width="3%">{include file="common/check_items.tpl"}</th>*}
                <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=category_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("cscart_category_id")}{if $search.sort_by == "category_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=category&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("cscart_category")}{if $search.sort_by == "category"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=woocommerce_category_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("woocommerce_category_id")}{if $search.sort_by == "woocommerce_category_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=woocommerce_category&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("woocommerce_category")}{if $search.sort_by == "woocommerce_category"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</th>
                <th width="5%">&nbsp;</th>
            </tr>
        </thead>
        <tbody>

        {foreach from=$categories_mapped item="category_map"}
        <tr class="cm-row-status-{$category_map.status|lower}">
            <td class="row-status" data-th='{__("cscart_category_id")}'><a href="{"categories.update?category_id=`$category_map.category_id`"|fn_url}">{$category_map.category_id}</a></td>
            <td class="row-status" data-th='{__("cscart_category")}'><a href="{"categories.update?category_id=`$category_map.category_id`"|fn_url}">{$category_map.category_id|fn_get_category_name}</a></td>
            <td class="row-status" data-th='{__("woocommerce_category_id")}'>{$category_map.woocommerce_category_id}</td>
            <td class="row-status" data-th='{__("woocommerce_category")}'>{$category_map.woocommerce_category}</td>
            <td class="nowrap" data-th='{__("tools")}'>
                <div class="hidden-tools">
                    {capture name="tools_list"}
                        <li>{btn type="list" class="cm-confirm" text=__("delete") href="wk_woocommerce.delete_category_map?id=`$category_map.id`&account_id=`$category_map.account_id`" method="POST"}</li>
                    {/capture}
                    {dropdown content=$smarty.capture.tools_list}
                </div>
            </td>
        </tr>
        {/foreach}
        </tbody>
        </table>
    </div>
    {include file="common/pagination.tpl"}
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{capture name="adv_buttons"}
	{include file="common/popupbox.tpl"
		act="create"
		text=__("map_new_category")
		title=__("map_new_category")
		id="woocommerce_categories_map"
        icon="icon-plus"
		content=""
	}
{/capture}

</form>

{capture name="sidebar"}
{*{include file="addons/wk_woocommerce_connector/common/components/tab.tpl"}*}
{/capture}

<div class="hidden" title="{__('map_new_category')}" id="content_woocommerce_categories_map">
    <form action="{""|fn_url}" method="post" name="map_woocommerce_category_form" class="form-horizontal form-edit cm-disable-empty-files" enctype="multipart/form-data">
            <input type="hidden" name="account_id" value="{$id}"> 
            <div class="cscart_categories pull-left category_container" >
                <div class="select_box_container">
                    {math equation="rand()" assign="rnd"}
                    <label for="ccategories_{$rnd}_ids" class="control-label cm-required">{__("cscart_categories")}</label>
                        {include file="pickers/categories/picker.tpl"
                            company_id=$runtime.company_id|default:$merchant_data.company_id
                            rnd=$rnd
                            data_id="categories"
                            input_name="cs_cart_category"
                            hide_link=true
                            hide_delete_button=true
                            display_input_id="category_ids"
                            disable_no_item_text=true
                            but_meta="btn"
                            show_active_path=true
                        }
                </div>
            </div>
            <div class="woocommerce_categories pull-right category_container">
                <div class="control-group">
                    <label class="control-label" for="elm_woocommerce_categories">{__("woocommerce_categories")}:</label>
                    <div class="controls">
                        <select name="woocommerce_category" id="elm_woocommerce_categories">
                            {foreach from=$woocommerce_collection item="category_map"}
                                <option value="{$category_map->id}">{$category_map->name}</option>
                            {/foreach}
                        </select>
                        <input type="hidden" name="woocommerce_category_name" id="woocommerce_category_name" value="{$woocommerce_collection.0->name}">
                    </div>
                </div>
            </div>

            <div class="buttons-container buttons-container-picker">
                {include file="buttons/button.tpl"  but_text=__("close") but_role="close" but_target_form="map_woocommerce_category_form"  but_meta="btn cm-dialog-closer"}

                {include file="buttons/button.tpl" but_role="submit" but_name="dispatch[wk_woocommerce.category_map]" but_target_form="map_woocommerce_category_form" but_text=__("wk_map") but_meta="btn btn-primary cm-submit"}
            </div>
    </form>
</div>

<script type="text/javascript">
    (function(_,$){
        $('#elm_woocommerce_categories').on('change',function() {
            var colName = $(".woocommerce_categories option:selected").text();
            $('#woocommerce_category_name').val(colName);
        });
    }(Tygh,Tygh.$));
</script>

{/capture}
{include file="common/mainbox.tpl" title=__("manage_category_mapping") content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons}

