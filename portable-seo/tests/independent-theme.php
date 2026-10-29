<?php
/** Read-only independent-theme smoke test, using WordPress's preinitialized hooks. */
if (PHP_SAPI !== 'cli') exit(2);
$_SERVER['REQUEST_SCHEME']='http'; $_SERVER['HTTP_HOST']=$_SERVER['SERVER_NAME']='figma-htmx-site.test';
$GLOBALS['wp_filter']['pre_option_template'][10][]=array('function'=>static function(){return 'twentytwentyfive';},'accepted_args'=>1);
$GLOBALS['wp_filter']['pre_option_stylesheet'][10][]=array('function'=>static function(){return 'twentytwentyfive';},'accepted_args'=>1);
require dirname(__DIR__,4).'/wp-load.php';
if (!class_exists('Portable_SEO') || !Portable_SEO::enabled() || !preg_match('/(?:\.test|localhost)$/',(string)wp_parse_url(home_url(),PHP_URL_HOST))) exit("Local test site and active plugin required.\n");
if ('twentytwentyfive'!==get_template() || function_exists('rp_option')) {fwrite(STDERR,"Theme override did not isolate Rockpack.\n");exit(1);}
global $wp_query; $id=(int)get_option('page_on_front'); $wp_query=new WP_Query(array('page_id'=>$id));
do_action('wp'); ob_start(); do_action('wp_head'); $html=ob_get_clean();
foreach(array('name="description"','property="og:title"','rel="canonical"','application/ld+json') as $tag) if(substr_count($html,$tag)!==1) {fwrite(STDERR,"Expected one $tag without Rockpack.\n");exit(1);}
echo "PASS: Portable SEO renders metadata, canonical and organization schema with Twenty Twenty-Five and no Rockpack functions.\n";
