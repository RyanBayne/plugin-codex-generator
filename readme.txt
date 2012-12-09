=== Plug-in Codex Generator ===
Contributors: stephenh1988
Donate link: stephenharris.info
Tags: documentation
Requires at least: 3.3
Tested up to: 3.4.2
Stable tag: 1.0
License: GPLv2 or later

Plug-in Codex Generator generates documention for your plug-in based on in-source documentation.

== Description ==

Creating and maintaining documentation pages for your plug-in can be tedious. This plug-in helps automate that process by generating documentation from sourcode comments. 

To create documentation for you plug-in follow these easy steps:

1. Ensure the plug-in is located in your `wp-content` folder
2. Go to the 'functions' or 'hooks' submenu page underneath 'Plug-in Codex', click screen options and select your plug-in.
3. Select which functions (or hooks) you wish to document and click 'Generate documentation'.
4. Posts of `pcg_function` or `pcg_hook` type will be created for each function/hook. If the page already exists, it shall be updated.
5. The generated or updated posts shall appear under 'Function pages' and 'Hook pages' respectively.

The content of the generated posts will be automatically overwritten each time you 'regenerate' the documentation. A second editor is provided for 'persistant' information which is not overwritten. You can use this, for instance, to include extra information or examples.

**This plug-in does not currently document classes**

Hooks are provided. In fact you can view the available hooks by going to the 'hooks' page and selecting Plug-in Codex Generator from the screen options. These hooks include a couple of filters that allow you to add or remove files that are parsed. 'Whitelisting' the plug-in files you want parsed will generally improve performance. 

== Installation ==

1. Upload `plugin-codex-generator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` menu in WordPress


== Frequently Asked Questions ==



== Changelog ==

= 1.0 =
* Initial release.


== Upgrade Notice ==


