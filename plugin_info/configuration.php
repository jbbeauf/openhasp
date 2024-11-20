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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Liste des topics racine}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Liste des topics racine MQTT pour la fonction Discovery. 1 topic par ligne, sans caractère /}}"></i></sup>
      </label>
      <div class="col-md-7">
        <textarea class="configKey form-control autogrow" data-l1key="mqtt::topic::roots"></textarea>
      </div>
    </div>
    <div class="form-group">
      <label class="col-sm-4 control-label"> {{Durée de l'inclusion automatique}}  <sub>({{minutes}})</sub>
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez la durée de l'inclusion automatique en minutes}}"></i></sup>
      </label>
      <div class="col-sm-5">
        <input class="configKey form-control" data-l1key="mqtt::discovery::duration::maximum" placeholder="{{Durée de l'inclusion automatique en minutes}}">
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Remplacement des caractères unicode affichés}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Remplacer les caractères unicodes par un texte. 1 caractères par ligne selon ce modèle \uXXXX:texte}}"></i></sup>
      </label>
      <div class="col-md-7">
        <textarea class="configKey form-control autogrow" data-l1key="text::unicode"></textarea>
      </div>
    </div>
  </fieldset>
</form>
