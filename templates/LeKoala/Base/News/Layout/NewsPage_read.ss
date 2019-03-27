<div class="container">
    <div class="row">
        <div class="NewsPage-Main col-lg-8 col-md-8 col-sm-12 col-xs-12">
            <div class="NewsPage-Single">
                <% with Item %>
                <% if ImageID %>
                <div class="NewsPage-MainImage mb-2">
                    <img src="$Image.FillMax(820,391).URL" alt="" class="img-fluid" />
                </div>
                <% end_if %>
                <div class="NewsPage-Box">
                    <div class="NewsPage-Date">$Published.Nice</div>
                    <h3>$Title</h3>
                    <div class="NewsPage-SingleContent">
                        $Content
                    </div>
                    <div class="NewsPage-Share">
                        <% include SocialShare %>
                    </div>
                </div>
                <% end_with %>
            </div>
        </div>
        <div class="NewsPage-Side col-lg-4 col-md-4 col-sm-12 col-xs-12">
            <% include LeKoala/Base/News/NewsSidebar %>
        </div>
    </div>
</div>
