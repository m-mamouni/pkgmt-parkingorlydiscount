// JavaScript Document
jQuery(document).ready(function($) {
	function getCalendar()
	{
		var codropsEvents = {};
		$.ajax({
			type: "POST",
			dataType:"json",
			async: false,
			url: ajax_object.ajax_url+'?action=getCalendarAction&post_id='+ajax_object.postid,
			success: function(data)
			{
				codropsEvents = data;
			}
		});
		return codropsEvents;
	}

	function fillCalendar()
	{
			var codropsEvents = getCalendar();
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
								modal: true,
								id: 'dialog_print',
								title: 'Impression',
								close: function () { $('.ui-widget-overlay').remove(); $('#cal-print-form').remove();},
								buttons:
										[
										{  //cancel button
											text: 'Annuler',
											click: function () {
												$(this).dialog('close');
											}
										},
										{ //print button
											id: 'PrintDialogButton',
											text: 'Imprimer',
											click: function () {
												var url = ajax_object.ajax_url+'?action=printResaAction&post_id='+ajax_object.postid+'&date='+datePlanning;
												var cx = ''
												$('#table_imprimer').find( 'input[name^=cx]' ).each(function(){
													if (this.checked) {
													  cx += '&cx[]='+$(this).val();}
												});
												if (cx == ''){
													$.dW.alert("Sélectionner une fiche");
													return;
												}
												url += cx;
												$(this).dialog("close");
												window.open(url, 'fiche', 'status=no,location=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=700,height=480,directories=no,', false);
											}
										},
										{
											id: 'PrintPlanningDialogButton',
											text: 'Planning',
											click: function () {
												$(this).dialog("close");
												var url = ajax_object.ajax_url+'?action=printResaAction&post_id='+ajax_object.postid+'&date='+datePlanning+'&out=planning';
												window.open(url, 'planning', 'status=no,location=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=700,height=480,directories=no',false);
												return;
											}
										}
										]});
						var $printForm = $('<form id="cal-print-form"></form>');
						var $printTable = $('<table></table>').attr('id', 'table_imprimer').addClass('imprimer');
						var $printTr = $('<tr></tr>');
						var $input = $('<input />').attr('type','checkbox').attr('id','cxall').attr('name','all');
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
						.append(addTr('<input type="checkbox" name="cx[]" value="bs">Bon de sortie')) //.append(addTr('<input type="checkbox" name="cx[]" value="fc">Facture'))
						.appendTo($printTable);
						$printForm.append($printTable);
						$printDiv.append($printForm).dialog('open');
						return false;
					}
				} );
			var $month = $( '#custom-month' ).html( cal.getMonthName() );
			var $year = $( '#custom-year' ).html( cal.getYear() );
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
	}

	$select_pkmgmt = $('<select></select>')
		.attr('id', 'site_table')
		.attr('name', 'site_table')
		.change(function(){
			try
			{
				var result = $('#JTableContainer').jtable('destroy');
			}
			catch (e)
			{
			}
			$('#JTableContainer').empty();
			clearAll();
			if ( $(this).val() == 'table_reservation')
				j_table_reservation();
			if ( $(this).val() == 'table_entrees')
				j_table_entrees();
			if ( $(this).val() == 'table_sorties')
				j_table_sorties();
			if ( $(this).val() == 'table_services')
				j_table_services();
			if ( $(this).val() == 'table_tarifs')
				j_table_tarifs();
			if ( $(this).val() == 'clients')
				j_clients();
			if ( $(this).val() == 'caisse')
				fct_caisse();
			if ( $(this).val() == 'graph')
				hcharts();

		})
		.appendTo($('#select_pkmgmt'));

		$('<option></option>').append("Réservations").attr('value', 'table_reservation').appendTo($select_pkmgmt);
		$('<option></option>').append("Entrées").attr('value', 'table_entrees').appendTo($select_pkmgmt);
		$('<option></option>').append("Sorties").attr('value', 'table_sorties').appendTo($select_pkmgmt);
		if (ajax_object.admin == 1 )
			$('<option></option>').append("Services").attr('value', 'table_services').appendTo($select_pkmgmt);
		if (ajax_object.admin == 1 )
			$('<option></option>').append("Tarifs").attr('value', 'table_tarifs').appendTo($select_pkmgmt);
		if (ajax_object.admin == 1 )
			$('<option></option>').append("Clients").attr('value', 'clients').appendTo($select_pkmgmt);
		if (ajax_object.admin == 1 )
			$('<option></option>').append("Caisse").attr('value', 'caisse').appendTo($select_pkmgmt);
		if (ajax_object.admin == 1 )
			$('<option></option>').append("Graph").attr('value', 'graph').appendTo($select_pkmgmt);
		jtable_validate();
		jtable_status();
		jtable_facture();
		jtable_bon_de_sortie();
		jtable_bon_de_prise_en_charge();
		jtable_edit();
		jtable_deletion();
		jtable_messages();


		function getData(url) {
			var ret;
			$.ajax({url: url,
				dataType:"json", type:'POST', async:false, success: function(data) { ret = data; }});
			return ret;
		}

		function addTr($elem) {
				var $td = $('<td></td>').append($elem);
				return( $('<tr></tr>').append($td));
			}

		function in_array(needle, haystack, argStrict) {
		  var key = '',
		    strict = !! argStrict;

		  if (strict) {
		    for (key in haystack) {
		      if (haystack[key] === needle) {
		        return true;
		      }
		    }
		  } else {
		    for (key in haystack) {
		      if (haystack[key] == needle) {
		        return true;
		      }
		    }
		  }

		  return false;
		}

		function clearAll()	{
				$('#HeaderContainer').empty();
				$('#button_pkmgmt').empty();
				$('#printDialogButton').parent().parent().parent().remove();
		}

		function convertDateToMySQL(srcdate) {
			var dstdate = srcdate.substr(6,4) + '-';
			dstdate += srcdate.substr(3,2) + '-';
			dstdate += srcdate.substr(0,2);
			dstdate += srcdate.substr(10);
			return dstdate;
		}

		function getFormattedDate(date) {
			var year = date.getFullYear();
  			var month = (1 + date.getMonth()).toString();
  			month = month.length > 1 ? month : '0' + month;
			var day = date.getDate().toString();
			day = day.length > 1 ? day : '0' + day;
			return year + '-' + month + '-' + day;
		}

		function hcharts() {
			if (ajax_object.admin != 1 )
				return;
			$('<div />')
				.attr('id',"hight_container")
				.css({"min-width": "310px", "height": "400px",  "margin": "0 auto"})
				.appendTo($('#JTableContainer'));
			var data = getData(ajax_object.ajax_url+'?action=graph_resa_dayAction&post_id='+ajax_object.postid);
			$('#hight_container').highcharts({
					chart: {
						zoomType: 'x'
					},
					title: {
						text: 'Nombre de réservation par jour'
					},
					subtitle: {
						text: 'Cliquez et faites glisser dans la zone de tracé pour zoomer'
						},
					xAxis: {
						type: 'datetime',
						minRange:  24*3600*1000
					},
					yAxis: {
						title: {
							text: 'Nombre de réservation'
							}
					},
					legend: {
						enabled: false
					},
					plotOptions: {
						area: {
							fillColor: {
								linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1},
								stops: [
									[0, Highcharts.getOptions().colors[0]],
									[1, Highcharts.Color(Highcharts.getOptions().colors[0]).setOpacity(0).get('rgba')]
								]
							},
							marker: {
								radius: 2
							},
							lineWidth: 1,
							states: {
								hover: {
									lineWidth: 1
								}
							},
							threshold: null
						}
					},
					series: [
						{
						type: 'area',
						name: 'nombre l\'année dernière',
						pointInterval: 24*3600*1000,
						pointStart: data.firstdate*1000,
						data: data.dataly
						},
						{
						type: 'area',
						name: 'nombre cette année',
						pointInterval: 24*3600*1000,
						pointStart: data.firstdate*1000,
						data: data.data
						}
						]
				});

		}

		function table_caisse(data) {
			if (ajax_object.admin != 1 )
				return;
			$('#JTableContainer').empty();
			$tbody = $('<tbody />');
			$thead = $('<thead />');
			var total_prix = 0;
			var total_resa = 0;
			$thead.append(
						$('<tr />')
							.append($('<th />').append("Type de paiement"))
							.append($('<th />').append("Nbr reservation"))
							.append($('<th />').append("Total")));
			$.each(data.Result, function(key, value)
				{
					total_prix += parseFloat(value.total);
					total_resa += parseInt(value.nbr_resa);
					$tbody.append(
						$('<tr />')
							.append($('<td />').append(value.paiement))
							.append($('<td />').append(value.nbr_resa))
							.append($('<td />').append(value.total))
					);
				}
				);
			$tbody.append(
						$('<tr />')
							.append($('<td />').append("TOTAL"))
							.append($('<td />').append(total_resa))
							.append($('<td />').append(total_prix))
					);
			$('<table />')
				.attr({'id':'table_caisse'})
				.append($thead)
				.append($tbody)
				.appendTo($('#JTableContainer'));
		}

		function fct_caisse() {
			if (ajax_object.admin != 1 )
				return;
			var data = getData(ajax_object.ajax_url+'?action=datecaisseAction&post_id='+ajax_object.postid);
			var now = getFormattedDate(new Date());
			$('<div />')
				 .addClass('filtering')
				 .append($('<form />').attr('type', 'post').attr('action', '')
				 .append($('<select />').attr('id', 'pkmgmt-filtering-select')
							.change(function()
								{
									$('#JTableContainer').html(ajax_object.plugin_url+'/admin/images/loading.gif');
									var seek = $('#pkmgmt-filtering-select').val();
									table_caisse(getData(ajax_object.ajax_url+'?action=caisseAction&post_id='+ajax_object.postid+'&seek='+seek));
								})
							)
					)
				.appendTo($('#HeaderContainer'));
			$.each(data.Result, function(key, value)
			{
				if ( now == value.date_facture )
					$('<option />').addClass('today').attr({'value': value.date_facture, 'selected':'selected' }).append(value.date_facture).appendTo($('#pkmgmt-filtering-select'));
				else
					$('<option />').attr('value', value.date_facture).append(value.date_facture).appendTo($('#pkmgmt-filtering-select'));
			});

			$('#pkmgmt-filtering-select').change();

		}

		function j_table_tarifs()
		{
			if (ajax_object.admin != 1 )
				return;
			$('<div />')
				 .addClass('filtering')
				 .append($('<form />').attr('type', 'post').attr('action', '')
				 .append($('<input />').attr('type', 'search').attr('id', 'pkmgmt-search-input').attr('name', 'page=pkmgmt-'+ajax_object.postid+"&seek")
						.keypress(function(e){if(e.keyCode==13) {event.preventDefault();$('#pkmgmt-site-action').click();}}))
				 .append($('<input />').attr('type','button').attr('id','pkmgmt-site-action').attr('value','Refresh').addClass('button')
						.click(function (e) {
				        	e.preventDefault();
							var seek = $('#pkmgmt-search-input').val();
							var tarif = $('#pkmgmt-filtering-select').val();
				            $('#JTableContainer').jtable('load',{ 'seek': seek, 'tarif': tarif });
		        		}))
				 .append('<br />')
				 .append($('<select />').attr('id', 'pkmgmt-filtering-select')
							.append($('<option />').attr({'value':'ext', 'selected':'selected'}).append('Tarifs Aérien'))
							.append($('<option />').attr('value', 'int').append('Tarifs Couvert'))
							.append($('<option />').attr('value', 'eco').append('Tarifs Eco'))
							.append($('<option />').attr('value', 'basse').append('Tarifs Basse saison'))
							.change(function()
								{
									$('#pkmgmt-site-action').click();
								})
							)
					)
				.appendTo($('#HeaderContainer'));
			var actions = {
					listAction:   ajax_object.ajax_url+'?action=listTarifsAction&post_id='+ajax_object.postid,
					createAction: ajax_object.ajax_url+'?action=createTarifAction&post_id='+ajax_object.postid,
					updateAction: ajax_object.ajax_url+'?action=updateTarifsAction&post_id='+ajax_object.postid,
				};
			if (ajax_object.admin == 1 )
			 actions.deleteAction = ajax_object.ajax_url+'?action=deleteTarifsAction&post_id='+ajax_object.postid;

			var jTarifs = {
				title: 'Tarifs',
				selecting: true,
				multiselect: true,
				selectingCheckboxes: true,
				paging: true,
				pageSize: 30,
				sorting: true,
				defaultSorting:'nbr_jours ASC',
				actions: actions,
				fields: {
					id: {
						key: true,
						list: false,
						create: false,
						edit: false,
						title: 'Identifiant'
					},
					nbr_jours: {
						title: 'Nombre de jours',
						create: true,
						edit: true,
						list: true
					},
					prix: {
						title: 'Prix',
						create: true,
						edit: true,
						list: true
					}
				},
				formCreated: function (event, data)
				{
					var tarif =$('#pkmgmt-filtering-select').val();
					$('form').append('<input type="hidden" name="tarif" value="'+tarif+'"/>');
				}

			};
			$('#JTableContainer').jtable(jTarifs);
			addDeleteButton();
			$('#pkmgmt-site-action').click();

		}

		function j_clients() {
			if (ajax_object.admin != 1 )
				return;
			$('<div />')
				.addClass('filtering')
				.append($('<form />').attr('type', 'post').attr('action', '')
				.append($('<input />').attr('type', 'search').attr('id', 'pkmgmt-search-input').attr('name', 'page=pkmgmt-'+ajax_object.postid+"&seek")
						.keypress(function(e){if(e.keyCode==13) {event.preventDefault();$('#pkmgmt-site-action').click();}}))
				.append($('<input />').attr('type','button').attr('id','pkmgmt-site-action').attr('value','Refresh').addClass('button')
						.click(function (e) {
				        	e.preventDefault();
							var seek = $('#pkmgmt-search-input').val();
				            $('#JTableContainer').jtable('load',{ 'seek': seek });
		        		}))
				)
	.appendTo($('#HeaderContainer'));
			var actions = {
					listAction:   ajax_object.ajax_url+'?action=listClientsAction&post_id='+ajax_object.postid,
					createAction: ajax_object.ajax_url+'?action=createClientAction&post_id='+ajax_object.postid,
					updateAction: ajax_object.ajax_url+'?action=updateClientAction&post_id='+ajax_object.postid,
				};
			if (ajax_object.admin == 1 )
			 actions.deleteAction = ajax_object.ajax_url+'?action=deleteClientAction&post_id='+ajax_object.postid;

			var jClients = {
				title: 'Clients',
				selecting: true,
				multiselect: true,
				selectingCheckboxes: true,
				paging: true,
				pageSize: 30,
				sorting: true,
				defaultSorting:'nom ASC',
				actions: actions,
				fields: {
						id: {
							key: true,
							list: false,
							create: false,
							edit: false,
							title: 'Identifiant'
						}
						,nom: {
							title: 'Nom',
							create: true,
							edit: true,
							list: true
						}
						,prenom: {
							title: 'Prénom',
							create: true,
							edit: true,
							list: true
						}
						,adresse: {
							title: 'Adresse',
							create: true,
							edit: true,
							list: false
						}
						,ville: {
							title: 'Ville',
							create: true,
							edit: true,
							list: false
						}
						,code_postal: {
							title: 'Code Postal',
							create: true,
							edit: true,
							list: true
						}
						,email: {
							title: 'Email',
							type: 'email',
							create: true,
							edit: true,
							list: true
						}
						,mobile: {
							title: 'Mobile',
							type: 'phone',
							create: true,
							edit: true,
							list: true
						}
					}
				};
			$('#JTableContainer').jtable(jClients);
			addDeleteButton();
			$('#pkmgmt-site-action').click();

		}


		function j_table_services() {
			if (ajax_object.admin != 1 )
				return;
			$('<div />')
				.addClass('filtering')
				.append($('<form />').attr('type', 'post').attr('action', '')
				.append($('<input />').attr('type', 'search').attr('id', 'pkmgmt-search-input').attr('name', 'page=pkmgmt-'+ajax_object.postid+"&seek")
						.keypress(function(e){if(e.keyCode==13) {event.preventDefault();$('#pkmgmt-site-action').click();}}))
				.append($('<input />').attr('type','button').attr('id','pkmgmt-site-action').attr('value','Refresh').addClass('button')
						.click(function (e) {
				        	e.preventDefault();
							var seek = $('#pkmgmt-search-input').val();
							var status = $('#pkmgmt-filtering-select').val();
				            $('#JTableContainer').jtable('load',{ 'seek': seek });
		        		}))
				)
	.appendTo($('#HeaderContainer'));
			var actions = {
					listAction:   ajax_object.ajax_url+'?action=listServicesAction&post_id='+ajax_object.postid,
					createAction: ajax_object.ajax_url+'?action=createServiceAction&post_id='+ajax_object.postid,
					updateAction: ajax_object.ajax_url+'?action=updateServicesAction&post_id='+ajax_object.postid,
				};
			if (ajax_object.admin == 1 )
			 actions.deleteAction = ajax_object.ajax_url+'?action=deleteServicesAction&post_id='+ajax_object.postid;

			var jServices = {
				title: 'Services',
				selecting: true,
				multiselect: true,
				selectingCheckboxes: true,
				paging: true,
				pageSize: 30,
				sorting: true,
				defaultSorting:'name ASC',
				actions: actions,
				fields: {
					id: {
						key: true,
						list: true,
						create: false,
						edit: false,
						title: 'Identifiant'
					},
					name: {
						title: 'Nom',
						create: true,
						edit: true,
						list: true
					},
					valeur: {
						title: 'Valeur',
						create: true,
						edit: true,
						list: true
					}
				}

			};
			$('#JTableContainer').jtable(jServices);
			addDeleteButton();
			$('#pkmgmt-site-action').click();

		}

		function j_table_reservation()
		{
			$('<div />')
				.addClass('filtering')
				.append($('<form />').attr('type', 'post').attr('action', '')
					.append($('<input />').attr('type', 'search').attr('id', 'pkmgmt-search-input').attr('name', 'page=pkmgmt-'+ajax_object.postid+"&seek")
								.keypress(function(e){if(e.keyCode==13) {event.preventDefault();$('#pkmgmt-site-action').click();}}))
					.append($('<input />').attr('type','button').attr('id','pkmgmt-site-action').attr('value','Appliquer').addClass('button')
						.click(function (e) {
				        	e.preventDefault();
							var seek = $('#pkmgmt-search-input').val();
							var status = $('#pkmgmt-filtering-select').val();
				            $('#JTableContainer').jtable(
								'load', {
									'seek': seek, 
									'status': status
								});
		        		}))
					.append('<br />')
					.append($('<select />').attr('id', 'pkmgmt-filtering-select')
							.append($('<option />').attr('value', '0').append('A Valider'))
							.append($('<option />').attr({'value':'1', 'selected':'selected'}).append('Validé'))
							.append($('<option />').attr('value', '2').append('Arrivé'))
							.append($('<option />').attr('value', '3').append('Sortie'))
							.change(function()
								{
									var seek = $('#pkmgmt-search-input').val();
									var status = $('#pkmgmt-filtering-select').val();
					            	$('#JTableContainer').jtable(
								'load', {
									'nom': seek, 'status': status
								});
								})
							)
					)
				.appendTo($('#HeaderContainer'));

				var status_opt = {
							'1': 'Validé'
						};
				if (ajax_object.admin == 1)
				{
					$('#pkmgmt-filtering-select')
						.append($('<option />').attr('value', '7').append('En suspend'))
						.append($('<option />').attr('value', '8').append('Pas venu'))
						.append($('<option />').attr('value', '9').append('Supprimé'));
					status_opt['2'] ='Arrivé';
					status_opt['3'] ='Sortie';
					status_opt['7'] ='En suspend';
					status_opt['8'] ='Pas venu';
					status_opt['9'] ='Supprimé';
				}
			aerogare_aller = ajax_object.aerogare_aller;
			aerogare_retour = ajax_object.aerogare_retour;
			var self = this;
			var jReservation = {
				title: 'Réservations',
				showTime: true,
				stepMinute: 5,
				selecting: true,
				multiselect: true,
				database: JSON.parse(ajax_object.database),
				selectingCheckboxes: true,
				paging: true,
				pageSize: 100,
				sorting: true,
				defaultSorting:'id DESC',
				actions: {
					listAction:   ajax_object.ajax_url+'?action=listResaAction&post_id='+ajax_object.postid,
					createAction: ajax_object.ajax_url+'?action=createResaAction&post_id='+ajax_object.postid,
					updateAction: ajax_object.ajax_url+'?action=updateResaAction&post_id='+ajax_object.postid,
					deleteAction: ajax_object.ajax_url+'?action=deleteResaAction&post_id='+ajax_object.postid,
					validAction: ajax_object.ajax_url+'?action=validResaAction&post_id='+ajax_object.postid,
					invoiceAction: ajax_object.ajax_url+'?action=invoiceResaAction&post_id='+ajax_object.postid,
					exitSplitAction: ajax_object.ajax_url+'?action=exitSplitResaAction&post_id='+ajax_object.postid,
					deliveryAction: ajax_object.ajax_url+'?action=deliveryResaAction&post_id='+ajax_object.postid,
					getResaTarifAction: ajax_object.ajax_url+'?action=getResaTarif&post_id='+ajax_object.postid,
					serviceResaTarifAction: ajax_object.ajax_url+'?action=serviceResaTarif&post_id='+ajax_object.postid,
					updateResaDBAction: ajax_object.ajax_url+'?action=updateResaDB&post_id='+ajax_object.postid
				},
				fields: {
					id: {
						key: true,
						list: true,
						create: false,
						edit: false,
						title: 'Identifiant'
					},
					date_create: {
						title: 'Date de creation',
						create: false,
						edit: false,
						list: true
					},
					civilite: {
						title: 'Civilité',
						type: 'select',
						options: {
							'0': 'Mademoiselle',
							'1': 'Madame',
							'2': 'Monsieur'
						},
						defaultValue: '0',
						list: false
					},
					paiement: {
						title : 'Paiement',
						list: false,
						edit: false,
						create: false
					},
					nom: {
						title: 'Nom',
						sorting: false,
						inputClass: 'validate[required]',
						list: true
					},
					prenom: {
						title: 'Prénom',
						inputClass: 'validate[required]',
						list: false
					},
					adresse: {
						title: 'Adresse',
						inputClass: 'validate[required]',
						list: false
					},
					cp: {
						title: 'Code Postal',
						inputClass: 'validate[required]',
						list: false
					},
					ville: {
						title: 'Ville',
						inputClass: 'validate[required]',
						list: false
					},
					mobile: {
						title: 'Mobile',
						sorting: false,
						inputClass: 'validate[required]',
						list: true
					},
					status: {
						title: 'Status',
						sorting: false,
						type: 'select',
						options: status_opt,
						defaultValue: '1',
						list: false
					},
					email: {
						title: 'Email',
						type: 'email',
						inputClass: 'validate[required]',
						list: false
					},
					modele: {
						title: 'Modèle',
						inputClass: 'validate[required]',
						list: false
					},
					immatriculation: {
						title: 'Immatriculation',
						inputClass: 'validate[required]',
						list: false,
						edit: true
					},
					codepromo: {
						title: 'Code Promo',
						list: false,
						edit: true
					},
					type: {
						title: 'Type',
						create: true,
						edit: true,
						type: 'select',
						options: {'ext':'ext', 'int': 'int', 'pre':'pre'},
						defaultValue: 'ext',
						list: false
					},
					zone: {
						title: 'Zone',
						create: false,
						edit: true,
						list: false
					},
					prix_resa: {
						title: 'Tarif',
						create: false,
						edit: true,
						list: false
					},
					navette: {
						title: 'Arrivée au parking',
						inputClass: 'validate[required]',
						list: true,
						sorting: false,
						type: 'date',
						params: {
							stepMinute: 5
							},
						displayFormat: 'dd/mm/yy',
						displayTimeFormat: 'hh:mm'
					},
					terminal_aller: {
						title: 'Terminal aller',
						type: 'select',
						options: ajax_object.aerogare_aller.options,
						defaultValue: 'S',
						list: false
					},
					nbr_aller: {
						title: '# pers. Aller',
						inputClass: 'validate[required]',
						list: false
					},
					date_retour: {
						title: 'Date de retour',
						list: true,
						sorting: true,
						type: 'date',
						params: {
							stepMinute: 5
							},
						displayFormat: 'dd/mm/yy'
					},
					terminal_retour: {
						title: 'Terminal retour',
						type: 'select',
						options: ajax_object.aerogare_retour.options,
						defaultValue: 'S',
						list: false
					},
					nbr_retour: {
						title: '# pers. Retour',
						inputClass: 'validate[required]',
						list: false
					},
					compagnie: {
						title: 'Compagnie',
						list: false
					},
					destination: {
						title: 'Destination',
						list: false
					}
				},
				formCreated: function (event, data)
				{
					if ( data.formType != 'edit' && data.formType != 'valid')
						return;
					if (ajax_object.admin == 1 )
						return;
					var navette = data.form.find('input[name^=navette]').val();
					//var date_retour = data.form.find('input[name^=date_retour]').val();
					var d1 = new Date();
					d1.setDate(navette.substr(0,2));
					d1.setMonth(navette.substr(3,2)-1);
					d1.setYear(navette.substr(6,4));
					d1.setHours(0);
					d1.setMinutes(0);
					d1.setSeconds(0);
					d1.setMilliseconds(0);
					d1 = d1.getTime();
					d2 = new Date();
					d2.setHours(0);
					d2.setMinutes(0);
					d2.setSeconds(0);
					d2.setMilliseconds(0);
					d2 = d2.getTime();

					if ( d2 >= d1 )
					{
						if ( data.formType == 'edit' )
						{
							$("#jtable-edit-form input").attr("disabled", true);
							$("#jtable-edit-form select").prop('disabled', 'disabled');
						}
						if ( data.formType = 'valid' )
						{
							$("#validate-form input").attr("disabled", true);
							$("#validate-form select").prop('disabled', 'disabled');
							var $validButton = $('#ValidDialogButton');
							var $calculButton = $('#CalculDialogButton');
							data.instance._setEnabledOfDialogButton($validButton, false, 'Valider');
							data.instance._setEnabledOfDialogButton($calculButton, false, 'Calculer');
						}
					}
				}

			};
			if (ajax_object.admin != 1 )
				jReservation.actions.updateAction = undefined;
			$('#JTableContainer').jtable(jReservation);
		        //Load all records when page is first shown
			$('#pkmgmt-site-action').click();
			//$(document).on('heartbeat-tick', function () {
				//$('#pkmgmt-site-action').click();
				//} );
				//$('#reservationTableContainer').jtable('load');
			addDeleteButton();
			$('#button_pkmgmt').append('<span>&nbsp;</span>');
			addPrintButton();
			$('#button_pkmgmt').append('<span>&nbsp;</span>');
			addCalButton();
		}

		function j_table_entrees()
		{
			$('<div />')
				.addClass('filtering')
				.append($('<form />').attr('type', 'post').attr('action', '')
					.append($('<input />').attr('type', 'search').attr('id', 'pkmgmt-search-input').attr('name', 'page=pkmgmt-'+ajax_object.postid+"&seek")
								.keypress(function(e){if(e.keyCode==13) {event.preventDefault();$('#pkmgmt-site-action').click();}}))
					.append($('<input />').attr('type','button').attr('id','pkmgmt-site-action').attr('value','Appliquer').addClass('button')
						.click(function (e) {
				        	e.preventDefault();
							var seek = $('#pkmgmt-search-input').val();
				            $('#JTableContainer').jtable(
								'load', {
									'seek': seek,'status': '1'
								});
		        		}))
					.append('<br />')

					)
				.appendTo($('#HeaderContainer'));
			aerogare_aller = ajax_object.aerogare_aller;
			aerogare_retour = ajax_object.aerogare_retour;
			var self = this;
			var jEntrees = {
				title: 'Entrées',
				showTime: true,
				stepMinute: 5,
				selecting: true,
				multiselect: true,
				database: JSON.parse(ajax_object.database),
				selectingCheckboxes: true,
				paging: true,
				pageSize: 100,
				sorting: true,
				defaultSorting:'navette ASC',
				actions: {
					listAction:   ajax_object.ajax_url+'?action=listResaAction&post_id='+ajax_object.postid+'&type=TodayIn',
					createAction: ajax_object.ajax_url+'?action=createResaAction&post_id='+ajax_object.postid,
					updateAction: ajax_object.ajax_url+'?action=updateResaAction&post_id='+ajax_object.postid,
					statusAction: ajax_object.ajax_url+'?action=statusResaAction&post_id='+ajax_object.postid
				},
				fields: {
					id: {
						key: true,
						list: true,
						create: false,
						edit: false,
						title: 'Identifiant'
					},
					date_create: {
						title: 'Date de creation',
						create: false,
						edit: false,
						list: true
					},
					nom: {
						title: 'Nom',
						sorting: false,
						inputClass: 'validate[required]',
						list: true
					},
					prenom: {
						title: 'Prénom',
						inputClass: 'validate[required]',
						list: false
					},
					mobile: {
						title: 'Mobile',
						sorting: false,
						inputClass: 'validate[required]',
						list: true
					},
					status: {
						title: 'Status',
						sorting: false,
						edit: false,
						create:false,
						type: 'select',
						options: {
							'0': 'A valider',
							'1': 'Validé',
							'2': 'Arrivé'
						},
						defaultValue: '1',
						list: false
					},
					email: {
						title: 'Email',
						type: 'email',
						inputClass: 'validate[required]',
						list: false
					},
					modele: {
						title: 'Modèle',
						inputClass: 'validate[required]',
						list: false
					},
					immatriculation: {
						title: 'Immatriculation',
						inputClass: 'validate[required]',
						list: false,
						edit: true
					},
					codepromo: {
						title: 'Code Promo',
						list: false,
						edit: true
					},
					type: {
						title: 'Type',
						create: true,
						edit: true,
						type: 'select',
						options: {'ext':'ext', 'int': 'int', 'pre':'pre'},
						defaultValue: 'ext',
						list: false
					},
					navette: {
						title: 'Arrivée au parking',
						inputClass: 'validate[required]',
						list: true,
						sorting: true,
						type: 'date',
						params: {
							stepMinute: 5
							},
						displayFormat: 'dd/mm/yy',
						displayTimeFormat: 'hh:mm'
					},
					terminal_aller: {
						title: 'Terminal aller',
						type: 'select',
						options: {'W':'Ouest', 'S': 'Sud'},
						defaultValue: 'S',
						list: false
					},
					nbr_aller: {
						title: '# pers. Aller',
						inputClass: 'validate[required]',
						list: false
					},
					date_retour: {
						title: 'Date de retour',
						list: true,
						sorting: false,
						type: 'date',
						params: {
							stepMinute: 5
							},
						displayFormat: 'dd/mm/yy'
					},
					terminal_retour: {
						title: 'Terminal retour',
						type: 'select',
						options: {'W':'Ouest', 'S': 'Sud'},
						defaultValue: 'S',
						list: false
					},
					nbr_retour: {
						title: '# pers. Retour',
						inputClass: 'validate[required]',
						list: false
					}
				},
				formCreated: function (event, data)
				{
					if ( data.formType != 'edit' )
						return;
					if ( data.formType == 'create' )
					{
						var $statut = data.form.find('input[name^=statut]');
						$statut.val(0);
					}

					return;
					var navette = data.form.find('input[name^=navette]').val();
					var d1 = new Date();
					d1.setDate(navette.substr(0,2));
					d1.setMonth(navette.substr(3,2)-1);
					d1.setYear(navette.substr(6,4));
					d1.setHours(0);
					d1.setMinutes(0);
					d1.setSeconds(0);
					d1.setMilliseconds(0);
					d1 = d1.getTime();
					d2 = new Date();
					d2.setHours(0);
					d2.setMinutes(0);
					d2.setSeconds(0);
					d2.setMilliseconds(0);
					d2 = d2.getTime();
					if ( d2 >= d1 )
					{
						$("#jtable-edit-form input").attr("disabled", true);
						$("#jtable-edit-form select").prop('disabled', 'disabled');
					}
				}

			};

			$('#JTableContainer').jtable(jEntrees);
		        //Load all records when page is first shown
			$('#pkmgmt-site-action').click();
		}

		function j_table_sorties()
		{
			$('<div />')
				.addClass('filtering')
				.append($('<form />').attr('type', 'post').attr('action', '')
					.append($('<input />').attr('type', 'search').attr('id', 'pkmgmt-search-input').attr('name', 'page=pkmgmt-'+ajax_object.postid+"&seek")
								.keypress(function(e){if(e.keyCode==13) {event.preventDefault();$('#pkmgmt-site-action').click();}}))
					.append($('<input />').attr('type','button').attr('id','pkmgmt-site-action').attr('value','Appliquer').addClass('button')
						.click(function (e) {
				        	e.preventDefault();
							var seek = $('#pkmgmt-search-input').val();
				            $('#JTableContainer').jtable(
								'load', {
									'seek': seek,'status': '2'
								});
		        		}))
					.append('<br />')

					)
				.appendTo($('#HeaderContainer'));
			aerogare_aller = ajax_object.aerogare_aller;
			aerogare_retour = ajax_object.aerogare_retour;
			var self = this;
			var jSorties = {
				title: 'Sorties',
				showTime: true,
				stepMinute: 5,
				selecting: true,
				multiselect: true,
				database: JSON.parse(ajax_object.database),
				selectingCheckboxes: true,
				paging: true,
				pageSize: 100,
				sorting: true,
				defaultSorting:'date_retour ASC',
				actions: {
					listAction:   ajax_object.ajax_url+'?action=listResaAction&post_id='+ajax_object.postid+'&type=TodayOut',
					validAction: ajax_object.ajax_url+'?action=validResaAction&post_id='+ajax_object.postid,
					getResaTarifAction: ajax_object.ajax_url+'?action=getResaTarif&post_id='+ajax_object.postid,
					invoiceAction: ajax_object.ajax_url+'?action=invoiceResaAction&post_id='+ajax_object.postid,
					updateAction: ajax_object.ajax_url+'?action=updateResaAction&post_id='+ajax_object.postid,
					updateResaDBAction: ajax_object.ajax_url+'?action=updateResaDB&post_id='+ajax_object.postid
				},
				fields: {
					id: {
						key: true,
						list: true,
						create: false,
						edit: false,
						title: 'Identifiant'
					},
					date_create: {
						title: 'Date de creation',
						create: false,
						edit: false,
						list: true
					},
					nom: {
						title: 'Nom',
						sorting: false,
						inputClass: 'validate[required]',
						list: true
					},
					prenom: {
						title: 'Prénom',
						inputClass: 'validate[required]',
						list: false
					},
					mobile: {
						title: 'Mobile',
						sorting: false,
						inputClass: 'validate[required]',
						list: true
					},
					status: {
						title: 'Status',
						sorting: false,
						edit: false,
						create:false,
						type: 'select',
						options: {
							'0': 'A valider',
							'1': 'Validé',
							'2': 'Arrivé'
						},
						defaultValue: '1',
						list: false
					},
					email: {
						title: 'Email',
						type: 'email',
						inputClass: 'validate[required]',
						list: false
					},
					immatriculation: {
						title: 'Immatriculation',
						inputClass: 'validate[required]',
						list: false,
						edit: true
					},
					codepromo: {
						title: 'Code Promo',
						list: false,
						edit: true
					},
					type: {
						title: 'Type',
						create: true,
						edit: true,
						type: 'select',
						options: {'ext':'ext', 'int': 'int', 'pre':'pre'},
						defaultValue: 'ext',
						list: false
					},
					navette: {
						title: 'Arrivée au parking',
						list: true,
						sorting: false,
						type: 'date',
						params: {
							stepMinute: 5
							},
						displayFormat: 'dd/mm/yy',
						displayTimeFormat: 'hh:mm'
					},
					date_retour: {
						title: 'Date de retour',
						list: true,
						sorting: true,
						type: 'date',
						params: {
							stepMinute: 5
							},
						displayFormat: 'dd/mm/yy'
					},
					paiement: {
						title : 'Paiement',
						list: false,
						edit: false,
						create: false
					},
					nbr_retour: {
						title: '# pers. Retour',
						inputClass: 'validate[required]',
						list: false
					}
				},
				formCreated: function (event, data)
				{
					if ( data.formType != 'edit' )
						return;
					var navette = data.form.find('input[name^=navette]').val();
					var d1 = new Date();
					d1.setDate(navette.substr(0,2));
					d1.setMonth(navette.substr(3,2)-1);
					d1.setYear(navette.substr(6,4));
					d1.setHours(0);
					d1.setMinutes(0);
					d1.setSeconds(0);
					d1.setMilliseconds(0);
					d1 = d1.getTime();
					d2 = new Date();
					d2.setHours(0);
					d2.setMinutes(0);
					d2.setSeconds(0);
					d2.setMilliseconds(0);
					d2 = d2.getTime();
					if ( d2 >= d1 )
					{
						$("#jtable-edit-form input[name^=navette]").attr("disabled", true);
						//$("#jtable-edit-form select").prop('disabled', 'disabled');
					}
				}

			};

			$('#JTableContainer').jtable(jSorties);
		        //Load all records when page is first shown
			$('#pkmgmt-site-action').click();
		}


		/************************************************************************
		* Add DELETE Button 											  *
		*************************************************************************/
		function addDeleteButton()
		{
			$('<input />')
				.attr('id', 'DeleteButton')
				.addClass('button')
				.attr('type', 'button')
				.attr('value','Supprimer')
				.click(function (){
					var selectedRows = $('#JTableContainer').jtable('selectedRows');
					if (selectedRows.length <= 0){
						$.dW.alert("Vous devez sélectionner au moins une ligne", {modal: true});
						return;
					}
					var deleteConfirmMessage = "Voulez-vous supprimer les enregistrements sélectionnés ?";
					$.dW.confirm( deleteConfirmMessage ,
						function() {
							$('#JTableContainer').jtable('deleteRows', selectedRows);
						},
						function() { return false; }, {modal: true});
				})
				.appendTo($('#button_pkmgmt'));

		}

		/************************************************************************
		* VALIDATE  extension for jTable											  *
		*************************************************************************/
		function jtable_validate()
		{
		//Reference to base object members
			var base = {
				_create: $.hik.jtable.prototype._create,
				_addColumnsToHeaderRow: $.hik.jtable.prototype._addColumnsToHeaderRow,
				_addCellsToRowUsingRecord: $.hik.jtable.prototype._addCellsToRowUsingRecord
			};

			//extension members
			$.extend(true, $.hik.jtable.prototype, {

				/************************************************************************
				* DEFAULT OPTIONS / EVENTS												  *
				*************************************************************************/
			options: {

				//Localization
				messages: {
					validRecord: 'Validate'
				}
			},

			/************************************************************************
			* PRIVATE FIELDS														*
			*************************************************************************/

			_$validDiv: null, //Reference to the editing dialog div (jQuery object)
			_$validingRow: null, //Reference to currently editing row (jQuery object)

			/************************************************************************
			* CONSTRUCTOR AND INITIALIZATION METHODS								*
			*************************************************************************/

			/* Overrides base method to do editing-specific constructions.
			*************************************************************************/
			_create: function () {
				base._create.apply(this, arguments);
				this._createValidDialogDiv();
			},

			/* Creates and prepares edit dialog div
			*************************************************************************/
			_createValidDialogDiv: function () {
				var self = this;

            //Create a div for dialog and add to container element
            self._$validDiv = $('<div></div>')
                .appendTo(self._$mainContainer);

            //Prepare dialog
            self._$validDiv.dialog({
                autoOpen: false,
                show: self.options.dialogShowEffect,
                hide: self.options.dialogHideEffect,
                width: 'auto',
                minWidth: '300',
								height: '540',
                modal: true,
                title: 'Validation',
                buttons:
					[
						{  //cancel button
                            text: self.options.messages.cancel,
                            click: function () { 	$(this).dialog('close'); }
                        },
						{  	//calcul button
							id: 'CalculDialogButton',
						 	text: self.options.messages.calcul,
                            click: function ()
							{
								var record = self._$validingRow.data('record');
								var $validForm = self._$validDiv.find('form:first');
								var $resaid = $validForm.find('input[name^=resaid]');
								var resaId = $resaid.val();
								//record['resaid'];
                                self._recalcul(resaId);
                            }
						},
						{ //save button
                            id: 'ValidDialogButton',
                            text: self.options.messages.valid,
                            click: function ()
							{
								var $validForm = self._$validDiv.find('form:first');
								var $validButton = $('#ValidDialogButton');

								if (self._trigger("formSubmitting", null, { form: $validForm, formType: 'valid', row: self._$validingRow }) != false)
								{
                                    self._setEnabledOfDialogButton($validButton, false, self.options.messages.valid);
                                    self._saveValidForm($validForm, $validButton);
                                }
                                //self._$validDiv.dialog('close');
							}
						}
					],
                close: function () {
                    var $validForm = self._$validDiv.find('form:first');
                    var $validButton = $('#ValidDialogButton');
                    self._trigger("formClosed", null, { form: $validForm, formType: 'valid', row: self._$validingRow });
                    self._setEnabledOfDialogButton($validButton, true, self.options.messages.valid);
					$('.ui-widget-overlay').remove();
					$('#validate-form').remove();

                }
            });
			},

			/************************************************************************
			* PUBLIC METHODS														*
			*************************************************************************/

			/************************************************************************
			* OVERRIDED METHODS													 	*
			*************************************************************************/

			/* Overrides base method to add a 'facting column cell' to header row.
			*************************************************************************/
			_addColumnsToHeaderRow: function ($tr) {
				base._addColumnsToHeaderRow.apply(this, arguments);
						 if (this.options.actions.validAction != undefined) {
					$tr.append(this._createEmptyCommandHeader());
				}
			},

			/* Overrides base method to add a 'fact command cell' to a row.
			*************************************************************************/
			_addCellsToRowUsingRecord: function ($row) {
				var self = this;
				base._addCellsToRowUsingRecord.apply(this, arguments);

				if (self.options.actions.validAction != undefined) {
					var $span = $('<span></span>').html(self.options.messages.validRecord);
					var $button = $('<button title="' + self.options.messages.validRecord + '"></button>')
						.addClass('jtable-command-button jtable-valid-command-button')
						.append($span)
						.click(function (e) {
							e.preventDefault();
							e.stopPropagation();
							self._showValidForm($row);
						});
					$('<td></td>')
						.addClass('jtable-valid-command-column')
						.append($button)
						.appendTo($row);
				}
			},

			/* Gets text for a field of a record according to it's type.
			*************************************************************************/
			_getValueForRecordField: function (record, fieldName) {
				var field = this.options.fields[fieldName];
				var fieldValue = record[fieldName];
				if (field.type == 'date') {
					return this._getDisplayTextForDateRecordField(field, fieldValue);
				} else if (field.type == 'time')
				{
					return this._getDisplayTextForTimeRecordField(field, fieldValue);
				} else {
					return fieldValue;
				}
			},

			_createDateInputForField: function (field, fieldName, value)
			{
				var displayFormat = field.displayFormat || this.options.defaultDateFormat;
				var displayTimeFormat = field.displayTimeFormat || this.options.defaultTimeFormat;
				var stepMinute = field.stepMinute || this.options.stepMinute;
				var stepHour = field.stepHour || this.options.stepHour;
				var $input = $('<input class="' + field.inputClass + '" id="Edit-' + fieldName + '" type="text" name="' + fieldName + '"></input>');
				if(value != undefined) {
					$input.val(value);
				}
				var options = { 'controlType': 'select', 'dateFormat': displayFormat, 'timeFormat': displayTimeFormat, 'stepMinute': stepMinute, 'stepHour': stepHour };
				if ( field.params )
					options = $.extend(true, {}, options, field.params)
				$input.datetimepicker(options);
				return $('<div />')
					.addClass('jtable-input jtable-date-input')
					.append($input);
			},

			/* Creates a time input for a field.
			*************************************************************************/
			_createTimeInputForField: function (field, fieldName, value)
			{
				var $input = $('<input class="' + field.inputClass + '" id="Edit-' + fieldName + '" type="text"' + (value != undefined ? 'value="' + value + '"' : '') + ' name="' + fieldName + '"></input>');
				if(value != undefined) {
					$input.val(value);
				}
				var options = { 'controlType': 'select','timeFormat': displayTimeFormat, 'stepMinute': stepMinute, 'stepHour': stepHour };
				if ( field.params )
					options = $.extend(true, {}, options, field.params)
				var displayTimeFormat = field.displayTimeFormat || this.options.defaultTimeFormat;
				var stepMinute = field.stepMinute || this.options.stepMinute;
				var stepHour = field.stepHour || this.options.stepHour;
				$input.timepicker(options);
				return $('<div />')
					.addClass('jtable-input jtable-date-input')
					.append($input);
			},

			_formValidationEvent : function ( tarif, resaId)
			{
				var self = this;
				var vform = {
				nom		 	: $('#validate-form').find('input[name^=nom]'),
				mobile	 		: $('#validate-form').find('input[name^=mobile]'),
				modele	 		: $('#validate-form').find('input[name^=modele]'),
				navette 		: $('#validate-form').find('input[name^=navette]'),
				nbr_aller 		: $('#validate-form').find('input[name^=nbr_aller]'),
				nbr_retour 		: $('#validate-form').find('input[name^=nbr_retour]'),
				prix_resa 		: $('#validate-form').find('input[name^=prix_resa]'),
				etat_des_lieux 	: $('#validate-form').find('input[name^=etat_des_lieux]'),
				remorque 		: $('#validate-form').find('input[name^=remorque]'),
				oubli 			: $('#validate-form').find('input[name^=oubli]'),
				lavage 			: $('#validate-form').find('input[name^=lavage]'),
				categorie 		: $('#validate-form').find('input[name^=categorie]'),
				notification 	: $('#validate-form').find('input[name^=notification]'),
				smssend 		: $('#validate-form').find('input[name^=smssend]')

				};
				vform.nom.change(function()
				{
					var value = $(this).val();
					self._updateDb(self.options.database.table_reservation,{'nom':value},{'id':resaId});
				});
				vform.mobile.change(function()
				{
					var value = $(this).val();
					self._updateDb(self.options.database.table_reservation,{'mobile': value},{'id':resaId});
				});
				vform.modele.change(function()
				{
					var value = $(this).val();
					self._updateDb(self.options.database.table_reservation,{'modele':value},{'id':resaId});
				});
				vform.nbr_aller.change(function()
				{
					var value = $(this).val();
					self._updateDb(self.options.database.table_reservation, {'nbr_aller': value}, {'id':resaId});
					//self._recalcul(resaId);
				});
				vform.nbr_retour.change(function()
				{
					var value = $(this).val();
					self._updateDb(self.options.database.table_reservation, {'nbr_retour':value}, {'id':resaId});
					//self._recalcul(resaId);
				});
				if ( ajax_object.admin != 1 )
				{
					vform.prix_resa.attr("readonly", "readonly");
					vform.prix_resa.click(function() {
						$.dW.alert("Vous n'avez pas les droits suffisants", {modal: true});
					});
				}
				else
				{
					vform.prix_resa.change(function()
					{
						var value = $(this).val();
						self._updateDb(self.options.database.table_reservation, {'prix_resa':value}, {'id':resaId});
						self._updateDb(self.options.database.table_tarif_resa, {'base':value}, {'resaid':resaId});
					});
				}
				vform.etat_des_lieux.click(function()
				{
					if ($(this)[0].checked)
						self._updateDb(self.options.database.table_tarif_resa, {'etat_des_lieux': 1}, {'resaid':resaId});
					else
						self._updateDb(self.options.database.table_tarif_resa, {'etat_des_lieux': 0}, {'resaid':resaId});
					//self._recalcul(resaId);
				});
				vform.remorque.click(function()
				{
					if ($(this)[0].checked)
						self._updateDb(self.options.database.table_tarif_resa, {'remorque': 1}, {'resaid':resaId});
					else
						self._updateDb(self.options.database.table_tarif_resa, {'remorque': 0}, {'resaid':resaId});
					//self._recalcul(resaId);
				});
				vform.oubli.click(function()
				{
					if ($(this)[0].checked)
						self._updateDb(self.options.database.table_tarif_resa, {'oubli': 1}, {'resaid':resaId});
					else
						self._updateDb(self.options.database.table_tarif_resa, {'oubli': 0}, '`resaid`='+resaId);
					//self._recalcul(resaId);
				});
				vform.lavage.change(function()
				{
					self._updateDb(self.options.database.table_tarif_resa, {'lavage': $(this)[0].value}, {'resaid':resaId});
					//self._recalcul(resaId);
				});
				vform.categorie.change(function()
				{
					self._updateDb(self.options.database.table_tarif_resa, {'categorie': $(this)[0].value}, {'resaid':resaId});
					//self._recalcul(resaId);
				});
				vform.notification.click(function()
				{
					if ($(this)[0].checked)
						self._updateDb(self.options.database.table_tarif_resa, {'notification': 1}, {'resaid':resaId});
					else
						self._updateDb(self.options.database.table_tarif_resa, {'notification': 0}, {'resaid':resaId});
					//self._recalcul(resaId);
				});
				vform.smssend.click(function()
				{
					if ($(this)[0].checked)
						self._updateDb(self.options.database.table_tarif_resa, {'smssend': 1}, {'resaid':resaId});
					else
						self._updateDb(self.options.database.table_tarif_resa, {'smssend': 0}, {'resaid':resaId});
					//self._recalcul(resaId);
				});

			},

			_getTarifDetail : function(resaId)
			{
				self = this;
				var ret;
				$.ajax({url: self.options.actions.getResaTarifAction+'&id='+resaId,
					dataType:"json", type:'POST', async:false, success: function(data) { ret = data; }});
				return ret;
			},

			_getService : function()
			{
				var ret;
				$.ajax({url: self.options.actions.serviceResaTarif,dataType:"json", type:'POST', async:false, success: function(data) { ret = data; }});
				return ret;
			},

			_recalcul : function(resaId)
			{
				var self = this;
				var tarif = this._getTarifDetail(resaId);
				var prix_resa = $('#validate-form').find('input[name^=prix_resa]');
				self._updateDb(self.options.database.table_reservation, {'prix_resa': tarif.tarif}, {'id':resaId});
				prix_resa.val(tarif.tarif);
			},

			_updateDb : function(table, data, where)
			{
				var self = this;
				if ( self.options.actions.updateResaDBAction == undefined )
				{
					self._showError(self.options.messages.configError);
					return;
				}
				var url = self.options.actions.updateResaDBAction+'&table='+table;
				$.each(data, function(key, val)
					{
						url +='&data['+key+']='+val;
					});
				$.each(where, function(key, val)
					{
						url += '&where['+key+']=' + val;
					});

				$.ajax({'url': url,'dataType':"json", 'type':'POST', 'async':false, success:
					function(data)
						{
							if (data.Result != 'OK')
								self._showError(data.Message);
						}, error: function(data) {
							self._showError(self.options.messages.serverCommunicationError);
							}});
			},

			_showValidForm: function($tableRow)
			{
				var self = this;

				var record = $tableRow.data('record');
				var resaId = record['id'];
				record['resaid'] = resaId;
				var tarif = self._getTarifDetail(resaId);
				record['etat_des_lieux'] = tarif.etat_des_lieux;
				record['remorque'] = tarif.remorque;
				record['oubli'] = tarif.oubli;
				record['lavage'] = tarif.lavage;
				record['categorie'] = tarif.categorie;
				record['notification'] = tarif.notification;
				record['smssend'] = tarif.smssend;
				if ( record['prix_resa'] === undefined || !record['prix_resa'] ||  record['prix_resa'] == 0 || record['prix_resa'] == "0")
					record['prix_resa'] = tarif.tarif;
				var save_fields = this.options.fields;
				this.options.fields = {
						id: {
							type: 'hidden'
						},
						resaid: {
							title: 'Identifiant',
							type: 'hidden'
						},
						email: {
							title: 'Email',
							type: 'hidden'
						},
						status: {
							title: 'Status',
							type: 'hidden'
						},
						paiement: {
							title : 'Paiement',
							type: 'hidden'
						},
						nom: {
							title: 'Nom',
							inputClass: 'validate[required]'
						},
						mobile: {
							title: 'Mobile',
							inputClass: 'validate[required]'
						},
						modele: {
							title: 'Modèle',
							inputClass: 'validate[required]'
						},
						codepromo: {
							title: 'Code Promo',
							list: false,
							edit: true
						},
						navette: {
							title: 'Arrivée à Parkineo',
							inputClass: 'validate[required]',
							type: 'date',
							displayFormat: 'dd/mm/yy',
							params: {
								hourMin: 3,
								stepMinute: 5,
								onClose: function (navette)
								{
									navette = convertDateToMySQL(navette);
									self._updateDb(self.options.database.table_reservation, {'navette': navette}, {'id':resaId});
								}
							}
						},
						date_retour: {
							title: 'Date de retour',
							type: 'date',
							displayFormat: 'dd/mm/yy',
							params: {
								hourMin: 3,
								stepMinute: 5,
								onClose: function(date_retour)
								{
									date_retour = convertDateToMySQL(date_retour);
									self._updateDb(self.options.database.table_reservation,{'date_retour':date_retour}, {'id':resaId});
									//self._recalcul(resaId);
								}
							}
						},
						nbr_aller: {
							title: '# pers. Aller',
							inputClass: 'validate[required]',
						},
						nbr_retour: {
							title: '# pres. Retour',
							inputClass: 'validate[required]',
						},
						prix_resa: {
							title: 'Tarif'
						},
						etat_des_lieux: {
							title: 'Etat des lieux',
							type: 'checkbox',
							values: {0:'non', 1:'oui'},
							defaultvalue: 0
						},
						remorque: {
							title: 'Remorque',
							type: 'checkbox',
							values: {0:'non', 1:'oui'},
						defaultvalue: 0
						},
						oubli: {
							title: 'Oubli dans véhicule',
							type: 'checkbox',
							values: {0:'non', 1:'oui'},
							defaultvalue: 0
						},
						lavage: {
							title: 'Lavage',
							type: 'radiobutton',
							options:
							{
								0:'Non', 'ext':'Ext', 'int':'Int', 'cplt':'Cplt'
							},
						},
						categorie: {
							title: 'Catégorie',
							type: 'radiobutton',
							options:
							{
								'A':'A', 'B':'B', 'C':'C'
							},
						},
						notification: {
						inputClass: '',
							title: 'Email envoyé',
							type: 'checkbox',
							values: {'0':'non', '1':'oui'},
							defaultvalue: 0
						},
						smssend: {
						inputClass: '',
                        title: 'SMS envoyé',
                        type: 'checkbox',
                        values: {'0':'non', '1':'oui'},
                        defaultvalue: 0
                    }
				};

				//Create valid form
				var $validForm = $('<form id="validate-form" class="jtable-dialog-form jtable-edit-form"  action="'+self.options.actions.validAction+'" method="POST"></form>');
				$.each(this.options.fields, function(fieldName, field)
				{
					var fieldValue = record[fieldName];
					if ( field.type == 'hidden')
					{
						$validForm.append(self._createInputForHidden(fieldName, fieldValue));
						return;
					}
					var $fieldContainer = $('<div class="jtable-input-field-container"></div>').appendTo($validForm);
					$fieldContainer.append(self._createInputLabelForRecordField(fieldName));
					var currentValue = self._getValueForRecordField(record, fieldName);
					$fieldContainer.append(
						self._createInputForRecordField({
							fieldName: fieldName,
							value: currentValue,
							record: record,
							formType: 'valid',
							form: $validForm
						}));
				});

				self._makeCascadeDropDowns($validForm, record, 'valid');

				//Open dialog
				self._$validingRow = $tableRow;
				self._$validDiv.append($validForm).dialog('open');
				self._trigger("formCreated", null, { 'form': $validForm, 'formType': 'valid', 'record': record, 'row': $tableRow, 'instance': self });
				self._formValidationEvent(tarif, resaId);
				this.options.fields = save_fields;
			},
			_saveValidForm: function ($validForm, $validButton)
			{
					var self = this;
					self._submitFormUsingAjax(
						$validForm.attr('action'),
						$validForm.serialize(),
						function (data)
						{
							//Check for errors
							if (data.Result != 'OK')
							{
								self._showError(data.Message);
								self._setEnabledOfDialogButton($validButton, true, self.options.messages.valid);
								return;
							}
							var record = self._$validingRow.data('record');
							self._updateRowTexts(self._$validingRow);
							self._updateStatus(record);
							self._$validDiv.dialog("close");
						},
						function ()
						{
							self._showError(self.options.messages.serverCommunicationError);
							self._setEnabledOfDialogButton($validButton, true, self.options.messages.valid);
						});
			},

			_updateStatus: function(record)
			{
				var self = this;
				if ($('#pkmgmt-filtering-select').val() == 0)
				{
					self._updateDb(self.options.database.table_reservation,{'status':1},{'id': record['id']});
					self._removeRowsFromTableWithAnimation(self._$validingRow);
				}
			}
		});
		}

		/************************************************************************
		* STATUS  extension for jTable											  *
		*************************************************************************/
		function jtable_status()
		{
			//Reference to base object members
			var base = {
				_create: $.hik.jtable.prototype._create,
				_addColumnsToHeaderRow: $.hik.jtable.prototype._addColumnsToHeaderRow,
				_addCellsToRowUsingRecord: $.hik.jtable.prototype._addCellsToRowUsingRecord
			};

			//extension members
			$.extend(true, $.hik.jtable.prototype, {

				/************************************************************************
				* DEFAULT OPTIONS / EVENTS											  *
				*************************************************************************/
				options: {

					//Localization
					messages: {
						statusRecord: 'Status'
					}
				},

				/************************************************************************
				* PRIVATE FIELDS														*
				*************************************************************************/

				_$statusDiv: null, //Reference to the editing dialog div (jQuery object)
				_$statusingRow: null, //Reference to currently editing row (jQuery object)

				/************************************************************************
				* CONSTRUCTOR AND INITIALIZATION METHODS								*
				*************************************************************************/

				/* Overrides base method to do editing-specific constructions.
				*************************************************************************/
				_create: function () {
					base._create.apply(this, arguments);
					this._createStatusDialogDiv();
				},

				_createStatusDialogDiv: function () {
					var self = this;

					//Create a div for dialog and add to container element
					self._$statusDiv = $('<div></div>')
						.appendTo(self._$mainContainer);

					//Prepare dialog
					self._$statusDiv.dialog({
						autoOpen: false,
						show: self.options.dialogShowEffect,
						hide: self.options.dialogHideEffect,
						width: 'auto',
						minWidth: '150',
						modal: true,
						title: 'Entrée',
						close: function () { $('.ui-widget-overlay').remove(); $('#status-form').remove();},
						buttons:
							[{  //cancel button
								text: self.options.messages.cancel,
								click: function () {
									self._$statusDiv.dialog('close');
								}
							}, { //sent button
								id: 'statusDialogButton',
								text: 'Arrivé !',
								click: function ()
								{
									var $statusForm = self._$statusDiv.find('form:first');
									var $statusButton = $('#statusDialogButton');
									self._saveStatusForm($statusForm, $statusButton);
								}
							}]
					});
				},


				_saveStatusForm: function ($statusForm, $statusButton)
				{
					var self = this;
					self._submitFormUsingAjax(
						self.options.actions.statusAction,
						$statusForm.serialize(),
						function (data)
						{
							//Check for errors
							if (data.Result != 'OK')
							{
								self._showError(data.Message);
								self._setEnabledOfDialogButton($statusButton, true, self.options.messages.status);
								return;
							}
							self._removeRowsFromTableWithAnimation(self._$statusingRow);
							self._$statusDiv.dialog("close");
						},
						function ()
						{
							self._showError(self.options.messages.serverCommunicationError);
							self._setEnabledOfDialogButton($statusButton, true, self.options.messages.status);
						});
				},

				/************************************************************************
				* PUBLIC METHODS														*
				*************************************************************************/

				/************************************************************************
				* OVERRIDED METHODS													 	*
				*************************************************************************/

				/* Overrides base method to add a 'facting column cell' to header row.
				*************************************************************************/
				_addColumnsToHeaderRow: function ($tr) {
					base._addColumnsToHeaderRow.apply(this, arguments);
							 if (this.options.actions.statusAction != undefined) {
						$tr.append(this._createEmptyCommandHeader());
					}
				},

				/* Overrides base method to add a 'fact command cell' to a row.
				*************************************************************************/
				_addCellsToRowUsingRecord: function ($row) {
					var self = this;
					base._addCellsToRowUsingRecord.apply(this, arguments);

					if (self.options.actions.statusAction != undefined) {
						var $span = $('<span></span>').html(self.options.messages.statusRecord);
						var $button = $('<button title="' + self.options.messages.statusRecord + '"></button>')
							.addClass('jtable-command-button jtable-status-command-button')
							.append($span)
							.click(function (e) {
								e.preventDefault();
								e.stopPropagation();
								self._showStatusForm($row);
							});
						$('<td></td>')
							.addClass('jtable-command-column')
							.append($button)
							.appendTo($row);
					}
				},

				_showStatusForm: function($tableRow)
				{
					var self = this;
					var record = $tableRow.data('record');

					var $statusForm = $('<form id="status-form" ></form>');
					$statusForm.append(self._createInputForHidden('id', record['id']));
					//Open dialog
					self._$statusingRow = $tableRow;
					self._$statusDiv.attr('id', record['id']);
					self._$statusDiv.append($statusForm).dialog('open');

				}
			});
		}


		/************************************************************************
		* FACTURE  extension for jTable											  *
		*************************************************************************/
		function jtable_facture()
		{
			//Reference to base object members
			var base = {
				_create: $.hik.jtable.prototype._create,
				_addColumnsToHeaderRow: $.hik.jtable.prototype._addColumnsToHeaderRow,
				_addCellsToRowUsingRecord: $.hik.jtable.prototype._addCellsToRowUsingRecord
			};

			//extension members
			$.extend(true, $.hik.jtable.prototype, {

				/************************************************************************
				* DEFAULT OPTIONS / EVENTS											  *
				*************************************************************************/
				options: {

					//Localization
					messages: {
						factRecord: 'Facture',
						Print: 'Imprimer',
						Send: 'Envoyer'
					}
				},

				/************************************************************************
				* PRIVATE FIELDS														*
				*************************************************************************/

				_$factDiv: null, //Reference to the editing dialog div (jQuery object)
				_$factingRow: null, //Reference to currently editing row (jQuery object)

				/************************************************************************
				* CONSTRUCTOR AND INITIALIZATION METHODS								*
				*************************************************************************/

				/* Overrides base method to do editing-specific constructions.
				*************************************************************************/
				_create: function () {
					base._create.apply(this, arguments);
					this._createFactDialogDiv();
				},

				_createFactDialogDiv: function () {
					var self = this;

					//Create a div for dialog and add to container element
					self._$factDiv = $('<div></div>')
						.appendTo(self._$mainContainer);

					//Prepare dialog
					self._$factDiv.dialog({
						id: 'dialog-fact',
						autoOpen: false,
						show: self.options.dialogShowEffect,
						hide: self.options.dialogHideEffect,
						width: 'auto',
						minWidth: '150',
						modal: true,
						title: 'Facture',
						close: function () {

						var paiement = $('#fact-form-paiement').val();
						if ( in_array(paiement, ajax_object.paiement ))
							self._removeRowsFromTableWithAnimation(self._$factingRow);
						$('.ui-widget-overlay').remove(); $('#fact-form').remove();
						},
						buttons:
							[{  //cancel button
								text: self.options.messages.cancel,
								click: function () {
									self._$factDiv.dialog('close');
								}
							}, { //print button
								id: 'printDialogButton',
								text: self.options.messages.Print,
								click: function ()
								{
									var url = self.options.actions.invoiceAction+"&out=print&cx=fc&ids=" + this.id;
									window.open(url, 'imprimer', 	'status=no,location=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=700,height=480,directories=no', false);
									self._$factDiv.dialog('close');
								}
							}, { //sent button
								id: 'sendDialogButton',
								text: 'Envoyer',
								click: function ()
								{
									var urll = self.options.actions.invoiceAction+"&out=send&ids=" + this.id;
									var ret;
									$.ajax({'url': urll,dataType:"json", type:'POST', async:false, success: function(data) {  ret = data; }});
									if (ret)
										$.dW.alert("Facture envoyé");
									else
										$.dW.alert("Erreur d'envoi");
									self._$factDiv.dialog('close');
								}
							}]
					});
				},

				/************************************************************************
				* PUBLIC METHODS														*
				*************************************************************************/

				/************************************************************************
				* OVERRIDED METHODS													 	*
				*************************************************************************/

				/* Overrides base method to add a 'facting column cell' to header row.
				*************************************************************************/
				_addColumnsToHeaderRow: function ($tr) {
					base._addColumnsToHeaderRow.apply(this, arguments);
							 if (this.options.actions.invoiceAction != undefined) {
						$tr.append(this._createEmptyCommandHeader());
					}
				},

				/* Overrides base method to add a 'fact command cell' to a row.
				*************************************************************************/
				_addCellsToRowUsingRecord: function ($row) {
					var self = this;
					base._addCellsToRowUsingRecord.apply(this, arguments);

					if (self.options.actions.invoiceAction != undefined) {
						var $span = $('<span></span>').html(self.options.messages.factRecord);
						var $button = $('<button title="' + self.options.messages.factRecord + '"></button>')
							.addClass('jtable-command-button jtable-fact-command-button')
							.append($span)
							.click(function (e) {
								e.preventDefault();
								e.stopPropagation();
								self._showFactForm($row);
							});
						$('<td></td>')
							.addClass('jtable-command-column')
							.append($button)
							.appendTo($row);
					}
				},
				_updateDb : function(table, data, where)
				{
					var self = this;
					if ( self.options.actions.updateResaDBAction == undefined )
					{
						self._showError(self.options.messages.configError);
						return;
					}
					var url = self.options.actions.updateResaDBAction+'&table='+table;
					$.each(data, function(key, val)
						{
							url +='&data['+key+']='+val;
						});
					$.each(where, function(key, val)
						{
							url += '&where['+key+']=' + val;
						});

					$.ajax({'url': url,'dataType':"json", 'type':'POST', 'async':false,
					success: function(data) {
							if (data.Result != 'OK')
								self._showError(data.Message);
								return false;
						},
					error: function(data) {
							self._showError(self.options.messages.serverCommunicationError);
							return false;
						}
					});
					return true;
				},
				_showFactForm: function($tableRow)
				{
					var self = this;
					var record = $tableRow.data('record');
					var $printDialogButton = $('#printDialogButton');
					var $sendDialogButton = $('#sendDialogButton');

					var $factForm = $('<form id="fact-form"></form>')
						.append($('<input type="hidden" id="fact-form-paiement" name="paiement" value="'+record['paiement']+'" />'));
					if ( ! in_array(record['paiement'], ajax_object.paiement ))
					{
						self._setEnabledOfDialogButton($sendDialogButton, false, self.options.messages.Send);
						self._setEnabledOfDialogButton($printDialogButton, false, self.options.messages.Print);
						var $divFactForm = $('<div />');
						var paiement = ajax_object.paiement;
						for (key in paiement)
						{
							var value = paiement[key];
							$divFactForm.append($('<button />')
								.addClass("ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only")
								.attr('name',value)
								.attr('value', value)
								.append($('<span />').append(value).addClass("ui-button-text"))
								.click(function(e){
										if (self._updateDb(self.options.database.table_reservation,{'paiement':$(this).val(), 'status':3},{'id':record['id']}))
											{
												$('#fact-form-paiement').val($(this).val());
												record['paiement'] = $(this).val();
												self._updateRecordValuesFromForm(record, $('#fact-form'));
												$(this).parent().remove();
												self._setEnabledOfDialogButton($sendDialogButton, true, self.options.messages.Send);
												self._setEnabledOfDialogButton($printDialogButton, true, self.options.messages.Print);
											}
									})
							)
						}
						$factForm.append($divFactForm);

					}
					//Open dialog
					self._$factingRow = $tableRow;
					self._$factDiv.attr('id', record['id']);
					self._$factDiv.append($factForm).dialog('open');
				}
			});
		}

		/************************************************************************
		* BON DE SORTIE  extension for jTable									  *
		*************************************************************************/
		function jtable_bon_de_sortie()
		{


			//Reference to base object members
			var base = {
				_create: $.hik.jtable.prototype._create,
				_addColumnsToHeaderRow: $.hik.jtable.prototype._addColumnsToHeaderRow,
				_addCellsToRowUsingRecord: $.hik.jtable.prototype._addCellsToRowUsingRecord
			};

			//extension members
			$.extend(true, $.hik.jtable.prototype, {

				/************************************************************************
				* DEFAULT OPTIONS / EVENTS											  *
				*************************************************************************/
				options: {

					//Localization
					messages: {
						bsRecord: 'Bon de sortie'
					}
				},

				/************************************************************************
				* PRIVATE FIELDS														*
				*************************************************************************/

				_$bsDiv: null, //Reference to the editing dialog div (jQuery object)
				_$bsRow: null, //Reference to currently editing row (jQuery object)

				/************************************************************************
				* CONSTRUCTOR AND INITIALIZATION METHODS								*
				*************************************************************************/

				/* Overrides base method to do editing-specific constructions.
				*************************************************************************/
				_create: function () {
					base._create.apply(this, arguments);
				},

				/************************************************************************
				* PUNLIC METHODS														*
				*************************************************************************/

				/************************************************************************
				* OVERRIDED METHODS													 	*
				*************************************************************************/

				/* Overrides base method to add a 'bs column cell' to header row.
				*************************************************************************/
				_addColumnsToHeaderRow: function ($tr) {
					base._addColumnsToHeaderRow.apply(this, arguments);
							 if (this.options.actions.exitSplitAction != undefined) {
						$tr.append(this._createEmptyCommandHeader());
					}
				},

				/* Overrides base method to add a 'bs command cell' to a row.
				*************************************************************************/
				_addCellsToRowUsingRecord: function ($row) {
					var self = this;
					base._addCellsToRowUsingRecord.apply(this, arguments);

					if (self.options.actions.exitSplitAction != undefined) {
						var $span = $('<span></span>').html(self.options.messages.bsRecord);
						var $button = $('<button title="' + self.options.messages.bsRecord + '"></button>')
							.addClass('jtable-command-button jtable-bs-command-button')
							.append($span)
							.click(function (e) {
								e.preventDefault();
								e.stopPropagation();
								self._openBs($row);
							});
						$('<td></td>')
							.addClass('jtable-command-column')
							.append($button)
							.appendTo($row);
					}
				},

				_openBs: function($tableRow)
				{
					var self = this;
					var record = $tableRow.data('record');
					var url = self.options.actions.exitSplitAction+"&ids=" + record['id'];
					window.open(url, 'imprimer', 'status=no,location=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=700,height=480,directories=no,', false);
				}
			});
		}

		/************************************************************************
		* BON DE PRISE EN CHARGE extension for jTable									  *
		*************************************************************************/
		function jtable_bon_de_prise_en_charge()
		{
			//Reference to base object members
			var base = {
				_create: $.hik.jtable.prototype._create,
				_addColumnsToHeaderRow: $.hik.jtable.prototype._addColumnsToHeaderRow,
				_addCellsToRowUsingRecord: $.hik.jtable.prototype._addCellsToRowUsingRecord
			};

			//extension members
			$.extend(true, $.hik.jtable.prototype, {

				/************************************************************************
				* DEFAULT OPTIONS / EVENTS											  *
				*************************************************************************/
				options: {

					//Localization
					messages: {
						pcRecord: 'Bon de prise en charge'
					}
				},

				/************************************************************************
				* PRIVATE FIELDS														*
				*************************************************************************/

				_$pcDiv: null, //Reference to the editing dialog div (jQuery object)
				_$pcRow: null, //Reference to currently editing row (jQuery object)

				/************************************************************************
				* CONSTRUCTOR AND INITIALIZATION METHODS								*
				*************************************************************************/

				/* Overrides base method to do editing-specific constructions.
				*************************************************************************/
				_create: function () {
					base._create.apply(this, arguments);
				},

				/************************************************************************
				* PUNLIC METHODS														*
				*************************************************************************/

				/************************************************************************
				* OVERRIDED METHODS													 	*
				*************************************************************************/

				/* Overrides base method to add a 'bs column cell' to header row.
				*************************************************************************/
				_addColumnsToHeaderRow: function ($tr) {
					base._addColumnsToHeaderRow.apply(this, arguments);
							 if (this.options.actions.deliveryAction != undefined) {
						$tr.append(this._createEmptyCommandHeader());

					}
				},

				/* Overrides base method to add a 'pc command cell' to a row.
				*************************************************************************/
				_addCellsToRowUsingRecord: function ($row) {
					var self = this;
					base._addCellsToRowUsingRecord.apply(this, arguments);

					if (self.options.actions.deliveryAction != undefined) {
						var $span = $('<span></span>').html(self.options.messages.pcRecord);
						var $button = $('<button title="' + self.options.messages.pcRecord + '"></button>')
							.addClass('jtable-command-button jtable-pc-command-button')
							.append($span)
							.click(function (e) {
								e.preventDefault();
								e.stopPropagation();
								self._openPc($row);
							});
						$('<td></td>')
							.addClass('jtable-command-column')
							.append($button)
							.appendTo($row);
					}
				},

				_openPc: function($tableRow)
				{
					var self = this;
					var record = $tableRow.data('record');
					var url = self.options.actions.deliveryAction+"&ids=" + record['id'];
					window.open(url, 'imprimer', 'status=no,location=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=700,height=480,directories=no',false);
				}
			});
		}


		/************************************************************************
		* EDIT RECORD extension for jTable                                      *
		*************************************************************************/
		function jtable_edit()
		{


			//Reference to base object members
			var base = {
				_create: $.hik.jtable.prototype._create,
				_addColumnsToHeaderRow: $.hik.jtable.prototype._addColumnsToHeaderRow,
				_addCellsToRowUsingRecord: $.hik.jtable.prototype._addCellsToRowUsingRecord
			};

			//extension members
			$.extend(true, $.hik.jtable.prototype, {

				/************************************************************************
				* DEFAULT OPTIONS / EVENTS                                              *
				*************************************************************************/
				options: {

					//Events
					recordUpdated: function (event, data) { },
					rowUpdated: function (event, data) { },

					//Localization
					messages: {
						editRecord: 'Edit Record'
					}
				},

				/************************************************************************
				* PRIVATE FIELDS                                                        *
				*************************************************************************/

				_$editDiv: null, //Reference to the editing dialog div (jQuery object)
				_$editingRow: null, //Reference to currently editing row (jQuery object)

				/************************************************************************
				* CONSTRUCTOR AND INITIALIZATION METHODS                                *
				*************************************************************************/

				/* Overrides base method to do editing-specific constructions.
				*************************************************************************/
				_create: function () {
					base._create.apply(this, arguments);
					this._createEditDialogDiv();
				},

				/* Creates and prepares edit dialog div
				*************************************************************************/
				_createEditDialogDiv: function () {
					var self = this;

					//Create a div for dialog and add to container element
					self._$editDiv = $('<div></div>')
						.appendTo(self._$mainContainer);

					//Prepare dialog
					self._$editDiv.dialog({
						autoOpen: false,
						show: self.options.dialogShowEffect,
						hide: self.options.dialogHideEffect,
						width: 'auto',
						minWidth: '300',
						height: '600',
						modal: true,
						title: self.options.messages.editRecord,
						buttons:
								[{  //cancel button
									text: self.options.messages.cancel,
									click: function () {
										self._$editDiv.dialog('close');
									}
								}, { //save button
									id: 'EditDialogSaveButton',
									text: self.options.messages.save,
									click: function () {

										//row maybe removed by another source, if so, do nothing
										if (self._$editingRow.hasClass('jtable-row-removed')) {
											self._$editDiv.dialog('close');
											return;
										}

										var $saveButton = self._$editDiv.find('#EditDialogSaveButton');
										var $editForm = self._$editDiv.find('form');
										if (self._trigger("formSubmitting", null, { form: $editForm, formType: 'edit', row: self._$editingRow }) != false) {
											self._setEnabledOfDialogButton($saveButton, false, self.options.messages.saving);
											self._saveEditForm($editForm, $saveButton);
										}
									}
								}],
						close: function () {
							var $editForm = self._$editDiv.find('form:first');
							var $saveButton = $('#EditDialogSaveButton');
							self._trigger("formClosed", null, { form: $editForm, formType: 'edit', row: self._$editingRow });
							self._setEnabledOfDialogButton($saveButton, true, self.options.messages.save);
							$('.ui-widget-overlay').remove();
							$('#jtable-edit-form').remove();
						}
					});
				},

				/************************************************************************
				* PUBLIC METHODS                                                        *
				*************************************************************************/

				/* Updates a record on the table (optionally on the server also)
				*************************************************************************/
				updateRecord: function (options) {
					var self = this;
					options = $.extend({
						clientOnly: false,
						animationsEnabled: self.options.animationsEnabled,
						url: self.options.actions.updateAction,
						success: function () { },
						error: function () { }
					}, options);

					if (!options.record) {
						self._logWarn('options parameter in updateRecord method must contain a record property.');
						return;
					}

					var key = self._getKeyValueOfRecord(options.record);
					if (key == undefined || key == null) {
						self._logWarn('options parameter in updateRecord method must contain a record that contains the key field property.');
						return;
					}

					var $updatingRow = self.getRowByKey(key);
					if ($updatingRow == null) {
						self._logWarn('Can not found any row by key: ' + key);
						return;
					}

					if (options.clientOnly) {
						$.extend($updatingRow.data('record'), options.record);
						self._updateRowTexts($updatingRow);
						self._onRecordUpdated($updatingRow, null);
						if (options.animationsEnabled) {
							self._showUpdateAnimationForRow($updatingRow);
						}

						options.success();
						return;
					}

					self._submitFormUsingAjax(
						options.url,
						$.param(options.record),
						function (data) {
							if (data.Result != 'OK') {
								self._showError(data.Message);
								options.error(data);
								return;
							}

							$.extend($updatingRow.data('record'), options.record);
							self._updateRecordValuesFromServerResponse($updatingRow.data('record'), data);

							self._updateRowTexts($updatingRow);
							self._onRecordUpdated($updatingRow, data);
							if (options.animationsEnabled) {
								self._showUpdateAnimationForRow($updatingRow);
							}

							options.success(data);
						},
						function () {
							self._showError(self.options.messages.serverCommunicationError);
							options.error();
						});
				},

				/************************************************************************
				* OVERRIDED METHODS                                                     *
				*************************************************************************/

				/* Overrides base method to add a 'editing column cell' to header row.
				*************************************************************************/
				_addColumnsToHeaderRow: function ($tr) {
					base._addColumnsToHeaderRow.apply(this, arguments);
					if (this.options.actions.updateAction != undefined) {
						$tr.append(this._createEmptyCommandHeader());
					}
				},

				/* Overrides base method to add a 'edit command cell' to a row.
				*************************************************************************/
				_addCellsToRowUsingRecord: function ($row) {
					var self = this;
					base._addCellsToRowUsingRecord.apply(this, arguments);

					if (self.options.actions.updateAction != undefined) {
						var $span = $('<span></span>').html(self.options.messages.editRecord);
						var $button = $('<button title="' + self.options.messages.editRecord + '"></button>')
							.addClass('jtable-command-button jtable-edit-command-button')
							.append($span)
							.click(function (e) {
								e.preventDefault();
								e.stopPropagation();
								self._showEditForm($row);
							});
						$('<td></td>')
							.addClass('jtable-command-column')
							.append($button)
							.appendTo($row);
					}
				},

				/************************************************************************
				* PRIVATE METHODS                                                       *
				*************************************************************************/

				/* Shows edit form for a row.
				*************************************************************************/
				_showEditForm: function ($tableRow) {
					var self = this;
					var record = $tableRow.data('record');

					//Create edit form
					var $editForm = $('<form id="jtable-edit-form" class="jtable-dialog-form jtable-edit-form" action="' + self.options.actions.updateAction + '" method="POST"></form>');

					//Create input fields
					for (var i = 0; i < self._fieldList.length; i++) {

						var fieldName = self._fieldList[i];
						var field = self.options.fields[fieldName];
						var fieldValue = record[fieldName];

						if (field.key == true) {
							if (field.edit != true) {
								//Create hidden field for key
								$editForm.append(self._createInputForHidden(fieldName, fieldValue));
								continue;
							} else {
								//Create a special hidden field for key (since key is be editable)
								$editForm.append(self._createInputForHidden('jtRecordKey', fieldValue));
							}
						}

						//Do not create element for non-editable fields
						if (field.edit == false) {
							continue;
						}

						//Hidden field
						if (field.type == 'hidden') {
							$editForm.append(self._createInputForHidden(fieldName, fieldValue));
							continue;
						}

						//Create a container div for this input field and add to form
						var $fieldContainer = $('<div class="jtable-input-field-container"></div>').appendTo($editForm);

						//Create a label for input
						$fieldContainer.append(self._createInputLabelForRecordField(fieldName));

						//Create input element with it's current value
						var currentValue = self._getValueForRecordField(record, fieldName);
						$fieldContainer.append(
							self._createInputForRecordField({
								fieldName: fieldName,
								value: currentValue,
								record: record,
								formType: 'edit',
								form: $editForm
							}));
					}

					self._makeCascadeDropDowns($editForm, record, 'edit');

					//Open dialog
					self._$editingRow = $tableRow;
					self._$editDiv.append($editForm).dialog('open');
					self._trigger("formCreated", null, { form: $editForm, formType: 'edit', record: record, row: $tableRow });
				},

				/* Saves editing form to the server and updates the record on the table.
				*************************************************************************/
				_saveEditForm: function ($editForm, $saveButton) {
					var self = this;
					self._submitFormUsingAjax(
						$editForm.attr('action'),
						$editForm.serialize(),
						function (data) {
							//Check for errors
							if (data.Result != 'OK') {
								self._showError(data.Message);
								self._setEnabledOfDialogButton($saveButton, true, self.options.messages.save);
								return;
							}

							var record = self._$editingRow.data('record');

							self._updateRecordValuesFromForm(record, $editForm);
							self._updateRecordValuesFromServerResponse(record, data);
							self._updateRowTexts(self._$editingRow);

							self._$editingRow.attr('data-record-key', self._getKeyValueOfRecord(record));

							self._onRecordUpdated(self._$editingRow, data);

							if (self.options.animationsEnabled) {
								self._showUpdateAnimationForRow(self._$editingRow);
							}

							self._$editDiv.dialog("close");
						},
						function () {
							self._showError(self.options.messages.serverCommunicationError);
							self._setEnabledOfDialogButton($saveButton, true, self.options.messages.save);
						});
				},

				/* This method ensures updating of current record with server response,
				* if server sends a Record object as response to updateAction.
				*************************************************************************/
				_updateRecordValuesFromServerResponse: function (record, serverResponse) {
					if (!serverResponse || !serverResponse.Record) {
						return;
					}

					$.extend(true, record, serverResponse.Record);
				},

				/* Gets text for a field of a record according to it's type.
				*************************************************************************/
				_getValueForRecordField: function (record, fieldName) {
					var field = this.options.fields[fieldName];
					var fieldValue = record[fieldName];
					if (field.type == 'date') {
						return this._getDisplayTextForDateRecordField(field, fieldValue);
					} else if (field.type == 'time')
					{
						return this._getDisplayTextForTimeRecordField(field, fieldValue);
					} else {
						return fieldValue;
					}
				},

				/* Updates cells of a table row's text values from row's record values.
				*************************************************************************/
				_updateRowTexts: function ($tableRow) {
					var record = $tableRow.data('record');
					var $columns = $tableRow.find('td');
					for (var i = 0; i < this._columnList.length; i++) {
						var displayItem = this._getDisplayTextForRecordField(record, this._columnList[i]);
						$columns.eq(this._firstDataColumnOffset + i).html(displayItem || '');
					}

					//this._onRowUpdated($tableRow);
				},

				/* Shows 'updated' animation for a table row.
				*************************************************************************/
				_showUpdateAnimationForRow: function ($tableRow) {
					$tableRow.stop(true, true).addClass('jtable-row-updated', 'slow', '', function () {
						$tableRow.removeClass('jtable-row-updated', 5000);
					});
				},

				/************************************************************************
				* EVENT RAISING METHODS                                                 *
				*************************************************************************/

				_onRowUpdated: function ($row) {
					this._trigger("rowUpdated", null, { row: $row, record: $row.data('record') });
				},

				_onRecordUpdated: function ($row, data) {
					this._trigger("recordUpdated", null, { record: $row.data('record'), row: $row, serverResponse: data });
				}

			});

		}

		/************************************************************************
		* DELETION extension for jTable                                         *
		*************************************************************************/
		function jtable_deletion()
		{


			//Reference to base object members
			var base = {
				_create: $.hik.jtable.prototype._create,
				_addColumnsToHeaderRow: $.hik.jtable.prototype._addColumnsToHeaderRow,
				_addCellsToRowUsingRecord: $.hik.jtable.prototype._addCellsToRowUsingRecord
			};

			//extension members
			$.extend(true, $.hik.jtable.prototype, {

				/************************************************************************
				* DEFAULT OPTIONS / EVENTS                                              *
				*************************************************************************/
				options: {

					//Options
					deleteConfirmation: true,

					//Events
					recordDeleted: function (event, data) { },

					//Localization
					messages: {
						deleteConfirmation: 'This record will be deleted. Are you sure?',
						deleteText: 'Delete',
						deleting: 'Deleting',
						canNotDeletedRecords: 'Can not delete {0} of {1} records!',
						deleteProggress: 'Deleting {0} of {1} records, processing...'
					}
				},

				/************************************************************************
				* PRIVATE FIELDS                                                        *
				*************************************************************************/

				_$deleteRecordDiv: null, //Reference to the adding new record dialog div (jQuery object)
				_$deletingRow: null, //Reference to currently deleting row (jQuery object)

				/************************************************************************
				* CONSTRUCTOR                                                           *
				*************************************************************************/

				/* Overrides base method to do deletion-specific constructions.
				*************************************************************************/
				_create: function () {
					base._create.apply(this, arguments);
					this._createDeleteDialogDiv();
				},

				/* Creates and prepares delete record confirmation dialog div.
				*************************************************************************/
				_createDeleteDialogDiv: function () {
					var self = this;

					//Create div element for delete confirmation dialog
					self._$deleteRecordDiv = $('<div><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><span class="jtable-delete-confirm-message"></span></p></div>').appendTo(self._$mainContainer);

					//Prepare dialog
					self._$deleteRecordDiv.dialog({
						autoOpen: false,
						show: self.options.dialogShowEffect,
						hide: self.options.dialogHideEffect,
						modal: true,
						title: self.options.messages.areYouSure,
						buttons:
								[{  //cancel button
									text: self.options.messages.cancel,
									click: function () {
										self._$deleteRecordDiv.dialog("close");
									}
								}, {//delete button
									id: 'DeleteDialogButton',
									text: self.options.messages.deleteText,
									click: function () {

										//row maybe removed by another source, if so, do nothing
										if (self._$deletingRow.hasClass('jtable-row-removed')) {
											self._$deleteRecordDiv.dialog('close');
											return;
										}

										var $deleteButton = $('#DeleteDialogButton');
										self._setEnabledOfDialogButton($deleteButton, false, self.options.messages.deleting);
										self._deleteRecordFromServer(
											self._$deletingRow,
											function () {
												self._removeRowsFromTableWithAnimation(self._$deletingRow);
												self._$deleteRecordDiv.dialog('close');
											},
											function (message) { //error
												self._showError(message);
												self._setEnabledOfDialogButton($deleteButton, true, self.options.messages.deleteText);
											}
										);
									}
								}],
						close: function () {
							$('.ui-widget-overlay').remove();
							var $deleteButton = $('#DeleteDialogButton');
							self._setEnabledOfDialogButton($deleteButton, true, self.options.messages.deleteText);
						}
					});
				},

				/************************************************************************
				* PUBLIC METHODS                                                        *
				*************************************************************************/

				/* This method is used to delete one or more rows from server and the table.
				*************************************************************************/
				deleteRows: function ($rows) {
					var self = this;

					if ($rows.length <= 0) {
						self._logWarn('No rows specified to jTable deleteRows method.');
						return;
					}

					if (self._isBusy()) {
						self._logWarn('Can not delete rows since jTable is busy!');
						return;
					}

					//Deleting just one row
					if ($rows.length == 1) {
						self._deleteRecordFromServer(
							$rows,
							function () { //success
								self._removeRowsFromTableWithAnimation($rows);
							},
							function (message) { //error
								self._showError(message);
							}
						);

						return;
					}

					//Deleting multiple rows
					self._showBusy(self._formatString(self.options.messages.deleteProggress, 0, $rows.length));

					//This method checks if deleting of all records is completed
					var completedCount = 0;
					var isCompleted = function () {
						return (completedCount >= $rows.length);
					};

					//This method is called when deleting of all records completed
					var completed = function () {
						var $deletedRows = $rows.filter('.jtable-row-ready-to-remove');
						if ($deletedRows.length < $rows.length) {
							self._showError(self._formatString(self.options.messages.canNotDeletedRecords, $rows.length - $deletedRows.length, $rows.length));
						}

						if ($deletedRows.length > 0) {
							self._removeRowsFromTableWithAnimation($deletedRows);
						}

						self._hideBusy();
					};

					//Delete all rows
					var deletedCount = 0;
					$rows.each(function () {
						var $row = $(this);
						self._deleteRecordFromServer(
							$row,
							function () { //success
								++deletedCount; ++completedCount;
								$row.addClass('jtable-row-ready-to-remove');
								self._showBusy(self._formatString(self.options.messages.deleteProggress, deletedCount, $rows.length));
								if (isCompleted()) {
									completed();
								}
							},
							function () { //error
								++completedCount;
								if (isCompleted()) {
									completed();
								}
							}
						);
					});
				},

				/* Deletes a record from the table (optionally from the server also).
				*************************************************************************/
				deleteRecord: function (options) {
					var self = this;
					options = $.extend({
						clientOnly: false,
						animationsEnabled: self.options.animationsEnabled,
						url: self.options.actions.deleteAction,
						success: function () { },
						error: function () { }
					}, options);

					if (options.key == undefined) {
						self._logWarn('options parameter in deleteRecord method must contain a key property.');
						return;
					}

					var $deletingRow = self.getRowByKey(options.key);
					if ($deletingRow == null) {
						self._logWarn('Can not found any row by key: ' + options.key);
						return;
					}

					if (options.clientOnly) {
						self._removeRowsFromTableWithAnimation($deletingRow, options.animationsEnabled);
						options.success();
						return;
					}

					self._deleteRecordFromServer(
							$deletingRow,
							function (data) { //success
								self._removeRowsFromTableWithAnimation($deletingRow, options.animationsEnabled);
								options.success(data);
							},
							function (message) { //error
								self._showError(message);
								options.error(message);
							},
							options.url
						);
				},

				/************************************************************************
				* OVERRIDED METHODS                                                     *
				*************************************************************************/

				/* Overrides base method to add a 'deletion column cell' to header row.
				*************************************************************************/
				_addColumnsToHeaderRow: function ($tr) {
					base._addColumnsToHeaderRow.apply(this, arguments);
					if (this.options.actions.deleteAction != undefined) {
						$tr.append(this._createEmptyCommandHeader());
					}
				},

				/* Overrides base method to add a 'delete command cell' to a row.
				*************************************************************************/
				_addCellsToRowUsingRecord: function ($row) {
					base._addCellsToRowUsingRecord.apply(this, arguments);

					var self = this;
					if (self.options.actions.deleteAction != undefined) {
						var $span = $('<span></span>').html(self.options.messages.deleteText);
						var $button = $('<button title="' + self.options.messages.deleteText + '"></button>')
							.addClass('jtable-command-button jtable-delete-command-button')
							.append($span)
							.click(function (e) {
								e.preventDefault();
								e.stopPropagation();
								self._deleteButtonClickedForRow($row);
							});
						$('<td></td>')
							.addClass('jtable-command-column')
							.append($button)
							.appendTo($row);
					}
				},

				/************************************************************************
				* PRIVATE METHODS                                                       *
				*************************************************************************/

				/* This method is called when user clicks delete button on a row.
				*************************************************************************/
				_deleteButtonClickedForRow: function ($row) {
					var self = this;

					var deleteConfirm;
					var deleteConfirmMessage = self.options.messages.deleteConfirmation;

					//If options.deleteConfirmation is function then call it
					if ($.isFunction(self.options.deleteConfirmation)) {
						var data = { row: $row, record: $row.data('record'), deleteConfirm: true, deleteConfirmMessage: deleteConfirmMessage, cancel: false, cancelMessage: null };
						self.options.deleteConfirmation(data);

						//If delete progress is cancelled
						if (data.cancel) {

							//If a canlellation reason is specified
							if (data.cancelMessage) {
								self._showError(data.cancelMessage); //TODO: show warning/stop message instead of error (also show warning/error ui icon)!
							}

							return;
						}

						deleteConfirmMessage = data.deleteConfirmMessage;
						deleteConfirm = data.deleteConfirm;
					} else {
						deleteConfirm = self.options.deleteConfirmation;
					}

					if (deleteConfirm != false) {
						//Confirmation
						self._$deleteRecordDiv.find('.jtable-delete-confirm-message').html(deleteConfirmMessage);
						self._showDeleteDialog($row);
					} else {
						//No confirmation
						self._deleteRecordFromServer(
							$row,
							function () { //success
								self._removeRowsFromTableWithAnimation($row);
							},
							function (message) { //error
								self._showError(message);
							}
						);
					}
				},

				/* Shows delete comfirmation dialog.
				*************************************************************************/
				_showDeleteDialog: function ($row) {
					this._$deletingRow = $row;
					this._$deleteRecordDiv.dialog('open');
				},

				/* Performs an ajax call to server to delete record
				*  and removesd row of record from table if ajax call success.
				*************************************************************************/
				_deleteRecordFromServer: function ($row, success, error, url) {
					var self = this;

					//Check if it is already being deleted right now
					if ($row.data('deleting') == true) {
						return;
					}

					$row.data('deleting', true);

					var postData = {};
					postData[self._keyField] = self._getKeyValueOfRecord($row.data('record'));
					postData['tarif'] = $('#pkmgmt-filtering-select').val();
					this._ajax({
						url: (url || self.options.actions.deleteAction),
						data: postData,
						success: function (data) {

							if (data.Result != 'OK') {
								$row.data('deleting', false);
								if (error) {
									error(data.Message);
								}

								return;
							}

							self._trigger("recordDeleted", null, { record: $row.data('record'), row: $row, serverResponse: data });

							if (success) {
								success(data);
							}
						},
						error: function () {
							$row.data('deleting', false);
							if (error) {
								error(self.options.messages.serverCommunicationError);
							}
						}
					});
				},

				/* Removes a row from table after a 'deleting' animation.
				*************************************************************************/
				_removeRowsFromTableWithAnimation: function ($rows, animationsEnabled) {
					var self = this;

					if (animationsEnabled == undefined) {
						animationsEnabled = self.options.animationsEnabled;
					}

					if (animationsEnabled) {
						//Stop current animation (if does exists) and begin 'deleting' animation.
						$rows.stop(true, true).addClass('jtable-row-deleting', 'slow', '').promise().done(function () {
							self._removeRowsFromTable($rows, 'deleted');
						});
					} else {
						self._removeRowsFromTable($rows, 'deleted');
					}
				}

			});

		}


		function jtable_messages()
		{
			$.extend(true, $.hik.jtable.prototype.options.messages, {
				serverCommunicationError: 'Erreur de communication avec le serveur.',
				loadingMessage: 'Chargement des données...',
				noDataAvailable: 'Aucune donnée !',
				addNewRecord: 'Ajouter',
				editRecord: 'Editer',
				areYouSure: 'Etes-vous sûr ?',
				deleteConfirmation: 'Cet enregistrement sera supprimé. Continuer ?',
				valid: 'Valider',
				status: 'Entrée',
				save: 'Sauvegarder',
				saving: 'Sauvegarde en cours...',
				calcul: "Calculer",
				cancel: 'Annuler',
				deleteText: 'Supprimer',
				deleting: 'Supression en cours...',
				error: 'Erreur',
				close: 'Fermer',
				cannotLoadOptionsFor: 'Impossible de charger les données du champ {0}',
				pagingInfo: 'Afficher {0} a {1} de {2}',
				canNotDeletedRecords: 'Impossible de supprimer {0} sur {1} enregistrement(s)!',
				deleteProggress: 'Supression {0} sur {1} enregistrement(s), en cours d\'exécution...',
				pageSizeChangeLabel: 'Enregistrement',
				gotoPageLabel: 'Page'
		    });
		}
		/************************************************************************
		* Seek filter extension                                        *
		*************************************************************************/
		function seekFilter()
		{
			$.datepicker.regional['fr'] = {
				closeText: 'Fermer',
				prevText: '<Préc',
				nextText: 'Suiv>',
				currentText: 'Courant',
				monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin',
				'Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
				monthNamesShort: ['Jan','Fév','Mar','Avr','Mai','Jun',
				'Jul','Aoû','Sep','Oct','Nov','Déc'],
				dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
				dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
				dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
				weekHeader: 'Sm',
				dateFormat: 'dd/mm/yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: ''};
			$.datepicker.setDefaults($.datepicker.regional['fr']);

			var current_date = new Date();
			var resadatealler_start = $('#date_aller_start');
			var resadatealler_end = $('#date_aller_end');
			var resadateretour_start = $('#date_retour_start');
			var resadateretour_end = $('#date_retour_end');
			resadatealler_start.datetimepicker({
				TimeFormat: "hh:mm",
                controlType: 'select',
				hourMin: 3,
				minuteMin: 0,
				hourMax: 23,
				stepMinute: 5,
				dateFormat: "dd/mm/yy",
				beforeShow: function(input, inst){
					$(this).dialog("widget").css("z-index", 200);
				},
				onSelect: function(selectedDateTime)
				{
					resadatealler_end.datetimepicker('option', 'minDate', resadatealler_start.datetimepicker('getDate'));
					resadateretour_start.datetimepicker('option', 'minDate', resadatealler_start.datetimepicker('getDate'));
					resadateretour_end.datetimepicker('option', 'minDate', resadatealler_start.datetimepicker('getDate'));
				}
			});
			resadatealler_end.datetimepicker({
				TimeFormat: "hh:mm",
                controlType: 'select',
				minDate: current_date,
				hourMin: 3,
				hourMax: 23,
				stepMinute: 5,
				dateFormat: "dd/mm/yy",
				beforeShow: function()
				{
						$(this).dialog("widget").css("z-index", 200);
				}
			});
			resadateretour_start.datetimepicker({
				TimeFormat: "hh:mm",
                controlType: 'select',
				hourMin: 3,
				minuteMin: 0,
				hourMax: 23,
				stepMinute: 5,
				dateFormat: "dd/mm/yy",
				beforeShow: function(input, inst){
					$(this).dialog("widget").css("z-index", 200);
				},
				onSelect: function(selectedDateTime)
				{
					resadateretour_end.datetimepicker('option', 'minDate', resadateretour_start.datetimepicker('getDate'));
				}
			});
			resadateretour_end.datetimepicker({
				TimeFormat: "hh:mm",
				minDate: current_date,
				hourMin: 3,
				hourMax: 23,
				stepMinute: 5,
				dateFormat: "dd/mm/yy",
				beforeShow: function()
				{
						$(this).dialog("widget").css("z-index", 200);
				}
			});
		}

		function removeDeleteButton()
		{
			$('#DeleteButton').remove();
		}

		function addPrintButton()
		{
			$('<input />')
				.attr('id', 'PrintButton')
				.addClass('button')
				.attr('type', 'button')
				.attr('value','Imprimer')
				.click(function (){
				var selectedRows = $('#JTableContainer').jtable('selectedRows');
				if (selectedRows.length <= 0) {
						$.dW.alert("Vous devez sélectionner au moins une ligne", {show: 'fade', hide: 'fade', modal: true});
						return;
					}
				var $printDiv = $('<div></div>').appendTo($("body"));
				$printDiv.dialog({
						autoOpen: false,
						show: 'fade',
						hide: 'fade',
						width: 'auto',
						minWidth: '350',
						modal: true,
						id: 'dialog_print',
						close: function () { $('.ui-widget-overlay').remove(); $('#jtable-print-button-form').remove();},
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
										var url = ajax_object.ajax_url+'?action=printResaAction&post_id='+ajax_object.postid;
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
										var selectedRows = $('#JTableContainer').jtable('selectedRows');
										$(selectedRows).each(function(index, element) {
											url += '&ids[]=' + $(element).data('record')['id'];
										});
										$(this).dialog("close");
										window.open(url, 'imprimer', 'status=no,location=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=700,height=480,directories=no,', false);
									}}]});
				var $printForm = $('<form id="jtable-print-button-form"></form>');
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
				.append(addTr('<input type="checkbox" name="cx[]" value="bs">Bon de sortie')) //.append(addTr('<input type="checkbox" name="cx[]" value="fc">Facture'))
				.appendTo($printTable);
				$printForm.append($printTable);
				$printDiv.append($printForm).dialog('open');

			})
				.appendTo($('#button_pkmgmt'));

		}

		function removePrintButton()
		{
			$('#PrintButton').remove();
		}

		function addCalButton()
		{
			$('<input />')
				.attr('id', 'CalButton')
				.addClass('button')
				.attr('type', 'button')
				.attr('value','Calendrier')
				.click(function() {
					$('body').append('<div id="calendar_dialog"><div class="custom-calendar-wrap">'+
										'<div id="custom-inner" class="custom-inner">'+
										'<div class="custom-header clearfix">'+
											'<nav>'+
												'<span id="custom-prev" class="custom-prev"></span>'+
												'<span id="custom-next" class="custom-next"></span>'+
											'</nav>'+
											'<h2 id="custom-month" class="custom-month"></h2>'+
											'<h3 id="custom-year" class="custom-year"></h3>'+
										'</div>'+
										'<div id="calendar" class="fc-calendar-container"></div>'+
										'</div>'+
									'</div></div>');
					//$.getScript(ajax_object.calendario);
					//$.getScript(ajax_object.calendrier);

					var options = {
						'title': 'Calendrier',
						'id': 'calendar_dialog',
						minWidth:620,
						width: 800,
						'modal': false,
						'create': function( event, ui ) {
							fillCalendar();
							}
					};
					options = $.extend(true, {}, $.ui.dialog.prototype.options, options);
					options.dynamicallyCreated = true;
					var dialog = $('#calendar_dialog').dialog( options );
					var widget = dialog.data("dialog");
					if ($(".ui-widget-overlay").size() > 1){
						$(".ui-widget-overlay:first").remove();
					}
					return dialog;
				})
				.appendTo($('#button_pkmgmt'));

		}

		function removeCalButton()
		{
			$('#CalButton').remove();
		}

		$select_pkmgmt.change();

	}
);
