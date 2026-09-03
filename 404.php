<?php

/**
 * 404 -- page not found.
 *
 * SCAFFOLD DEFAULT -- rewrite to the site's design during the build.
 *
 * The copy and links are hard-coded. These were six ACF option fields until
 * 2026-09-03; a 404 is a designed page like any other.
 */

get_header();

get_template_part('template-parts/pagehead', null, array(
	'eyebrow' => __('404', 'devq'),
	'title'   => __('Page not found', 'devq'),
	'sub'     => __('The page you are looking for may have been moved, renamed, or is temporarily unavailable.', 'devq'),
));

// Where to send someone who lands here. Set these to the site's real sections.
$quick_links = array(
	array('url' => home_url('/'), 'label' => __('Home', 'devq')),
);
?>

<div class="container devq-section">
	<?php get_template_part('template-parts/empty-state', null, array(
		'title'   => __('Try a search instead', 'devq'),
		'message' => __('Search the site, or use one of the links below.', 'devq'),
	)); ?>

	<?php if ($quick_links) : ?>
		<div class="devq-quicklinks">
			<?php foreach ($quick_links as $link) : ?>
				<a href="<?php echo esc_url($link['url']); ?>" class="btn"><?php echo esc_html($link['label']); ?></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
