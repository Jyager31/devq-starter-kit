<?php

/**
 * Theme Settings CSS
 * 
 * This file loads all theme settings and generates CSS variables.
 */

// Branding settings
$favicon = get_field('branding_favicon', 'option');
$logo = get_field('branding_logo', 'option');
$alt_logo = get_field('branding_alt_logo', 'option');
$company_name = get_field('branding_company_name', 'option');
$cta = get_field('branding_header_cta', 'option');
$brand_guidelines = get_field('branding_guidelines_pdf', 'option');

// Contact settings
$email = get_field('contact_email', 'option');
$contact_phone = get_field('contact_phone', 'option');
$address = get_field('contact_address', 'option');

// Social settings
$facebook = get_field('social_facebook', 'option');
$twitter = get_field('social_twitter', 'option');
$instagram = get_field('social_instagram', 'option');
$linkedin = get_field('social_linkedin', 'option');
$youtube = get_field('social_youtube', 'option');

// Style settings - Colors
$primary_color = get_field('styles_primary_color', 'option') ?: '#007bff';
$secondary_color = get_field('styles_secondary_color', 'option') ?: '#6c757d';
$accent_color = get_field('styles_accent_color', 'option') ?: '#ffc107';

// Style settings - Typography
$font_embed = get_field('styles_font_embed', 'option');
$fontawesome_kit = get_field('styles_fontawesome_kit', 'option') ?: '5f935150b0';
$heading_font = get_field('styles_heading_font', 'option') ?: "'Montserrat', sans-serif";
$heading_weight = get_field('styles_heading_weight', 'option') ?: '500';
$heading_line_height = get_field('styles_heading_line_height', 'option') ?: '1.2';
$body_font = get_field('styles_body_font', 'option') ?: "'Open Sans', sans-serif";
$body_weight = get_field('styles_body_weight', 'option') ?: '400';
$body_size = get_field('styles_body_size', 'option') ?: '16';
$body_line_height = get_field('styles_body_line_height', 'option') ?: '1.5';

// Style settings - Buttons
$button_radius = get_field('styles_button_radius', 'option') ?: '4';
$button_padding = get_field('styles_button_padding', 'option') ?: '10px 20px';

// Style settings - Spacing
$small_spacing = get_field('spacing_small', 'option') ?: '15';
$medium_spacing = get_field('spacing_medium', 'option') ?: '30';
$large_spacing = get_field('spacing_large', 'option') ?: '60';
$section_top_padding = get_field('styles_section_padding_top', 'option') ?: '80';
$section_bottom_padding = get_field('styles_section_padding_bottom', 'option') ?: '80';
$mobile_section_top_padding = get_field('styles_mobile_section_padding_top', 'option') ?: '40';
$mobile_section_bottom_padding = get_field('styles_mobile_section_padding_bottom', 'option') ?: '40';

// Script settings
$header_scripts = get_field('scripts_header', 'option');
$footer_scripts = get_field('scripts_footer', 'option');
$google_analytics = get_field('scripts_google_analytics', 'option');
$google_tag_manager = get_field('scripts_google_tag_manager', 'option');
$facebook_pixel = get_field('scripts_facebook_pixel', 'option');

// Layout style settings
$layout_archive_style = get_field('layout_archive_style', 'option') ?: 'grid';
$layout_single_style = get_field('layout_single_style', 'option') ?: 'classic';

// 404 Page settings
$error_title = get_field('404_title', 'option') ?: 'Page Not Found';
$error_message = get_field('404_message', 'option') ?: 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.';
$error_show_search = get_field('404_show_search', 'option');
$error_link_1 = get_field('404_link_1', 'option');
$error_link_2 = get_field('404_link_2', 'option');
$error_link_3 = get_field('404_link_3', 'option');

// For backward compatibility
$primary_logo = $logo;
$tertiary_color = $accent_color;
$font1 = $heading_font;
$font2 = $body_font;

// Output font embed code if provided (allow link and style tags for Google Fonts)
if ($font_embed) {
    echo wp_kses($font_embed, array(
        'link' => array('rel' => array(), 'href' => array(), 'type' => array(), 'crossorigin' => array()),
        'style' => array('type' => array()),
    ));
}

// Output FontAwesome kit if provided
if ($fontawesome_kit) {
    echo '<script src="https://kit.fontawesome.com/' . esc_attr($fontawesome_kit) . '.js" crossorigin="anonymous"></script>';
}
?>

<style>
    :root {
        /* Colors */
        --primary: <?php echo esc_attr($primary_color); ?>;
        --secondary: <?php echo esc_attr($secondary_color); ?>;
        --tertiary: <?php echo esc_attr($accent_color); ?>;

        /* Typography */
        --font1: <?php echo wp_strip_all_tags($heading_font); ?>;
        --font2: <?php echo wp_strip_all_tags($body_font); ?>;
        --heading-weight: <?php echo esc_attr($heading_weight); ?>;
        --heading-line-height: <?php echo esc_attr($heading_line_height); ?>;
        --body-weight: <?php echo esc_attr($body_weight); ?>;
        --body-size: <?php echo esc_attr($body_size); ?>px;
        --body-line-height: <?php echo esc_attr($body_line_height); ?>;

        /* Buttons */
        --button-radius: <?php echo esc_attr($button_radius); ?>px;
        --button-padding: <?php echo esc_attr($button_padding); ?>;

        /* Spacing */
        --spacing-small: <?php echo esc_attr($small_spacing); ?>px;
        --spacing-medium: <?php echo esc_attr($medium_spacing); ?>px;
        --spacing-large: <?php echo esc_attr($large_spacing); ?>px;
        --section-padding-top: <?php echo esc_attr($section_top_padding); ?>px;
        --section-padding-bottom: <?php echo esc_attr($section_bottom_padding); ?>px;
    }

    @media (max-width: 767px) {
        :root {
            --section-padding-top: <?php echo esc_attr($mobile_section_top_padding); ?>px;
            --section-padding-bottom: <?php echo esc_attr($mobile_section_bottom_padding); ?>px;
        }
    }

    /* Apply typography settings */
    body,
    p,
    ul,
    li {
        font-weight: var(--body-weight);
        font-size: var(--body-size);
        line-height: var(--body-line-height);
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        font-weight: var(--heading-weight);
        line-height: var(--heading-line-height);
    }
</style>