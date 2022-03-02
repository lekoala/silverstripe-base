(function ($) {
  $.entwine("ss", function ($) {
    /**
     * Bulkselect table cell behaviours
     */
    $("td.col-FullGridSelect").entwine({
      onmatch: function () {},
      onunmatch: function () {},
      onclick: function (e) {
        // Prevent row click
        e.stopPropagation();
        // Check/uncheck checkbox when clicking cell
        var $cb = $($(e.target).find("input"));
        if (!$cb.prop("checked")) {
          $cb.prop("checked", true);
        } else {
          $cb.prop("checked", false);
        }
        // Required to trigger onchange event for instantSave
        $cb.trigger("change");
      },
    });

    /**
     * Instant save
     */
    var securityID = null;
    var instantSaveURL = null;
    var parentRecord = null;
    $("td.col-FullGridSelect input.FullGridSelect-instantSave").entwine({
      onchange: function (e) {
        var $cb = this;

        if (!securityID) {
          securityID = $("#Form_ItemEditForm_SecurityID").val();
        }
        if (!instantSaveURL) {
          instantSaveURL = $cb.parents(".fullgrid").data("url") + "/instantSave";
        }
        if (!parentRecord) {
          parentRecord = $cb.parents(".fullgrid").data("record");
        }

        // Confirm adding
        if ($cb.data("confirm-message")) {
          res = confirm($cb.data("confirm-message"));
          if (!res) {
            $cb.prop("checked", false);
            return;
          }
        }

        // Do an instant ajax save for this record
        if ($cb.hasClass("FullGridSelect-instantSave")) {
          var res = true;
          if (!$cb.prop("checked")) {
            res = confirm("Are you sure to uncheck this record?");
            if (!res) {
              $cb.prop("checked", true);
            }
          }
          if (res) {
            $.ajax({
              url: instantSaveURL,
              headers: {
                "X-CSRF-TOKEN": securityID,
              },
              type: "POST",
              dataType: "json",
              data: {
                checked: $cb.prop("checked") ? 1 : 0,
                id: $cb.parents("tr").data("id"),
                record: parentRecord,
              },
            }).done(function (res) {});
          }
        }
      },
    });

    /**
     * Fuzzy search on cells
     */
    $("input.FullGridFieldQuickFilter").entwine({
      onmatch: function () {},
      onunmatch: function () {},
      onfocusout: function () {
        this.doFilter();
      },
      onkeyup: function () {
        this.doFilter();
      },
      doFilter: function () {
        var table = this.parents("fieldset").find("table.grid-field__table");
        var val = this.val().toLowerCase();
        table.find("tbody tr").each(function (i, item) {
          var text = item.textContent.toLowerCase();
          item.style.display = text.indexOf(val) === -1 ? "none" : "table-row";
        });
      },
    });
  });
})(jQuery);
