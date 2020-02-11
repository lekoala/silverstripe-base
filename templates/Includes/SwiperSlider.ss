<div class="swiper-container">
    <div class="swiper-wrapper">
    <% loop Slides %>
        <div class="swiper-slide">
            <%-- render the content of your slide here using forTemplate on slider items --%>
            $Me
        </div>
    <% end_loop %>
    </div>
    <% if not HidePagination %>
    <div class="swiper-pagination"></div>
    <% end_if %>
    <% if not HideNavigation %>
    <div class="swiper-button-next swiper-button-white"></div>
    <div class="swiper-button-prev swiper-button-white"></div>
    <% end_if %>
</div>
