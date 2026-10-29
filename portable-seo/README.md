# Portable SEO

A lightweight WordPress SEO plugin for projects where you want to own the implementation. Requires WordPress 6.4+ and PHP 7.4+. It does not depend on any theme, Rockpack, a static generator, or a third-party service.

## Install

1. Install the `portable-seo` folder in `wp-content/plugins/`, or upload the plugin ZIP under **Plugins > Add New > Upload Plugin**. Activate it.
2. Under **Settings > Portable SEO**, set a home title, default description and an absolute social image URL appropriate to the site. Configure organization name, logo, contact details and social profile URLs if available. Blank fields fall back to WordPress values where available; a site with no usable image or description can omit those tags until configured. Use actual public HTTPS URLs in production.
3. On public pages, posts and supported public custom post types, use the **SEO** box to override title, description and image or select **Do not index**. Empty fields fall back to the post excerpt/content, featured image or site settings. Per-item metadata is preserved if the plugin is deactivated.
4. Ensure the theme calls `wp_head()` and supports WordPress's `title-tag`; verify output on the public site. WordPress's own `/wp-sitemap.xml` is used and items marked noindex are excluded. Submit the sitemap in Search Console and Bing as appropriate.

Do not run two SEO providers simultaneously. Portable SEO automatically pauses its output when it detects certain known plugins (Yoast, Rank Math, AIOSEO, SEOPress and The SEO Framework), but detection is best-effort and cannot identify every plugin or theme that emits SEO tags. Inspect the HTML for duplicates when installing it. If another provider is active, its own settings and sitemap policies determine indexing; Portable SEO's noindex settings do not control that provider.

## Behavior and limitations

- Produces description, Open Graph, Twitter, canonical, Article schema for public posts, and Organization schema on the homepage. Descriptions are not invented when no data exists. Social images should be usable, publicly accessible image URLs; configure one for each site.
- Sets `noindex` for search, 404 and flagged content; respects WordPress's Search Engine Visibility setting. On `local`, `development`, and `staging` environments it adds a response-level `X-Robots-Tag: noindex, nofollow`; REST responses get `noindex`. Configure equivalent headers for server-served static files, caches and CDN responses, which can bypass WordPress.
- The canonical uses the WordPress URL configuration and canonical permalink APIs. Configure the real public site URL, HTTPS, reverse proxy and staging environment correctly. A site's custom archive/pagination URL convention may require an adapter via `portable_seo_canonical_url`.
- A WordPress theme must explicitly call `Portable_SEO::breadcrumbs()` with the same trail displayed to the user. The plugin does not infer breadcrumbs or generate Product/FAQ/LocalBusiness/Video schema without relevant site data. Example:

```php
if ( class_exists( 'Portable_SEO' ) && Portable_SEO::enabled() ) {
    Portable_SEO::breadcrumbs( array(
        array( 'name' => 'Home', 'url' => home_url( '/' ) ),
        array( 'name' => get_the_title(), 'url' => get_permalink() ),
    ) );
}
```

- The plugin does not convert images, add performance guarantees, create or upload a sitemap for static exports, or set up Search Console. Static generators should integrate their URL rewriting, sitemap and noindex exclusions with `Portable_SEO::noindex($post_id)`, then rebuild after metadata changes. The output of a generated sitemap must match routes actually served by that deployment.
- Extension hooks include `portable_seo_default_{field}`, `portable_seo_section_description`, `portable_seo_document_title`, `portable_seo_canonical_url`, `portable_seo_organization_schema`, `portable_seo_sitemap_noindex_query` and `portable_seo_after_canonical`.

## Tests

`php wp-content/plugins/portable-seo/tests/independent-theme.php` verifies output with Twenty Twenty-Five without Rockpack functions. `php wp-content/plugins/portable-seo/tests/integration.php` is an integration test tailored to this local `.test` installation. It requires an active plugin, administrator, InnoDB and a disposable test database; fixture writes are rolled back. For another environment, adapt its local host/bootstrap guard before using it and review all side effects. Validate canonical URLs, rich results and crawler access after deployment.
