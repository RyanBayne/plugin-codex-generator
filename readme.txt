=== Plug-in Codex Generator ===
Contributors: stephenh1988
Donate link: stephenharris.info
Tags: documentation
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 1.0.6
License: GPLv2 or later

Plug-in Codex Generator generates documention for your plug-in based on in-source documentation.

== Description ==

Creating and maintaining documentation pages for your plug-in can be tedious. This plug-in helps automate that process by generating documentation from sourcode comments. 

**Disclaimer:** I still consider this plug-in to be in its early stages, but I wanted to give it an airing and hopefully encourage others to contribute.

This plug-in is based on the code and ideas in [**Codex Generator**](http://wordpress.org/extend/plugins/codex-generator/) by [Rarst](http://www.rarst.net/).

### How It Works

To create documentation for you plug-in follow these easy steps:

1. Ensure the plug-in you wish to document is located in your `wp-content` folder and properly documenated according to [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc).
2. Go to the 'functions' or 'hooks' submenu page underneath 'Plug-in Docs', click screen options and select your plug-in.
3. Select which functions (or hooks) you wish to document and click 'Generate documentation'.
4. Posts of `pcg_function` or `pcg_hook` type will be created for each function/hook. If the page already exists, it shall be updated.
5. The generated or updated posts shall appear under 'Function pages' and 'Hook pages' respectively.
6. (Optional) Once the pages are published, regenerate the documentation again to ensure that the pages link to each other where appropriate

The content of the generated posts will be automatically overwritten each time you 'regenerate' the documentation. A second editor is provided for 'persistant' information which is not overwritten. You can use this, for instance, to include extra information or examples.

**Functions with the tags @ignore or '@access private' are ignored by the plug-in.** `@access private` function will not be ignored in the future, but labelled as private. `@ignore` is the recommended method for telling the plug-in to ignore a function.

Hooks are provided. In fact you can view the available hooks by going to the 'hooks' page and selecting Plug-in Codex Generator from the screen options. These hooks include a couple of filters that allow you to add or remove files that are parsed. 'Whitelisting' the plug-in files you want parsed will generally improve performance (see the FAQ).


### Known Issues
The following is an incomplete list of known bugs or limitations - feel free to get involved and contribute :)

 * **Out of memory?** - see FAQ
 * **This plug-in does not currently document classes or their methods**
 * Hooks need improved parsing. Should we create docbloc parsing for hooks?
 * Not confirmed, but a hook with `array($this,'callback')` or `array(\__CLASS\__,'callback')` as an argument will just be interpreted as $array.
 * Doesn't document all tags. E.g. @access
 * When applying a filter callback to $this from within a class. The generated docs will just have '$this' as an argument - would be nice to treat it differently from a normal variable. But this is not easy.
 * Although you can generate documents for multiple plug-ins, there is currently no way of discerning them once the documents are generated. This would need a extra taxonomy for pcg_hook & pcg_function.


== Installation ==

1. Upload `plugin-codex-generator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` menu in WordPress


== Frequently Asked Questions ==

= Can I use Markdown in the DocBloc? =
Yes - if you have [WP-MarkDown](http://wordpress.org/extend/plugins/wp-markdown/) activated - the description part of a docbloc will support MarkDown. You should ensure each line of the description begins with (an optional space), then an aesterix and then a space.


= The generated documentation is incomplete or looks mess - what went wrong? =

First ensure that you're documentating your functions according to [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc). Sometimes the parser can be quite strict. For instance, if parameters are missing, ensure that the name given in @param is the name used when defining the function. If you're convinced you've found a bug please use the GitHub issues page, providing the docbloc, the output and an explanation of what's wrong and/or what you were expecting.


= How Do I Display A List of Hooks / Functions =

You can use the functions `plugincodex_get_functions()` and `plugincodex_get_hooks()` respectively.  Alternatively, for pre-formatted list you can use the `[plugin_codex]` shortcode.

For example:

     //List all functions in the package 'foobar'
     [plugin_codex package=foobar]

     //List all hooks in the package 'foobar'
     [plugin_codex object=hook]

     //List all filters (hooks)
     [plugin_codex object="hook" type="filter"]

These list the generated documentation pages (with links) of the functions/hooks. The filter `plugincodex_plugin_codex_shortcode` allows you to alter the mark-up generated by the shortcode.


= I Ran Out Of Memory - What Do I Do? =

Generally there are files that contain no functions or no functions you wish to be documentated. By whitelisting the files you *do* want parsed, you save on resources. There's a hook for that. It passes the relative (to `wp-content`) file paths of the plug-in, the plug-in being parsed, and also the query arguments. 

     add_filter('plugincodex_relative_plugin_paths', 'myplugin_parse_files',10,3);
     function myplugin_parse_files($files,$plugin,$args){

          if( $plugin != 'my-plugin/my-plugin.php')
                    return $files;

          $files = array_intersect($files, array( 
                    'my-plugin/some/path/to/a/file.php',
                    'my-plugin/some/path/to/another/file.php',
               ));
          return $files;
     }

There is also `plugincodex_absolute_plugin_paths` hook that fires after this and filters the absolute paths. 

= What Docbloc Tags Can I use? =

The following are supported

* `@param` and * `@return`
* `@deprecated` - it's recommended that you use `@see` to denote the replacement
* `@see` - note if using `@deprecated` then the first instance of `@see` will be assumed to be the replacement function. The plug-in will also try to 'auto-link' to objects reference by `@see`. For this reason you should append function names with `()`.
* `@since`
* `@uses` and 
* `@used-by`
* `@link` - this is also supported inline: `{@link url descriptoin}` e.g. `{@link www.example.com Example Link}`
* `@package`
* `@ignore` - to ignore the function
* `@access private` - current the parser ignores private functions too


== Changelog ==

= 1.0.6 =
* Auto-link @see elements (tags and in-line). Function names should end with `()`.

= 1.0.5 =
* Refactored code. Still needs some work. Updated documentation.

= 1.0.4 =
* Added MarkDown support for DocBloc descriptions when WP-MarkDown is activated.

= 1.0.3 =
* Add (filtered) error classes to 'deprecated' notices for styling.
* Update readme with examples of how to whitelist plug-in files.

= 1.0.2 =
* Allows filtering & querying functions by package

= 1.0.1 =
* Flushes rewrite rules on activate/deactivate.
* Adds filter for options since there is limited UI for options.

= 1.0 =
* Initial release.


== Upgrade Notice ==


