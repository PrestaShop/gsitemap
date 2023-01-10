<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Gsitemap extends Module
{
    const HOOK_ADD_URLS = 'gSitemapAppendUrls';

    /**
     * @var bool
     */
    public $cron = false;

    /**
     * @var array
     */
    protected $sql_checks = [];

    /**
     * @var array<int, string>
     */
    protected $type_array = [];

    public function __construct()
    {
        $this->name = 'gsitemap';
        $this->tab = 'checkout';
        $this->version = '4.2.1';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->trans('Google sitemap', [], 'Modules.Gsitemap.Admin');
        $this->description = $this->trans('Generate your Google sitemap file with this module, and keep it up-to-date.', [], 'Modules.Gsitemap.Admin');
        $this->ps_versions_compliancy = [
            'min' => '1.7.1.0',
            'max' => _PS_VERSION_,
        ];
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall this module?', [], 'Admin.Notifications.Warning');
        $this->type_array = [
            'home',
            'meta',
            'product',
            'category',
            'cms',
            'module',
        ];

        $metas = Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'meta` ORDER BY `id_meta` ASC');
        $disabled_metas = explode(',', Configuration::get('GSITEMAP_DISABLE_LINKS'));
        foreach ($metas as $meta) {
            if (in_array($meta['id_meta'], $disabled_metas)) {
                if (($key = array_search($meta['page'], $this->type_array)) !== false) {
                    unset($this->type_array[$key]);
                }
            }
        }
    }

    /**
     * Google sitemap installation process:
     *
     * Step 1 - Pre-set Configuration option values
     * Step 2 - Install the Addon and create a database table to store sitemap files name by shop
     *
     * @return bool Installation result
     */
    public function install()
    {
        foreach ([
            'GSITEMAP_PRIORITY_HOME' => 1.0,
            'GSITEMAP_PRIORITY_PRODUCT' => 0.9,
            'GSITEMAP_PRIORITY_CATEGORY' => 0.8,
            'GSITEMAP_PRIORITY_CMS' => 0.7,
            'GSITEMAP_FREQUENCY' => 'weekly',
            'GSITEMAP_CHECK_IMAGE_FILE' => false,
            'GSITEMAP_LAST_EXPORT' => false,
        ] as $key => $val) {
            if (!Configuration::updateValue($key, $val)) {
                return false;
            }
        }

        return parent::install()
            && Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'gsitemap_sitemap` (`link` varchar(255) DEFAULT NULL, `id_shop` int(11) DEFAULT 0) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;') && $this->installHook();
    }

    /**
     * Registers hook(s)
     *
     * @return bool
     */
    protected function installHook()
    {
        $hook = new Hook();
        $hook->name = self::HOOK_ADD_URLS;
        $hook->title = 'GSitemap Append URLs';
        $hook->description = 'This hook allows a module to add URLs to a generated sitemap';
        $hook->position = true;

        return $hook->save();
    }

    /**
     * Google sitemap uninstallation process:
     *
     * Step 1 - Remove Configuration option values from database
     * Step 2 - Remove the database containing the generated sitemap files names
     * Step 3 - Uninstallation of the Addon itself
     *
     * @return bool Uninstallation result
     */
    public function uninstall()
    {
        foreach ([
            'GSITEMAP_PRIORITY_HOME' => '',
            'GSITEMAP_PRIORITY_PRODUCT' => '',
            'GSITEMAP_PRIORITY_CATEGORY' => '',
            'GSITEMAP_PRIORITY_CMS' => '',
            'GSITEMAP_FREQUENCY' => '',
            'GSITEMAP_CHECK_IMAGE_FILE' => '',
            'GSITEMAP_LAST_EXPORT' => '',
        ] as $key => $val) {
            if (!Configuration::deleteByName($key)) {
                return false;
            }
        }

        $hook = new Hook(Hook::getIdByName(self::HOOK_ADD_URLS));
        if (Validate::isLoadedObject($hook)) {
            $hook->delete();
        }

        return parent::uninstall() && $this->removeSitemap();
    }

    /**
     * Delete all the generated sitemap files  and drop the addon table.
     *
     * @return bool
     */
    public function removeSitemap()
    {
        $links = Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'gsitemap_sitemap`');
        if ($links) {
            foreach ($links as $link) {
                if (!@unlink($this->normalizeDirectory(_PS_ROOT_DIR_) . $link['link'])) {
                    return false;
                }
            }
        }
        if (!Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'gsitemap_sitemap`')) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
        /* Store the posted parameters and generate a new Google sitemap files for the current Shop */
        if (Tools::isSubmit('SubmitGsitemap')) {
            Configuration::updateValue('GSITEMAP_FREQUENCY', pSQL(Tools::getValue('gsitemap_frequency')));
            Configuration::updateValue('GSITEMAP_INDEX_CHECK', '');
            Configuration::updateValue('GSITEMAP_CHECK_IMAGE_FILE', pSQL(Tools::getValue('gsitemap_check_image_file')));
            $meta = '';
            if (Tools::getValue('gsitemap_meta')) {
                $meta .= implode(', ', Tools::getValue('gsitemap_meta'));
            }
            Configuration::updateValue('GSITEMAP_DISABLE_LINKS', $meta);
            $this->emptySitemap();
            $this->createSitemap();

        /* If no posted form and the variable [continue] is found in the HTTP request variable keep creating sitemap */
        } elseif (Tools::getValue('continue')) {
            $this->createSitemap();
        }

        /* Empty the Shop domain cache */
        if (method_exists('ShopUrl', 'resetMainDomainCache')) {
            ShopUrl::resetMainDomainCache();
        }

        /* Get Meta pages and remove index page it's managed elsewhere (@see $this->getHomeLink()) */
        $store_metas = array_filter(Meta::getMetasByIdLang((int) $this->context->cookie->id_lang), function ($meta) {
            return $meta['page'] != 'index';
        });
        $store_url = $this->context->link->getBaseLink();
        $this->context->smarty->assign([
            'gsitemap_form' => './index.php?tab=AdminModules&configure=gsitemap&token=' . Tools::getAdminTokenLite('AdminModules') . '&tab_module=' . $this->tab . '&module_name=gsitemap',
            'gsitemap_cron' => $store_url . 'modules/gsitemap/gsitemap-cron.php?token=' . Tools::substr(Tools::encrypt('gsitemap/cron'), 0, 10) . '&id_shop=' . $this->context->shop->id,
            'gsitemap_feed_exists' => file_exists($this->normalizeDirectory(_PS_ROOT_DIR_) . 'index_sitemap.xml'),
            'gsitemap_last_export' => Configuration::get('GSITEMAP_LAST_EXPORT'),
            'gsitemap_frequency' => Configuration::get('GSITEMAP_FREQUENCY'),
            'gsitemap_store_url' => $store_url,
            'gsitemap_links' => Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'gsitemap_sitemap` WHERE id_shop = ' . (int) $this->context->shop->id),
            'store_metas' => $store_metas,
            'gsitemap_disable_metas' => explode(',', Configuration::get('GSITEMAP_DISABLE_LINKS')),
            'gsitemap_customer_limit' => [
                'max_exec_time' => (int) ini_get('max_execution_time'),
                'memory_limit' => (int) ini_get('memory_limit'),
            ],
            'prestashop_ssl' => Configuration::get('PS_SSL_ENABLED'),
            'gsitemap_check_image_file' => Configuration::get('GSITEMAP_CHECK_IMAGE_FILE'),
            'shop' => $this->context->shop,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
    }

    /**
     * Delete all the generated sitemap files from the files system and the database.
     *
     * @param int $id_shop
     *
     * @return bool
     */
    public function emptySitemap($id_shop = 0)
    {
        if (!isset($this->context)) {
            $this->context = new Context();
        }
        if ($id_shop != 0) {
            $this->context->shop = new Shop((int) $id_shop);
        }
        $links = Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'gsitemap_sitemap` WHERE id_shop = ' . (int) $this->context->shop->id);
        if ($links) {
            foreach ($links as $link) {
                @unlink($this->normalizeDirectory(_PS_ROOT_DIR_) . $link['link']);
            }

            return Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'gsitemap_sitemap` WHERE id_shop = ' . (int) $this->context->shop->id);
        }

        return true;
    }

    /**
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param array $new_link contain the link elements
     * @param string $lang language of link to add
     * @param int $index index of the current Google sitemap file
     * @param int $i count of elements added to sitemap main array
     * @param int $id_obj identifier of the object of the link to be added to the Gogle sitemap file
     *
     * @return bool
     */
    public function addLinkToSitemap(&$link_sitemap, $new_link, $lang, &$index, &$i, $id_obj)
    {
        if ($i <= 25000 && memory_get_usage() < 100000000) {
            $link_sitemap[] = $new_link;
            ++$i;

            return true;
        } else {
            $this->recursiveSitemapCreator($link_sitemap, $lang, $index);
            if ($index % 20 == 0 && !$this->cron) {
                $this->context->smarty->assign([
                    'gsitemap_number' => (int) $index,
                    'gsitemap_refresh_page' => $this->context->link->getAdminLink('AdminModules', true, [], [
                        'tab_module' => $this->tab,
                        'module_name' => $this->name,
                        'continue' => 1,
                        'type' => $new_link['type'],
                        'lang' => $lang,
                        'index' => $index,
                        'id' => (int) $id_obj,
                        'id_shop' => $this->context->shop->id,
                    ]),
                ]);

                return false;
            } elseif ($index % 20 == 0 && $this->cron) {
                header('Refresh: 5; url=http' . (Configuration::get('PS_SSL_ENABLED') ? 's' : '') . '://' . Tools::getShopDomain(false, true) . __PS_BASE_URI__ . 'modules/gsitemap/gsitemap-cron.php?continue=1&token=' . Tools::substr(Tools::encrypt('gsitemap/cron'), 0, 10) . '&type=' . $new_link['type'] . '&lang=' . $lang . '&index=' . $index . '&id=' . (int) $id_obj . '&id_shop=' . $this->context->shop->id);
                exit();
            } else {
                if ($this->cron) {
                    Tools::redirect($this->context->link->getBaseLink() . 'modules/gsitemap/gsitemap-cron.php?continue=1&token=' . Tools::substr(Tools::encrypt('gsitemap/cron'), 0, 10) . '&type=' . $new_link['type'] . '&lang=' . $lang . '&index=' . $index . '&id=' . (int) $id_obj . '&id_shop=' . $this->context->shop->id);
                } else {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true, [], [
                        'tab_module' => $this->tab,
                        'module_name' => $this->name,
                        'configure' => $this->name,
                        'continue' => 1,
                        'type' => $new_link['type'],
                        'lang' => $lang,
                        'index' => $index,
                        'id' => (int) $id_obj,
                        'id_shop' => $this->context->shop->id,
                    ]));
                }
                exit();
            }
        }
    }

    /**
     * Hydrate $link_sitemap with home link
     *
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param array $lang language of link to add
     * @param int $index index of the current Google sitemap file
     * @param int $i count of elements added to sitemap main array
     *
     * @return bool
     */
    protected function getHomeLink(&$link_sitemap, $lang, &$index, &$i)
    {
        $link = new Link();

        return $this->addLinkToSitemap($link_sitemap, [
            'type' => 'home',
            'page' => 'home',
            'link' => $link->getPageLink('index', null, $lang['id_lang']),
            'image' => false,
        ], $lang['iso_code'], $index, $i, -1);
    }

    /**
     * Hydrate $link_sitemap with meta link
     *
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param array $lang language of link to add
     * @param int $index index of the current Google sitemap file
     * @param int $i count of elements added to sitemap main array
     * @param int $id_meta meta object identifier
     *
     * @return bool
     */
    protected function getMetaLink(&$link_sitemap, $lang, &$index, &$i, $id_meta = 0)
    {
        if (method_exists('ShopUrl', 'resetMainDomainCache')) {
            ShopUrl::resetMainDomainCache();
        }
        $link = new Link();
        $metas = Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'meta` WHERE `configurable` > 0 AND `id_meta` >= ' . (int) $id_meta . ' AND page <> \'index\' ORDER BY `id_meta` ASC');
        foreach ($metas as $meta) {
            $url = '';
            if (!in_array($meta['id_meta'], explode(',', Configuration::get('GSITEMAP_DISABLE_LINKS')))) {
                $url = $link->getPageLink($meta['page'], null, $lang['id_lang']);

                if (!$this->addLinkToSitemap($link_sitemap, [
                    'type' => 'meta',
                    'page' => $meta['page'],
                    'link' => $url,
                    'image' => false,
                ], $lang['iso_code'], $index, $i, $meta['id_meta'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Hydrate $link_sitemap with products link
     *
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param array $lang language of link to add
     * @param int $index index of the current Google sitemap file
     * @param int $i count of elements added to sitemap main array
     * @param int $id_product product object identifier
     *
     * @return bool
     */
    protected function getProductLink(&$link_sitemap, $lang, &$index, &$i, $id_product = 0)
    {
        $link = new Link();
        if (method_exists('ShopUrl', 'resetMainDomainCache')) {
            ShopUrl::resetMainDomainCache();
        }

        /*
        * If group feature is enabled, we will show only publicly accessible categories in the sitemap.
        * In the core, if there is at least one category of the product publicly accessible, the product is accessible.
        * So, we do a subselect where we try to find at least one category accessible, then we inner join it to the product table
        * and we are left with only accessible products.
        */
        if (Group::isFeatureActive() && !empty(Configuration::get('PS_UNIDENTIFIED_GROUP'))) {
            $group_join = ' INNER JOIN (SELECT DISTINCT cp.`id_product` FROM `' . _DB_PREFIX_ . 'category_product` cp 
            INNER JOIN `' . _DB_PREFIX_ . 'category_group` ctg ON (ctg.`id_category` = cp.`id_category`) 
            WHERE ctg.`id_group` = ' . (int) Configuration::get('PS_UNIDENTIFIED_GROUP') . ' AND cp.`id_product` >= ' . (int) $id_product . ' 
            ) g ON ps.`id_product` = g.`id_product`';
        } else {
            $group_join = ' ';
        }

        // Get product IDs
        $products_id = Db::getInstance()->ExecuteS('SELECT ps.`id_product` FROM `' . _DB_PREFIX_ . 'product_shop` ps' . $group_join . '
        WHERE ps.`id_product` >= ' . (int) $id_product . ' AND ps.`active` = 1 AND ps.`visibility` != \'none\' 
        AND ps.`id_shop`=' . $this->context->shop->id . ' 
        ORDER BY ps.`id_product` ASC');

        // Process each category and add it to list of links that will be further "converted" to XML and added to the sitemap
        foreach ($products_id as $product_id) {
            $product = new Product((int) $product_id['id_product'], false, (int) $lang['id_lang']);

            $url = $link->getProductLink($product, $product->link_rewrite, htmlspecialchars(strip_tags($product->category)), $product->ean13, (int) $lang['id_lang'], (int) $this->context->shop->id, 0);

            $images_product = [];
            foreach ($product->getImages((int) $lang['id_lang']) as $id_image) {
                if (isset($id_image['id_image'])) {
                    $image_link = $this->context->link->getImageLink($product->link_rewrite, $product->id . '-' . (int) $id_image['id_image'], ImageType::getFormattedName('large'));
                    $image_link = (!in_array(rtrim(Context::getContext()->shop->virtual_uri, '/'), explode('/', $image_link))) ? str_replace([
                        'https',
                        Context::getContext()->shop->domain . Context::getContext()->shop->physical_uri,
                    ], [
                        'http',
                        Context::getContext()->shop->domain . Context::getContext()->shop->physical_uri . Context::getContext()->shop->virtual_uri,
                    ], $image_link) : $image_link;
                }
                $file_headers = (Configuration::get('GSITEMAP_CHECK_IMAGE_FILE') && isset($image_link)) ? @get_headers($image_link) : true;
                if (isset($image_link) && ((isset($file_headers[0]) && $file_headers[0] != 'HTTP/1.1 404 Not Found') || $file_headers === true)) {
                    $images_product[] = [
                        'title_img' => htmlspecialchars(strip_tags($product->name)),
                        'caption' => htmlspecialchars(strip_tags($product->meta_description)),
                        'link' => $image_link,
                    ];
                }
                unset($image_link);
            }

            if (!$this->addLinkToSitemap($link_sitemap, [
                'type' => 'product',
                'page' => 'product',
                'lastmod' => $product->date_upd,
                'link' => $url,
                'images' => $images_product,
            ], $lang['iso_code'], $index, $i, $product_id['id_product'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hydrate $link_sitemap with categories link
     *
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param array $lang language of link to add
     * @param int $index index of the current Google sitemap file
     * @param int $i count of elements added to sitemap main array
     * @param int $id_category category object identifier
     *
     * @return bool
     */
    protected function getCategoryLink(&$link_sitemap, $lang, &$index, &$i, $id_category = 0)
    {
        $link = new Link();
        if (method_exists('ShopUrl', 'resetMainDomainCache')) {
            ShopUrl::resetMainDomainCache();
        }

        // If group feature is enabled, we will show only publicly accessible categories in the sitemap
        if (Group::isFeatureActive() && !empty(Configuration::get('PS_UNIDENTIFIED_GROUP'))) {
            $group_join = ' INNER JOIN `' . _DB_PREFIX_ . 'category_group` cg ON c.`id_category` = cg.`id_category` AND cg.`id_group` = ' . (int) Configuration::get('PS_UNIDENTIFIED_GROUP');
        } else {
            $group_join = ' ';
        }

        // Get category IDs
        $categories_id = Db::getInstance()->ExecuteS('SELECT c.id_category FROM `' . _DB_PREFIX_ . 'category` c
                INNER JOIN `' . _DB_PREFIX_ . 'category_shop` cs ON c.`id_category` = cs.`id_category`' .
                $group_join . '
                WHERE c.`id_category` >= ' . (int) $id_category . ' AND c.`active` = 1 
                AND c.`id_category` != ' . (int) Configuration::get('PS_ROOT_CATEGORY') . ' 
                AND c.id_category != ' . (int) Configuration::get('PS_HOME_CATEGORY') . ' 
                AND c.id_parent > 0 AND c.`id_category` > 0 AND cs.`id_shop` = ' . (int) $this->context->shop->id . ' 
                ORDER BY c.`id_category` ASC');

        // Process each category and add it to list of links that will be further "converted" to XML and added to the sitemap
        foreach ($categories_id as $category_id) {
            $category = new Category((int) $category_id['id_category'], (int) $lang['id_lang']);
            $url = $link->getCategoryLink($category, urlencode($category->link_rewrite), (int) $lang['id_lang']);

            if ($category->id_image) {
                $image_link = $this->context->link->getCatImageLink($category->link_rewrite, (int) $category->id_image, ImageType::getFormattedName('category'));
                $image_link = (!in_array(rtrim(Context::getContext()->shop->virtual_uri, '/'), explode('/', $image_link))) ? str_replace([
                    'https',
                    Context::getContext()->shop->domain . Context::getContext()->shop->physical_uri,
                ], [
                    'http',
                    Context::getContext()->shop->domain . Context::getContext()->shop->physical_uri . Context::getContext()->shop->virtual_uri,
                ], $image_link) : $image_link;
            }
            $file_headers = (Configuration::get('GSITEMAP_CHECK_IMAGE_FILE') && isset($image_link)) ? @get_headers($image_link) : true;
            $image_category = [];
            if (isset($image_link) && ((isset($file_headers[0]) && $file_headers[0] != 'HTTP/1.1 404 Not Found') || $file_headers === true)) {
                $image_category = [
                    'title_img' => htmlspecialchars(strip_tags($category->name)),
                    'caption' => Tools::substr(htmlspecialchars(strip_tags($category->description)), 0, 350),
                    'link' => $image_link,
                ];
            }

            if (!$this->addLinkToSitemap($link_sitemap, [
                'type' => 'category',
                'page' => 'category',
                'lastmod' => $category->date_upd,
                'link' => $url,
                'image' => $image_category,
            ], $lang['iso_code'], $index, $i, (int) $category_id['id_category'])) {
                return false;
            }

            unset($image_link);
        }

        return true;
    }

    /**
     * return the link elements for the CMS object
     *
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param array $lang the language of link to add
     * @param int $index the index of the current Google sitemap file
     * @param int $i the count of elements added to sitemap main array
     * @param int $id_cms the CMS object identifier
     *
     * @return bool
     */
    protected function getCmsLink(&$link_sitemap, $lang, &$index, &$i, $id_cms = 0)
    {
        $link = new Link();
        if (method_exists('ShopUrl', 'resetMainDomainCache')) {
            ShopUrl::resetMainDomainCache();
        }
        $cmss_id = Db::getInstance()->ExecuteS('SELECT c.`id_cms` FROM `' . _DB_PREFIX_ . 'cms` c INNER JOIN `' . _DB_PREFIX_ . 'cms_lang` cl ON c.`id_cms` = cl.`id_cms` ' . ($this->tableColumnExists(_DB_PREFIX_ . 'supplier_shop') ? 'INNER JOIN `' . _DB_PREFIX_ . 'cms_shop` cs ON c.`id_cms` = cs.`id_cms` ' : '') . 'INNER JOIN `' . _DB_PREFIX_ . 'cms_category` cc ON c.id_cms_category = cc.id_cms_category AND cc.active = 1
            WHERE c.`active` =1 AND c.`indexation` =1 AND c.`id_cms` >= ' . (int) $id_cms . ($this->tableColumnExists(_DB_PREFIX_ . 'supplier_shop') ? ' AND cs.id_shop = ' . (int) $this->context->shop->id : '') . ' AND cl.`id_lang` = ' . (int) $lang['id_lang'] . ' GROUP BY  c.`id_cms` ORDER BY c.`id_cms` ASC');

        if (is_array($cmss_id)) {
            foreach ($cmss_id as $cms_id) {
                $cms = new CMS((int) $cms_id['id_cms'], $lang['id_lang']);
                $cms->link_rewrite = urlencode((is_array($cms->link_rewrite) ? $cms->link_rewrite[(int) $lang['id_lang']] : $cms->link_rewrite));
                $url = $link->getCMSLink($cms, null, null, $lang['id_lang']);

                if (!$this->addLinkToSitemap($link_sitemap, [
                    'type' => 'cms',
                    'page' => 'cms',
                    'link' => $url,
                    'image' => false,
                ], $lang['iso_code'], $index, $i, $cms_id['id_cms'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns link elements generated by modules subscribes to hook gsitemap::HOOK_ADD_URLS
     *
     * The hook expects modules to return a vector of associative arrays each of them being acceptable by
     *   the gsitemap::_addLinkToSitemap() second attribute (minus the 'type' index).
     * The 'type' index is automatically set to 'module' (not sure here, should we be safe or trust modules?).
     *
     * @param array $link_sitemap by ref. accumulator for all the links for the Google sitemap file to be generated
     * @param array $lang the language being processed
     * @param int $index the index of the current Google sitemap file
     * @param int $i the count of elements added to sitemap main array
     * @param int $num_link restart at link number #$num_link
     *
     * @return bool
     */
    protected function getModuleLink(&$link_sitemap, $lang, &$index, &$i, $num_link = 0)
    {
        /** @var array|string $modules_links */
        $modules_links = Hook::exec(self::HOOK_ADD_URLS, [
            'lang' => $lang,
        ], null, true);
        if (empty($modules_links) || !is_array($modules_links)) {
            return true;
        }
        $links = [];
        foreach ($modules_links as $module_links) {
            $links = array_merge($links, $module_links);
        }
        foreach ($links as $n => $link) {
            if ($num_link > $n) {
                continue;
            }
            $link['type'] = 'module';
            if (!$this->addLinkToSitemap($link_sitemap, $link, $lang['iso_code'], $index, $i, $n)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create the Google sitemap by Shop
     *
     * @param int $id_shop Shop identifier
     *
     * @return bool
     */
    public function createSitemap($id_shop = 0)
    {
        if (@fopen($this->normalizeDirectory(_PS_ROOT_DIR_) . '/test.txt', 'wb') == false) {
            $this->context->controller->errors[] = $this->trans('An error occured while trying to check your file permissions. Please adjust your permissions to allow PrestaShop to write a file in your root directory.', [], 'Modules.Gsitemap.Admin');

            return false;
        } else {
            @unlink($this->normalizeDirectory(_PS_ROOT_DIR_) . 'test.txt');
        }

        if ($id_shop != 0) {
            $this->context->shop = new Shop((int) $id_shop);
        }

        $type = Tools::getValue('type') ? Tools::getValue('type') : '';
        $languages = Language::getLanguages(true, $this->context->shop->id);
        $lang_stop = Tools::getValue('lang') ? true : false;
        $id_obj = Tools::getValue('id') ? (int) Tools::getValue('id') : 0;
        foreach ($languages as $lang) {
            $i = 0;
            $index = (Tools::getValue('index') && Tools::getValue('lang') == $lang['iso_code']) ? (int) Tools::getValue('index') : 0;
            if ($lang_stop && $lang['iso_code'] != Tools::getValue('lang')) {
                continue;
            } elseif ($lang_stop && $lang['iso_code'] == Tools::getValue('lang')) {
                $lang_stop = false;
            }

            $link_sitemap = [];
            foreach ($this->type_array as $type_val) {
                if ($type == '' || $type == $type_val) {
                    $function = 'get' . Tools::ucfirst($type_val) . 'Link';
                    if (!$this->$function($link_sitemap, $lang, $index, $i, $id_obj)) {
                        return false;
                    }
                    $type = '';
                    $id_obj = 0;
                }
            }
            $this->recursiveSitemapCreator($link_sitemap, $lang['iso_code'], $index);
            $page = '';
            $index = 0;
        }

        $this->createIndexSitemap();
        Configuration::updateValue('GSITEMAP_LAST_EXPORT', date('r'));
        Tools::file_get_contents('https://www.google.com/webmasters/sitemaps/ping?sitemap=' . urlencode($this->context->link->getBaseLink() . $this->context->shop->physical_uri . $this->context->shop->virtual_uri . $this->context->shop->id));

        if ($this->cron) {
            exit();
        }
        Tools::redirectAdmin('index.php?tab=AdminModules&configure=gsitemap&token=' . Tools::getAdminTokenLite('AdminModules') . '&tab_module=' . $this->tab . '&module_name=gsitemap&validation');
        exit();
    }

    /**
     * Store the generated sitemap file to the database
     *
     * @param string $sitemap the name of the generated Google sitemap file
     *
     * @return bool
     */
    protected function saveSitemapLink($sitemap)
    {
        if ($sitemap) {
            return Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . 'gsitemap_sitemap` (`link`, id_shop) VALUES (\'' . pSQL($sitemap) . '\', ' . (int) $this->context->shop->id . ')');
        }

        return false;
    }

    /**
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param string $lang the language of link to add
     * @param int $index the index of the current Google sitemap file
     *
     * @return bool
     */
    protected function recursiveSitemapCreator($link_sitemap, $lang, &$index)
    {
        if (!count($link_sitemap)) {
            return false;
        }

        $sitemap_link = $this->context->shop->id . '_' . $lang . '_' . $index . '_sitemap.xml';
        $write_fd = fopen($this->normalizeDirectory(_PS_ROOT_DIR_) . $sitemap_link, 'wb');

        fwrite($write_fd, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL);
        foreach ($link_sitemap as $key => $file) {
            fwrite($write_fd, '<url>' . PHP_EOL);
            $lastmod = (isset($file['lastmod']) && !empty($file['lastmod'])) ? date('c', strtotime($file['lastmod'])) : null;
            $this->addSitemapNode($write_fd, htmlspecialchars(strip_tags($file['link'])), $this->getPriorityPage($file['page']), Configuration::get('GSITEMAP_FREQUENCY'), $lastmod);

            $images = [];
            if (isset($file['image']) && $file['image']) {
                $images[] = $file['image'];
            }
            if (isset($file['images']) && $file['images']) {
                $images = array_merge($images, $file['images']);
            }
            foreach ($images as $image) {
                $this->addSitemapNodeImage($write_fd, htmlspecialchars(strip_tags($image['link'])), isset($image['title_img']) ? htmlspecialchars(str_replace([
                    "\r\n",
                    "\r",
                    "\n",
                ], '', $this->removeControlCharacters(strip_tags($image['title_img'])))) : '', isset($image['caption']) ? htmlspecialchars(str_replace([
                    "\r\n",
                    "\r",
                    "\n",
                ], '', strip_tags($image['caption']))) : '');
            }
            fwrite($write_fd, '</url>' . PHP_EOL);
        }
        fwrite($write_fd, '</urlset>' . PHP_EOL);
        fclose($write_fd);
        $this->saveSitemapLink($sitemap_link);
        ++$index;

        return true;
    }

    /**
     * return the priority value set in the configuration parameters
     *
     * @param string $page
     *
     * @return float|string|bool
     */
    protected function getPriorityPage($page)
    {
        return Configuration::get('GSITEMAP_PRIORITY_' . Tools::strtoupper($page)) ? Configuration::get('GSITEMAP_PRIORITY_' . Tools::strtoupper($page)) : 0.1;
    }

    /**
     * Add a new line to the sitemap file
     *
     * @param resource $fd file system object resource
     * @param string $loc string the URL of the object page
     * @param string $priority
     * @param string $change_freq
     * @param string $last_mod the last modification date/time as a timestamp
     */
    protected function addSitemapNode($fd, $loc, $priority, $change_freq, $last_mod = null)
    {
        fwrite(
            $fd,
            '<loc>' . (Configuration::get('PS_REWRITING_SETTINGS') ? '<![CDATA[' . $loc . ']]>' : $loc) . '</loc>' . PHP_EOL . ($last_mod ? '<lastmod>' . date('c', strtotime($last_mod)) . '</lastmod>' : '') . PHP_EOL . '<changefreq>' . $change_freq . '</changefreq>' . PHP_EOL . '<priority>' . number_format((float) $priority, 1, '.', '') . '</priority>' . PHP_EOL
        );
    }

    protected function addSitemapNodeImage($fd, $link, $title, $caption)
    {
        fwrite($fd, '<image:image>' . PHP_EOL . '<image:loc>' . (Configuration::get('PS_REWRITING_SETTINGS') ? '<![CDATA[' . $link . ']]>' : $link) . '</image:loc>' . PHP_EOL . '<image:caption><![CDATA[' . $caption . ']]></image:caption>' . PHP_EOL . '<image:title><![CDATA[' . $title . ']]></image:title>' . PHP_EOL . '</image:image>' . PHP_EOL);
    }

    /**
     * Create the index file for all generated sitemaps
     *
     * @return bool
     */
    protected function createIndexSitemap()
    {
        $sitemaps = Db::getInstance()->ExecuteS('SELECT `link` FROM `' . _DB_PREFIX_ . 'gsitemap_sitemap` WHERE id_shop = ' . $this->context->shop->id);
        if (!$sitemaps) {
            return false;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>';
        $xml_feed = new SimpleXMLElement($xml);

        foreach ($sitemaps as $link) {
            $sitemap = $xml_feed->addChild('sitemap');
            $sitemap->addChild('loc', $this->context->link->getBaseLink() . $link['link']);
            $sitemap->addChild('lastmod', date('c'));
        }
        file_put_contents($this->normalizeDirectory(_PS_ROOT_DIR_) . $this->context->shop->id . '_index_sitemap.xml', $xml_feed->asXML());

        return true;
    }

    protected function tableColumnExists($table_name, $column = null)
    {
        if (array_key_exists($table_name, $this->sql_checks)) {
            if (!empty($column) && array_key_exists($column, $this->sql_checks[$table_name])) {
                return $this->sql_checks[$table_name][$column];
            } else {
                return $this->sql_checks[$table_name];
            }
        }

        $table = Db::getInstance()->ExecuteS('SHOW TABLES LIKE \'' . $table_name . '\'');
        if (empty($column)) {
            if (count($table) < 1) {
                return $this->sql_checks[$table_name] = false;
            } else {
                $this->sql_checks[$table_name] = true;
            }
        } else {
            $table = Db::getInstance()->ExecuteS('SELECT * FROM `' . $table_name . '` LIMIT 1');

            return $this->sql_checks[$table_name][$column] = array_key_exists($column, current($table));
        }

        return true;
    }

    protected function normalizeDirectory($directory)
    {
        $last = $directory[Tools::strlen($directory) - 1];

        if (in_array($last, [
            '/',
            '\\',
        ])) {
            $directory[Tools::strlen($directory) - 1] = DIRECTORY_SEPARATOR;

            return $directory;
        }

        $directory .= DIRECTORY_SEPARATOR;

        return $directory;
    }

    protected function removeControlCharacters($text)
    {
        $text = (string) preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $text);
        $text = (string) preg_replace('!\s+!', ' ', $text);

        return $text;
    }
}
