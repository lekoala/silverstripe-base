<div class="cms-subsites" data-pjax-fragment="SubsiteList">
	<div class="field dropdown">
		<select id="SubsitesSelect">
			<% loop $ListSubsitesExpanded %>
				<option value="$ID" $CurrentState style="color:$Color;background:$BackgroundColor">$Title.RAW</option>
			<% end_loop %>
		</select>
	</div>
</div>
