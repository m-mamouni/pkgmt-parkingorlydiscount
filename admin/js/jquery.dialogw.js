/*! dialogWrapper - v2.1.1 - 2012-06-03
* https://bitbucket.org/MostThingsWeb/dialogwrapper
* Copyright (c) 2012 MostThingsWeb (Chris Laplante); Licensed MIT */

(function($) {
    if ($.dW){
        return;
    }

    $.dW = {};

    if (!$.ui || !$.ui.dialog){
        return;
    }

    // Internal function used to generate a random ID
    function randomID() {
        var id = "";
        for ( var i = 1; i <= 10; i++){
            id += (Math.floor(Math.random() * 10) + 1);
        }
        return id;
    }

    // Internal function used for getting the dialog on top
    function _getTopDialog(elems) {
        // Store the greates z-index that has been seen so far
        var maxZ = 0;
        // Stores a reference to the element that has the greatest z-index so
        // far
        var maxElem;
        // Check each element's z-index
        elems.each(function() {
            var dialog = $(this).parent();
            // If it's bigger than the currently biggest one, store the value
            // and reference
            if (dialog.css("z-index") > maxZ) {
                maxElem = dialog;
                maxZ = dialog.css("z-index");
            }
        });
        // Finally, return the reference to the element on top
        return maxElem;
    }

    // Declare custom option defaults in the global prototype object so
    // users can modify it
    $.ui.dialog.prototype.options.hasClose = true;

    $.ui.dialog.prototype.destroy = function(){
        var self = this;

        self.uiDialog.hide();
        self.element
        .unbind('.dialog')
        .removeData('dialog')
        .removeClass('ui-dialog-content ui-widget-content')
        .hide().appendTo('body');
        self.uiDialog.remove();

        if (self.originalTitle) {
            self.element.attr('title', self.originalTitle);
        }

        return self;
    };

    // Issue #1: Modaled dialogs that aren't dynamically created still need
    // the modal attribute
    var _createFn = $.ui.dialog.prototype._create;
    $.ui.dialog.prototype._create = function(){
        _createFn.apply(this);
        var options = this.options;
        if (!options.dynamicallyCreated && options.modal){
            this.uiDialog.attr("modal", true);
        }
    };

    // Override the default close method to handle stacked overlays
    $.ui.dialog.prototype.close = function(event){
        var self = this, maxZ, thisZ;
        
        if (false === self._trigger('beforeClose', event)) {
            return;
        }

        // *** Begin modifications ***

        // If a modaled dialog was closed, let's check if the overlay is still needed
        if (self.uiDialog.find(".ui-dialog-content").is("[modal=true]")){
            // Get a collection of modals that are visible and that require a modal,
            // excluding this one
            var modalizedDialogs = $(".ui-dialog-content:visible[modal=true]").not(self.uiDialog.find(".ui-dialog-content"));
            var modalizedDialogLen = modalizedDialogs.size();

            // If a dialog exists that requires a modal, drop the overlay behind
            // the top dialog; otherwise, remove the overlay
            if (modalizedDialogLen > 0){
                $(".ui-widget-overlay").css("z-index", parseInt(_getTopDialog(modalizedDialogs).css("z-index"), 10) - 1);
            } else {
                $(".ui-widget-overlay").remove();
            }
        }

        // Remove keyboard and mouse blocking
        $([document, window]).unbind('.dialog-overlay');

        // *** End modifications ***

        self.uiDialog.unbind('keypress.ui-dialog');

        self._isOpen = false;

        if (self.options.hide) {
            self.uiDialog.hide(self.options.hide, function() {
                self._trigger('close', event);
            });
        } else {
            self.uiDialog.hide();
            self._trigger('close', event);
        }

       // $.ui.dialog.overlay.resize();

        // adjust the maxZ to allow other modal dialogs to continue to work (see #4309)
        if (self.options.modal) {
            maxZ = 0;
            $('.ui-dialog').each(function() {
                if (this !== self.uiDialog[0]) {
                    thisZ = $(this).css('z-index');
                    if(!isNaN(thisZ)) {
                        maxZ = Math.max(maxZ, thisZ);
                    }
                }
            });
            $.ui.dialog.maxZ = maxZ;
        }

        return self;
    };

    // For dynamically-created dialogs, this private parameter stores the detached
    // version of the titlebar close button
    $.ui.dialog.prototype._titlebarClose = null;

    // Override the default implementation of _setOption to account for our custom options
    var _setOptionFn = $.ui.dialog.prototype._setOption;
    $.ui.dialog.prototype._setOption = function(key, value){
        // Intercept dialogWrapper-added options
        var self = this,
        uiDialog = self.uiDialog;
        
        if (key === "hasClose"){
            // Currently, this option is only available for dialogs created with
            // dialogWrapper
            if (!self.options.dynamicallyCreated){
                return;
            }
            // Cancel if the value is already set
            if (value === self.options.hasClose){
                return;
            }
            // Should we remove the close link or reattach it?
            if (value){
                uiDialog.find(".ui-dialog-titlebar").append(self._titlebarClose);
            } else {
                self._titlebarClose = uiDialog.find(".ui-dialog-titlebar-close").detach();
            }
        } else {
            // For native options, pass the call onto the original _setOption function
            _setOptionFn.call(this, key, value);
        }

        // For dialogWrapper-created options, we need to persist the settings
        $.Widget.prototype._setOption.apply(self, arguments);
    };

    $.dW = $.extend({
        version: "2.1",
        /*
         * Return the topmost open dialog
         */
        getTopDialog: function(){
            // Check that a dialog exists and is open
            var dialogs = $(".ui-dialog-content:visible");
            
            if (dialogs.size() === 0){
                return null;
            }
            
            if (dialogs.size() === 1){
                return dialogs[0];
            }
            
            return _getTopDialog(dialogs);
        },
        /*
         * Attempt to resolve the given identifier to an open dialog
         */
        findDialog: function(dialog){
            if (!dialog) {
                return this.getTopDialog();
            }
            if (!(dialog instanceof Object)){
                if (dialog.constructor === Number){
                    dialog = $("#" + String(dialog));
                } else if (dialog.constructor === String) {
                    if (!(/^#/.test(dialog))){
                        dialog = "#" + dialog;
                    }
                    dialog = $(dialog);
                } else {
                    return false;
                }
            }

            if (!dialog.jquery){
                dialog = $(dialog);
            }

            // Die if the dialog doesn't exist
            if (dialog.size() === 0) {
                return false;
            }
            return dialog;
        },
        /*
         * Dynamically create a dialog
         */
        createDialog : function(){
            var title = "", body, args = null;
            // What kind of arguments do we have?
            switch (arguments.length){
                case 1:
                    body = arguments[0];
                    break;
                case 2:
                    body = arguments[0];
                    args = arguments[1];
                    break;
                case 3:
                    title = arguments[0];
                    body = arguments[1];
                    args = arguments[2];
                    break;
            }
            
            // Define our default parameters
            var options = {
                title : title,
                id: "dWd" + randomID()
            };
            
            // Use the default jQuery UI Dialog options as the base, with our default
            // options overriding any conflicting options, and the user-supplied
            // options overriding those
            options = $.extend(true, {}, $.ui.dialog.prototype.options, options, args);
            
            // Explicitly remember that this dialog was dynamically created
            options.dynamicallyCreated = true;
            
            // Create the dialog markup
            $("body").append("<div id='" + options.id + "'><p>" + body + "</p></div>");
            var dialog = $("#" + options.id).dialog(options);
            
            // Access the dialog widget object
            var widget = dialog.data("dialog");
            
            // If more than one overlay exists, remove the first one (since this
            // is the order in which overlays are created)
            if ($(".ui-widget-overlay").size() > 1){
                $(".ui-widget-overlay:first").remove();
            }
            
            // The dialog's close button must be set up to use dialogWrapper methods
            // to close the dialog instead of native methods
            var dW = this;
            dialog.find(".ui-dialog-titlebar-close").unbind("click").attr("href", "javascript: void false").click(function(){
                dW.destroyDialog(dialog.find(".ui-dialog-content"));
            });
            
            // If the dialog doesn't have a close button, remove it
            if (!options.hasClose){
                // The titlebar is contained in the wrapper div
                widget._titlebarClose = dialog.parent().find(".ui-dialog-titlebar-close").detach();
            }

            // If the dialog has a modal, remember that
            if (options.modal){
                dialog.attr("modal", "true");
            }

            return dialog;
        },
        /*
         * Hide a dialog
         */
        hideDialog: function(dialog, args){
            var options = {
                hide: null
            };

            $.extend(options, args);

            // Find the dialog
            dialog = this.findDialog(dialog);

            if (!dialog){
                return false;
            }

            // If the user specified a method of hiding the dialog, override the existing
            // value
            if (options.hide){
                dialog.dialog("option", "hide", options.hide);
            }

            // Hide the dialog
            dialog.dialog("close");

            return true;
        },
        /*
         * Hide all dialgos that are open
         */
        hideDialogs: function(){
            var success = true, dW = this;
            $(".ui-dialog-content:visible").each(function(){
                if (!dW.hideDialog($(this))){
                    success = false;
                }
            });
            return success;
        },
        /*
         * Destroy all dialogs that are open. By default, the HTML is removed as well
         */
        destroyDialogs: function(remove){
            if (remove === null){
                remove = true;
            }
            var success = true, dW = this;
            $(".ui-dialog-content:visible").each(function(){
                if (!dW.destroyDialog($(this), {
                    remove: remove
                })){
                    success = false;
                }
            });
            return success;
        },
        /*
         * Destroy a dialog box. By default, the HTML is removed for it as well
         */
        destroyDialog: function(dialog, args){
            var options = {
                remove: true,
                hide: null
            };

            $.extend(options, args);

            // Find the dialog
            dialog = this.findDialog(dialog);

            if (!dialog){
                return false;
            }

            // Attempt to hide the dialog first
            if (!this.hideDialog(dialog, args)){
                return false;
            }
            
            // Destroy the dialog after the close event has triggered
            dialog.bind("dialogclose", function(){
                $(dialog).dialog("destroy");

                // Finally, if specified, remove it from the DOM tree
                if (options.remove){
                    $(dialog).remove();
                }
            });
            
            return true;
        },
        /*
         * Create a confirm-styled dialog
         */
        confirm: function(prompt, yes, no, args) {
            // Require yes and no; args is optional
            if (!yes || !no){
                return null;
            }
            args = args || {};
            var dW = this;
            return this.createDialog("Confirm", prompt, $.extend(true, {
                buttons : {
                    "No" : function() {
                        (no || $.noop).apply(this);
                        dW.destroyDialog(this);
                    },
                    "Yes" : function() {
                        (yes || $.noop).apply(this);
                        dW.destroyDialog(this);
                    }
                }
            },
            args));
        },
        /*
         * Create an alert-styled dialog
         */
        alert: function(prompt, args, callback) {
            args = args || {};
            var dW = this;
            return this.createDialog("Info", prompt, $.extend(true, {
                buttons : {
                    "Ok" : function() {
                        // When the Ok button is clicked, just hide this dialog
                        dW.destroyDialog(this);
                        (callback || $.noop).apply(this);
                    }
                }
            },
            args));
        },
        /*
         * Create an input-box styled dialog
         */
        input: function(prompt, args, callback){
            // callback is required; args is optional
            if (!callback){
                return null;
            }
            args = args || {};
            var dW = this;
            var inputID = randomID();
            var ret = this.createDialog("Input", prompt + "<br/><br/><input id='" + inputID + "' style='width: 100%;' type='text'/>", $.extend(true, {
                buttons : {
                    "Ok" : function() {
                        // When the Ok button is clicked, pass the value to the callback
                        // and close the dialog
                        callback.call(this, $("#" + inputID).val());

                        dW.destroyDialog(this);
                    }
                }
            },
            args));
            $("#" + inputID).val(args.defaultValue);
            return ret;
        },
        /*
         * Provide classic-style methods for backwards-compatibility
         */
        classicMode: function(){
            $.getTopDialog = this.getTopDialog;
            $.findDialog = this.findDialog;
            $.createDialog = this.createDialog;
            $.hideDialog = this.hideDialog;
            $.hideDialogs = this.hideDialogs;
            $.destroyDialog = this.destroyDialog;
            $.destroyDialogs = this.destroyDialogs;
            $.alert = this.alert;
            $.confirm = this.confim;
            $.input = this.input;
        }
    }, $.dW);

}(jQuery));