<div style="width: 700px; margin: 0 auto;">
</div>
{if isset($smarty.get.validation)}
	<div class="conf confirm" style="width: 710px; margin: 0 auto;">
		{l s='Your Sitemaps were successfully created. Please do not forget to setup the URL' d='Modules.Gsitemap.Admin'} <a href="{$gsitemap_store_url|escape:'htmlall':'UTF-8'}{$shop->id|intval}_index_sitemap.xml" target="_blank"><span style="text-decoration: underline;">{$gsitemap_store_url|escape:'htmlall':'UTF-8'}{$shop->id|intval}_index_sitemap.xml</a></span> {l s='in your Google Webmaster account.' d='Modules.Gsitemap.Admin'}
</div>
<br/>
{/if}
{if isset($google_maps_error)}
	<div class="error" style="width: 710px; margin: 0 auto;">		
		{$google_maps_error|escape:'htmlall':'UTF-8'}<br />	
	</div>
{/if}
{if isset($gsitemap_refresh_page)}
	<fieldset style="width: 700px; margin: 0 auto; text-align: center;">
		<legend><img src="{$module_dir|escape:'htmlall':'UTF-8'}logo.gif" alt="" />{l s='Your Sitemaps' d='Modules.Gsitemap.Admin'}</legend>
		<p>{$gsitemap_number|intval} {l s='Sitemaps were already created.' d='Modules.Gsitemap.Admin'}<br/>
		</p>
		<br/>
		<form action="{$gsitemap_refresh_page|escape:'htmlall':'UTF-8'}" method="post" id="gsitemap_generate_sitmap">
			<img src="../img/loader.gif" alt=""/>
			<input type="submit" class="button" value="{l s='Continue' d='Admin.Actions'}" style="display: none;"/>
		</form>
	</fieldset>
{else}
	{if $gsitemap_links}
		<fieldset style="width: 700px; margin: 0 auto;">
			<legend><img src="{$module_dir|escape:'htmlall':'UTF-8'}logo.gif" alt="" />{l s='Your Sitemaps' d='Modules.Gsitemap.Admin'}</legend>
			{l s='Please set up the following Sitemap URL in your Google Webmaster account:' d='Modules.Gsitemap.Admin'}<br/> 
			<a href="{$gsitemap_store_url|escape:'htmlall':'UTF-8'}{$shop->id|intval}_index_sitemap.xml" target="_blank"><span style="color: blue;">{$gsitemap_store_url|escape:'htmlall':'UTF-8'}{$shop->id|intval}_index_sitemap.xml</span></a><br/><br/>
			{l s='This URL is the master Sitemaps file. It refers to the following sub-sitemap files:' d='Modules.Gsitemap.Admin'}
			<div style="max-height: 220px; overflow: auto;">
				<ul>
					{foreach from=$gsitemap_links item=gsitemap_link}
						<li><a target="_blank" style="color: blue;" href="{$gsitemap_store_url|escape:'htmlall':'UTF-8'}{$gsitemap_link.link|escape:'htmlall':'UTF-8'}">{$gsitemap_link.link|escape:'htmlall':'UTF-8'}</a></li>
						{/foreach}
				</ul>
			</div>
			<p>{l s='Your last update was made on this date:' d='Modules.Gsitemap.Admin'} {$gsitemap_last_export|escape:'htmlall':'UTF-8'}</p>
		</fieldset>
	{/if}
	<br/>
	{if ($gsitemap_customer_limit.max_exec_time < 30 && $gsitemap_customer_limit.max_exec_time > 0) || ($gsitemap_customer_limit.memory_limit < 128 && $gsitemap_customer_limit.memory_limit > 0)}
		<div class="warn" style="width: 700px; margin: 0 auto;">
			<p>{l s='For a better use of the module, please make sure that you have' d='Modules.Gsitemap.Admin'}<br/>
			<ul>
				{if $gsitemap_customer_limit.memory_limit < 128 && $gsitemap_customer_limit.memory_limit > 0}
					<li>{l s='a minimum memory_limit value of 128 MB.' d='Modules.Gsitemap.Admin'}</li>
					{/if}
					{if $gsitemap_customer_limit.max_exec_time < 30 && $gsitemap_customer_limit.max_exec_time > 0}
					<li>{l s='a minimum max_execution_time value of 30 seconds.' d='Modules.Gsitemap.Admin'}</li>
					{/if}
			</ul>
			{l s='You can edit these limits in your php.ini file. For more details, please contact your hosting provider.' d='Modules.Gsitemap.Admin'}</p>
	</div>
{/if}
<br/>
<form action="{$gsitemap_form|escape:'htmlall':'UTF-8'}" method="post">
	<fieldset style="width: 700px; margin: 0 auto;">
		<legend><img src="{$module_dir|escape:'htmlall':'UTF-8'}logo.gif" alt="" />{l s='Configure your Sitemap' d='Modules.Gsitemap.Admin'}</legend>
		<p>{l s='Several Sitemaps files will be generated depending on how your server is configured and on the number of activated products in your catalog.' d='Modules.Gsitemap.Admin'}<br/></p>
		<div class="margin-form">
			<label for="gsitemap_frequency" style="width: 235px;">{l s='How often do you update your store?' d='Modules.Gsitemap.Admin'}
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
		<label for="ggsitemap_check_image_file" style="width: 526px;">{l s='Check this box if you wish to check the presence of the image files on the server' d='Modules.Gsitemap.Admin'}
			<input type="checkbox" name="gsitemap_check_image_file" value="1" {if $gsitemap_check_image_file}checked{/if}></label>
		<label for="ggsitemap_check_all" style="width: 526px;"><span>{l s='check all' d='Modules.Gsitemap.Admin'}</span>
			<input type="checkbox" name="gsitemap_check_all" value="1" class="check"></label>
		<br class="clear" />
		<p for="gsitemap_meta">{l s='Indicate the pages that you do not want to include in your Sitemaps file:' d='Modules.Gsitemap.Admin'}</p>
		<ul>
			{foreach from=$store_metas item=store_meta}
				<li style="float: left; width: 200px; margin: 1px;">
					<input type="checkbox" class="gsitemap_metas" name="gsitemap_meta[]"{if in_array($store_meta.id_meta, $gsitemap_disable_metas)} checked="checked"{/if} value="{$store_meta.id_meta|intval}" /> {$store_meta.title|escape:'htmlall':'UTF-8'} [{$store_meta.page|escape:'htmlall':'UTF-8'}]
				</li>
			{/foreach}
		</ul>
		<br/>
		<div class="margin-form" style="clear: both;">
			<input type="submit" style="margin: 20px;" class="button" name="SubmitGsitemap" onclick="$('#gsitemap_loader').show();" value="{l s='Generate Sitemap' d='Modules.Gsitemap.Admin'}" />{l s='This can take several minutes' d='Modules.Gsitemap.Admin''}
		</div>
		<p id="gsitemap_loader" style="text-align: center; display: none;"><img src="../img/loader.gif" alt=""/></p>
	</fieldset>
</form><br />

<p class="info" style="width: 680px; margin: 10px auto;">
	<b style="display: block; margin-top: 5px; margin-left: 3px;">{l s='You have two ways to generate Sitemap:' d='Modules.Gsitemap.Admin'}</b><br /><br />
	1. <b>{l s='Manually:' d='Modules.Gsitemap.Admin'}</b> {l s='using the form above (as often as needed)' d='Modules.Gsitemap.Admin'}<br />
	<br /><span style="font-style: italic;">{l s='-or-' d='Modules.Gsitemap.Admin'}</span><br /><br />
	2. <b>{l s='Automatically:' d='Modules.Gsitemap.Admin'}</b> {l s='Ask your hosting provider to setup a "Cron task" to load the following URL at the time you would like:' d='Modules.Gsitemap.Admin'}
	<a href="{$gsitemap_cron|escape:'htmlall':'UTF-8'}" target="_blank">{$gsitemap_cron|escape:'htmlall':'UTF-8'}</a><br /><br />
	{l s='It will automatically generate your XML Sitemaps.' d='Modules.Gsitemap.Admin'}<br /><br />
</p>
{/if}
<script type="text/javascript">
$(document).ready(function() {
	
	if ($('.gsitemap_metas:checked').length == $('.gsitemap_metas').length)
		$('.check').parent('label').children('span').html("{l s='uncheck all' d='Modules.Gsitemap.Admin'}");
	
	
	$('.check').toggle(function() {
		$('.gsitemap_metas').attr('checked', 'checked');
		$(this).parent('label').children('span').html("{l s='uncheck all' d='Modules.Gsitemap.Admin'}");
	}, function() {
		$('.gsitemap_metas').removeAttr('checked');
		$(this).parent('label').children('span').html("{l s='check all' d='Modules.Gsitemap.Admin'}");
	});
});
</script>
