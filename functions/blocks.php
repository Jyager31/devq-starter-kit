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
            'icon'              => devq_block_icon($filteredname, $icon),
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


/**
 * The inserter / List View icon for a block.
 *
 * Every block sharing the DevQ mark is fine in the inserter and useless in List
 * View, which is now the only way a client moves between blocks: WordPress iframes
 * the editor canvas, ACF sees that iframe and pins every block to preview with no
 * edit toggle, so selecting a block in List View is how its fields get opened.
 * Ten identical rows named by title alone is a slow read.
 *
 * Drop an SVG at blocks/<block>/icon.svg to give a block its own. Anything without
 * one keeps the DevQ mark.
 *
 * @param string $filteredname Block folder name.
 * @param string $default      Fallback icon markup.
 * @return string
 */
function devq_block_icon($filteredname, $default)
{
    $path = get_template_directory() . '/blocks/' . $filteredname . '/icon.svg';

    if (!file_exists($path)) {
        return $default;
    }

    $svg = file_get_contents($path);

    // ACF prints the icon markup straight into the editor UI, so anything with a
    // script in it would run there. Take the file only if it is a plain SVG.
    if (!$svg || stripos($svg, '<svg') === false || stripos($svg, '<script') !== false) {
        return $default;
    }

    return $svg;
}


/**
 * Is this render happening inside the editor rather than on the page?
 *
 * ACF renders block previews over its own admin-ajax endpoint, and the core block
 * renderer comes in over REST. Neither is a front-end request.
 *
 * @return bool
 */
function devq_in_block_editor()
{
    if (is_admin()) {
        return true;
    }

    return defined('REST_REQUEST') && REST_REQUEST;
}


/**
 * Editor-only stand-in for a block that has nothing to render yet.
 *
 * A block whose repeater is empty prints nothing on the front end -- not the
 * heading, not the wrapper, nothing -- which is correct output and a terrible
 * editing experience: the section is invisible on the live page AND invisible in
 * the editor, so nobody can see that it exists and is waiting on content. On
 * Bellco two home page blocks sat empty through a full client review because
 * neither of them left a mark anywhere.
 *
 * Call it in place of the block markup, then return:
 *
 *     if (empty($applications)) {
 *         devq_block_placeholder('Application Carousel', 'Add at least one case study.');
 *         return;
 *     }
 *
 * Prints nothing on the front end, so the block stays silent there.
 *
 * @param string $title   The block's name, as the client sees it in List View.
 * @param string $message What they need to add. Say the field, not the concept.
 * @return void
 */
function devq_block_placeholder($title, $message = '')
{
    if (!devq_in_block_editor()) {
        return;
    }

    if ($message === '') {
        $message = __('This section is empty. Add content to its fields in the Block panel on the right and it will appear here.', 'devq');
    }

    printf(
        '<div class="devq-editor-placeholder"><span class="devq-editor-placeholder__title">%s</span><span class="devq-editor-placeholder__message">%s</span></div>',
        esc_html($title),
        esc_html($message)
    );
}
