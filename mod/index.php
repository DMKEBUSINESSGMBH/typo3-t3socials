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
$GLOBALS['LANG']->includeLLFile('EXT:t3socials/mod/locallang.xml');
// This checks permissions and exits if the users has no permission for entry.
$GLOBALS['BE_USER']->modAccess($MCONF, 1);
// DEFAULT initialization of a module [END]

tx_rnbase::load('tx_rnbase_configurations');
tx_rnbase::load('tx_rnbase_mod_BaseModule');
tx_rnbase::load('tx_t3socials_mod_util_Template');
tx_rnbase::load('tx_t3socials_mod_util_Message');

/**
 * Backend Modul für t3socials
 *
 * @package tx_t3socials
 * @subpackage tx_t3socials_mod
 * @author Rene Nitzsche <rene@system25.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class  tx_t3socials_mod_Module
	extends tx_rnbase_mod_BaseModule {

	/**
	 * Method to get the extension key
	 *
	 * @return	string Extension key
	 */
	public function getExtensionKey() {
		return 't3socials';
	}

	/**
	 * Method to set the tabs for the mainmenu
	 * Umstellung von SelectBox auf Menu
	 *
	 * @return array
	 */
	protected function getFuncMenu() {
		$mainmenu = $this->getFormTool()->showTabMenu($this->getPid(), 'function', $this->getName(), $this->MOD_MENU['function']);
		return $mainmenu['menu'];
	}

	/**
	 * {@inheritDoc}
	 * @see tx_rnbase_mod_BaseModule::useModuleTemplate()
	 * @TODO TRUE liefern wenn Probleme im Core gefixed sind. So werden Labels im Funktionsmenü
	 * des Moduls noch nicht geparsed.
	 */
	protected function useModuleTemplate() {
		return FALSE;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3socials/mod/index.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3socials/mod/index.php']);
}

// Make instance:
/** @var tx_t3socials_mod_Module $SOBE */
$SOBE = tx_rnbase::makeInstance('tx_t3socials_mod_Module');
$SOBE->init();

// Include files?
foreach ((array) $SOBE->include_once as $incFile) {
	require_once $incFile;
}

$SOBE->main();
$SOBE->printContent();
