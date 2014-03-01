{assign var=enabledFeeds value='ENABLED_FEEDS'|config}
{assign var=storeName value='STORE_NAME'|config|escape}
{% if array_key_exists('NEWS_POSTS', $enabledFeeds) %}
	<link rel="alternate" type="application/rss+xml" title="[[storeName]] | {t _news_posts_feed}" href="[[ url("rss/news") ]]"/>
{% endif %}
{% if array_key_exists('CATEGORY_PRODUCTS', $enabledFeeds) && !empty($category.ID) %}
	<link rel="alternate" type="application/rss+xml" title="[[storeName]] | {t _category_products_feed} ({$category.name()|escape})" href="[[ url("rss/products/" ~ category.ID) ]]"/>
{% endif %}
{% if array_key_exists('ALL_PRODUCTS', $enabledFeeds) %}
	<link rel="alternate" type="application/rss+xml" title="[[storeName]] | {t _all_products_feed}" href="[[ url("rss/products") ]]"/>
{% endif %}

[[ partial("layout/frontend/header.tpl") ]]
{% if empty(hideLeft) %}
	[[ partial("layout/frontend/leftSide.tpl") ]]
{% endif %}
{* include file="layout/frontend/rightSide.tpl" *}
