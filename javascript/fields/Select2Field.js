(function ($) {
  ModularBehaviour.addOptionsTransformer("select2", function (opts, el) {
    opts.width = "100%";
    opts.createTag = function (params) {
      var term = $.trim(params.term);

      if (term === "") {
        return null;
      }

      // Disallow small tags
      if (term.length <= 2) {
        return null;
      }

      // Disallow numeric tags (which can be confused with IDs)
      if (!isNaN(term - parseFloat(term))) {
        return null;
      }

      return {
        id: term,
        text: term,
      };
    };
  });
})(jQuery);
