<div id="related-product">
	<input type="hidden" value="1" name="cartAssociationsModuleSubmitted" />
	<div
		class="autocomplete-search"
		data-formid="cart_associations_products"
		data-fullname="cart_associations_products"
		data-mappingvalue="id"
		data-mappingname="name"
		data-remoteurl="{$remote_url}"
		data-limit="0"
>
		<div class="search search-with-icon">
			<input type="text" id="cart_associations_products" class="form-control search typeahead cart_associations_products" placeholder="{l s='Search and add a related product' mod='cartassociations'}" autocomplete="off">
		</div>

			<ul id="cart_associations_products-data" class="typeahead-list nostyle col-sm-12 product-list">
			{foreach $cart_associations as $cart_association}
				<li class="media">
					<div class="media-left">
						<img class="media-object image" src="{$cart_association.image}" />
					</div>
					<div class="media-body media-middle">
						<span class="label">{$cart_association.name}{if $cart_association.reference} (ref:{$cart_association.reference}){/if}</span><i class="material-icons delete">clear</i>
					</div>
					<input type="hidden" name="cart_associations_products[data][]" value="{$cart_association.id_product}" />
				</li>
			{/foreach}
		</ul>

		<div class="invisible" id="tplcollection-cart_associations_products">
			<span class="label">%s</span><i class="material-icons delete">clear</i>
		</div>
	</div>
</div>
<script type="text/javascript">
    $('#cart_associations_products').on('focusout', function resetSearchBar() {
        $('#cart_associations_products').typeahead('val', '');
    });
</script>