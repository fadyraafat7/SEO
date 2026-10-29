<?php
/**
 * Plugin Name: Portable SEO
 * Description: Lightweight, reusable SEO metadata and structured data for WordPress.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Text Domain: portable-seo
 */
defined( 'ABSPATH' ) || exit;

final class Portable_SEO {
    const OPTION = 'portable_seo_settings';
    const PREFIX = '_portable_seo_';
    private static $conflict = false;

    public static function init() {
        self::$conflict = defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION') || defined('THE_SEO_FRAMEWORK_VERSION');
        add_action('admin_menu', array(__CLASS__, 'admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        if (self::$conflict) { add_action('admin_notices', array(__CLASS__, 'conflict_notice')); return; }
        add_action('add_meta_boxes', array(__CLASS__, 'add_boxes'));
        add_action('save_post', array(__CLASS__, 'save_box'));
        add_filter('pre_get_document_title', array(__CLASS__, 'title'));
        add_action('wp', array(__CLASS__, 'replace_canonical'));
        add_action('wp_head', array(__CLASS__, 'metadata'), 1);
        add_action('wp_head', array(__CLASS__, 'schema'), 2);
        add_action('wp_head', array(__CLASS__, 'canonical_tag'), 3);
        add_filter('wp_robots', array(__CLASS__, 'robots'));
        add_filter('wp_sitemaps_posts_query_args', array(__CLASS__, 'sitemap_args'));
        add_action('send_headers', array(__CLASS__, 'environment_headers'));
        add_filter('rest_post_dispatch', array(__CLASS__, 'rest_headers'));
    }
    public static function enabled() { return !self::$conflict; }
    public static function conflict_notice() { if (current_user_can('activate_plugins')) echo '<div class="notice notice-warning"><p>Portable SEO paused its metadata output because another supported SEO plugin is active. Use one SEO provider at a time.</p></div>'; }
    public static function option($key, $default = '') {
        $settings = get_option(self::OPTION, array());
        if (is_array($settings) && isset($settings[$key]) && is_scalar($settings[$key]) && '' !== trim((string)$settings[$key])) return (string)$settings[$key];
        return apply_filters('portable_seo_default_' . $key, $default);
    }
    public static function meta($id, $key) {
        if (!$id) return '';
        $name = self::PREFIX . $key;
        if (metadata_exists('post', $id, $name)) return (string)get_post_meta($id, $name, true);
        return (string)apply_filters('portable_seo_legacy_meta', '', $id, $key);
    }
    public static function noindex($id) { return '1' === self::meta($id, 'noindex'); }
    public static function admin_menu() { add_options_page('Portable SEO', 'Portable SEO', 'manage_options', 'portable-seo', array(__CLASS__, 'settings_page')); }
    public static function register_settings() {
        register_setting('portable_seo', self::OPTION, array('type'=>'array', 'sanitize_callback'=>array(__CLASS__, 'sanitize_settings'), 'default'=>array()));
    }
    public static function sanitize_settings($input) {
        $input = is_array($input) ? array_filter($input, 'is_scalar') : array(); $out = array();
        foreach (array('home_title','description','organization_name','organization_phone','organization_address') as $key) $out[$key] = sanitize_text_field($input[$key] ?? '');
        foreach (array('image','organization_logo') as $key) $out[$key] = esc_url_raw($input[$key] ?? '', array('http','https'));
        $out['organization_email'] = sanitize_email($input['organization_email'] ?? '');
        $out['socials'] = implode("\n", array_filter(array_map(static function($line) { return esc_url_raw(trim($line), array('http','https')); }, preg_split('/\R/', (string)($input['socials'] ?? '')))));
        return $out;
    }
    public static function settings_page() {
        if (!current_user_can('manage_options')) return;
        $options = get_option(self::OPTION, array()); $options = is_array($options) ? $options : array();
        $fields = array('home_title'=>'Home page title', 'description'=>'Default description', 'image'=>'Default social image URL', 'organization_name'=>'Organization name', 'organization_logo'=>'Organization logo URL', 'organization_phone'=>'Organization telephone', 'organization_email'=>'Organization email', 'organization_address'=>'Organization address', 'socials'=>'Social profile URLs (one per line)');
        echo '<div class="wrap"><h1>Portable SEO</h1><p>Leave fields blank to use WordPress values and available theme defaults. Use a full HTTPS URL for images on a live site.</p><form action="options.php" method="post">';
        settings_fields('portable_seo'); echo '<table class="form-table" role="presentation">';
        foreach ($fields as $key=>$label) {
            echo '<tr><th scope="row"><label for="pseo-' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td>';
            if ('socials' === $key) echo '<textarea class="large-text" rows="4" id="pseo-socials" name="' . esc_attr(self::OPTION) . '[socials]">' . esc_textarea($options[$key] ?? '') . '</textarea>';
            else echo '<input class="regular-text" id="pseo-' . esc_attr($key) . '" name="' . esc_attr(self::OPTION) . '[' . esc_attr($key) . ']" type="' . (in_array($key, array('image','organization_logo'), true) ? 'url' : 'text') . '" value="' . esc_attr($options[$key] ?? '') . '">';
            echo '</td></tr>';
        }
        echo '</table>'; submit_button(); echo '</form><p>Per-page settings are available in the SEO box on public post types. WordPress manages its own sitemap.</p></div>';
    }
    public static function add_boxes() {
        foreach (get_post_types(array('public'=>true, 'show_ui'=>true), 'names') as $type) {
            if ('attachment' === $type || !post_type_supports($type, 'title')) continue;
            add_meta_box('portable-seo', 'SEO', array(__CLASS__, 'box'), $type, 'normal', 'high');
        }
    }
    public static function box($post) {
        wp_nonce_field('portable_seo_' . $post->ID, 'portable_seo_nonce');
        echo '<p><label for="pseo-title">SEO title</label><br><input class="widefat" id="pseo-title" name="portable_seo_title" value="' . esc_attr(self::meta($post->ID,'title')) . '" placeholder="Use the page title"></p>';
        echo '<p><label for="pseo-description">Meta description</label><br><textarea class="widefat" rows="3" id="pseo-description" name="portable_seo_description" maxlength="320">' . esc_textarea(self::meta($post->ID,'description')) . '</textarea></p>';
        echo '<p><label for="pseo-image">Social image URL</label><br><input class="widefat" type="url" id="pseo-image" name="portable_seo_image" value="' . esc_attr(self::meta($post->ID,'image')) . '" placeholder="Use featured image or site default"></p>';
        echo '<p><label><input type="checkbox" name="portable_seo_noindex" value="1" ' . checked(self::noindex($post->ID), true, false) . '> Do not index this content; exclude it from the WordPress sitemap.</label></p>';
    }
    public static function save_box($id) {
        if (wp_is_post_revision($id) || wp_is_post_autosave($id) || empty($_POST['portable_seo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['portable_seo_nonce'])), 'portable_seo_' . $id) || !current_user_can('edit_post',$id)) return;
        $values = array('title'=>sanitize_text_field(wp_unslash($_POST['portable_seo_title'] ?? '')), 'description'=>sanitize_textarea_field(wp_unslash($_POST['portable_seo_description'] ?? '')), 'image'=>esc_url_raw(wp_unslash($_POST['portable_seo_image'] ?? ''), array('http','https')), 'noindex'=>isset($_POST['portable_seo_noindex']) && '1' === $_POST['portable_seo_noindex'] ? '1' : '0');
        // Explicit empty values intentionally override imported legacy metadata.
        foreach ($values as $key=>$value) update_post_meta($id, self::PREFIX . $key, $value);
    }
    public static function current_id() { return is_home() && !is_front_page() ? (int)get_option('page_for_posts') : (is_singular() ? get_queried_object_id() : 0); }
    public static function title($title) {
        $id = self::current_id(); $custom = self::meta($id,'title');
        if ($custom) return $custom;
        if (is_front_page() && self::option('home_title')) return self::option('home_title');
        return apply_filters('portable_seo_document_title', $title);
    }
    public static function description() {
        $id = self::current_id(); $description = self::meta($id,'description');
        if ($description) return $description;
        if ($id && has_excerpt($id)) return wp_strip_all_tags(get_the_excerpt($id), true);
        $section = apply_filters('portable_seo_section_description', '', $id);
        if ($section) return wp_strip_all_tags($section, true);
        if ($id && is_singular() && !post_password_required($id)) {
            $text = get_post_field('post_content',$id);
            if ($text) return wp_trim_words(wp_strip_all_tags(strip_shortcodes($text), true), 28, '…');
        }
        return self::option('description', get_bloginfo('description'));
    }
    public static function image() {
        $id = self::current_id(); $image = self::meta($id,'image');
        if ($image) return $image;
        if ($id && has_post_thumbnail($id)) return get_the_post_thumbnail_url($id,'large');
        return self::option('image', get_site_icon_url(512));
    }
    public static function canonical() {
        if (is_404() || is_search()) return '';
        $page = max(1,(int)get_query_var('paged'));
        if (is_singular()) $url = wp_get_canonical_url(get_queried_object_id()) ?: get_permalink(get_queried_object_id());
        elseif (is_home() && 1 === $page) $url = get_option('page_for_posts') ? get_permalink((int)get_option('page_for_posts')) : home_url('/');
        elseif (is_front_page() && 1 === $page) $url = home_url('/');
        else {
            $url = get_pagenum_link($page, false);
            // Keep meaningful pagination and archive parameters while dropping common campaign IDs.
            $query = wp_parse_url($url, PHP_URL_QUERY); $params = array();
            if ($query) { parse_str($query, $params); foreach (array_keys($params) as $key) if (preg_match('/^(?:utm_.+|gclid|dclid|fbclid|msclkid)$/i', $key)) $url = remove_query_arg($key, $url); }
        }
        return apply_filters('portable_seo_canonical_url', $url, $page);
    }
    public static function replace_canonical() { remove_action('wp_head','rel_canonical'); }
    public static function canonical_tag() { $url = self::canonical(); if ($url) echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n"; do_action('portable_seo_after_canonical'); }
    public static function robots($robots) {
        if (is_search() || is_404() || self::noindex(self::current_id())) { unset($robots['index']); $robots['noindex'] = true; }
        return $robots;
    }
    public static function sitemap_args($args) {
        $exclusion = array('relation'=>'OR', array('key'=>self::PREFIX.'noindex','compare'=>'NOT EXISTS'), array('key'=>self::PREFIX.'noindex','value'=>'1','compare'=>'!='));
        $exclusion = apply_filters('portable_seo_sitemap_noindex_query', $exclusion);
        $existing = isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : array();
        $args['meta_query'] = $existing ? array('relation'=>'AND', $existing, $exclusion) : array($exclusion);
        return $args;
    }
    public static function environment_headers() { if (in_array(wp_get_environment_type(), array('local','development','staging'), true)) header('X-Robots-Tag: noindex, nofollow', false); }
    public static function rest_headers($response) { $response->header('X-Robots-Tag','noindex'); return $response; }
    public static function metadata() {
        $title=wp_get_document_title(); $desc=self::description(); $image=self::image(); $url=self::canonical();
        if ($desc) echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
        $properties=array('og:type'=>is_singular('post')?'article':'website', 'og:site_name'=>get_bloginfo('name'), 'og:title'=>$title, 'og:description'=>$desc, 'og:url'=>$url, 'og:image'=>$image, 'og:locale'=>get_locale());
        if (is_singular('post')) $properties['article:published_time']=get_the_date(DATE_W3C);
        foreach ($properties as $key=>$value) if ($value) echo '<meta property="' . esc_attr($key) . '" content="' . esc_attr($value) . '">' . "\n";
        $twitter=array('twitter:card'=>$image?'summary_large_image':'summary', 'twitter:title'=>$title, 'twitter:description'=>$desc, 'twitter:image'=>$image);
        foreach ($twitter as $key=>$value) if ($value) echo '<meta name="' . esc_attr($key) . '" content="' . esc_attr($value) . '">' . "\n";
    }
    public static function print_json($data) { echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>' . "\n"; }
    public static function schema() {
        if (is_404() || is_search() || (is_singular() && post_password_required())) return;
        $name=self::option('organization_name', get_bloginfo('name')); $logo=self::option('organization_logo', get_site_icon_url(512));
        $organization=array('@context'=>'https://schema.org','@type'=>'Organization','name'=>$name,'url'=>home_url('/'));
        if ($logo) $organization['logo']=array('@type'=>'ImageObject','url'=>$logo);
        if (self::option('organization_phone')) $organization['telephone']=self::option('organization_phone');
        if (self::option('organization_email')) $organization['email']=self::option('organization_email');
        if (self::option('organization_address')) $organization['address']=array('@type'=>'PostalAddress','streetAddress'=>self::option('organization_address'));
        $socials=array_filter(array_map('trim',preg_split('/\R/', self::option('socials')))); if ($socials) $organization['sameAs']=array_values($socials);
        $organization=apply_filters('portable_seo_organization_schema', $organization);
        if (is_front_page()) self::print_json($organization);
        if (is_singular('post')) {
            $article=array('@context'=>'https://schema.org','@type'=>'Article','headline'=>get_the_title(),'description'=>self::description(),'mainEntityOfPage'=>self::canonical(),'datePublished'=>get_the_date(DATE_W3C),'dateModified'=>get_the_modified_date(DATE_W3C),'author'=>array('@type'=>'Person','name'=>get_the_author_meta('display_name',get_post_field('post_author',get_queried_object_id()))),'publisher'=>array('@type'=>'Organization','name'=>$name));
            if ($logo) $article['publisher']['logo']=array('@type'=>'ImageObject','url'=>$logo);
            if (self::image()) $article['image']=self::image();
            self::print_json($article);
        }
    }
    /** Call from a theme that renders the same breadcrumb trail to visitors. */
    public static function breadcrumbs($items) {
        if (!self::enabled() || count($items)<2) return;
        $entries=array(); foreach (array_values($items) as $i=>$item) { if (empty($item['name'])) return; $entry=array('@type'=>'ListItem','position'=>$i+1,'name'=>wp_strip_all_tags($item['name'])); if (!empty($item['url'])) $entry['item']=esc_url_raw($item['url']); $entries[]=$entry; }
        self::print_json(array('@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$entries));
    }
}
add_action('plugins_loaded', array('Portable_SEO','init'));
