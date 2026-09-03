/**
 * Does the editing experience still actually work?
 *
 * functions/editor-contract.php checks what core and ACF SHIPPED -- it greps
 * their files for the class names and behaviours this theme leans on. That
 * catches a rename or a removal, and it runs over SSH with no browser, which is
 * what makes it usable across a fleet.
 *
 * What a grep cannot tell you is whether our CSS still WINS. A class can survive
 * a release while core adds a rule with more specificity, or moves where the
 * width is set, and then the panel quietly returns to its default with every
 * selector still matching something. That only shows up in a live editor, so
 * this measures the finished result:
 *
 *   - the inspector is the width we asked for
 *   - the canvas is not capping full-bleed blocks at the content width
 *   - a field ACF marked 50% is rendering full width while the panel is narrow
 *   - the store method the "Add section" buttons need still exists
 *
 * Silent when everything holds. It only speaks up when something has moved, and
 * only for users who can do something about it -- functions/admin-ux.php loads
 * it for manage_options only, so a client never sees this.
 *
 * Re-run by hand at any time with devqEditorContract() in the console.
 */
(function () {
    function check(canvas) {
        var problems = [];

        // --- The inspector is the width we asked for --------------------------
        var sidebar = document.querySelector('.interface-interface-skeleton__sidebar');
        var wanted = parseInt(
            getComputedStyle(document.documentElement).getPropertyValue('--devq-inspector-w'),
            10
        );

        if (!sidebar) {
            problems.push('The inspector sidebar was not found. .interface-interface-skeleton__sidebar has probably been renamed, which takes the panel width and the drag handle with it.');
        } else if (window.innerWidth >= 1200 && wanted) {
            var actual = Math.round(sidebar.getBoundingClientRect().width);

            if (Math.abs(actual - wanted) > 2) {
                problems.push(
                    'The inspector is ' + actual + 'px but should be ' + wanted + 'px. ' +
                    'The class still exists, so core is now winning the width -- admin-ux.css needs a look.'
                );
            }
        }

        // --- Full-bleed blocks are not boxed in the canvas ---------------------
        if (!canvas) {
            problems.push('No iframe[name="editor-canvas"]. If core stopped iframing the canvas, ACF blocks may be directly editable again and several things in this theme are solving a problem that is gone.');
        } else {
            var doc = canvas.contentDocument;
            var wrapper = doc && doc.querySelector('.wp-block:has(> [data-type^="acf/"])');

            if (wrapper && getComputedStyle(wrapper).maxWidth !== 'none') {
                problems.push(
                    'ACF blocks are being capped at ' + getComputedStyle(wrapper).maxWidth +
                    ' in the canvas, so full-bleed sections preview boxed with gutters. ' +
                    'The rule in editor-canvas.css is no longer reaching them.'
                );
            }
        }

        // --- One field per row is winning over ACF's inline width -------------
        if (document.body.classList.contains('devq-inspector-narrow') && sidebar) {
            var half = null;
            var fields = sidebar.querySelectorAll('.acf-fields > .acf-field');

            for (var i = 0; i < fields.length; i++) {
                if (fields[i].style.width && fields[i].style.width !== '100%' && fields[i].offsetParent !== null) {
                    half = fields[i];
                    break;
                }
            }

            if (half) {
                var fieldW = half.getBoundingClientRect().width;
                var rowW = half.parentElement.getBoundingClientRect().width;

                if (rowW - fieldW > 8) {
                    problems.push(
                        'A field ACF marked ' + half.style.width + ' is rendering at ' + Math.round(fieldW) +
                        'px inside a ' + Math.round(rowW) + 'px row, so fields are still sharing rows in a narrow panel. ' +
                        'ACF has probably changed how it writes field widths.'
                    );
                }
            }
        }

        // --- The "Add section" buttons still have a store to talk to ----------
        var hasInserterApi = false;

        try {
            hasInserterApi = typeof wp.data.dispatch('core/editor').setIsInserterOpened === 'function' ||
                typeof wp.data.dispatch('core/edit-post').setIsInserterOpened === 'function';
        } catch (e) {}

        if (!hasInserterApi) {
            problems.push('setIsInserterOpened is gone from both core/editor and core/edit-post. "Add section above / below" cannot place the inserter, so it will insert at the end of the page instead.');
        }

        // --- The extension points the toolbar buttons are built on -------------
        var missing = [];

        if (!window.wp || !wp.hooks) { missing.push('wp.hooks'); }
        if (!window.wp || !wp.blockEditor || !wp.blockEditor.BlockControls) { missing.push('wp.blockEditor.BlockControls'); }
        if (!window.wp || !wp.components || !wp.components.ToolbarButton) { missing.push('wp.components.ToolbarButton'); }
        if (!window.wp || !wp.compose || !wp.compose.createHigherOrderComponent) { missing.push('wp.compose.createHigherOrderComponent'); }

        if (missing.length) {
            problems.push('Missing editor packages: ' + missing.join(', ') + '. The pencil and the add-section buttons will not render at all.');
        }

        return problems;
    }

    function report(problems) {
        if (!problems.length) {
            return true;
        }

        /* eslint-disable no-console */
        console.group('%cDevQ editor contract', 'font-weight:600');
        console.warn(
            problems.length + ' assumption(s) this theme makes about the editor no longer hold. ' +
            'The site is fine; editing is worse. See "After a WordPress or ACF update" in the theme CLAUDE.md.'
        );
        problems.forEach(function (p) { console.warn('• ' + p); });
        console.groupEnd();
        /* eslint-enable no-console */

        return false;
    }

    function run() {
        try {
            return report(check(document.querySelector('iframe[name="editor-canvas"]')));
        } catch (e) {
            /* eslint-disable-next-line no-console */
            console.warn('DevQ editor contract check could not run: ' + e);
            return false;
        }
    }

    window.devqEditorContract = run;

    // Late enough that the canvas has rendered its blocks and ACF has drawn its
    // fields; measuring before that reports failures that are really just timing.
    window.addEventListener('load', function () {
        window.setTimeout(run, 4000);
    });
}());
