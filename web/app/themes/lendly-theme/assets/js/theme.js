/* Mobile navigation toggle. */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.querySelector('.nav-toggle');
		if (!btn) return;
		btn.addEventListener('click', function () {
			var open = document.body.classList.toggle('nav-open');
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && document.body.classList.contains('nav-open')) {
				document.body.classList.remove('nav-open');
				btn.setAttribute('aria-expanded', 'false');
				btn.focus();
			}
		});
	});
})();
