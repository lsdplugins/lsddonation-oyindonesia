// TODO :: Adding nonce to Verify Trigger Listener
document.addEventListener('lsdd-oyindonesia-payment', function (e) {

	alert("OY");
	// Empty Cart
	let cart = new LSDD_CRUD('_lsdd_cart');
	cart.reset();

	// Redirect to OYIndonesia Payment Page
	if (e.detail.param) {
		window.location = e.detail.param;
	}

}, false);