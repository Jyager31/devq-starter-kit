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
  (That dispatch is gone entirely as of 2026-09-03, along with `theme-settings-css.php`.)

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
acfjson/              4 settings field groups (branding, contact, social,
                      scripts) + your block groups
assets/               css: aos, reflex, slick, beefup, magnific,
                           editor-canvas (iframe), admin-ux (wp-admin)
                      js:  mobile-menu, custom, editor-insert, editor-inspector,
                           editor-onboarding,
                           vendor libs
blocks/               EMPTY. Your blocks go here.
functions/            acf, admin-ux, animations, blocks, editor-canvas,
                      emailnotifications, navwalker, page-builder, posttype,
                      scripts, shortcodes, spacing
images/               theme chrome (login logo, placeholders)
scripts/              site-health.php
header.php            single file, rewrite to spec
footer.php            single file, rewrite to spec
404.php               written to spec, hard-coded copy
archive.php           written to spec (ships a card grid)
single.php            written to spec (ships a classic hero)
style.css             the :root brand tokens + global CSS + header/footer baseline
```

## Theme Settings (ACF Options)

Four pages, on purpose.

| Page | Capability | Fields |
|---|---|---|
| Branding | `edit_posts` | logo, alt logo, favicon, company name, header CTA |
| Contact | `edit_posts` | phone, email, address |
| Social | `edit_posts` | facebook, instagram, linkedin, youtube, twitter |
| Scripts | `manage_options` | GA, GTM, FB Pixel, header/footer script blobs |

A setting earns a page here only if it genuinely **changes after launch** and is not a design
decision: a new phone number, a new Instagram account, a swapped logo, a marketing tag.
`header.php` and `footer.php` read these, so those stay client-editable even though the layout
is bespoke.

Removed 2026-09-03 -- read `devq_theme_settings_pages()` before adding any of them back:

| Page | Was | Now |
|---|---|---|
| **Styles** | 26 fields of colour, type, spacing, button geometry | the `:root` block in `style.css` |
| **Layouts** | picked between 6 `template-parts/{archive,single}/style-*.php` | `archive.php` / `single.php` written to spec |
| **404** | title, message, search toggle, 3 links | `404.php` written to spec |

Same reasoning that retired the header, mobile-menu and footer style variants: a designed
thing does not belong behind a dropdown, nobody ever picked anything but the default, and as
an options page it was a switch that restyled every template on the site sitting on a client's
account.

A sub page does **not** inherit its parent's capability, so each one is set individually --
setting only the parent leaves every child open, which is how an account handed out for content
work ends up able to inject JavaScript into every page. Move a page between tiers with the
`devq_theme_settings_pages` filter.

## Client editing experience

WordPress 7.1 iframes the post editor canvas unconditionally — `useShouldIframe()` is gone and
`editor.js` hardcodes `shouldIframe: true`. ACF detects that iframe and pins every ACF block to
**preview** with no edit toggle. Nothing turns it off, and defeating it breaks core's layout
without producing a form (tested). So on 7.1+ a client edits blocks entirely through the block
inspector, and the canvas is a preview they read. Details in `CLAUDE.md`.

The kit ships that as a working experience rather than leaving each build to rediscover it:

- `functions/editor-canvas.php` puts `style.css` (brand tokens included), the grid and every
  registered block's CSS **inside the iframe**. Without it every block previews as unstyled
  serif HTML — ACF's per-block `enqueue_style` does not reach the frame either.
- `assets/css/editor-fonts.css` repeats `header.php`'s webfonts as an `@import`, because an
  iframe cannot be handed a `<link>`. `site-health.php` fails if the two drift.
- `assets/css/editor-canvas.css` undoes front-end behaviour with no JS behind it in the
  editor, so an AOS block does not preview as an empty band.
- `assets/css/admin-ux.css` makes the inspector usable: 480px above 1200px, and one field per
  row while it is under 560px (ACF's 50/25% widths clip an input badly in a narrow panel; once it
  is wide they come back).
- `assets/js/editor-insert.js` adds **Add section above / below** to the block toolbar. Core's
  between-blocks "+" only appears while the pointer is in the gap between two sections, and its
  Options menu inserts a paragraph; these open the section list with the insertion point already
  set.
- `assets/js/editor-inspector.js` lets the client drag that panel wider, remembers it, and puts
  an **Edit fields** pencil in the block toolbar that opens it wide in one click -- the closest
  thing left to clicking a block and typing into it. Wide is a mode, not a saved width: the
  pencil, Escape, double-clicking the handle or a reload all bring the panel back.
- `devq_block_placeholder()` gives an empty block a labelled dashed box in the editor. Without
  it a block with an empty repeater renders nothing anywhere and nobody can see it exists.
- List View opens by default the first time a user edits, once.
- A **How to edit your site** panel sits at the top of the dashboard. Customise per site with
  the `devq_help_steps` and `devq_help_footnote` filters.

Because all of that leans on class names and behaviours core and ACF do not promise to keep,
`functions/editor-contract.php` writes each assumption down with a check for it: `site-health.php`
greps what core and ACF shipped (works over SSH, no browser), and `assets/js/editor-contract.js`
measures in a live editor whether our CSS still wins -- a class can survive a release while core
starts beating it, which looks identical from disk. Both stay silent until something moves.

Full mechanics, the traps, and the post-update checklist in `CLAUDE.md`.

## Menus

Two registered locations: `primary` and `footer`. Use `theme_location`; the old hardcoded
`'menu' => 'Desktop'` lookup is gone.

## CSS

- Breakpoints: **1199px** (tablet) and **767px** (mobile). Never 991px.
- **Brand tokens are the `:root` block at the top of `style.css`** -- colours, type, buttons,
  spacing. One source, read by the front end and by the editor canvas. They were an ACF
  Theme Settings > Styles page until 2026-09-03; do not put them back.
- Colour un-styled body copy with `var(--body-color)`. `style.css` names `p, ul, li` directly,
  and a rule matching an element beats an inherited value, so overriding `body { color }` on a
  dark build does not reach them.
- `scripts/site-health.php` warns when a token is still the kit's shipped default.

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
