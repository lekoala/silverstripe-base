<div class="container">
    <% if Content %>
    <div class="row my-4">
        <div class="col-md-12">$Content</div>
    </div>
    <% end_if %>
    <div class="row my-4">
        <div class="col-md-6 ContactPage-Details">
            <% if ShowInfosOnly %>
            $Infos
            <% else %>
                <% if Address %>
                <span class="ContactPage-Label"><%t ContactPage.Address "ADDRESS" %>:</span>
                <a href="$GoogleMapsLink" target="_blank">$Address</a><br/>
                <% end_if %>

                <% if Phone %>
                <span class="ContactPage-Label"><%t ContactPage.Phone "PHONE" %>:</span>
                <a href="tel:$Phone">$Phone</a><br/>
                <% end_if %>

                <% if Email %>
                <span class="ContactPage-Label"><%t ContactPage.Email "E-MAIL" %>:</span>
                <a href="mailto:$Email">$Email</a><br/>
                <% end_if %>
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
