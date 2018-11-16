$( function ()  {
	$(document).ready( function () {
		$('.oo-ui-inputWidget-input').keydown(
			function() {
				$(".oo-ui-buttonElement-button[name=clearpages]").css({ "value": "Preview", "name":"Preview", "background-color": "#36c", "border-color": "#36c"  });
				$(".oo-ui-buttonElement-button[name=clearpages]").attr( "name", "preview");
				$(".oo-ui-buttonElement-button[name=preview]").html("Preview");
			}
		);
	});
} );
