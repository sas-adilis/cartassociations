<?php
$sql = [];
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'cart_association`';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
