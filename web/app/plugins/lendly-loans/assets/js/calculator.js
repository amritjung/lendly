/* Lendly repayment calculator. Config comes from window.LENDLY (Loans > Settings). */
(function () {
	'use strict';

	var cfg = window.LENDLY || {};
	var PERIODS = { weekly: 52, fortnightly: 26, monthly: 12 };
	var money = new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD', maximumFractionDigits: 0 });
	var money2 = new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD' });

	/** Amortised repayment per period, fees included. */
	function repayment(amount, ratePct, months, frequency) {
		var ppy = PERIODS[frequency] || 12;
		var n = Math.round((months / 12) * ppy);
		var r = ratePct / 100 / ppy;
		var principal = amount + (cfg.establishmentFee || 0);
		var base = r === 0 ? principal / n : (principal * r) / (1 - Math.pow(1 + r, -n));
		var fee = ((cfg.monthlyFee || 0) * 12) / ppy;
		var per = base + fee;
		return { perPeriod: per, periods: n, total: per * n };
	}

	window.LendlyCalc = { repayment: repayment, money: money, money2: money2 };

	function init(root) {
		var amount = root.querySelector('[data-in="amount"]');
		var freq = root.querySelector('[data-in="frequency"]');
		var out = function (k) { return root.querySelector('[data-out="' + k + '"]'); };
		var applyUrl = root.getAttribute('data-apply-url');

		function term() {
			var t = root.querySelector('[data-in="term"]:checked');
			return t ? parseInt(t.value, 10) : cfg.termDefault;
		}

		function render() {
			var a = parseInt(amount.value, 10);
			var res = repayment(a, cfg.rateFrom, term(), freq.value);
			var pct = ((a - amount.min) / (amount.max - amount.min)) * 100;
			amount.style.setProperty('--fill', pct + '%');
			out('amount').textContent = money.format(a);
			out('repayment').textContent = money2.format(res.perPeriod) + ' / ' + freq.value.replace('ly', '');
			out('total').textContent = 'Total repayable ' + money.format(res.total) + ' over ' + res.periods + ' repayments';
			var url = new URL(applyUrl, window.location.origin);
			url.searchParams.set('amount', a);
			url.searchParams.set('term', term());
			out('apply-link').href = url.toString();
		}

		root.addEventListener('input', render);
		root.addEventListener('change', render);
		render();
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.lendly-calc').forEach(init);
	});
})();
