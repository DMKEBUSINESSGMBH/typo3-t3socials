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

tx_rnbase::load('tx_t3socials_trigger_MessageBuilder');


/**
 * Message Builder für universal messages, manually created in external code
 *
 * @package tx_t3socials
 * @subpackage tx_t3socials_api
 * @author Michael Hadorn <michael.hadorn@laupercomputing.ch>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 *
 *  Working Example:
	// call publish message api from t3socials
	/** @var tx_t3socials_api_publish $t3Publish * /
	tx_rnbase::load('tx_t3socials_api_publish');
	$errors = tx_t3socials_api_publish::publishMessage('head', 'intro', 'message', 'url.ch', array(1,2));
	var_dump($errors);
 *
 */
class tx_t3socials_api_publish {

	protected $type = 'api';

	protected $errors = array();

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $headline
	 * @param string $intro
	 * @param string $messageString
	 * @param string $url
	 * @param int $uid needed for logging and check if message was already sent
	 * @param string $table needed for logging and check if message was already sent
	 * @param array|bool|false $networks id of configured networks or null, all with autosend
	 * @return null
	 */
	public function publishMessage($headline, $intro, $messageString, $url, $uid = 0, $table = '', $networks = false) {
		$this->errors = array();
		$avoidDoubleSend = false;
		if ($uid > 0 && strlen($table) > 0) {
			$avoidDoubleSend = true;
		}

		if (is_array($networks)) {
			// use only defined networks
			// uids sind immer int!
			$networks = array_map('intval', $networks);
		} else {
			//
			$networkSrv = tx_t3socials_srv_ServiceRegistry::getNetworkService();
			/** @var tx_t3socials_models_Network[] $networksConf */
			$networksConf = $networkSrv->findAccountsWithAutosend(true);
			foreach ($networksConf as $network) {
				$networks[] = $network->getUid();
			}
		}

		if (count($networks) == 0) {
			$this->errors['no_networks'] = 'No networks are configured';
			return $this->errors;
		}

		$networkSrv = tx_t3socials_srv_ServiceRegistry::getNetworkService();

		// check if double send
		if ($avoidDoubleSend) {
			if ($networkSrv->hasSent($uid, $table)) {
				$this->errors['aready_published'] = 'Message already published at least on one social media network.';
				return $this->errors;
			}
		}

		$message = self::getMessage($headline, $intro, $messageString, $url);

		if (!($message->getHeadline()) && !($message->getIntro()) && !($message->getMessage()) && !($message->getUid())) {
			$message = 'message invalid';
		}

		// Wurde keine Message zurück gegeben
		// ist die Validierung fehlgeschlagen!
		if (!$message instanceof tx_t3socials_models_Message) {
			$this->errors['message_not_found'] = $message;
			return $this->errors;
		}

		$hasSendSomething = FALSE;
		foreach ($networks as $networkId) {
			/* @var $account tx_t3socials_models_Network */
			$account = $networkSrv->get($networkId);

			// wir erzeugen ein clone, um spezielle manipulationen
			// für das netzwerk zu machen
			$messageCopy = clone $message;

			try {
				$connection = tx_t3socials_network_Config::getNetworkConnection($account);
				$connection->setNetwork($account);
				$error = $connection->sendMessage($messageCopy);
				// fehler beim senden?
				if ($error) {
					$this->errors[] = $error;
				} else {
					// erfolgreich versendet
					$hasSendSomething = TRUE;
				}
			} catch (Exception $e) {
				$this->errors[] = $e;
			}
		}

		// save send in table auto_send to avoid double sening
		if ($hasSendSomething) {
			$networkSrv->setSent($uid, $table);
		}

		$return = true;
		if (count($this->errors) > 0) {
			$return = $this->errors;
		}
		return $return;
	}

	/**
	 * Prints the errors (uses local one, or if given these)
	 * @param null $errors
	 * @return string
	 */
	public function getErrorsHTML($errors = null) {
		$html = '';
		if (is_null($errors)) {
			$errors = $this->errors;
		}
		if (count($errors) > 0) {
			$html .= '<div class="t3social-status t3social-error"><span class="title">Fehler beim Veröffentlichen auf den Sozialen Kanälen</span><ul>';
			foreach ($errors as $errorCode => $errorText) {
				$html .= '<li>'.$errorText.' (Code: "'.$errorCode.'")</li>';
			}
			$html .= '</ul></div>';
		} else {
			$html .= '<div class="t3social-status t3social-success"><span class="title">Meldung wurde erfolgreich auf Facebook und Twitter veröffentlicht.</span></div>';
		}
		return $html;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @param $headline
	 * @param $intro
	 * @param $messageString
	 * @param $url
	 * @return tx_t3socials_models_Message
	 */
	protected function getMessage($headline, $intro, $messageString, $url) {
		// todo: define this type as constant
		$message = tx_t3socials_models_Message::getInstance($this->type);
		$message->setHeadline($headline);
		$message->setIntro($intro);
		$message->setMessage($messageString);
		$message->setUrl($url);
		return $message;
	}

}
