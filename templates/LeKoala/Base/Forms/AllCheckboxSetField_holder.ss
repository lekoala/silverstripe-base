<div id="$HolderID" class="field<% if $extraClass %> $extraClass<% end_if %>">
	<% if $Title %><label class="left" for="$ID">$Title</label><% end_if %>
    <button class="check-all" onclick="jQuery(this).hide().parents('.field').find('.middleColumn input').attr('checked','checked');jQuery(this).parent().find('.uncheck-all').show();">Check all</button>
    <button class="uncheck-all" style="display:none" onclick="jQuery(this).hide().parents('.field').find('.middleColumn input').removeAttr('checked');jQuery(this).parent().find('.check-all').show();">Uncheck all</button>
	<div class="middleColumn">
		$Field
	</div>
	<% if $RightTitle %><label class="right" for="$ID">$RightTitle</label><% end_if %>
	<% if $Message %><span class="message $MessageType">$Message</span><% end_if %>
	<% if $Description %><span class="description">$Description</span><% end_if %>
</div>
