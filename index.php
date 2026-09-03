<?php

/**
 * The fallback template.
 *
 * WordPress only reaches this when nothing more specific matched. Everything
 * that normally renders has its own file -- home.php, archive.php, single.php,
 * page.php, search.php, 404.php -- so this stays a thin delegation rather than
 * a second copy of the archive markup.
 */

get_header();

get_template_part('template-parts/pagehead', null, array(
	'title' => is_home() ? __('Blog', 'devq') : wp_get_document_title(),
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
			<?php the_posts_pagination(array('mid_size' => 2)); ?>
		</div>
	<?php else : ?>
		<?php get_template_part('template-parts/empty-state'); ?>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
