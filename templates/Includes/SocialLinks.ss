<ul class="SocialLinks $ExtraClasses">
    <% if SiteConfig.Facebook %>
    <li class="SocialLinks-item SocialLinks-facebook" rel="noreferrer">
        <a href="$SiteConfig.FacebookLink" title="Facebook" target="_blank" ><span class="ei-social_facebook"></span></a>
    </li>
    <% end_if %>
    <% if SiteConfig.Twitter %>
    <li class="SocialLinks-item SocialLinks-twitter">
        <a href="$SiteConfig.TwitterLink" title="Twitter" target="_blank" rel="noreferrer"><span class="ei ei-social_twitter"></span></a>
    </li>
    <% end_if %>
    <% if SiteConfig.Youtube %>
    <li class="SocialLinks-item SocialLinks-youtube">
        <a href="$SiteConfig.YoutubeLink" title="Youtube" target="_blank" rel="noreferrer"><span class="ei ei-social_youtube"></span></a>
    </li>
    <% end_if %>
    <% if SiteConfig.Vimeo %>
    <li class="SocialLinks-item SocialLinks-vimeo">
        <a href="$SiteConfig.VimeoLink" title="Vimeo" target="_blank" rel="noreferrer"><span class="ei ei-social_vimeo"></span></a>
    </li>
    <% end_if %>
    <% if SiteConfig.LinkedIn %>
    <li class="SocialLinks-item SocialLinks-linkedin">
        <a href="$SiteConfig.LinkedInLink" title="LinkedIn" target="_blank" rel="noreferrer"><span class="ei ei-social_linkedin"></span></a>
    </li>
    <% end_if %>
    <% if SiteConfig.Instagram %>
    <li class="SocialLinks-item SocialLinks-instagram">
        <a href="$SiteConfig.InstagramLink" title="Instagram" target="_blank" rel="noreferrer"><span class="ei ei-social_instagram"></span></a>
    </li>
    <% end_if %>
    <% if SiteConfig.Pinterest %>
    <li class="SocialLinks-item SocialLinks-pinterest">
        <a href="$SiteConfig.PinterestLink" title="Pinterest" target="_blank" rel="noreferrer"><span class="ei ei-social_pinterest"></span></a>
    </li>
    <% end_if %>
</ul>
