/**
 * The block inspector: resizable, and reachable from the block itself.
 *
 * On WordPress 7.1+ the post editor canvas is always an iframe, and ACF pins
 * every block to preview whenever that iframe exists, with no edit/preview
 * toggle. Editing in the canvas is not recoverable -- forcing the toggle back on
 * renders the block with zero fields. So the inspector is the only editing
 * surface a client has, and two things follow:
 *
 * 1. A fixed width cannot be right for every block. A block with a repeater of
 *    cards needs more room than one with a heading and a button. Drag the left
 *    edge, or double-click it to snap between the default and wide. The width is
 *    remembered per browser.
 *
 * 2. The gesture is gone. Before 7.1 a client clicked the block and typed into
 *    it. Now the fields are in a panel they have to know to go and open. The
 *    pencil in the block toolbar restores the gesture: click the block, click
 *    the pencil, get the fields -- wide. Click it again for the preview back.
 *
 * editor.BlockEdit is the supported extension point for the button; nothing here
 * patches ACF or core internals. Both halves are independent -- if the wp.*
 * packages this expects are ever missing, the resize still works.
 *
 * Widths are written to --devq-inspector-w. assets/css/admin-ux.css owns what
 * that does, including the >=1200px gate, so a small screen never gets a panel
 * wider than its canvas.
 */
(function (wp) {
    var KEY = 'devqInspectorWidth';
    var MIN = 320;
    var DEFAULT = 480;
    var SIDEBAR = '.interface-interface-skeleton__sidebar';

    /**
     * Widest the panel may go: two thirds of the window, hard capped, so there
     * is always a canvas left to preview into.
     */
    function maxWidth() {
        return Math.max(MIN, Math.min(1100, Math.round(window.innerWidth * 0.66)));
    }

    function currentWidth() {
        var v = parseInt(document.documentElement.style.getPropertyValue('--devq-inspector-w'), 10);

        return v || DEFAULT;
    }

    function apply(w) {
        w = Math.max(MIN, Math.min(maxWidth(), Math.round(w)));
        document.documentElement.style.setProperty('--devq-inspector-w', w + 'px');

        try {
            localStorage.setItem(KEY, String(w));
        } catch (e) {}

        return w;
    }

    function isWide() {
        return currentWidth() > DEFAULT + 40;
    }

    function sidebar() {
        return document.querySelector(SIDEBAR);
    }

    function isOpen() {
        var el = sidebar();

        // The sidebar stays in the DOM when closed, at zero width.
        return !!el && el.getBoundingClientRect().width > 2;
    }

    var saved = DEFAULT;

    try {
        var stored = parseInt(localStorage.getItem(KEY), 10);

        if (stored) {
            saved = stored;
        }
    } catch (e) {}

    apply(saved);

    // ─── Drag handle ─────────────────────────────────────────────────────────

    var grip = document.createElement('div');
    grip.className = 'devq-inspector-grip';
    grip.setAttribute('title', 'Drag to resize this panel. Double-click to snap it wide.');
    grip.hidden = true;
    document.body.appendChild(grip);

    function place() {
        var el = sidebar();

        if (!el) {
            grip.hidden = true;
            return;
        }

        var r = el.getBoundingClientRect();

        if (r.width < 2) {
            grip.hidden = true;
            return;
        }

        grip.hidden = false;
        grip.style.top = r.top + 'px';
        grip.style.height = r.height + 'px';
        grip.style.left = (r.left - 3) + 'px';
    }

    var dragging = false;

    grip.addEventListener('mousedown', function (e) {
        if (!sidebar()) {
            return;
        }

        dragging = true;
        document.body.classList.add('devq-inspector-resizing');
        e.preventDefault();
    });

    window.addEventListener('mousemove', function (e) {
        if (!dragging) {
            return;
        }

        apply(window.innerWidth - e.clientX);
        place();
    });

    window.addEventListener('mouseup', function () {
        if (!dragging) {
            return;
        }

        dragging = false;
        document.body.classList.remove('devq-inspector-resizing');
    });

    grip.addEventListener('dblclick', function () {
        apply(isWide() ? DEFAULT : maxWidth());
        place();
    });

    window.addEventListener('resize', function () {
        apply(currentWidth());
        place();
    });

    // The sidebar opens, closes and changes height as panels come and go, and
    // none of that fires an event worth listening for. A cheap poll keeps the
    // grip glued to it without a subtree MutationObserver, which in this editor
    // fires constantly.
    place();
    setInterval(place, 300);

    // ─── "Edit fields" in the block toolbar ──────────────────────────────────

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
     * Open the block inspector. Which store owns the sidebar has moved between
     * releases, so try each and take the first that does not throw.
     */
    function openInspector() {
        var attempts = [
            function () { wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/block'); },
            function () { wp.data.dispatch('core/interface').enableComplementaryArea('core/edit-post', 'edit-post/block'); },
            function () { wp.data.dispatch('core/interface').enableComplementaryArea('core/editor', 'edit-post/block'); }
        ];

        for (var i = 0; i < attempts.length; i++) {
            try {
                attempts[i]();
                return true;
            } catch (e) {}
        }

        return false;
    }

    function onEditFields() {
        // Already open and already wide means the click is asking for the
        // preview back, which is the second half of the old toggle.
        if (isWide() && isOpen()) {
            apply(DEFAULT);
            place();
            return;
        }

        openInspector();

        // The sidebar has to exist before it can be measured and widened.
        window.setTimeout(function () {
            apply(maxWidth());
            place();
        }, 60);
    }

    var withEditFieldsButton = createHigherOrderComponent(function (BlockEdit) {
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
                            icon: 'edit',
                            label: 'Edit fields',
                            showTooltip: true,
                            onClick: onEditFields
                        })
                    )
                )
            );
        };
    }, 'withDevqEditFieldsButton');

    wp.hooks.addFilter('editor.BlockEdit', 'devq/edit-fields-button', withEditFieldsButton);
}(window.wp));
