<?php

/**
 * The template for displaying archive pages.
 */

get_header();

// Read the style here rather than relying on theme-settings-css.php. That file
// is included from header.php, i.e. inside load_template()'s function scope, so
// its $layout_archive_style never reaches this template -- which rendered every
// archive blank behind an "Undefined variable" warning.
$layout_archive_style = get_field('layout_archive_style', 'option') ?: 'grid';
get_template_part('template-parts/archive/style', $layout_archive_style);

get_footer();
