{*
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
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
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

{if isset($smarty.get.validation)}
<div class="alert alert-success" role="alert">
   <button type="button" class="close" data-dismiss="alert" aria-label="Close">
   <span aria-hidden="true">&times;</span>
   </button>
   <p class="alert-text">{l s='Your sitemaps were successfully created. Please do not forget to setup the URL' d='Modules.Gsitemap.Admin'} <a class="alert-link" href="{$gsitemap_store_url|escape:'htmlall':'UTF-8'}{$shop->id|intval}_index_sitemap.xml" target="_blank">{$gsitemap_store_url|escape:'htmlall':'UTF-8'}{$shop->id|intval}_index_sitemap.xml</a> {l s='in your Google Webmaster account.' d='Modules.Gsitemap.Admin'}</p>
</div>
{/if}

<div class="panel">
   {if isset($google_maps_error)}
   <div class="error">
      {$google_maps_error|escape:'htmlall':'UTF-8'}<br>
   </div>
   {/if}
   {if isset($gsitemap_refresh_page)}
   <h3><i class="icon icon-sitemap"></i> {l s='Your sitemaps' d='Modules.Gsitemap.Admin'}</h3>
   <p>{$gsitemap_number|intval} {l s='Sitemaps were already created.' d='Modules.Gsitemap.Admin'}</p>
   <br><br>
   <form action="{$gsitemap_refresh_page|escape:'htmlall':'UTF-8'}" method="post" id="gsitemap_generate_sitmap">
      <img src="../img/loader.gif" alt=""/>
      <input type="submit" class="button" value="{l s='Continue' d='Admin.Actions'}" style="display: none;"/>
   </form>
   {else}
   {if $gsitemap_links}
   <h3><i class="icon icon-sitemap"></i> {l s='Your sitemaps' d='Modules.Gsitemap.Admin'}</h3>
   {l s='Please set up the following sitemap URL in your Google Webmaster account:' d='Modules.Gsitemap.Admin'}<br>
   <a href="{$gsitemap_store_url|escape:'htmlall':'UTF-8'}{$shop->id|intval}_index_sitemap.xml" target="_blank">{$gsitemap_store_url|escape:'htmlall':'UTF-8'}{$shop->id|intval}_index_sitemap.xml</a><br><br>
   {l s='The above URL is the master sitemap file. It refers to the following sub-sitemap files:' d='Modules.Gsitemap.Admin'}
   <div style="max-height: 220px; overflow: auto;">
      <ul>
         {foreach from=$gsitemap_links item=gsitemap_link}
         <li><a target="_blank" href="{$gsitemap_store_url|escape:'htmlall':'UTF-8'}{$gsitemap_link.link|escape:'htmlall':'UTF-8'}">{$gsitemap_link.link|escape:'htmlall':'UTF-8'}</a></li>
         {/foreach}
      </ul>
   </div>
   <p>{l s='Your last update was made on this date:' d='Modules.Gsitemap.Admin'} {$gsitemap_last_export|escape:'htmlall':'UTF-8'}</p>
   {else}
   <h3><i class="icon icon-sitemap"></i> {l s='Your sitemaps' d='Modules.Gsitemap.Admin'}</h3>
   <p>{l s='This shop has no sitemap yet.' d='Modules.Gsitemap.Admin'}<br>
   </p>
   {/if}
   {if ($gsitemap_customer_limit.max_exec_time < 30 && $gsitemap_customer_limit.max_exec_time > 0) || ($gsitemap_customer_limit.memory_limit < 128 && $gsitemap_customer_limit.memory_limit > 0)}
   <br>
   <div class="alert alert-warning" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
      </button>
      <p>{l s='For a better use of the module, please make sure that you have' d='Modules.Gsitemap.Admin'}<br>
      <ul>
         {if $gsitemap_customer_limit.memory_limit < 128 && $gsitemap_customer_limit.memory_limit > 0}
         <li>{l s='A minimum memory_limit value of 128 MB.' d='Modules.Gsitemap.Admin'}</li>
         {/if}
         {if $gsitemap_customer_limit.max_exec_time < 30 && $gsitemap_customer_limit.max_exec_time > 0}
         <li>{l s='A minimum max_execution_time value of 30 seconds.' d='Modules.Gsitemap.Admin'}</li>
         {/if}
      </ul>
      {l s='You can edit these limits in your php.ini file. For more details, please contact your hosting provider.' d='Modules.Gsitemap.Admin'}</p>
   </div>
   {/if}
</div>

<div class="panel">
   <form action="{$gsitemap_form|escape:'htmlall':'UTF-8'}" method="post">
      <h3><i class="icon icon-wrench"></i> {l s='Configure your sitemap' d='Modules.Gsitemap.Admin'}</h3>
      <p>{l s='Several sitemap files will be generated depending on how your server is configured and on the number of activated products in your catalog.' d='Modules.Gsitemap.Admin'}<br></p>
      <div class="margin-form">
         <label for="gsitemap_frequency" >{l s='How often do you update your store?' d='Modules.Gsitemap.Admin'}
         <select name="gsitemap_frequency">
         <option{if $gsitemap_frequency == 'always'} selected="selected"{/if} value='always'>{l s='always' d='Modules.Gsitemap.Admin'}</option>
         <option{if $gsitemap_frequency == 'hourly'} selected="selected"{/if} value='hourly'>{l s='hourly' d='Modules.Gsitemap.Admin'}</option>
         <option{if $gsitemap_frequency == 'daily'} selected="selected"{/if} value='daily'>{l s='daily' d='Modules.Gsitemap.Admin'}</option>
         <option{if $gsitemap_frequency == 'weekly' || $gsitemap_frequency == ''} selected="selected"{/if} value='weekly'>{l s='weekly' d='Modules.Gsitemap.Admin'}</option>
         <option{if $gsitemap_frequency == 'monthly'} selected="selected"{/if} value='monthly'>{l s='monthly' d='Modules.Gsitemap.Admin'}</option>
         <option{if $gsitemap_frequency == 'yearly'} selected="selected"{/if} value='yearly'>{l s='yearly' d='Modules.Gsitemap.Admin'}</option>
         <option{if $gsitemap_frequency == 'never'} selected="selected"{/if} value='never'>{l s='never' d='Modules.Gsitemap.Admin'}</option>
         </select></label>
      </div>
      <label><input type="checkbox" name="gsitemap_check_image_file" value="1" {if $gsitemap_check_image_file}checked{/if}> {l s='Check this box if you wish to check the presence of the image files on the server' d='Modules.Gsitemap.Admin'}</label>
      <br>
      <p>{l s='Indicate the pages that you do not want to include in your sitemap files:' d='Modules.Gsitemap.Admin'}</p>
      <button class="btn btn-secondary" id="check">{l s='Uncheck all' d='Modules.Gsitemap.Admin'}</button>
      <br>
      <br class="clear" />
      <ul>
         {foreach from=$store_metas item=store_meta}
         <li style="float: left; width: 400px; margin-bottom: 15px">
            <label><input type="checkbox" class="gsitemap_metas" name="gsitemap_meta[]" {if in_array($store_meta.id_meta, $gsitemap_disable_metas)} checked="checked"{/if} value="{$store_meta.id_meta|intval}"> {$store_meta.title|escape:'htmlall':'UTF-8'} [{$store_meta.page|escape:'htmlall':'UTF-8'}]</label>
         </li>
         {/foreach}
      </ul>
      <br>
      <div class="margin-form" style="clear: both;">
         <br>
         <button type="submit" class="btn btn-primary btn-lg" name="SubmitGsitemap" onclick="$('#gsitemap_loader').show();">{l s='Generate sitemap' d='Modules.Gsitemap.Admin'}</button>
         <br><br>
         <div class="alert alert-info" role="alert">
            {l s='Generating a sitemap can take several minutes' d='Modules.Gsitemap.Admin'}</p>
         </div>
      </div>
      <p id="gsitemap_loader" style="text-align: center; display: none;"><img src="../img/loader.gif" alt=""/></p>
   </form>
</div>

<div class="panel">
   <h3><i class="icon icon-tags"></i> {l s='Information' d='Modules.Gsitemap.Admin'}</h3>
   <p>
      <strong>{l s='You have two ways to generate sitemaps.' d='Modules.Gsitemap.Admin'}</strong><br><br>
      1. <strong>{l s='Manually:' d='Modules.Gsitemap.Admin'}</strong> {l s='Using the form above (as often as needed)' d='Modules.Gsitemap.Admin'}<br>
      <br><span style="font-style: italic;">{l s='-or-' d='Modules.Gsitemap.Admin'}</span><br><br>
      2. <strong>{l s='Automatically:' d='Modules.Gsitemap.Admin'}</strong> {l s='Ask your hosting provider to setup a "Cron job" to load the following URL at the time you would like:' d='Modules.Gsitemap.Admin'}
      <a href="{$gsitemap_cron|escape:'htmlall':'UTF-8'}" target="_blank">{$gsitemap_cron|escape:'htmlall':'UTF-8'}</a><br>
      {l s='It will automatically generate your XML sitemaps.' d='Modules.Gsitemap.Admin'}
   </p>
   {/if}
   </p>
</div>

<script type="text/javascript">
   $(document).ready(function() {
      if ($('.gsitemap_metas:checked').length == $('.gsitemap_metas').length)
         $('#check').html("{l s='Uncheck all' d='Modules.Gsitemap.Admin'}");
      $('#check').toggle(function() {
         $('.gsitemap_metas').removeAttr('checked');
         $(this).html("{l s='Check all' d='Modules.Gsitemap.Admin'}");
      }, function() {
         $('.gsitemap_metas').attr('checked', 'checked');
         $(this).html("{l s='Uncheck all' d='Modules.Gsitemap.Admin'}");
      });
   });
</script>
