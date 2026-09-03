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
