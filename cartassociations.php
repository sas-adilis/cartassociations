<?php
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CartAssociations extends Module
{
    public function __construct()
    {
        $this->name = 'cartassociations';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Adilis';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Cart associations');
        $this->description = $this->l('Add associations to cart');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall, all datas will be lost?');

        if (!$this->isHookInstalledInTemplateFile()) {
            $this->warning = $this->l('Please install hook in cart-detailed.tpl');
        }
    }
    
    public function install()
    {
        if (file_exists($this->getLocalPath().'sql/install.php')) {
            require_once($this->getLocalPath().'sql/install.php');
        }
        return
            parent::install() &&
            $this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('displayCartAssociations') &&
            $this->registerHook('displayHeader');
    }
    
    public function uninstall()
    {
        if (file_exists($this->getLocalPath() . 'sql/uninstall.php')) {
            require_once($this->getLocalPath() . 'sql/uninstall.php');
        }
        return parent::uninstall();
    }

    /**
     * @param $params
     * @return void|string
     */
    public function hookDisplayAdminProductsExtra($params) {
        $id_product = (int)$params['id_product'];
        if (!$id_product) {
            return;
        }


        $query = new DbQuery();
        $query->select('p.`id_product`, p.`reference`, pl.`name`, pl.link_rewrite');
        $query->from('cart_association',  'ca');
        $query->innerJoin('product', 'p', 'p.id_product = ca.id_product_2');
        $query->join(Shop::addSqlAssociation('product', 'p'));
        $query->leftJoin('product_lang', 'pl', '
            p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.(int)$this->context->cookie->id_lang.Shop::addSqlRestrictionOnLang('pl')
        );
        $query->where('ca.id_product_1 = '.$id_product);
        $query->orderBy('ca.position ASC');

        $cart_associations = Db::getInstance()->executeS($query);
        foreach ($cart_associations as $key => $association) {
            $image = Image::getCover($association['id_product']);
            $cart_associations[$key]['image'] = $this->context->link->getImageLink($association['link_rewrite'], $image['id_image'], 'small_default');
        }

        $remote_url  = 'index.php?controller=AdminProducts&ajax=1';
        $remote_url .= '&action=productsList&forceJson=1&disableCombination=1';
        $remote_url .= '&exclude_packs=0&forceJson=1&excludeVirtuals=0';
        $remote_url .= '&limit=20&q=%QUERY';
        $remote_url .= '&token='.Tools::getAdminTokenLite('AdminProducts');

        $this->context->smarty->assign(array(
            'cart_associations' => $cart_associations,
            'remote_url' => $remote_url
        ));

        return $this->display(__FILE__, 'admin-product-tab.tpl');
    }

    /**
     * @param $params
     * @return void
     */
    public function hookActionProductUpdate($params)
    {
        $id_product = $params['id_product'];
        if ($id_product && Tools::getIsset('cartAssociationsModuleSubmitted')) {
            $datas = Tools::getValue('cart_associations_products');
            $cart_associations = $datas['data'] ?? [];
            $cart_associations = array_map('intval', $cart_associations);
            $cart_associations = array_unique($cart_associations);

            Db::getInstance()->delete('cart_association', 'id_product_1 = '.(int)$id_product);
            $position = 1; $datas_to_insert = [];
            foreach ($cart_associations as $cart_association) {
                $datas_to_insert[] = array(
                    'id_product_1' => (int)$id_product,
                    'id_product_2' => (int)$cart_association,
                    'position' => $position++
                );
            }
            Db::getInstance()->insert('cart_association', $datas_to_insert, false, true, Db::REPLACE);
        }
    }

    private function isHookInstalledInTemplateFile(): bool
    {
        $file = _PS_THEME_DIR_.'templates/checkout/_partials/cart-detailed.tpl';
        if (!file_exists($file)) {
            $file = _PS_PARENT_THEME_DIR_.'templates/checkout/_partials/cart-detailed.tpl';
        }

        if (!file_exists($file)) {
            return false;
        }

        $content = file_get_contents($file);
        return $content && strpos($content, 'displayCartAssociations') !== false;
    }

    public function hookDisplayCartAssociations($params) {
        $cache_id = 'cart_associations|'.$this->context->cart->id;
        if (!Cache::retrieve($cache_id)) {
            $products_in_cart = $cart_associations = [];
            foreach ($this->context->cart->getProducts() as $product_in_cart) {
                $products_in_cart[] = $product_in_cart['id_product'];
            }

            $now = date('Y-m-d') . ' 00:00:00';
            $nb_days_new_product = (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT');
            $id_lang = $this->context->cookie->id_lang;
            $id_shop = $this->context->shop->id;

            $sql = new DbQuery();
            $sql->select('
                ca.id_product_1, p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity,
                pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
                pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
                image_shop.`id_image` id_image, il.`legend`, m.`name` AS manufacturer_name,
                (DATEDIFF(product_shop.`date_add`,
                    DATE_SUB(
                        "' . $now . '",
                        INTERVAL ' . $nb_days_new_product . ' DAY
                    )
                ) > 0) as new'
            );
            $sql->from('cart_association',  'ca');
            $sql->innerJoin('product', 'p', 'p.id_product = ca.id_product_2');
            $sql->join(Shop::addSqlAssociation('product', 'p'));
            $sql->leftJoin(
                'product_lang',
                'pl',
                'p.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . (int)$id_lang . Shop::addSqlRestrictionOnLang('pl')
            );
            $sql->leftJoin('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int)$id_shop);
            $sql->leftJoin('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int)$id_lang);
            $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');
            $sql->where('ca.id_product_1 IN ('.implode(',', $products_in_cart).')');
            $sql->where('ca.id_product_2 NOT IN ('.implode(',', $products_in_cart).')');
            $sql->where('product_shop.`active` = 1');
            $sql->where('product_shop.`visibility` IN ("both", "catalog")');

            if (Group::isFeatureActive()) {
                $groups = FrontController::getCurrentCustomerGroups();
                $sql->where('
                    EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
                    JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::getCurrent()->id) . ')
                    WHERE cp.`id_product` = p.`id_product`)'
                );
            }

            if (Combination::isFeatureActive()) {
                $sql->select('product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute');
                $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', 'p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int)$id_shop);
            }
            $sql->join(Product::sqlStock('p', 0));
            $sql->orderBy('ca.position ASC');

            $products = Db::getInstance()->executeS($sql);

            if (count($products)) {
                $presenterFactory = new ProductPresenterFactory(Context::getContext());
                $presentationSettings = $presenterFactory->getPresentationSettings();

                $presenter = new ProductListingPresenter(
                    new ImageRetriever(
                        $this->context->link
                    ),
                    $this->context->link,
                    new PriceFormatter(),
                    new ProductColorsRetriever(),
                    $this->context->getTranslator()
                );

                foreach ($products as $product) {
                    if (!isset($cart_associations[$product['id_product_1']])) {
                        $cart_associations[$product['id_product_1']] = [];
                    }
                    $cart_associations[$product['id_product_1']][] = $presenter->present(
                        $presentationSettings,
                        Product::getProductProperties($id_lang, $product, $this->context),
                        $this->context->language
                    );
                }
            }
            Cache::store($cache_id, $cart_associations);
        }

        /** @var Product $product */
        $product = $params['product'];

        $cart_associations = Cache::retrieve($cache_id);
        if (isset($cart_associations[$product->id])) {
            $this->context->smarty->assign(array(
                'cart_associations' => $cart_associations[$product->id]
            ));
            return $this->display($this->getLocalPath(), '/views/templates/hook/cart-associations.tpl');
        }
    }

    public function hookDisplayHeader() {
        $this->context->controller->registerStylesheet('modules-cartassociations', 'modules/'.$this->name.'/views/css/cartassociations.css', ['media' => 'all', 'priority' => 150]);
    }
}