=== Plugin Name ===
Contributors: misanthrop
Donate link: http://wordpress.org/extend/plugins/wp-download-codes/
Tags: download, download code, code generator
Requires at least: 2.5
Tested up to: 3.1.2
Stable tag: 1.2.2

The plugin enables to generation and management of download codes for .zip files. It was written to enable the free download of records and CDs with dedicated codes printed on the cover of the releases or on separate download cards.

== Description ==

The plugin enables to generation and management of download codes for .zip files. It was written to enable the free download of records and CDs with dedicated codes printed on the cover of the releases or on separate download cards.

With the plugin you can:

*   Create and manage **releases**, which are items bundled as zips (e.g. digital versions of vinyl albums) to be downloaded with download codes.
*   Specify the allowed number of downloads for each release.
*   Create alphanumeric **download codes** for each release using a prefix for each code. The number of characters can be specified for each code.
*   Review downloads codes and set them to "final" when you want to use and distribute them.
*   Export final download codes in a plain list.
*   Analyze the use of the download codes.

== Installation ==

1. Upload the `wp-download-codes` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Create a folder within the `wp-content` directory and upload one or several zip files via FTP.
1. Optionally protect the folder via CHMOD in order to avoid unauthorized downloads using direct file links.
1. Go to the 'Settings' page and enter the zip folder specified above.
1. Create a new release and assign a valid zip file to it.
1. Create download codes for the release via 'Manage codes' and make them final.
1. Put `[download-code id="xyz"]` in a page or post, where "xyz" is the ID of the respective release. Alternatively, you can write `[download-code]` without an ID to allow any download code. In the latter case, the download code should be assigned directly to a release.

== Frequently Asked Questions ==

= Can I have download forms for several releases? =

No, currently each download code form must have assigned the ID of a specific release.

= Why do I have to upload the zip files via FTP?  =

Most providers do not allow an upload quota which is sufficient to upload larger zip files. Therefore, an option using an upload form has not been considered yet.

== Screenshots ==


== Changelog ==

= 1.3 =
* Changed download mechanism in order to get fix the header issues appearing with many firefox versions

= 1.2.2 =
* Changed menu order in adminstration
* Fixed the determination of the upload path which was sometimes not working for blank installations
* Tried to fix the download file streaming as there are still problems with some browser and OS combinations

= 1.2.1 =
* Fixed HTML rendering of "Settings" form in plugin administration

= 1.2 =
* Added possibility to specify the absolute path for the download file location in 'Settings'. This should help if in your wordpress installation the upload folder cannot be determined.

= 1.1 = 
* Added functionality to edit list of allowed file types.
* Added annotation to documentation about folder protection to avoid unauthorized downloading.
* Applied minor bug fixes.

= 1.0.9 =
* Fixed download problem on WP sites with blog URL differing from WP URL

= 1.0.8 =
* Enabled reset of specifc or all download codes.
* Enabled modification of download form messages in the `Settings` dialog.
* Enabled the [download-code] shortcode without having to provide the release ID.
* Fixed problems ocurring from side effects with other plugins during the sending of filestream headers.
* Fixed problem with insufficient memory occuring in some PHP environments.

= 1.0.7 =
* Fixed different behavior of upload path determination (absolute, relative).

= 1.0.6 =
* Fixed side effects to media library.

= 1.0.5 =
* Added header for information about the length of the downloaded file.
* Fixed deletion of session.

= 1.0.4 =
* Fixed "Make final" functionality for WP 2.7.
* Introduced differentiation between absolute and relative upload paths.

= 1.0.3 =
* Added "mp3" to the allowed file types.
* Reworked constraints for fields on 'Manage Releases'. 

= 1.0.2 =
* Bug fix: (Existing) zip folders below the upload directory can now be selected via drop-down.
* Bug fix: On the 'Manage Codes' page, the non-existence of releases was handled (link to 'Add new release' sub page was displayed').

= 1.0.1 =
* Improved editing and addition of releases.
* Corrected setting of options during initialization.

= 1.0.0 =
* Initial version.

== Arbitrary section ==
