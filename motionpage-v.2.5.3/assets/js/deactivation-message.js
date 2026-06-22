window.onload = function () {
	const pluginSelector = document.querySelector('[data-slug="motion-page-plugin"]');

	pluginSelector.insertAdjacentHTML(
		"beforeend",
		'<div id="mp-deactivate-popover" role="dialog" style="opacity:0; display: grid;position: fixed;top: -100px; left: -100px;background: white;padding: 10px;border-radius: 4px;width: 200px;box-shadow: 0px 2px 8px #dfdfdf;gap: 12px;"><div>Your personal details were successfully updated</div><div>You can change your details at any time in the user account section.</div><div style="display: grid;gap: 6px;"><div tabindex="0" role="button" aria-pressed="false" style="cursor: pointer;background: #72aee645;color: #2271b1;border-radius: 2px;padding: 4px;text-align: center;" onclick="_MP_DEACTIVATE_POPOVER()">Deactivate</div><div tabindex="0" role="button" aria-pressed="false" style="cursor: pointer;background: #72aee645;color: #2271b1;border-radius: 2px;padding: 4px;text-align: center;" onclick="_MP_CLOSE_POPOVER()">Close</div></div><div style="bottom: -1rem;left: 50%;transform: translate(-50%, -50%) rotate(45deg) scale(0.7);height: 1rem;width: 1rem;background-color: #fff;position: absolute;"></div></div>',
	);

	const deactivationSelector = document.querySelector('[data-slug="motion-page-plugin"] .deactivate a');

	deactivationSelector.addEventListener("click", function (e) {
		e.preventDefault();

		const rect = deactivationSelector.getBoundingClientRect();
		const p = document.getElementById("mp-deactivate-popover");
		p.style.top = `${rect.top - p.clientHeight - 14}px`;
		p.style.left = `${rect.left - p.clientWidth / 2 + deactivationSelector.offsetWidth / 2}px`;
		p.style.opacity = "1";
	});
};

function _MP_DEACTIVATE_POPOVER() {
	window.location.href = document
		.querySelector('[data-slug="motion-page-plugin"] .deactivate a')
		.getAttribute("href");
}

function _MP_CLOSE_POPOVER() {
	document.getElementById("mp-deactivate-popover").style.opacity = "0";
}
