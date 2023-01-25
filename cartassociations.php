<?php
/**
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2015 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

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
    }
    
    public function install()
    {
        if (file_exists($this->getLocalPath().'sql/install.php')) {
            require_once($this->getLocalPath().'sql/install.php');
        }
        return
            parent::install() &&
            $this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('actionProductUpdate');
    }
    
    public function uninstall()
    {
        if (file_exists($this->getLocalPath() . 'sql/uninstall.php')) {
            require_once($this->getLocalPath() . 'sql/uninstall.php');
        }
        parent::uninstall();
    }

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

    private function getOtherProductsIds($id_product, $active_only = true): array
    {
        $query = new DbQuery();
        $query->select('id_product_2');
        $query->from('cart_association',  'ca');
        if ($active_only) {
            $query->innerJoin('product', 'p', 'p.id_product = ca.id_product_2 AND p.active = 1');
        }
        $query->where('ca.id_product_1 = '.(int)$id_product);
        $query->orderBy('ca.position ASC');

        $products = Db::getInstance()->executeS($query);
        $id_products = array_map('intval', array_column($products, 'id_product_2'));
        return array_unique($id_products);
    }

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
}