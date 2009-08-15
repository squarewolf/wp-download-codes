=== Plugin Name ===
Contributors: misanthrop
Donate link: http://wordpress.org/extend/plugins/wp-download-codes/
Tags: download, download code, code generator
Requires at least: 2.5
Tested up to: 2.8.4
Stable tag: 1.0.0

The plugin enables to generation and management of download codes for .zip files. It was written to enable the free download of records and CDs with dedicated codes printed on the cover of the releases or on separate download cards.

== Description ==

The plugin enables to generation and management of download codes for .zip files. It was written to enable the free download of records and CDs with dedicated codes printed on the cover of the releases or on separate download cards.

With the plugin you can:
*   Create and manage **releases**, which are items bundled as zips (e.g. digital versions of vinyl albums) to be downloaded with download codes.
*   Specify the allowed number of downloads for each release.
*   Create alphanumeric **download codes** for each release using a prefix for each code. The number of characters can be set for each code.
*   Review downloads codes and set them to "final" when you want to use and distribute them.
*   Export final download codes in a plain list.
*   Analyze the use of the download codes.

== Installation ==

1. Upload the `wp-download-codes` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a folder within the `wp-content´ directory and upload one or several zip files via FTP.
1. Go to the "Settings" page and enter the zip folder specified above.
1. Create a new release and assign a valid zip file to it.
1. Create download codes via "Manage codes".
1. Put `[download-codes id="xyz"]´ in a page or post, where "xyz" is the ID of the release.

== Frequently Asked Questions ==

= Can I have download forms for several releases? =

No, currently each download code form must have assigned the ID of a specific release.

= Why do I have to upload the zip files via FTP?  =

Most providers do not allow an upload quota which is sufficient to upload larger zip files. Therefore, an option using an upload form has not been considered yet.

== Screenshots ==


== Changelog ==

= 1.0.0 =
* Initial version.

== Arbitrary section ==
