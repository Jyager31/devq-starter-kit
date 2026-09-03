/**
 * One-time editor nudge.
 *
 * WordPress iframes the post editor canvas. ACF detects that iframe and pins every
 * ACF block to preview mode with no edit toggle, so a block's fields can only be
 * reached by selecting the block and using the Block tab of the inspector. List
 * View is how you select one without hunting through a rendered page.
 *
 * That makes List View load-bearing rather than optional, so open it by default
 * the first time someone edits here. This runs ONCE per user -- PHP sets a flag
 * and stops enqueueing it -- so anyone who closes List View keeps it closed.
 */
(function (wp) {
	if (!wp || !wp.domReady || !wp.data) {
		return;
	}

	wp.domReady(function () {
		var prefs = wp.data.dispatch('core/preferences');

		if (!prefs || typeof prefs.set !== 'function') {
			return;
		}

		// The preference moved from the 'core/edit-post' scope to 'core' during
		// WordPress 6.5. Write both; the scope that is not in use is inert.
		['core', 'core/edit-post'].forEach(function (scope) {
			try {
				prefs.set(scope, 'showListViewByDefault', true);
			} catch (e) {
				// A scope that does not exist on this version throws. Nothing to do.
			}
		});
	});
})(window.wp);
