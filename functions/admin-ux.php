<?php

/**
 * Admin usability.
 *
 * Everything here exists for the same reason: WordPress iframes the post editor
 * canvas, ACF sees that iframe and pins every ACF block to preview with no edit
 * toggle, so a client edits a page entirely through the block inspector and List
 * View. Neither was designed for that job. See functions/editor-canvas.php for
 * the mechanics.
 */

/**
 * Screens that are the block editor.
 *
 * @param string $hook Current admin page.
 * @return bool
 */
function devq_is_editor_screen($hook)
{
    return in_array($hook, array('post.php', 'post-new.php'), true);
}


/**
 * Editor and ACF admin styles.
 */
function devq_admin_ux_assets($hook)
{
    $is_acf_screen = strpos($hook, 'theme-general-settings') !== false
        || strpos($hook, 'acf') !== false;

    if (!devq_is_editor_screen($hook) && !$is_acf_screen && $hook !== 'index.php') {
        return;
    }

    $theme_uri = get_template_directory_uri();
    $theme_dir = get_template_directory();

    wp_enqueue_style(
        'devq-admin-ux',
        $theme_uri . '/assets/css/admin-ux.css',
        array(),
        filemtime($theme_dir . '/assets/css/admin-ux.css')
    );

    if (!devq_is_editor_screen($hook)) {
        return;
    }

    // Drag-resize for the inspector, and the "Edit fields" button that opens it
    // wide from the block toolbar. The fields are only reachable in that panel
    // on WP 7.1+, so both are part of the editing experience, not decoration.
    wp_enqueue_script(
        'devq-editor-inspector',
        $theme_uri . '/assets/js/editor-inspector.js',
        array('wp-hooks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-data'),
        filemtime($theme_dir . '/assets/js/editor-inspector.js'),
        true
    );

    // "Add section above / below" on the block toolbar. Core's between-blocks
    // "+" only exists while the pointer is in the gap between two sections, and
    // its Options menu inserts a paragraph rather than opening the section list.
    wp_enqueue_script(
        'devq-editor-insert',
        $theme_uri . '/assets/js/editor-insert.js',
        array('wp-hooks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-data'),
        filemtime($theme_dir . '/assets/js/editor-insert.js'),
        true
    );

    // Does any of the above still work? Admins only -- a client can do nothing
    // with the answer. Silent unless something has moved.
    if (current_user_can('manage_options')) {
        wp_enqueue_script(
            'devq-editor-contract',
            $theme_uri . '/assets/js/editor-contract.js',
            array('wp-data'),
            filemtime($theme_dir . '/assets/js/editor-contract.js'),
            true
        );
    }

    devq_maybe_prime_list_view($theme_uri, $theme_dir);
}
add_action('admin_enqueue_scripts', 'devq_admin_ux_assets');


/**
 * Open List View by default, once, for a user who has not edited here before.
 *
 * The flag is written whether or not the script gets to run, deliberately: this
 * is a first-run nudge, and a user who closes List View must not have it forced
 * back open on their next visit.
 *
 * @param string $theme_uri
 * @param string $theme_dir
 * @return void
 */
function devq_maybe_prime_list_view($theme_uri, $theme_dir)
{
    $user_id = get_current_user_id();

    if (!$user_id || get_user_meta($user_id, 'devq_list_view_primed', true)) {
        return;
    }

    update_user_meta($user_id, 'devq_list_view_primed', 1);

    wp_enqueue_script(
        'devq-editor-onboarding',
        $theme_uri . '/assets/js/editor-onboarding.js',
        array('wp-dom-ready', 'wp-data'),
        filemtime($theme_dir . '/assets/js/editor-onboarding.js'),
        true
    );
}


/**
 * "How to edit your site" dashboard panel.
 *
 * The dashboard is where someone who has lost the thread actually looks, so the
 * instructions live there rather than in a notice they dismiss once and never
 * see again.
 */
function devq_help_dashboard_widget()
{
    if (!current_user_can('edit_posts')) {
        return;
    }

    wp_add_dashboard_widget(
        'devq_help',
        __('How to edit your site', 'devq'),
        'devq_help_dashboard_widget_render'
    );

    // Put it above whatever WordPress and the plugins have already registered.
    global $wp_meta_boxes;

    if (empty($wp_meta_boxes['dashboard']['normal']['core'])) {
        return;
    }

    $core = $wp_meta_boxes['dashboard']['normal']['core'];

    if (!isset($core['devq_help'])) {
        return;
    }

    $ours = array('devq_help' => $core['devq_help']);
    unset($core['devq_help']);

    $wp_meta_boxes['dashboard']['normal']['core'] = array_merge($ours, $core);
}
add_action('wp_dashboard_setup', 'devq_help_dashboard_widget');


/**
 * The panel's content.
 *
 * Written for someone who has never used WordPress. Every step names the thing
 * they click, in the order they click it. Override per site with the
 * devq_help_steps / devq_help_footnote filters rather than editing this.
 */
function devq_help_dashboard_widget_render()
{
    $steps = array(
        sprintf(
            /* translators: %s: keyboard shortcut for List View. */
            __('Go to <strong>Pages</strong> and open the page you want to change. To see the page\'s sections as a list, click the <strong>List View</strong> icon at the top left of the toolbar, or press %s.', 'devq'),
            '<kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>O</kbd>'
        ),
        __('Click a section in that list. Its settings open in the <strong>Block</strong> tab on the right — that panel is where all the text, images and links for that section live.', 'devq'),
        __('Change what you need, then click <strong>Update</strong> at the top right. The middle of the screen is a preview of the section, so it will not let you type into it directly. That is normal.', 'devq'),
        __('To add a new section, click the section it should sit next to. In the small toolbar that appears above it, the two arrow buttons add a section <strong>above</strong> or <strong>below</strong> that one, and a list of sections opens for you to pick from.', 'devq'),
        __('A dashed grey box means that section has no content in it yet. Sections like that do not appear on the live site at all until you fill them in.', 'devq'),
        __('Your logo, phone number, address and social links are the same on every page, so they are set once under <strong>Theme Settings</strong> in the left menu rather than page by page.', 'devq'),
    );

    $steps = apply_filters('devq_help_steps', $steps);

    $footnote = apply_filters(
        'devq_help_footnote',
        __('Stuck, or something does not look right? Email josh@thedevq.com and say which page you were on and what you were trying to change.', 'devq')
    );

    echo '<div class="devq-help"><ol>';

    foreach ($steps as $step) {
        echo '<li>' . wp_kses($step, array(
            'strong' => array(),
            'em'     => array(),
            'kbd'    => array(),
            'code'   => array(),
            'a'      => array('href' => array()),
        )) . '</li>';
    }

    echo '</ol>';

    if ($footnote) {
        echo '<p class="devq-help__footnote">' . wp_kses($footnote, array(
            'strong' => array(),
            'a'      => array('href' => array()),
        )) . '</p>';
    }

    echo '</div>';
}



/* -----------------------------------------------------------------------------
 * ACF tab fields in the block inspector
 *
 * A legacy block's tabs are almost always saved as "placement": "left", which
 * ACF renders as a rail pinned with `position: absolute; left: 0; width: 20%`,
 * with the fields beside it. In a ~265-480px inspector that is a fifth of the
 * panel spent on a strip of stacked buttons, and the fields next to it clip.
 * Since WordPress iframes the canvas, ACF pins every block to preview and this
 * panel is the only way a client reaches the fields at all, so it being usable
 * is not cosmetic.
 *
 * assets/css/admin-ux.css can lay that rail out as horizontal pills, and still does
 * as a fallback -- but it is fighting ACF's own absolute positioning with
 * overrides, and which class carries the reserved gutter has already moved once
 * between ACF versions. Setting the placement properly is the durable fix: ACF
 * prints it as `data-placement` on the tab anchor and its JS builds
 * `.acf-tab-wrap.-top` from that, so a tab told it is top-placed is laid out by
 * ACF's own top-tab CSS with no rail and no gutter to reclaim.
 *
 * The scoping is the whole problem. Left tabs elsewhere in wp-admin have to keep
 * working -- a post edit screen's own field groups, a Theme Settings options
 * page, the field group editor -- and get_current_screen() cannot tell us,
 * because a block's fields are rendered over AJAX and REST where the screen
 * object is absent or belongs to something else. Worse, ACF's own
 * `acf_did_render_block_form` flag is set once and never cleared, and on a post
 * edit screen the block form preloads BEFORE the post's own ACF metaboxes
 * render in the same request -- so keying off it would silently reflow every
 * metabox on the page.
 *
 * What is reliable is the id the form is rendered against. acf_render_block_form()
 * and the fetch-block AJAX endpoint both call acf_render_fields() with the
 * block's own id, which ACF guarantees carries a `block_` prefix
 * (acf_ensure_block_id_prefix()); nothing else in ACF renders fields against an
 * id of that shape. acf/pre_render_fields and acf/render_fields bracket exactly
 * that call, so the flag is on for the block form and off the moment it ends --
 * including for tabs nested inside a group or repeater, which render inside the
 * bracket.
 *
 * acf/prepare_field is render-time only. It is not consulted when ACF loads a
 * field for the field group editor or when it saves one, so this can never write
 * `top` back into a block's field group JSON.
 * -------------------------------------------------------------------------- */

/**
 * One entry per open acf_render_fields() call, each saying whether that call is
 * rendering a block's inspector form.
 *
 * A stack rather than a bool because acf/render_fields fires for every
 * acf_render_fields() call, not just ours, and a nested one must not clear a
 * flag it did not set.
 *
 * @param string $op    'push', 'pop' or 'read'.
 * @param bool   $value Only read by 'push'.
 * @return bool True while any open call is a block form.
 */
function devq_block_form_stack($op = 'read', $value = false)
{
    static $stack = array();

    if ($op === 'push') {
        $stack[] = (bool) $value;
    } elseif ($op === 'pop') {
        array_pop($stack);
    }

    return in_array(true, $stack, true);
}


/**
 * Is this acf_render_fields() call rendering a block's inspector form?
 *
 * @param mixed $post_id The id ACF is rendering the fields against.
 * @return bool
 */
function devq_is_block_form($post_id)
{
    if (is_string($post_id) && strpos($post_id, 'block_') === 0) {
        return true;
    }

    // Belt: the endpoint the inspector calls for a block's form renders nothing
    // else, whatever id it was handed. ACF's own repeater table keys off this
    // same test.
    return doing_action('wp_ajax_acf/ajax/fetch-block');
}


/**
 * Open the bracket.
 *
 * @param array $fields
 * @param mixed $post_id
 * @return array Unmodified.
 */
function devq_open_field_render($fields, $post_id)
{
    devq_block_form_stack('push', devq_is_block_form($post_id));

    return $fields;
}
add_filter('acf/pre_render_fields', 'devq_open_field_render', 10, 2);


/**
 * Close it.
 */
function devq_close_field_render()
{
    devq_block_form_stack('pop');
}
add_action('acf/render_fields', 'devq_close_field_render', 10, 0);


/**
 * Force tabs to the top, inside the block inspector only.
 *
 * @param array|false $field ACF passes false when an earlier filter cancelled
 *                           the render.
 * @return array|false
 */
function devq_tab_placement_top($field)
{
    if (!is_array($field) || !devq_block_form_stack()) {
        return $field;
    }

    $field['placement'] = 'top';

    return $field;
}
add_filter('acf/prepare_field/type=tab', 'devq_tab_placement_top');
