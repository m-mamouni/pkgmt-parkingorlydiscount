(function($) {

	if (typeof _pkmgmt == 'undefined' || _pkmgmt === null)
		_pkmgmt = {};

	postboxes.add_postbox_toggles(_pkmgmt.screenId);
	var page = _pkmgmt.screenId;


	$('#pkmgmt-info-gestion_autovalid')
	.change(function(){
	var d = $(this).val();
	if (d==1) d=0; else d=1;
	$(this).val(d);
		});
	$('#pkmgmt-info-gestion_mail')
	.change(function(){
	var d = $(this).val();
	if (d==1) d=0; else d=1;
	$(this).val(d);
		});
	$('#pkmgmt-info-gestion_base')
	.change(function(){
	var d = $(this).val();
	if (d==1) d=0; else d=1;
	$(this).val(d);
		});
	$('#pkmgmt-info-type_ext')
	.change(function(){
	var d = $(this).val();
	if (d==1) d=0; else d=1;
	$(this).val(d);
		});
	$('#pkmgmt-info-type_int')
	.change(function(){
	var d = $(this).val();
	if (d==1) d=0; else d=1;
	$(this).val(d);
		});

	$('#pkmgmt-info-paiement_cb')
		.change(function(){
		var d = $(this).val();
		if (d==1) d=0; else d=1;
		$(this).val(d);
			});
	$('#pkmgmt-info-paiement_espece')
		.change(function(){
		var d = $(this).val();
		if (d==1) d=0; else d=1;
		$(this).val(d);
			});
	$('#pkmgmt-info-paiement_cheque')
		.change(function(){
		var d = $(this).val();
		if (d==1) d=0; else d=1;
		$(this).val(d);
			});

	$('#pkmgmt-info-frais_ferie')
		.change(function(){
		var d = $(this).val();
		if (d==1) d=0; else d=1;
		$(this).val(d);
			});
	$('#pkmgmt-info-frais_nuit')
		.change(function(){
		var d = $(this).val();
		if (d==1) d=0; else d=1;
		$(this).val(d);
			});
	$('#pkmgmt-info-frais_dimanche')
		.change(function(){
		var d = $(this).val();
		if (d==1) d=0; else d=1;
		$(this).val(d);
			});
	$('#pkmgmt-info-cg')
		.change(function(){
		var d = $(this).val();
		if (d==1) d=0; else d=1;
		$(this).val(d);
			});
	$('#pkmgmt-paypal-actif')
    .change(function(){
    var d = $(this).val();
    if (d==1) d=0; else d=1;
    $(this).val(d);
        });
    $('#pkmgmt-paypal-commun')
    .change(function(){
    var d = $(this).val();
    if (d==1) d=0; else d=1;
    $(this).val(d);
        });
	$('#pkmgmt-info-logo_button')
		.click(function() {
 		formfield = $('#pkmgmt-info-logo').attr('name');

		tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
 		return false;
	});

	$('input#pkmgmt-title:disabled').css({cursor: 'default'});

			$('input#pkmgmt-title').mouseover(function() {
				$(this).not('.focus').addClass('mouseover');
			});

			$('input#pkmgmt-title').mouseout(function() {
				$(this).removeClass('mouseover');
			});

			$('input#pkmgmt-title').focus(function() {
				$(this).addClass('focus').removeClass('mouseover');
			});

			$('input#pkmgmt-title').blur(function() {
				$(this).removeClass('focus');
			});

			$('input#pkmgmt-title').change(function() {
				updateTag();
			});

			updateTag();

	window.send_to_editor = function(html) {
	 imgurl = $('img',html).attr('src');
	 imgpath = imgurl.replace(_pkmgmt.siteurl,_pkmgmt.base);
	 $('#pkmgmt-info-logo').val(imgpath);
	 tb_remove();
	}

	function updateTag() {
		var title = $('input#pkmgmt-title').val();
		if (title)
			title = title.replace(/["'\[\]]/g, '');

		$('input#pkmgmt-title').val(title);
		var postId = $('input#post_ID').val();
		var tag = '[parking-management id="' + postId + '" title="' + title + '"]';
		$('input#pkmgmt-anchor-text').val(tag);

	}


})(jQuery);
