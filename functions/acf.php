<?php

/**
 * Theme Settings with ACF
 * 
 * This file registers ACF options pages for theme settings
 * and enables ACF Local JSON sync functionality.
 */

/**
 * ACF Local JSON - Auto Sync System
 * 
 * This enables automatic export/import of ACF field groups
 * for version control and team collaboration.
 */

// Set custom save path for ACF JSON files
add_filter('acf/settings/save_json', 'devq_acf_json_save_point');
function devq_acf_json_save_point($path)
{
    $path = get_template_directory() . '/acfjson';

    if (!file_exists($path)) {
        wp_mkdir_p($path);
    }

    return $path;
}

// Set custom load path for ACF JSON files
add_filter('acf/settings/load_json', 'devq_acf_json_load_point');
function devq_acf_json_load_point($paths)
{
    // This theme owns its field groups outright; drop ACF's defaults so a
    // stray acf-json/ elsewhere can never shadow acfjson/.
    return array(get_template_directory() . '/acfjson');
}

// Debug function to check if Local JSON is working (only when WP_DEBUG is enabled)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_notices', 'devq_acf_local_json_debug');
}
function devq_acf_local_json_debug()
{
    // Only show to administrators and only on ACF pages
    if (!current_user_can('manage_options') || !function_exists('acf_get_field_groups')) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'acf') === false) {
        return;
    }

    $save_path = apply_filters('acf/settings/save_json', '');
    $load_paths = apply_filters('acf/settings/load_json', array());

    echo '<div class="notice notice-info"><p>';
    echo '<strong>ACF Local JSON Debug:</strong><br>';
    echo 'Save Path: ' . $save_path . '<br>';
    echo 'Load Paths: ' . implode(', ', $load_paths) . '<br>';
    echo 'Directory Exists: ' . (file_exists($save_path) ? 'Yes' : 'No') . '<br>';
    echo 'Directory Writable: ' . (is_writable($save_path) ? 'Yes' : 'No');
    echo '</p></div>';
}

/**
 * Theme Settings Options Pages
 */

add_action('acf/init', 'devq_acf_op_init');
function devq_acf_op_init()
{
    if (!function_exists('acf_add_options_sub_page')) {
        return;
    }

    // Add parent. edit_posts so the menu itself is visible to a client Editor --
    // the lock is applied per sub page below, not here.
    $parent = acf_add_options_page(array(
        'page_title'  => __('Theme General Settings'),
        'menu_title'  => __('Theme Settings'),
        'menu_slug'   => 'theme-general-settings',
        'capability'  => 'edit_posts',
        'redirect'    => true,
        'position'    => 3
    ));

    foreach (devq_theme_settings_pages() as $slug => $page) {
        acf_add_options_sub_page(array(
            'page_title'  => __($page['title']),
            'menu_title'  => __($page['title']),
            'menu_slug'   => $slug,
            'parent_slug' => $parent['menu_slug'],
            'capability'  => $page['capability'],
        ));
    }
}


/**
 * The Theme Settings sub pages, and who is allowed to open each one.
 *
 * Deliberately short. A setting earns a place here only if it is something that
 * legitimately CHANGES after launch and is not a design decision: a new phone
 * number, a new Instagram account, a swapped logo, a marketing tag. Everything
 * that describes how the site LOOKS is code -- built once against the approved
 * design, same as header.php and footer.php.
 *
 * Removed 2026-09-03, and worth knowing why before adding anything back:
 *   Styles   26 fields of colour, type, spacing and button geometry. Now the
 *            :root block in style.css. As an options page it was a switch that
 *            restyled every template on the site, sitting on a client's account.
 *   Layouts  picked between template-parts/{archive,single}/style-*.php. Same
 *            pattern as the header/footer style variants already deleted -- a
 *            designed thing behind a dropdown. archive.php and single.php are
 *            now written to spec per site.
 *   404 Page a 404 is a designed page. 404.php is written to spec.
 *
 * A sub page does NOT inherit its parent's capability. Set it on the parent alone
 * and every child stays wide open -- which is how an account handed out for
 * content work ends up able to inject JavaScript into every page on the site.
 *
 *   edit_posts     content a client maintains
 *   manage_options anything that can re-plumb the whole site
 *
 * A site can move a page either way:
 *
 *     add_filter('devq_theme_settings_pages', function ($pages) {
 *         $pages['scripts']['capability'] = 'edit_posts';
 *         return $pages;
 *     });
 *
 * @return array
 */
function devq_theme_settings_pages()
{
    $pages = array(
        'branding' => array('title' => 'Branding', 'capability' => 'edit_posts'),
        'contact'  => array('title' => 'Contact',  'capability' => 'edit_posts'),
        'social'   => array('title' => 'Social',   'capability' => 'edit_posts'),
        'scripts'  => array('title' => 'Scripts',  'capability' => 'manage_options'),
    );

    return apply_filters('devq_theme_settings_pages', $pages);
}



function devq_acf_admin_head()
{
?>
    <style type="text/css">
        .acf-flexible-content .layout .acf-fc-layout-handle {
            /*background-color: #00B8E4;*/
            background-color: #202428;
            color: #eee;
        }

        .acf-repeater.-row>table>tbody>tr>td,
        .acf-repeater.-block>table>tbody>tr>td {
            border-top: 5px solid #202428;
        }

        .acf-repeater .acf-row-handle {
            vertical-align: top !important;
            padding-top: 16px;
        }

        .acf-repeater .acf-row-handle span {
            font-size: 20px;
            font-weight: bold;
            color: #202428;
        }

        .imageUpload img {
            width: 75px;
        }

        .acf-repeater .acf-row-handle .acf-icon.-minus {
            top: 30px;
        }

        .acf-repeater.-row>table>tbody>tr:nth-child(2n)>td,
        .acf-repeater.-block>table>tbody>tr:nth-child(2n)>td,
        .acf-repeater.-row>table>tbody>tr:nth-child(2n)>td tr>td,
        .acf-repeater.-block>table>tbody>tr:nth-child(2n)>td tr>td {
            border-top: 5px solid #000000;
            background: #ececec;
        }

        .acf-repeater.-row>table>tbody>tr:nth-child(2n) .acf-row-handle span,
        .acf-repeater.-block>table>tbody>tr:nth-child(2n) .acf-row-handle span,
        .acf-repeater.-row>table>tbody>tr:nth-child(2n)>td .acf-row-handle span,
        .acf-repeater.-block>table>tbody>tr:nth-child(2n)>td .acf-row-handle span {
            color: #46474A;
        }

        .removal1 td.acf-fields,
        .removal1 .-block>table>tbody>tr>td {
            border-top: 0 !important;
        }
    </style>
<?php
}

add_action('acf/input/admin_head', 'devq_acf_admin_head');
