$( function ()  {
	$(document).ready( function () {
		$('.oo-ui-inputWidget-input').change(
			function() {
				var clearButton = document.getElementsByClassName("oo-ui-buttonElement-button");
				var clearButtonText = clearButton[1].childNodes;
				clearButton[1].value = "Preview";
				clearButton[1].name = "Preview";
				clearButton[1].style.backgroundColor = '#36c';
				clearButton[1].style.borderColor = '#36c';
				clearButtonText[1].innerHTML = 'Preview';
			}
		);
	});

} );
