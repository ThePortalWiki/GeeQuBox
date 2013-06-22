<?php
/**
 * GeeQuBox.php
 * Written by David Raison
 * @license: CC-BY-SA 3.0 http://creativecommons.org/licenses/by-sa/3.0/lu/
 *
 * @file GeeQuBox.php
 * @ingroup GeeQuBox
 *
 * @author David Raison
 *
 * Uses the lightbox jquery plugin written by Leandro Vieira Pinho (CC-BY-SA) 2007,
 * http://creativecommons.org/licenses/by-sa/2.5/br/
 * http://leandrovieira.com/projects/jquery/lightbox/
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is a MediaWiki extension, and must be run from within MediaWiki.' );
}

define('EXTPATH','GeeQuBox');

$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'GeeQuBox',
	'version' => '0.02.1',
	'author' => array( '[http://www.mediawiki.org/wiki/User:Clausekwis David Raison]' ), 
	'url' => 'http://www.mediawiki.org/wiki/Extension:GeeQuBox',
	'descriptionmsg' => 'geequbox-desc'
);

// see http://www.mediawiki.org/wiki/ResourceLoader/Documentation/Using_with_extensions
$wgResourceModules['ext.GeeQuBox'] = array(
	// JavaScript and CSS styles. To combine multiple file, just list them as an array.
	'scripts' => 'js/jquery.lightbox-0.5.min.js',
	// ResourceLoader needs to know where your files are; specify your
	// subdir relative to "extensions" or $wgExtensionAssetsPath
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => EXTPATH,
	'position' => 'top',
);

// defaults
$wgGqbDefaultWidth = 1024;

$wgExtensionMessagesFiles['GeeQuBox'] = dirname(__FILE__) .'/GeeQuBox.i18n.php';
$wgHooks['LanguageGetMagic'][] = 'wfGeeQuBoxLanguageGetMagic';

$gqb = new GeeQuBox;
$wgHooks['BeforePageDisplay'][] = array($gqb,'gqbAddLightbox');

function wfGeeQuBoxLanguageGetMagic( &$magicWords, $langCode = 'en' ) {
	$magicWords['geequbox'] = array( 0, 'geequbox' );
	return true;
}

/**
 * Class that handles all GeeQuBox operations.
 */
class GeeQuBox {

	private static $_page;

	public function gqbAddLightBox( $page ) { 
		try {
			self::$_page = $page;
			$this->_gqbReplaceHref( $page );
			$this->_gqbAddScripts( $page );
			return true;
		} catch ( Exception $e ) {
			wfDebug('GeeQuBox::'.$e->getMessage());
			return false;
		}
	}

	private function _gqbAddScripts() {
		global $wgExtensionAssetsPath;

		$eDir = $wgExtensionAssetsPath .'/'.EXTPATH.'/';
		self::$_page->addModules( 'ext.GeeQuBox' );
		return true;
	}

	/**
	 * We need to change this: <a href="/wiki/File:8734_f8db_390.jpeg"> into
	 * the href of the actual image file (i.e. $file->getURL()) because that
	 * is what Lightbox expects. (Note that this is not too different from the SlimboxThumbs
	 * approach but there doesn't seem to be an alternative approach.)
	 */
	private function _gqbReplaceHref() {
		global $wgGqbDefaultWidth;

		$page = self::$_page->getHTML();
		$pattern = '~href="/wiki/([^"]+)"\s*class="image"~';
		$replaced = preg_replace_callback($pattern,'self::_gqbReplaceMatches',$page);

		self::$_page->clearHTML();
		self::$_page->addHTML( $replaced );
	}

	private static function _gqbReplaceMatches( $matches ) {
		global $wgGqbDefaultWidth;

		$titleObj = Title::newFromText( rawurldecode( $matches[1] ) );
	        $image = wfFindFile( $titleObj, false, false, true );
        	$realwidth = (Integer) $image->getWidth();
	        $width = ( $realwidth > $wgGqbDefaultWidth ) ? $wgGqbDefaultWidth : $realwidth;
		$iPath = ( $realwidth < $wgGqbDefaultWidth ) ? $image->getURL() : $image->createThumb($width);
		$title = self::$_page->parse( "'''[[:" . $titleObj->getFullText() . "|" . $titleObj->getText() . "]]'''" );
	
		return 'href="/wiki/'. $matches[1] .'" data-title="'. htmlspecialchars( $title )  .'" data-url="'. $iPath . '" class="image lightboxed"';
	}

}
