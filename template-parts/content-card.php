<?php

/**
 * One post card, as used by the blog index, archives and search results.
 *
 * SCAFFOLD DEFAULT -- rewrite to the site's design during the build.
 *
 * Shared rather than repeated in each template: when these three carried their
 * own copy they had already drifted into two different card designs.
 *
 * Uses devq_image() for the thumbnail so it ships a srcset, real dimensions and
 * the attachment's own alt text instead of the full-size original. It already
 * defaults to loading="lazy" / decoding="async".
 */

$thumb_id = get_post_thumbnail_id();
?>

<article <?php post_class('devq-card'); ?>>
	<a href="<?php the_permalink(); ?>" class="devq-card__link">
		<?php if ($thumb_id) : ?>
			<div class="devq-card__image">
				<?php echo devq_image($thumb_id, 'medium_large'); ?>
			</div>
		<?php endif; ?>

		<div class="devq-card__body">
			<span class="devq-card__date"><?php echo esc_html(get_the_date()); ?></span>
			<h2><?php the_title(); ?></h2>
			<div class="devq-card__excerpt"><?php the_excerpt(); ?></div>
			<span class="devq-card__more"><?php esc_html_e('Read more', 'devq'); ?></span>
		</div>
	</a>
</article>
