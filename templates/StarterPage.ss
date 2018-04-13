<!DOCTYPE html>
<html lang="$ContentLocale">
<head>
	<% base_tag %>
	<title><% if $MetaTitle %>$MetaTitle<% else %>$Title<% end_if %> | $SiteConfig.Title</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	$MetaTags(false)
    $SiteConfig.Favicons
</head>

<body class="$BodyClass" <% if $i18nScriptDirection %>dir="$i18nScriptDirection"<% end_if %>>

<div class="wrapper">
	<% include Header %>
    <div class="page">
    $Layout
    </div>
	<% include Footer %>
</div>

</body>
</html>
