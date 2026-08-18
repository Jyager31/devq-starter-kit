# ACF JSON Auto-Sync Directory

This directory enables automatic synchronization of ACF field groups using ACF's built-in Local JSON feature.

## How It Works

**Automatic Export:** When you save a field group in WordPress admin, ACF automatically saves a JSON file here.

**Automatic Import:** When WordPress loads, ACF reads JSON files from this directory and makes fields available.

**Sync Detection:** ACF shows "Sync available" in admin when the database differs from JSON files.

## Workflow

### Creating/Editing Field Groups

1. Create or edit field groups in WordPress admin
2. Save the field group -- ACF automatically creates/updates JSON here
3. Commit JSON files to version control

### Adding AI-Created Blocks

1. Create the JSON file directly in this directory (e.g., `group_newblock_block.json`)
2. Visit **Custom Fields > Field Groups** in admin
3. Click "Sync available" if shown
4. Fields are now active

## File Naming

- Files are named `group_[identifier].json`
- Block field groups: `group_[blockname]_block.json`
- Theme settings: `group_theme_styles.json`
- Do not rename files -- ACF uses the internal `key` field to identify groups

## Current Files

- `group_image_block.json` -- Image block fields (Content, Options, Animation tabs)
- `group_content_block.json` -- Content block fields (Content, Options, Animation tabs)
- `group_theme_styles.json` -- Theme settings fields

## Important Notes

- Do not delete JSON files manually -- use WordPress admin to delete field groups
- All block field groups must include Content, Options, and Animation tabs
- See `CLAUDE.md` at the theme root for complete field structure requirements
