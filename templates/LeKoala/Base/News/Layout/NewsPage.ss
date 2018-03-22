<div class="container">
    <div class="row">
        <div class="NewsPage-Main col-lg-8 col-md-8 col-sm-12 col-xs-12">
            <div class="NewsPage-List">
                <% if PaginatedList.exists %>
                <% loop PaginatedList %>
                <div class="mb-4">
                $Me
                </div>
                <% end_loop %>
                <% else %>
                <p><%t NewsPage.NO_ARTICLES "No articles" %></p>
                <% end_if %>
            </div>

            <% include Pagination List=$PaginatedList %>
        </div>
        <div class="NewsPage-Side col-lg-4 col-md-4 col-sm-12 col-xs-12">
            <% include LeKoala/Base/News/NewsSidebar %>
        </div>
    </div>
</div>
