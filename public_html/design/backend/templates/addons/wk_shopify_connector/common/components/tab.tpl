<div class="sidebar-row" id="views">
    <ul class="nav nav-list saved-search">
        <li {if $runtime.controller == 'wk_shopify' && $runtime.mode == 'manage'}class="active"{/if}>
            <a href="{"wk_shopify.manage"|fn_url}">{__("manage_shopify_accounts")}</a>
        </li>
        <li {if $runtime.controller == 'wk_shopify_product' && $runtime.mode == 'manage'}class="active"{/if}>
            <a href="{"wk_shopify_product.manage&account_id=`$account_id`"|fn_url}">{__("manage_shopify_products")}</a>
        </li>
        <li {if $runtime.controller == 'wk_shopify_order'}class="active"{/if}>
            <a href="{"wk_shopify.order_manage&account_id=`$account_id`"|fn_url}">{__("manage_shopify_orders")}</a>
        </li>
        <li {if $runtime.controller == 'wk_shopify_order'}class="active"{/if}>
            <a href="{"wk_shopify.list_shopify_orders&account_id=`$account_id`"|fn_url}">{__("list_all_orders")}</a>
        </li>
        <li {if $runtime.controller == 'wk_shopify_shipping' && $runtime.mode == 'manage'}class="active"{/if}>
            <a href="{"wk_shopify.shipping_manage&account_id=`$account_id`"|fn_url}">{__("manage_shopify_shipping_templates")}</a>
        </li>
        <li {if $runtime.controller == 'wk_shopify' && $runtime.mode == 'category_map'}class="active"{/if}>
            <a href="{"wk_shopify.category_map&account_id=`$account_id`"|fn_url}">{__("manage_category_mapping")}</a>
        </li>
    </ul>
</div>  
<hr> 