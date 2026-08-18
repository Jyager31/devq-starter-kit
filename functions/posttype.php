<?php

/**
 * Custom post types for this site.
 *
 * Empty by design -- register what this site actually needs. Template:
 *
 * register_post_type('project', array(
 *     'labels'      => array(
 *         'name'          => __('Projects', 'devq'),
 *         'singular_name' => __('Project', 'devq'),
 *     ),
 *     'menu_icon'   => 'dashicons-portfolio',
 *     'public'      => true,
 *     'has_archive' => true,
 *     'rewrite'     => array('slug' => 'projects', 'with_front' => false),
 *     'supports'    => array('title', 'editor', 'thumbnail', 'excerpt'),
 *     'show_in_rest' => true,
 * ));
 *
 * Flush rewrite rules ONCE after adding a CPT (Settings > Permalinks, or
 * `wp rewrite flush`) or its archive 404s. If you imported a database first,
 * flush the object cache BEFORE flushing rewrites.
 *
 * A CPT that exists only to hold data and has no front-end URL should be
 * 'public' => false -- a public one leaks into the sitemap.
 */
function devq_register_post_types()
{
    // register_post_type(...);
}
add_action('init', 'devq_register_post_types');
