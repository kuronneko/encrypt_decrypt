$(document).ready(function() {
	$('select').select2({
		//minimumInputLength: 3, // only start searching when the user has input 3 or more characters
		minimumResultsForSearch: 8,
		language: "es"
	});
});