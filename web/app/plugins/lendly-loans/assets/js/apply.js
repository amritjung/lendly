/* Lendly multi-step application. Posts JSON to the REST endpoint in window.LENDLY_APPLY. */
(function () {
	'use strict';

	var api = window.LENDLY_APPLY || {};

	function init(root) {
		var form = root.querySelector('.lendly-form');
		var steps = Array.prototype.slice.call(form.querySelectorAll('.lendly-step'));
		var progress = root.querySelectorAll('[data-progress]');
		var btnBack = form.querySelector('[data-nav="back"]');
		var btnNext = form.querySelector('[data-nav="next"]');
		var btnSubmit = form.querySelector('[data-nav="submit"]');
		var alertBox = form.querySelector('.lendly-form__alert');
		var current = 0;

		function show(i, initial) {
			current = i;
			steps.forEach(function (s, idx) { s.hidden = idx !== i; });
			progress.forEach(function (p, idx) {
				p.classList.toggle('is-active', idx === i);
				p.classList.toggle('is-done', idx < i);
			});
			btnBack.hidden = i === 0;
			btnNext.hidden = i === steps.length - 1;
			btnSubmit.hidden = i !== steps.length - 1;
			if (i === steps.length - 1) buildReview();
			if (initial) return;
			var first = steps[i].querySelector('input, select');
			root.scrollIntoView({ behavior: 'smooth', block: 'start' });
			if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 250);
		}

		function setError(name, msg) {
			var el = form.querySelector('[data-error-for="' + name + '"]');
			var input = form.elements[name];
			if (el) el.textContent = msg || '';
			if (input && input.setAttribute) input.setAttribute('aria-invalid', msg ? 'true' : 'false');
		}

		function customCheck(input) {
			if (input.name === 'mobile') {
				var m = input.value.replace(/[\s()-]/g, '');
				return /^(\+?61|0)4\d{8}$/.test(m) ? '' : 'Enter an Australian mobile number, e.g. 0412 345 678.';
			}
			if (input.name === 'dob' && input.value) {
				var d = new Date(input.value + 'T00:00:00');
				var adult = new Date(); adult.setFullYear(adult.getFullYear() - 18);
				return d <= adult ? '' : 'You must be 18 or older to apply.';
			}
			return '';
		}

		function validateStep(i) {
			var ok = true;
			steps[i].querySelectorAll('input, select').forEach(function (input) {
				if (!input.name || input.type === 'hidden') return;
				var msg = '';
				if (!input.checkValidity()) {
					msg = input.type === 'checkbox' ? 'This consent is required.' : (input.validationMessage || 'Please check this field.');
				} else {
					msg = customCheck(input);
				}
				setError(input.name, msg);
				if (msg && ok) { ok = false; input.focus(); }
			});
			return ok;
		}

		function data() {
			var out = {};
			Array.prototype.forEach.call(form.elements, function (el) {
				if (!el.name) return;
				out[el.name] = el.type === 'checkbox' ? el.checked : el.value.trim();
			});
			return out;
		}

		function label(name) {
			var el = form.elements[name];
			if (!el) return '';
			if (el.tagName === 'SELECT') return el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : '';
			return el.value;
		}

		function buildReview() {
			var d = data();
			var calc = window.LendlyCalc;
			var est = calc ? calc.repayment(parseFloat(d.amount), (window.LENDLY || {}).rateFrom || 0, parseInt(d.term_months, 10), 'monthly') : null;
			var rows = [
				['Loan amount', calc ? calc.money.format(d.amount) : '$' + d.amount],
				['Term', label('term_months')],
				['Purpose', label('purpose')],
				['Name', d.first_name + ' ' + d.last_name],
				['Email', d.email],
				['Mobile', d.mobile],
				['Address', d.street + ', ' + d.suburb + ' ' + d.state + ' ' + d.postcode],
				['Income after tax', '$' + d.income_amount + ' ' + label('income_frequency').toLowerCase()],
				['Estimated repayment', est ? calc.money2.format(est.perPeriod) + ' / month (from lowest rate)' : '']
			];
			var dl = form.querySelector('[data-review]');
			dl.innerHTML = '';
			rows.forEach(function (r) {
				var dt = document.createElement('dt'); dt.textContent = r[0];
				var dd = document.createElement('dd'); dd.textContent = r[1];
				dl.appendChild(dt); dl.appendChild(dd);
			});
		}

		btnNext.addEventListener('click', function () {
			if (validateStep(current)) show(current + 1);
		});
		btnBack.addEventListener('click', function () { show(current - 1); });

		form.addEventListener('input', function (e) {
			if (e.target.name) setError(e.target.name, '');
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (!validateStep(current)) return;

			alertBox.hidden = true;
			btnSubmit.disabled = true;
			btnSubmit.textContent = 'Submitting…';

			fetch(api.endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', 'X-Lendly-Nonce': api.nonce, 'X-WP-Nonce': api.restNonce },
				body: JSON.stringify(data())
			})
				.then(function (res) { return res.json().then(function (body) { return { status: res.status, body: body }; }); })
				.then(function (r) {
					if (r.status === 200 && r.body.ok) {
						form.hidden = true;
						root.querySelector('.lendly-progress').hidden = true;
						var ok = root.querySelector('.lendly-success');
						ok.querySelector('[data-reference]').textContent = r.body.reference;
						ok.hidden = false;
						return;
					}
					var fields = (r.body.data && r.body.data.fields) || {};
					var firstBad = null;
					Object.keys(fields).forEach(function (name) {
						setError(name, fields[name]);
						var step = form.elements[name] && form.elements[name].closest('.lendly-step');
						var idx = steps.indexOf(step);
						if (idx > -1 && (firstBad === null || idx < firstBad)) firstBad = idx;
					});
					if (firstBad !== null && firstBad !== current) show(firstBad);
					throw new Error(r.body.message || 'Something went wrong.');
				})
				.catch(function (err) {
					alertBox.textContent = err.message || 'Network error. Please try again.';
					alertBox.hidden = false;
				})
				.finally(function () {
					btnSubmit.disabled = false;
					btnSubmit.textContent = 'Submit application';
				});
		});

		show(0, true);
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.lendly-apply').forEach(init);
	});
})();
