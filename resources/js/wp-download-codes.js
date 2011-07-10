/**
 * WP Download Codes Plugin
 * 
 * FILE
 * resources/wp-download-codes.js
 *
 * DESCRIPTION
 * Contains JS functions for plugin
 *
 */

var $ = jQuery.noConflict();

$(document).ready(function() {

	// add lightbox DOM elements
	$("body").append('<div id="lightbox"><div class="close"></div><div id="lightbox-content" class="content"></div><textarea id="lightbox-textarea"></textarea></div>'); 
	$("body").append('<div id="overlay" class="overlay"></div>');	

	// open lightbox to list download codes
	$("a.action-list").click(function() {
		var lightbox = $(this).attr("rel");
		return openLightbox(lightbox, true);	
	});
	
	// open lightbox to list downloads
	$("a.action-report").click(function() {
		var lightbox = $(this).attr("rel");
		return openLightbox(lightbox);	
	});
	
	// add confirm step before deleting release
	$("a.action-delete").click(function() {
		return confirm("Are you absolutely sure? This cannot be undone!");
	});
	
	// add confirm step before finalizing codes
	$("a.action-finalize").click(function() {
		return confirm("Are you absolutely sure? Codes cannot be deleted after they're finalized.");
	});
	
	// close button on lightbox
	$("#lightbox .close").click(closeLightbox);
});

/***********************
// open lightbox
*/
function openLightbox($lightbox, $selectable) {
	var selectable = $selectable ? true : false;
	var textarea;

	$("#overlay").show().live("click", closeLightbox);	
	$("#lightbox").show();
	if(selectable) {
		$("#lightbox-textarea").show().html($("#" + $lightbox).text());
		$("#lightbox-content").hide();
	} else {
		$("#lightbox-content").show().html($("#" + $lightbox).html());
		$("#lightbox-textarea").hide();
	}
	return false;
}

/***********************
// close lightbox
*/
function closeLightbox() {
	$("#overlay").hide();
	$("#lightbox").hide();	
	return false;
}