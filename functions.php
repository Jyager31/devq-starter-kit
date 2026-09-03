<?php

/**
 * DevQ Base Theme functions and definitions.
 */

if (!function_exists('devqbase_setup')) :

	function devqbase_setup()
	{

		load_theme_textdomain('devq', get_template_directory() . '/languages');

		add_theme_support('automatic-feed-links');

		add_theme_support('title-tag');

		add_theme_support('post-thumbnails');

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus(array(
			'primary' => esc_html__('Primary', 'devq'),
			'footer'  => esc_html__('Footer', 'devq'),
		));

		add_theme_support('html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		));

		add_theme_support('post-formats', array(
			'aside',
			'image',
			'video',
			'quote',
			'link',
		));
	}
endif;
add_action('after_setup_theme', 'devqbase_setup');

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function devqbase_content_width()
{
	$GLOBALS['content_width'] = apply_filters('devqbase_content_width', 640);
}
add_action('after_setup_theme', 'devqbase_content_width', 0);


require get_template_directory() . '/functions/acf.php';
require get_template_directory() . '/functions/spacing.php';
require get_template_directory() . '/functions/blocks.php';
require get_template_directory() . '/functions/posttype.php';
require get_template_directory() . '/functions/scripts.php';
require get_template_directory() . '/functions/shortcodes.php';
require get_template_directory() . '/functions/navwalker.php';
require get_template_directory() . '/functions/emailnotifications.php';
require get_template_directory() . '/functions/page-builder.php';
require get_template_directory() . '/functions/animations.php';

// Client-facing editing experience. Both files assume the editor canvas is an
// iframe and ACF blocks are therefore preview-only -- read the header of
// functions/editor-canvas.php before changing either.
require get_template_directory() . '/functions/editor-canvas.php';
require get_template_directory() . '/functions/admin-ux.php';


function is_blog()
{
	return (is_archive() || is_author() || is_category() || is_home() || is_single() || is_tag()) && 'post' == get_post_type();
}


/**
 * Disable comments entirely.
 */

// Close comments on the front-end
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

// Hide existing comments
add_filter('comments_array', '__return_empty_array', 10, 2);

// Remove comments page from admin menu
add_action('admin_menu', function () {
	remove_menu_page('edit-comments.php');
});

// Remove comments link from admin bar
add_action('wp_before_admin_bar_render', function () {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu('comments');
});

// Remove comment support from all post types
add_action('init', function () {
	foreach (get_post_types() as $post_type) {
		if (post_type_supports($post_type, 'comments')) {
			remove_post_type_support($post_type, 'comments');
			remove_post_type_support($post_type, 'trackbacks');
		}
	}
}, 100);

// Redirect any direct access to comments admin page
add_action('admin_init', function () {
	global $pagenow;
	if ($pagenow === 'edit-comments.php') {
		wp_safe_redirect(admin_url());
		exit;
	}
});

// Remove comments metabox from dashboard
add_action('admin_init', function () {
	remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
});

// Remove comments from admin bar on front-end
add_action('init', function () {
	if (is_admin_bar_showing()) {
		remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
	}
});


/**
 * Site blocks.
 *
 * Every block this site uses is built for this site and registered here.
 * blocks/ ships empty on purpose -- never start a build from a premade block.
 * The old 30-block library lives in the toolkit at
 * Commands/block-builder/_library/ and is for post-launch use only.
 *
 * add_filter('devq_blocks', function ($blocks) {
 *     $blocks[] = 'Hero Banner';   // folder: blocks/herobanner/
 *     return $blocks;
 * });
 */
