<div class="container">
    <div class="row my-4">
        <div class="col-md-6 ContactPage-Details">
            <% if ShowInfosOnly %>
            $Infos
            <% else %>
            <%t ContactPage.Address "ADDRESS" %>:<br/>
            <a href="$GoogleMapsLink" target="_blank">$Address</a><br/>
            <% end_if %>

            <% if Phone %>
            <%t ContactPage.Phone "PHONE" %>: <a href="tel:$Phone">$Phone</a><br/>
            <% end_if %>

            <% if Email %>

            <%t ContactPage.Email "E-MAIL" %>: <a href="mailto:$Email">$Email</a><br/>
            <% end_if %>

            <% if ShowInfosOnly %>

            <% else %>
            <br/>
            $Infos
            <% end_if %>

            <% if MapEmbed %>
            <div class="mt-4">
            $MapEmbed.RAW
            </div>
            <% end_if %>
        </div>
        <div class="col-md-6 ContactPage-ContactForm">
            $ContactForm
        </div>
    </div>
    <% if ShowMap %>
    <div class="row mb-4">
        <div class="col-md-12 ContactPage-Map">

        </div>
    </div>
    <% end_if %>
</div>
