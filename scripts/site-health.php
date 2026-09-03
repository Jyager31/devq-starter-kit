<?php
/**
 * DevQ Site Health Check
 *
 * Audits a running client site for common issues:
 * required plugins, ACF field sync, theme settings,
 * page content, menu setup, and configuration.
 *
 * Usage:
 *   wp eval-file "wp-content/themes/devq-starter/scripts/site-health.php"
 *   wp eval-file "wp-content/themes/devq-starter/scripts/site-health.php" --verbose
 */

$verbose = in_array('--verbose', $args ?? array());
$pass = 0;
$warn = 0;
$fail = 0;

function health_pass($msg) {
    global $pass;
    $pass++;
    WP_CLI::log(WP_CLI::colorize("%G[PASS]%n {$msg}"));
}

function health_warn($msg) {
    global $warn;
    $warn++;
    WP_CLI::log(WP_CLI::colorize("%Y[WARN]%n {$msg}"));
}

function health_fail($msg) {
    global $fail;
    $fail++;
    WP_CLI::log(WP_CLI::colorize("%R[FAIL]%n {$msg}"));
}

function health_info($msg) {
    WP_CLI::log(WP_CLI::colorize("%C[INFO]%n {$msg}"));
}

WP_CLI::log('');
WP_CLI::log(WP_CLI::colorize('%B=== DevQ Site Health Check ===%n'));
WP_CLI::log('');

// ─── 1. Theme Status ─────────────────────────────────────────────────────────

WP_CLI::log(WP_CLI::colorize('%W--- Theme ---%n'));

$active_theme = wp_get_theme();
$parent_theme = $active_theme->parent();
$is_child = (bool) $parent_theme;
$devq_theme = $is_child ? $parent_theme : $active_theme;

// A scaffolded site renames the theme to the client, so identify the kit by its
// own marker rather than by name.
// WP_Theme::get() only exposes known headers, and extra_theme_headers is
// registered too late for the active theme, so read style.css directly.
$devq_kit_stamp = trim((string) get_file_data(
    $devq_theme->get_stylesheet_directory() . '/style.css',
    array('kit' => 'DevQ Kit')
)['kit']);
if ($devq_kit_stamp || strpos(strtolower($devq_theme->get('Name')), 'devq') !== false) {
    health_pass("DevQ kit theme active: {$devq_theme->get('Name')} (v{$devq_theme->get('Version')})"
        . ($devq_kit_stamp ? " from kit {$devq_kit_stamp}" : ''));
} else {
    health_fail('Active theme does not look like a DevQ kit theme');
}

// One theme per site is the intended shape now. A child theme means this site
// predates the kit, or someone reintroduced the split.
if ($is_child) {
    health_warn("Child theme active ({$active_theme->get('Name')}) -- the kit expects a single theme");
} else {
    health_pass('Single theme, no child');
}

// The kit deliberately ships no auto-updater; it is consumed by git clone.
if (class_exists('Puc_v4_Factory') || class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
    health_warn('Theme update checker present -- the kit ships without one; a release could clobber this site');
} else {
    health_pass('No auto-updater (expected)');
}

// blocks/ should contain only blocks built for this site.
$devq_block_names = function_exists('devq_get_blocks') ? devq_get_blocks() : array();
if (empty($devq_block_names)) {
    health_warn('No blocks registered yet -- add them via the devq_blocks filter');
} else {
    health_pass(count($devq_block_names) . ' site block(s) registered: ' . implode(', ', $devq_block_names));
}

WP_CLI::log('');

// ─── 2. Required Plugins ─────────────────────────────────────────────────────

WP_CLI::log(WP_CLI::colorize('%W--- Plugins ---%n'));

$required_plugins = array(
    'advanced-custom-fields-pro/acf.php' => 'ACF Pro',
);

$recommended_plugins = array(
    'gravityforms/gravityforms.php' => 'Gravity Forms',
    'wordpress-seo/wp-seo.php' => 'Yoast SEO',
);

foreach ($required_plugins as $path => $name) {
    if (is_plugin_active($path)) {
        health_pass("{$name} is active");
    } else {
        health_fail("{$name} is NOT active (required)");
    }
}

foreach ($recommended_plugins as $path => $name) {
    if (is_plugin_active($path)) {
        health_pass("{$name} is active");
    } else {
        health_warn("{$name} is not active (recommended)");
    }
}

// Check ACF Pro license
if (function_exists('acf_pro_get_license_key')) {
    $license = acf_pro_get_license_key();
    if ($license) {
        health_pass('ACF Pro license key set');
    } else {
        health_warn('ACF Pro license key not set -- updates may not work');
    }
}

WP_CLI::log('');

// ─── 3. ACF Field Groups ─────────────────────────────────────────────────────

WP_CLI::log(WP_CLI::colorize('%W--- ACF Field Groups ---%n'));

if (function_exists('acf_get_field_groups')) {
    $field_groups = acf_get_field_groups();
    $local_count = 0;
    $db_only = array();

    foreach ($field_groups as $group) {
        if (!empty($group['local'])) {
            $local_count++;
        } else {
            $db_only[] = $group['title'];
        }
    }

    health_pass(count($field_groups) . " field groups loaded ({$local_count} from JSON)");

    if (!empty($db_only)) {
        health_warn(count($db_only) . ' field groups only in database (not version-controlled): ' . implode(', ', array_slice($db_only, 0, 5)));
    }

    // Check for sync-needed groups
    $sync_needed = array();
    foreach ($field_groups as $group) {
        if (!empty($group['local']) && $group['local'] === 'json') {
            $json_modified = !empty($group['modified']) ? $group['modified'] : 0;
            // If the group has local JSON but also exists in DB with different modified time
            if (!empty($group['ID']) && $group['ID'] > 0) {
                // Group exists in both DB and JSON -- might need sync
            }
        }
    }
} else {
    health_fail('ACF not loaded -- cannot check field groups');
}

// Check acfjson directory
$acfjson_dir = get_template_directory() . '/acfjson';
if (is_dir($acfjson_dir)) {
    $json_files = glob($acfjson_dir . '/group_*.json');
    health_pass(count($json_files) . ' ACF JSON files in acfjson/ directory');
} else {
    health_fail('acfjson/ directory not found');
}

WP_CLI::log('');

// ─── 4. Theme Settings ───────────────────────────────────────────────────────

WP_CLI::log(WP_CLI::colorize('%W--- Theme Settings ---%n'));

if (function_exists('get_field')) {
    // Branding
    $logo = get_field('branding_logo', 'option');
    $company_name = get_field('branding_company_name', 'option');
    $favicon = get_field('branding_favicon', 'option');

    if ($logo) {
        health_pass('Logo set');
    } else {
        health_warn('No logo configured (Theme Settings > Branding)');
    }

    if ($company_name) {
        health_pass("Company name: {$company_name}");
    } else {
        health_warn('No company name set (Theme Settings > Branding)');
    }

    if ($favicon) {
        health_pass('Favicon set');
    } else {
        health_warn('No favicon configured (Theme Settings > Branding)');
    }

    // Contact
    $email = get_field('contact_email', 'option');
    $phone = get_field('contact_phone', 'option');

    if ($email) {
        health_pass("Contact email: {$email}");
    } else {
        health_warn('No contact email set (Theme Settings > Contact)');
    }

    if ($phone) {
        health_pass("Contact phone: {$phone}");
    } else {
        health_warn('No contact phone set (Theme Settings > Contact)');
    }


    // Scripts
    $ga = get_field('scripts_google_analytics', 'option');
    $gtm = get_field('scripts_google_tag_manager', 'option');
    if ($ga || $gtm) {
        health_pass('Analytics configured (' . ($ga ? 'GA' : '') . ($ga && $gtm ? ' + ' : '') . ($gtm ? 'GTM' : '') . ')');
    } else {
        health_warn('No analytics configured (GA/GTM)');
    }
} else {
    health_fail('get_field() not available -- ACF not loaded');
}

// ─── Brand tokens ────────────────────────────────────────────────────────────
//
// These moved out of Theme Settings > Styles and into the :root block in
// style.css on 2026-09-03, so this reads the stylesheet rather than ACF. Still
// carrying the kit's shipped default means the site was never taken off the
// scaffold's palette.

// The reference block must not reach a launched site.
if (is_dir(get_template_directory() . '/blocks/kitdemo')) {
    health_fail('blocks/kitdemo/ is still present -- delete it before launch (it is the kit reference block, not a real one)');
} else {
    health_pass('Reference block removed');
}

$style_css = @file_get_contents(get_template_directory() . '/style.css');

if ($style_css === false) {
    health_fail('Could not read style.css');
} else {
    $shipped_defaults = array(
        '--primary'   => '#007bff',
        '--secondary' => '#6c757d',
        '--tertiary'  => '#ffc107',
        '--font1'     => "'Montserrat', sans-serif",
        '--font2'     => "'Open Sans', sans-serif",
    );

    foreach ($shipped_defaults as $token => $default) {
        if (!preg_match('/' . preg_quote($token, '/') . ':\s*([^;]+);/', $style_css, $m)) {
            health_fail("{$token} is not declared in style.css's :root block");
            continue;
        }

        $value = trim($m[1]);

        if ($value === $default) {
            health_warn("{$token} is still the kit default ({$default}) -- set it in style.css");
        } else {
            health_pass("{$token}: {$value}");
        }
    }

    // Fonts are the one thing declared twice -- header.php cannot reach the block
    // editor's iframe, so editor-fonts.css repeats the families as an @import. If
    // they drift, the live page is right and the editor previews in a fallback face.
    $header_php   = @file_get_contents(get_template_directory() . '/header.php');
    $editor_fonts = @file_get_contents(get_template_directory() . '/assets/css/editor-fonts.css');

    if ($header_php === false || $editor_fonts === false) {
        health_warn('Could not compare header.php webfonts against assets/css/editor-fonts.css');
    } else {
        preg_match_all('/family=([A-Za-z0-9+]+)/', $header_php, $hf);
        preg_match_all('/family=([A-Za-z0-9+]+)/', $editor_fonts, $ef);

        $in_header = array_unique($hf[1]);
        $in_editor = array_unique($ef[1]);

        if (!$in_header && !$in_editor) {
            health_info('No Google Fonts declared in header.php -- self-hosted or system stack');
        } elseif (array_diff($in_header, $in_editor) || array_diff($in_editor, $in_header)) {
            health_fail(
                'Webfont drift: header.php has [' . implode(', ', $in_header) . '] but '
                . 'editor-fonts.css has [' . implode(', ', $in_editor) . '] -- '
                . 'the block editor will preview in a fallback face'
            );
        } else {
            health_pass('Webfonts match between header.php and the editor canvas (' . implode(', ', $in_header) . ')');
        }
    }
}

WP_CLI::log('');

// ─── 5. Pages & Content ──────────────────────────────────────────────────────

WP_CLI::log(WP_CLI::colorize('%W--- Pages & Content ---%n'));

// Check front page
$front_page_id = get_option('page_on_front');
$show_on_front = get_option('show_on_front');

if ($show_on_front === 'page' && $front_page_id) {
    $front_page = get_post($front_page_id);
    if ($front_page) {
        health_pass("Static front page set: \"{$front_page->post_title}\" (ID: {$front_page_id})");
    } else {
        health_fail("Front page ID {$front_page_id} does not exist");
    }
} else {
    health_warn('No static front page set -- showing latest posts');
}

// Check published pages
$pages = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'publish',
    'numberposts' => -1,
));

health_info(count($pages) . ' published pages');

$empty_pages = array();
foreach ($pages as $page) {
    $content = trim($page->post_content);
    if (empty($content)) {
        $empty_pages[] = $page->post_title;
    }
}

if (!empty($empty_pages)) {
    health_warn(count($empty_pages) . ' empty pages: ' . implode(', ', $empty_pages));
} else {
    health_pass('All published pages have content');
}

// Check for draft pages
$drafts = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'draft',
    'numberposts' => -1,
));

if (!empty($drafts)) {
    $draft_titles = wp_list_pluck($drafts, 'post_title');
    health_info(count($drafts) . ' draft pages: ' . implode(', ', $draft_titles));
}

WP_CLI::log('');

// ─── 6. Navigation Menu ──────────────────────────────────────────────────────

WP_CLI::log(WP_CLI::colorize('%W--- Navigation ---%n'));

$locations = get_nav_menu_locations();
if (!empty($locations['primary'])) {
    $menu = wp_get_nav_menu_object($locations['primary']);
    if ($menu) {
        $menu_items = wp_get_nav_menu_items($menu->term_id);
        $item_count = $menu_items ? count($menu_items) : 0;
        health_pass("Primary menu: \"{$menu->name}\" ({$item_count} items)");

        // Check for broken menu items
        if ($menu_items) {
            $broken = array();
            foreach ($menu_items as $item) {
                if ($item->type === 'post_type') {
                    $target_post = get_post($item->object_id);
                    if (!$target_post || $target_post->post_status !== 'publish') {
                        $broken[] = $item->title;
                    }
                }
            }
            if (!empty($broken)) {
                health_warn('Broken menu items (link to unpublished/deleted pages): ' . implode(', ', $broken));
            }
        }
    } else {
        health_fail('Primary menu location assigned but menu not found');
    }
} else {
    health_fail('No menu assigned to primary location');
}

WP_CLI::log('');

// ─── 7. Permalinks ───────────────────────────────────────────────────────────

WP_CLI::log(WP_CLI::colorize('%W--- Configuration ---%n'));

$permalink_structure = get_option('permalink_structure');
if ($permalink_structure === '/%postname%/') {
    health_pass('Permalinks set to /%postname%/');
} elseif (!empty($permalink_structure)) {
    health_warn("Permalinks set to: {$permalink_structure} (expected /%postname%/)");
} else {
    health_fail('Permalinks using default (plain) -- SEO unfriendly');
}

// Check site visibility
if (get_option('blog_public') == '0') {
    health_warn('Search engines are BLOCKED (Settings > Reading > Discourage search engines)');
} else {
    health_pass('Site is visible to search engines');
}

// Check SSL
$site_url = get_option('siteurl');
if (strpos($site_url, 'https://') === 0) {
    health_pass('Site URL uses HTTPS');
} else {
    health_warn('Site URL does not use HTTPS');
}

WP_CLI::log('');

// ─── 8. PHP Error Log ────────────────────────────────────────────────────────

WP_CLI::log(WP_CLI::colorize('%W--- Error Log ---%n'));

$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $log_size = filesize($error_log);
    $log_size_kb = round($log_size / 1024, 1);

    if ($log_size > 1048576) { // > 1MB
        health_warn("PHP error log is large ({$log_size_kb}KB): {$error_log}");
    } elseif ($log_size > 0) {
        health_info("PHP error log: {$log_size_kb}KB at {$error_log}");
    } else {
        health_pass('PHP error log is empty');
    }

    // Show last few errors if verbose
    if ($verbose && $log_size > 0) {
        $lines = array_slice(file($error_log), -10);
        WP_CLI::log('  Last errors:');
        foreach ($lines as $line) {
            WP_CLI::log('  ' . trim($line));
        }
    }
} else {
    health_info('PHP error log not found or not configured');
}

// Check WP_DEBUG
if (defined('WP_DEBUG') && WP_DEBUG) {
    health_info('WP_DEBUG is ON');
} else {
    health_pass('WP_DEBUG is OFF (production)');
}

WP_CLI::log('');

// ─── Summary ─────────────────────────────────────────────────────────────────

WP_CLI::log(WP_CLI::colorize('%B=== Summary ===%n'));
WP_CLI::log('');

$total = $pass + $warn + $fail;
WP_CLI::log(WP_CLI::colorize("%G{$pass} passed%n  |  %Y{$warn} warnings%n  |  %R{$fail} failures%n  |  {$total} total checks"));
WP_CLI::log('');

if ($fail > 0) {
    WP_CLI::error("Site has {$fail} issue(s) that need attention.", false);
} elseif ($warn > 0) {
    WP_CLI::warning("Site is functional but has {$warn} warning(s) to review.");
} else {
    WP_CLI::success('All checks passed!');
}
