<?php
/**
 * Dummy module return as json_encode
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		0.1 - in development mode
 */
/*
http://docs.aws.amazon.com/AWSECommerceService/latest/DG/CHAP_response_elements.html 
$('div.informaltable > table tr').each(function(i, el) {
    var $this = $(el), $td = $this.find('td:first'),
    $a = $td.find('a'), text = $a.attr('name');

    if ( typeof text == 'undefined' || text == '' ){
        text = $td.find('.code').text();
    }
    if ( typeof text != 'undefined' && text != '' ) {
		var text2 = text; //text.match(/([A-Z]?[^A-Z]*)/g).slice(0,-1).join(' ');
        console.log( '\''+text+'\' => \''+text+'\',' );
    }
});
*/  
function __WooZoneLight_attributesList() {
	$attrList = array(
		'About' => 'About',
		'AboutMe' => 'AboutMe',
		'Actor' => 'Actor',
		'AdditionalName' => 'AdditionalName',
		'AlternateVersion' => 'AlternateVersion',
		'Amount' => 'Amount',
		'Artist' => 'Artist',
		'ASIN' => 'ASIN',
		'AspectRatio' => 'AspectRatio',
		'AudienceRating' => 'AudienceRating',
		'AudioFormat' => 'AudioFormat',
		'Author' => 'Author',
		'Availability' => 'Availability',
		'AvailabilityAttributes' => 'AvailabilityAttributes',
		'Benefit' => 'Benefit',
		'Benefits' => 'Benefits',
		'BenefitType' => 'BenefitType',
		'BenefitDescription' => 'BenefitDescription',
		'Bin' => 'Bin',
		'Binding' => 'Binding',
		'BinItemCount' => 'BinItemCount',
		'BinName' => 'BinName',
		'BinParameter' => 'BinParameter',
		'Brand' => 'Brand',
		'BrowseNodeId' => 'BrowseNodeId',
		'CartId' => 'CartId',
		'CartItem' => 'CartItem',
		'CartItemId' => 'CartItemId',
		'CartItems' => 'CartItems',
		'Category' => 'Category',
		'CEROAgeRating' => 'CEROAgeRating',
		'ClothingSize' => 'ClothingSize',
		'Code' => 'Code',
		'Collection' => 'Collection',
		'CollectionItem' => 'CollectionItem',
		'CollectionParent' => 'CollectionParent',
		'Color' => 'Color',
		'Comment' => 'Comment',
		'ComponentType' => 'ComponentType',
		'Condition' => 'Condition',
		'CorrectedQuery' => 'CorrectedQuery',
		'CouponCombinationType' => 'CouponCombinationType',
		'Creator' => 'Creator',
		'CurrencyAmount' => 'CurrencyAmount',
		'CurrencyCode' => 'CurrencyCode',
		'Date' => 'Date',
		'DateAdded' => 'DateAdded',
		'DateCreated' => 'DateCreated',
		'Department' => 'Department',
		'Details' => 'Details',
		'Director' => 'Director',
		'EAN' => 'EAN',
		'EANList' => 'EANList',
		'EANListElement' => 'EANListElement',
		'Edition' => 'Edition',
		'EditorialReviewIsLinkSuppressed' => 'EditorialReviewIsLinkSuppressed',
		'EISBN' => 'EISBN',
		'EligibilityRequirement' => 'EligibilityRequirement',
		'EligibilityRequirementDescription' => 'EligibilityRequirementDescription',
		'EligibilityRequirements' => 'EligibilityRequirements',
		'EligibilityRequirementType' => 'EligibilityRequirementType',
		'EndDate' => 'EndDate',
		'EpisodeSequence' => 'EpisodeSequence',
		'ESRBAgeRating' => 'ESRBAgeRating',
		'Feature' => 'Feature',
		'Feedback' => 'Feedback',
		'Fitment' => 'Fitment',
		'FitmentAttribute' => 'FitmentAttribute',
		'FitmentAttributes' => 'FitmentAttributes',
		'FixedAmount' => 'FixedAmount',
		'Format' => 'Format',
		'FormattedPrice' => 'FormattedPrice',
		'Genre' => 'Genre',
		'GroupClaimCode' => 'GroupClaimCode',
		'HardwarePlatform' => 'HardwarePlatform',
		'HazardousMaterialType' => 'HazardousMaterialType',
		'Height' => 'Height',
		'HelpfulVotes' => 'HelpfulVotes',
		'HMAC' => 'HMAC',
		'IFrameURL' => 'IFrameURL',
		'Image' => 'Image',
		'IsAdultProduct' => 'IsAdultProduct',
		'IsAutographed' => 'IsAutographed',
		'ISBN' => 'ISBN',
		'IsCategoryRoot' => 'IsCategoryRoot',
		'IsEligibleForSuperSaverShipping' => 'IsEligibleForSuperSaverShipping',
		'IsEligibleForTradeIn' => 'IsEligibleForTradeIn',
		'IsEmailNotifyAvailable' => 'IsEmailNotifyAvailable',
		'IsFit' => 'IsFit',
		'IsInBenefitSet' => 'IsInBenefitSet',
		'IsInEligibilityRequirementSet' => 'IsInEligibilityRequirementSet',
		'IsLinkSuppressed' => 'IsLinkSuppressed',
		'IsMemorabilia' => 'IsMemorabilia',
		'IsNext' => 'IsNext',
		'IsPrevious' => 'IsPrevious',
		'ItemApplicability' => 'ItemApplicability',
		'ItemDimensions' => 'ItemDimensions',
		'IssuesPerYear' => 'IssuesPerYear',
		'IsValid' => 'IsValid',
		'ItemAttributes' => 'ItemAttributes',
		'ItemPartNumber' => 'ItemPartNumber',
		'Keywords' => 'Keywords',
		'Label' => 'Label',
		'Language' => 'Language',
		'Languages' => 'Languages',
		'LargeImage' => 'LargeImage',
		'LastModified' => 'LastModified',
		'LegalDisclaimer' => 'LegalDisclaimer',
		'Length' => 'Length',
		'ListItemId' => 'ListItemId',
		'ListPrice' => 'ListPrice',
		'LoyaltyPoints' => 'LoyaltyPoints',
		'Manufacturer' => 'Manufacturer',
		'ManufacturerMaximumAge' => 'ManufacturerMaximumAge',
		'ManufacturerMinimumAge' => 'ManufacturerMinimumAge',
		'ManufacturerPartsWarrantyDescription' => 'ManufacturerPartsWarrantyDescription',
		'MaterialType' => 'MaterialType',
		'MaximumHours' => 'MaximumHours',
		'MediaType' => 'MediaType',
		'MediumImage' => 'MediumImage',
		'MerchandisingMessage' => 'MerchandisingMessage',
		'MerchantId' => 'MerchantId',
		'Message' => 'Message',
		'MetalType' => 'MetalType',
		'MinimumHours' => 'MinimumHours',
		'Model' => 'Model',
		'MoreOffersUrl' => 'MoreOffersUrl',
		'MPN' => 'MPN',
		'Name' => 'Name',
		'Nickname' => 'Nickname',
		'Number' => 'Number',
		'NumberOfDiscs' => 'NumberOfDiscs',
		'NumberOfIssues' => 'NumberOfIssues',
		'NumberOfItems' => 'NumberOfItems',
		'NumberOfPages' => 'NumberOfPages',
		'NumberOfTracks' => 'NumberOfTracks',
		'OccasionDate' => 'OccasionDate',
		'OfferListingId' => 'OfferListingId',
		'OperatingSystem' => 'OperatingSystem',
		'OtherCategoriesSimilarProducts' => 'OtherCategoriesSimilarProducts',
		'PackageQuantity' => 'PackageQuantity',
		'ParentASIN' => 'ParentASIN',
		'PartBrandBins' => 'PartBrandBins',
		'PartBrowseNodeBins' => 'PartBrowseNodeBins',
		'PartNumber' => 'PartNumber',
		'PartnerName' => 'PartnerName',
		'Platform' => 'Platform',
		'Price' => 'Price',
		'ProductGroup' => 'ProductGroup',
		'ProductTypeSubcategory' => 'ProductTypeSubcategory',
		'Promotion' => 'Promotion',
		'PromotionId' => 'PromotionId',
		'Promotions' => 'Promotions',
		'PublicationDate' => 'PublicationDate',
		'Publisher' => 'Publisher',
		'PurchaseURL' => 'PurchaseURL',
		'Quantity' => 'Quantity',
		'Rating' => 'Rating',
		'RegionCode' => 'RegionCode',
		'RegistryName' => 'RegistryName',
		'RelatedItem' => 'RelatedItem',
		'RelatedItems' => 'RelatedItems',
		'RelatedItemsCount' => 'RelatedItemsCount',
		'RelatedItemPage' => 'RelatedItemPage',
		'RelatedItemPageCount' => 'RelatedItemPageCount',
		'Relationship' => 'Relationship',
		'RelationshipType ' => 'RelationshipType ',
		'ReleaseDate' => 'ReleaseDate',
		'RequestId' => 'RequestId',
		'Role' => 'Role',
		'RunningTime' => 'RunningTime',
		'SalesRank' => 'SalesRank',
		'SavedForLaterItem' => 'SavedForLaterItem',
		'SearchBinSet' => 'SearchBinSet',
		'SearchBinSets' => 'SearchBinSets',
		'SeikodoProductCode' => 'SeikodoProductCode',
		'ShipmentItems' => 'ShipmentItems',
		'Shipments' => 'Shipments',
		'SimilarProducts' => 'SimilarProducts',
		'SimilarViewedProducts' => 'SimilarViewedProducts',
		'Size' => 'Size',
		'SKU' => 'SKU',
		'SmallImage' => 'SmallImage',
		'Source' => 'Source',
		'StartDate' => 'StartDate',
		'StoreId' => 'StoreId',
		'StoreName' => 'StoreName',
		'Studio' => 'Studio',
		'SubscriptionLength' => 'SubscriptionLength',
		'Summary' => 'Summary',
		'SwatchImage' => 'SwatchImage',
		'TermsAndConditions' => 'TermsAndConditions',
		'ThumbnailImage' => 'ThumbnailImage',
		'TinyImage' => 'TinyImage',
		'Title' => 'Title',
		'TopItem' => 'TopItem',
		'TopItemSet' => 'TopItemSet',
		'TotalCollectible' => 'TotalCollectible',
		'TotalItems' => 'TotalItems',
		'TotalNew' => 'TotalNew',
		'TotalOfferPages' => 'TotalOfferPages',
		'TotalOffers' => 'TotalOffers',
		'TotalPages' => 'TotalPages',
		'TotalRatings' => 'TotalRatings',
		'TotalRefurbished' => 'TotalRefurbished',
		'TotalResults' => 'TotalResults',
		'TotalReviewPages' => 'TotalReviewPages',
		'TotalReviews' => 'TotalReviews',
		'Totals' => 'Totals',
		'TotalTimesRead' => 'TotalTimesRead',
		'TotalUsed' => 'TotalUsed',
		'TotalVotes' => 'TotalVotes',
		'Track' => 'Track',
		'TradeInValue' => 'TradeInValue',
		'TransactionDate' => 'TransactionDate',
		'TransactionDateEpoch' => 'TransactionDateEpoch',
		'TransactionId' => 'TransactionId',
		'TransactionItem' => 'TransactionItem',
		'TransactionItemId' => 'TransactionItemId',
		'TransactionItems' => 'TransactionItems',
		'Type' => 'Type',
		'UPC' => 'UPC',
		'UPCList' => 'UPCList',
		'UPCListElement' => 'UPCListElement',
		'URL' => 'URL',
		'URLEncodedHMAC' => 'URLEncodedHMAC',
		'UserAgent' => 'UserAgent',
		'UserId' => 'UserId',
		'VariationAttribute' => 'VariationAttribute',
		'VariationDimension' => 'VariationDimension',
		'Warranty' => 'Warranty',
		'WEEETaxValue' => 'WEEETaxValue',
		'Weight' => 'Weight',
		'Width' => 'Width',
		'Year' => 'Year'
	);
	return $attrList;
}

function __WooZoneLight_attributes_clean_duplicate( $istab = '' ) {
	global $WooZoneLight;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLight-form-row attr-clean-duplicate' . ($istab!='' ? ' '.$istab : '') . '">';

	$html[] = '<label style="display:inline;float:none;" for="clean_duplicate_attributes">' . __('Clean duplicate attributes:', $WooZoneLight->localizationName) . '</label>';

	$options = $WooZoneLight->getAllSettings('array', 'amazon');
	$val = '';
	if ( isset($options['clean_duplicate_attributes']) ) {
		$val = $options['clean_duplicate_attributes'];
	}
		
	ob_start();
?>
		<select id="clean_duplicate_attributes" name="clean_duplicate_attributes" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="WooZoneLight-button blue" style="width: 160px;" id="WooZoneLight-attributescleanduplicate" value="' . ( __('clean Now ', $WooZoneLight->localizationName) ) . '">
	<span style="margin:0px; margin-left: 10px; display: block;" class="response"></span>';

	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>'

		$("body").on("click", "#WooZoneLight-attributescleanduplicate", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLight_AttributesCleanDuplicate',
				'sub_action'	: 'attr_clean_duplicate'
			}, function(response) {

				var $box = $('.attr-clean-duplicate'), $res = $box.find('.response');
				$res.html( response.msg_html );
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
   	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function __WooZoneLight_category_slug_clean_duplicate( $istab = '' ) {
	global $WooZoneLight;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLight-form-row category-slug-clean-duplicate' . ($istab!='' ? ' '.$istab : '') . '">';

	$html[] = '<label style="display:inline;float:none;" for="clean_duplicate_category_slug">' . __('Clean duplicate category slug:', $WooZoneLight->localizationName) . '</label>';

	$options = $WooZoneLight->getAllSettings('array', 'amazon');
	$val = '';
	if ( isset($options['clean_duplicate_category_slug']) ) {
		$val = $options['clean_duplicate_category_slug'];
	}
		
	ob_start();
?>
		<select id="clean_duplicate_category_slug" name="clean_duplicate_category_slug" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="WooZoneLight-button blue" style="width: 160px;" id="WooZoneLight-categoryslugcleanduplicate" value="' . ( __('clean Now ', $WooZoneLight->localizationName) ) . '">
	<span style="margin:0px; margin-left: 10px; display: block;" class="response"></span>';

	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>'

		$("body").on("click", "#WooZoneLight-categoryslugcleanduplicate", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLight_CategorySlugCleanDuplicate',
				'sub_action'	: 'category_slug_clean_duplicate'
			}, function(response) {

				var $box = $('.category-slug-clean-duplicate'), $res = $box.find('.response');
				$res.html( response.msg_html );
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
   	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function __WooZoneLightAffIDsHTML( $istab = '' )
{
    global $WooZoneLight;
    
    $html         = array();
    $img_base_url = $WooZoneLight->cfg['paths']["plugin_dir_url"] . 'modules/amazon/assets/flags/';
    
    $config = @unserialize(get_option($WooZoneLight->alias . '_amazon'));
    
    $html[] = '<div class="WooZoneLight-form-row' . ($istab!='' ? ' '.$istab : '') . '">';
    $html[] = '<label>Your Affiliate IDs</label>';
    $html[] = '<div class="WooZoneLight-form-item large">';
    $html[] = '<span class="formNote">Your Affiliate ID probably ends in -20, -21 or -22. You get this ID by signing up for Amazon Associates.</span>';
    $html[] = '<div class="WooZoneLight-aff-ids">';
    $html[] = '<img src="' . ($img_base_url) . 'US-flag.gif" height="20"><input type="text" value="' . ($config['AffiliateID']['com']) . '" name="AffiliateID[com]" id="AffiliateID[com]"> <br />';
    $html[] = '<img src="' . ($img_base_url) . 'CA-flag.gif" height="20"><input type="text" value="' . ($config['AffiliateID']['ca']) . '" name="AffiliateID[ca]" id="AffiliateID[ca]"> <br />';
    $html[] = '<img src="' . ($img_base_url) . 'UK-flag.gif" height="20"><input type="text" value="' . ($config['AffiliateID']['uk']) . '" name="AffiliateID[uk]" id="AffiliateID[uk]"> <br />';
    $html[] = '<img src="' . ($img_base_url) . 'DE-flag.gif" height="20"><input type="text" value="' . ($config['AffiliateID']['de']) . '" name="AffiliateID[de]" id="AffiliateID[de]"> <br />';
    $html[] = '<img src="' . ($img_base_url) . 'FR-flag.gif" height="20"><input type="text" value="' . ($config['AffiliateID']['fr']) . '" name="AffiliateID[fr]" id="AffiliateID[fr]"> <br />';
    $html[] = '<img src="' . ($img_base_url) . 'IN-flag.gif" height="20"><input type="text" value="' . ($config['AffiliateID']['in']) . '" name="AffiliateID[in]" id="AffiliateID[in]"> <br />';
    $html[] = '<img src="' . ($img_base_url) . 'IT-flag.gif" height="20"><input type="text" value="' . ($config['AffiliateID']['it']) . '" name="AffiliateID[it]" id="AffiliateID[it]"> <br />';
    $html[] = '<img src="' . ($img_base_url) . 'ES-flag.gif" height="20"><input type="text" value="' . ($config['AffiliateID']['es']) . '" name="AffiliateID[es]" id="AffiliateID[es]"> <br />';
    $html[] = '<img src="' . ($img_base_url) . 'JP-flag.gif" height="20"><input type="text" value="' . ($config['AffiliateID']['jp']) . '" name="AffiliateID[jp]" id="AffiliateID[jp]"> <br />';
    $html[] = '<img src="' . ($img_base_url) . 'CN-flag.gif" height="20"><input type="text" value="' . ($config['AffiliateID']['cn']) . '" name="AffiliateID[cn]" id="AffiliateID[cn]"> <br />';
    $html[] = '</div>';
    $html[] = '<h3>Some hints and information:</h3>';
    $html[] = '- The link will use IP-based Geolocation to geographically target your visitor to the Amazon store of his/her country (according to their current location). <br />';
    $html[] = '- You don\'t have to specify all affiliate IDs if you are not registered to all programs. <br />';
    $html[] = '- The ASIN is unfortunately not always globally unique. That\'s why you sometimes need to specify several ASINs for different shops. <br />';
    $html[] = '- If you have an English website, it makes most sense to sign up for the US, UK and Canadian programs. <br />';
    $html[] = '</div>';
    $html[] = '</div>';
    
    return implode("\n", $html);
}

global $WooZoneLight;
echo json_encode(array(
    $tryed_module['db_alias'] => array(
        
        /* define the form_sizes  box */
        'amazon' => array(
            'title' => 'Amazon settings',
            'icon' => '{plugin_folder_uri}assets/amazon.png',
            'size' => 'grid_4', // grid_1|grid_2|grid_3|grid_4
            'header' => true, // true|false
            'toggler' => false, // true|false
            'buttons' => true, // true|false
            'style' => 'panel', // panel|panel-widget
            
				// tabs
				'tabs'	=> array(
					'__tab1'	=> array(__('Amazon SETUP', $WooZoneLight->localizationName), 'protocol, country, AccessKeyID, SecretAccessKey, AffiliateId, main_aff_id, buttons, help_required_fields, help_available_countries'),
					'__tab2'	=> array(__('Plugin SETUP', $WooZoneLight->localizationName), 'onsite_cart, cross_selling, checkout_type, 90day_cookie, remove_gallery, redirect_time, show_review_tab, redirect_checkout_msg, product_buy_is_amazon_url, frontend_show_free_shipping, frontend_show_coupon_text, charset, services_used_forip'),
					'__tab3'	=> array(__('Import SETUP', $WooZoneLight->localizationName), 'price_setup, product_variation, import_price_zero_products, default_import, import_type, ratio_prod_validate, item_attribute, selected_attributes, attr_title_normalize, cron_number_of_images, number_of_images, rename_image, spin_at_import, spin_max_replacements'),
				),
            
            // create the box elements array
            'elements' => array(
                'protocol' => array(
                	'premium' => true,
                    'type' => 'select',
                    'std' => '',
                    'size' => 'large',
                    'force_width' => '200',
                    'title' => 'Request Type',
                    'desc' => 'How the script should make the request to Amazon API.',
                    'options' => array(
                        'auto' => 'Auto Detect',
                        'soap' => 'SOAP',
                        'xml' => 'XML (over cURL, streams, fsockopen)'
                    )
                ),
                'country' => array(
                    'type' => 'select',
                    'std' => '',
                    'size' => 'large',
                    'force_width' => '150',
                    'title' => 'Amazon locations',
                    'desc' => 'All possible locations.',
                    'options' => array(
                        'com' => 'Worldwide',
                        'de' => 'Germany',
                        'co.uk' => 'United Kingdom',
                        'ca' => 'Canada',
                        'fr' => 'France',
                        'co.jp' => 'Japan',
                        'in' => 'India',
                        'it' => 'Italy',
                        'cn' => 'China',
                        'es' => 'Spain'
                    )
                ),
                
				'services_used_forip' => array(
                    'type' => 'select',
                    'std' => 'www.geoplugin.net',
                    'size' => 'large',
                    'force_width' => '200',
                    'title' => 'External server country detection:',
                    'desc' => 'We use an external server for detecting client country per IP address.',
                    'options' => array(
                        'api.hostip.info' 			=> 'api.hostip.info',
                        'www.geoplugin.net' 		=> 'www.geoplugin.net',
                        'www.telize.com'			=> 'www.telize.com',
                        'ipinfo.io' 				=> 'ipinfo.io',
                    )
                ),
                
				'charset' 	=> array(
					'type' 		=> 'text',
					'std' 		=> 'UTF-8',
					'size' 		=> 'large',
					'force_width'=> '400',
					'title' 	=> __('Server Charset:', $WooZoneLight->localizationName),
					'desc' 		=> __('Server Charset (used by php-query class)', $WooZoneLight->localizationName)
				),

                'product_buy_is_amazon_url' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => 'yes',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Show Amazon Url as Buy Url',
                    'desc' => 'If you choose YES then the product buy url will be the original amazon product url.',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                'frontend_show_free_shipping' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => 'yes',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Show Free Shipping',
                    'desc' => 'Show Free Shipping text on frontend.',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                'frontend_show_coupon_text' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => 'yes',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Show Coupon',
                    'desc' => 'Show Coupon text on frontend.',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),

                'onsite_cart' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => 'false',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'On-site Cart',
                    'desc' => 'This option will allow your customers to add multiple Amazon Products into Cart and checkout trought Amazon\'s system with all at once.',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                
				'checkout_type' => array(
                    'type' => 'select',
                    'std' => '_self',
                    'size' => 'large',
                    'force_width' => '200',
                    'title' => 'Checkout type:',
                    'desc' => 'This option will allow you to setup how the Amazon Checkout process will happen. If you wish to open the amazon products into a new tab, or in the same tab.',
                    'options' => array(
                        'self' => 'Self - into same tab',
                        '_blank' => 'Blank - open new tab'
                    )
                ),
                
                
				'item_attribute' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => 'yes',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Import Attributes',
                    'desc' => 'This option will allow to import or not the product item attributes.',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                
				'selected_attributes' 	=> array(
					'premium' => true,
					'type' 		=> 'multiselect_left2right',
					'std' 		=> array(),
					'size' 		=> 'large',
					'rows_visible'	=> 18,
					'force_width'=> '300',
					'title' 	=> __('Select attributes', $WooZoneLight->localizationName),
					'desc' 		=> __('Choose what attributes to be added on import process.', $WooZoneLight->localizationName),
					'info'		=> array(
						'left' => 'All Amazon Attributes list',
						'right' => 'Your chosen items from list'
					),
					'options' 	=> __WooZoneLight_attributesList()
				),
                
                /*'clean_duplicate_attributes' => array(
                    'type' => 'select',
                    'std' => 'yes',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Clean duplicate attributes',
                    'desc' => 'Clean duplicate attributes.',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),*/
                

				'attr_title_normalize' => array(
					'premium' => true,
                    'type' => 'select',
                    'std' => 'no',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Beautify attribute title',
                    'desc' => 'separate attribute title words by space',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                
                '90day_cookie' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => 'yes',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => '90 days cookies',
                    'desc' => 'This option will activate the 90 days cookies feature',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                
                'price_setup' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => 'only_amazon',
                    'size' => 'large',
                    'force_width' => '290',
                    'title' => 'Prices setup',
                    'desc' => 'Get product offer price from Amazon or other Amazon sellers.',
                    'options' => array(
                        'only_amazon' => 'Only Amazon',
                        'amazon_or_sellers' => 'Amazon OR other sellers (get lowest price)'
                    )
                ),
                
				
                'product_variation' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => 'no',
                    'size' => 'large',
                    'force_width' => '160',
                    'title' => 'Variation',
                    'desc' => 'Get product variations. Be carefull about <code>Yes All variations</code> one product can have a lot of variation, execution time is dramatically increased!',
                    'options' => array(
                        'no' => 'NO',
                        'yes_1' => 'Yes 1 variation',
                        'yes_2' => 'Yes 2 variations',
                        'yes_3' => 'Yes 3 variations',
                        'yes_4' => 'Yes 4 variations',
                        'yes_5' => 'Yes 5 variations',
                        'yes_all' => 'Yes All variations'
                    )
                ),
                
                'import_price_zero_products' => array(
                    'type' => 'select',
                    'std' => 'no',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Import products with price 0',
                    'desc' => 'Choose Yes if you want to import products with price 0',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                
                'default_import' => array(
                    'type' => 'select',
                    'std' => 'publish',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Import as',
                    'desc' => 'Default import products with status "publish" or "draft"',
                    'options' => array(
                        'publish' => 'Publish',
                        'draft' => 'Draft'
                    )
                ),
                'import_type' => array(
                    'type' => 'select',
                    'std' => 'default',
                    'size' => 'large',
                    'force_width' => '280',
                    'title' => 'Image Import type',
                    'options' => array(
                        'default' => 'Default - download images at import',
                        'asynchronous' => 'Asynchronous image download'
                    )
                ),
				'ratio_prod_validate' 	=> array(
					'type' 		=> 'select',
					'std'		=> 90,
					'size' 		=> 'large',
					'title' 	=> __('Ratio product validation:', $WooZoneLight->localizationName),
					'force_width'=> '100',
					'desc' 		=> __('The minimum percentage of total assets download (product + variations) from which a product is considered valid!', $WooZoneLight->localizationName),
					'options'	=> $WooZoneLight->doRange( range(10, 100, 5) )
				),
                'cron_number_of_images' => array(
                    'type' => 'text',
                    'std' => '10',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Cron number of images',
                    'desc' => 'The number of images your cronjob file will download at each execution.'
                ),
                'number_of_images' => array(
                    'type' => 'text',
                    'std' => 'all',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Number of images',
                    'desc' => 'How many images to download for each products. Default is <code>all</code>'
                ),
                'rename_image' => array(
                    'type' => 'select',
                    'std' => 'product_title',
                    'size' => 'large',
                    'force_width' => '130',
                    'title' => 'Image names',
                    'options' => array(
                        'product_title' => 'Product title',
                        'random' => 'Random number'
                    )
                ),
                'cross_selling' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => 'yes',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Cross-selling',
                    'desc' => 'Show Frequently Bought Together box.',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                'remove_gallery' => array(
                    'type' => 'select',
                    'std' => 'no',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Gallery',
                    'desc' => 'Show gallery in product description.',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                'show_review_tab' => array(
                    'type' => 'select',
                    'std' => 'yes',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Review tab',
                    'desc' => 'Show Amazon reviews tab in product description.',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                'redirect_checkout_msg' => array(
                    'type' => 'textarea',
                    'std' => 'You will be redirected to {amazon_website} to complete your checkout!',
                    'size' => 'large',
                    'force_width' => '160',
                    'title' => 'Checkout message',
                    'desc' => 'Message for checkout redirect box.'
                ),
                'redirect_time' => array(
                    'type' => 'text',
                    'std' => '3',
                    'size' => 'large',
                    'force_width' => '120',
                    'title' => 'Redirect in',
                    'desc' => 'How many seconds to wait before redirect to Amazon!'
                ),
                'help_required_fields' => array(
                    'type' => 'message',
                    'status' => 'info',
                    'html' => 'The following fields are required in order to send requests to Amazon and retrieve data about products and listings. If you do not already have access keys set up, please visit the <a href="https://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&amp;action=access-key#access_credentials" target="_blank">AWS Account Management</a> page to create and retrieve them.'
                ),
                'AccessKeyID' => array(
                    'type' => 'text',
                    'std' => '',
                    'size' => 'large',
                    'title' => 'Access Key ID',
                    'force_width' => '250',
                    'desc' => 'Are required in order to send requests to Amazon API.'
                ),
                'SecretAccessKey' => array(
                    'type' => 'text',
                    'std' => '',
                    'size' => 'large',
                    'force_width' => '300',
                    'title' => 'Secret Access Key',
                    'desc' => 'Are required in order to send requests to Amazon API.'
                ),
                'AffiliateId' => array(
                    'type' => 'html',
                    'std' => '',
                    'size' => 'large',
                    'title' => 'Affiliate Information',
                    'html' => __WooZoneLightAffIDsHTML( '__tab1' )
                ),
                'main_aff_id' => array(
                    'type' => 'select',
                    'std' => '',
                    'force_width' => '150',
                    'size' => 'large',
                    'title' => 'Main Affiliate ID',
                    'desc' => 'This Affiliate id will be use in API request and if user are not from any of available amazon country.',
                    'options' => array(
						'com' => 'United States',
						'uk' => 'United Kingdom',
						'de' => 'Deutschland',
						'fr' => 'France',
						'jp' => 'Japan',
						'ca' => 'Canada',
						'cn' => 'China',
						'in' => 'India',
						'it' => 'Italia',
						'es' => 'España',
					)
                ),
                
                'spin_at_import' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => 'no',
                    'size' => 'large',
                    'force_width' => '100',
                    'title' => 'Spin on Import',
                    'desc' => 'Choose YES if you want to auto spin post, page content at amazon import',
                    'options' => array(
                        'yes' => 'YES',
                        'no' => 'NO'
                    )
                ),
                'spin_max_replacements' => array(
                    'type' => 'select',
                    'premium' => true,
                    'std' => '10',
                    'force_width' => '150',
                    'size' => 'large',
                    'title' => 'Spin max replacements',
                    'desc' => 'Choose the maximum number of replacements for auto spin post, page content at amazon import.',
                    'options' => array(
						'10' 		=> '10 replacements',
						'30' 		=> '30 replacements',
						'60' 		=> '60 replacements',
						'80' 		=> '80 replacements',
						'100' 		=> '100 replacements',
						'0' 		=> 'All possible replacements',
					)
                ),
                
				'buttons' => array(
					'type' => 'buttons',
					'options' => array(
						'check_amz' => array(
							'width' => '162px',
							'type' => 'button',
							'value' => 'Check Amazon AWS Keys',
							'color' => 'blue',
							'action' => 'WooZoneLightCheckAmzKeys'
						)
					)
				),
                'help_available_countries' => array(
                    'type' => 'message',
                    'status' => 'info',
                    'html' => '
							<strong>Available countries: &nbsp;</strong>
							<a href="https://affiliate-program.amazon.com/" target="_blank">United States</a>, &nbsp;
							<a href="https://affiliate-program.amazon.co.uk/" target="_blank">United Kingdom</a>, &nbsp;
							<a href="https://partnernet.amazon.de/" target="_blank">Deutschland</a>, &nbsp;
							<a href="https://partenaires.amazon.fr/" target="_blank">France</a>, &nbsp;
							<a href="https://affiliate.amazon.co.jp/" target="_blank">Japan</a>, &nbsp;
							<a href="https://associates.amazon.ca/" target="_blank">Canada</a>, &nbsp;
							<a href="https://associates.amazon.cn/" target="_blank">China</a>, &nbsp;
							<a href="https://programma-affiliazione.amazon.it/" target="_blank">Italia</a>, &nbsp;
							<a href="https://afiliados.amazon.es/" target="_blank">España</a>, &nbsp;
							<a href="https://affiliate-program.amazon.in/" target="_blank">India</a>
						'
                )
            )
        )
    )
));