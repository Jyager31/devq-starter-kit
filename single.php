<?php

/**
 * The template for displaying all single posts.
 */

get_header();

// See archive.php -- the same function-scope trap applies here.
$layout_single_style = get_field('layout_single_style', 'option') ?: 'classic';
get_template_part('template-parts/single/style', $layout_single_style);

get_footer();
