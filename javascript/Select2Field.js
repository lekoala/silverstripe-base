(function($) {
  $("div.select2 select").entwine({
    onadd: function() {
      var opts = this.data("config");
      opts.createTag = function(params) {
        var term = $.trim(params.term);

        if (term === "") {
          return null;
        }

        // Disallow small tags
        if(term.length <= 2) {
            return null;
        }

        // Disallow numeric tags (which can be confused with IDs)
        if(!isNaN(term - parseFloat(term))) {
            return null;
        }

        return {
          id: term,
          text: term
        };
      };
      this.select2(opts);
    }
  });
})(jQuery);
