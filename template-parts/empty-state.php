<?php

/**
 * "Nothing here" plus a search form.
 *
 * SCAFFOLD DEFAULT -- rewrite to the site's design during the build.
 *
 * @param string $args['title']   Heading. Optional.
 * @param string $args['message'] Supporting line. Optional.
 */

$title   = isset($args['title']) ? $args['title'] : __('Nothing found', 'devq');
$message = isset($args['message']) ? $args['message'] : __('Try a different search, or head back to the homepage.', 'devq');
?>

<div class="devq-empty">
	<h2><?php echo esc_html($title); ?></h2>
	<p><?php echo esc_html($message); ?></p>

	<form role="search" method="get" class="devq-searchform" action="<?php echo esc_url(home_url('/')); ?>">
		<label class="screen-reader-text" for="devq-empty-search"><?php esc_html_e('Search for:', 'devq'); ?></label>
		<input type="search" id="devq-empty-search" class="search-field" name="s"
			placeholder="<?php esc_attr_e('Search…', 'devq'); ?>"
			value="<?php echo esc_attr(get_search_query()); ?>">
		<button type="submit" class="search-submit"><?php esc_html_e('Search', 'devq'); ?></button>
	</form>
</div>
