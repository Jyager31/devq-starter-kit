/**
 * "Add section above / below" in the block toolbar.
 *
 * Adding a section between two existing ones is the second thing a client tries
 * to do and the first one they give up on. Core's answer is a "+" that appears
 * when the pointer is inside the gap between two blocks, and on a DevQ site that
 * gap is the block spacing between two full-bleed sections -- a thin strip you
 * have to find, in a preview you cannot type into. It is not rendered at rest,
 * so it cannot be pinned open with CSS either.
 *
 * The Options menu has "Insert before" and "Insert after", but those insert a
 * paragraph. On a site built from sections, an empty paragraph is not a thing
 * anyone wants, and there is no obvious way from there to the section list.
 *
 * So: two buttons on the block itself. Click the section you want the new one
 * next to, then the arrow that points where it should go. The inserter opens
 * with its insertion point already set, so whatever gets picked lands exactly
 * there rather than at the end of the page.
 *
 * setIsInserterOpened({ rootClientId, insertionIndex }) is what carries that
 * position. Verified against a real insert: with two sections on the page and
 * insertionIndex 1, the new block arrived at index 1 rather than being appended.
 *
 * Only on acf/* blocks -- these say "section" because that is what they are on a
 * DevQ build, and a core paragraph is not one.
 */
(function (wp) {
    if (!wp || !wp.hooks || !wp.element || !wp.blockEditor || !wp.components || !wp.compose || !wp.data) {
        return;
    }

    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var BlockControls = wp.blockEditor.BlockControls;
    var ToolbarGroup = wp.components.ToolbarGroup;
    var ToolbarButton = wp.components.ToolbarButton;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;

    if (!BlockControls || !ToolbarGroup || !ToolbarButton || !createHigherOrderComponent) {
        return;
    }

    /**
     * Open the block inserter with its insertion point set next to a block.
     *
     * @param {string} clientId The block to insert next to.
     * @param {number} offset   0 for above it, 1 for below it.
     */
    function openInserterAt(clientId, offset) {
        var bes = wp.data.select('core/block-editor');
        var index = bes.getBlockIndex(clientId);

        if (typeof index !== 'number' || index < 0) {
            return;
        }

        var payload = {
            rootClientId: bes.getBlockRootClientId(clientId) || '',
            insertionIndex: index + offset
        };

        // Which store owns the inserter has moved between releases, so try each
        // and take the first that does not throw.
        var attempts = [
            function () { wp.data.dispatch('core/editor').setIsInserterOpened(payload); },
            function () { wp.data.dispatch('core/edit-post').setIsInserterOpened(payload); }
        ];

        for (var i = 0; i < attempts.length; i++) {
            try {
                attempts[i]();
                return;
            } catch (e) {}
        }
    }

    var withAddSectionButtons = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            if (!props.isSelected || String(props.name).indexOf('acf/') !== 0) {
                return el(BlockEdit, props);
            }

            return el(
                Fragment,
                null,
                el(BlockEdit, props),
                el(
                    BlockControls,
                    null,
                    el(
                        ToolbarGroup,
                        null,
                        el(ToolbarButton, {
                            icon: 'insert-before',
                            label: 'Add section above',
                            showTooltip: true,
                            onClick: function () { openInserterAt(props.clientId, 0); }
                        }),
                        el(ToolbarButton, {
                            icon: 'insert-after',
                            label: 'Add section below',
                            showTooltip: true,
                            onClick: function () { openInserterAt(props.clientId, 1); }
                        })
                    )
                )
            );
        };
    }, 'withDevqAddSectionButtons');

    wp.hooks.addFilter('editor.BlockEdit', 'devq/add-section-buttons', withAddSectionButtons);
}(window.wp));
