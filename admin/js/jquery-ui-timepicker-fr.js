/* French initialisation for the jQuery UI time picker plugin. */
/* Written by David ALEXANDRE. */
jQuery(function($) {
       $.timepicker.regional['fr'] = {
	        currentText: 'Maintenant',
		closeText: 'Ok',
		ampm: false,
		amNames: ['AM', 'A'],
		pmNames: ['PM', 'P'],
		timeFormat: 'hh:mm',
		timeSuffix: '',
		timeOnlyTitle: 'Sélectionner l\'heure',
		timeText: 'Heure',
		hourText: 'Heure',
		minuteText: 'Minute',
		secondText: 'Seconde',
		millisecText: 'Milliseconde',
		timezoneText: 'Time Zone',
		isRTL: false
      };
      $.timepicker.setDefaults($.timepicker.regional['fr']);
});