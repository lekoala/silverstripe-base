<section id="sitesearch">
    <div class="container">
        <h1>$Title</h1>
        <% if $Query %>
            <p class="searchQuery"><%t Page_results.YOUSEARCHEDFOR "Vous avez cherché '{Query}'" Query=$Query %></p>
        <% end_if %>

        <% if $Results %>
        <ul id="searchResults">
            <% loop $Results %>
            <li class="searchItem">
                <h4>
                    <a href="$Link">
                        <% if $MenuTitle %>
                        $MenuTitle
                        <% else %>
                        $Title
                        <% end_if %>
                    </a>
                </h4>
                <% if $Content %>
                    <p>$Content.LimitWordCount</p>
                <% end_if %>
                <a class="readMoreLink" href="$Link"><%t Page_results.READMORE "Lire plus à propos de '{Title}'..." Title=$Title %></a>
            </li>
            <% end_loop %>
        </ul>
        <% else %>
        <p><%t Page_results.NORESULTS "Aucun résultat pour cette recherche." %></p>
        <% end_if %>

        <% include Pagination List=$Results %>
    </div>
</section>
