<?php
$sql = [];
$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'cart_association` (
  `id_product_1` int(10) unsigned NOT NULL,
  `id_product_2` int(10) unsigned NOT NULL,
  `position` int(10) unsigned NOT NULL,
  KEY `cart_association` (`id_product_1`,`id_product_2`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
