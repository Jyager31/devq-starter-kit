<?php

/**
 * The header for our theme.
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<?php
// Include theme settings CSS
include(get_template_directory() . '/theme-settings-css.php');

// Get page-specific scripts
$page_head_scripts = get_field('page_head_scripts');
$page_body_scripts = get_field('page_body_scripts');
?>

<head>
  <?php
  // Output scripts
  if ($header_scripts) {
      echo wp_kses_post($header_scripts);
  }
  if ($page_head_scripts) {
      echo wp_kses_post($page_head_scripts);
  }

  // Google Analytics
  if ($google_analytics) : ?>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($google_analytics); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];

      function gtag() {
        dataLayer.push(arguments);
      }
      gtag('js', new Date());
      gtag('config', '<?php echo esc_attr($google_analytics); ?>');
    </script>
  <?php endif;

  // Google Tag Manager
  if ($google_tag_manager) : ?>
    <!-- Google Tag Manager -->
    <script>
      (function(w, d, s, l, i) {
        w[l] = w[l] || [];
        w[l].push({
          'gtm.start': new Date().getTime(),
          event: 'gtm.js'
        });
        var f = d.getElementsByTagName(s)[0],
          j = d.createElement(s),
          dl = l != 'dataLayer' ? '&l=' + l : '';
        j.async = true;
        j.src =
          'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
        f.parentNode.insertBefore(j, f);
      })(window, document, 'script', 'dataLayer', '<?php echo esc_attr($google_tag_manager); ?>');
    </script>
    <!-- End Google Tag Manager -->
  <?php endif; ?>

  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="profile" href="http://gmpg.org/xfn/11">
  <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
  <?php if (isset($favicon) && $favicon) : ?>
    <link rel="shortcut icon" href="<?php echo esc_url($favicon['url']); ?>" />
  <?php endif; ?>

  <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
  <?php
  // Google Tag Manager (noscript)
  if ($google_tag_manager) : ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($google_tag_manager); ?>"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
  <?php endif;

  // Facebook Pixel
  if ($facebook_pixel) : ?>
    <!-- Facebook Pixel Code -->
    <script>
      ! function(f, b, e, v, n, t, s) {
        if (f.fbq) return;
        n = f.fbq = function() {
          n.callMethod ?
            n.callMethod.apply(n, arguments) : n.queue.push(arguments)
        };
        if (!f._fbq) f._fbq = n;
        n.push = n;
        n.loaded = !0;
        n.version = '2.0';
        n.queue = [];
        t = b.createElement(e);
        t.async = !0;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s)
      }(window, document, 'script',
        'https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', '<?php echo esc_attr($facebook_pixel); ?>');
      fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id=<?php echo esc_attr($facebook_pixel); ?>&ev=PageView&noscript=1" /></noscript>
    <!-- End Facebook Pixel Code -->
  <?php endif;

  // Output body scripts
  if ($footer_scripts) {
      echo wp_kses_post($footer_scripts);
  }
  if ($page_body_scripts) {
      echo wp_kses_post($page_body_scripts);
  }
  ?>

<?php
/**
 * Site header.
 *
 * This is the scaffold's starting point, not a finished design -- every site
 * rewrites this file to spec. It reads the Theme Settings content fields so the
 * client can still change the logo, CTA and phone number without a developer.
 */

$logo         = get_field('branding_logo', 'option');
$cta          = get_field('branding_header_cta', 'option');
$company_name = get_field('branding_company_name', 'option');
$header_phone = get_field('contact_phone', 'option');
?>

<header class="devq-header-wrap">
  <div class="container">
    <div class="devq-header">
      <div class="devq-header-logo">
        <a href="<?php echo esc_url(home_url('/')); ?>">
          <?php if (!empty($logo['url'])) : ?>
            <img class="mainLogo" src="<?php echo esc_url($logo['url']); ?>"
                 alt="<?php echo esc_attr(!empty($logo['alt']) ? $logo['alt'] : $company_name); ?>">
          <?php else : ?>
            <span class="devq-header-wordmark"><?php echo esc_html($company_name ?: get_bloginfo('name')); ?></span>
          <?php endif; ?>
        </a>
      </div>

      <nav class="devq-header-nav desktopOnly" aria-label="Primary navigation">
        <?php
        wp_nav_menu(array(
          'theme_location' => 'primary',
          'menu_class'     => 'devq-desktop-nav',
          'container'      => false,
          'fallback_cb'    => false,
          'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
          'walker'         => new devq_nav_walker(),
        ));
        ?>
      </nav>

      <div class="devq-header-actions">
        <?php if (!empty($cta['url'])) : ?>
          <a href="<?php echo esc_url($cta['url']); ?>" class="btn devq-header-cta desktopOnly"
             <?php echo !empty($cta['target']) ? 'target="' . esc_attr($cta['target']) . '"' : ''; ?>>
            <?php echo esc_html($cta['title']); ?>
          </a>
        <?php endif; ?>

        <div class="mobileOnly">
          <button class="devq-hamburger" id="devq-menu-toggle" aria-label="Toggle menu"
                  aria-expanded="false" aria-controls="devq-mobile-menu">
            <span class="devq-hamburger-box">
              <span class="devq-hamburger-bar"></span>
              <span class="devq-hamburger-bar"></span>
              <span class="devq-hamburger-bar"></span>
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</header>

<div class="devq-mobile-menu" id="devq-mobile-menu" data-style="fullscreen"
     aria-hidden="true" role="dialog" aria-label="Mobile navigation">
  <button class="devq-menu-close" id="devq-menu-close" aria-label="Close menu">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>

  <?php if (!empty($logo['url'])) : ?>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="devq-mobile-logo">
      <img src="<?php echo esc_url($logo['url']); ?>" alt="<?php echo esc_attr(!empty($logo['alt']) ? $logo['alt'] : $company_name); ?>">
    </a>
  <?php endif; ?>

  <nav aria-label="Mobile navigation">
    <?php
    wp_nav_menu(array(
      'theme_location' => 'primary',
      'menu_class'     => 'devq-mobile-nav',
      'container'      => false,
      'fallback_cb'    => false,
      'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
      'walker'         => new DevQ_Mobile_Nav_Walker(),
    ));
    ?>
  </nav>

  <?php if (!empty($header_phone)) : ?>
    <div class="devq-mobile-contact">
      <a href="tel:<?php echo esc_attr($header_phone); ?>"><?php echo esc_html($header_phone); ?></a>
    </div>
  <?php endif; ?>
</div>

<style>
  /* Shared Hamburger Button Styles */
  .devq-hamburger {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 10px;
    z-index: 10;
  }

  .devq-hamburger-box {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    width: 28px;
    height: 20px;
    position: relative;
  }

  .devq-hamburger-bar {
    display: block;
    width: 100%;
    height: 2.5px;
    background: #333;
    border-radius: 2px;
    transition: transform 0.3s ease, opacity 0.3s ease;
    transform-origin: center;
  }

  /* Hamburger -> X morph */
  .devq-hamburger.is-active .devq-hamburger-bar:nth-child(1) {
    transform: translateY(7.5px) rotate(45deg);
  }

  .devq-hamburger.is-active .devq-hamburger-bar:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
  }

  .devq-hamburger.is-active .devq-hamburger-bar:nth-child(3) {
    transform: translateY(-7.5px) rotate(-45deg);
  }

  /* Responsive visibility */
  .desktopOnly { display: inherit; }
  .mobileOnly { display: none; }

  @media (max-width: 1199px) {
    .desktopOnly { display: none !important; }
    .mobileOnly { display: block !important; }
    .devq-hamburger { display: block; }
  }
</style>
