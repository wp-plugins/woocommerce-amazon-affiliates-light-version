jQuery(document).ready(function($) {

	var WooZoneLight_launch_search = function (data) {
		var searchAjaxLoader 	= jQuery("#WooZoneLight-ajax-loader"),
			searchBtn 			= jQuery("#WooZoneLight-search-link");
			
		searchBtn.hide();	
		searchAjaxLoader.show();
		
		var data = {
			action: 'amazon_request',
			search: jQuery('#WooZoneLight-search').val(),
			category: jQuery('#WooZoneLight-category').val(),
			page: ( parseInt(jQuery('#WooZoneLight-page').val(), 10) > 0 ? parseInt(jQuery('#WooZoneLight-page').val(), 10) : 1 )
		};
		
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#WooZoneLight-ajax-results").html(response);
			
			searchBtn.show();	
			searchAjaxLoader.hide();
		});
	};
	
	jQuery('body').on('change', '#WooZoneLight-page', function (e) {
		WooZoneLight_launch_search();
	});
	
	jQuery("#WooZoneLight-search-form").submit(function(e) {
		WooZoneLight_launch_search();
		return false;
	});
	
	jQuery('body').on('click', 'a.WooZoneLight-load-product', function (e) {
		e.preventDefault();
		
		var data = {
			'action': 'WooZoneLight_load_product',
			'ASIN':  jQuery(this).attr('rel')
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: data,
			success: function(response) {
				if(response.status == 'valid'){
					window.location = response.redirect_url;
					return true;
				}else{
					alert(response.msg);
					return false
				}
			}
		});
	});
});