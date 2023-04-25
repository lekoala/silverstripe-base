<cleave-input<% if InputType %> type="$InputType"<% end_if %> data-config='$getConfigAsJson'>
    <input $AttributesHTML('class') class="form-control $extraClass" <% include SilverStripe/Forms/AriaAttributes %> />
</cleave-input>
