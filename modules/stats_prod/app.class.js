/*
Document   :  WooZoneLightStatsProd
Author     :  Andrei Dinca, AA-Team http://codecanyon.net/user/AA-Team
*/

// Initialization and events code for the app
WooZoneLightStatsProd = (function ($) {
    "use strict";

    // public
    var debug_level = 0;
    var maincontainer = null;
    var loading = null;
    var loaded_page = 0;

	// init function, autoload
	(function init() {
		// load the triggers
		$(document).ready(function(){
			maincontainer = $("#WooZoneLight-wrapper");
			loading = maincontainer.find("#WooZoneLight-main-loading");

			triggers();
		});
	})();

	function row_loading( row, status )
	{
		if( status == 'show' ){
			if( row.size() > 0 ){
				if( row.find('.WooZoneLight-row-loading-marker').size() == 0 ){
					var row_loading_box = $('<div class="WooZoneLight-row-loading-marker"><div class="WooZoneLight-row-loading"><div class="WooZoneLight-meter WooZoneLight-animate" style="width:30%; margin: 22px 0px 0px 30%;"><span style="width:100%"></span></div></div></div>')
					row_loading_box.find('div.WooZoneLight-row-loading').css({
						'width': row.width(),
						'height': row.height(),
						'top': '-16px'
					});

					row.find('td').eq(0).append(row_loading_box);
				}
				row.find('.WooZoneLight-row-loading-marker').fadeIn('fast');
			}
		}else{
			row.find('.WooZoneLight-row-loading-marker').fadeOut('fast');
		}
	}

	function triggers()
	{
	}

	// external usage
	return {
    }
})(jQuery);
