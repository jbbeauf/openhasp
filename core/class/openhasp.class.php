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

class openhasp extends eqLogic {
  /*     * *************************Attributs****************************** */


  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  */
  public static $_encryptConfigKey = array('updateConf_byIp_http_password');

  /*     * ***********************Methode static*************************** */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community */
   public static function getConfigForCommunity() {
      // Cette function doit retourner des infos complémentataires sous la forme d'un
      // string contenant les infos formatées en HTML.
      return __('Utiliser dans jeedom un écran multifonction sous openHASP : écran tactile personnalisé et connecté via MQTT', __FILE__);
   }

   /**
   * Callback appelé par le plugin mqtt2 à la réception d'un message
   * @param AssociativeArray $_message Message MQTT, retourné par la fonction json_decode()
   */ 
   public static function handleMqttMessage($_message) {
    log::add(__CLASS__, 'debug', 'handleMqttMessage MQTT Message brut reçu : ' . print_r($_message,true));
    /* Mode discovery en cours : traitement à part */
    if (1 == config::byKey('mqtt::discovery::running', 'openhasp')) {
      $topic = config::byKey('mqtt::discovery::rootTopic', 'openhasp');
      $message = $_message[$topic]['discovery'];
      if (isset($message)) {
        $currentPlateDiscovered = array_values($message)[0];
        /* Boucle sur chaque équipement du plugin openhasp */
        $isNewPlate = true;
        foreach (eqLogic::byType(__CLASS__, true) as $openhasp) {
          $conMqttRootTopic = $openhasp->getConfiguration('conf::mqtt::rootTopic');
          $confMqttName = $openhasp->getConfiguration('conf::mqtt::name');
          if ($conMqttRootTopic . '/' . $confMqttName . '/' == $currentPlateDiscovered[node_t] ) {
            $isNewPlate = false;
            break;
          }
        }
        
        if ($isNewPlate) {
          $eqLogic = new openhasp();
          $eqLogic->setLogicalId($currentPlateDiscovered[node_t]);
          $eqLogic->setName($currentPlateDiscovered[node]);
          $eqLogic->setEqType_name('openhasp');
          $eqLogic->save();
          $eqLogic->validateConfigByIp(str_replace('http://', '', $currentPlateDiscovered[uri]), '', '', false);
          log::add(__CLASS__, 'info', __('Mode inclusion automatique', __FILE__) . ' - ' . __('Nouvel équipement ajouté', __FILE__) . ' ' . $currentPlateDiscovered[node] . ' ' . __('IP', __FILE__) . ' ' . $currentPlateDiscovered[uri]);
          event::add('openhasp::MainPage::reloadIfVisible', 0);
        }
        
        return;
      }
    }
    
    /* Boucle sur chaque équipement du plugin openhasp */
    foreach (eqLogic::byType(__CLASS__, true) as $openhasp) {
      /* Configuration par MQTT demandée : on attend la réponse de l'équipement */
      if (1 == $openhasp->getConfiguration('validateConfigByMqttRequested')) {
        $topic = $openhasp->getConfiguration('updateConf_byMqtt_topic');
        $hostname = $openhasp->getConfiguration('updateConf_byMqtt_name');
        $message = $_message[$topic][$hostname];
        if (isset($message['state']['statusupdate'])) {
          $openhasp->setConfiguration('validateConfigByMqttRequested', 0);
          $openhasp->setConfiguration('conf::wifi::ssid', $message['state']['statusupdate']['ssid']);
          $openhasp->setConfiguration('conf::wifi::ip', $message['state']['statusupdate']['ip']);
          $openhasp->setConfiguration('conf::mqtt::rootTopic', $topic);
          $openhasp->setConfiguration('conf::mqtt::name', $hostname);
          $openhasp->setConfiguration('updateConf_byIp_ip', $message['state']['statusupdate']['ip']);
          /* Sauvegarde des modifications et rafraichissement de la page de l'équipement */
          $openhasp->save(true);
          $openhasp->setChanged(0);
          event::add('openhasp::equipment::reload', $openhasp->getId());
        }
      }
      /* Cas normal : on traite le message reçu par rapport à l'équipement courant */
      $topic = $openhasp->getConfiguration('conf::mqtt::rootTopic');
      $hostname = $openhasp->getConfiguration('conf::mqtt::name');
      $message = $_message[$topic][$hostname];
      /* Si le message mqtt reçu correpond à l'équipement courant */
      if (isset($message)) {
        $cmdLastSeen = $openhasp->getCmd('info', 'lastSeen');
        $equipmentUpdated = false;
        /* Boucle sur chaque commande de type info de l'équipement courant */
        foreach ($openhasp->getCmd('info') as $cmd) {
          /* Sauter la commande info lastSeen */
          if ('lastSeen' == $cmd->getLogicalId()) {
            continue;
          }
          $subMessage = $message;
          $cmdToBeUpdated = true;
          /* On regarde si le message mqtt reçu correspond à la commande courante */
          foreach(explode('/', $cmd->getLogicalId()) as $subTopic){
            if (isset($subMessage[$subTopic])) {
              $subMessage = $subMessage[$subTopic];
            } else {
              $cmdToBeUpdated = false;
              break;
            }
          }
          /* Si le message mqtt reçu correspond à la commande courante : on la met à jour */
          if ($cmdToBeUpdated) {
            log::add(__CLASS__, 'debug', 'handleMqttMessage Equipement ' . $openhasp->getHumanName() . ' - Mise à jour commande ' . $cmd->getHumanName() . ' avec valeur ' . $subMessage);
            $openhasp->checkAndUpdateCmd($cmd->getLogicalId(), $subMessage);
            $equipmentUpdated = true;
          }
        } /* Fin boucle sur chaque commande de type info de l'équipement courant */
        if ($equipmentUpdated) {
          $openhasp->checkAndUpdateCmd($cmdLastSeen->getLogicalId(), date('Y-m-d H:i:s'));
        }
      } /* Fin si le message mqtt reçu correpond à l'équipement courant */
    } /* Fin Boucle sur chaque équipement du plugin openhasp */

  }

  public static function handleMqttSubscription($_action = 'suscribe', $_topic) {
    log::add(__CLASS__, 'debug', 'handleMqttSubscription MQTT subscription action = ' . print_r($_action,true) . ' - topic = ' . print_r($_topic, true));
    if (!class_exists('mqtt2')) {
      include_file('core', 'mqtt2', 'class', 'mqtt2');
    }
    $subscribed = isset(mqtt2::getSubscribed()[$_topic]);
    
    /* Abonnement au topic */
    if ('suscribe' == $_action && !$subscribed) {
      mqtt2::addPluginTopic(__CLASS__, $_topic);
      log::add(__CLASS__, 'info', __('Abonnement au topic MQTT', __FILE__) . ' ' . $_topic);
    }
    
    /* Désabonnement du topic si aucun autre équipement n'a besoin du même topic */
    if ('unsuscribe' == $_action && $subscribed) {
      $counter = 0;
      foreach (eqLogic::byType(__CLASS__, true) as $openhasp) {
        if ($_topic == $openhasp->getConfiguration('conf::mqtt::rootTopic')) {
          $counter += 1;
        }
      }
      /* Cas particulier : suppression d'un équipement avec discovery actif */
      if (1 == config::byKey('mqtt::discovery::running', __CLASS__)) {
        if ($_topic == config::byKey('mqtt::discovery::rootTopic',  __CLASS__)) {
          $counter += 1;
        }        
      }
      if (0 == $counter) {
        mqtt2::removePluginTopic($_topic);
        log::add(__CLASS__, 'info', __('Désabonnement au topic MQTT', __FILE__) . ' ' . $_topic);
      }
    }
  }

  public static function handleMqttPublish($_topic , $_value) {
    try {
      if (!class_exists('mqtt2')) {
        include_file('core', 'mqtt2', 'class', 'mqtt2');
      }
      mqtt2::publish($_topic, $_value);
      log::add(__CLASS__, 'debug', 'handleMqttPublish Publication Topic ' . $_topic . ' - Valeur ' . $_value);
    } catch (\Throwable $th) {
      log::add(__CLASS__, 'error', $this->getHumanName() . ' ' . __('Erreur lors de l\'éxécution de la commande', __FILE__) . ' : ' . $th);
    }
  }
  
  public static function mqttDiscovery($_mode, $_mqttRootTopic) {
    log::add(__CLASS__, 'debug', 'mqttDiscovery mode=' . $_mode . ' - mqttRootTopic=' . $_mqttRootTopic);
    switch ($_mode) {
      case 0:
        log::add(__CLASS__, 'info', __('Arrêt du mode inclusion automatique', __FILE__));
          if (0 == config::byKey('mqtt::discovery::running', __CLASS__))
          {
            throw new Exception(__("mqttDiscovery erreur : mode inclusion automatique non lancé", __FILE__));
          }
          config::save('mqtt::discovery::running', 0, __CLASS__);
          $cron = cron::byClassAndFunction('openhasp', 'mqttDiscoveryCron');
          if (is_object($cron)) {
            $cron->stop();
          }
          $mqttRootTopic = config::byKey('mqtt::discovery::rootTopic',  __CLASS__);
          self::handleMqttSubscription('unsuscribe', $mqttRootTopic);
          config::save('mqtt::discovery::rootTopic', '',  __CLASS__);
          config::save('mqtt::discovery::duration::elapsed', -1,  __CLASS__);
        break;
      
        case 1:
          log::add(__CLASS__, 'info', __('Démarrage du mode inclusion automatique', __FILE__) . ' - ' . __('Ecoute du topic racine MQTT', __FILE__) . ' ' . $_mqttRootTopic);
          if (1 == config::byKey('mqtt::discovery::running', __CLASS__))
          {
            throw new Exception(__("mqttDiscovery erreur : mode inclusion automatique déjà en cours", __FILE__));
          }
          $cron = cron::byClassAndFunction('openhasp', 'mqttDiscoveryCron');
          if (is_object($cron)) {
            $cron->remove();
            $cron = null;
          }
          $cron = new cron();
          $cron->setClass('openhasp');
          $cron->setFunction('mqttDiscoveryCron');
          $cron->setEnable(1);
          $cron->setDeamon(1);
          $cron->setDeamonSleepTime(60);
          $cron->setSchedule('* * * * *');
          $cron->setTimeout(1440);
          $cron->save();
          
          self::handleMqttSubscription('suscribe', $_mqttRootTopic);
          self::handleMqttPublish($_mqttRootTopic . '/discovery', '');
          config::save('mqtt::discovery::rootTopic', $_mqttRootTopic,  __CLASS__);
          config::save('mqtt::discovery::duration::elapsed', 0,  __CLASS__);
          config::save('mqtt::discovery::running', 1,  __CLASS__);
          $cron->run();
          break;
      default:
        throw new Exception(__("mqttDiscovery erreur : mode inconnu", __FILE__));
        break;
    }
  }

  public static function mqttDiscoveryCron() {
    log::add('openhasp', 'debug', 'mqttDiscoveryCron running = ' . config::byKey('mqtt::discovery::running', __CLASS__) . ' discoveryDurationElapsed = ' . config::byKey('mqtt::discovery::duration::elapsed',  __CLASS__) . ' - discoveryDurationMaximum = ' . config::byKey('mqtt::discovery::duration::maximum',  __CLASS__));
    if (0 == config::byKey('mqtt::discovery::running', __CLASS__)) {
      return;
    }
    
    $discoveryDurationElapsed = config::byKey('mqtt::discovery::duration::elapsed',  __CLASS__);
    if (-1 == $discoveryDurationElapsed) {
      return;
    }
    
    $discoveryDurationMaximum = config::byKey('mqtt::discovery::duration::maximum',  __CLASS__);
    if (!is_numeric($discoveryDurationMaximum) || $discoveryDurationMaximum <= 1) {
      $discoveryDurationMaximum = 12; // 12 min par défaut
    }
    
    if ($discoveryDurationElapsed >= $discoveryDurationMaximum) {
      self::mqttDiscovery(0,'');
      event::add('openhasp::MainPage::setDiscoveryButtonDisable', 'bt_discoveryStop');
      return;
    }
    
    if ($discoveryDurationElapsed > 0) {
      $mqttRootTopic = config::byKey('mqtt::discovery::rootTopic',  __CLASS__);
      self::handleMqttPublish($mqttRootTopic . '/discovery', '');
    }

    config::save('mqtt::discovery::duration::elapsed', $discoveryDurationElapsed + 1,  __CLASS__);    
  }


  public static function cron10() {
    /* Boucle sur chaque équipement du plugin openhasp pour se ré-abonner */
    foreach (eqLogic::byType(__CLASS__, true) as $openhasp) {
      $topic = $openhasp->getConfiguration('conf::mqtt::rootTopic');
      if ('' != $topic) {
        self::handleMqttSubscription('suscribe', $topic);
    }
  }

    /* Suppression du daemon discovery */
    /* On peut pas le supprimer dans la fonction mqttDiscovery quand elle est appelée par ce même daemon */
    if (0 == config::byKey('mqtt::discovery::running', __CLASS__)) {
      $cron = cron::byClassAndFunction('openhasp', 'mqttDiscoveryCron');
      if (is_object($cron)) {
        $cron->remove();
        $cron = null;
      }
    }
  }
  
  public static function checkAndGetValue($valueToCheck, $defaultValue)
  {
    if (isset($valueToCheck)) {
      return $valueToCheck;
    } else {
      return $defaultValue;
    }
  }

  /*
	* Name: getAsJsonl
	* Descr: get on url for a jsonl file
 	*/
	public static function getAsJsonl($url) {
    $return = '';
		$request_http = new com_http($url);
		try {
			$return = $request_http->exec(10, 1);
		} catch (Exception $e) {
      log::add(__CLASS__, 'error', 'getAsJsonl Erreur - ' . $e);
    }
    // log::add(__CLASS__, 'debug', 'getAsJsonl return = ' . print_r($return, true));
		return $return;
	}

    /*
	* Name: getConfiguration
	* Descr: get on url for a json file
 	*/
	public static function getAsJson($url, $username, $password) {
    $return = '';
		$request_http = new com_http($url, $username, $password);
		try {
			$return = json_decode($request_http->exec(10,1), true);
		} catch (Exception $e) {
      log::add(__CLASS__, 'error', 'getAsJson Error - ' . $e);
    }
		return $return;
	}

  public static function replaceUnicodeCharacters($_text) {
    $return = $_text;

    $listUnicodeElements = preg_split ('/\r?\n/', config::byKey('text::unicode', 'openhasp'));
    foreach ($listUnicodeElements as $unicodeElement) {
      $unicodeElementSplitted = explode(':', $unicodeElement);
      $return = str_replace($unicodeElementSplitted[0], $unicodeElementSplitted[1], $return);
    }

    // log::add(__CLASS__, 'debug', 'replaceUnicodeCharacters  in ' . print_r($_text, true));
    // log::add(__CLASS__, 'debug', 'replaceUnicodeCharacters out ' . print_r($return, true));
    return $return;
  }
	// /***/

   /*
	* Name: extractObjectsFromJsonl
	* Descr: Convert data from jsonl file into proper array
 	*/
	public static function extractObjectsFromJsonl($_jsonl) {
		$return = array();
    $page = -1;
    $jsonl = preg_replace('/\s+/', '', $_jsonl);
    foreach (explode('}{', '}' . $jsonl . '{') as $element)
    {
      if ('' != $element) {
        $elementDecoded = json_decode('{' . $element . '}', true, 512, JSON_INVALID_UTF8_IGNORE);
        if (array_key_exists('page', $elementDecoded) || array_key_exists('id', $elementDecoded)) {
          if (array_key_exists('page', $elementDecoded)) {
              $page = $elementDecoded['page'];
          } else {
            if ($page >= 0){
              $elementDecoded['page'] = $page;
            }
          }
          if (array_key_exists('id', $elementDecoded)) {
            array_push($return, $elementDecoded);
          }
        }
      }
    }
    log::add(__CLASS__, 'debug', 'extractObjectsFromJsonl return = ' . print_r($return, true));
		return $return;
	}

	/***/
	public static function checkIp($_ip) {
    /* Vérifie si l'adresse IP n'est pas vide */
    if ('' == $_ip) {
      throw new Exception(__("Échec vérification IP : Adresse IP vide", __FILE__));
    }

    /* Vérifie si l'adresse IP est valide : c'est à dire au bon format */
    if (!filter_var($_ip, FILTER_VALIDATE_IP)) {
      throw new Exception(__("Échec vérification IP : Adresse IP invalide", __FILE__) . ' ' . $ipAddress);
    }

    /* Vérifie si l'adresse IP est joingnable : ping */
		exec(system::getCmdSudo() . 'ping -n -c 1 -t 255 ' . $_ip . ' 2>&1 > /dev/null', $output, $return_val);
		if (0 != $return_val) {
      throw new Exception(__("Échec vérification IP : Impossible de se connecter à l'hôte ayant pour adresse IP", __FILE__) . ' ' . $ipAddress);
    }
  }


  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
    $this->setIsEnable(1);
    $this->setIsVisible(1);
    $this->setConfiguration('validateConfigByMqttRequested', 0);
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
    // log::add(__CLASS__, 'debug', 'postInsert');
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
    // log::add(__CLASS__, 'debug', 'preUpdate');
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
    // log::add(__CLASS__, 'debug', 'postUpdate');
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
    // log::add(__CLASS__, 'debug', 'preSave');
  }
  
  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    // log::add(__CLASS__, 'debug', 'postSave');
    $commandOrder = 1;
    /* Commande Action Refresh */
    $action = $this->getCmd(null, 'command/statusupdate');
    if (!is_object($action)) {
      $action = new openhaspCmd();
      $action->setLogicalId('command/statusupdate');
      $action->setEqLogic_id($this->getId());
      $action->setName(__('Rafraîchir', __FILE__));
      $action->setType('action');
      $action->setSubType('other');
      $action->setOrder($commandOrder++);
      $action->setConfiguration('type', 'general');
      $action->save();
    }
    
    /* Commande Info Last seen */
    $info = $this->getCmd(null, 'lastSeen');
    if (!is_object($info)) {
      $info = new openhaspCmd();
      $info->setLogicalId('lastSeen');
      $info->setEqLogic_id($this->getId());
      $info->setName(__('Vu pour la dernière fois', __FILE__));
      $info->setType('info');
      $info->setSubType('string');
      $info->setOrder($commandOrder++);
      $info->setConfiguration('type', 'general');
      $info->save();
    }
    
    /* Commande Info Adresse IP */
    $info = $this->getCmd(null, 'state/statusupdate/ip');
    if (!is_object($info)) {
      $info = new openhaspCmd();
      $info->setLogicalId('state/statusupdate/ip');
      $info->setEqLogic_id($this->getId());
      $info->setName(__('IP', __FILE__));
      $info->setType('info');
      $info->setSubType('string');
      $info->setOrder($commandOrder++);
      $info->setConfiguration('type', 'general');
      $info->save();
    }
    
    /* Commande Info Largeur de l'écran en pixel */
    $info = $this->getCmd(null, 'state/statusupdate/tftWidth');
    if (!is_object($info)) {
      $info = new openhaspCmd();
      $info->setLogicalId('state/statusupdate/tftWidth');
      $info->setEqLogic_id($this->getId());
      $info->setName(__('Largeur écran', __FILE__));
      $info->setType('info');
      $info->setSubType('numeric');
      $info->setUnite('px');
      $info->setOrder($commandOrder++);
      $info->setConfiguration('type', 'general');
      $info->save();
    }
    
    /* Commande Info Hauteur de l'écran en pixel */
    $info = $this->getCmd(null, 'state/statusupdate/tftHeight');
    if (!is_object($info)) {
      $info = new openhaspCmd();
      $info->setLogicalId('state/statusupdate/tftHeight');
      $info->setEqLogic_id($this->getId());
      $info->setName(__('Hauteur écran', __FILE__));
      $info->setType('info');
      $info->setSubType('numeric');
      $info->setUnite('px');
      $info->setOrder($commandOrder++);
      $info->setConfiguration('type', 'general');
      $info->save();
    }
    
    /* Commande Info / Action Numéro de la page courante */
    $info = $this->getCmd(null, 'state/page');
    if (!is_object($info)) {
      $info = new openhaspCmd();
      $info->setLogicalId('state/page');
      $info->setEqLogic_id($this->getId());
      $info->setName(__('Page courante', __FILE__));
      $info->setType('info');
      $info->setSubType('numeric');
      $info->setOrder($commandOrder++);
      $info->setConfiguration('type', 'general');
      $info->save();
    }
    $action = $this->getCmd(null, 'command/page');
    if (!is_object($action)) {
      $action = new openhaspCmd();
      $action->setLogicalId('command/page');
      $action->setEqLogic_id($this->getId());
      $action->setName(__('Page courante', __FILE__) . ' ' . __('Commande', __FILE__));
      $action->setType('action');
      $action->setSubType('slider');
      $action->setValue($info->getId());
      $action->setConfiguration('message','#slider#');
      $action->setConfiguration('minValue','1');
      $action->setOrder($commandOrder++);
      $action->setConfiguration('type', 'general');
      $action->save();
      // $numberOfObjectsAdded++;
    }
    
    /* Commande Info / Action pour la mise en veille de l'écran */
    $info = $this->getCmd(null, 'state/idle');
    if (!is_object($info)) {
      $info = new openhaspCmd();
      $info->setLogicalId('state/idle');
      $info->setEqLogic_id($this->getId());
      $info->setName(__('Veille de l\'écran', __FILE__));
      $info->setType('info');
      $info->setSubType('string');
      $info->setOrder($commandOrder++);
      $info->setConfiguration('type', 'general');
      $info->save();
    }
    $action = $this->getCmd(null, 'command/idle');
    if (!is_object($action)) {
      $action = new openhaspCmd();
      $action->setLogicalId('command/idle');
      $action->setEqLogic_id($this->getId());
      $action->setName(__('Veille de l\'écran', __FILE__) . ' ' . __('Commande', __FILE__));
      $action->setType('action');
      $action->setSubType('select');
      $action->setValue($info->getId());
      $action->setConfiguration('message','#select#');
      $action->setConfiguration('listValue','off|OFF;short|Court;long|Long');
      $action->setOrder($commandOrder++);
      $action->setConfiguration('type', 'general');
      $action->save();
      // $numberOfObjectsAdded++;
    }
    
    /* Commande Info / Action pour l'état et la luminosité de l'écran */
    $info = $this->getCmd(null, 'state/backlight/state');
    if (!is_object($info)) {
      $info = new openhaspCmd();
      $info->setLogicalId('state/backlight/state');
      $info->setEqLogic_id($this->getId());
      $info->setName(__('État de l\'écran', __FILE__));
      $info->setType('info');
      $info->setSubType('string');
      $info->setOrder($commandOrder++);
      $info->setConfiguration('type', 'general');
      $info->save();
    }
    $info = $this->getCmd(null, 'state/backlight/brightness');
    if (!is_object($info)) {
      $info = new openhaspCmd();
      $info->setLogicalId('state/backlight/brightness');
      $info->setEqLogic_id($this->getId());
      $info->setName(__('Luminosité de l\'écran', __FILE__));
      $info->setType('info');
      $info->setSubType('numeric');
      $info->setOrder($commandOrder++);
      $info->setConfiguration('type', 'general');
      $info->save();
    }
    // $action = $this->getCmd(null, 'command/backlight', null, true);
    // if (!is_array($action) && !is_object($action)) {
    //   $action = new openhaspCmd();
    //   $action->setLogicalId('command/backlight');
    //   $action->setEqLogic_id($this->getId());
    //   $action->setName(__('Écran ON', __FILE__) . ' ' . __('Commande', __FILE__));
    //   $action->setType('action');
    //   $action->setSubType('slider');
    //   //$action->setValue($info->getId());
    //   $action->setConfiguration('message','json::{"state":"on","brightness":#slider#}');
    //   $action->setConfiguration('minValue','1');
    //   $action->setConfiguration('maxValue','255');
    //   $action->save();
    //   $action = new openhaspCmd();
    //   $action->setLogicalId('command/backlight');
    //   $action->setEqLogic_id($this->getId());
    //   $action->setName(__('Écran OFF', __FILE__) . ' ' . __('Commande', __FILE__));
    //   $action->setType('action');
    //   $action->setSubType('other');
    //   //$action->setValue($info->getId());
    //   $action->setConfiguration('message','0');
    //   $action->save();
    // }

    /* S'abonner au topic MQTT */
    if ($this->getIsEnable()) {
      $rootTopic = $this->getConfiguration('conf::mqtt::rootTopic');
      if ('' != $rootTopic) {
        self::handleMqttSubscription('suscribe', $rootTopic);
      }
    }
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {

  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
    $rootTopic = $this->getConfiguration('conf::mqtt::rootTopic');
    if ('' != $rootTopic) {
      self::handleMqttSubscription('unsuscribe', $rootTopic);
    }
  }

  /**
   * Valider la configuration de l'équipemet depuis son adresse IO
   * @param string $ipAddress Adresse IP de l'équipement
   * @param string $httpUsername Identifiant de connexion HTTP, peut être laissé vide si non configuré dans l'équipement
   * @param string $httpPassword Mot de passe de connexion HTTP, peut être laissé vide si non configuré dans l'équipement
   */
  public function validateConfigByIp($ipAddress, $httpUsername = '', $httpPassword = '', $refreshPage = false) {
    log::add(__CLASS__, 'info', 'Configuration de l\'équipement ' . $this->getHumanName() . ' avec son adresse IP ' . $ipAddress);

    /* Vérifie l'adresse IP fournie */
    self::checkIp($ipAddress);
    
    /* Supprime la configuration actuelle */
    self::clearConfiguration();

    /* Récupération de la configuration de l'écran */
    $config = self::getAsJson('http://' . $ipAddress . '/api/config/', $httpUsername, $httpPassword);
    if (is_null($config)) {
      /* Cas où un nom d'utilisatur et un mot de passe ont été définis pour se connecter en http */
      throw new \Exception(__('Erreur en récupérant les données : vérifier le nom d\'utilisateur et le mot de passe. Section \'Configuration HTTP\'', __FILE__));
    }
    $this->setConfiguration('conf::startLayout', $config['hasp']['pages']);
    $this->setConfiguration('conf::mqtt:brokerIp', $config['mqtt']['host']);
    $this->setConfiguration('conf::mqtt::rootTopic', explode('/', $config['mqtt']['topic']['node'])[0]);
    $this->setConfiguration('conf::mqtt::name', $config['mqtt']['name']);
    $newLogicalId = explode('/', $config['mqtt']['topic']['node'])[0] . '/' . $config['mqtt']['name'];
    
    $config = self::getAsJson('http://' . $ipAddress . '/api/info/', $httpUsername, $httpPassword);
    $this->setConfiguration('conf::version', $config['openHASP']['Version']);
    $this->setConfiguration('conf::wifi::ssid', $config['Wifi']['SSID']);
    $this->setConfiguration('conf::wifi::ip', $config['Wifi']['IP Address']);

    /* Changement du logicalId de l'équipement */
    $this->setLogicalId($newLogicalId);

    /* Sauvegarde de la configuration par IP */
    $this->setConfiguration('updateConf_byIp_ip', $ipAddress);
    $this->setConfiguration('updateConf_byIp_http_username', $httpUsername);
    $this->setConfiguration('updateConf_byIp_http_password', $httpPassword);

    /* Sauvegarde des modifications */
    $this->save(true);
    $this->setChanged(0);

    /* S'abonne au topic MQTT */
    $rootTopic = $this->getConfiguration('conf::mqtt::rootTopic');
    self::handleMqttSubscription('suscribe', $rootTopic);

    /* Rafraichissement de la page de l'équipement */
    if ($refreshPage) {
      event::add('openhasp::equipment::reload', $this->getId());
    }
  }

  
  /**
   * Valider la configuration de l'équipemet avec MQTT
   * @param string $rootTopic Topic MQTT principal
   * @param string $rootName Nom de l'équipement sur MQTT 
   */
  public function validateConfigByMqtt($rootTopic, $rootName) {
    log::add(__CLASS__, 'info', 'Configuration de l\'équipement ' . $this->getHumanName() . ' avec MQTT ' . $rootTopic . '/' . $rootName);

    /* Vérification que le sujet racine n'est pas vide */
    if ('' == $rootTopic)
    {
       throw new Exception(__("Renseigner un topic racine !", __FILE__));
    }
    /* Vérification que le nom d'hôte n'est pas vide */
    if ('' == $rootName)
    {
        throw new Exception(__("Renseigner un tnom d'hôte !", __FILE__));
    }

    /* Supprime la configuration actuelle */
    self::clearConfiguration();

    /* Sauvegarde de la configuration par MQTT */
    $this->setConfiguration('updateConf_byMqtt_topic', $rootTopic);
    $this->setConfiguration('updateConf_byMqtt_name', $rootName);
    $this->setConfiguration('validateConfigByMqttRequested', 1);
    
    /* Sauvegarde des modifications */
    $this->save(true);
    $this->setChanged(0);   
    
    /* S'abonne au topic MQTT */
    self::handleMqttSubscription('suscribe', $rootTopic);

    /* Publie sur MQTT une demande d'actualisation de l'état */
    self::handleMqttPublish($rootTopic . '/' . $rootName . '/command/statusupdate','');

    /* Timeout */
    // ?
  }

  public function clearConfiguration() {
    $this->setConfiguration('conf::startLayout', '');
    $this->setConfiguration('conf::mqtt:brokerIp', '');
    $this->setConfiguration('conf::mqtt::rootTopic', '');
    $this->setConfiguration('conf::mqtt::name', '');
    $this->setConfiguration('conf::version', '');
    $this->setConfiguration('conf::wifi::ssid', '');
    $this->setConfiguration('conf::wifi::ip', '');
    self::handleMqttSubscription('unsuscribe', $this->getConfiguration('conf::mqtt::rootTopic'));
    
    /* Sauvegarde des modifications */
    $this->save(true);
    $this->setChanged(0);
  }

  public function importCommands() {
    /* Vérification que les paramètres de configuration utilisés ne sont pas vides */
    $topic = $this->getConfiguration('conf::mqtt::rootTopic');
    $hostname = $this->getConfiguration('conf::mqtt::name');
    $pageName = $this->getConfiguration('conf::startLayout');
    $ip = $this->getConfiguration('conf::wifi::ip');
    if ('' == $topic || '' == $hostname || '' == $pageName || $ip == '') {
      throw new Exception(__("Configurer l'équipement avec son adresse IP !", __FILE__));
    }

    $url = 'http://' . $ip . $pageName;
    $jsonl = self::getAsJsonl($url);
    $jsonlCleared = self::replaceUnicodeCharacters($jsonl);
    $objects = $this->extractObjectsFromJsonl($jsonlCleared);
    $numberOfObjectsAdded = 0;
    foreach ($objects as $object) {
      /* On saute les objets qui ont des actions associées : c'est du fonctionnement interne à l'écran */
      if (isset($object['action'])) {
        continue;
      }

      /* Cas particulier : bouton toggle*/
      if ('btn' == $object['obj'] && isset($object['toggle']) && true == $object['toggle']) {
        $object['obj'] = 'btn_toggle';
      }

      /* Le texte d'un objet peut être vide : on affichera son id à la place */
      if ('' == $object['text']) {
        $object['text'] = $object['id'];
      }

      $objectReference = 'p' . $object['page'] . 'b' . $object['id'];
      log::add(__CLASS__, 'debug', 'importCommands - Reference = ' . $objectReference . ' - Object = ' . print_r($object, true));
      
      if (in_array($object['obj'], array('btn'))) {
        if ('btn' == $object['obj']) {
          $displayableTypeName = __('Bouton', __FILE__);
        }
        log::add(__CLASS__, 'debug', 'importCommands ' . $displayableTypeName . ' = ' . $object['text']);
        $info = $this->getCmd(null, 'state/' . $objectReference . '/event');
        if (!is_object($info)) {
          $info = new openhaspCmd();
          $info->setLogicalId('state/' . $objectReference . '/event');
          $info->setEqLogic_id($this->getId());
          $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['text']);
          $info->setType('info');
          $info->setSubType('string');
          $info->setConfiguration('type', 'specific');
          $info->setConfiguration('page',  $object['page']);
          $info->save();
          $numberOfObjectsAdded++;
        }
      }

      if (in_array($object['obj'], array('checkbox', 'switch', 'btn_toggle'))) {
        if ('checkbox' == $object['obj']) {
          $displayableTypeName = __('Checkbox', __FILE__);
        }
        if ('switch' == $object['obj']) {
          $displayableTypeName = __('Switch', __FILE__);
        }
        if ('btn_toggle' == $object['obj']) {
          $displayableTypeName = __('Bouton Toggle', __FILE__);
        }
        log::add(__CLASS__, 'debug', 'importCommands ' . $displayableTypeName . ' = ' . $object['text']);
        $info = $this->getCmd(null, 'state/' . $objectReference . '/val');
        if (!is_object($info)) {
          $info = new openhaspCmd();
          $info->setLogicalId('state/' . $objectReference . '/val');
          $info->setEqLogic_id($this->getId());
          $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['text']);
          $info->setType('info');
          $info->setSubType('string');
          $info->setConfiguration('type', 'specific');
          $info->setConfiguration('page',  $object['page']);
          $info->save();
          $numberOfObjectsAdded++;
        }
        $action = $this->getCmd(null, 'command/' . $objectReference . '.val');
        if (!is_object($action)) {
          $action = new openhaspCmd();
          $action->setLogicalId('command/' . $objectReference . '.val');
          $action->setEqLogic_id($this->getId());
          $action->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['text'] . ' ' . __('Commande', __FILE__));
          $action->setType('action');
          $action->setSubType('select');
          $action->setValue($info->getId()); /* $info précédente */
          $action->setConfiguration('message','#select#');
          $action->setConfiguration('listValue','0|OFF;1|ON');
          $action->setConfiguration('type', 'specific');
          $action->setConfiguration('page',  $object['page']);
          $action->save();
          $numberOfObjectsAdded++;
        }
      }

      if (in_array($object['obj'], array('cpicker'))) {
        if ('cpicker' == $object['obj']) {
          $displayableTypeName = __('Sélecteur de couleurs', __FILE__);
        }
        log::add(__CLASS__, 'debug', 'importCommands ' . $displayableTypeName . ' = ' . $object['text']);
        $info = $this->getCmd(null, 'state/' . $objectReference . '/color');
        if (!is_object($info)) {
          $info = new openhaspCmd();
          $info->setLogicalId('state/' . $objectReference . '/color');
          $info->setEqLogic_id($this->getId());
          $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['text']);
          $info->setType('info');
          $info->setSubType('string');
          $info->setConfiguration('type', 'specific');
          $info->setConfiguration('page',  $object['page']);
          $info->save();
          $numberOfObjectsAdded++;
        }
        /* TODO : couleur envoyée (command) != couleur reçue (state), à investiguer */
        $action = $this->getCmd(null, 'command/' . $objectReference . '.color');
        if (!is_object($action)) {
          $action = new openhaspCmd();
          $action->setLogicalId('command/' . $objectReference . '.color');
          $action->setEqLogic_id($this->getId());
          $action->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . __('Sélecteur de couleurs', __FILE__) . ' ' . $object['text'] . ' ' . __('Commande', __FILE__));
          $action->setType('action');
          $action->setSubType('color');
          $action->setValue($info->getId()); /* $info précédente */
          $action->setConfiguration('message','#color#');
          $action->setConfiguration('type', 'specific');
          $action->setConfiguration('page',  $object['page']);
          $action->save();
          $numberOfObjectsAdded++;
        }
      }

      if (in_array($object['obj'], array('arc', 'bar', 'gauge', 'led', 'linemeter', 'slider'))) {
        if ('arc' == $object['obj']) {
          $displayableTypeName = __('Arc', __FILE__);
          $defaultMin = 0;
          $defaultMax = 100;
        }
        if ('bar' == $object['obj']) {
          $displayableTypeName = __('Barre de progression', __FILE__);
          $defaultMin = 0;
          $defaultMax = 100;
        }
        if ('gauge' == $object['obj']) {
          $displayableTypeName = __('Jauge', __FILE__);
          $defaultMin = 0;
          $defaultMax = 100;
        }
        if ('led' == $object['obj']) {
          $displayableTypeName = __('LED', __FILE__);
          $defaultMin = 0;
          $defaultMax = 255;
        }
        if ('linemeter' == $object['obj']) {
          $displayableTypeName = __('Line meter', __FILE__);
          $defaultMin = 0;
          $defaultMax = 100;
        }
        if ('slider' == $object['obj']) {
          $displayableTypeName = __('Curseur', __FILE__);
          $defaultMin = 0;
          $defaultMax = 100;
        }
        log::add(__CLASS__, 'debug', 'importCommands ' . $displayableTypeName . ' = ' . $object['text']);
        $info = $this->getCmd(null, 'state/' . $objectReference . '/val');
        if (!is_object($info)) {
          $info = new openhaspCmd();
          $info->setLogicalId('state/' . $objectReference . '/val');
          $info->setEqLogic_id($this->getId());
          $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['text']);
          $info->setType('info');
          $info->setSubType('numeric');
          $info->setConfiguration('type', 'specific');
          $info->setConfiguration('page',  $object['page']);
          $info->save();
          $numberOfObjectsAdded++;
        }
        $action = $this->getCmd(null, 'command/' . $objectReference . '.val');
        if (!is_object($action)) {
          $action = new openhaspCmd();
          $action->setLogicalId('command/' . $objectReference . '.val');
          $action->setEqLogic_id($this->getId());
          $action->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['text'] . ' ' . __('Commande', __FILE__));
          $action->setType('action');
          $action->setSubType('slider');
          $action->setValue($info->getId()); /* $info précédente */
          $action->setConfiguration('message','#slider#');
          $action->setConfiguration('minValue', self::checkAndGetValue($object['min'], $defaultMin));
          $action->setConfiguration('maxValue', self::checkAndGetValue($object['max'], $defaultMax));
          $action->setConfiguration('type', 'specific');
          $action->setConfiguration('page',  $object['page']);
          $action->save();
          $numberOfObjectsAdded++;
        }
      }
    }

    if ($numberOfObjectsAdded > 0 ) {
      log::add(__CLASS__, 'debug', 'importCommands ' . $numberOfObjectsAdded . ' objet(s) ajouté(s)');
      $this->save(true);
      $this->setChanged(0);
      event::add('openhasp::equipment::reload', $this->getId());
    }
  }

  /*     * **********************Getteur Setteur*************************** */

}

class openhaspCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
    if ($this->getType() != 'action') {
      return;
    }

    $eqLogic = $this->getEqLogic();
    $rootTopic = $eqLogic->getConfiguration('conf::mqtt::rootTopic');
    $rootName = $eqLogic->getConfiguration('conf::mqtt::name');

    /* Vérification que le sujet racine et le nom d'hôte ne sont pas vide */
    if ('' == $rootName || '' == $rootTopic)
    {
      throw new Exception(__("Configurer d\'abord l'équipement", __FILE__));
    }

		/* Cas du bouton Refresh */
		if ($this->getLogicalId() == 'command/statusupdate') {
      /* Publie sur MQTT une demande d'actualisation de l'état pour les commandes générales */
      $eqLogic->handleMqttPublish($rootTopic . '/' . $rootName . '/command/statusupdate','');
      /* Envoie une demande d'actualisation pour chaque commande action, sauf la commande Refresh */
      foreach ($eqLogic->getCmd('action') as $cmd) {
        if ($cmd->getLogicalId() == $this->getLogicalId()) {
          /* Ne pas refaire la commande Refresh */
          continue;
        }
        $topicCmd = $cmd->getLogicalId();
        $value = $cmd->getConfiguration('message');
        /* Demande d'actualisation = commande sans valeur */
        $eqLogic->handleMqttPublish($rootTopic . '/' . $rootName . '/' . $topicCmd, '');
      }
			return;
		}
		/***/

    $topicCmd = $this->getLogicalId();
    $value = $this->getConfiguration('message');
    switch ($this->getSubType()) {
      case 'slider':
        $value = str_replace('#slider#', $_options['slider'], $value);
        break;
      case 'color':
          $value = str_replace('#color#', $_options['color'], $value);
          break;
      case 'select':
        $value = str_replace('#select#', $_options['select'], $value);
        break;
      case 'message':
        $value = str_replace('#message#', $_options['message'], $value);
        $value = str_replace('#title#', $_options['title'], $value);
        break;
    }
    $value = jeedom::evaluateExpression($value);

    $eqLogic->handleMqttPublish($rootTopic . '/' . $rootName . '/' . $topicCmd, $value);
    /* Demande d'actualisation = commande sans valeur */
    $eqLogic->handleMqttPublish($rootTopic . '/' . $rootName . '/' . $topicCmd, '');
  }

  /*     * **********************Getteur Setteur*************************** */
}
