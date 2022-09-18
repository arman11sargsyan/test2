{$import_product_href = $import_product_href|default:"exim.import&section=products"}
        {$has_permission_an_import = $has_permission_an_import|default:fn_check_permissions("exim", "import", "admin", "POST")}
        {$allow_create_product = $allow_create_product|default:true}

        {capture name="tools_list_items"}
            {hook name="products:tools_list_items"}
                {if $has_permission_an_import}
                    <li>{btn type="list" text=__("import_products") href="{$import_product_href}"}</li>
                {/if}
            {/hook}
        {/capture}
	
        {*{if $product_add_button_as_dropdown && $smarty.capture.tools_list_items|trim}
            {capture name="dropdown_list"}
                {hook name="products:tools_list_before_items"}{/hook}
                {if $allow_create_product}
                    <li>{btn type="list" text=__("create_new_product") href="products.add"}</li>
                {/if}
                {$smarty.capture.tools_list_items nofilter}
            {/capture}
            {dropdown content=$smarty.capture.dropdown_list icon="icon-plus" no_caret=true placement="right"}
        {else} *}
            {hook name="products:product_add_button"}
                {include file="common/tools.tpl" tool_href="products.add" prefix="top" title=__("add_product") hide_tools=true icon="icon-plus"}
            {/hook}
        {*{/if}*}