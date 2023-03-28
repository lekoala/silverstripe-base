<ul $AttributesHTML>
	<% if $Options.Count %>
		<% loop $Options %>
			<li class="$Class $Up.extraItemClass">
				<input id="$ID" class="checkbox form-check-input" name="$Name" type="checkbox" value="$Value.ATT"<% if $isChecked %> checked="checked"<% end_if %><% if $isDisabled || $Up.isReadonly %> disabled="disabled"<% end_if %> />
				<label for="$ID">$Title</label>
			</li>
		<% end_loop %>
	<% else %>
		<li>No options available</li>
	<% end_if %>
</ul>
