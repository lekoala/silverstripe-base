<?php

// Add styles selector, if you have an editor.css, styles will be use
// @link https://docs.silverstripe.org/en/4/developer_guides/customising_the_admin_interface/typography/
\SilverStripe\Forms\HTMLEditor\TinyMCEConfig::get('cms')
    ->addButtonsToLine(1, 'styleselect')
    ->addButtonsToLine(2, 'anchor')
    ->enablePlugins('anchor')
    ->setOption('statusbar', false)
    ->setOption('importcss_append', true);

// GraphQL performance
// See _config/controllers.yml
// @link https://github.com/silverstripe/silverstripe-graphql/issues/192
// if (Director::isLive()) {
//     \SilverStripe\GraphQL\Controller::remove_extension(\SilverStripe\GraphQL\Extensions\IntrospectionProvider::class);
// }
