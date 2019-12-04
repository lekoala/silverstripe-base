<div class="container">
    <div class="row">
        $Content

        <ul class="accordion">
        <% loop Items %>
        <li class="accordion-block">
            <div class="accordion-title">$Title</div>
            <div class="accordion-content">
                $Content
            </div>
        </li>
        <% end_loop %>
        </ul>
    </div>
</div>
