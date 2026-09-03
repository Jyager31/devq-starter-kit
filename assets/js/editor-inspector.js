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
 *    edge to set the width you want; that one is remembered per browser.
 *
 * 2. The gesture is gone. Before 7.1 a client clicked the block and typed into
 *    it. Now the fields are in a panel they have to know to go and open. The
 *    pencil in the block toolbar restores the gesture: click the block, click
 *    the pencil, get the fields -- wide.
 *
 * TWO WIDTHS, AND ONLY ONE OF THEM STICKS.
 *
 * The base width is what the client dragged, and it persists. Wide is a mode you
 * are in while editing one block, and it does NOT persist -- an earlier build
 * saved it, so one click of the pencil left every future editor session opening
 * at 1100px with nothing obvious to bring it back. Anything that can put the
 * panel into wide mode can take it out again: the pencil, double-clicking the
 * drag handle, Escape, and a reload.
 *
 * editor.BlockEdit is the supported extension point for the button; nothing here
 * patches ACF or core internals. Both halves are independent -- if the wp.*
 * packages this expects are ever missing, the resize still works.
 *
 * Widths are written to --devq-inspector-w. assets/css/admin-ux.css owns what
 * that does, including the >=1200px gate, so a small screen never gets a panel
 * wider than its canvas. This file also toggles .devq-inspector-narrow on the
 * body, which is what decides whether fields stack one per row.
 */
(function (wp) {
    var KEY = 'devqInspectorWidth';
    var MIN = 320;
    var DEFAULT = 480;

    // Below this the panel is too narrow to put two fields beside each other:
    // a 50/50 pair splits it and a number input with a unit appended clips to a
    // digit. Above it, ACF's own field widths are worth having back. Keep in
    // step with the .devq-inspector-narrow rules in admin-ux.css.
    var STACK_BELOW = 560;

    // The width rules in admin-ux.css are gated on this, because under it there
    // is no canvas left to take the room from.
    var MIN_WINDOW = 1200;

    var SIDEBAR = '.interface-interface-skeleton__sidebar';

    var base = DEFAULT;   // what the client dragged to. Persisted.
    var wide = false;     // a mode, for editing one block. Never persisted.

    /**
     * Widest the panel may go: two thirds of the window, hard capped, so there
     * is always a canvas left to preview into.
     */
    function maxWidth() {
        return Math.max(MIN, Math.min(1100, Math.round(window.innerWidth * 0.66)));
    }

    function clamp(w) {
        return Math.max(MIN, Math.min(maxWidth(), Math.round(w)));
    }

    function targetWidth() {
        return wide ? maxWidth() : base;
    }

    function sidebar() {
        return document.querySelector(SIDEBAR);
    }

    function isOpen() {
        var el = sidebar();

        // The sidebar stays in the DOM when closed, at zero width.
        return !!el && el.getBoundingClientRect().width > 2;
    }

    /**
     * Push the current state into the page: the width variable, the stacking
     * class, the grip's position, and a notification for the toolbar button.
     */
    function render() {
        var w = targetWidth();
        document.documentElement.style.setProperty('--devq-inspector-w', w + 'px');

        // Under MIN_WINDOW the CSS ignores the variable and WordPress's own
        // 280px applies, so the panel is narrow whatever this says.
        var effective = window.innerWidth < MIN_WINDOW ? 280 : w;
        document.body.classList.toggle('devq-inspector-narrow', effective < STACK_BELOW);

        place();

        window.dispatchEvent(new CustomEvent('devq-inspector-change'));
    }

    function setBase(w) {
        base = clamp(w);

        try {
            localStorage.setItem(KEY, String(base));
        } catch (e) {}

        render();
    }

    function expand() {
        wide = true;
        render();
    }

    function collapse() {
        wide = false;
        render();
    }

    try {
        var stored = parseInt(localStorage.getItem(KEY), 10);

        if (stored) {
            base = clamp(stored);
        }
    } catch (e) {}

    // ─── Drag handle ─────────────────────────────────────────────────────────

    var grip = document.createElement('div');
    grip.className = 'devq-inspector-grip';
    grip.setAttribute('title', 'Drag to resize this panel. Double-click to open it wide.');
    grip.hidden = true;
    document.body.appendChild(grip);

    function place() {
        var el = sidebar();

        if (!el || window.innerWidth < MIN_WINDOW) {
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
        grip.style.left = (r.left - 4) + 'px';
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

        // A drag is an explicit choice of width, so it ends wide mode and
        // becomes the width that sticks.
        wide = false;
        setBase(window.innerWidth - e.clientX);
    });

    window.addEventListener('mouseup', function () {
        if (!dragging) {
            return;
        }

        dragging = false;
        document.body.classList.remove('devq-inspector-resizing');
    });

    grip.addEventListener('dblclick', function () {
        if (wide) {
            collapse();
        } else {
            expand();
        }
    });

    window.addEventListener('resize', function () {
        base = clamp(base);
        render();
    });

    // Escape is the habit for "get me out of this", and someone who has lost the
    // drag handle will try it. Only while wide, so it never eats the key from a
    // modal or an ACF field that wants it.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && wide) {
            collapse();
        }
    });

    // The sidebar opens, closes and changes height as panels come and go, and
    // none of that fires an event worth listening for. A cheap poll keeps the
    // grip glued to it without a subtree MutationObserver, which in this editor
    // fires constantly.
    render();
    setInterval(place, 300);

    // ─── "Edit fields" in the block toolbar ──────────────────────────────────

    if (!wp || !wp.hooks || !wp.element || !wp.blockEditor || !wp.components || !wp.compose || !wp.data) {
        return;
    }

    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var BlockControls = wp.blockEditor.BlockControls;
    var ToolbarGroup = wp.components.ToolbarGroup;
    var ToolbarButton = wp.components.ToolbarButton;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;

    if (!BlockControls || !ToolbarGroup || !ToolbarButton || !createHigherOrderComponent || !useState) {
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
        // Open and already wide means the click is asking for the preview back,
        // which is the second half of the old toggle.
        if (wide && isOpen()) {
            collapse();
            return;
        }

        openInspector();

        // The sidebar has to exist before it can be measured and widened.
        window.setTimeout(expand, 60);
    }

    /**
     * The button, held in step with the panel so it reads as pressed while the
     * panel is wide -- otherwise nothing on screen says clicking it again is
     * what closes it.
     */
    function EditFieldsButton() {
        var state = useState(wide);
        var isWide = state[0];
        var setIsWide = state[1];

        useEffect(function () {
            var sync = function () { setIsWide(wide); };
            window.addEventListener('devq-inspector-change', sync);

            return function () {
                window.removeEventListener('devq-inspector-change', sync);
            };
        }, []);

        return el(
            BlockControls,
            null,
            el(
                ToolbarGroup,
                null,
                el(ToolbarButton, {
                    icon: 'edit',
                    label: isWide ? 'Done editing fields' : 'Edit fields',
                    isPressed: isWide,
                    showTooltip: true,
                    onClick: onEditFields
                })
            )
        );
    }

    var withEditFieldsButton = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            if (!props.isSelected || String(props.name).indexOf('acf/') !== 0) {
                return el(BlockEdit, props);
            }

            return el(Fragment, null, el(BlockEdit, props), el(EditFieldsButton, null));
        };
    }, 'withDevqEditFieldsButton');

    wp.hooks.addFilter('editor.BlockEdit', 'devq/edit-fields-button', withEditFieldsButton);
}(window.wp));
