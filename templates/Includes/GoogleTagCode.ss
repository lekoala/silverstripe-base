<% if SiteConfig.shouldRequireGoogleAnalytics %>
<!-- Global Site Tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=$SiteConfig.GoogleAnalyticsCode"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '$SiteConfig.GoogleAnalyticsCode');
</script>
<% end_if %>
