# Plug-in Codex Generator #
**Contributors:** stephenh1988
  
**Donate link:** stephenharris.info
  
**Tags:** documentation
  
**Requires at least:** 3.3
  
**Tested up to:** 3.4.2
  
**Stable tag:** 1.0.1
  
**License:** GPLv2 or later
  

Plug-in Codex Generator generates documention for your plug-in based on in-source documentation.

## Description ##

Creating and maintaining documentation pages for your plug-in can be tedious. This plug-in helps automate that process by generating documentation from sourcode comments. 

**Disclaimer:** I still consider this plug-in to be in its early stages, but I wanted to give it an airing and hopefully encourage others to contribute.

### How It Works

To create documentation for you plug-in follow these easy steps:

1. Ensure the plug-in you wish to document is located in your `wp-content` folder and properly documenated according to [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc).
2. Go to the 'functions' or 'hooks' submenu page underneath 'Plug-in Docs', click screen options and select your plug-in.
3. Select which functions (or hooks) you wish to document and click 'Generate documentation'.
4. Posts of `pcg_function` or `pcg_hook` type will be created for each function/hook. If the page already exists, it shall be updated.
5. The generated or updated posts shall appear under 'Function pages' and 'Hook pages' respectively.
6. (Optional) Once the pages are published, regenerate the documentation again to ensure that the pages link to each other where appropriate

The content of the generated posts will be automatically overwritten each time you 'regenerate' the documentation. A second editor is provided for 'persistant' information which is not overwritten. You can use this, for instance, to include extra information or examples.

**Functions with the tags @ignore or '@access private' are ignored by the plug-in**

Hooks are provided. In fact you can view the available hooks by going to the 'hooks' page and selecting Plug-in Codex Generator from the screen options. These hooks include a couple of filters that allow you to add or remove files that are parsed. 'Whitelisting' the plug-in files you want parsed will generally improve performance. 


### Known Issues
The following is an incomplete list of known bugs or limitations - feel free to get involved and contribute :)

 * **This plug-in does not currently document classes or their methods**
 * Hooks need improved parsing. Should we create docbloc parsing for hooks?
 * Not confirmed, but a hook with array($this,'callback') or array(__CLASS__,'callback') as an argument will just be interpreted as $array.
 * Doesn't document all tags. E.g. @access
 * When applying a filter callback to $this from within a class. The generated docs will just have '$this' as an argument - would be nice to treat it
 * differently from a normal variable. But this is not easy.
 * Although you can generate documents for multiple plug-ins, there is currently no way of discerning them once the documents are generated. This would need
 * a extra taxonomy for pcg_hook & pcg_function.

## Installation ##

1. Upload `plugin-codex-generator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` menu in WordPress


## Frequently Asked Questions ##



## Changelog ##

### 1.0.1 ###
* Flushes rewrite rules on activate/deactivate.
* Adds filter for options since there is limited UI for options.

### 1.0 ###
* Initial release.


## Upgrade Notice ##


