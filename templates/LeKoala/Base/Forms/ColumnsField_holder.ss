<div <% if $Name %>id="$Name"<% end_if %> class="<% if $extraClass %>$extraClass<% end_if %>">
	<% if $Title %><label class="left">$Title</label><% end_if %>

	<div class="middleColumn row<% if $Zebra %> $Zebra<% end_if %>">
		<% loop $FieldList %>
			<div class="$Up.ColumnClass($Pos) $FirstLast $EvenOdd">
				$FieldHolder
			</div>
		<% end_loop %>
	</div>
	<% if $RightTitle %><label class="right">$RightTitle</label><% end_if %>
	<% if $Message %><span class="message $MessageType">$Message</span><% end_if %>
	<% if $Description %><span class="description">$Description</span><% end_if %>
</div>
