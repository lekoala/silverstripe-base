(function ($) {
    // $.fn.filepond.registerPlugin(FilePondPluginFileRename);
    $.fn.filepond.registerPlugin(FilePondPluginFileValidateSize);
    $.fn.filepond.registerPlugin(FilePondPluginFileValidateType);
    var defaults = {
        allowFileTypeValidation: true
    };
    $.fn.filepond.setDefaults(defaults);
})(jQuery);
