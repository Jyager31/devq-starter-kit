<?php

/**
 * Page banner for the default templates.
 *
 * SCAFFOLD DEFAULT -- rewrite to the site's design during the build.
 *
 * @param string $args['eyebrow'] Optional small label above the title.
 * @param string $args['title']   Required. Already-escaped or plain text.
 * @param string $args['sub']     Optional supporting line. May contain markup.
 * @param array  $args['meta']    Optional list of short strings, dot-separated.
 */

$eyebrow = isset($args['eyebrow']) ? $args['eyebrow'] : '';
$title   = isset($args['title']) ? $args['title'] : '';
$sub     = isset($args['sub']) ? $args['sub'] : '';
$meta    = isset($args['meta']) ? (array) $args['meta'] : array();
?>

<div class="container-fluid">
	<div class="devq-pagehead">
		<div class="container">
			<?php if ($eyebrow) : ?>
				<span class="devq-pagehead__eyebrow"><?php echo esc_html($eyebrow); ?></span>
			<?php endif; ?>

			<h1><?php echo wp_kses_post($title); ?></h1>

			<?php if ($sub) : ?>
				<p class="devq-pagehead__sub"><?php echo wp_kses_post($sub); ?></p>
			<?php endif; ?>

			<?php if ($meta) : ?>
				<div class="devq-pagehead__meta">
					<?php foreach ($meta as $item) : ?>
						<span><?php echo esc_html($item); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
