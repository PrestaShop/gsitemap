<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
class GsitemapCronModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        // Check correct token when not in CLI context
        if (!Tools::isPHPCLI() && Tools::substr(Tools::hash('gsitemap/cron'), 0, 10) != Tools::getValue('token')) {
            exit('Bad token');
        }

        // Check if the requested shop exists
        $shops = Db::getInstance()->ExecuteS('SELECT id_shop FROM `' . _DB_PREFIX_ . 'shop`');
        $list_id_shop = [];
        foreach ($shops as $shop) {
            $list_id_shop[] = (int) $shop['id_shop'];
        }

        $id_shop = (Tools::getIsset('id_shop') && in_array(Tools::getValue('id_shop'), $list_id_shop)) ? (int) Tools::getValue('id_shop') : (int) Configuration::get('PS_SHOP_DEFAULT');

        // Mark a flag that we are in cron context
        $this->module->cron = true;

        // If this is the first request to generate, we delete all previous sitemaps
        if (!Tools::getIsset('continue')) {
            $this->module->emptySitemap((int) $id_shop);
        }

        // Run generation
        $this->module->createSitemap((int) $id_shop);
    }
}
