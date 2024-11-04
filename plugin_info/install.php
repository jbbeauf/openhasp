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
}

// Fonction exécutée automatiquement après la mise à jour du plugin
function openhasp_update() {
    log::add(__CLASS__, 'debug', __('openhasp_update...', __FILE__));
}

// Fonction exécutée automatiquement après la suppression du plugin
function openhasp_remove() {
}
