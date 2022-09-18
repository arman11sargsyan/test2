<li><a href="{"products.manage?company_id=`$id`"|fn_url}">{__("view_vendor_products")}</a></li>
        {if "ULTIMATE"|fn_allowed_for && $runtime.company_id}
            <li><a href="{"categories.manage?company_id=`$id`"|fn_url}">{__("view_vendor_categories")}</a></li>
        {/if}
        {if "MULTIVENDOR"|fn_allowed_for}
		    {if $auth.user_type === "UserTypes::ADMIN"|enum }
            <li><a href="{"profiles.manage?user_type={"UserTypes::VENDOR"|enum}&company_id=`$id`"|fn_url}">{__("view_vendor_admins")}</a></li>
			{/if}
        {else}
            <li><a href="{"profiles.manage?company_id=`$id`"|fn_url}">{__("view_vendor_users")}</a></li>
        {/if}
        <li><a href="{"orders.manage?company_id=`$id`"|fn_url}">{__("view_vendor_orders")}</a></li>