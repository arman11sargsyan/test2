<div class="sidebar-row" id="views">
    <ul class="nav nav-list saved-search">
        <li {if $runtime.controller == 'wk_woocommerce' && $runtime.mode == 'manage'}class="active"{/if}>
            <a href="{"wk_woocommerce.manage"|fn_url}">{__("manage_accounts")}</a>
        </li>
        <li {if $runtime.controller == 'wk_woocommerce_product' && $runtime.mode == 'manage'}class="active"{/if}>
            <a href="{"wk_woocommerce_product.manage&account_id=`$account_id`"|fn_url}">{__("manage_woocommerce_products")}</a>
        </li>
        <li {if $runtime.controller == 'wk_woocommerce_order' && $runtime.mode == 'manage'}class="active"{/if}>
            <a href="{"wk_woocommerce_order.manage&account_id=`$account_id`"|fn_url}">{__("manage_woocommerce_orders")}</a>
        </li>
        <li {if $runtime.controller == 'wk_woocommerce_order' && $runtime.mode == 'list_orders'}class="active"{/if}>
            <a href="{"wk_woocommerce_order.list_orders&account_id=`$account_id`"|fn_url}">{__("list_woocommerce_orders")}</a>
        </li>
        <li {if $runtime.controller == 'wk_woocommerce' && $runtime.mode == 'shipping_map'}class="active"{/if}>
            <a href="{"wk_woocommerce.shipping_map&account_id=`$account_id`"|fn_url}">{__("manage_shippings_mapping")}</a>
        </li>
        <li {if $runtime.controller == 'wk_woocommerce' && $runtime.mode == 'payment_map'}class="active"{/if}>
            <a href="{"wk_woocommerce.payment_map&account_id=`$account_id`"|fn_url}">{__("manage_payment_mapping")}</a>
        </li>
        <li {if $runtime.controller == 'wk_woocommerce' && $runtime.mode == 'category_map'}class="active"{/if}>
            <a href="{"wk_woocommerce.category_map&account_id=`$account_id`"|fn_url}">{__("manage_category_mapping")}</a>
        </li>
        {* <li {if $runtime.controller == 'wk_woocommerce' && $runtime.mode == 'attribute_map'}class="active"{/if}>
            <a href="{"wk_woocommerce.attribute_map&account_id=`$account_id`"|fn_url}">{__("manage_feature_mapping")}</a>
        </li> *}
    </ul>
</div>  
<hr> 