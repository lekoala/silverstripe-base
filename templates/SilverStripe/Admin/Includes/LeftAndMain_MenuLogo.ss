<div class="cms-sitename vertical-align-items" data-cms-version="$CMSVersion">
    <% if not IsLive %><span class="cms-env-marker">$DirectorEnv</span><% end_if %>
    <a href="$BaseHref" target="_blank"><% if $SiteConfig %>$SiteConfig.Title<% else %>$ApplicationName<% end_if %></a>
</div>
