<?php
/**
 * The thim_downloaded_font_files option imported from Pantheon maps Google
 * Font URLs to Pantheon filesystem paths (/code/wp-content/uploads/...).
 * thim-core builds its @font-face CSS by str_replace-ing the *current*
 * upload basedir with the upload baseurl over those stored paths
 * (thim-core/inc/customizer/modules/webfonts/downloader.php), so on Upsun
 * the replacement never matches and the raw /code/... path is emitted as
 * the font URL — a 404 that drops the site to fallback fonts.
 *
 * Deleting the option is enough: the font files already exist on the
 * uploads mount, and the downloader rebuilds the map with current paths on
 * the next front-end render (re-downloading from fonts.gstatic.com only if
 * a file is missing).
 */

return static function () {
	delete_option( 'thim_downloaded_font_files' );
};
