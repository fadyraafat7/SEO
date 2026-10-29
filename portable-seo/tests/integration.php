<?php
/** Run locally from the WordPress root: php wp-content/plugins/portable-seo/tests/integration.php */
if ( PHP_SAPI !== 'cli' ) exit(2);
$_SERVER['REQUEST_SCHEME']='http'; $_SERVER['HTTP_HOST']=$_SERVER['SERVER_NAME']='figma-htmx-site.test';
require dirname(__DIR__,4) . '/wp-load.php';
if (!class_exists('Portable_SEO') || !Portable_SEO::enabled() || !preg_match('/(?:\.test|localhost)$/', (string)wp_parse_url(home_url(),PHP_URL_HOST))) exit("Active Portable SEO and a local test site required.\n");
global $wpdb;
foreach (array($wpdb->posts,$wpdb->postmeta,$wpdb->options) as $table) { $status=$wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s',$wpdb->esc_like($table))); if (!$status || 'InnoDB' !== $status->Engine) exit("InnoDB required.\n"); }
remove_action('transition_post_status',array('Rockpack_Static_SSG','on_transition_post_status'),10);
remove_action('wp_after_insert_post',array('Rockpack_Static_SSG','on_page_structure_change'),10);
$admin=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID')); if (!$admin) exit("Local administrator required.\n");
$count=0; $failed=false; $check=static function($condition,$message)use(&$count){ if(!$condition) throw new RuntimeException($message); $count++; echo "PASS $message\n"; };
$wpdb->query('START TRANSACTION');
try {
    wp_set_current_user($admin[0]);
    $id=wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>'Portable SEO fixture','post_content'=>'Actual content for the search description.'),true);
    if(is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    update_post_meta($id,'_rp_seo_title','Legacy title'); update_post_meta($id,'_rp_seo_noindex','1');
    $check(Portable_SEO::meta($id,'title')==='Legacy title' && Portable_SEO::noindex($id),'legacy Rockpack values remain available');
    $_POST=array('portable_seo_nonce'=>wp_create_nonce('portable_seo_'.$id),'portable_seo_title'=>'Custom <b>title</b>','portable_seo_description'=>'A real description','portable_seo_image'=>'https://example.com/image.jpg');
    Portable_SEO::save_box($id);
    $check(Portable_SEO::meta($id,'title')==='Custom title' && !Portable_SEO::noindex($id),'editor saves sanitized values and can clear legacy noindex');
    $check(Portable_SEO::meta($id,'image')==='https://example.com/image.jpg','per-page image is saved');
    $_POST['portable_seo_nonce']='invalid'; $_POST['portable_seo_title']='Should not save'; Portable_SEO::save_box($id);
    $check(Portable_SEO::meta($id,'title')==='Custom title','invalid nonce cannot modify metadata');
    wp_set_current_user(0); $_POST['portable_seo_nonce']=wp_create_nonce('portable_seo_'.$id); Portable_SEO::save_box($id);
    $check(Portable_SEO::meta($id,'title')==='Custom title','anonymous user cannot modify metadata');
    wp_set_current_user($admin[0]);
    $settings=Portable_SEO::sanitize_settings(array('description'=>'<b>Helpful text</b>','image'=>'javascript:alert(1)','socials'=>"https://example.com/profile\njavascript:alert(1)"));
    $check($settings['description']==='Helpful text' && $settings['image']==='' && $settings['socials']==='https://example.com/profile','settings sanitize text and reject unsafe URLs');
    $args=Portable_SEO::sitemap_args(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1));
    $query=new WP_Query($args); $check(in_array($id,wp_list_pluck($query->posts,'ID'),true),'cleared noindex overrides legacy value in sitemap');
    update_post_meta($id,'_portable_seo_noindex','1'); $query=new WP_Query($args);
    $check(!in_array($id,wp_list_pluck($query->posts,'ID'),true),'noindex excludes content from sitemap');
    $combined=Portable_SEO::sitemap_args(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'meta_query'=>array('relation'=>'OR',array('key'=>'_portable_seo_title','compare'=>'EXISTS'),array('key'=>'_unused','compare'=>'NOT EXISTS'))));
    $check(!in_array($id,wp_list_pluck((new WP_Query($combined))->posts,'ID'),true),'noindex remains effective with an existing OR meta query');
    // Remove all theme adapters to exercise the plugin with plain WordPress values.
    foreach(array('portable_seo_legacy_meta','portable_seo_default_home_title','portable_seo_default_description','portable_seo_default_image','portable_seo_default_organization_logo','portable_seo_default_socials','portable_seo_organization_schema','portable_seo_section_description','portable_seo_document_title','portable_seo_canonical_url','portable_seo_sitemap_noindex_query') as $hook) remove_all_filters($hook);
    global $wp_query; $old=$wp_query; $wp_query=new WP_Query(array('p'=>$id,'post_type'=>'post'));
    update_post_meta($id,'_portable_seo_noindex','0'); update_post_meta($id,'_portable_seo_title',''); update_post_meta($id,'_portable_seo_description','');
    $check(false !== strpos(Portable_SEO::description(),'Actual content'),'plain WordPress content is used for description without theme adapters');
    $check(Portable_SEO::canonical()===get_permalink($id),'plain WordPress canonical resolves the permalink');
    ob_start(); Portable_SEO::metadata(); $head=ob_get_clean();
    $check(substr_count($head,'property="og:title"')===1 && substr_count($head,'name="description"')===1,'portable metadata renders once without theme helpers');
    ob_start(); Portable_SEO::breadcrumbs(array(array('name'=>'Home','url'=>home_url('/')),array('name'=>'Example </script>','url'=>get_permalink($id)))); $json=ob_get_clean();
    $check(false !== strpos($json,'BreadcrumbList') && false === strpos($json,'Example </script>'),'breadcrumb JSON safely escapes HTML');
    $wp_query=$old; echo "$count Portable SEO checks passed.\n";
} catch(Throwable $e) { $failed=true; fwrite(STDERR,'FAIL '.$e->getMessage()."\n"); }
finally { $_POST=array(); $wpdb->query('ROLLBACK'); }
exit($failed?1:0);
