<div class="alert alert-$Type<% if Dismissible %> alert-dismissible<% end_if %> js-alert fade show" role="alert" data-id="$ID">
  $Content.RAW
  <% if Dismissible %>
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
    <% end_if %>
</div>
