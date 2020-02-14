# Blocks

## Working with blocks

We have a blocks page that allows managing content by block instead of the big "content thing"

We have a default block, the "Content Block" that allow setting a title, a content and an image

Blocks are all the same DataOBject : the Block.php
Content is stored as json, so there is no need to create extension of this class. Instead,
we simply create new Types of block that use different templates for rendering.

## Creating new block types

Typically, you want to integrate some new block based on a given html content

Start to create a new block (it can be in your app or your theme) in:

    /app/templates/Blocks/MyNewBlock.ss

Then you can call the task

    /dev/tasks/BlocksCreateTask

It will create the php class if missing and scss styles in your theme provided you have a scss directory

## Use the fluent field builder

Given the data is stored in json blocks, adding field is not very convient

You can do it manually with this

     $fields->push(new TextField('BlockData[Title]', 'Title'));

But it's really ugly to have to call this BlockData[Title] thing

Instead, you can have a much nicer

     $fields->addText('Title');

Please refer to BlockFieldList for all available methods it should cover most common cases
For advanced usage, you might need to extend it in your your project to deal with
your custom field types

By default, the Image field is visible (inherited from the BaseBlock). If you find this
not convenient, feel free to remove it

    $fields->removeByName('Image');

WARNING : as a safety measure, avoid naming conflicts with existing Block.php fields
like Content. This is why the default value is "Description" in addEditor().

## Data and settings

There are two tabs : the Main tab and the Settings tab. Basically, anything inside the main
tab should be localizable and anything in the setting tab should be fixed.

In order to add a setting, you can add fields to the setting tab like this

        $fields->addSettings(function (BlockFieldList $fields) {
            $fields->addCheckbox('MySetting');
        });

Basically, what this helper does is setting the fluent builder key to Settings and set the
current tab to Settings and reset everything after.

## Dealing with basic collections (aka Items)

For basic collections, we can still store everything inside our json blob

In this example, I add 3 items with Name, Email, Description and Image

    foreach (range(1, 3) as $i) {
        $fields->addHeader("Item $i");
        $fields->addText([$i, 'Name']);
        $fields->addText([$i, 'Email']);
        $fields->addEditor([$i,'Description']);
        $fields->addUpload([$i, 'ImageID'], "Image"); // don't forget the ID suffix
    }

Please note the [] array notation we use. Instead of passing the name as a string, we pass and array with the index
and the name as the second argument. Our BlockFieldList knows how to deal with this.

And you can freely loop over them thanks to our special $Items (defined as a const ITEM_KEYS in Blocks.php)
Assets (files and images) are automatically published if used in the block

    <% loop $Items %>
    <div class="col col-$Columns" id="item-$Counter">
    $Name <$Email><br/>
    $Description
    <img src="$Image.Link">
    </div>
    <% end_loop %>

It includes default iterator values (FirstLast, Pos) but also specials one (Total, Counter and Columns).
- Total : the total number of items in the set
- Counter : the total number of items in the page
- Columns : a 12 columns based number (ideal for Bootstrap like usages)

Nice!

## Dealing with Files & Images

By default, you have a many_many Files and Images on each blocks. You can easily add these fields with

    $fields->addImages();

or

    $fields->addFiles();

Useful for sliders, attached documents...

These are sortable by default thanks to bummzack/sortablefile and can be used in the templates with

    <% loop $SortedImages %>

    <% end_loop %>

## Dealing with relations

You might wonder what are doing these

    public function Collection()
    {
        return false;
    }

    public function SharedCollection()
    {
        return false;
    }

They return false by default to avoid any issue in the templates. But they
can return any DataList or ArrayList that might be relevant depending
on what your block does.

Like a Block for displaying latest blog posts, it could be

    public function SharedCollection()
    {
        return BlogPost::get()->limit(3);
    }

Please note of the difference between Collections and SharedCollections

- Collection : data is filtered for the given block. It's specific and require a has_one relation from the DataObject to the Block
- SharedCollection : data is the same for all blocks of this type

## Other templating stuff

### Menus

you can generated anchored base menus with the following snippet. Each block can have it's own id (Settings tab, based on MenuTitle if empty)

    <ul>
    <% loop MenuAnchorsItems %>
    <li><a href="$Link">$Title</a></li>
    <% end_loop %>
    </ul>

### Overriding the default Content block

You can easily override the default template by adding your own template in /app/templates/Blocks/ContentBlock.ss

Default template is:

    $Description.RAW

    <% if Image %>
    $Image
    <% end_if %>

    <% if ButtonID %>
    <% include BlockButton %>
    <% end_if %>

### Buttons

TODO

### Pages

All block content is rendered into the Content field of the page. This is needed in order to keep search working without adjustement.

Each block represent a section, something like this

    <section id="MyHTMLID" class="Block Block-MyBlock">
    Here is the block content based on your template
    </section>

You can disable to prevent this automatic "section" stuff if you have a more custom need by disabling the wrap block config

    LeKoala\Base\Blocks\BlocksPage:
      wrap_blocks: false

### Extra data

Since block types are not DataObjects, their methods are not exposed to the template, only their data

Basically, we take all the data fields, all the settings and merge them together before calling "renderWith" function

You can expose additionnal data to the template by using ExtraData

    function ExtraData() {
        return [
            'Hello' => 'World
        ];
    }

And then call

    Hello $Hello

in your templates

### Casting

If you store html in your blocks, it's not going to be casted properly. So ensure to use .RAW in your templates

    $Description.RAW

### Previewing blocks

It can be a bit tedious to save and publish each time you change a block in order to refresh the $Content variable of the page
This is why in Dev mode you can pass ?live=1 as a url parameter in order to fully refresh all blocks content
when displaying the page

### Context

Keep in mind that html for the blocks are generated on save, in the admin. That means that some context stuff (Sessions, Controller::curr)
may not work as you may think. Try to be as stateless as possible to avoid surprises!

## Use Query

The blocks give you a powerful accessor: the $Query method. In your template
you can loop over ANY DATAOBJECT. Please be very cautious with this.

    <% loop Query(TeamMembers) %>
    $Name
    <% end_loop %>
