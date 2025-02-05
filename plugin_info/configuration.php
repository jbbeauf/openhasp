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
      <label class="col-md-4 control-label">{{Liste des sujets racine MQTT}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Liste des sujets racine MQTT pour la fonction Discovery. 1 sujet par ligne, sans caractère /}}"></i></sup>
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
    <br/>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Gestion des caractères Unicode}}</label>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Affichage des caractères unicode}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Affichage des caractères unicode (voir documentation)}}"></i></sup>
      </label>
      <div class="col-md-7">
        <select class="configKey form-control" data-l1key="unicode::replace::option">
          <option value="no">{{Ne pas modifier}}</option>
          <option value="hex">{{Utiliser le format \uXXXX (par défaut)}}</option>
          <option value="text">{{Remplacer par le texte correspondant}}</option>
        </select>
      </div>
    </div>
    <div class="form-group">
			<label class="col-lg-4 control-label">{{Configuration pour le remplacement par le texte correspondant}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Séparateurs de début et de fin pour le remplacement des caractères unicode par le texte correspondant}}"></i></sup>
      </label>
			<div class="col-lg-3">
				<div class="input-group" style="width:100%">
					<span class="input-group-addon roundedLeft">Séparateur début</span>
					<input type="text" class="configKey form-control" data-l1key="unicode::replace::text::begin" placeholder="{{" style="width: 70px;" />
					<span class="input-group-addon">Séparateur fin</span>
					<input type="text" class="configKey form-control roundedRight" data-l1key="unicode::replace::text::end" placeholder="}}" style="width: 70px;" />
				</div>
			</div>
		</div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Correspondance des caractères unicode affichés}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Correspondance des caractères unicodes par un texte. 1 caractère par ligne selon ce modèle \uXXXX:texte}}"></i></sup>
      </label>
      <div class="col-md-7">
        <textarea class="configKey form-control autogrow" data-l1key="text::unicode"></textarea>
      </div>
    </div>
  </fieldset>
</form>
