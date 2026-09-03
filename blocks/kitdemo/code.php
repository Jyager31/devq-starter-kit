<?php
/**
 * Kit Demo Block
 *
 * A REFERENCE BLOCK, not a production one. It exists so the block pattern can be
 * QA'd on a fresh scaffold -- and so the editor canvas has something real to
 * preview, which an empty blocks/ folder cannot provide.
 *
 * Delete blocks/kitdemo/ before launch. The registration in functions.php is
 * guarded on the folder existing, so removing the folder is the whole job, and
 * scripts/site-health.php fails while it is still here.
 *
 * It deliberately exercises the full pattern: every tab, a repeater, an image,
 * a link, a select that changes layout, the empty state, the spacing helper and
 * both animation modes.
 */

// Error handling
if (!function_exists('get_field')) {
    echo 'ACF plugin is not active. This block requires ACF to function properly.';
    return;
}

// ACF Fields - Content Tab
$eyebrow    = get_field('eyebrow');
$heading    = get_field('heading');
$subheading = get_field('subheading');
$content    = get_field('content');
$button     = get_field('button');
$columns    = get_field('columns') ?: '3';
$cards      = get_field('cards');

// Empty state. A block whose repeater is empty prints nothing on the front end --
// not even the heading -- so without this it is invisible in the editor too and
// nobody can see the section exists and is waiting on content.
if (empty($cards)) {
    devq_block_placeholder(
        'Kit Demo',
        'Add at least one card under Content → Cards. Until then this section does not appear on the page.'
    );
    return;
}

// Options Tab Fields (ALWAYS include these)
$margin_top          = get_field('margin_top') ?: '';
$margin_bottom       = get_field('margin_bottom') ?: '';
$margin_top_other    = get_field('margin_top_other') ?: 0;
$margin_bottom_other = get_field('margin_bottom_other') ?: 0;
$custom_class        = get_field('custom_class');
$custom_id           = get_field('custom_id');

// Animation Tab Fields (ALWAYS include these)
$animation_type     = get_field('animation_type') ?: 'recommended';
$animation_duration = get_field('animation_duration') ?: 800;
$disable_animation  = get_field('disable_animation');

// Generate unique block ID
$unique_block_id = generate_unique_block_id('kitdemo');

// Build dynamic attributes
$block_classes = 'container-fluid kitdemo-block kitdemo-cols-' . $columns;
if ($custom_class) {
    $block_classes .= ' ' . $custom_class;
}

$block_id = $custom_id ? $custom_id : $unique_block_id;

// Build AOS attributes
$is_recommended = ($animation_type === 'recommended');
$aos_attributes = '';
if (!$disable_animation && !$is_recommended) {
    $aos_attributes = 'data-aos="' . esc_attr($animation_type) . '"';
    if ($animation_duration != 800) {
        $aos_attributes .= ' data-aos-duration="' . esc_attr($animation_duration) . '"';
    }
}

// In recommended mode, animate individual elements with devq_aos() instead.
$animate = (!$disable_animation && $is_recommended);
?>

<div class="<?php echo esc_attr($block_classes); ?>" id="<?php echo esc_attr($block_id); ?>" <?php echo $aos_attributes; ?>>
    <div class="container">

        <?php if ($eyebrow || $heading || $subheading) : ?>
            <div class="kitdemo-intro" <?php if ($animate) echo devq_aos('fade-up', 0, $animation_duration); ?>>
                <?php if ($eyebrow) : ?>
                    <span class="kitdemo-eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>

                <?php if ($heading) : ?>
                    <h2 class="kitdemo-heading"><?php echo esc_html($heading); ?></h2>
                <?php endif; ?>

                <?php if ($subheading) : ?>
                    <p class="kitdemo-subheading"><?php echo esc_html($subheading); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($content) : ?>
            <div class="kitdemo-content" <?php if ($animate) echo devq_aos('fade-up', 100, $animation_duration); ?>>
                <?php echo wp_kses_post($content); ?>
            </div>
        <?php endif; ?>

        <div class="kitdemo-grid">
            <?php foreach ($cards as $i => $card) :
                $card_image = !empty($card['image']) ? $card['image'] : 0;
                $card_link  = !empty($card['link']) ? $card['link'] : null;
            ?>
                <article class="kitdemo-card" <?php if ($animate) echo devq_aos('fade-up', 100 + ($i * 100), $animation_duration); ?>>
                    <?php if ($card_image) : ?>
                        <div class="kitdemo-card-image">
                            <?php echo devq_image($card_image, 'medium_large'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="kitdemo-card-body">
                        <?php if (!empty($card['title'])) : ?>
                            <h3 class="kitdemo-card-title"><?php echo esc_html($card['title']); ?></h3>
                        <?php endif; ?>

                        <?php if (!empty($card['description'])) : ?>
                            <p class="kitdemo-card-description"><?php echo esc_html($card['description']); ?></p>
                        <?php endif; ?>

                        <?php if ($card_link && !empty($card_link['url'])) : ?>
                            <a class="kitdemo-card-link"
                               href="<?php echo esc_url($card_link['url']); ?>"
                               target="<?php echo esc_attr(!empty($card_link['target']) ? $card_link['target'] : '_self'); ?>">
                                <?php echo esc_html(!empty($card_link['title']) ? $card_link['title'] : 'Learn more'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($button && !empty($button['url'])) : ?>
            <div class="kitdemo-cta" <?php if ($animate) echo devq_aos('fade-up', 200, $animation_duration); ?>>
                <a class="btn"
                   href="<?php echo esc_url($button['url']); ?>"
                   target="<?php echo esc_attr(!empty($button['target']) ? $button['target'] : '_self'); ?>">
                    <?php echo esc_html(!empty($button['title']) ? $button['title'] : 'Learn more'); ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php output_block_spacing_css($margin_top, $margin_bottom, $margin_top_other, $margin_bottom_other, $block_id); ?>
