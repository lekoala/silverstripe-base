/* global FilePondPluginFileValidateSize, FilePondPluginFileValidateType, FilePondPluginFileMetadata , FilePondPluginFilePoster, FilePondPluginImageExifOrientation, FilePondPluginImagePreview*/
(function ($) {
    $.fn.filepond.registerPlugin(FilePondPluginFileValidateSize);
    $.fn.filepond.registerPlugin(FilePondPluginFileValidateType);
    // $.fn.filepond.registerPlugin(FilePondPluginFileMetadata);
    // $.fn.filepond.registerPlugin(FilePondPluginFilePoster);
    $.fn.filepond.registerPlugin(FilePondPluginImageExifOrientation);
    // $.fn.filepond.registerPlugin(FilePondPluginImagePreview);
    var defaults = {
        allowFileTypeValidation: true
        // allowFilePoster: true,
        // allowImagePreview: true,
        // imagePreviewHeight: 60,
        // imagePreviewMaxHeight: 60,
        // imagePreviewMaxFileSize: '2MB'
    };
    $.fn.filepond.setDefaults(defaults);

    // Ensure it is run
    $('[data-module="filepond"]').ModularBehaviour();
})(jQuery);
