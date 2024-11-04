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
  public static $_encryptConfigKey = array('httpPassword');

  /*     * ***********************Methode static*************************** */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community */
   public static function getConfigForCommunity() {
      // Cette function doit retourner des infos complémentataires sous la forme d'un
      // string contenant les infos formatées en HTML.
      return 'Ajouter facilement dans jeedom un écran sous openHASP : écran tactile personnalisé et connecté via MQTT';
   }

   public static function handleMqttMessage($_message) {
    // log::add(__CLASS__, 'debug', ' MQTT Message brut reçu : ' . print_r($_message,true));

    /* Boucle sur chaque équipement du plugin openhasp */
    foreach (eqLogic::byType(__CLASS__, true) as $openhasp) {
      // log::add(__CLASS__, 'debug', 'Equipement ' . $openhasp->getHumanName());
      /* Configuration par MQTT demandée : on attend la réponse de l'équipement */
      if ($openhasp->getConfiguration('validateConfigByMqttRequested') == 1) {
        $topic = $openhasp->getConfiguration('confMqttRootTopic');
        $hostname = $openhasp->getConfiguration('confMqttRootName');
        $message = $_message[$topic][$hostname];
        if (isset($message['state']['statusupdate'])) {
          $openhasp->setConfiguration('validateConfigByMqttRequested', 0);
          $openhasp->setConfiguration('wifi_ssid', $message['state']['statusupdate']['ssid']);
          $openhasp->setConfiguration('wifi_ip', $message['state']['statusupdate']['ip']);
          $openhasp->setConfiguration('mqtt_topic_root', $topic);
          $openhasp->setConfiguration('mqtt_name', $hostname);
          $openhasp->setConfiguration('confIpAddress', $message['state']['statusupdate']['ip']);
          /* Sauvegarde des modifications et rafraichissement de la page de l'équipement */
          $openhasp->save(true);
          $openhasp->setChanged(0);
          event::add('openhasp::loadObjects', $openhasp->getId());
        }
      }
      /* Cas normal : on traite le message reçu par rapport à l'équipement courant */
      $topic = $openhasp->getConfiguration('mqtt_topic_root');
      $hostname = $openhasp->getConfiguration('mqtt_name');
      $message = $_message[$topic][$hostname];
      /* Si le message mqtt reçu correpond à l'équipement courant */
      if (isset($message)) {
        $cmdLastSeen = $openhasp->getCmd('info', 'lastSeen');
        $equipmentUpdated = false;
        /* Boucle sur chaque commande de type info de l'équipement courant */
        foreach ($openhasp->getCmd('info') as $cmd) {
          // log::add(__CLASS__, 'debug', ' - Commande ' . $cmd->getHumanName() . ' - ' . $cmd->getConfiguration('mqttTopicCmd'));
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
            log::add(__CLASS__, 'debug', 'Equipement ' . $openhasp->getHumanName() . ' - Mise à jour commande ' . $cmd->getHumanName() . ' avec valeur ' . $subMessage);
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
    if (!class_exists('mqtt2')) {
      include_file('core', 'mqtt2', 'class', 'mqtt2');
    }
    $subscribed = isset(mqtt2::getSubscribed()[$_topic]);
    if ($_action == 'suscribe' && !$subscribed) {
      mqtt2::addPluginTopic(__CLASS__, $_topic);
      log::add(__CLASS__, 'debug', 'Abonnement au topic ' . $_topic);
    } else if ($_action == 'unsuscribe' && $subscribed) {
      mqtt2::removePluginTopic($_topic);
      log::add(__CLASS__, 'error', 'Désabonnement au topic ' . $_topic);
    } else if ($_action == 'suscribe' && $subscribed) {
      log::add(__CLASS__, 'debug', 'Déjà abonné au topic ' . $_topic);
    }
  }

  public static function handleMqttPublish($_topic , $_value) {
    try {
      if (!class_exists('mqtt2')) {
        include_file('core', 'mqtt2', 'class', 'mqtt2');
      }
      mqtt2::publish($_topic, $_value);
      // log::add(__CLASS__, 'debug', 'MQTT - Publication Topic ' . $_topic . ' - Valeur ' . $_value);
    } catch (\Throwable $th) {
      log::add(__CLASS__, 'error', $this->getHumanName() . ' ' . __('Erreur lors de l\'éxécution de la commande', __FILE__) . ' : ' . $th);
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
        $elementDecoded = json_decode('{' . $element . '}', true);
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
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    /* Commande Action Refresh */
    $action = $this->getCmd(null, 'command/statusupdate');
    if (!is_object($action)) {
      $action = new openhaspCmd();
      $action->setLogicalId('command/statusupdate');
      $action->setEqLogic_id($this->getId());
      $action->setName(__('Rafraîchir', __FILE__));
      $action->setType('action');
      $action->setSubType('other');
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
      $action->save();
      $numberOfObjectsAdded++;
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
      $action->save();
      $numberOfObjectsAdded++;
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
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
    $rootTopic = $this->getConfiguration('mqtt_topic_root');
    if ('' != $rootTopic) {
      self::handleMqttSubscription('unsuscribe', $rootTopic);
    }
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /**
   * Valider la configuration de l'équipemet depuis son adresse IO
   * @param string $ipAddress Adresse IP de l'équipement
   * @param string $httpUsername Identifiant de connexion HTTP, peut être laissé vide si non configuré dans l'équipement
   * @param string $httpPassword Mot de passe de connexion HTTP, peut être laissé vide si non configuré dans l'équipement
   */
  public function validateConfigByIp($ipAddress, $httpUsername, $httpPassword) {
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
    $this->setConfiguration('hasp_pages', $config['hasp']['pages']);
    $this->setConfiguration('mqtt_broker', $config['mqtt']['host']);
    $this->setConfiguration('mqtt_topic_root', explode('/', $config['mqtt']['topic']['node'])[0]);
    $this->setConfiguration('mqtt_name', $config['mqtt']['name']);
    
    $config = self::getAsJson('http://' . $ipAddress . '/api/info/', $httpUsername, $httpPassword);
    $this->setConfiguration('openhasp_version', $config['openHASP']['Version']);
    $this->setConfiguration('wifi_ssid', $config['Wifi']['SSID']);
    $this->setConfiguration('wifi_ip', $config['Wifi']['IP Address']);

    /* Sauvegarde de la configuration par IP */
    $this->setConfiguration('confIpAddress', $ipAddress);
    $this->setConfiguration('confIpHttpUsername', $httpUsername);
    $this->setConfiguration('confIpHttpPassword', $httpPassword);

    /* Sauvegarde des modifications */
    $this->save(true);
    $this->setChanged(0);

    /* S'abonne au topic MQTT */
    $rootTopic = $this->getConfiguration('mqtt_topic_root');
    self::handleMqttSubscription('suscribe', $rootTopic);

    /* Rafraichissement de la page de l'équipement */
    event::add('openhasp::loadObjects', $this->getId());
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
    $this->setConfiguration('confMqttRootTopic', $rootTopic);
    $this->setConfiguration('confMqttRootName', $rootName);
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
    self::handleMqttSubscription('unsuscribe', $this->getConfiguration('mqtt_topic_root'));
    $this->setConfiguration('hasp_pages', '');
    $this->setConfiguration('mqtt_broker', '');
    $this->setConfiguration('mqtt_topic_root', '');
    $this->setConfiguration('mqtt_name', '');
    $this->setConfiguration('openhasp_version', '');
    $this->setConfiguration('wifi_ssid', '');
    $this->setConfiguration('wifi_ip', '');
    
    /* Sauvegarde des modifications */
    $this->save(true);
    $this->setChanged(0);
  }

  public function importCommands() {
    /* Vérification que les paramètres de configuration utilisés ne sont pas vides */
    $topic = $this->getConfiguration('mqtt_topic_root');
    $hostname = $this->getConfiguration('mqtt_name');
    $pageName = $this->getConfiguration('hasp_pages');
    $ip = $this->getConfiguration('wifi_ip');
    if ('' == $topic || '' == $hostname || '' == $pageName || $ip == '') {
      throw new Exception(__("Configurer l'équipement avec son adresse IP !", __FILE__));
    }

    $url = 'http://' . $ip . $pageName;
    $jsonl = self::getAsJsonl($url);
    $objects = $this->extractObjectsFromJsonl($jsonl);
    $numberOfObjectsAdded = 0;
    foreach ($objects as $object) {
      // log::add(__CLASS__, 'debug', 'Object');
      $objectReference = 'p' . $object['page'] . 'b' . $object['id'];
      // log::add(__CLASS__, 'debug', ' - Reference = ' . $objectReference);
      switch ($object['obj']) {
        case 'btn':
          // log::add(__CLASS__, 'debug', 'Btn = ' . $object['text']);
          $info = $this->getCmd(null, 'state/' . $objectReference . '/event');
          if (!is_object($info)) {
            $info = new openhaspCmd();
            $info->setLogicalId('state/' . $objectReference . '/event');
            $info->setEqLogic_id($this->getId());
            $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . __('Bouton', __FILE__) . ' ' . $object['text']);
            $info->setType('info');
            $info->setSubType('string');
            $info->save();
            $numberOfObjectsAdded++;
          }
          break;
        case 'checkbox':
          // log::add(__CLASS__, 'debug', 'Checkbox = ' . $object['text']);
          $info = $this->getCmd(null, 'state/' . $objectReference . '/val');
          if (!is_object($info)) {
            $info = new openhaspCmd();
            $info->setLogicalId('state/' . $objectReference . '/val');
            $info->setEqLogic_id($this->getId());
            $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . __('Checkbox', __FILE__) . ' ' . $object['text']);
            $info->setType('info');
            $info->setSubType('string');
            $info->save();
            $numberOfObjectsAdded++;
          }
          $action = $this->getCmd(null, 'command/' . $objectReference . '.val');
          if (!is_object($action)) {
            $action = new openhaspCmd();
            $action->setLogicalId('command/' . $objectReference . '.val');
            $action->setEqLogic_id($this->getId());
            $action->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . __('Checkbox', __FILE__) . ' ' . $object['text'] . ' ' . __('Commande', __FILE__));
            $action->setType('action');
            $action->setSubType('select');
            $action->setValue($info->getId()); /* $info précédente */
            $action->setConfiguration('message','#select#');
            $action->setConfiguration('listValue','0|OFF;1|ON');
            $action->save();
            $numberOfObjectsAdded++;
          }
        case 'cpicker':
          // log::add(__CLASS__, 'debug', 'Cpicker = ' . $object['text']);
          $info = $this->getCmd(null, 'state/' . $objectReference . '/color');
          if (!is_object($info)) {
            $info = new openhaspCmd();
            $info->setLogicalId('state/' . $objectReference . '/color');
            $info->setEqLogic_id($this->getId());
            $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . __('Sélecteur de couleurs', __FILE__) . ' ' . $object['text']);
            $info->setType('info');
            $info->setSubType('string');
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
            $action->save();
            $numberOfObjectsAdded++;
          }
          break;
        case 'slider':
          // log::add(__CLASS__, 'debug', 'Slider = ' . $object['text']);
          $info = $this->getCmd(null, 'state/' . $objectReference . '/val');
          if (!is_object($info)) {
            $info = new openhaspCmd();
            $info->setLogicalId('state/' . $objectReference . '/val');
            $info->setEqLogic_id($this->getId());
            $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . __('Curseur', __FILE__) . ' ' . $object['text']);
            $info->setType('info');
            $info->setSubType('numeric');
            $info->save();
            $numberOfObjectsAdded++;
          }
          $action = $this->getCmd(null, 'command/' . $objectReference . '.val');
          if (!is_object($action)) {
            $action = new openhaspCmd();
            $action->setLogicalId('command/' . $objectReference . '.val');
            $action->setEqLogic_id($this->getId());
            $action->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . __('Curseur', __FILE__) . ' ' . $object['text'] . ' ' . __('Commande', __FILE__));
            $action->setType('action');
            $action->setSubType('slider');
            $action->setValue($info->getId()); /* $info précédente */
            $action->setConfiguration('message','#slider#');
            $action->setConfiguration('minValue', self::checkAndGetValue($object['min'], 0));
            $action->setConfiguration('maxValue', self::checkAndGetValue($object['max'], 100));
            $action->save();
            $numberOfObjectsAdded++;
          }
          break;
        default:
          /* Tous les autres : pas *encore* fait ! */
          break;
      }
    }

    if ($numberOfObjectsAdded > 0 ) {
      log::add(__CLASS__, 'debug', $numberOfObjectsAdded . ' objet(s) ajouté(s)');
      $this->save(true);
      $this->setChanged(0);
      event::add('openhasp::loadObjects', $this->getId());
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
    $rootTopic = $eqLogic->getConfiguration('mqtt_topic_root');
    $rootName = $eqLogic->getConfiguration('mqtt_name');

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
