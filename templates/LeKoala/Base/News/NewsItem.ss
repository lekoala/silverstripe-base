<div class="NewsItem">
    <% if ImageID %>
    <div class="NewsItem-Image mb-2">
        <a href="$Link"><img src="$Image.FillMax(820,391).URL" alt="" class="img-fluid" /></a>
    </div>
    <% end_if %>
    <div class="NewsItem-Box">
        <div class="NewsItem-Date">$Published.Nice</div>
        <h3><a href="$Link">$Title</a></h3>
        <div class="NewsItem-Summary">$Summary</div>
        <a class="NewsItem-ReadMore" href="$Link"><%t NewsItem.READ_MORE "Read more" %> <span class="ei-arrow_right"></span></a>
    </div>
</div>
