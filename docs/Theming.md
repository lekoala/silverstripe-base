# Theming

## SiteConfig

In the SiteConfig, a couple of new settings appear in the Theme tab

- Your website colors
- Your website fonts
- Which custom css theme to apply

## Custom css theme

All css files in your theme ending with -theme.css will be considered as
"themable css file".

A themable css file is a special kind of file:
- It can be selected through the SiteConfig (ex: classic-theme, modern-theme)
- Values from the SiteConfig can be used to set values

To keep a standard behaviour, we use css3 vars as a default implementation. You can find
a sample file in sass/_sample-theme.scss.

Css3 variables will be matched to the SiteConfig values. The dash notation will be replaced by a camelcase notation so:
--header-font-family will match HeaderFontFamily in your SiteConfig.

Colors also have the following variants: contrast, highlight, muted, accessible through --primary-color-{variant} convention.

In case no value are defined in the SiteConfig, the default value of the css3 variable declaration will be used. It
is therefore unecessary to specify defaults by using var(varname, default);

Font usage expect either your theme to include the fonts using @import or use the SiteConfig option
if your theme allows to change fonts.

This module also provide css3 overrides for Bootstrap 4 to allow theming without recompiling the whole framework.
This is very useful if you allow selecting primary color in the SiteConfig and expected components to be styled accordingly.
You can find the theme file in sass/bootstrap-extras/_theme.scss. Please note all components might not be compatible.

## Disabling theme options

In your -theme files, you can specify the following parameters in comments:
- /* @disallowFonts */ : Disable font selection. Useful if the font is hardwired through import statements.
- /* @disallowColors */ : If you don't support proper theming through css3 variables and use a dedicated build step.

## Minifier

It is recommended to install a minifier to ensure generated css files are optimized for production use

    composer require axllent/silverstripe-minifier
