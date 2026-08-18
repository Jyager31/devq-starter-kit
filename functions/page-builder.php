<?php
/**
 * DevQ Page Builder Utility
 *
 * Programmatic page creation with ACF blocks.
 */

/**
 * Get ACF field keys from a block's JSON definition.
 * Parses acfjson/group_{blockname}_block.json and returns field_name => field_key map.
 * For repeater fields, also includes sub_fields mapped as repeater_name_sub_field_name => field_key.
 *
 * @param string $block_name The filtered block name (e.g., 'hero', 'textimage')
 * @return array Associative array of field_name => field_key
 */
function devq_get_acf_field_keys($block_name) {
    $theme_dir = get_template_directory();
    $json_file = $theme_dir . '/acfjson/group_' . $block_name . '_block.json';

    if (!file_exists($json_file)) {
        return array();
    }

    $json = json_decode(file_get_contents($json_file), true);
    if (!$json || !isset($json['fields'])) {
        return array();
    }

    $keys = array();
    foreach ($json['fields'] as $field) {
        // Skip tab fields
        if ($field['type'] === 'tab') {
            continue;
        }

        if (!empty($field['name']) && !empty($field['key'])) {
            $keys[$field['name']] = $field['key'];

            // Handle repeater sub_fields
            if ($field['type'] === 'repeater' && isset($field['sub_fields'])) {
                foreach ($field['sub_fields'] as $sub_field) {
                    if (!empty($sub_field['name']) && !empty($sub_field['key'])) {
                        $keys[$field['name'] . '_' . $sub_field['name']] = $sub_field['key'];
                    }
                }
            }
        }
    }

    return $keys;
}

/**
 * Generate Gutenberg block comment markup for an ACF block.
 *
 * @param string $block_name The filtered block name (e.g., 'hero')
 * @param string $block_id Unique block ID
 * @param array $fields Optional field data to embed in the block comment
 * @param array $field_keys Optional field_name => field_key map
 * @return string The block comment markup
 */
function devq_generate_block_markup($block_name, $block_id, $fields = array(), $field_keys = array()) {
    $data = array();

    // Detect repeater fields from the ACF JSON definition
    $repeater_fields = array();
    $theme_dir = get_template_directory();
    $json_file = $theme_dir . '/acfjson/group_' . $block_name . '_block.json';
    if (file_exists($json_file)) {
        $json = json_decode(file_get_contents($json_file), true);
        if ($json && isset($json['fields'])) {
            foreach ($json['fields'] as $field) {
                if (isset($field['type']) && $field['type'] === 'repeater' && !empty($field['name'])) {
                    $sub_map = array();
                    if (isset($field['sub_fields'])) {
                        foreach ($field['sub_fields'] as $sf) {
                            if (!empty($sf['name']) && !empty($sf['key'])) {
                                $sub_map[$sf['name']] = $sf['key'];
                                // Check for nested repeaters
                                if ($sf['type'] === 'repeater' && isset($sf['sub_fields'])) {
                                    $nested_map = array();
                                    foreach ($sf['sub_fields'] as $nsf) {
                                        if (!empty($nsf['name']) && !empty($nsf['key'])) {
                                            $nested_map[$nsf['name']] = $nsf['key'];
                                        }
                                    }
                                    $sub_map['__nested_' . $sf['name']] = $nested_map;
                                }
                            }
                        }
                    }
                    $repeater_fields[$field['name']] = array(
                        'key' => $field['key'],
                        'sub_fields' => $sub_map,
                    );
                }
            }
        }
    }

    foreach ($fields as $name => $value) {
        // Flatten repeater arrays into ACF's expected format
        if (is_array($value) && isset($repeater_fields[$name])) {
            $rep = $repeater_fields[$name];
            $data[$name] = count($value);
            $data['_' . $name] = $rep['key'];

            foreach ($value as $i => $row) {
                if (!is_array($row)) continue;
                foreach ($row as $sub_name => $sub_value) {
                    $flat_key = $name . '_' . $i . '_' . $sub_name;

                    // Check for nested repeater
                    $nested_key = '__nested_' . $sub_name;
                    if (is_array($sub_value) && isset($rep['sub_fields'][$nested_key])) {
                        $nested_map = $rep['sub_fields'][$nested_key];
                        $data[$flat_key] = count($sub_value);
                        if (isset($rep['sub_fields'][$sub_name])) {
                            $data['_' . $flat_key] = $rep['sub_fields'][$sub_name];
                        }
                        foreach ($sub_value as $ni => $nrow) {
                            if (!is_array($nrow)) continue;
                            foreach ($nrow as $nsub_name => $nsub_value) {
                                $nflat_key = $flat_key . '_' . $ni . '_' . $nsub_name;
                                $data[$nflat_key] = $nsub_value;
                                if (isset($nested_map[$nsub_name])) {
                                    $data['_' . $nflat_key] = $nested_map[$nsub_name];
                                }
                            }
                        }
                    } else {
                        $data[$flat_key] = $sub_value;
                        if (isset($rep['sub_fields'][$sub_name])) {
                            $data['_' . $flat_key] = $rep['sub_fields'][$sub_name];
                        }
                    }
                }
            }
        } else {
            $data[$name] = $value;
            if (isset($field_keys[$name])) {
                $data['_' . $name] = $field_keys[$name];
            }
        }
    }

    $block_attrs = array(
        'id' => $block_id,
        'name' => 'acf/' . $block_name,
        'data' => $data,
        'mode' => 'edit',
    );

    return '<!-- wp:acf/' . $block_name . ' ' . wp_json_encode($block_attrs) . ' /-->';
}

/**
 * Save ACF block field values as postmeta.
 *
 * ACF stores block fields as:
 * - {block_id}_{field_name} = value
 * - _{block_id}_{field_name} = field_key
 *
 * For repeater fields, it also stores:
 * - {block_id}_{repeater_name} = count (number of rows)
 * - {block_id}_{repeater_name}_{index}_{sub_field_name} = value
 * - _{block_id}_{repeater_name}_{index}_{sub_field_name} = sub_field_key
 *
 * @param int $post_id The post ID
 * @param string $block_id The unique block ID
 * @param array $fields Associative array of field_name => value
 * @param string $block_name The filtered block name for key lookups
 */
function devq_save_block_meta($post_id, $block_id, $fields, $block_name) {
    $field_keys = devq_get_acf_field_keys($block_name);

    foreach ($fields as $name => $value) {
        if (is_array($value) && isset($field_keys[$name])) {
            // Check if this is a repeater field by looking at the JSON
            $theme_dir = get_template_directory();
            $json_file = $theme_dir . '/acfjson/group_' . $block_name . '_block.json';
            $json = json_decode(file_get_contents($json_file), true);
            $is_repeater = false;

            if ($json && isset($json['fields'])) {
                foreach ($json['fields'] as $field) {
                    if (isset($field['name'], $field['type']) && $field['name'] === $name && $field['type'] === 'repeater') {
                        $is_repeater = true;
                        break;
                    }
                }
            }

            if ($is_repeater) {
                // Save repeater count
                update_post_meta($post_id, $block_id . '_' . $name, count($value));
                update_post_meta($post_id, '_' . $block_id . '_' . $name, $field_keys[$name]);

                // Save each row
                foreach ($value as $index => $row) {
                    foreach ($row as $sub_name => $sub_value) {
                        $meta_key = $block_id . '_' . $name . '_' . $index . '_' . $sub_name;
                        update_post_meta($post_id, $meta_key, $sub_value);

                        $sub_field_key_name = $name . '_' . $sub_name;
                        if (isset($field_keys[$sub_field_key_name])) {
                            update_post_meta($post_id, '_' . $meta_key, $field_keys[$sub_field_key_name]);
                        }
                    }
                }
                continue;
            }
        }

        // Regular field
        $meta_key = $block_id . '_' . $name;
        update_post_meta($post_id, $meta_key, $value);

        if (isset($field_keys[$name])) {
            update_post_meta($post_id, '_' . $meta_key, $field_keys[$name]);
        }
    }
}

/**
 * Build block content from an array of block definitions.
 *
 * Each block definition should have:
 * - 'name' => block name (human readable like "Hero" or filtered like "hero")
 * - 'fields' => associative array of field_name => value
 *
 * @param array $blocks Array of block definitions
 * @return array ['post_content' => string, 'blocks_meta' => array of [block_id, fields, block_name]]
 */
function devq_build_block_content($blocks) {
    $content_parts = array();
    $blocks_meta = array();

    foreach ($blocks as $block) {
        $name = isset($block['name']) ? $block['name'] : '';
        $fields = isset($block['fields']) ? $block['fields'] : array();

        // Filter the name the same way the theme does
        $filtered_name = strtolower(str_replace(array(' ', '-'), '', $name));

        $block_id = 'block_' . uniqid();
        $field_keys = devq_get_acf_field_keys($filtered_name);

        $content_parts[] = devq_generate_block_markup($filtered_name, $block_id, $fields, $field_keys);

        $blocks_meta[] = array(
            'block_id' => $block_id,
            'fields' => $fields,
            'block_name' => $filtered_name,
        );
    }

    return array(
        'post_content' => implode("\n\n", $content_parts),
        'blocks_meta' => $blocks_meta,
    );
}

/**
 * Create a WordPress page with ACF blocks.
 *
 * @param array $args {
 *     @type string $title    Page title (required)
 *     @type string $slug     Page slug (optional, auto-generated from title)
 *     @type string $status   Post status: 'draft' or 'publish' (default: 'draft')
 *     @type int    $parent   Parent page ID (default: 0)
 *     @type string $template Page template file (default: '')
 *     @type array  $blocks   Array of block definitions, each with 'name' and 'fields'
 * }
 * @return int|WP_Error Post ID on success, WP_Error on failure
 */
function devq_create_page($args) {
    $defaults = array(
        'title' => '',
        'slug' => '',
        'status' => 'draft',
        'parent' => 0,
        'template' => '',
        'blocks' => array(),
    );

    $args = wp_parse_args($args, $defaults);

    if (empty($args['title'])) {
        return new WP_Error('missing_title', 'Page title is required.');
    }

    // Build block content
    $block_data = devq_build_block_content($args['blocks']);

    // Create the page
    $post_data = array(
        'post_title' => sanitize_text_field($args['title']),
        'post_name' => $args['slug'] ? sanitize_title($args['slug']) : sanitize_title($args['title']),
        'post_content' => $block_data['post_content'],
        'post_status' => in_array($args['status'], array('draft', 'publish', 'private'), true) ? $args['status'] : 'draft',
        'post_type' => 'page',
        'post_parent' => absint($args['parent']),
    );

    // wp_insert_post() calls wp_unslash() internally, which strips backslash
    // escapes from JSON (e.g. \" in HTML attributes). Pre-slash to compensate.
    // Also remove kses filters so HTML inside block comment JSON isn't mangled.
    kses_remove_filters();
    $post_data['post_content'] = wp_slash($post_data['post_content']);
    $post_id = wp_insert_post($post_data, true);
    kses_init_filters();

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    // Set page template if provided
    if (!empty($args['template'])) {
        update_post_meta($post_id, '_wp_page_template', $args['template']);
    }

    // Save block meta
    foreach ($block_data['blocks_meta'] as $block_meta) {
        devq_save_block_meta($post_id, $block_meta['block_id'], $block_meta['fields'], $block_meta['block_name']);
    }

    return $post_id;
}

/**
 * REST API endpoint for creating pages (fallback when WP-CLI isn't available).
 */
function devq_register_rest_routes() {
    register_rest_route('devq/v1', '/create-page', array(
        'methods' => 'POST',
        'callback' => 'devq_rest_create_page',
        'permission_callback' => function () {
            return current_user_can('edit_pages');
        },
    ));

    register_rest_route('devq/v1', '/create-menu', array(
        'methods' => 'POST',
        'callback' => 'devq_rest_create_menu',
        'permission_callback' => function () {
            return current_user_can('edit_theme_options');
        },
    ));

    register_rest_route('devq/v1', '/setup-front-page', array(
        'methods' => 'POST',
        'callback' => 'devq_rest_setup_front_page',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
    ));

    register_rest_route('devq/v1', '/site-info', array(
        'methods' => 'GET',
        'callback' => 'devq_rest_site_info',
        'permission_callback' => function () {
            return current_user_can('edit_pages');
        },
    ));
}
add_action('rest_api_init', 'devq_register_rest_routes');

/**
 * REST API callback for page creation.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function devq_rest_create_page($request) {
    $params = $request->get_json_params();

    if (empty($params)) {
        return new WP_REST_Response(array('error' => 'Invalid JSON body'), 400);
    }

    $result = devq_create_page($params);

    if (is_wp_error($result)) {
        return new WP_REST_Response(array('error' => $result->get_error_message()), 400);
    }

    return new WP_REST_Response(array(
        'post_id' => $result,
        'edit_url' => get_edit_post_link($result, 'raw'),
        'view_url' => get_permalink($result),
    ), 201);
}

/**
 * REST API callback for creating a navigation menu from page IDs.
 *
 * Expects JSON body:
 * - menu_name (string, optional) -- defaults to "Primary Menu"
 * - page_ids (array of int) -- ordered list of page IDs for menu items
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function devq_rest_create_menu($request) {
    $params = $request->get_json_params();

    if (empty($params['page_ids']) || !is_array($params['page_ids'])) {
        return new WP_REST_Response(array('error' => 'page_ids array is required'), 400);
    }

    $menu_name = !empty($params['menu_name']) ? sanitize_text_field($params['menu_name']) : 'Primary Menu';
    $menu_exists = wp_get_nav_menu_object($menu_name);

    // Delete existing menu if it exists so we can recreate it
    if ($menu_exists) {
        wp_delete_nav_menu($menu_exists->term_id);
    }

    $menu_id = wp_create_nav_menu($menu_name);
    if (is_wp_error($menu_id)) {
        return new WP_REST_Response(array('error' => $menu_id->get_error_message()), 500);
    }

    $menu_order = 1;
    $items_created = 0;
    foreach ($params['page_ids'] as $page_id) {
        $page_id = absint($page_id);
        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') {
            continue;
        }

        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title'     => get_the_title($page_id),
            'menu-item-object-id' => $page_id,
            'menu-item-object'    => 'page',
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
            'menu-item-position'  => $menu_order,
        ));
        $menu_order++;
        $items_created++;
    }

    // Assign to primary location
    $locations = get_theme_mod('nav_menu_locations');
    if (!is_array($locations)) {
        $locations = array();
    }
    $locations['primary'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);

    return new WP_REST_Response(array(
        'menu_id' => $menu_id,
        'menu_name' => $menu_name,
        'items_created' => $items_created,
    ), 201);
}

/**
 * REST API callback for setting a page as the static front page.
 *
 * Expects JSON body:
 * - page_id (int) -- the page ID to set as front page
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function devq_rest_setup_front_page($request) {
    $params = $request->get_json_params();

    if (empty($params['page_id'])) {
        return new WP_REST_Response(array('error' => 'page_id is required'), 400);
    }

    $page_id = absint($params['page_id']);
    $page = get_post($page_id);

    if (!$page || $page->post_type !== 'page') {
        return new WP_REST_Response(array('error' => 'Invalid page ID'), 404);
    }

    update_option('show_on_front', 'page');
    update_option('page_on_front', $page_id);

    return new WP_REST_Response(array(
        'page_id' => $page_id,
        'title' => get_the_title($page_id),
        'message' => 'Front page set successfully',
    ), 200);
}

/**
 * REST API callback for site info -- returns site URL, active theme and available blocks.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function devq_rest_site_info($request) {
    $blocks = array();
    if (function_exists('devq_get_blocks')) {
        $blocks = devq_get_blocks();
    }


    return new WP_REST_Response(array(
        'site_url' => get_site_url(),
        'theme' => get_stylesheet(),
        'parent_theme' => get_template(),
        'blocks' => $blocks,
    ), 200);
}
