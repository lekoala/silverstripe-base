<% if Button %>
<% with Button %>
<a href="$Link" class="btn<% if ExtraClasses %> $ExtraClasses<%end_if %>"<% if NewWindow %> target="_blank"<% end_if %>>
$Title
</a>
<% end_with %>
<% end_if %>
