# Theming

## SiteConfig

In the SiteConfig, a couple of new settings appear in the Theme tab

- Your website colors
- Your website fonts
- Which custom theme to apply

## Custom css theme

All css files in your theme ending with -theme.css will be considered as
"themable css file".

A themable css file is a special kind of file:
- It can be selected through the SiteConfig (ex: classic-theme, modern-theme)
- Values from the SiteConfig can be used to set values

To keep a standard behaviour, we use css3 vars as a default implementation.

For instance, this is a sample scss file

    :root {
        --header-font-family: 'Source Sans Pro', sans-serif;
        --header-font-weight: 300;
        --body-font-family: 'Source Sans Pro', sans-serif;
        --body-font-weight: 400;
        --primary-color: $primary;
        --secondary-color: $secondary;
    }
    body {
        font-family: var(--body-font-family);
        font-weight: var(--body-font-weight);
    }

    h1,h2,h3,h4,h5,h6 {
        font-family: var(--header-font-family);
        font-weight: var(--header-font-weight);
    }

You can define css3 variables. The dash notation will be replaced by a camelcase notation so:
--header-font-family will match HeaderFontFamily in your SiteConfig

All var() declaration will be replaced according to the value in the SiteConfig.

In the example above, we have even set a default value for primary and secondary colors in case
they are not set in the SiteConfig.

Font usage expect either your theme to include the fonts using @import or use the SiteConfig option
if your theme allows to change fonts.

## Minifier

It is recommended to install a minifier to ensure generated css files are optimized for production use

    composer require axllent/silverstripe-minifier
