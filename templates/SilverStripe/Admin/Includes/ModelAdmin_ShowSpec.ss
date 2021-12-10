<main>
<h5><%t SilverStripe\Admin\ModelAdmin.IMPORTSPECFIELDS 'Database columns' %></h5>
<% loop $Fields %>
<dl>
    <dt><em>$Name</em></dt>
    <dd>$Description</dd>
</dl>
<% end_loop %>

<h5><%t SilverStripe\Admin\ModelAdmin.IMPORTSPECRELATIONS 'Relations' %></h5>
<% loop $Relations %>
<dl>
    <dt><em>$Name</em></dt>
    <dd>$Description</dd>
</dl>
<% end_loop %>
</main>
