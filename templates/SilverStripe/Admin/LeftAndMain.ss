<!DOCTYPE html>
<html lang="$Locale.RFC1766">
	<head>
	<% base_tag %>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, maximum-scale=1.0" />
    <script src="https://cdn.jsdelivr.net/gh/lekoala/nomodule-browser-warning.js/nomodule-browser-warning.min.js" nomodule defer id="nomodule-browser-warning"></script>
	<title>$Title</title>
</head>
<body class="loading cms" data-frameworkpath="$ModulePath(silverstripe/framework)"
	data-member-tempid="$CurrentMember.TempIDHash.ATT" <% if $GraphQLLegacy %>data-graphql-legacy="1"<% end_if %>
>

    <div class="ss-loading-screen">
        <svg xmlns="http://www.w3.org/2000/svg" class="ss-loading-screen__logo" viewBox="0 0 90 90" width="90" height="90">
            <path class="ss-loading-screen__logo__part ss-loading-screen__logo__top" d="M40.1 39.8c4.3 6.5 2.9 15.4-3.5 20.1-3.9 2.9-8.5 4.4-13.4 4.4-7.2 0-13.8-3.3-18.1-9.1C1.6 50.4.1 44.5 1 38.5c.9-5.9 4-11.2 8.8-14.8L40.3 1.1c4.3 6.5 2.9 15.4-3.5 20.1L18.2 35c-3.7 2.8-4.5 8.1-1.7 11.8 1.6 2.1 4.1 3.4 6.8 3.4 1.8 0 3.6-.6 5-1.7l11.8-8.7z"/>
            <path class="ss-loading-screen__logo__part ss-loading-screen__logo__bot" d="M49.9 50.2c-4.3-6.5-2.9-15.4 3.5-20.1 3.9-2.9 8.5-4.4 13.4-4.4 7.2 0 13.8 3.3 18.1 9.1 3.6 4.8 5.1 10.8 4.2 16.7-.9 5.9-4 11.2-8.8 14.8L49.7 88.9c-2-3.1-2.9-6.7-2.3-10.4.6-3.9 2.6-7.4 5.8-9.7L71.8 55c1.8-1.3 3-3.3 3.3-5.6.3-2.2-.2-4.5-1.6-6.3-1.6-2.1-4.1-3.4-6.8-3.4-1.8 0-3.6.6-5 1.7l-11.8 8.8z"/>
        </svg>

        <h3 class="ss-loading-screen__text">
            loading
            <svg xmlns="http://www.w3.org/2000/svg" class="ss-loading-screen__dots" viewBox="0 0 10 3" width="10" height="3">
                <circle cx="1" cy="1.75" r="1.25" class="ss-loading-screen__dot ss-loading-screen__dot--1"/>
                <circle cx="5.5" cy="1.75" r="1.25" class="ss-loading-screen__dot ss-loading-screen__dot--2"/>
                <circle cx="10.5" cy="1.75" r="1.25" class="ss-loading-screen__dot ss-loading-screen__dot--3"/>
            </svg>
        </h3>
        <noscript><p class="nojs-warning alert alert-warning"><%t SilverStripe\\Admin\\LeftAndMain.REQUIREJS 'The CMS requires that you have JavaScript enabled.' %></p></noscript>
    </div>

	<div class="cms-container" data-layout-type="custom">
		$BaseMenu
		$Content
		$PreviewPanel
    </div>
</body>
</html>
