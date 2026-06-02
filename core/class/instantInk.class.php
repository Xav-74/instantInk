<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

if (!class_exists('instantInk_API')) {
	require_once __DIR__ . '/../../3rdparty/instantInk_API.php';
}

if (!class_exists('instantInk_local_API')) {
	require_once __DIR__ . '/../../3rdparty/instantInk_local_API.php';
}

class instantInk extends eqLogic {
	
    /*     * *************************Attributs****************************** */

	public static $_widgetPossibility = array(
		'custom' => true,
		'parameters' => array(),
	);
	
	
    /*     * ***********************Methode static*************************** */
    
    public static function pull() {

		log::add('instantInk', 'debug', 'Cron '.config::byKey('cronPattern', 'instantInk'));
		foreach (eqLogic::byType('instantInk', true) as $instantInk) {								// type = instantInk et eqLogic enable
			$cmdRefresh = $instantInk->getCmd(null, 'refresh');		
			if (!is_object($cmdRefresh) ) {															// Si la commande n'existe pas ou condition non respectée
			  	continue; 																			// continue la boucle
			}
			$cmdRefresh->execCmd(); 
		}	
	}

	public static function cronDaily() {
        
        log::add('instantInk', 'debug', 'CronDaily');
		foreach (eqLogic::byType('instantInk', true) as $instantInk) {
			$cmdHistory = $instantInk->getCmd(null, 'getHistory');		
			if (!is_object($cmdHistory) ) {
			  	continue;
			}
			$cmdHistory->execCmd(); 
		}
	}

	public static function getConfigForCommunity()
	{
		$index = 1;
		$CommunityInfo = "```\n";
		if ( !empty(config::byKey('sessionId', 'instantInk')) ) { $CommunityInfo = $CommunityInfo . 'sessionId configured'; }
        else { $CommunityInfo = $CommunityInfo . 'sessionId missing'; }
		if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', config::byKey('sessionId', 'instantInk'))) { $CommunityInfo = $CommunityInfo . ' - valid format' . "\n"; }
		else { $CommunityInfo = $CommunityInfo . ' - invalid format' . "\n"; }
		if (file_exists(dirname(__FILE__) . '/../../data/instantInk_tokens.json')) { $CommunityInfo = $CommunityInfo . 'File "instantInk_tokens.json" found' . "\n"; }
		else { $CommunityInfo = $CommunityInfo . 'File "instantInk_tokens.json" not found' . "\n"; }
		if ( !empty(config::byKey('cronPattern', 'instantInk')) && config::byKey('cronPattern', 'instantInk') !='' ) { $CommunityInfo = $CommunityInfo . 'Cron : ' . config::byKey('cronPattern', 'instantInk') . "\n"; }
        else { $CommunityInfo = $CommunityInfo . 'Cron : ' . "\n"; }
		foreach (eqLogic::byType('instantInk', true) as $instantInk)  {
			$CommunityInfo = $CommunityInfo . "Printer #" . $index . " - ID : " . $instantInk->getConfiguration('printerId') . " - Model : ". $instantInk->getConfiguration('model') . " - Name : ". $instantInk->getConfiguration('name') . " - IP : ". $instantInk->getConfiguration('IPaddress') . "\n";
			$index++;
		}
		$CommunityInfo = $CommunityInfo . "```";
		return $CommunityInfo;
	}

	public static function scheduleCron($cronPattern) {
				
		$cron = cron::byClassAndFunction('instantInk', 'pull');
		if (!is_object($cron)) {
			$cron = new cron();
			$cron->setClass('instantInk');
			$cron->setFunction('pull');
			$cron->setEnable(1);
			$cron->setDeamon(0);
			$cron->setSchedule($cronPattern);
			$cron->setTimeout(5);
			$cron->save();
			log::add('instantInk', 'debug', 'Create cron pull - setSchedule : '.$cronPattern);
		}
		else { $cron->setSchedule($cronPattern); }
		$cron->save();
		log::add('instantInk', 'debug', 'Update cron pull - setSchedule : '.$cronPattern);
	}

	public static function getLogLevelFromHttpStatus($httpStatus, $success)
	{
		return ( $httpStatus == $success ) ? 'debug' : 'error';
	}	

    /*     * *********************Méthodes d'instance************************* */

    /* fonction appelée pendant la séquence de sauvegarde avant l'insertion 
     * dans la base de données pour une nouvelle entrée */
    public function preInsert() {
	}

	/* fonction appelée pendant la séquence de sauvegarde après l'insertion 
     * dans la base de données pour une nouvelle entrée */
    public function postInsert() {
    }

	 /* fonction appelée avant le début de la séquence de sauvegarde */
    public function preSave() {

 		$this->setLogicalId($this->getConfiguration('printerId'));
	}

	/* fonction appelée après la fin de la séquence de sauvegarde */
    public function postSave() {

 		$order = 1;
 		$this->createCmd('currentPlan_page', __('Nombre pages plan', __FILE__), $order, 'info', 'numeric');
		$order++;
        $this->createCmd('currentPlan_rollover', __('Nombre pages max report plan', __FILE__), $order, 'info', 'numeric');
		$order++;
		$this->createCmd('currentPlan_additional', __('Nombre pages additionnelles max plan', __FILE__), $order, 'info', 'numeric');
		$order++;
 		$this->createCmd('currentPlan_price', __('Prix plan', __FILE__), $order, 'info', 'string');
		$order++;		
        $this->createCmd('period', __('Période', __FILE__), $order, 'info', 'string');
		$order++;
        $this->createCmd('billingCycle_page', __('Nombre pages imprimées période', __FILE__), $order, 'info', 'numeric');
		$order++;
        $this->createCmd('billingCycle_rollover', __('Nombre pages imprimées report période', __FILE__), $order, 'info', 'numeric');
		$order++;
        $this->createCmd('billingCycle_rollovermax', __('Nombre pages report max période', __FILE__), $order, 'info', 'numeric');
		$order++;
		$this->createCmd('billingCycle_additional', __('Nombre pages additionnelles imprimées période', __FILE__), $order, 'info', 'numeric');
		$order++;
		$this->createCmd('billingCycle_price', __('Prix période', __FILE__), $order, 'info', 'string');
		$order++;
        $this->createCmd('cartridge_K', __('Statut cartouche noire', __FILE__), $order, 'info', 'numeric');
		$order++;
 		$this->createCmd('cartridge_C', __('Statut cartouche cyan', __FILE__), $order, 'info', 'numeric');
		$order++;
		$this->createCmd('cartridge_M', __('Statut cartouche magenta', __FILE__), $order, 'info', 'numeric');
		$order++;
		$this->createCmd('cartridge_Y', __('Statut cartouche jaune', __FILE__), $order, 'info', 'numeric');
		$order++;
		$this->createCmd('lastUpdate', 'Dernière mise à jour', $order, 'info', 'string');
		$order++;
		$this->createCmd('history', 'Historique', $order, 'info', 'string');
		$order++;
        
		$this->createCmd('refresh', __('Rafraichir', __FILE__), $order, 'action', 'other');
		$order++;
		$this->createCmd('getHistory', __('Obtenir historique', __FILE__), $order, 'action', 'other');
	}

	/* fonction appelée pendant la séquence de sauvegarde avant l'insertion 
     * dans la base de données pour une mise à jour d'une entrée */
    public function preUpdate() {
	}

	/* fonction appelée pendant la séquence de sauvegarde après l'insertion 
     * dans la base de données pour une mise à jour d'une entrée */
    public function postUpdate() {
	}

	/* fonction appelée avant l'effacement d'une entrée */
    public function preRemove() {
    }

	/* fonnction appelée aprés l'effacement d'une entrée */
    public function postRemove() {
    }
    
    /* Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin */
    public function toHtml($_version = 'dashboard') {
    	
		$this->emptyCacheWidget(); 		//vide le cache. Pratique pour le développement
				
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		
		$version = jeedom::versionAlias($_version);
		$replace['#version#'] = $_version;

		//Traitement des des options de configuration
		$replace['#printer_id'.$this->getId().'#'] = $this->getConfiguration('printerId');
				
		// Traitement des commandes infos
		foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '_name#'] = $cmd->getName();
			$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
			$replace['#' . $cmd->getLogicalId() . '_visible#'] = $cmd->getIsVisible();
			$replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
			if ($cmd->getIsHistorized() == 1) { $replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor'; }
		}

		// Traitement des commandes actions
		foreach ($this->getCmd('action') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '_visible#'] = $cmd->getIsVisible();
		}
		
		// On definit le template à appliquer
		$template = 'instantInk_dashboard_flatdesign';
		$replace['#template#'] = $template;

		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, $template, 'instantInk')));
	}
    
    /* Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    } */

    /* Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    } */
	 
	private function createCmd($commandName, $commandDescription, $order, $type, $subType, $isHistorized = 0, $template = [])
	{	
		$cmd = $this->getCmd(null, $commandName);
        if (!is_object($cmd)) {
            $cmd = new instantInkCmd();
            $cmd->setOrder($order);
			$cmd->setName(__($commandDescription, __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId($commandName);
			$cmd->setType($type);
			$cmd->setSubType($subType);
			$cmd->setIsHistorized($isHistorized);
			if (!empty($template)) { $cmd->setTemplate($template[0], $template[1]); }
			$cmd->save();
			log::add('instantInk', 'debug', 'Add command '.$cmd->getName().' (LogicalId : '.$cmd->getLogicalId().')');
        }
    }
	
	
    /*     * **********************Getteur Setteur*************************** */

	public static function connection()
    {
		log::add('instantInk', 'debug', '┌─Command execution : connection()');
		$sessionId = config::byKey('sessionId', 'instantInk');
        if (!$sessionId) {
			log::add('instantInk', 'debug', '| shellSessionId missing. Please configure the plugin');
			throw new Exception('shellSessionId missing. Please configure the plugin');
		}

        $api = new instantInk_API($sessionId);
        $result = $api->connection();

		log::add('instantInk', 'debug', '| Connection  OK  with account email '.$result['email']);
		log::add('instantInk', 'debug', '└─End of connection()');
        return $result;
    }

	public static function getConnectionStatus()
    {
		log::add('instantInk', 'debug', '┌─Command execution : getConnectionStatus()');
		$tokenFile = dirname(__FILE__) . '/../../data/instantInk_tokens.json';
 		
		if (!file_exists($tokenFile)) {
			$result = ['has_session' => false, 'has_token' => false, 'session_expired' => true, 'token_expired' => true];
			log::add('instantInk', 'debug', '| Tokens : '.json_encode($result));
			log::add('instantInk', 'debug', '└─End of getConnectionStatus()');
			return $result;
		}

		$api = new instantInk_API();
		$result = $api->getConnectionStatus();
		log::add('instantInk', 'debug', '| Tokens : '.json_encode($result));
		log::add('instantInk', 'debug', '└─End of getConnectionStatus()');
		return $result;
	}

	public static function resetTokens()
	{		
		$filename = __DIR__.'/../../data/'.'instantInk_tokens.json';
		if ( file_exists($filename) ) {
			unlink($filename);
			$result = array();
			$result['res'] = "OK";
			log::add('instantInk', 'debug', 'File '.$filename.' deleted');
			return $result;
		}
		else { 
			log::add('instantInk', 'debug', 'File '.$filename.' doesn\'t exist'); 
			return null;
		}
	}

	public static function synchronize()
    {
		log::add('instantInk', 'debug', '┌─Command execution : synchronize()');
		$sessionId = config::byKey('sessionId', 'instantInk');
        if (!$sessionId) {
			log::add('instantInk', 'debug', '| shellSessionId missing. Please configure the plugin');
			throw new Exception('shellSessionId missing. Please configure the plugin');
		}
		
		$api = new instantInk_API($sessionId);
		$myConnection = $api->connection();
		$printer = $api->getPrinters();

		$json = json_decode($printer->body, true);
		if ( !$json['printer_id'] || $json['printer_id'] == '' ) {
 			log::add('instantInk', 'debug', '| No printer found');
		}
		else {
			instantInk::addPrinter($json['name'], $json['printer_id'], $json['sku'], $json['image_url']);
			log::add('instantInk', 'debug', '└─End of synchronise()');
		}
		return true;
	}

	public static function addPrinter($name, $printerId, $model, $img_url)
    {
        foreach (eqLogic::byType('instantInk', true) as $instantInk) {		   
            if ( $instantInk->getConfiguration('printerId') == $printerId ) {
				$instantInk->setConfiguration('printerId', $printerId);
				$instantInk->setConfiguration('model', $model);
				$instantInk->setConfiguration('name', $name);
				$instantInk->save();

				$img = file_get_contents($img_url);
				$filename = dirname(__FILE__).'/../../data/'.$printerId.'.png';
				file_put_contents($filename, $img);

				log::add('instantInk', 'debug', '| Printer already exists - Update printer (name : '.$name.' - printerId : '.$printerId.' - model : '.$model.' - image : '.$img_url.')');
				return; 																			
			}
		}
		
		$printer = new instantInk();
        $printer->setEqType_name('instantInk');
        $printer->setName($name);
        $printer->setConfiguration('printerId', $printerId);
        $printer->setConfiguration('model', $model);
		$printer->setConfiguration('name', $name);
		$printer->setIsEnable(1);
		$printer->setIsVisible(1);
		$printer->setCategory('multimedia',1);
        $printer->save();

		$img = file_get_contents($img_url);
		$filename = dirname(__FILE__).'/../../data/'.$printerId.'.png';
		file_put_contents($filename, $img);
        
		log::add('instantInk', 'debug', '| Add printer (name : '.$name.' - printerId : '.$printerId.' - model : '.$model.' - image : '.$img_url.')');        
    }

	public function refreshInstantInkData()
    {
 		$sessionId = config::byKey('sessionId', 'instantInk');
        if (!$sessionId) {
			log::add('instantInk', 'debug', '| shellSessionId missing. Please configure the plugin');
			throw new Exception('shellSessionId missing. Please configure the plugin');
		}
		$IPaddress = $this->getConfiguration('IPaddress');
		log::add('instantInk', 'debug', '| printerId : '.$this->getConfiguration('printerId'));

		//instantInk_API
        $api = new instantInk_API($sessionId);
        $myConnection = $api->connection();
		
		$data = $api->getInstantInkDataDashboard();
		$json = json_decode($data->body, true);
		/*$this->checkAndUpdateCmd('currentPlan_page', 			$json['currentPlan']['pages'] ?? 0);
		$this->checkAndUpdateCmd('currentPlan_price', 			$json['currentPlan']['price'] ?? '');
		$this->checkAndUpdateCmd('currentPlan_rollover', 		$json['currentPlan']['rollover'] ?? 0);*/
		$this->checkAndUpdateCmd('period', 						$json['billingCycleSelectionList'][0]['label'] ?? '');
 		$id = $json['billingCycleSelectionList'][0]['id'];
		log::add('instantInk', 'debug', '| Result getInstantInkDataDashboard() : ['.$data->httpCode.'] '.$data->body);

		$data2 = $api->getInstantInkDataBillingCycle($id);
		$json2 = json_decode($data2->body, true);
		$this->checkAndUpdateCmd('currentPlan_page', 			$json2['plan']['pages'] ?? 0);
		$this->checkAndUpdateCmd('currentPlan_price', 			$json2['plan']['price'] ?? '');
		$this->checkAndUpdateCmd('currentPlan_rollover', 		$json2['plan']['rollover_cap'] ?? 0);
		$this->checkAndUpdateCmd('currentPlan_additional', 		$json2['plan']['overage_block_size'] ?? 0);
		$this->checkAndUpdateCmd('billingCycle_page', 			$json2['totals']['regular_pages'] ?? 0);
		$this->checkAndUpdateCmd('billingCycle_rollover', 		$json2['totals']['rollover_pages'] ?? 0);
		$this->checkAndUpdateCmd('billingCycle_rollovermax', 	$json2['totals']['initial_rollover_pages'] ?? 0);
		$this->checkAndUpdateCmd('billingCycle_additional', 	$json2['totals']['additional_pages'] ?? 0);
		$this->checkAndUpdateCmd('billingCycle_price', 			$json2['totals']['total_price'] ?? 0);
		log::add('instantInk', 'debug', '| Result getInstantInkDataBillingCycle() : ['.$data2->httpCode.'] '.$data2->body);

		/*$data3 = $api->getInstantInkDataInkStatus();
		$json3 = json_decode($data3->body);
		foreach ($json3->ink_statuses as $ink) {
			switch ($ink->color) {
				case 'K':
					$this->checkAndUpdateCmd('cartridge_K', 	$ink->level_state ?? 0);
					break;
				case 'C':
					$this->checkAndUpdateCmd('cartridge_C', 	$ink->level_state ?? 0);
					break;
				case 'M':
					$this->checkAndUpdateCmd('cartridge_M', 	$ink->level_state ?? 0);
					break;
				case 'Y':
					$this->checkAndUpdateCmd('cartridge_Y', 	$ink->level_state ?? 0);
					break;
				case 'CMY':
					$this->checkAndUpdateCmd('cartridge_C', 	$ink->level_state ?? 0);
					$this->checkAndUpdateCmd('cartridge_M', 	$ink->level_state ?? 0);
					$this->checkAndUpdateCmd('cartridge_Y', 	$ink->level_state ?? 0);
					break;
				default:
					log::add('instantInk', 'debug', '| No cartridge found');
					break;
			}
		}*/
		
		//instantInk_local_API
		if ( $IPaddress != null && $IPaddress != '') {
			$local_api = new instantInk_local_API($IPaddress);
			$data3 = $local_api->getXML();
			$json3 = $local_api->parseXMLConsumables($data3);
			foreach ($json3 as $ink) {
				switch ($ink['label']) {
					case 'K':
						$this->checkAndUpdateCmd('cartridge_K', 	$ink['percent'] ?? 0);
						break;
					case 'C':
						$this->checkAndUpdateCmd('cartridge_C', 	$ink['percent'] ?? 0);
						break;
					case 'M':
						$this->checkAndUpdateCmd('cartridge_M', 	$ink['percent'] ?? 0);
						break;
					case 'Y':
						$this->checkAndUpdateCmd('cartridge_Y', 	$ink['percent'] ?? 0);
						break;
					case 'CMY':
						$this->checkAndUpdateCmd('cartridge_C', 	$ink['percent'] ?? 0);
						$this->checkAndUpdateCmd('cartridge_M', 	$ink['percent'] ?? 0);
						$this->checkAndUpdateCmd('cartridge_Y', 	$ink['percent'] ?? 0);
						break;
					default:
						log::add('instantInk', 'debug', '| No cartridge found');
						break;
				}
			}
		}

		//Other
		$this->checkAndUpdateCmd('lastUpdate', date('d/m/Y H:i:s', time()));

 		if ( $data->httpCode == '200' && $data2->httpCode == '200' /*&& $data3->httpCode == '200'*/) { $httpCode = '200'; }
		else { 
			 $httpCode =
        		$data->httpCode != '200' ? $data->httpCode : ($data2->httpCode);
		}

		log::add('instantInk', $this->getLogLevelFromHttpStatus($httpCode, '200'), '└─End of refreshInstantInkData() : ['.$httpCode.']');
	}

	public function getHistory()
    {
 		log::add('instantInk', 'debug', '| printerId : '.$this->getConfiguration('printerId'));
		$sessionId = config::byKey('sessionId', 'instantInk');
        if (!$sessionId) {
			log::add('instantInk', 'debug', '| shellSessionId missing. Please configure the plugin');
			throw new Exception('shellSessionId missing. Please configure the plugin');
		}

		$api = new instantInk_API($sessionId);
        $myConnection = $api->connection();
		$history = array();

		$data = $api->getInstantInkDataDashboard();
		$json = json_decode($data->body, true);
		$cycles = $json['billingCycleSelectionList'] ?? [];
		$nbCycle = min(count($cycles), 12);
		
		for ($i=1; $i <= $nbCycle; $i++) {
			$id = $cycles[$i]['id'];
			$data2 = $api->getInstantInkDataBillingCycle($id);
			$json2 = json_decode($data2->body, true);
			
			$history[] = array(
				"period"	=> $cycles[$i]['label'],
				"pages"		=> $json2['totals']['total_pages'] ?? null,
				"price"		=> $json2['totals']['total_price'] ?? null
			);
		}
		
		$history = array_reverse($history);
		$json_history = json_encode($history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$this->checkAndUpdateCmd('history', $json_history);
		
		log::add('instantInk', 'debug', '| Result getHistory() : '.$json_history);		
		log::add('instantInk', $this->getLogLevelFromHttpStatus($data->httpCode, '200'), '└─End of getHistory() : ['.$data->httpCode.']');
	}
}


class instantInkCmd extends cmd {
	
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /* Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }*/

    public function execute($_options = array()) {
    
		$eqLogic = $this->getEqLogic(); 										// On récupère l'éqlogic de la commande $this
		$logical = $this->getLogicalId();
		log::add('instantInk', 'debug', '┌─Command execution : '.$logical);
		
		try {
            switch ($logical) {
                case 'refresh':
                    $eqLogic->refreshInstantInkData();
					break;
				case 'getHistory':
					$eqLogic->getHistory();
					break;
                default:
                    throw new \Exception("Unknown command", 1);
                    break;
            }
        } catch (Exception $e) {
            echo 'Exception : ',  $e->getMessage(), "\n";
            log::add('instantInk', 'debug', '└─Command execution error : '.$logical.' - '.$e->getMessage());
        }
		
		$eqLogic->refreshWidget();
	}
	

    /*     * **********************Getteur Setteur*************************** */
}


?>
