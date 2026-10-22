document.addEventListener('click', (event) => {
	if (!(event.target instanceof Element)) {
		return;
	}

	const button = event.target.closest('[data-fill-demo]');

	if (!(button instanceof HTMLButtonElement)) {
		return;
	}

	const rolePicker = document.querySelector('#demo-role');
	const identifier = document.querySelector('#identifier');
	const password = document.querySelector('#password');
	const status = document.querySelector('#demo-access-status');
	const selectedIdentifier = rolePicker?.value;

	if (!(identifier instanceof HTMLInputElement) || !(password instanceof HTMLInputElement) || !selectedIdentifier) {
		return;
	}

	identifier.value = selectedIdentifier;
	password.value = 'password';
	status.textContent = `Demo credentials filled for ${rolePicker.selectedOptions[0].textContent}.`;
	identifier.focus();
});
