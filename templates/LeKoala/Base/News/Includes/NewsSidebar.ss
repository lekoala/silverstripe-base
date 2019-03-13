<aside class="NewsSidebar">

    <!-- Search -->
    <div class="NewsSidebar-Widget NewsSidebar-SearchBox">
        <form method="get" action="$Link(search)">
            <div class="form-group">
                <input type="search" name="q" value="" placeholder="<%t NewsSidebar.SEARCH "Search" %>" required>
                <button type="submit"><span class="ei-search"></span></button>
            </div>
        </form>
    </div>

    <!-- Categories -->
    <div class="NewsSidebar-Widget NewsSidebar-Categories">
        <div class="NewsSidebar-Title"><h2><%t NewsSidebar.CATEGORIES "Categories" %></h2></div>

        <ul>
            <% loop Categories %>
            <li><a href="$Link">$Title</a></li>
            <% end_loop %>
        </ul>

    </div>

    <!-- Popular Posts -->
    <div class="NewsSidebar-Widget NewsSidebar-PopularPosts">
        <div class="NewsSidebar-Title"><h2><%t NewsSidebar.POPULAR_POSTS "Popular posts" %></h2></div>

        <% loop PopularItems %>
        <article class="post">
            <figure class="post-thumb"><a href="$Linkl"><img src="$Image.FillMax(80,80).URL" alt="" /></a></figure>
            <div class="text"><a href="$Link">$Title</a></div>
            <div class="post-info">$Published.Nice</div>
        </article>
        <% end_loop %>

    </div>

    <!--Archive Widget-->
    <div class="NewsSidebar-Widget NewsSidebar-Archives">
        <div class="NewsSidebar-Title">
            <h2><%t NewsSidebar.ARCHIVES "Archives" %></h2>
        </div>
        <ul>
            <% loop ArchivesList %>
            <li><a href="$Link">$Title</a></li>
            <% end_loop %>
        </ul>
    </div>

    <!-- Popular Tags -->
    <% if TagsList %>
    <div class="NewsSidebar-Widget NewsSidebar-Tags">
        <div class="NewsSidebar-Title">
            <h2><%t NewsSidebar.TAGS "Tags" %></h2>
        </div>
        <% loop TagsList %>
        <a href="{$Top.Link}tags/$URLSegment">$Title</a>
        <% end_loop %>
    </div>
    <% end_if %>
</aside>
