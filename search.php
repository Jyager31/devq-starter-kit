<?php

/**
 * Search results.
 *
 * SCAFFOLD DEFAULT -- rewrite to the site's design during the build.
 */

get_header();

global $wp_query;
$found = (int) $wp_query->found_posts;

get_template_part('template-parts/pagehead', null, array(
	'eyebrow' => __('Search', 'devq'),
	/* translators: %s: the search term. */
	'title'   => sprintf(esc_html__('Results for “%s”', 'devq'), esc_html(get_search_query())),
	'sub'     => sprintf(
		/* translators: %s: number of results found. */
		esc_html(_n('%s result', '%s results', $found, 'devq')),
		number_format_i18n($found)
	),
));
?>

<div class="container devq-section">
	<?php if (have_posts()) : ?>
		<div class="devq-cardgrid">
			<?php while (have_posts()) : the_post(); ?>
				<?php get_template_part('template-parts/content-card'); ?>
			<?php endwhile; ?>
		</div>

		<div class="devq-pagination">
			<?php the_posts_pagination(array(
				'mid_size'  => 2,
				'prev_text' => __('&laquo; Previous', 'devq'),
				'next_text' => __('Next &raquo;', 'devq'),
			)); ?>
		</div>
	<?php else : ?>
		<?php get_template_part('template-parts/empty-state', null, array(
			'title'   => __('No results', 'devq'),
			'message' => __('Nothing matched that search. Try a different word or a broader term.', 'devq'),
		)); ?>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
