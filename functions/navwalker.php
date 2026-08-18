<?php

if (!class_exists('devq_nav_walker')) :

class devq_nav_walker extends Walker_Nav_Menu
{

    private $blog_sidebar_pos = "";
    // add classes to ul sub-menus
    function start_lvl(&$output, $depth = 0, $args = array())
    {
        // depth dependent classes
        $indent = ($depth > 0  ? str_repeat("\t", $depth) : ''); // code indent
        $display_depth = ($depth + 1); // because it counts the first submenu as 0
        $classes = array(
            'dropdown-menu',
            ($display_depth % 2  ? 'menu-odd' : 'menu-even'),
            ($display_depth >= 2 ? '' : ''),
            'menu-depth-' . $display_depth
        );
        $class_names = implode(' ', $classes);

        // build html

        $output .= "\n" . $indent . '<ul class="' . $class_names . '">' . "\n";
    }

    // add main/sub classes to li's and links
    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
    {
        global $wp_query;

        // WordPress passes an array on some fallback paths and an stdClass on the
        // normal one; everything below uses object syntax.
        $args = (object) $args;
        foreach (array('before', 'after', 'link_before', 'link_after') as $devq_key) {
            if (!isset($args->$devq_key)) {
                $args->$devq_key = '';
            }
        }

        $indent = ($depth > 0 ? str_repeat("\t", $depth) : ''); // code indent

        // passed classes
        $classes = empty($item->classes) ? array() : (array) $item->classes;

        // WordPress adds 'menu-item-has-children' automatically -- no DB query needed
        $has_children = in_array('menu-item-has-children', $classes);

        // depth dependent classes
        $depth_classes = array(
            ($depth >= 1 ? '' : 'dropdown'),
            ($depth >= 2 ? 'sub-sub-menu-item' : ''),
            ($depth % 2 ? 'menu-item-odd' : 'menu-item-even'),
            'menu-item-depth-' . $depth
        );
        $depth_class_names = esc_attr(implode(' ', $depth_classes));

        $class_names = esc_attr(implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item)));

        // build html
        $output .= $indent . '<li id="nav-menu-item-' . $item->ID . '" class="' . $depth_class_names . ' ' . $class_names . '">';

        // link attributes
        $attributes  = !empty($item->attr_title) ? ' title="'  . esc_attr($item->attr_title) . '"' : '';
        $attributes .= !empty($item->target)     ? ' target="' . esc_attr($item->target) . '"' : '';
        $attributes .= !empty($item->xfn)        ? ' rel="'    . esc_attr($item->xfn) . '"' : '';
        $attributes .= !empty($item->url)        ? ' href="'   . esc_attr($item->url) . '"' : '';

        if ($depth == 0 && $has_children) {
            // These lines adds your custom class and attribute
            $attributes .= ' class="dropdown-toggle"';
            $attributes .= ' data-toggle="dropdown"';
            $attributes .= ' data-hover="dropdown"';
            $attributes .= ' data-animations="fadeInUp"';
        }
        $description  = !empty($item->description) ? '<span class="fa ' . esc_attr($item->description) . '" aria-hidden="true"></span>' : '';

        $item_output = $args->before;
        $item_output .= '<a' . $attributes . '>';
        $item_output .= $description . $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after; //If you want the description to be output after <a>
        //$item_output .= $description.$args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after; //If you want the description to be output before </a>

        // Add the caret if menu level is 0
        if ($depth == 0 && $has_children) {
            $item_output .= '<i class="fas fa-caret-down"></i>';
        }

        $item_output .= '</a>';
        $item_output .= $args->after;

        // build html
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args, $id);
    }
} //End Walker_Nav_Menu

endif;


/**
 * Mobile Nav Walker
 * Adds sub-menu toggle buttons for mobile menu accordion behavior.
 */
class DevQ_Mobile_Nav_Walker extends Walker_Nav_Menu
{
    function start_lvl(&$output, $depth = 0, $args = array())
    {
        $output .= '<ul class="sub-menu">';
    }

    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
    {
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $has_children = in_array('menu-item-has-children', $classes);
        $class_names = esc_attr(implode(' ', array_filter($classes)));

        $output .= '<li class="' . $class_names . '">';

        $attributes  = !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
        $attributes .= !empty($item->xfn)    ? ' rel="'    . esc_attr($item->xfn) . '"' : '';
        $attributes .= !empty($item->url)    ? ' href="'   . esc_attr($item->url) . '"' : '';

        $output .= '<a' . $attributes . '>' . apply_filters('the_title', $item->title, $item->ID) . '</a>';

        if ($has_children) {
            $output .= '<button class="devq-submenu-toggle" aria-label="Toggle submenu"><i class="fas fa-chevron-down"></i></button>';
        }
    }
}