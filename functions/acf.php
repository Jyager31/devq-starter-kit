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
    // Check function exists.
    if (function_exists('acf_add_options_sub_page')) {

        // Add parent.
        $parent = acf_add_options_page(array(
            'page_title'  => __('Theme General Settings'),
            'menu_title'  => __('Theme Settings'),
            'menu_slug'   => 'theme-general-settings',
            'capability'  => 'edit_posts',
            'redirect'    => true,
            'position'    => 3
        ));

        // Add sub pages with standardized slugs that match our theme-settings directory structure

        // Branding settings
        $child = acf_add_options_sub_page(array(
            'page_title'  => __('Branding'),
            'menu_title'  => __('Branding'),
            'menu_slug'   => 'branding',
            'parent_slug' => $parent['menu_slug'],
        ));

        // Contact settings
        $child = acf_add_options_sub_page(array(
            'page_title'  => __('Contact'),
            'menu_title'  => __('Contact'),
            'menu_slug'   => 'contact',
            'parent_slug' => $parent['menu_slug'],
        ));

        // Social settings
        $child = acf_add_options_sub_page(array(
            'page_title'  => __('Social'),
            'menu_title'  => __('Social'),
            'menu_slug'   => 'social',
            'parent_slug' => $parent['menu_slug'],
        ));

        // Styles settings
        $child = acf_add_options_sub_page(array(
            'page_title'  => __('Styles'),
            'menu_title'  => __('Styles'),
            'menu_slug'   => 'styles',
            'parent_slug' => $parent['menu_slug'],
        ));

        // Scripts settings
        $child = acf_add_options_sub_page(array(
            'page_title'  => __('Scripts'),
            'menu_title'  => __('Scripts'),
            'menu_slug'   => 'scripts',
            'parent_slug' => $parent['menu_slug'],
        ));

        // Layouts settings
        $child = acf_add_options_sub_page(array(
            'page_title'  => __('Layouts'),
            'menu_title'  => __('Layouts'),
            'menu_slug'   => 'layouts',
            'parent_slug' => $parent['menu_slug'],
        ));

        // 404 Page settings
        $child = acf_add_options_sub_page(array(
            'page_title'  => __('404 Page'),
            'menu_title'  => __('404 Page'),
            'menu_slug'   => '404',
            'parent_slug' => $parent['menu_slug'],
        ));
    }
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
