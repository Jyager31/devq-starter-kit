<?php

/**
 * A single blog post.
 *
 * SCAFFOLD DEFAULT -- rewrite to the site's design during the build.
 */

get_header();

while (have_posts()) : the_post();

	$categories = get_the_category();

	// An imported or CLI-created post can have post_author 0, which makes
	// get_the_author() an empty string -- printing a bare "by" with nothing
	// after it. Only add the byline when there is actually a name.
	$meta   = array(get_the_date());
	$author = get_the_author();

	if ($author) {
		/* translators: %s: post author name. */
		$meta[] = sprintf(__('by %s', 'devq'), $author);
	}

	get_template_part('template-parts/pagehead', null, array(
		'eyebrow' => $categories ? $categories[0]->name : '',
		'title'   => get_the_title(),
		'meta'    => $meta,
	));
	?>

	<div class="container devq-section">
		<div class="devq-prose">
			<?php the_content(); ?>
		</div>

		<?php
		$prev = get_previous_post();
		$next = get_next_post();
		?>
		<?php if ($prev || $next) : ?>
			<nav class="devq-postnav" aria-label="<?php esc_attr_e('Post navigation', 'devq'); ?>">
				<?php if ($prev) : ?>
					<a class="devq-postnav__prev" href="<?php echo esc_url(get_permalink($prev)); ?>">
						<span class="devq-postnav__label"><?php esc_html_e('← Previous', 'devq'); ?></span>
						<span class="devq-postnav__title"><?php echo esc_html(get_the_title($prev)); ?></span>
					</a>
				<?php endif; ?>

				<?php if ($next) : ?>
					<a class="devq-postnav__next" href="<?php echo esc_url(get_permalink($next)); ?>">
						<span class="devq-postnav__label"><?php esc_html_e('Next →', 'devq'); ?></span>
						<span class="devq-postnav__title"><?php echo esc_html(get_the_title($next)); ?></span>
					</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	</div>

<?php endwhile; ?>

<?php get_footer(); ?>
