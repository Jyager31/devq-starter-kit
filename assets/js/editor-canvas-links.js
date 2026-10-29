/**
 * Links and forms in the editor canvas go nowhere.
 *
 * On 7.1+ the canvas is always an iframe and an ACF block is always a live
 * preview of its template -- so a block that wraps its image in a link (most
 * hero and banner blocks) is a real, clickable <a> in there. Clicking the block
 * to select it follows the link: the iframe navigates to the link's page, the
 * canvas is now a cross-origin document, ACF's block preview throws a
 * SecurityError reading it, the block editor's iframe handler throws on the
 * null document, and the whole screen is replaced with "The editor has
 * encountered an unexpected error." Nothing in core or ACF stops it.
 *
 * Found on ITVT 2026-10-14: a linked graphic on TVOT Connect, and the linked
 * home slideshow, could not be clicked without crashing the editor.
 *
 * Capture phase, so it runs before anything a block's own script attached.
 * preventDefault only -- the click still reaches the block and selects it.
 * Rich text is left alone: a link inside contenteditable never navigates, and
 * cancelling the click there would fight caret placement.
 *
 * Loaded through enqueue_block_assets (functions/editor-canvas.php), which also fires on the outer admin
 * page; the frame check keeps it out of there, where it would break every link
 * in wp-admin.
 */
(function () {
    var frame = window.frameElement;

    if (!frame || frame.name !== 'editor-canvas') {
        return;
    }

    document.addEventListener('click', function (e) {
        var link = e.target && e.target.closest ? e.target.closest('a[href]') : null;

        if (!link || link.closest('[contenteditable="true"]')) {
            return;
        }

        e.preventDefault();
    }, true);

    // A Gravity Form or search box in a preview navigates the frame the same way.
    document.addEventListener('submit', function (e) {
        e.preventDefault();
    }, true);
})();
