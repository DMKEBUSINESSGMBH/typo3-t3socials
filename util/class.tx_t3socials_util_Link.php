<?php
/***************************************************************
*  Copyright notice
*
 * (c) 2014 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
tx_rnbase::load('tx_rnbase_util_Misc');

/**
 * Util zum handeln von Links und URLs
 *
 * @package tx_t3socials
 * @subpackage tx_t3socials_util
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_t3socials_util_Link {

	/**
	 * Erzeugt eine URl anhand eines Link Objekts
	 * Dabei wird die Ausführung von RealURL geprüft!
	 *
	 * @param tx_rnbase_util_Link $linkObject
	 * @return string
	 */
	public static function getRealUrlAbsUrlForLink(
		tx_rnbase_util_Link $linkObject
	) {
		// wir klonen das linkobjekt, da wir änderungen daran durchführen!
		$link = clone $linkObject;

		// wenn keine PostProc definiert ist oder wir uns im FE befinden
		// benötigen wir keine sonderbehandlung
		if (
			!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc'])
			|| TYPO3_MODE === 'FE'
		) {
			return $link->makeUrl(FALSE);
		}
		// else: wir müssen eine sonderbehandlung durchführen!

		// für Realurl ist eine tsfe notwendig
		tx_rnbase::load('tx_rnbase_util_Misc');
		$tsfe = tx_rnbase_util_Misc::prepareTSFE();
		// das mainScript MUSS index.php sein
		// see tx_realurl->prefixEnablingSpURL
		$tsfe->config['mainScript'] = 'index.php';
		// den prefix für diese url setzen
		$tsfe->absRefPrefix = $link->getAbsUrlSchema() ? $link->getAbsUrlSchema() : tx_rnbase_util_Misc::getIndpEnv('TYPO3_SITE_URL');

		// Die URL von rnbase darf dafür nie absolut sein!
		// durch das setzen von absRefPrefix ist diese bereits absolut!
		$link->setAbsUrl(FALSE);

		// parameter für die PostProc sammeln
		$ld = array('totalURL' => $link->makeUrl(FALSE));
		$params = array(
			// hier steckt nur die url drin!
			'LD' => &$ld,
			// das ist notwendig, wenn der link aus dem BE heraus erzeugt werden soll!
			'TCEmainHook' => TRUE,
		);
		// jetzt die hooks aufrufen (unter anderem realurl)
		tx_rnbase::load('tx_rnbase_util_Misc');
		tx_rnbase::load('tx_rnbase_util_Typo3Classes');
		$templateService = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getTemplateServiceClass());
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc'] as $funcRef) {
			tx_rnbase_util_Misc::callUserFunction($funcRef, $params, $templateService);
		}
		return $ld['totalURL'];
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3socials/util/class.tx_t3socials_util_Link.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3socials/util/class.tx_t3socials_util_Link.php']);
}
