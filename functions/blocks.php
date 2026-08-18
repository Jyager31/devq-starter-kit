<?php

function devq_theme_setup()
{
    add_theme_support('align-wide');
}
add_action('after_setup_theme', 'devq_theme_setup');


function devq_block_categories($categories)
{
    $category_slugs = wp_list_pluck($categories, 'slug');

    $devq_category = array(
        array(
            'slug'  => 'devq',
            'title' => __('DevQ Blocks', 'devq'),
            'icon'  => null,
        )
    );

    return in_array('devq', $category_slugs, true) ? $categories : array_merge($devq_category, $categories);
}

add_filter('block_categories_all', 'devq_block_categories', 1, 1);


function devq_allowed_block_types($allowed_block_types, $editor_context)
{
    if (!empty($editor_context->post) && $editor_context->post->post_type === 'page') {
        $allowed = array();
        foreach (devq_get_blocks() as $name) {
            $allowed[] = 'acf/' . devq_filtername($name);
        }

        // A fresh scaffold has no blocks yet. Returning an empty array here
        // tells WordPress "allow nothing" and leaves the page editor unusable,
        // so fall through to the full block list until this site has its own.
        if (empty($allowed)) {
            return true;
        }

        return $allowed;
    }
    return true;
}
add_filter('allowed_block_types_all', 'devq_allowed_block_types', 10, 2);


function devq_filtername($name)
{
    $name = strtolower($name);
    $name = str_replace(" ", "", $name);
    $name = str_replace("-", "", $name);
    return $name;
}


function devq_get_blocks()
{
    // Deliberately empty. Every site builds its own blocks and registers them
    // through the devq_blocks filter:
    //
    //     add_filter('devq_blocks', function ($blocks) {
    //         $blocks[] = 'Hero Banner';
    //         return $blocks;
    //     });
    //
    // The 30 blocks this theme used to ship live in the toolkit at
    // Commands/block-builder/_library/ and are for post-launch use only --
    // never as a starting point for a new build.
    $blocks = array();

    return apply_filters('devq_blocks', $blocks);
}


function register_acf_block_types()
{

    $icon = '<svg id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 181.05 176.02"><defs><style>.cls-1{fill:#231f20;}.cls-1,.cls-2{stroke-width:0px;}.cls-2{fill:#3bbfad;}</style></defs><path class="cls-1" d="m35.42,61.79c5.87-16.77,21.78-28.82,40.56-28.82,23.75,0,43,19.25,43,43,0,13.12-5.89,24.84-15.15,32.73l27.79,18.98c12.61-13.56,20.33-31.73,20.33-51.7C151.95,34.01,117.93,0,75.97,0,45.91,0,19.93,17.47,7.61,42.81c-.02.05-.05.09-.08.14l27.82,19c.02-.05.04-.1.06-.15Z"/><path class="cls-2" d="m92.3,115.75c-5.04,2.07-10.54,3.23-16.32,3.23-23.75,0-43-19.25-43-43,0-.24.03-.47.04-.71L3.02,54.78c-1.95,6.73-3.02,13.83-3.02,21.19,0,41.96,34.01,75.97,75.97,75.97,10.43,0,20.36-2.12,29.4-5.93l75.67,30-88.75-60.27Z"/></svg>';


    $basefunctions = devq_get_blocks();

    $theme_path = get_template_directory();
    $theme_uri  = get_template_directory_uri();

    foreach ($basefunctions as $name) {
        $filteredname = devq_filtername($name);
        $block_rel = "/blocks/" . $filteredname;

        $args = array(
            'name'              => $filteredname,
            'title'             => __($name, 'devq'),
            'render_template'   => 'blocks/' . $filteredname . '/code.php',
            'category'          => 'devq',
            'icon'              => $icon,
            'mode'              => 'edit',
            'align'             => 'wide',
            'supports'          => array('align' => array('wide', 'full', 'center')),
            'keywords'          => array($name),
            'enqueue_style'     => '',
            'enqueue_script'    => '',
            'example'           => array(
                'attributes' => array(
                    'mode' => 'preview',
                    'data' => array(
                        '__is_preview' => true,
                        'block_name' => $filteredname
                    )
                )
            )
        );

        if (file_exists($theme_path . $block_rel . "/style.css")) {
            $args['enqueue_style'] = $theme_uri . $block_rel . "/style.css";
        }

        if (file_exists($theme_path . $block_rel . "/script.js")) {
            $args['enqueue_script'] = $theme_uri . $block_rel . "/script.js";
        }

        acf_register_block_type($args);
    }
}


// Check if function exists and hook into setup.
if (function_exists('acf_register_block_type')) {
    add_action('acf/init', 'register_acf_block_types');
}


/**
 * Version per-block assets by file mtime.
 *
 * ACF enqueues blocks/<name>/style.css and script.js with ver = ACF_VERSION,
 * which never changes when you edit the file -- so an edit serves stale forever
 * behind a far-future cache header. Stamp the real mtime instead.
 */
function devq_version_block_asset($src)
{
    $theme_uri = get_template_directory_uri();

    if (strpos($src, $theme_uri . '/blocks/') === false) {
        return $src;
    }

    $clean = strtok($src, '?');
    $path  = get_template_directory() . substr($clean, strlen($theme_uri));

    if (!file_exists($path)) {
        return $src;
    }

    return add_query_arg('ver', filemtime($path), $clean);
}
add_filter('style_loader_src', 'devq_version_block_asset', 20);
add_filter('script_loader_src', 'devq_version_block_asset', 20);
