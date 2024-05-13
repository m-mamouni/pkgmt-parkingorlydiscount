var $ = jQuery.noConflict();
$(document).ready(
    function () {
        jQuery.datetimepicker.setLocale('fr');
        jQuery(function ($) {
            $.datepicker.regional['fr'] = {
                closeText: 'Fermer',
                prevText: '<Préc',
                nextText: 'Suiv>',
                currentText: 'Courant',
                monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
                monthNamesShort: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun',
                    'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                dayNames: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
                dayNamesShort: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
                dayNamesMin: ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
                weekHeader: 'Sm',
                dateFormat: 'dd/mm/yy',
                firstDay: 1,
                isRTL: false,
                showMonthAfterYear: false,
                yearSuffix: ''
            };
            $.datepicker.setDefaults($.datepicker.regional['fr']);
        });
        $('form *:input[name=nom]').focus();
        if ($('#conditiongenerale').val()) {
            if (!$('#conditiongenerale').is(':checked'))
                $("#submitbtn").attr("disabled", "disabled");
            $('#conditiongenerale').change(function () {
                swSubmitBtn($(this).is(':checked'));
            });
        }
        const fullMsg = "Parking Complet \njusqu'au 3 mai 2024 inclus appelez désistement possible";
        const fullDateStart = new Date('04/19/2024 00:00:00');
        const fullDateEnd = new Date('05/03/2024 23:59:59');
        let current_date = getCurrentDate();
        if (isFull(current_date))
            current_date = fullDateEnd;
        const resanavette = $('.navette');
        const resadateretour = $('.date_retour');
        const homeresanavette = $('#homenavette');
        const homeresadateretour = $('#homeresadateretour');
        let _navette = current_date;
        let _homenavette = current_date;
        homeresanavette.prop("readonly", true).datetimepicker({
            controlType: 'select',
            hourMin: 0,
            stepMinute: 5,
            step: 5,
            minuteMin: 0,
            minDate: current_date,
            dateFormat: "d/m/Y H:i",
            format: "d/m/Y H:i",
            formatTime: "H:i",
            formatDate: "d/m/Y",
            numberOfMonths: 1,
            showButtonPanel: true,
            beforeShow: function () {
                clearHomeDiv();
                homeresadateretour.empty();
                $(this).dialog("widget").css("z-index", 60);
                setTimeout(function () {
                    $('#ui-datepicker-div').css("z-index", 70);
                }, 0);
            },
            onSelect: function () {
                homeresadateretour.datetimepicker('option', 'minDate', homeresanavette.datetimepicker('getDate'));
            },
            onClose: function (selectedDateTime) {
                _homenavette = selectedDateTime;
            }
        });

        homeresadateretour.prop("readonly", true).datetimepicker({
            controlType: 'select',
            hourMin: 0,
            stepMinute: 5,
            step: 5,
            minuteMin: 0,
            minDate: current_date,
            dateFormat: "d/m/Y H:i",
            format: "d/m/Y H:i",
            formatTime: "H:i",
            formatDate: "d/m/Y",
            numberOfMonths: 1,
            showButtonPanel: true,
            beforeShow: function () {
                clearHomeDiv();
                $(this).dialog("widget").css("z-index", 15);
                setTimeout(function () {
                    $('#ui-datepicker-div').css("z-index", 70);
                }, 0);
            },
            onShow: function () {
                this.setOptions({
                    minDate: _homenavette
                });
            },
            onClose: function () {
                if (homeresanavette.datetimepicker({dateFormat: 'dd-mm-yy'}).val()) {
                    const data = {
                        action: "getTarifByDate",
                        post_id: ajax_object.postid,
                        navette: homeresanavette.datetimepicker({dateFormat: 'dd-mm-yy'}).val(),
                        date_retour: homeresadateretour.datetimepicker({dateFormat: 'dd-mm-yy'}).val(),
                    };
                    const response = getData(ajax_object.ajax_url, data);
                    homeDivTarif(response);
                }
            },

        });

        function getData(url, data) {
            let ret;
            $.ajax({
                url: url,
                data: data,
                dataType: "json",
                type: 'POST',
                async: false,
                success: function (data) {
                    ret = data;
                }
            });
            return ret;
        }

        function clearHomeDiv() {
            $('#home_form_estimation').empty();
        }

        function homeDivTarif(data) {
            if (data && data.ext) {
                divTarifTemplate('ext-tarif', 'Parking extérieur', data.ext, data.nbr, 'extérieur', 'ext');
            }
            if (data && data.int) {
                divTarifTemplate('int-tarif', 'Parking intérieur', data.int, data.nbr, 'intérieur', 'int');
            }
            $('#home_form_estimation').slideDown();
        }

        function divTarifTemplate(id, title, amount, nbr_jour, type, type2) {
            var $divTitle = $("<div></div>")
                .attr('id', id + '-title')
                .text(title);
            var $divNbrJours = $("<div></div>")
                .attr('id', id + '-days')
                .addClass('pkmgmt-days')
                .text(nbr_jour + ' jours');
            var $divAmount = $("<div></div>")
                .attr('id', id + '-amount')
                .addClass('pkmgmt-amount')
                .text('€ ' + amount);
            var $divHeader = $("<div></div>")
                .attr('id', id + '-header')
                .addClass('pkmgmt-header')
                .append($divTitle)
                .append(
                    $("<div></div>")
                        .attr('id', id + '-subheader')
                        .append($divAmount)
                        .append($divNbrJours)
                );
            var $divBody = $("<div></div>")
                .attr('id', id + '-body')
                .addClass('pkmgmt-body')
                .append(homeDivList(id + '-ul', type))
            $('<div style="display:none"></div>')
                .attr('id', id)
                .append($divHeader)
                .append($divBody)
                .append(homeDivButton(id, homeresanavette.datetimepicker({dateFormat: "dd/mm/yy"}).val(), homeresadateretour.datetimepicker({dateFormat: "dd/mm/yy"}).val(), type2))
                .appendTo($('#home_form_estimation')).slideDown("slow");
        }

        function homeDivList(attr, text) {
            return $("<ul></ul>")
                .addClass(attr)
                .append(homeDivListLi('tarif-list-li', 'Navette comprise'))
                .append(homeDivListLi('tarif-list-li', 'Parking sécurisé'))
                .append(homeDivListLi('tarif-list-li', 'Parking ' + text));
        }

        function homeDivListLi(attr, text) {
            return $("<li></li>")
                .addClass(attr)
                .text(text);
        }

        function dialogMessage(title, message) {
            var $messageDiv = $('<div></div>').appendTo($("body"));
            $messageDiv.addClass("dialog-message");
            $messageDiv.attr("title", title);
            $("<p></p>").text(message).appendTo($messageDiv);
            $("#dialog-message").dialog({
                modal: true,
                buttons: {
                    Ok: function () {
                        $(this).dialog("close");
                        $("#dialog-message").remove();
                    }
                }
            }).open();
        }

        function homeDivButton(id, navette, date_retour, type) {
            return $('<form></form>')
                .attr('id', id)
                .attr('name', id)
                .attr('action', '/reservation')
                .attr('method', 'post')
                .addClass(id + '-' + type + '-form')
                .append($('<input name="navette" type="hidden">').attr("value", navette))
                .append($('<input name="date_retour" type="hidden">').attr("value", date_retour))
                .append($('<input name="type" type="hidden">').attr("value", type))
                .append($('<button>Réserver</button>'))
                ;
        }

        function getCurrentDate() {
            return new Date();
        }

        function isFullStart(nDate) {
            return (nDate.getTime() >= fullDateStart.getTime());
        }

        function isFull(nDate) {
            if (nDate) {
                return (nDate.getTime() <= fullDateEnd.getTime());
            }
            return false;
        }

        function swSubmitBtn(state) {
            if ($('#conditiongenerale').val()) {
                if (state && $('#conditiongenerale').is(':checked')) {
                    $("#submitbtn").removeAttr("disabled");
                } else {
                    $("#submitbtn").attr("disabled", "disabled");
                }
            }
        }

        function getDateConvert(selectedDate) {
            const pattern = /(\d{2})\/(\d{2})\/(\d{4}) (.*)/;
            return new Date(selectedDate.replace(pattern, '$3-$2-$1 $4'));
        }

        function showSpinner(s) {
            const ajaxHolder = document.createDocumentFragment();

            // Add div wrapper for spinner
            const ajaxDiv = document.createElement('div');
            ajaxDiv.id = 'book-spinner';
            ajaxDiv.style.position = 'fixed';
            ajaxDiv.style.top = '0';
            ajaxDiv.style.left = '0';
            ajaxDiv.style.width = '100vw';
            ajaxDiv.style.height = '100vh';
            ajaxDiv.style.display = 'flex';
            ajaxDiv.style.justifyContent = 'center';
            ajaxDiv.style.alignContent = 'center';
            ajaxDiv.style.zIndex = '9999';
            ajaxDiv.style.backgroundColor = 'rgba(0, 0, 0, 0.4)';

            // ajaxDiv.style.borderRadius = '11px';
            // ajaxDiv.style.opacity = 1;
            ajaxDiv.setAttribute('class', 'book-spinner');
            // ajaxDiv.style.background = '#000000';

            // Add image spinner
            const divImg = document.createElement('img');
            divImg.style.width = '30px';
            divImg.style.height = '30px';
            divImg.style.margin = 'auto';
            divImg.src = 'data:image/gif;base64,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';

            ajaxDiv.appendChild(divImg);
            ajaxHolder.appendChild(ajaxDiv);
            document.body.appendChild(ajaxHolder);
            if (s) s.attr("disabled", "disabled");
        }

        resanavette.datetimepicker({
            lang: 'fr',
            i18n: {
                fr: { //French
                    months: [
                        "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
                    ],
                    dayOfWeekShort: [
                        "Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"
                    ],
                    dayOfWeek: ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"]
                }
            },
            controlType: 'select',
            stepMinute: 5,
            minDate: current_date,
            minuteMin: 0,
            step: 5,
            dateFormat: "dd/mm/yy",
            format: "d/m/Y H:i",
            formatTime: "H:i",
            formatDate: "d/m/Y",
            beforeShow: function () {
                $(this).dialog("widget").css("z-index", 15);
                setTimeout(function () {
                    $('#ui-datepicker-div').css("z-index", 16);
                }, 0);
            },
            showButtonPanel: true,
            onClose: function (selectedDateTime) {
                resanavette.val(moment(selectedDateTime.toISOString()).format("DD/MM/YYYY HH:mm"));
                resadateretour.datetimepicker('option', 'minDate', resanavette.datetimepicker('getDate'));

                if (selectedDateTime) {
                    _navette = selectedDateTime;
                    if (isFull(selectedDateTime) && isFullStart(selectedDateTime)) {
                        alert(fullMsg);
                        swSubmitBtn(false);
                    } else {
                        swSubmitBtn(true);
                    }
                }
            }
        });


        resadateretour.datetimepicker({
            controlType: 'select',
            stepMinute: 5,
            step: 5,
            minDate: current_date,
            dateFormat: "d/m/Y",
            format: "d/m/Y H:i",
            formatDate: "d/m/Y",
            formatTime: "H:i",
            showButtonPanel: true,
            beforeShow: function () {
                $(this).dialog("widget").css("z-index", 200);
            },
            onShow: function () {
                this.setOptions({
                    minDate: _navette ? _navette : false
                });
            },
            onClose: function (datetimeText) {
                $('.date_retour').val(moment(datetimeText.toISOString()).format("DD/MM/YYYY HH:mm"));
            }
        });

        var indicatif = ajax_object.indicatif ? ajax_object.indicatif : "fr";
        $("#mobile").intlTelInput(
            {
                onlyCountries: ["fr", "be", "ch", "de", "nl"],
                autoPlaceholder: false,
                autoHideDialCode: false,
                nationalMode: true,
                preferredCountries: [indicatif],
                utilsScript: ajax_object.pkmgmt_includes_dir + "/js/utils.js"
            });
        $.validator.addMethod(
            "mobileFR",
            function () {
                return !!$("#mobile").intlTelInput("isValidNumber");
            },
            "Vérifiez le numéro de téléphone"
        );
        $.validator.addMethod(
            "codepostalFR",
            function (value, element) {
                regexp = /^[\d]{4}|[\d]{5}$/;
                var check = false;
                return this.optional(element) || regexp.test(value);
            },
            "Vérifiez le code postal"
        );
        $('.email').on('keyup change blur', function () {
            $('.email').val($(this).val());
        });
        $.extend(true, $.validator.messages, {
            required: "Ce champ est requis.",
            email: "Adresse invalide.",
            date: "Date invalide.",
            number: "Nombre invalide",
            digits: "Nombre uniquement",
            maxlength: $.validator.format("Pas plus de {0} caracteres."),
            minlength: $.validator.format("Veuillez entrez au moins {0} caractères."),
            rangelength: $.validator.format("Veuillez entrer une valeur entre {0} et {1} caractère de long."),
            range: $.validator.format("Veuillez entrer une valeur entre {0} et {1}."),
            max: $.validator.format("Veuillez entrer une valeur inférieur ou égale à {0}."),
            min: $.validator.format("Veuillez entrer une valeur supérieur ou égale à {0}.")
        });
        $('#pkmgmt-reservation')
            .on('submit', function (event) {
                event.preventDefault();
            })
            .validate({
                    rules: {
                        nom: {
                            minlength: 2,
                            required: true
                        },
                        prenom: {
                            minlength: 2,
                            required: true
                        },
                        cp: {
                            codepostalFR: true,
                            digits: true,
                            required: true
                        },
                        email: {
                            required: true,
                            email: true
                        },
                        mobile: {
                            mobileFR: true,
                            required: true
                        },
                        destination: {
                            minlength: 4,
                            required: true
                        },
                        immatriculation: {
                            minlength: 6,
                            required: true
                        },
                        modele: {
                            minlength: 2,
                            required: true
                        },
                        navette: {
                            required: true
                        },
                        date_retour: {
                            required: true
                        },
                        nbr_retour: {
                            digits: true,
                            range: [1, 8],
                            required: true
                        }
                    },
                    highlight: function (element) {
                        $(element).closest('.control-group').removeClass('success').addClass('error');
                    },
                    success: function (element) {
                        element
                            .text('')
                            .removeClass('error')
                            .addClass('valid')
                            .addClass('success')
                            .addClass('fas fa-check')
                            .closest('.control-group');
                    },
                    submitHandler: function (form) {
                        // if ($(form.email).val() !== "david@zdm.fr") {
                        //     showSpinner($("#submitbtn"));
                        //     form.submit();
                        //     return;
                        // }
                        if (ajax_object.autovalid === '0') {
                            form.submit();
                            return;
                        }
                        const navetteDate = getDateConvert($(form.navette).val());
                        if (isFull(navetteDate) && isFullStart(navetteDate)) {
                            alert(fullMsg);
                            return;
                        }
                        $('#dialogForm')
                            .dialog({
                                autoOpen: false,
                                resizable: false,
                                show: 'fade',
                                hide: 'fade',
                                width: 'auto',
                                zIndexMax: '300',
                                minWidth: '350',
                                modal: true,
                                buttons: [
                                    {
                                        text: 'Annuler',
                                        "class": 'cancel_button',
                                        click: function () {
                                            $(this).dialog('close');
                                        }
                                    },
                                    {
                                        text: 'Valider',
                                        "class": 'valid_button',
                                        click: function () {
                                            const shuttleTime = $('.navette').val();
                                            if (isFull(getDateConvert(shuttleTime)) && isFullStart(getDateConvert(shuttleTime))) {
                                                alert(fullMsg);
                                            } else {
                                                $(this).dialog('close');
                                                showSpinner($("#submitbtn"));
                                                form.submit();
                                            }
                                        }
                                    }
                                ]
                            })
                            .dialog("open");

                        $('.cancel_button, .valid_button').removeClass('ui-button');
                    },
                    errorElement: "span",
                    errorPlacement: function (error, element) {
                        if (element.attr("name") === "mobile") {
                            element.parent().parent("td").next("td").empty();
                            error.appendTo(element.parent().parent("td").next("td"));
                        } else {
                            element.parent("td").next("td").empty();
                            error.appendTo(element.parent("td").next("td"));
                        }
                    }
                }
            );

    }
);
