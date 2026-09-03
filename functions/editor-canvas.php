<?php

/**
 * Block editor canvas.
 *
 * WordPress renders the post editor canvas inside an <iframe name="editor-canvas">.
 * Two things follow, and both land on the client:
 *
 * 1. The iframe inherits nothing from wp-admin and nothing from the front end. ACF's
 *    per-block enqueue_style does not reach it either. Without this file every DevQ
 *    block previews as unstyled serif HTML and the page looks broken.
 *
 * 2. ACF pins every block to PREVIEW mode when that iframe exists and removes the
 *    edit/preview toggle. The check is in acf-pro-blocks.min.js, downstream of
 *    anything PHP hands it -- 'mode' => 'edit' does not change it, and neither does
 *    a saved "mode":"edit" on the instance. There is no setting that turns it off.
 *    Core stopped gating the iframe on block api_version in WP 7.0, so this is now
 *    every page rather than just patterns and query loops.
 *
 * So a block's fields live only in the inspector, and the canvas is a preview the
 * client reads. Making that preview correct is the whole job.
 *
 * Everything registered through add_editor_style() has its selectors rewritten to
 * sit under .editor-styles-wrapper, so front-end CSS cannot leak onto the admin UI.
 * A selector STARTING with :root, html or body is substituted rather than nested --
 * core's ROOT_SELECTOR_TOKENS in block-editor.js -- which is what lets style.css's
 * :root token block resolve inside the canvas untouched. Relative url() is rebased
 * against each file's own directory, so images and fonts in these sheets resolve.
 *
 * In wp-admin this same list is appended to TinyMCE's content_css
 * (class-wp-editor.php), so ACF WYSIWYG fields pick up the tokens for free.
 */

/**
 * Register the canvas stylesheets.
 *
 * Order matters: tokens and fonts first, then the theme, then blocks, then the
 * editor-only overrides last so they can undo front-end behaviour.
 */
function devq_editor_canvas_styles()
{
    add_theme_support('editor-styles');

    $styles = array(
        'assets/css/editor-fonts.css',
        'assets/css/reflex.css',
        'style.css',
    );

    foreach (devq_editor_block_stylesheets() as $block_style) {
        $styles[] = $block_style;
    }

    $styles[] = 'assets/css/editor-canvas.css';

    add_editor_style($styles);
}
add_action('after_setup_theme', 'devq_editor_canvas_styles', 20);

/**
 * Theme-relative paths to the stylesheet of every REGISTERED block.
 *
 * Reads the registration list rather than globbing blocks/*\/style.css. A glob
 * also sweeps up scratch folders that were never registered, and those have
 * historically carried debug rules -- an h1 { color: red } that turns the post
 * title red -- straight into the client's editor.
 *
 * @return array
 */
function devq_editor_block_stylesheets()
{
    if (!function_exists('devq_get_blocks')) {
        return array();
    }

    $theme_path = get_template_directory();
    $found      = array();

    foreach (devq_get_blocks() as $name) {
        $rel = 'blocks/' . devq_filtername($name) . '/style.css';

        if (file_exists($theme_path . '/' . $rel)) {
            $found[] = $rel;
        }
    }

    return $found;
}
