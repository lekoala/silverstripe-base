<!DOCTYPE html>
<html lang="$ContentLocale"<% if $i18nScriptDirection %> dir="$i18nScriptDirection"<% end_if %>>
<head>
	<% base_tag %>
	<title><% if $MetaTitle %>$MetaTitle<% else %>$Title<% end_if %> | $SiteConfig.Title</title>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	$MetaTags(false)
    $SiteConfig.Favicons
</head>

<body class="$BodyClass">

<div class="wrapper typography">
	<% include Header %>
    <div class="Layout">
    $Layout
    </div>
	<% include Footer %>
</div>

</body>
</html>
