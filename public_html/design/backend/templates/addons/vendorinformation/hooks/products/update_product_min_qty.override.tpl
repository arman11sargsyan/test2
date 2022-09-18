{component name="configurable_page.field" entity="products" tab="detailed" section="pricing_inventory" field="min_qty"}
    <div class="control-group">
        <label class="control-label" for="elm_min_qty">{__("min_order_qty")}:</label>
        <div class="controls">
            <input type="text" name="product_data[min_qty]" value="{$product_data.min_qty}" class="cm-numeric" data-m-dec="0">
        </div>
    </div>
{/component}