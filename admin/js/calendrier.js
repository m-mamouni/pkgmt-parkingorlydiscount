// JavaScript Document
$(function() {
			function addTr($elem) {
				var $td = $('<td></td>').append($elem);
				return( $('<tr></tr>').append($td));
			}
			
			var codropsEvents = {};
			$.ajax({
        		type: "POST",
				dataType:"json",
				async: false,
        		url: '/?type=ajax&action=getCaldendar',
				success: function(data)
				{
					codropsEvents = data;
				}
			});
			var transEndEventNames = {
					'WebkitTransition' : 'webkitTransitionEnd',
					'MozTransition' : 'transitionend',
					'OTransition' : 'oTransitionEnd',
					'msTransition' : 'MSTransitionEnd',
					'transition' : 'transitionend'
				},
				transEndEventName = transEndEventNames[ Modernizr.prefixed( 'transition' ) ],
				$wrapper = $( '#custom-inner' );
			var $calendar = $( '#calendar' );
			var cal = $calendar.calendario( {
					caldata : codropsEvents,
					weeks : [ 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi' ],
					weekabbrs : [ 'Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam' ],
					months : [ 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Decembre' ],
					monthabbrs : [ 'Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec' ],
					displayWeekAbbr : true,
					onDayClick : function( $el, $content, dateProperties ) {
						var year = dateProperties.year;
						var month = dateProperties.month <10 ? '0' + dateProperties.month : dateProperties.month;
						var day = dateProperties.day < 10 ? '0' + dateProperties.day : dateProperties.day;
						var datePlanning = year+'-'+month+'-'+day;
						var $printDiv = $('<div></div>').appendTo($("body"));
						$printDiv.dialog({
								autoOpen: false,
								show: 'fade',
								hide: 'fade',
								width: 'auto',
								minWidth: '350',
								modal: false,
								id: 'dialog_print',
								title: 'Impression',
								buttons:
										[{  //cancel button
											text: 'Annuler',
											click: function () {
												$(this).dialog('close');
											}
										}, { //save button
											id: 'PrintDialogButton',
											text: 'Imprimer',
											click: function () {
												var url = '/?type=ajax&action=imprimerdate&date='+datePlanning;
												var cx = ''
												$('#table_imprimer').find( 'input[name^=cx]' ).each(function(){
													if (this.checked)
													  cx += '&cx[]='+$(this).val();
												});
												if (cx == ''){
													$.dW.alert("Sélectionner une fiche");
													return;
												}
												url += cx;
												$(this).dialog("close");
												window.open(url, 'imprimer', 'status=no,location=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=700,height=480,directories=no,', false);
										}}, {
											id: 'PrintPlanningDialogButton',
											text: 'Planning',
											click: function () {
												$(this).dialog("close");
												var url = '/?type=ajax&action=imprimer&date='+datePlanning;
												window.open(url, 'imprimer', 'status=no,location=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=700,height=480,directories=no',false);
												return;
											}
											}
										]});			
						var $printForm = $('<form></form>');
						var $printTable = $('<table></table>')
							.attr('id', 'table_imprimer')
							.addClass('imprimer');
						var $printTr = $('<tr></tr>');
						var $input = $('<input />')
								.attr('type','checkbox')
								.attr('id','cxall')
								.attr('name','all');
						$('<td></td>')
							.append($input.change( function() {
									
										var cxBoxes = $('#table_imprimer').find( 'input[name^=cx]' );
										var c = this.checked ? 'checked' : '';
										$('#table_imprimer').find( 'input[name^=cx]' ).each(function(){
											this.checked = c;
										});
									}))
							.append('Tous')
							.appendTo($printTr);
						$('<tbody></tbody>')
						.append($printTr)
						.append(addTr('<input type="checkbox" name="cx[]" value="bp">Bon de prise en charge'))
						.append(addTr('<input type="checkbox" name="cx[]" value="bs">Bon de sortie'))
						.append(addTr('<input type="checkbox" name="cx[]" value="fc">Facture'))
						.appendTo($printTable);
						$printForm.append($printTable);
						$printDiv.append($printForm).dialog('open');
						return false; 
					}
				} ),
				$month = $( '#custom-month' ).html( cal.getMonthName() ),
				$year = $( '#custom-year' ).html( cal.getYear() );

				$( '#custom-next' ).on( 'click', function() {
					cal.gotoNextMonth( updateMonthYear );
				} );
				$( '#custom-prev' ).on( 'click', function() {
					cal.gotoPreviousMonth( updateMonthYear );
				} );

				function updateMonthYear() {				
					$month.html( cal.getMonthName() );
					$year.html( cal.getYear() );
				}

			});