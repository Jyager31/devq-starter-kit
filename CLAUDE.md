# DevQ Starter Kit - Claude Block Generator Instructions

## What this is

A **scaffold**, not a framework. Clone it, rename the directory to the client slug, and build.
One theme per site: there is no parent, no child, and no premade blocks.

`get_template_directory()` and `get_stylesheet_directory()` are always the same directory here.
That is deliberate -- the old parent/child split is what caused the block-JSON path bug, the
empty-repeater-in-editor bug and a long list of CSS specificity traps.

## Theme Architecture

- **Theme root:** This directory
- **Block location:** `blocks/[blockname]/code.php`
- **Block registration:** the `devq_blocks` filter (see below). `blocks/` ships EMPTY.
- **ACF JSON:** `acfjson/group_[blockname]_block.json`
- **Spacing system:** `functions/spacing.php` -- centralized responsive spacing
- **Animation helper:** `functions/animations.php` -- `devq_aos()` helper for AOS attribute generation
- **Page builder:** `functions/page-builder.php` -- programmatic page creation
- **Brand tokens:** the `:root` block at the top of `style.css` -- the single source
- **Editor canvas:** `functions/editor-canvas.php` -- puts `style.css`, the grid and the
  registered blocks' CSS inside the editor iframe
- **Admin usability:** `functions/admin-ux.php` -- block inspector CSS, the first-run List View
  nudge, and the "How to edit your site" dashboard panel
- **Main stylesheet:** `style.css`
- **Header / footer:** `header.php` and `footer.php` -- single files, rewritten to spec per site

## Blocks are built per site

**Never** start a build from a premade block. Every section gets a block designed for that site.

The 30 blocks this theme used to ship now live in the toolkit at
`Claude Code Toolkit/Commands/block-builder/_library/`, for **post-launch use only** -- an
already-launched site that needs a routine section and has no design requirement worth a custom
block. `_library/_scripts/create-block-library.php` remains a useful reference for field shapes
(exact field names, select values, repeater structure); read it for structure, not for choices.

## Registering a block

```php
add_filter('devq_blocks', function ($blocks) {
    $blocks[] = 'Proof Cards';   // display name; folder is blocks/proofcards/
    return $blocks;
});
```

`devq_get_blocks()` returns an empty array by default, so the list is whatever this site adds.
While it is empty, `devq_allowed_block_types()` falls through to the full core block list rather
than locking the page editor.

## The client's editing experience

Read this before changing `functions/editor-canvas.php`, `functions/admin-ux.php` or the
`:root` block in `style.css`. It is one mechanism with several consequences.

**Sometimes WordPress iframes the post editor canvas, and when it does, ACF blocks become
preview-only.** ACF checks for `iframe[name="editor-canvas"]` in `acf-pro-blocks.min.js`; when it
finds one it pins every ACF block to **preview** and removes the edit/preview toggle. That check
is in ACF's JavaScript, downstream of anything PHP hands it — `'mode' => 'edit'` does nothing, and
neither does a saved `"mode":"edit"`. There is no setting that turns it off.

**On WordPress 7.1+ this is unconditional.** `useShouldIframe()` is gone from `edit-post.js`
(the file no longer contains the string `apiVersion`), and `editor.js` hardcodes
`shouldIframe: true`. There is no prop, filter or setting to opt out. Verified 2026-09-03.

On **7.0.x** it was gated — the canvas was iframed only for the Gutenberg plugin, a device
preview, `wp_template` / `wp_block`, zoom-out, or **every** block being `apiVersion >= 3`. ACF
blocks are v2, so ordinary pages escaped and fields rendered in the canvas. That is why the same
site flips behaviour across a core update, and why a block editable this morning is preview-only
this afternoon. Before blaming theme code for an editor change, check `wp_version` and the mtime
of `wp-includes/js/dist/edit-post.js`.

Do not try to defeat it. Renaming the iframe so ACF's DOM check misses it was tested: ACF stops
pinning preview, but it still renders `acf-block-preview` rather than the form (zero `.acf-field`
in either document) **and** core's own layout collapses, because core queries
`iframe[name="editor-canvas"]` for canvas sizing.

So on 7.1+:

- **The inspector is the only editing surface.** `admin-ux.css` takes it from 280px to 480px
  above 1200px. Width is `--devq-inspector-w`, so `assets/js/editor-inspector.js` can drag it and
  remember it per browser.
- **Two widths, and only one of them sticks.** The base width is what the client dragged, and it
  persists. Wide is a mode you are in while editing one block, and it must NOT persist -- an
  earlier build saved it, and one click of the pencil then left every future session opening at
  1100px with nothing obvious to bring it back. Anything that can enter wide can leave it: the
  pencil, double-clicking the handle, Escape, a reload.
- **The gesture is gone, so put it back.** Before 7.1 a client clicked the block and typed into
  it. The pencil in the block toolbar -- "Edit fields" -- opens the inspector wide in one click,
  reads as pressed while it is, and hands the preview back on the second click. Added through
  `editor.BlockEdit`, not by patching ACF.
- **Fields stack one per row only while the panel is narrow** (`.devq-inspector-narrow`, under
  560px). Wide enough and ACF's own 50/25% widths are worth having back -- a text input stretched
  across 1100px is its own kind of bad.
- **The canvas preview must be correct**, because it is all the client sees.
- **List View is how a block gets selected.** `functions/admin-ux.php` opens it once per user.

What the theme does about it:

| Problem | Handled by |
|---|---|
| The iframe inherits no CSS at all, so blocks preview as unstyled serif | `add_editor_style()` in `editor-canvas.php` — `style.css`, the grid, and every **registered** block's `style.css` |
| ACF's per-block `enqueue_style` never reaches the iframe | same — that is why block CSS is listed there and not left to ACF |
| Brand tokens have to reach the iframe | free: they are the `:root` block in `style.css`, already in that list |
| Webfonts: an iframe cannot be handed a `<link>` | `assets/css/editor-fonts.css`, one `@import`, kept in step with `header.php` |
| ACF WYSIWYG fields render in the browser default serif | free: wp-admin appends the editor-style list to TinyMCE's `content_css` |
| Full-bleed blocks preview boxed at the content width, gutters either side | `assets/css/editor-canvas.css` |
| The canvas runs no JS, so AOS leaves animated blocks at opacity 0 | `assets/css/editor-canvas.css` |
| Left-placement ACF tabs eat 53px of a 265px inspector | `assets/css/admin-ux.css`, scoped to the sidebar |
| ACF's 50/25% field widths clip inputs in a narrow panel (a number field with a unit shows one digit) | `assets/css/admin-ux.css` -- one field per row under 560px, `!important` over ACF's inline `style="width:50%"` |
| A repeater-heavy block needs more panel than a heading-and-button one | `assets/js/editor-inspector.js` -- drag, snap, and the toolbar button |
| An empty block renders nothing and is invisible in both places | `devq_block_placeholder()` |

**The token contract.** The `:root` block at the top of `style.css` is the only place tokens
are declared, and `style.css` is in the editor-style list. Core's `ROOT_SELECTOR_TOKENS`
(`block-editor.js`) **substitutes** `.editor-styles-wrapper` for a selector starting with
`:root`, `html` or `body` rather than nesting under it, so that one block is correct on the
front end and in the canvas with no second copy anywhere. Declare a token in a block's own
stylesheet or an inline `<style>` and you brand the front end while leaving the editor
behind — which is how a client ends up reporting the editor looks broken while every page
they load is fine.

**Fonts are the one thing that needs saying twice**, because an iframe cannot be handed a
`<link>`: the tags in `header.php` and the `@import` in `assets/css/editor-fonts.css` have
to name the same families. Do not put a remote URL in `add_editor_style()` instead — core
does a server-side `wp_remote_get()` for those on **every** editor page load, uncached.

**Theme Settings is deliberately four pages.** Branding, Contact and Social at `edit_posts`;
Scripts at `manage_options`. A setting earns a place there only if it genuinely changes after
launch and is not a design decision — a new phone number, a swapped logo, a marketing tag.
Everything describing how the site *looks* is code. See `devq_theme_settings_pages()` in
`functions/acf.php` for what was removed and why; move a page between tiers with the
`devq_theme_settings_pages` filter rather than editing the array.

A sub page does **not** inherit its parent's capability. Set it on the parent alone and every
child stays open — which is how an account handed out for content work ends up able to inject
JavaScript into every page on the site.

---

## Versioning

`Version:` in `style.css` is the source of truth. There is no auto-updater: the kit is consumed
by `git clone` at scaffold time, and a scaffolded site is thereafter its own theme. Fixes flow
forward to new sites, not backward to shipped ones.

The `DevQ Kit:` line in `style.css` records the kit commit a site was born from. Leave it.

## Field Naming Convention

All new blocks MUST follow this naming convention. Existing blocks may use legacy names (documented below) but new work should be consistent.

### Standard Field Names

| Purpose | Field Name | Type | Notes |
|---------|-----------|------|-------|
| Main heading | `heading` | text | Never use `title` at block level |
| Above heading | `eyebrow` | text | Small label above heading |
| Below heading | `subheading` | text | Never use `subtitle` or `description` at block level |
| Rich text body | `content` | wysiwyg | Never use `text` or `body` |
| Single button | `button` | link | For blocks with one CTA |
| Primary button | `primary_button` | link | For blocks with two CTAs |
| Secondary button | `secondary_button` | link | For blocks with two CTAs |
| Main image | `image` | image | Generic image field |
| Background image | `background_image` | image | For hero/section backgrounds |
| Background color | `background_color` | color_picker | -- |
| Background style | `background` | select | light/dark/primary/secondary |
| Overlay opacity | `overlay_opacity` | number | 0-100 percentage |
| Overlay color | `overlay_color` | color_picker | -- |
| Layout toggle | `image_position` | select | left/right |
| Column count | `columns` | select | 2/3/4 |
| Display style | `style` | select | Block-specific options |
| Form embed | `form_shortcode` | text | Gravity Forms shortcode |

### Repeater Item Fields

| Purpose | Field Name | Notes |
|---------|-----------|-------|
| Item heading | `title` | Use `title` inside repeaters (not `heading`) |
| Item body | `description` | Use `description` inside repeaters (not `content`) |
| Item image | `image` | Generic |
| Person photo | `photo` | For team/testimonial repeaters |
| Person name | `name` | -- |
| Person role | `role` | Job title / position |
| Person quote | `quote` | For testimonial repeaters |
| Logo image | `logo` | For logo bar / marquee repeaters |
| Item link | `link` | -- |
| Icon class | `icon_class` | FontAwesome class string |
| Featured flag | `is_featured` | true_false for highlighted items |
| Star rating | `rating` | number 1-5 |

### Legacy Field Names (existing blocks)

These fields exist in current blocks and should NOT be renamed (would break client sites):
- FAQ uses `question`/`answer` instead of `title`/`description` -- acceptable, domain-specific
- Banner uses `text` instead of `content` -- legacy, use `content` for new blocks
- Stats repeater uses `number`/`label`/`prefix`/`suffix` -- acceptable, domain-specific
- Pricing repeater uses `price`/`period`/`features` -- acceptable, domain-specific

## Creating a New Block

### Step 1: Register the Block

Add the human-readable name to the `$basefunctions` array in `functions/blocks.php`:

```php
$basefunctions = array(
    "Image",
    "Content",
    "Your New Block",  // Add here
);
```

The `devq_filtername()` function processes the name:
- Converts to lowercase
- Removes spaces and hyphens
- Result becomes the folder name and ACF identifier
- Example: `"Your New Block"` -> folder `yournewblock`, ACF block `acf/yournewblock`

### Step 2: Create the Block Folder and code.php

Create `blocks/[filteredname]/code.php`. Use this exact template:

```php
<?php
/**
 * [Block Name] Block
 */

// Error handling
if (!function_exists('get_field')) {
    echo 'ACF plugin is not active. This block requires ACF to function properly.';
    return;
}

// ACF Fields - Content Tab
$field1 = get_field('field1');

// Empty state (REQUIRED on any block that can render nothing --
// an empty repeater, an empty gallery, a query with no results)
if (empty($field1)) {
    devq_block_placeholder('[Block Name]', 'Add at least one [item] for this section to appear.');
    return;
}

// Options Tab Fields (ALWAYS include these)
$margin_top = get_field('margin_top') ?: '';
$margin_bottom = get_field('margin_bottom') ?: '';
$margin_top_other = get_field('margin_top_other') ?: 0;
$margin_bottom_other = get_field('margin_bottom_other') ?: 0;
$custom_class = get_field('custom_class');
$custom_id = get_field('custom_id');

// Animation Tab Fields (ALWAYS include these)
$animation_type = get_field('animation_type') ?: 'recommended';
$animation_duration = get_field('animation_duration') ?: 800;
$disable_animation = get_field('disable_animation');

// Generate unique block ID
$unique_block_id = generate_unique_block_id('blockname');

// Build dynamic attributes
$block_classes = 'container-fluid blockname-block';
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
// For recommended mode, use devq_aos() helper on individual elements
// $animate = (!$disable_animation && $is_recommended);

// Check required fields
if (!$field1) {
    echo 'Please fill out all required fields for this block.';
    return;
}

?>

<div class="<?php echo esc_attr($block_classes); ?>" <?php echo $block_id ? 'id="' . esc_attr($block_id) . '"' : ''; ?> <?php echo $aos_attributes; ?>>
    <div class="container">
        <!-- Block content here -->
    </div>
</div>

<?php
// Output responsive spacing CSS using unique block ID
output_block_spacing_css($margin_top, $margin_bottom, $margin_top_other, $margin_bottom_other, $block_id);
?>

<style>
.blockname-block {
    /* Base styles */
}

/* Tablet - 1199px and below */
@media (max-width: 1199px) {
    .blockname-block {
        /* Tablet styles */
    }
}

/* Mobile - 767px and below */
@media (max-width: 767px) {
    .blockname-block {
        /* Mobile styles */
    }
}
</style>
```

### Step 3: Create the ACF JSON

Create `acfjson/group_[blockname]_block.json`. Every block MUST have 3 tabs:

1. **Content** -- Block-specific fields
2. **Options** -- Margin Top, Margin Top Other, Margin Bottom, Margin Bottom Other, Custom Class, Custom ID
3. **Animation** -- Animation Type (select), Animation Duration (number), Disable Animation (true/false)

Use `blocks/image/code.php` and `acfjson/group_image_block.json` as the reference implementation.

#### ACF JSON Key Naming Convention

All field keys must be prefixed with the block name to avoid collisions:
- `field_[blockname]_content_tab`
- `field_[blockname]_[fieldname]`
- `field_[blockname]_options_tab`
- `field_[blockname]_margin_top`
- `field_[blockname]_animation_tab`
- `field_[blockname]_animation_type`

#### Options Tab Fields (copy exactly)

| Field | Type | Width | Choices | Conditional |
|-------|------|-------|---------|------------|
| Margin Top | select | 50% | none/small/medium/large/other | -- |
| Margin Top Other | number | 50% | append: "px", min: 0 | margin_top == other |
| Margin Bottom | select | 50% | none/small/medium/large/other | -- |
| Margin Bottom Other | number | 50% | append: "px", min: 0 | margin_bottom == other |
| Custom Class | text | 50% | prepend: "." | -- |
| Custom ID | text | 50% | prepend: "#" | -- |

#### Animation Tab Fields (copy exactly)

| Field | Type | Width | Default |
|-------|------|-------|---------|
| Animation Type | select | 50% | recommended |
| Animation Duration | number | 25% | 800, append: "ms", min: 300, max: 3000 |
| Disable Animation | true_false | 25% | 0 |

Animation Type choices: **recommended** (first/default), fade-up, fade-down, fade-left, fade-right, fade-up-right, fade-up-left, fade-down-right, fade-down-left, flip-up, flip-down, flip-left, flip-right, slide-up, slide-down, slide-left, slide-right, zoom-in, zoom-in-up, zoom-in-down, zoom-in-left, zoom-in-right, zoom-out

### Animation System

The animation system has two modes controlled by the Animation Type field:

**Recommended mode** (`$is_recommended = true`): Smart per-element animations tailored to each block type. No AOS on the outer container. Instead, individual elements get `devq_aos()` calls with staggered delays. Use `functions/animations.php` helper:

```php
// Helper: devq_aos($type, $delay, $duration) returns AOS data attributes string
echo devq_aos('fade-up', 100, $animation_duration);
// Output: data-aos="fade-up" data-aos-delay="100"
```

**Manual mode** (any other type): AOS applied to the outer container as a whole. No per-element animation.

#### Recommended Animation Patterns by Block Type

| Category | Blocks | Recommended Behavior |
|----------|--------|---------------------|
| Hero stagger | hero, herovideo, herofullscreen | Per-element fade-up with 100ms stagger |
| Hero slider | heroslider | No AOS (Slick handles transitions) |
| Split layouts | herosplit, textimage, about, contactsplit | Opposing directions based on layout (text fades from its side, image from opposite) |
| Card/grid stagger | cards, team, pricing, stats, featureslist, process, blogposts, testimonials | Header fade-up, items stagger fade-up |
| Timeline | timeline | Header fade-up, items directional (left items fade-right, right items fade-left) |
| Gallery/logobar | gallery, logobar | Header fade-up, items stagger fade-up |
| CTA stagger | cta | Per-element fade-up stagger |
| Banner | banner | fade-down (slides from top) |
| Header+content | video, beforeafter, tabs, comparisontable | Header fade-up, content fade-up with delay |
| Simple | content, faq, map, marquee | fade-up on container |
| Image | image | zoom-in on container |

## CSS Rules

### CSS Variables (set dynamically from Theme Settings)

```css
--primary          /* Primary brand color */
--secondary        /* Secondary brand color */
--tertiary         /* Accent color */
--font1            /* Heading font family */
--font2            /* Body font family */
--heading-weight   /* Heading font weight */
--body-weight      /* Body font weight */
--body-size        /* Body font size */
--button-radius    /* Button border radius */
--button-padding   /* Button padding */
--spacing-small    /* Small spacing value */
--spacing-medium   /* Medium spacing value */
--spacing-large    /* Large spacing value */
--section-padding-top     /* Section top padding */
--section-padding-bottom  /* Section bottom padding */
--transition-default      /* Standard transition: all 0.3s ease-in-out */
--transition-fast         /* Fast transition: all 0.1s ease-in-out */
```

### Class Naming

- All CSS classes MUST be unique to the block: `.blockname-block`, `.blockname-content`, etc.
- Outer wrapper: `container-fluid blockname-block`
- Inner wrapper: `container`
- NEVER set font-size on h1-h6, p, ul, ol, li (globally controlled)

### Breakpoints

- **Tablet:** `@media (max-width: 1199px)`
- **Mobile:** `@media (max-width: 767px)`
- Only use these two breakpoints. Do NOT use 991px.

### Buttons

Use the `.btn` class system for all buttons. Do NOT use `.btn-inline`.

| Class | Usage |
|-------|-------|
| `btn` | Primary button (solid primary bg, white text) |
| `btn btn-secondary` | Secondary color variant |
| `btn btn-tertiary` | Tertiary color variant |
| `btn btn-white` | White bg, primary text (for dark backgrounds) |
| `btn btn-outline` | Transparent bg, primary border/text |
| `btn btn-outline-secondary` | Transparent bg, secondary border |
| `btn btn-outline-white` | Transparent bg, white border/text (for dark backgrounds) |

## Images -- always use devq_image()

Never hand-write `<img src="<?php echo $url; ?>">` in a block. That serves the full-size original
with no `srcset` and no width/height -- multiple MB on one page, plus a CLS penalty.

```php
<?php echo devq_image(get_field('photo'), 'large', array('class' => 'servicecard-img')); ?>
```

Accepts an ACF image array, an attachment ID, or a URL. Real attachments get `srcset`, `sizes`,
intrinsic dimensions and the alt text from the media library. Pick the `$size` that matches how
big the image actually renders -- a 300px-wide card does not need `full`.

## Escaping Rules

- Text content: `esc_html()`
- URLs: `esc_url()`
- HTML attributes: `esc_attr()`
- Rich text / WYSIWYG: `wp_kses_post()`
- CSS in style tags: `wp_strip_all_tags()`

## Available JS Libraries

Always loaded:
- **jQuery** (WordPress bundled)
- **AOS** -- Scroll animations (initialized in footer)
- **Mobile Menu** -- Custom vanilla JS (`assets/js/mobile-menu.js`)

Conditionally loaded (auto-detected from page content):
- **Slick** -- Carousel/slider (loaded when Hero Slider or Testimonials blocks present)
- **BeefUp** -- Accordion (loaded when FAQ block present)
- **Magnific Popup** -- Lightbox/modal (loaded when Gallery block present)

Available but commented out in `functions/scripts.php` (uncomment when needed):
- **jQuery Validate** -- Form validation

## Repeater Field Pattern

```php
<?php if (have_rows('items')) :
    while (have_rows('items')) : the_row();
        $title = get_sub_field('title');
        $content = get_sub_field('content');
        ?>
        <div class="blockname-item">
            <h3><?php echo esc_html($title); ?></h3>
            <?php echo wp_kses_post($content); ?>
        </div>
        <?php
    endwhile;
endif; ?>
```

## Testing Checklist

After creating a block:
1. All 3 tabs appear (Content, Options, Animation)
2. Margin options work (None/Small/Medium/Large/Other)
3. Custom class and ID applied correctly
4. AOS animation works and can be disabled
5. Responsive design correct at 1199px and 767px
6. No console JS errors
7. No PHP errors
8. All output properly escaped

## Block Category

All blocks register under the `devq` category. The block slug is `acf/[filteredname]`.

## Spacing System

The spacing system is centralized in `functions/spacing.php`:
- `generate_unique_block_id($type)` -- Creates a unique ID for each block instance
- `output_block_spacing_css($top, $bottom, $top_other, $bottom_other, $id)` -- Outputs responsive `<style>` tag
- Desktop values: Small (20px), Medium (40px), Large (80px) -- configurable in Theme Settings
- Mobile values: Small (15px), Medium (25px), Large (40px) -- auto-applied at 767px

## New Site Setup

1. Clone the kit into the site's themes directory and rename it to the client slug:
   ```bash
   git clone https://github.com/Jyager31/devq-starter-kit.git {client-slug}
   rm -rf {client-slug}/.git
   ```
2. Set `Theme Name:` in `style.css` to the client name. Leave the `DevQ Kit:` line.
3. Install plugins. On Local for Windows, `wp plugin install <zip>` does not work -- unzip into
   `wp-content/plugins/` directly, then `wp plugin activate`.
4. Activate the theme.
5. Fill in Theme Settings (branding, contact, social, styles).
6. Write `header.php` and `footer.php` to the site's design.
7. Build the site's blocks. One block per section, designed for this site.

There is no bootstrap script and no child theme step.

## Programmatic Page Creation

### Creating a Page with Blocks

Use `devq_create_page()` to create pages programmatically:

```php
$post_id = devq_create_page(array(
    'title' => 'About Us',
    'slug' => 'about',
    'status' => 'publish',
    'blocks' => array(
        array(
            'name' => 'Hero',
            'fields' => array(
                'heading' => 'About Our Company',
                'subheading' => 'Learn more about what we do',
                'overlay_opacity' => 60,
            ),
        ),
        array(
            'name' => 'Content',
            'fields' => array(
                'content' => '<p>Company description here.</p>',
            ),
        ),
    ),
));
```

### WP-CLI Commands

```bash
# Bulk create from JSON file
wp devq bulk-create --file=pages.json

# List registered blocks
wp devq list-blocks
```

### REST API (fallback when WP-CLI unavailable)

```bash
curl -X POST http://localhost/wp-json/devq/v1/create-page \
  -H "Content-Type: application/json" \
  -u "admin:password" \
  -d '{"title":"About","status":"draft","blocks":[]}'
```

### Notes

- Block data lives in the `post_content` Gutenberg block comment, **not** post meta. That
  comment is authoritative for both the front end and the editor.
- Pass block field values under the `fields` key, not `data`:
  `array('name' => 'Proof Cards', 'fields' => array('heading' => '...'))`.
- Repeaters are flattened for you into ACF's `name`, `name_0_sub`, `_name_0_sub` shape. Verify
  a new block round-trips before building 20 pages on it.
