<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ABSPATH' ) ) { exit; }
require_once dirname(__DIR__) . '/flakflak-wp.php';
$GLOBALS['ff_check_count'] = 0;
function ff_assert($ok, $label) {
    if (!$ok) throw new RuntimeException($label);
    $GLOBALS['ff_check_count']++;
}
$uuid = '729aedb2-760b-49a4-ab25-f3faec603907';
$code = '<script src="https://embed.flakflak.com/widget.js" data-site-id="'.$uuid.'" defer></script>';
$settings = ['embed_code' => $code, 'visibility' => 'all', 'post_ids' => []];
add_filter('pre_option_flakflak_wp_settings', function() use (&$settings) { return $settings; });
$embed = FlakFlak_WP_Plugin::parse_embed($code);
ff_assert(!is_wp_error($embed) && $embed['site_id'] === $uuid, 'Valid production embed accepted');
$local = FlakFlak_WP_Plugin::parse_embed('<script src="http://embed-flakflak.localhost/widget.js" data-site-id="'.$uuid.'" data-api-url="http://api-flakflak.localhost/api/v1" defer></script>');
ff_assert(!is_wp_error($local) && $local['api_url'] === 'http://api-flakflak.localhost/api/v1', 'Local URLs and optional API URL accepted');
foreach (['', '<script>alert(1)</script>', $code.$code, '<script src="javascript:alert(1)" data-site-id="'.$uuid.'"></script>', '<script src="https://embed.flakflak.com/widget.js"></script>', '<img src=x onerror=alert(1)>'.$code] as $bad) {
    ff_assert(is_wp_error(FlakFlak_WP_Plugin::parse_embed($bad)), 'Malformed or unsafe code rejected');
}
$valid = FlakFlak_WP_Plugin::sanitize_settings(['embed_code' => $code, 'visibility' => 'specific', 'post_ids' => '123, 456, 123']);
ff_assert($valid['post_ids'] === [123,456], 'Post IDs normalized and deduplicated');
foreach (['', '0', '-1', '123, nope', '123,,456', '1.5', '999999999999999999999999999999'] as $bad) {
    ff_assert(FlakFlak_WP_Plugin::sanitize_settings(['embed_code' => $code, 'visibility' => 'specific', 'post_ids' => $bad]) === $settings, 'Invalid IDs retain prior settings');
}
ff_assert(FlakFlak_WP_Plugin::sanitize_settings(['embed_code' => '', 'visibility' => 'all']) === $settings, 'Required embed retains prior settings on blank');
$stripped = FlakFlak_WP_Plugin::sanitize_settings(['embed_code' => str_replace(' defer', ' onload="alert(1)" data-extra="x" defer', $code), 'visibility' => 'all']);
ff_assert(strpos($stripped['embed_code'], 'onload') === false && strpos($stripped['embed_code'], 'data-extra') === false, 'Only supported embed attributes are retained');
function ff_query($kind, $id = 0) {
    $GLOBALS['wp_query'] = new WP_Query();
    $GLOBALS['wp_query']->queried_object_id = $id;
    $GLOBALS['wp_query']->queried_object = (object) ['ID' => $id];
    $GLOBALS['wp_query']->is_singular = $kind === 'singular';
    $GLOBALS['wp_query']->is_home = $kind === 'home';
    $GLOBALS['wp_query']->is_404 = $kind === '404';
    $GLOBALS['wp_query']->is_feed = $kind === 'feed';
    $GLOBALS['wp_query']->is_embed = $kind === 'embed';
    $GLOBALS['wp_query']->is_category = $kind === 'archive';
}
ff_query('singular', 123);
ff_assert(FlakFlak_WP_Plugin::should_load(), 'All-pages mode loads on a singular page');
ff_query('archive', 123);
ff_assert(FlakFlak_WP_Plugin::should_load(), 'All-pages mode loads on archives');
$settings = $valid;
ff_query('singular', 123);
ff_assert(FlakFlak_WP_Plugin::should_load(), 'Matching post ID loads');
ff_query('singular', 789);
ff_assert(!FlakFlak_WP_Plugin::should_load(), 'Unlisted post ID excluded');
ff_query('archive', 123);
ff_assert(!FlakFlak_WP_Plugin::should_load(), 'Matching term ID does not count as a post ID');
ff_query('home', 456);
ff_assert(FlakFlak_WP_Plugin::should_load(), 'Configured posts page can be targeted');
foreach (['404', 'feed', 'embed'] as $kind) {
    ff_query($kind, 123);
    ff_assert(!FlakFlak_WP_Plugin::should_load(), 'Non-page views excluded');
}
ff_query('singular', 123);
FlakFlak_WP_Plugin::enqueue_embed();
FlakFlak_WP_Plugin::enqueue_embed();
ff_assert(count(array_keys(wp_scripts()->queue, FlakFlak_WP_Plugin::HANDLE, true)) === 1, 'Embed enqueues once');
ff_assert(wp_scripts()->get_data(FlakFlak_WP_Plugin::HANDLE, 'group') === 1, 'Embed loads in footer');
$tag = FlakFlak_WP_Plugin::script_tag('<script src="https://embed.flakflak.com/widget.js" nonce="keep-me"></script>', FlakFlak_WP_Plugin::HANDLE, '');
ff_assert(strpos($tag, 'data-site-id="'.$uuid.'"') !== false && strpos($tag, 'defer') !== false, 'Generated script contains project ID and defer');
ff_assert(strpos($tag, 'nonce="keep-me"') !== false, 'Existing nonce preserved');
ff_assert(FlakFlak_WP_Plugin::script_tag('unchanged', 'another-plugin', '') === 'unchanged', 'Unrelated scripts unchanged');
$settings['embed_code'] = '';
ff_assert(!FlakFlak_WP_Plugin::should_load(), 'Unconfigured plugin does not load');
$settings = ['embed_code' => $code, 'visibility' => 'all', 'post_ids' => []];
require_once ABSPATH . 'wp-admin/includes/template.php';
FlakFlak_WP_Plugin::register_settings();
add_filter('user_has_cap', function($caps) { $caps['manage_options'] = true; return $caps; });
$GLOBALS['wp_settings_errors'] = [];
ob_start();
FlakFlak_WP_Plugin::settings_page();
$html = ob_get_clean();
ff_assert(strpos($html, 'action="options.php"') !== false && strpos($html, '_wpnonce') !== false, 'Standard protected settings form rendered');
ff_assert(strpos($html, 'flakflak-embed-code') !== false && strpos($html, 'required') !== false, 'Required embed field rendered');
ff_assert(strpos($html, 'value="all"  checked=') !== false || preg_match('/value="all"[^>]*checked/', $html), 'All pages is selected by default');
ff_assert(strpos($html, 'flakflak-post-ids') !== false, 'Post-ID field rendered');
echo $GLOBALS['ff_check_count']." checks passed; no WordPress settings changed.\n";
