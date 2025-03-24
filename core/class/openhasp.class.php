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
          foreach(explode('/', str_replace('.', '/', $cmd->getLogicalId())) as $subTopic){
            if (isset($subMessage[$subTopic])) {
              $subMessage = $subMessage[$subTopic];
            } else {
              $cmdToBeUpdated = false;
              break;
            }
          }
          /* Si le message mqtt reçu correspond à la commande courante : on la met à jour */
          if ($cmdToBeUpdated) {
            $subMessage = self::convertReceivedTextToReadableText($subMessage);
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

  public static function handleMqttSubscription($_action, $_topic) {
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

  public static function handleMqttPublish($_topic , $_value, $options = array()) {
    try {
      if (!class_exists('mqtt2')) {
        include_file('core', 'mqtt2', 'class', 'mqtt2');
      }
      if ('' != $_value) {
      $sendValue = self::convertUnicodeInTextToSend($_value);
      } else {
        $sendValue = '';
      }
      mqtt2::publish($_topic, $sendValue, $options);
      log::add(__CLASS__, 'debug', 'handleMqttPublish Publication Topic >' . $_topic . '< - Valeur >' . $sendValue . '< - Options >' . print_r($options, true) . '<');
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

  public static function cron() {
    /* Auto-refresh - Boucle sur chaque équipement du plugin openhasp */
    foreach (eqLogic::byType(__CLASS__, true) as $openhasp) {
      $autoRefresh = $openhasp->getConfiguration('conf::autoRefresh');
      if ('1' == $autoRefresh) {
        $cmd = $openhasp->getCmd(null, 'command/statusupdate');
        if (!is_object($cmd)) {
          $cmd->execute();
        }
      }
    }
  }

  public static function cron5() {
    /* Auto-refresh - Boucle sur chaque équipement du plugin openhasp */
    foreach (eqLogic::byType(__CLASS__, true) as $openhasp) {
      $autoRefresh = $openhasp->getConfiguration('conf::autoRefresh');
      if ('2' == $autoRefresh) {
        $cmd = $openhasp->getCmd(null, 'command/statusupdate');
        if (!is_object($cmd)) {
          $cmd->execute();
        }
      }
    }
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

    /* Auto-refresh - Boucle sur chaque équipement du plugin openhasp */
    foreach (eqLogic::byType(__CLASS__, true) as $openhasp) {
      $autoRefresh = $openhasp->getConfiguration('conf::autoRefresh');
      if ('3' == $autoRefresh) {
        $cmd = $openhasp->getCmd(null, 'command/statusupdate');
        if (!is_object($cmd)) {
          $cmd->execute();
        }
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


  /*
  * Name: replaceUnicodeCharacter
  * Descr: Return displayable character for given unicode
  */
  public static function replaceUnicodeCharacter($unicodeCharacter) {
    $return = '';
    
    // Configuration du plugin : Remplacer les caractères unicode en utilisant le format \uXXXX 
    if ('hex' == config::byKey('unicode::replace::option', 'openhasp')) {
      $return = '\\u' . strtoupper(dechex($unicodeCharacter));
    }
    
    // Configuration du plugin : Remplacer les caractères unicode en utilisant le texte correspondant
    if ('text' == config::byKey('unicode::replace::option', 'openhasp')) {
      $UnicodeAllElements = preg_replace ('/\r?\n/', '|/-(|', config::byKey('text::unicode', 'openhasp')); // Oui il ne faudrait pas que cette chaîne '|/-(|' soit utilisée comme texte
      $UnicodeAllElements =  $UnicodeAllElements . '|/-(|'; // Pour trouver le dernier élément
      $posUnicode = strpos(strtoupper($UnicodeAllElements), strtoupper(dechex($unicodeCharacter)));
      if (true == $posUnicode) {
        $posUnicode1 = strpos($UnicodeAllElements, ';', $posUnicode);
        $posUnicode2 = strpos($UnicodeAllElements, '|/-(|', $posUnicode);
        $return = config::byKey('unicode::replace::text::begin', 'openhasp') . substr($UnicodeAllElements, $posUnicode1+1, $posUnicode2 - $posUnicode1 - 1) . config::byKey('unicode::replace::text::end', 'openhasp');
      } else {
        $return = '\\u' . strtoupper(dechex($characterUnicode));
      }
    }
    
    log::add(__CLASS__, 'debug','replaceUnicodeCharacter - INPUT>' . $unicodeCharacter . '<   OUTPUT>' . $return . '<');
    return $return;
  }

  /*
	* Name: convertReceivedTextToReadableText
	* Descr: Make received text readable by replacing all unicode characters into input UTF-8 text
 	*/
  public static function convertReceivedTextToReadableText($_text) {
    $return = '';

    // Configuration du plugin : Ne pas remplacer les caractères unicode
    if ('no' == config::byKey('unicode::replace::option', 'openhasp')) {
      return $_text;
    }  
    
    $textArray = str_split($_text, 1);
    for ($iCounter = 0 ;  $iCounter < count($textArray) ; /* Incrémentation dans la boucle */ ) { 
        $charNo1 = ord($textArray[$iCounter++]);
        if ($charNo1 >= 0 && $charNo1 < 128) {
            /* UCS code : 0x00000000 - 0x0000007F */
            $return = $return . chr($charNo1);
        } else {
            $charNo2 = ord($textArray[$iCounter++]);
              if ($charNo1 >= 192 && $charNo1 < 224) {
                /* UCS code : 0x00000080 - 0x000007FF */
                $characterUnicode = (($charNo1 & 0x1F) << 6) + ($charNo2 & 0x3F);
                $return = $return . mb_chr($characterUnicode);
            } else {
              $charNo3 = ord($textArray[$iCounter++]);
              if ($charNo1 >= 224 && $charNo1 < 240) {
                  /* UCS code : 0x00000800 - 0x0000FFFF */
                  /* Correspond à tous les exemples disponibles dans la doc openHASP */
                  $characterUnicode = (($charNo1 & 0xF) << 12) + (($charNo2 & 0x3F) << 6) + ($charNo3 & 0x3F);
                  $return = $return . self::replaceUnicodeCharacter($characterUnicode);
              } else {
                $charNo4 = ord($textArray[$iCounter++]);
                if ($charNo1 >= 240 && $charNo1 < 248) {
                      /* UCS code : 0x00010000 - 0x001FFFFF */
                      /* Aucun exemple dans la doc openHASP mais on fait comme si ça fonctionnait pareil */
                      $characterUnicode = (($charNo1 & 0x7) << 18) + (($charNo2 & 0x3F) << 12) + (($charNo3 & 0x3F) << 6) + ($charNo4 & 0x3F);
                      $return = $return . self::replaceUnicodeCharacter($characterUnicode);
                } else {
                  $return = $return + '????';
                }
              }
            }
        }
    }
    return $return;
  }

    /*
	* Name: convertUnicodeInTextToSend
	* Descr: Make text to send corectly encoded : replace \uxxxx and text-unicode-equivalent
 	*/
  public static function convertUnicodeInTextToSend($_text) {
    // Remplacer le texte correspondant à un caractère unicode par son code \uXXXX
    $charBegin = config::byKey('unicode::replace::text::begin', 'openhasp');
    $charBegin = '\\' . implode('\\', str_split($charBegin)); // <3 caractères spéciaux possibles dans la regex
    $charEnd = config::byKey('unicode::replace::text::end', 'openhasp');
    $charEnd = '\\' . implode('\\', str_split($charEnd)); // <3 caractères spéciaux possibles dans la regex
    $pattern = '/'. $charBegin  . '[^' . $charEnd . ']+' . $charEnd . '/';
    $return = preg_replace_callback(
      $pattern,
      function ($matches) {
        $charBegin = config::byKey('unicode::replace::text::begin', 'openhasp');
        $charEnd = config::byKey('unicode::replace::text::end', 'openhasp');
        $textUnicode = substr($matches[0], strlen($charBegin), strlen($matches[0]) - strlen($charBegin) - strlen($charEnd));
        $UnicodeAllElements = preg_replace ('/\r?\n/', '|/-(|', config::byKey('text::unicode', 'openhasp')); // Oui il ne faudrait pas que cette chaîne '|/-(|' soit utilisée comme texte
        $UnicodeAllElements =  '|/-(|' . $UnicodeAllElements; // Pour trouver le premier élément
        $posText = strpos(strtoupper($UnicodeAllElements), strtoupper($textUnicode));
        if (true == $posText) {
          $posUnicode1 = strrpos($UnicodeAllElements, '|/-(|', $posText -strlen($UnicodeAllElements));
          return substr($UnicodeAllElements, $posUnicode1+5, $posText - $posUnicode1 - 6);
        } else {
          return $matches[0]; // retour par défaut si pas de correspondance trouvée
        }
      },
      $_text
    );
    
    // Remplacement du \uxxxx par le caractère unicode correspondant
    $return = preg_replace_callback(
      '/\\\\[uU][A-Fa-f0-9]{4}/',  //   \uXXXX ou \UXXXX --> uniquement 4 caractères !!! Trop compliqué de gérer les auters cas comme \uABC : est ce que les C est pour l'unicode ou un caractère ascii après l'unicode \uAB ?
      function ($matches) {
        $characterUnicode = hexdec(substr($matches[0], 2));
        if ($characterUnicode >= 0 && $characterUnicode <= 0x7F) {
            return $characterUnicode;
        }
        if ($characterUnicode >= 0x80 && $characterUnicode <= 0x7FF) {
            return chr(0xC0 + (($characterUnicode>>6)&0x1F)) . chr(0x80 + ($characterUnicode&0x3F));
        }
        if ($characterUnicode >= 0x800 && $characterUnicode <= 0xFFFF) {
            return chr(0xE0 + (($characterUnicode>>12)&0xF)) . chr(0x80 + (($characterUnicode>>6)&0x3F)) . chr(0x80 + ($characterUnicode&0x3F));
        }
        /* Juste pour le plaisir je garde cette convertion mais ne sera jamais appelé avec {4} du pattern cherché*/
        if ($characterUnicode >= 0x10000 && $characterUnicode <= 0x1FFFFF) {
            return chr(0xF0 + (($characterUnicode>>18)&0x7)) . chr(0x80 + (($characterUnicode>>12)&0x3F)) .chr(0x80 + (($characterUnicode>>6)&0x3F)) . chr(0x80 + ($characterUnicode&0x3F));
        }
        return $matches[0]; // retour par défaut si pas de correspondance trouvé
      },
      $return
    );

    return $return;
  }

  /*
	* Name: extractObjectsFromJsonl
	* Descr: Convert data from jsonl file into proper array
 	*/
	public static function extractObjectsFromJsonl($_jsonl) {
		$return = array();
    $page = -1;
    $jsonl = preg_replace('/}\s+/', '}', $_jsonl);
    $jsonl = preg_replace('/\s+{/', '{', $_jsonl);
    foreach (explode('}{', '}' . $jsonl . '{') as $element)
    {
      if ('' != $element) {
        $elementDecoded = json_decode('{' . $element . '}', true, 2147483647, JSON_INVALID_UTF8_IGNORE );
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
      throw new Exception(__("Échec vérification IP : Adresse IP invalide", __FILE__) . ' ' . $_ip);
    }

    /* Vérifie si l'adresse IP est joingnable : ping */
		exec(system::getCmdSudo() . 'ping -n -c 1 -t 255 ' . $_ip . ' 2>&1 > /dev/null', $output, $return_val);
		if (0 != $return_val) {
      throw new Exception(__("Échec vérification IP : Impossible de se connecter à l'hôte ayant pour adresse IP", __FILE__) . ' ' . $_ip);
    }
  }


  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
    $this->setIsEnable(1);
    $this->setIsVisible(1);
    $this->setConfiguration('validateConfigByMqttRequested', 0);
    $this->setConfiguration('conf::autoRefresh', '0'); // Rafraîchissement automatique désactivé par défaut à la création de l'équipement
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
    // log::add(__CLASS__, 'debug', 'preRemove');
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
    // log::add(__CLASS__, 'debug', 'postRemove');
    $rootTopic = $this->getConfiguration('conf::mqtt::rootTopic');
    if ('' != $rootTopic) {
      self::handleMqttSubscription('unsuscribe', $rootTopic);
    }
  }

  /* Mais pourquoi cette fonction n'est pas dans la doc comme les autres !!!!! */
  /* https://doc.jeedom.com/fr_FR/dev/plugin_template#M%C3%A9thode%20pre%20et%20post */
  public function postAjax () {
    // log::add(__CLASS__, 'debug', 'postAjax');
    
    /* Suppression de tous les objets listener attachés à cet équipement */
    $equipmentListeners = listener::searchClassFunctionOption(
      __CLASS__,
      'openhaspListenerAction',
      '"equipment":"'.$this->getId().'"'
    );
    if (0 != count($equipmentListeners)) {
      while (count($equipmentListeners) > 0) {
        array_pop($equipmentListeners)->remove();
      }
    }
    
    /* Parcourir toutes les commandes de type action */
    foreach ($this->getCmd('action') as $cmdAction) {
      /* Si la commande courante est reliée à une commande info */
      if ((1 == $cmdAction->getConfiguration('linkToCmdInfo', 0)) && ('' != $cmdAction->getConfiguration('cmdInfoJeedomLinked', ''))) {
        /* Récupérer l'objet commande info lié */
        preg_match("/#([0-9]*)#/", $cmdAction->getConfiguration('cmdInfoJeedomLinked', ''), $matches);
        $cmdInfoId = $matches[1];
        $cmdInfo = cmd::byId($cmdInfoId);
        if (is_object($cmdInfo) && $cmdInfo->getType() == 'info') {
          /* Si la commande info liée existe */
          // log::add(__CLASS__, 'debug', 'postAjax cmdInfo >' . print_r($cmdInfo, true) . '<');
          $listener = new listener();
          $listener->setClass(__CLASS__);
          $listener->setFunction('openhaspListenerAction');
          $listener->emptyEvent();
          $listener->addEvent($cmdInfoId);
          $listener->setOption('equipment', $this->getId());
          $listener->setOption('cmdAction', $cmdAction->getId());
          $listener->save();

        }
      }
    }
  }


  public static function openhaspListenerAction($_options) {
    
    /* Récupérer la commande Action à appeler et vérifier qu'elle est valide */
    $cmdAction = cmd::byId($_options['cmdAction']);
    if (!is_object($cmdAction) || !$cmdAction->getEqLogic()->getIsEnable() || !$cmdAction->getType() == 'action') {
      /* Commande non valide : on supprime l'objet listener appelé */
      log::add(__CLASS__, 'info', __FUNCTION__ . ' Commande id=' . $_options['cmdAction'] . 'non valide : objet listener id=' . $_options['listener_id'] . ' supprimé');
      listener::byId($_options['listener_id'])->remove();
      return;
    } 
    
    /* Exécution de la commande */
    log::add(__CLASS__, 'debug', __FUNCTION__ . ' - Publication automatique de la commande ' . $cmdAction->getHumanName() . ' avec la valeur >' . print_r($_options['value'], true) . '<');
    $cmdAction->execute($_options);

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
    $objects = $this->extractObjectsFromJsonl($jsonl);
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

      /* La propriété text contient ce qui est affiché (bref le texte) */
      if ('' == $object['text']) {
        /* Le texte d'un objet peut être vide : on affichera son type et son id à la place */
        $object['text'] = $object['obj'] . ' ' . $object['id'];
      } else {
        /* Gestion de l'unicode pour les objets qui ont la propriété 'text' définie */
        $object['text'] = self::convertReceivedTextToReadableText($object['text']);
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

      if (in_array($object['obj'], array('roller', 'dropdown'))) {
        if ('roller' == $object['obj']) {
          $displayableTypeName = __('Liste tournate', __FILE__);
        }
        if ('dropdown' == $object['obj']) {
          $displayableTypeName = __('Liste déroulante', __FILE__);
        }
        $rollerOptions = $object['options'];
        $rollerOptionsArray = explode("\n", $rollerOptions);
        for($i = 0; $i < count($rollerOptionsArray); $i++) {
            $rollerOptionsArray[$i] = $i . '|' . $rollerOptionsArray[$i];
        }
        $listoptions = implode(";", $rollerOptionsArray);
        log::add(__CLASS__, 'debug', 'importCommands ' . $displayableTypeName . ' = ' . $object['id']);
        $info = $this->getCmd(null, 'state/' . $objectReference . '/val');
        if (!is_object($info)) {
          $info = new openhaspCmd();
          $info->setLogicalId('state/' . $objectReference . '/val');
          $info->setEqLogic_id($this->getId());
          $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['id'] . ' ' . __('Valeur', __FILE__));
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
          $action->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['id'] . ' ' . __('Commande', __FILE__));
          $action->setType('action');
          $action->setSubType('select');
          $action->setValue($info->getId()); /* $info précédente */
          $action->setConfiguration('message','#select#');
          $action->setConfiguration('listValue', $listoptions);
          $action->setConfiguration('type', 'specific');
          $action->setConfiguration('page',  $object['page']);
          $action->save();
          $numberOfObjectsAdded++;
        }
        $info = $this->getCmd(null, 'state/' . $objectReference . '/text');
        if (!is_object($info)) {
          $info = new openhaspCmd();
          $info->setLogicalId('state/' . $objectReference . '/text');
          $info->setEqLogic_id($this->getId());
          $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['id']. ' ' . __('Texte', __FILE__));
          $info->setType('info');
          $info->setSubType('string');
          $info->setConfiguration('type', 'specific');
          $info->setConfiguration('page',  $object['page']);
          $info->save();
          $numberOfObjectsAdded++;
        }
      }

      if (in_array($object['obj'], array('qrcode',))) {
        if ('qrcode' == $object['obj']) {
          $displayableTypeName = __('Qrcode', __FILE__);
        }
        log::add(__CLASS__, 'debug', 'importCommands ' . $displayableTypeName . ' = ' . $object['text']);
        $info = $this->getCmd(null, 'state/' . $objectReference . '/text');
        if (!is_object($info)) {
          $info = new openhaspCmd();
          $info->setLogicalId('state/' . $objectReference . '/text');
          $info->setEqLogic_id($this->getId());
          $info->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['id']);
          $info->setType('info');
          $info->setSubType('string');
          $info->setConfiguration('type', 'specific');
          $info->setConfiguration('page',  $object['page']);
          $info->save();
          $numberOfObjectsAdded++;
        }
        $action = $this->getCmd(null, 'command/' . $objectReference . '.text');
        if (!is_object($action)) {
          $action = new openhaspCmd();
          $action->setLogicalId('command/' . $objectReference . '.text');
          $action->setEqLogic_id($this->getId());
          $action->setName(__('Page', __FILE__) . ' ' . $object['page'] . ' - ' . $displayableTypeName . ' ' . $object['id'] . ' ' . __('Commande', __FILE__));
          $action->setType('action');
          $action->setSubType('other');
          $action->setValue($info->getId()); /* $info précédente */
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

    log::add('openhasp', 'debug', __FUNCTION__ . ' - Commande Action execute() - eqLogic >' . print_r($eqLogic, true) . '<');
    log::add('openhasp', 'debug', __FUNCTION__ . ' - Commande Action execute() - command >' . print_r($this, true) . '<');

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

    /* Comportement différent si exécution manuelle ou automatique sur une commande info (via listener) */
    if (isset($_options['listener_id']) && $_options['listener_id'] > 0) {
      /* Exécution de la commande en automatique : sur un évènement d'une commande info liée */
      if ('' == $value) {
        /* Si le message est vide alors on y force la valeur de la commande info */
        $value = $_options['value'];
      } else {
        switch ($this->getSubType()) {
          case 'slider':
            $value = str_replace('#slider#', $_options['value'], $value);
            break;
          case 'color':
              $value = str_replace('#color#', $_options['value'], $value);
              break;
          case 'select':
            $value = str_replace('#select#', $_options['value'], $value);
            break;
          case 'message':
            $value = str_replace('#message#', $_options['value'], $value);
            break;
        }
      }
    } else {
      /* Autres cas : exécution manuelle */
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
    }
    $value = jeedom::evaluateExpression($value);

    $options = array();
    if (1 == $this->getConfiguration('retainPrev') && 0 == $this->getConfiguration('retain')) {
      /* L'option retain a été suprimée : pour la supprimer il faut envoyer une commande sans valeur avec retain = 1 */      
      $options['retain'] = 1;
      $eqLogic->handleMqttPublish($rootTopic . '/' . $rootName . '/' . $topicCmd, '', $options);
      sleep(1);
    }
    
    $options['retain'] = (1 == $this->getConfiguration('retain')) ? 1 : 0;
    $eqLogic->handleMqttPublish($rootTopic . '/' . $rootName . '/' . $topicCmd, $value, $options);
    if ($this->getConfiguration('retainPrev') != $this->getConfiguration('retain')) {
      $this->setConfiguration('retainPrev', $this->getConfiguration('retain'));
      $this->save(true);
    }

    if (1 == $this->getConfiguration('refresh')) {
      /* Demande d'actualisation = commande sans valeur et avec retain = 0 */
      $options['retain'] = 0;
      $eqLogic->handleMqttPublish($rootTopic . '/' . $rootName . '/' . $topicCmd, '', $options);
    }
  }

  /*     * **********************Getteur Setteur*************************** */
}
