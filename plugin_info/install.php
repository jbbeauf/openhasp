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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement après l'installation du plugin
function openhasp_install() {
    log::add(__CLASS__, 'debug', __('Installation de openhasp...', __FILE__));

    /* Liste des dépendances et leur installation : merci au plugin vlx2mqtt ! */
    $depPlugins = array(
      'mqtt2' => 'MQTT Manager'
    );
    foreach ($depPlugins as $pluginId => $pluginName) {
      try {
        $plugin = plugin::byId($pluginId);
      } catch (Exception $e) {
        $errorMessage = __('Le plugin', __FILE__) . ' ' . $pluginName . ' ' . __('n\'est pas installé', __FILE__);
        log::add(__CLASS__, 'debug', __('Installation abandonnée', __FILE__) . ' : ' . $errorMessage);
        throw new Exception($errorMessage);
      }
      if (!$plugin->isActive()) {
        $errorMessage = __('Le plugin', __FILE__) . ' ' . $pluginName . ' ' . __('n\'est pas activé', __FILE__);
        log::add(__CLASS__, 'debug', __('Installation abandonnée', __FILE__) . ' : ' . $errorMessage);
        throw new Exception($errorMessage);
      }
      if ($pluginId == 'mqtt2') {
        if ($plugin->deamon_info()['state'] != 'ok') {
          $errorMessage = __('Le démon du plugin', __FILE__) . ' ' . $pluginName . ' ' . __('n\'est pas démarré', __FILE__);
          log::add(__CLASS__, 'debug', __('Installation abandonnée', __FILE__) . ' : ' . $errorMessage);
          throw new Exception($errorMessage);
        }
      }
    }

    /* Configuration par défaut lors de l'installation */
    config::save('mqtt::topic::roots', "hasp", 'openhasp'); /* hasp est le topic racine par défaut dans openHASP*/
    config::save('mqtt::discovery::duration::maximum', "12", 'openhasp'); /* 12 min par défaut*/
    config::save('unicode::replace::option', "hex", 'openhasp'); /* Utiliser le format \uXXXX par défaut*/
    config::save('unicode::replace::text::begin', "{{", 'openhasp'); /* Séparateur de début '{{' par défaut*/
    config::save('unicode::replace::text::end', "}}", 'openhasp'); /* Séparateur de fin '{{' par défaut*/
    $file = __DIR__ . '/../data/TextReplaceUnicode.txt';
    if (file_exists($file)) {
      config::save('text::unicode', file_get_contents($file), 'openhasp'); /* Chargement du fichier par défaut */
    } else {
      config::save('text::unicode', '\uE004;account', 'openhasp'); /* Si le fichier n'est pas trouvé (pourquoi pas) : juste un exemple par défaut */
    }
  }
  
  // Fonction exécutée automatiquement après la mise à jour du plugin
  function openhasp_update() {
    /* Configuration par défaut lors de la mise à jour */
    if ('' ==  config::byKey('mqtt::topic::roots', 'openhasp')) {
      config::save('mqtt::topic::roots', "hasp", 'openhasp'); /* hasp est le topic racine par défaut dans openHASP*/
    }

    if ('' ==  config::byKey('mqtt::discovery::duration::maximum', 'openhasp')) {
      config::save('mqtt::discovery::duration::maximum', "12", 'openhasp'); /* 12 min par défaut*/
    }

    if ('' ==  config::byKey('unicode::replace::option', 'openhasp')) {
      config::save('unicode::replace::option', "hex", 'openhasp'); /* Utiliser le format \uXXXX par défaut*/
    }

    if ('' ==  config::byKey('unicode::replace::text::begin', 'openhasp')) {
      config::save('unicode::replace::text::begin', "{{", 'openhasp'); /* Séparateur de début '{{' par défaut*/
    }

    if ('' ==  config::byKey('unicode::replace::text::end', 'openhasp')) {
      config::save('unicode::replace::text::end', "}}", 'openhasp'); /* Séparateur de fin '{{' par défaut*/
    }

    if ('' ==  config::byKey('text::unicode', 'openhasp')) {
      $file = __DIR__ . '/../data/TextReplaceUnicode.txt';
      if (file_exists($file)) {
        config::save('text::unicode', file_get_contents($file), 'openhasp'); /* Chargement du fichier par défaut */
      } else {
        config::save('text::unicode', '\uE004;account', 'openhasp'); /* Si le fichier n'est pas trouvé (pourquoi pas) : juste un exemple par défaut */
      }
    }
}

// Fonction exécutée automatiquement après la suppression du plugin
function openhasp_remove() {
  $cron = cron::byClassAndFunction('openhasp', 'mqttDiscoveryCron');
  if (is_object($cron)) {
    $cron->remove();
    $cron = null;
  }

  /* Se désabonner de tout ce qui est lié au plugin courant */
  mqtt2::removePluginTopicByPlugin('openhasp');
}
