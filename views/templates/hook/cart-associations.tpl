{foreach $cart_associations as $cart_association}
    <li class="cart-item cart-item-association bg-light">
        <div class="product-line-grid row align-items-center small-gutters">
            <!--  product left body: description -->
            <div class="product-line-grid-body col-12 col-sm-6 col-md-6">
                <div class="row align-items-center small-gutters">
                    <div class="col col-auto product-image">
                        <a href="{$cart_association.url}">
                            {if $cart_association.default_image}
                                <img src="{$cart_association.default_image.bySize.cart_default.url}" alt="{$cart_association.name|escape:'quotes'}"  class="img-fluid" loading="lazy">
                            {else}
                                <img src="{$urls.no_picture_image.bySize.cart_default.url}" class="img-fluid"  loading="lazy" />
                            {/if}
                        </a>
                    </div>
                    <div class="col">
                        <small class="product-advise text-primary">{l s="We recommand" mod="cart_associations"}</small>
                        <div class="product-line-info">
                            <a class="label" href="{$cart_association.url}">{$cart_association.name}</a>
                        </div>
                    </div>
                </div>
            </div>
            <!--  product left body: description -->
            <div class="col-12 col-sm-6 col-md-6 product-line-grid-right product-line-actions">
                <div class="row align-items-center small-gutters justify-content-end">
                    <!--  product unit-->
                    <div class="col col-auto col-md unit-price">
                        {if $cart_association.has_discount}
                            <span class="product-discount">
                        <span class="regular-price">{$cart_association.regular_price}</span>
                            {if $cart_association.discount_type === 'percentage'}
                                <span class="discount discount-percentage mr-1">
                                 -{$cart_association.discount_percentage_absolute}
                                </span>
                            {else}
                            <span class="discount discount-amount mr-1">
                                 -{$cart_association.discount_to_display}
                            </span>
                            {/if}
                    </span>
                        {/if}
                        <span class="value">{$cart_association.price}</span>
                        {if $cart_association.unit_price_full}
                            <div class="unit-price-cart">{$cart_association.unit_price_full}</div>
                        {/if}
                        {hook h='displayProductPriceBlock' product=$cart_association type="unit_price"}
                    </div>
                    <div class="col col-auto col-md qty">
                        {if !$allow_add_variant_to_cart_from_listing && $cart_association.id_product_attribute}
                            <a class="btn btn-product-list" href="{$cart_association.url}" >
                                {l s='View' mod='cartassociations'}
                            </a>
                        {else}
                            <form action="{$urls.pages.cart}" method="post">
                                <input type="hidden" name="id_product" value="{$cart_association.id}">
                                <input type="hidden" name="token" value="{$static_token}">
                                <input type="hidden" name="id_product_attribute" value="{$cart_association.id_product_attribute}">
                                <div class="input-group-add-cart">
                                    <button
                                            class="btn btn-product-list add-to-cart"
                                            data-button-action="add-to-cart"
                                            type="submit"
                                            {if !$cart_association.add_to_cart_url}
                                                disabled
                                            {/if}
                                    >
                                        {l s='Add' mod='cartassociations'}
                                    </button>
                                </div>
                            </form>
                        {/if}
                    </div>
                    <div class="col col-auto col-md price">
                        <i class="remove-association fa fa-times" aria-hidden="true" data-id-product="{$cart_association.id_product}"></i>
                    </div>
                    <div class="col col-auto"></div>
                </div>
            </div>
        </div>
    </li>
{/foreach}