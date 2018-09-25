/**
 * Tweaks for the CMS
 * TODO: how to avoid attaching everything to document (use entwine?)
 */
(function($) {
    // Allow select with a data map attribute to update other selects
    $(document).on('change', 'select[data-map]', function(event) {
        var el = $(this);
        var data = el.data('map');
        var target = el.data('target');
        if (!data || !target) {
            console.log("Empty data or no target");
            return;
        }
        var val = el.val();
        var input = $('#Form_ItemEditForm_' + target);
        if (data[val]) {
            input.val(data[val]).trigger('change').trigger("chosen:updated").trigger("liszt:updated");
        }
    });

    // Allow button that copy stuff to clipboard
    $(document).on('click', 'button[data-clipboard]', function(event) {
        // Otherwise in a gridfield the row might be clicked
        event.stopPropagation();
        copyTextToClipboard($(this).data('value'));
    });

    // Confirmable stuff
    $(document).on('click', 'button[data-confirm],a[data-confirm]', function(event) {
        return confirm($(this).data('confirm'));
    });

    // Promptable stuff
    $(document).on('click', 'button[data-prompt]', function(event) {
        var result = prompt($(this).data('prompt'), $(this).data('promptDefault'));
        if (result) {
            $.cookie('prompt_result', result);
            return true;
        }
        return false;
    });

    // Prevent submit with enter
    $(document).on("keypress", ":input:not(textarea)", function(event) {
        if (event.keyCode == 13) {
            event.preventDefault();
            // This is just lazy excel like tab
            $(this).parents('.field').next('.field').find(':input').first().focus();
        }
        return true;
    });

    $.entwine('ss', function($) {
        // Load tab if set in url
        var tabLoaded = false;
        $('ul.ui-tabs-nav a').entwine({
            onmatch: function() {
                this._super();

                if (tabLoaded) {
                    return;
                }

                var url = this.attr('href'),
                    hash = url.split('#')[1];

                if (window.location.hash) {
                    var currHash = location.hash.substring(1);
                    if (currHash == hash) {
                        this.trigger('click');
                        tabLoaded = true;
                    }
                }
            },
            onclick: function() {
                // Track active tab on submit
                var input = $('#js-form-active-tab');
                if (!input.length) {
                    input = $('<input type="hidden" name="_activetab" class="no-change-track" id="js-form-active-tab" />');
                    $('#Form_ItemEditForm').append(input);
                }
                var url = this.attr('href'),
                    hash = url.split('#')[1];

                input.val(hash);
            }
        });
        // Prevent navigation for no ajax
        $('.grid-field__icon-action.no-ajax').entwine({
            onmatch: function() {},
            onunmatch: function() {},
            onclick: function(e) {
                e.stopPropagation();
            }
        });
        // Let input work properly in grids
        $('.ss-gridfield-items td select, .ss-gridfield-items td input').entwine({
            onmatch: function() {},
            onunmatch: function() {},
            onclick: function(e) {
                // Prevent row click
                e.stopPropagation();
            }
        });
        // Clickable icons
        $('.uploadfield-item__thumbnail').entwine({
            onclick: function() {
                var id = this.parent().find('input').val();
                var uploadfield = this.parents('div.entwine-uploadfield').find('input.entwine-uploadfield');
                var state = uploadfield.data('state');
                var files = state.data.files;
                for (var i = 0; i < files.length; i++) {
                    var item = files[i];
                    if (item.id == id) {
                        window.open(item.url);
                    }
                }
            }
        });
    });

})(jQuery);

// @link https://stackoverflow.com/questions/400212/how-do-i-copy-to-the-clipboard-in-javascript
// Expose a global function copyTextToClipboard
function copyTextToClipboard(text) {
    var successful, msg, type;
    var textArea = document.createElement("textarea");
    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.left = 0;
    textArea.style.width = '2em';
    textArea.style.height = '2em';
    textArea.style.padding = 0;
    textArea.style.border = 'none';
    textArea.style.outline = 'none';
    textArea.style.boxShadow = 'none';
    textArea.style.background = 'transparent';
    textArea.value = text;

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        successful = document.execCommand('copy');

        if (successful) {
            msg = "Data copied to the clipboard";
            type = "success";
        } else {
            msg = "Failed to copy data to the clipboard";
            type = "error"
        }
        jQuery.noticeAdd({
            text: msg,
            type: type,
            stayTime: 5000,
            inEffect: {
                left: '0',
                opacity: 'show'
            }
        });
    } catch (err) {}

    document.body.removeChild(textArea);

    return false;
}

function resizeIframe(iframe) {
    var $obj = jQuery(iframe);
    var height = $obj.contents().height();
    if ($obj.is(':visible') || height == 0) {
        var clone = $obj.clone().attr("id", false)
            .css({
                visibility: "hidden",
                display: "block",
                position: "absolute"
            });
        jQuery("body").append(clone);
        height = clone.height();
        clone.remove();
    }
    if (height > 0) {
        $obj.height(height);
    }
}
