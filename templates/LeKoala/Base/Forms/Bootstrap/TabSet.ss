<div $getAttributesHTML("class") class="$extraClass">
	<ul class="nav nav-tabs" role="tablist">
	  <% loop $Tabs %>
		<li class="$FirstLast $MiddleString $extraClass nav-item">
			<a href="#$id" id="tab-$id" class="nav-link<% if Selected %> active<% end_if %>" data-toggle="tab" role="tab" aria-controls="profile" aria-selected="<% if Selected %>true<% else %>false<% end_if %>">$Title</a>
		</li>
	  <% end_loop %>
	</ul>

	<div class="tab-content tab-content-default">
	  <% loop $Tabs %>
		  <% if $Tabs %>
			$FieldHolder
		  <% else %>
			<div $getAttributesHTML("class") class="tab-pane $extraClass<% if Selected %> show active<% end_if %>">
				<% loop $Fields %>
					$FieldHolder
				<% end_loop %>
			</div>
		  <% end_if %>
	  <% end_loop %>
	</div>
</div>
