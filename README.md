# DevQ Starter Kit

A WordPress **site scaffold**, not a framework. Clone it, rename the directory to the client
slug, and build. One theme per site -- no parent, no child theme, no premade blocks.

Kit repo: `Jyager31/devq-starter-kit`

## Quick Start

```bash
cd wp-content/themes
git clone https://github.com/Jyager31/devq-starter-kit.git acme-roofing
rm -rf acme-roofing/.git
# set "Theme Name: Acme Roofing" in style.css, then activate
```

Then: install ACF Pro, fill in Theme Settings, write `header.php` / `footer.php` to the design,
and build the site's blocks.

## Why there is no child theme

Every site used to get `devq-starter` (parent) plus `<slug>-child`. In practice the parent was
cloned per site and edited in place anyway, so it never delivered the shared-framework benefit
it charged for. What it did deliver:

- `devq_generate_block_markup()` resolved block field keys from the **parent** dir only, so a
  block defined in the child serialized its repeaters as a raw nested array. The block rendered
  on the front end and showed an **empty repeater in the editor**.
- Blocks print their CSS inline in the body, *after* the child stylesheet in `<head>` -- so a
  child rule lost every specificity tie it should have won.
- `archive.php` / `index.php` / `single.php` dispatched on `$layout_archive_style`, a variable
  only ever assigned inside `theme-settings-css.php` -- which is included from `header.php`, i.e.
  inside a function scope. Every archive and single post rendered a PHP warning and no content.

With one theme, `get_template_directory() === get_stylesheet_directory()` and all three are
structurally impossible.

## Blocks are built per site

`blocks/` ships empty. Every section of a site gets a block designed for that site, registered
through the `devq_blocks` filter:

```php
add_filter('devq_blocks', function ($blocks) {
    $blocks[] = 'Proof Cards';   // folder: blocks/proofcards/
    return $blocks;
});
```

The 30 blocks this theme used to ship live in the toolkit at
`Claude Code Toolkit/Commands/block-builder/_library/` and are **post-launch only** -- for an
already-launched site that needs a routine section. They are not a starting point for a build.

While `devq_get_blocks()` is empty, the page editor falls through to the full core block list
rather than locking up.

## File Structure

```
acfjson/              7 settings field groups (branding, contact, social,
                      styles, scripts, layout, 404) + your block groups
assets/               css: aos, reflex, slick, beefup, magnific
                      js:  mobile-menu, custom, vendor libs
blocks/               EMPTY. Your blocks go here.
functions/            acf, animations, blocks, emailnotifications, navwalker,
                      page-builder, posttype, scripts, shortcodes, spacing
images/               theme chrome (login logo, placeholders)
scripts/              site-health.php
template-parts/       archive/ and single/ layout variants
header.php            single file, rewrite to spec
footer.php            single file, rewrite to spec
theme-settings-css.php  ACF options -> :root CSS variables (inline, in <head>)
style.css             tokens + global CSS + header/footer baseline
```

## Theme Settings (ACF Options)

| Page | Fields |
|---|---|
| Branding | logo, alt logo, favicon, company name, header CTA, guidelines PDF |
| Contact | phone, email, address |
| Social | facebook, instagram, linkedin, youtube, twitter |
| Styles | colors, typography, buttons, spacing, section padding |
| Scripts | GA, GTM, FB Pixel, header/footer script blobs |
| Layouts | blog archive style, blog single style |
| 404 | title, message, search toggle, 3 links |

`header.php` and `footer.php` read the Branding / Contact / Social fields, so logo, phone and
social links stay client-editable even though the layout is bespoke.

Header, mobile-menu and footer **style variants were removed** -- they were deleted by hand on
every build anyway. Blog archive/single variants remain.

## Menus

Two registered locations: `primary` and `footer`. Use `theme_location`; the old hardcoded
`'menu' => 'Desktop'` lookup is gone.

## CSS

- Breakpoints: **1199px** (tablet) and **767px** (mobile). Never 991px.
- Variables from Theme Settings: `--primary`, `--secondary`, `--tertiary`, `--font1`, `--font2`,
  `--section-padding-top`, `--section-padding-bottom`, `--transition-default`.
- `theme-settings-css.php` emits `:root` inline in `<head>` **before** `wp_head()`. Leave it
  there -- the stylesheet is meant to win over it.

Per-block `style.css` / `script.js` are versioned by `filemtime`, so an edit busts cache. ACF
would otherwise stamp them with `ACF_VERSION`, which never moves.

## JS Libraries

jQuery, AOS, Slick, BeefUp, Magnific Popup. Slick/BeefUp/Magnific load conditionally based on
which blocks are on the page (`devq_page_has_block()`).

## Versioning

`Version:` in `style.css` is the source of truth. **There is no auto-updater.** The kit is
consumed by `git clone` at scaffold time and a scaffolded site is thereafter its own theme;
fixes flow forward to new sites, not backward to shipped ones.

`DevQ Kit:` in `style.css` records the kit commit a site was born from. Leave it in place.

## Docs

`CLAUDE.md` -- block authoring: field naming, the code.php template, ACF JSON conventions,
animation, escaping, spacing, and the programmatic page builder.
