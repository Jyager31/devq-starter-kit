<?php

/**
 * What the editing experience assumes about WordPress and ACF, and a check for
 * each assumption.
 *
 * The editor UX in this theme reaches into things neither WordPress nor ACF
 * promises to keep: two CSS class names, a substitution rule inside
 * block-editor.js, ACF's DOM check for the canvas iframe, and ACF writing its
 * field widths as an inline style. None of that is a public API. All of it can
 * change in a minor release, and WP Engine applies those automatically.
 *
 * The danger is not that they change. It is that they change QUIETLY. Every one
 * of these failures looks like nothing: the inspector goes back to 280px, or
 * blocks preview in Times New Roman, or fields sit two-up and clip. The site
 * still works, so nobody files a bug -- the client just finds the editor a bit
 * worse and says nothing, and we find out months later on the next build.
 *
 * So each assumption is written down here with something that proves it. The
 * checks are greps against the files core and ACF actually shipped, which means
 * they run over SSH on any install in the fleet, with no browser:
 *
 *     wp eval-file wp-content/themes/<theme>/scripts/site-health.php
 *
 * Results are cached against a signature of the WordPress version, the ACF
 * version and the theme version, so the work happens once per update -- which is
 * exactly when the answer can change.
 *
 * What this deliberately does NOT do is try to keep working when an assumption
 * breaks. Every one of these degrades into "the editor is less pleasant", never
 * into a broken page: the CSS stops matching, or a JS guard returns early. The
 * job here is to say so out loud.
 *
 * See CLAUDE.md, "After a WordPress or ACF update", for what to do when one of
 * these fails.
 */

/**
 * The last versions this theme's editor UX was actually checked against.
 *
 * Bump these together with a run through the checklist in CLAUDE.md -- not
 * because a check went green, but because someone opened the editor and looked.
 */
define('DEVQ_EDITOR_TESTED_WP', '7.1');
define('DEVQ_EDITOR_TESTED_ACF', '6.7.0.2');


/**
 * Does a file contain a string, without reading the whole thing into memory?
 *
 * block-editor.js is several megabytes. This is called at most once per update,
 * but there is no reason to hold that in memory to answer a yes/no question.
 *
 * @param string $path
 * @param string $needle
 * @return bool
 */
function devq_file_contains($path, $needle)
{
    if (!$path || !is_readable($path)) {
        return false;
    }

    $handle = @fopen($path, 'rb');

    if (!$handle) {
        return false;
    }

    $overlap = strlen($needle);
    $tail    = '';
    $found   = false;

    while (!feof($handle)) {
        $chunk = fread($handle, 262144);

        if ($chunk === false) {
            break;
        }

        if (strpos($tail . $chunk, $needle) !== false) {
            $found = true;
            break;
        }

        // Carry enough of the tail that a match spanning two reads is still seen.
        $tail = substr($chunk, -$overlap);
    }

    fclose($handle);

    return $found;
}


/**
 * The assumptions, and where each one is visible on disk.
 *
 * @return array
 */
function devq_editor_contract_definitions()
{
    $inc = ABSPATH . WPINC;
    $acf = defined('ACF_PATH') ? untrailingslashit(ACF_PATH) : '';

    $checks = array(
        'canvas_is_iframed' => array(
            'label'  => 'Core still iframes the editor canvas',
            'path'   => $inc . '/js/dist/editor.js',
            'needle' => 'shouldIframe',
            'impact' => 'If this goes, canvas editing may be back and this theme is working around a problem that no longer exists. Re-read the notes in CLAUDE.md before building on them.',
        ),
        'canvas_iframe_name' => array(
            'label'  => 'The canvas iframe is still named editor-canvas',
            'path'   => $inc . '/js/dist/block-editor.js',
            'needle' => 'editor-canvas',
            'impact' => 'ACF keys its preview-pinning off this name and core uses it for canvas sizing. A rename changes both behaviours at once.',
        ),
        'root_selector_tokens' => array(
            'label'  => 'Core still substitutes .editor-styles-wrapper for :root',
            'path'   => $inc . '/js/dist/block-editor.js',
            'needle' => 'ROOT_SELECTOR_TOKENS',
            'impact' => 'This is what lets the :root brand tokens in style.css resolve inside the canvas. Without it every block previews unbranded -- serif type, no colours -- while the front end stays correct.',
        ),
        'inspector_sidebar_class' => array(
            'label'  => 'The inspector still uses .interface-interface-skeleton__sidebar',
            'path'   => $inc . '/css/dist/components/style.css',
            'needle' => 'interface-interface-skeleton__sidebar',
            'impact' => 'assets/css/admin-ux.css sizes the panel through this class and assets/js/editor-inspector.js anchors the drag handle to it. If it is renamed the panel silently returns to 280px.',
        ),
        'block_inspector_class' => array(
            'label'  => 'The inspector body still uses .block-editor-block-inspector',
            'path'   => $inc . '/css/dist/block-editor/style.css',
            'needle' => 'block-editor-block-inspector',
            'impact' => 'The sidebar accepts a new width while this inner region stays pinned, so without it the panel grows dead space instead of wider fields.',
        ),
        'acf_pins_preview_on_iframe' => array(
            'label'  => 'ACF still pins blocks to preview when the canvas is an iframe',
            'path'   => $acf ? $acf . '/assets/build/js/pro/acf-pro-blocks.min.js' : '',
            'needle' => 'editor-canvas',
            'impact' => 'If ACF drops this check, fields render in the canvas again and the inspector stops being the only editing surface. Good news, but the docs and the "Edit fields" button would both be describing the old world.',
        ),
        'acf_inline_field_width' => array(
            'label'  => 'ACF still writes field widths as an inline style',
            'path'   => $acf ? $acf . '/includes/acf-field-functions.php' : '',
            'needle' => 'width:{$width}%',
            'impact' => 'The one-field-per-row rule in admin-ux.css needs !important to beat that inline style. If ACF switches to a class the rule is merely redundant -- but if it switches to a grid, the rule needs rewriting.',
        ),
    );

    /**
     * Add a site's own assumptions, or drop one this theme makes.
     *
     * A build that leans on something else core does not promise -- a class it
     * overrides, a plugin's markup -- should register it here rather than
     * finding out from a client. Each entry needs a label, a path, a needle and
     * an impact sentence saying what goes wrong when it stops being true.
     */
    return apply_filters('devq_editor_contract_definitions', $checks);
}


/**
 * The versions the editor UX was last checked against by hand.
 *
 * Filterable so a site pinned to an older stack can say so, rather than warning
 * about drift on every admin page load for a version it will never run.
 *
 * @return array
 */
function devq_editor_tested_versions()
{
    return apply_filters('devq_editor_tested_versions', array(
        'WordPress' => DEVQ_EDITOR_TESTED_WP,
        'ACF'       => DEVQ_EDITOR_TESTED_ACF,
    ));
}


/**
 * A fingerprint of everything that can change the answers.
 *
 * @return string
 */
function devq_editor_contract_signature()
{
    global $wp_version;

    return implode('|', array(
        $wp_version,
        defined('ACF_VERSION') ? ACF_VERSION : 'no-acf',
        wp_get_theme()->get('Version'),
    ));
}


/**
 * Run the checks, or return the cached answer for this exact set of versions.
 *
 * @param bool $force Skip the cache.
 * @return array {
 *     @type string $signature
 *     @type array  $failed    Keys of checks that did not pass.
 *     @type array  $untested  Version drift, as label => [tested, running].
 * }
 */
function devq_editor_contract_results($force = false)
{
    global $wp_version;

    $signature = devq_editor_contract_signature();
    $cached    = get_transient('devq_editor_contract');

    if (!$force && is_array($cached) && isset($cached['signature']) && $cached['signature'] === $signature) {
        return $cached;
    }

    $failed = array();

    foreach (devq_editor_contract_definitions() as $key => $check) {
        if (!devq_file_contains($check['path'], $check['needle'])) {
            $failed[] = $key;
        }
    }

    $tested   = devq_editor_tested_versions();
    $untested = array();

    if (!empty($tested['WordPress']) && version_compare($wp_version, $tested['WordPress'], '!=')) {
        $untested['WordPress'] = array($tested['WordPress'], $wp_version);
    }

    if (!empty($tested['ACF']) && defined('ACF_VERSION') && version_compare(ACF_VERSION, $tested['ACF'], '!=')) {
        $untested['ACF'] = array($tested['ACF'], ACF_VERSION);
    }

    $results = array(
        'signature' => $signature,
        'failed'    => $failed,
        'untested'  => $untested,
        'checked'   => time(),
    );

    // A week is short enough that a hand-edited plugin file gets noticed and long
    // enough that this is not real work. The signature is what actually keeps it
    // honest across an update.
    set_transient('devq_editor_contract', $results, WEEK_IN_SECONDS);

    return $results;
}


/**
 * Tell an administrator, on the screen where it matters, when an assumption has
 * moved under us.
 *
 * Only for users who could act on it. A client seeing "core changed a class
 * name" learns nothing and worries anyway.
 */
function devq_editor_contract_notice()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (!$screen || !in_array($screen->base, array('post', 'dashboard'), true)) {
        return;
    }

    $results = devq_editor_contract_results();

    if (empty($results['failed']) && empty($results['untested'])) {
        return;
    }

    $definitions = devq_editor_contract_definitions();

    echo '<div class="notice notice-warning"><p><strong>' .
        esc_html__('The block editor customisations in this theme need re-checking.', 'devq') .
        '</strong></p>';

    if (!empty($results['untested'])) {
        foreach ($results['untested'] as $what => $versions) {
            printf(
                '<p>%s</p>',
                sprintf(
                    /* translators: 1: WordPress or ACF, 2: tested version, 3: running version. */
                    esc_html__('%1$s was tested at %2$s and is running %3$s.', 'devq'),
                    esc_html($what),
                    esc_html($versions[0]),
                    esc_html($versions[1])
                )
            );
        }
    }

    foreach ($results['failed'] as $key) {
        if (!isset($definitions[$key])) {
            continue;
        }

        printf(
            '<p><strong>%s</strong><br>%s</p>',
            esc_html__('No longer true: ', 'devq') . esc_html($definitions[$key]['label']),
            esc_html($definitions[$key]['impact'])
        );
    }

    echo '<p>' . esc_html__('See "After a WordPress or ACF update" in the theme\'s CLAUDE.md.', 'devq') . '</p></div>';
}
add_action('admin_notices', 'devq_editor_contract_notice');


/**
 * Drop the cached answer whenever core or a plugin updates, so the next admin
 * page load re-checks rather than waiting out the transient.
 */
add_action('upgrader_process_complete', function () {
    delete_transient('devq_editor_contract');
}, 10, 0);
