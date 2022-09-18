{if $payout.payout_type = "VendorPayoutTypes::PAYOUT"|enum && $payout.payout_amount < 0}
	<small class="muted">
	{include file="common/price.tpl" value=$payout.display_amount} {if $payout.payout_taxapply =='1' && $auth.user_type === "UserTypes::ADMIN"|enum} {$payout.payout_taxname} {include file="common/price.tpl" value=$payout.payout_totalcommission} Total Paid {include file="common/price.tpl" value=$payout.commission_amount} {/if}
	</small>
	{else}
	{include file="common/price.tpl" value=$payout.display_amount} {if $payout.payout_taxapply =='1' && $auth.user_type === "UserTypes::ADMIN"|enum} {$payout.payout_taxname} {include file="common/price.tpl" value=$payout.payout_totalcommission} Total Paid {include file="common/price.tpl" value=$payout.commission_amount} {/if}
	{if $smarty.request.selected_section ==''}
	<br>
	<small class="muted">
        out of {include file="common/price.tpl" value=$payout.order_amount}
	</small>
	{/if}
{/if}