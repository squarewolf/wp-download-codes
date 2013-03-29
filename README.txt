=== Plugin Name ===
Contributors: misanthrop
Donate link: http://wordpress.org/extend/plugins/wp-download-codes/
Tags: download, download code, code generator
Requires at least: 2.5
Tested up to: 3.4
Stable tag: 2.1.3

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
1. Since version 2.1 you can provide your users with a direct link to the download code form using the "yourcode" query parameter (e.g. http://yourwordpressblog.com/download/?yourcode=XYZ).

== Frequently Asked Questions ==

= Can I have download forms for several releases? =

No, currently each download code form must have assigned the ID of a specific release.

= Why do I have to upload the zip files via FTP?  =

Most providers do not allow an upload quota which is sufficient to upload larger zip files. Therefore, an option using an upload form has not been considered yet.

== Screenshots ==


== Changelog ==

= 2.1.3 =
* Improved query for a quicker display of releases in "Manage Releases"

= 2.1.2 =
* As suggested by allolex, an issue with truncated downloads was tried to be fixed by completely flushing the download stream.

= 2.1.1 =
* Included MYSQL patch from Sean Patrick Rhorer to enable display of releases even with a restrictive host setting for max join size

= 2.1 =
* Added feature for direct download code links through the query parameter "yourcode"

= 2.0 =
* Added an (optional) artist field so that releases can have a title (album name) and artist
* Introduced "code groups" in order to be able to created and delete batches of code groups with the same code prefixes
* Added a new field to the settings page so that users can customize what set of characters their download codes are composed from (this also avoid confusion between numbers and letter like between 0 and O as well as between l and 1)
* Changed the download link that users click on to display the release's title and the file size (rather than using the filename as the link text)
* Updated HTML markup for the forms and tables so that it matches WordPress conventions (label tags, descriptions, etc)
* Moved a lot of the common DB calls into individual functions in dc_functions.php
* Added a JS confirm alert if you try to delete a release or a batch of download codes to avoid accidental deletions
* Added a similar JS confirm before the user finalizes a batch of codes (since it's irreversible)
* Added "lightbox" style popups to display the list of download codes or the download report. It also works on the off-chance that JS isn't enabled.

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
