document.querySelectorAll('.cart-item-association .remove-association').forEach(function (element) {
    element.addEventListener('click', function (event) {
        event.preventDefault();
        const id_product = element.getAttribute('data-id-product');
        addCookieCartIgnore(id_product);
        element.closest('.cart-item-association').remove();
    });
});

function addCookieCartIgnore(id_product) {
    const v = document.cookie.match('(^|;) ?cart_associations_ignore=([^;]*)(;|$)');
    const value =  v ? v[2] + '|' + id_product : id_product;
    let d = new Date;
    d.setTime(d.getTime() + 24*60*60*1000*7); // Keep for a week
    document.cookie = "cart_associations_ignore=" + value + ";path=/;expires=" + d.toGMTString();
}