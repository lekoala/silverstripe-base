(function ($) {
    $.fn.filepond.registerPlugin(FilePondPluginFileRename);
    $.fn.filepond.registerPlugin(FilePondPluginFileValidateSize);
    $.fn.filepond.registerPlugin(FilePondPluginFileValidateType);
    var defaults = {
        allowFileTypeValidation: true,
        allowFileRename: true,
        fileRenameFunction: function (file) {
            return file.name;
        }
    };
    $.fn.filepond.setDefaults(defaults);
})(jQuery);
