<?php
/**
 * WikihelpRedirect, based on Polyglot
 *
 * Features:
 *  * Redirect '#REDIRECT' links to the localized version
 *
 * See the README file for more information
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Daniel Kinzler, brightbyte.de
 * @author Jan Holesovsky, kendy@suse.cz
 * @copyright © 2007 Daniel Kinzler
 * @copyright © 2010 Jan Holesovsky
 * @licence GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

$wgExtensionCredits['other'][] = array( 
	'path' => __FILE__,
	'name' => 'WikihelpRedirect', 
	'author' => 'Jan Holesovsky', 
	'url' => 'http://help.libreoffice.org',
	'description' => 'Enhance redirects to point to the right language version.',
  'license-name' => 'GPLv2+'
);

$wgHooks['ArticleFromTitle'][] = 'wfWikihelpRedirectArticleFromTitle';

function wfWikihelpRedirectArticleFromTitle( &$title, &$article ) {
	global $wgRequest;

	if ($wgRequest->getVal( 'redirect' ) == 'no') {
		return true;
	}

	if (!$title->isRedirect()) {
		return true;
	}

	// find the target
	$a = new Article($title);
	$a->loadPageData();

	if (!$a->mIsRedirect) {
		return true;
	}

	// build the possible names of the pages
	$try_langs = array();
	$lang = $wgRequest->getText( 'Language' );
	if ( strlen( $lang ) == 5 &&
	     $lang[2] == '-' &&
	     $lang[3] >= 'A' && $lang[3] <= 'Z' &&
	     $lang[4] >= 'A' && $lang[4] <= 'Z' )
	{
		$try_langs[] = '/' . $lang;
	}

	if (strlen($lang) == 2 || (strlen($lang) > 2 && $lang[2] == '-')) {
		$try_langs[] =  '/' . substr( $lang, 0, 2 );
	}
	else {
		return true;
	}

	// fallback to English
	$try_langs[] = "";

	$try_versions = array();
	$version = $wgRequest->getText('Version');
	if ($version == "") {
		// 3.3 had no version tag
		$try_versions[] = "3.3/";
	}
	elseif ($version == "3.4") {
		$try_versions[] = "3.4/";
	}
	elseif ($version == "3.5") {
		$try_versions[] = "3.5/";
	}
	elseif ($version == "3.6") {
		$try_versions[] = "3.6/";
	}
	elseif ($version == "4.0") {
		$try_versions[] = "4.0/";
	}
        elseif ($version == "4.1") {
                $try_versions[] = "4.1/";
        }
        elseif ($version == "4.2") {
                $try_versions[] = "4.2/";
        }
        elseif ($version == "4.3") {
                $try_versions[] = "4.3/";
        }
        elseif ($version == "4.4") {
                $try_versions[] = "4.4/";
        }
        elseif ($version == "5.0") {
                $try_versions[] = "5.0/";
        }
	// the following has to be adapted (added) with every version bump
	// [the newest version should be with no prefix]
	//elseif ($version == "4.0") {
	//	$try_versions[] = "4.0/";
	//}

	// always add this to get at least something when no version fits
	$try_versions[] = "";

	// take the first existing page (the right language in the right
	// version)
	$t = $a->followRedirect();
	$found = false;
	foreach ($try_versions as $v) {
		foreach ($try_langs as $l) {
			$target = Title::makeTitleSafe($t->getNamespace(),
				$v . $t->getDBkey() . $l,
				$t->getFragment(), $t->getInterwiki());
			if ($target !== null && $target->isKnown()) {
				$found = true;
				break;
			}
		}
		if ($found) {
			break;
		}
	}
	if (!$found) {
		return true;
	}

	if (!class_exists('WikihelpRedirect')) {
		class WikihelpRedirect extends Article {
			var $mTarget;
		
			function __construct( $source, $target ) {
				Article::__construct($source);
				$this->mTarget = $target;
				$this->mIsRedirect = true;
			}
		
			function followRedirect() {
				return $this->mTarget;
			}

			function loadPageData( $data = 'fromdb' ) {
				Article::loadPageData( $data );
				$this->mIsRedirect = true;
			}
		}
	}
	
	// trigger redirect to the localized page
	$article = new WikihelpRedirect( $title, $target );
	
	return true;
}
