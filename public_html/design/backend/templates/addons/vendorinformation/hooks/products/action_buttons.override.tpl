<li class="bulkedit-action--legacy hide">{btn type="list" text=__("clone_selected") dispatch="dispatch[products.m_clone]" form="manage_products_form" }</li>
        <li class="bulkedit-action--legacy hide">{btn type="list" text=__("export_selected") dispatch="dispatch[products.export_range]" form="manage_products_form"}</li>
        <li class="bulkedit-action--legacy hide">{btn type="delete_selected" dispatch="dispatch[products.m_delete]" form="manage_products_form"}</li>
        <li class="divider bulkedit-action--legacy hide"></li>
		{if $auth.user_type != "UserTypes::VENDOR"|enum}
        <li>{btn type="list" text=__("global_update") href="products.global_update"}</li>
		{/if}
		<li>{btn type="list" text=__("bulk_product_addition") href="products.m_add"}</li>
		{if $auth.user_type != "UserTypes::VENDOR"|enum}
			<li>{btn type="list" text=__("product_subscriptions") href="products.p_subscr"}</li>
			{if $products}
				<li>{btn type="list" text=__("export_found_products") href="products.export_found"}</li>
			{/if}
		{/if}