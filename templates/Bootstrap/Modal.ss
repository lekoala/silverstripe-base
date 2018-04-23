<div class="modal" tabindex="-1" role="dialog"<% if ID %> id="$ID"<% end_if %>>
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">$Title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="<%t BootstrapModal.Close "Close" %>">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        $Content
      </div>
      <% if Actions %>
      <div class="modal-footer">
        <% loop Actions %>
        <button type="button" class="btn btn-primary">$Title</button>
        <% end_loop %>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><%t BootstrapModal.Close "Close" %></button>
      </div>
      <% end_if %>
    </div>
  </div>
</div>
