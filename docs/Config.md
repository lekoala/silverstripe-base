# Config

## Starter config

Here is a sample mysite.yml config

Make sure you include the "after" part to make sure your config
is not overriden by the base config

    ---
    Name: myproject
    After:
        - '#base-extensions'
    ---
    SilverStripe\Core\Manifest\ModuleManifest:
        project: app
    SilverStripe\Assets\File:
        allowed_extensions:
            - svg
    LeKoala\Base\Admin\BaseLeftAndMainExtension:
        dark_theme: true
        help_enabled: false
        removed_items:
            - SilverStripe-CampaignAdmin-CampaignAdmin
            - SilverStripe-VersionedAdmin-ArchiveAdmin
    SilverStripe\Control\Email\Email:
        admin_email: admin@mywebsite.com
        tech_email: tech@agency.com
    LeKoala\Base\i18n\BaseI18n:
        default_locales:
            - en_US
            - fr_FR

And here is a sample theme.yml

    ---
    Name: mytheme
    After:
    - '#base-theme'
    ---
    SilverStripe\View\SSViewer:
        themes:
        - '$public'
        - 'mythemehere'
        - '$default'
    SilverStripe\SiteConfig\SiteConfig:
        auto_include_css: false
    LeKoala\Base\Faq\FaqPageController:
        theme_files: true
    LeKoala\Base\Contact\ContactPageController:
        theme_files: true
