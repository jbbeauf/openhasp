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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
  */
    ajax::init();

    /* Bouton Valider l'adresse IP */
    if (init('action') == 'validateConfigByIp') {
      $eqLogic = openhasp::byId(init('id'));
      if (!is_object($eqLogic)) {
        throw new \Exception(__('Equipement introuvable', __FILE__) . ' : ' . init('id'));
      }
      ajax::success($eqLogic->validateConfigByIp(init('ipAddress'), init('httpUsername'), init('httpPassword'), true));
    }

    /* Bouton Valider la configuration MQTT */
    if (init('action') == 'validateConfigByMqtt') {
      $eqLogic = openhasp::byId(init('id'));
      if (!is_object($eqLogic)) {
        throw new \Exception(__('Equipement introuvable', __FILE__) . ' : ' . init('id'));
      }
      ajax::success($eqLogic->validateConfigByMqtt(init('rootTopic'), init('rootName')));
    }

    /* Boutons pour découverte automatique */
    if (init('action') == 'discovery') {      
      ajax::success(openhasp::mqttDiscovery(init('mode'), init('mqttRootTopic')));
    }

    /* Commande : nouvelle */
    if (init('action') == 'commandCreateNew') {
      $eqLogic = openhasp::byId(init('id'));
      if (!is_object($eqLogic)) {
        throw new \Exception(__('Equipement introuvable', __FILE__) . ' : ' . init('id'));
      }

      ajax::success($eqLogic->commandCreateNew(init('typeCommand'), init('newElement')));
    }

    /* Commande : Supprimer */
    if (init('action') == 'commandDeleteExiting') {
      $eqLogic = openhasp::byId(init('id'));
      if (!is_object($eqLogic)) {
        throw new \Exception(__('Equipement introuvable', __FILE__) . ' : ' . init('id'));
      }

      ajax::success($eqLogic->commandDeleteExiting(init('idCommand')));
    }

    /* Commande : Modifier */
    if (init('action') == 'commandModify') {
      $eqLogic = openhasp::byId(init('id'));
      if (!is_object($eqLogic)) {
        throw new \Exception(__('Equipement introuvable', __FILE__) . ' : ' . init('id'));
      }

      ajax::success($eqLogic->commandModify(init('idCommand'), init('typeCommand'), init('newElement')));
    }
    
    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
    /*     * *********Catch exeption*************** */
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
