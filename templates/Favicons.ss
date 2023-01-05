<% cached 'Favicons', $SiteConfig.FaviconID, $SiteConfig.LastEdited  %>
<% if $SiteConfig.FaviconID %>
<% if not PwaIconsPath %><link rel="apple-touch-icon" sizes="180x180" href="/assets/_theme/$SiteConfig.ID/apple-touch-icon.png"><% end_if %>
<link rel="icon" type="image/png" sizes="32x32" href="/assets/_theme/$SiteConfig.ID/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/_theme/$SiteConfig.ID/favicon-16x16.png">
<link rel="mask-icon" href="/assets/_theme/$SiteConfig.ID/safari-pinned-tab.svg" color="$MaskColor">
<% end_if %>
<% if ThemeColor %>
<meta name="msapplication-TileColor" content="$ThemeColor">
<meta name="theme-color" content="$ThemeColor">
<% end_if %>
<% end_cached %>
