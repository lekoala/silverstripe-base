 <% if $List.MoreThanOnePage %>
<div class="pagination">
    <ul>
        <% if $List.NotFirstPage %>
        <li class="page-item"><a class="page-link page-prev" href="$List.PrevLink">
            <span class="ei ei-arrow_carrot-left"></span></a>
        </li>
        <% end_if %>
        <% loop $List.Pages %>
        <li class="page-item"><a class="page-link" href="$Link" class="<% if $CurrentBool %>active<% end_if %>">$PageNum</a></li>
        <% end_loop %>
        <% if $List.NotLastPage %>
        <li class="page-item"><a class="page-link page-next" href="$List.NextLink">
            <span class="ei ei-arrow_carrot-right"></span></a>
        </li>
        <% end_if %>
    </ul>
</div>
<% end_if %>






