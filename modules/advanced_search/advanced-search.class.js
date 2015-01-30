/*
Document   :  Amazon Advanced Search
Author     :  Andrei Dinca, AA-Team http://codecanyon.net/user/AA-Team
*/
// Initialization and events code for the app
WooZoneLightAdvancedSearch = (function ($) {
    "use strict";

    // public
    var ASINs = [];
    var loaded_products = 0;
    var debug_level = 0;

	// init function, autoload
	(function init() {
		// init the tooltip
		tooltip();

		// load the triggers
		$(document).ready(function(){
			var loading = $("#WooZoneLight-advanced-search #main-loading");

			triggers();
 
			load_categ_parameters( $(".WooZoneLight-categories-list li.on a") );

			// show debug hint
			console.log( '// want some debug?' );
			console.log( 'WooZoneLightAdvancedSearch.setDegubLevel(1);' );
		});
	})();

	function load_categ_parameters( that )
	{
		var loading = $("#WooZoneLight-advanced-search #main-loading");
		loading.css('display', 'block');
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, {
			'action' 		: 'WooZoneLightCategParameters',
			'categ'			: that.data('categ'),
			'nodeid'		: that.data('nodeid'),
			'debug_level'	: debug_level
		}, function(response) {
			if( response.status == 'valid' ){
				$('#WooZoneLight-parameters-container').html( response.html );

				// clear the products from right panel
				$(".WooZoneLight-product-list").html('');
			}

			loading.css('display', 'none');
		}, 'json');
	}

	function updateExecutionQueue()
	{
		var queue_list = $("input.WooZoneLight-items-select");
		if( queue_list.length > 0 ){
			$.each( queue_list, function(){
				var that = $(this),
					asin = that.val();

				if( that.is(':checked') ){
					// if not in global asins storage than push to array
					if( $.inArray( asin, ASINs) == -1 ){
						ASINs.push( asin );
					}
				}

				if( that.is(':checked') == false ){
					// if not in global asins storage than push to array
					if( $.inArray( asin, ASINs) > -1){
						// remove array key by value
						ASINs.splice( ASINs.indexOf(asin), 1 );
					}
				}
			});

		}else{
			// refresh the array list
			ASINs = [];
		}

		// update the queue list DOM
		if( ASINs.length > 0 ){
			var newHtml = [];
			$.each( ASINs, function( key, value ){
				var original_img = $("img#WooZoneLight-item-img-" + value);

				if( original_img.length > 0 ){
					newHtml.push( '<a href="#' + ( value ) + '" class="removeFromQueue" title="Remove from Queue">' );
					newHtml.push( 	'<img src="' + ( original_img.attr('src') ) + '" width="30" height="30">' );
					newHtml.push( 	'<span></span>' );
					newHtml.push( '</a>' );
				}
			});

			// append the new html DOM elements to queue container
			$("#WooZoneLight-execution-queue-list").html( newHtml.join( "\n" ) );
		}

		// clear the execution queue if not ASIN(s)
		else{
			$("#WooZoneLight-execution-queue-list").html( 'No item(s) yet' );

			// uncheck "select all" if need
			if( jQuery("#WooZoneLight-items-select-all").is(':checked') ){
				jQuery("#WooZoneLight-items-select-all").removeAttr('checked');
			}
		}
	}

	function launchSearch( that, reset_page )
	{
		var loading = $("#WooZoneLight-advanced-search #main-loading");
		loading.css('display', 'block');

		// get the current browse node
		var current_node = '';
		jQuery("#WooZoneLightGetChildrens select").each(function(){
		    var that_select = jQuery(this);

		    if( that_select.val() != "" ){
		        current_node = that_select.val();
		    }
		});

		var page = $("select#WooZoneLight-page").val() > 0 ? parseInt($("select#WooZoneLight-page").val(), 10) : 1;
		if( reset_page == true ){
			page = 1;
		}
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, {
			'action' 		: 'WooZoneLightLaunchSearch',
			'params'		: that.serialize(),
			'page'			: page,
			'node'			: current_node,
			'debug_level'	: debug_level
		}, function(response) {

			ASINs = [];
			$(".WooZoneLight-product-list").html( response );
			jQuery("#WooZoneLight-items-select-all").click();
			loading.css('display', 'none');
		}, 'html');
	}

	function tailProductImport( import_step, callback )
	{
		//console.log( import_step ); 
		// stop if not valid ASINs key
		if(typeof ASINs[import_step] == 'undefined') return false;

		var asin = ASINs[import_step];

		// increse the loaded products marker
		++loaded_products;

		// make the import
		jQuery.post(ajaxurl, {
			'action' 		: 'WooZoneLightImportProduct',
			'asin'			: asin,
			'category'		: $(".WooZoneLight-categories-list li.on a").data('categ'),
			'to-category'	: $("#amzStore-to-category").val(),
			'debug_level'	: debug_level
		}, function(response) {

			if( typeof response.status != 'undefined' && response.status == 'valid' ) {
				// show the download assets lightbox
				if( response.show_download_lightbox == true ){
					$("#WooZoneLight-wrapper").append( response.download_lightbox_html );
					
					WooZoneLightAssetDownload.download_asset( $('.WooZoneLight-images-tail').find('li').eq(0), undefined, 100, function(){
						
						$(".WooZoneLight-asset-download-lightbox").remove();
				
						jQuery('a.removeFromQueue[href$="#' + ( asin ) + '"]').html( '<span class="success"></span>' );

						// continue insert the rest of ASINs
						if( ASINs.length > import_step ) tailProductImport( ++import_step, callback );
		
						// execute the callback at the end of loop
						if( ASINs.length == import_step ){
							callback( loaded_products );
						}
					} );
				}
				else{
					jQuery('a.removeFromQueue[href$="#' + ( asin ) + '"]').html( '<span class="success"></span>' );

					// continue insert the rest of ASINs
					if( ASINs.length > import_step ) tailProductImport( ++import_step, callback );
	
					// execute the callback at the end of loop
					if( ASINs.length == import_step ){
						callback( loaded_products );
					}
				}
			} else {
				// alert('Unable to import product: ' + asin );
				// return false;
				
				var errMsg = '';
				if ( typeof response.status != 'undefined' )
					errMsg = response.msg;
				else
					errMsg = 'unknown error occured: could be related to max_execution_time, memory_limit server settings!';
 
				jQuery('a.removeFromQueue[href$="#' + ( asin ) + '"]').html( '<span class="error"></span>' );
				jQuery('.WooZoneLight-queue-table').find('tbody:last').append('<tr><td colspan=3>' + errMsg + '</td></tr>');

				// continue insert the rest of ASINs
				if( ASINs.length > import_step ) tailProductImport( ++import_step, callback );
	
				// execute the callback at the end of loop
				if( ASINs.length == import_step ){
					callback( loaded_products );
				}
			}

		}, 'json');
	}

	// public method
	function launchImport( that )
	{
		var loading = $("#WooZoneLight-advanced-search #main-loading");
		loading.css('display', 'block');
		if( ASINs.length == 0 ){
			alert( 'First please select products from the list!' );
			loading.css('display', 'none');
			return false;
		}
  
		tailProductImport( 0, function( loaded_products ){
			//console.log( 'done', loaded_products ) ;

			jQuery('body').find('#WooZoneLight-advanced-search .WooZoneLight-items-list tr.on').remove();
			loading.css('display', 'none');

			return true;
		});
	}

	function getChildNodes( that )
	{
		var loading = $("#WooZoneLight-advanced-search #main-loading");
		loading.css('display', 'block');

		// prev element valud
		var ascensor_value = that.val(),
			that_index = that.index();

		// max 3 deep
		if ( that_index > 10 ){
			loading.css('display', 'none');
			return false;
		}

		var container = $('#WooZoneLightGetChildrens');
		var remove = false;
		// remove items prev of current selected
		container.find('select').each( function(i){
			if( remove == true ) $(this).remove();
			if( $(this).index() == that_index ){
				remove = true;
			}
		});

		// store current childrens into array
		if( ascensor_value != "" ){
			// make the import
			jQuery.post(ajaxurl, {
				'action' 		: 'WooZoneLightGetChildNodes',
				'ascensor'		: ascensor_value,
				'debug_level'	: debug_level
			}, function(response) {
				if( response.status == 'valid' ){
					$('#WooZoneLightGetChildrens').append( response.html );

					loading.css('display', 'none');
				}
			}, 'json');

		}else{
			loading.css('display', 'none');
		}
	}

	function setDegubLevel( new_level )
	{
		debug_level = new_level;
		return "new debug level: " + debug_level;
	}

	function tooltip()
	{
		/* CONFIG */
		var xOffset = -40,
			yOffset = -250;

		/* END CONFIG */
		jQuery('body').on('mouseover', '.WooZoneLight-tooltip', function (e) {
			var img_src = $(this).data('img');
			console.log( $(this), img_src ); 
			$("body").append("<img id='WooZoneLight-tooltip' src="+ img_src +">");
			$("#WooZoneLight-tooltip")
				.css("top",(e.pageY - xOffset) + "px")
				.css("left",(e.pageX + yOffset) + "px")
				.show();
	  	});
		
		jQuery('body').on('mouseout', '.WooZoneLight-tooltip', function (e) {
			$("#WooZoneLight-tooltip").remove();
	    });
		jQuery('body').on('mousemove', '.WooZoneLight-tooltip', function (e) {
			$("#WooZoneLight-tooltip")
				.css("top",(e.pageY - xOffset) + "px")
				.css("left",(e.pageX + yOffset) + "px");
		});
	}

	function triggers()
	{
		jQuery('body').on('click', '.WooZoneLight-categories-list a', function (e) {
			e.preventDefault();

			var that = $(this),
				that_p = that.parent('li');

			// escape if is the same block
			if( that.parent('li').hasClass('on') ) return true;

			// get current clicked category paramertes
			load_categ_parameters(that);

			$(".WooZoneLight-categories-list li.on").removeClass('on');
			that_p.addClass('on');
		});
		
		jQuery('body').on('change', 'select.WooZoneLightParameter-sort', function (e) {
		    var that = $(this),
		        val = that.val(),
		        opt = that.find("[value=" + ( val ) + "]"),
		        desc = opt.data('desc');

		    $("p#WooZoneLightOrderDesc").html( "<strong>" + ( val ) + ":</strong> " + desc );
		});

		// check / uncheck all
		jQuery('body').on('change', '#WooZoneLight-items-select-all', function (e)
		{
			var that = $(this),
				selectors = $("input.WooZoneLight-items-select");

			if( that.is(':checked') == true){

				selectors.each(function(){
					var sub_that = $(this),
						tr_parent = sub_that.parents('tr').eq(0);
					sub_that.attr('checked', 'true');
					tr_parent.addClass('on');
				});
			}else{
				selectors.each(function(){
					var sub_that = $(this),
						tr_parent = sub_that.parents('tr').eq(0);
					sub_that.removeAttr('checked');
					tr_parent.removeClass('on');
				});
			}

			// update the execution queue
			updateExecutionQueue();
		})

		// temp
		.click();
		
		jQuery('body').on('change', 'input.WooZoneLight-items-select', function (e)
		{
			var that = $(this),
				tr_parent = that.parents('tr').eq(0);
			if( that.is(':checked') == false){
				tr_parent.removeClass('on');
			}else{
				tr_parent.addClass('on');
			}

			// update the execution queue
			updateExecutionQueue();
		});
		
		jQuery('body').on('click', '#WooZoneLight-advanced-search .WooZoneLight-items-list tr td:not(:last-child, :first-child)', function (e)
		{
			var that = $(this),
				tr_parent = that.parent('tr'),
				input = tr_parent.find('input');
			input.click();
		});
		
		jQuery('body').on('click', '#WooZoneLight-advanced-search a.removeFromQueue', function (e) 
		{
			e.preventDefault();

			var that = $(this),
				href = that.attr('href').replace("#", ''),
				tr_parent = $('tr#WooZoneLight-item-row-' + href),
				input = tr_parent.find('input');

			input.click();
		});
		
		jQuery('body').on('submit', '#WooZoneLight_import_panel', function (e) {
			e.preventDefault();

			launchSearch( $(this), true );
		});
		
		jQuery('body').on('change', 'select#WooZoneLight-page', function (e) {
			e.preventDefault();

			launchSearch( $("#WooZoneLight_import_panel"), false );
		});

		jQuery('body').on('click', 'a#WooZoneLight-advance-import-btn', function (e) {
			e.preventDefault();

			launchImport();
		});
		
		jQuery('body').on('change', '#WooZoneLightGetChildrens select', function (e) {
			e.preventDefault();

			getChildNodes( $(this) );
		});
	}

	// external usage
	return {
		"setDegubLevel": setDegubLevel,
        "ASINs": ASINs,
        "launchImport": launchImport
    }
})(jQuery);