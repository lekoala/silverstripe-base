<div class="container">
    <% if SiteConfig.IconID %>
    <div class="text-center my-4 animated fadeIn">
        $SiteConfig.Icon.ScaleHeight(50)
    </div>
    <% end_if %>
    <div class="my-4">
        $Content
    </div>
    <div class="my-4">
        $Form
    </div>
</div>
