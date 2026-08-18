<?php

/**
 * The template for displaying the footer.
 */

?>

<?php
/**
 * Site footer.
 *
 * Like header.php, this is the scaffold's starting point and is rewritten to
 * spec on every build. It reads the Theme Settings content fields so branding,
 * contact details and social links stay client-editable.
 */

$company_name = get_field('branding_company_name', 'option');
$footer_phone = get_field('contact_phone', 'option');
$footer_email = get_field('contact_email', 'option');

$social = array(
    'facebook'  => get_field('social_facebook', 'option'),
    'instagram' => get_field('social_instagram', 'option'),
    'linkedin'  => get_field('social_linkedin', 'option'),
    'youtube'   => get_field('social_youtube', 'option'),
    'twitter'   => get_field('social_twitter', 'option'),
);
$social = array_filter($social);
?>

<footer class="devq-footer">
  <div class="container">
    <?php if (has_nav_menu('footer')) : ?>
      <nav class="devq-footer-nav" aria-label="Footer navigation">
        <?php
        wp_nav_menu(array(
          'theme_location' => 'footer',
          'menu_class'     => 'devq-footer-menu',
          'container'      => false,
          'fallback_cb'    => false,
          'depth'          => 1,
        ));
        ?>
      </nav>
    <?php endif; ?>

    <?php if ($footer_phone || $footer_email) : ?>
      <div class="devq-footer-contact">
        <?php if ($footer_phone) : ?>
          <a href="tel:<?php echo esc_attr($footer_phone); ?>"><?php echo esc_html($footer_phone); ?></a>
        <?php endif; ?>
        <?php if ($footer_email) : ?>
          <a href="mailto:<?php echo esc_attr($footer_email); ?>"><?php echo esc_html($footer_email); ?></a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($social)) : ?>
      <ul class="devq-footer-social">
        <?php foreach ($social as $network => $url) : ?>
          <li>
            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"
               aria-label="<?php echo esc_attr(ucfirst($network)); ?>"><?php echo esc_html(ucfirst($network)); ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <p class="devq-footer-copyright">
      Copyright &copy; <?php echo esc_html(date('Y')); ?>
      <?php echo esc_html($company_name ?: get_bloginfo('name')); ?>
      &mdash; Website Powered by <a href="https://thedevq.com/" target="_blank" rel="noopener">DevQ</a>
    </p>
  </div>
</footer>

<?php wp_footer(); ?>

<?php
// Get page-specific CSS and scripts
$page_custom_css = get_field('page_custom_css');
$lower1199 = get_field('lower1199');
$lower767 = get_field('lower767');
$page_footer_scripts = get_field('page_footer_scripts');

// Output any additional footer scripts
if ($page_footer_scripts) {
	echo wp_kses_post($page_footer_scripts);
}

// Output page-specific CSS
if ($page_custom_css) :
	echo '<style>';
	echo wp_strip_all_tags($page_custom_css);
	echo '</style>';
endif;

// Output responsive CSS
if ($lower1199) :
	echo '<style> @media (max-width: 1199px) {';
	echo wp_strip_all_tags($lower1199);
	echo '} </style>';
endif;

if ($lower767) :
	echo '<style> @media (max-width: 767px) {';
	echo wp_strip_all_tags($lower767);
	echo '} </style>';
endif;
?>

</body>

</html>
