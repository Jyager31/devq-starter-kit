<?php

/**
 * The blog index (the page set as "Posts page" in Settings > Reading).
 *
 * SCAFFOLD DEFAULT -- rewrite to the site's design during the build.
 *
 * This file exists so the blog index has an obvious home. Without it WordPress
 * falls through to index.php, and the routing stops being readable from the
 * file list.
 */

get_header();

get_template_part('template-parts/pagehead', null, array(
	'title' => single_post_title('', false) ?: __('Blog', 'devq'),
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
			'title'   => __('No posts yet', 'devq'),
			'message' => __('Once posts are published they will appear here.', 'devq'),
		)); ?>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
