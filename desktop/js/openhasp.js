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

/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd_general").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
})
$("#table_cmd_specific").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
})

function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} }
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {}
  }
  if (!isset(_cmd.configuration.type)) {
    _cmd.configuration.type = 'general'
  }
  if ('specific' == _cmd.configuration.type && !isset(_cmd.configuration.page)) {
    _cmd.configuration.page = 'all'
  }

  var classPage = '';
  if ('specific' == _cmd.configuration.type && '' != _cmd.configuration.page) {
    classPage = 'page_' + _cmd.configuration.page
    if (0 ==  $('#table_cmd_' + init(_cmd.configuration.type) + ' tbody').children().length) {
      $('#filter_page').find("a.btn-success").removeClass('btn-success')
      $('#btn_commandPageFilterAllPages').addClass('btn-success')
    }
  }
  var tr = '<tr class="cmd ' + classPage + '" data-cmd_id="' + init(_cmd.id) + '">'

  if ('specific' == _cmd.configuration.type) {
    /* Colonne ID */
    tr += '<td class="hidden-xs">'
    tr += '<span class="cmdAttr" data-l1key="id"></span>'
    tr += '</td>'
    /* Colonne Page */
    tr += '<td>'
    tr += '<span class="cmdAttr form-control input-sm hidden" data-l1key="configuration" data-l2key="type">' + _cmd.configuration.type + '</span>'
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="page"/>'
    tr += '</td>'
  } else {
    /* Colonne ID */
    tr += '<td class="hidden-xs">'
    tr += '<span class="cmdAttr" data-l1key="id"></span>'
    tr += '<span class="cmdAttr form-control input-sm hidden" data-l1key="configuration" data-l2key="type">' + _cmd.configuration.type + '</span>'
    tr += '<span class="cmdAttr form-control input-sm hidden" data-l1key="configuration" data-l2key="page" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">' + _cmd.configuration.page + '</span>'
    tr += '</td>'
  }
  /* Colonne Nom */
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn">'
  tr += '<a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a>'
  tr += '</span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande information liée}}">'
  tr += '<option value="">{{Aucune}}</option>'
  tr += '</select>'
  tr += '</td>'
  /* Colonne Type */
  tr += '<td>'
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'
  /* Colonne Topic MQTT */
  tr += '<td >'
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="logicalId" placeholder="{{Topic}}" title="{{Topic}}"/> '
  tr += '<input class="cmdAttr form-control input-sm cmdType action" style="margin-top:3px" data-l1key="configuration" data-l2key="message" placeholder="{{Message}}" title="{{Message}}"/> '
  tr += '</td>'
  /* Colonne Etat */
  tr += '<td>'
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'
  tr += '</td>'
  /* Colonne Options MQTT */
  tr += '<td>'
  if ('action' == init(_cmd.type)) {
    tr += '<label class="checkbox-inline cmdAction"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="retain">{{Retain}}&nbsp;<sup><i class="fas fa-question-circle tooltips" title="{{Dire au serveur mqtt de retenir ce message}}"></i></sup></label><br/>'
    tr += '<label class="checkbox-inline cmdAction"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="refresh">{{Refresh}}&nbsp;<sup><i class="fas fa-question-circle tooltips" title="{{Envoyer une commande vide pour demander à l\'écran de renvoyer la valeur de l\'élément}}"></i></sup></label>'
  }
  tr += '</td>'
  /* Colonne Options Jeedom */
  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked>{{Historiser}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary">{{Inverser}}</label> '
  tr += '<div style="margin-top:7px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="listValue" placeholder="{{Liste de valeur|texte séparé par ;}}" title="{{Liste}}">';
  tr += '</div>'
  tr += '</td>'
  /* Colonne Actions */
  tr += '<td>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
  }
  tr += ' <i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>'
  tr += '</td>'
  tr += '</tr>'
  $('#table_cmd_' + init(_cmd.configuration.type) + '  tbody').append(tr)
  var tr = $('#table_cmd_' + init(_cmd.configuration.type) + ' tbody tr').last()
  jeedom.eqLogic.buildSelectCmd({
    id: $('.eqLogicAttr[data-l1key=id]').value(),
    filter: { type: 'info' },
    error: function(error) {
      $.fn.showAlert({ message: error.message, level: 'danger' })
    },
    success: function(result) {
      tr.find('.cmdAttr[data-l1key=value]').append(result)
      tr.setValues(_cmd, '.cmdAttr')
      jeedom.cmd.changeType(tr, init(_cmd.subType))
    }
  })
  if ('' != classPage) {
    var addNewFilter = 1
    $('#filter_page').find("a").each(function() {
      if (_cmd.configuration.page == $(this).attr('page')) {
        addNewFilter = 0
        return false
      }
    });
    if (1 == addNewFilter) {
      $('#filter_page').append('<a class="btn btn-default btn-sm commandPageFilter" style="margin-top:5px;" page="' + _cmd.configuration.page + '">{{Page}}  ' + _cmd.configuration.page + '</a> ')
    }
  }
}

/**
 * ????
 */
$('#table_cmd_general').on('change', '.cmdAttr[data-l1key=type]', function() {
  let tr = $(this).closest('tr')
  tr.find('.cmdType').hide()
  if ($(this).value() != '') {
    tr.find('.cmdType.' + $(this).value()).show()
  }
})
$('#table_cmd_pages').on('change', '.cmdAttr[data-l1key=type]', function() {
  let tr = $(this).closest('tr')
  tr.find('.cmdType').hide()
  if ($(this).value() != '') {
    tr.find('.cmdType.' + $(this).value()).show()
  }
})

/**
 * Equimement ouvert
 * - Onglet "Commandes générales"
 *   - Bouton "Importer les commandes"
 */
$('#bt_importCommands').off('click').on('click', function() {
  $.ajax({
    type: "POST",
    url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
    data: {
      action: "importCommands",
      id: $('.eqLogicAttr[data-l1key=id]').value()
      // pageName: $('.eqLogicAttr[data-l1key=configuration][data-l2key=conf::startLayout]').value()
    },
    dataType: 'json',
    error: function(error) {
      $.fn.showAlert({ message: error.message, level: 'danger' })
    },
    success: function(data) {
      if (data.state != 'ok') {
        $.fn.showAlert({ message: data.result, level: 'danger' })
        return
      }
      $.fn.showAlert({ message: '{{Opération réalisée avec succès}}', level: 'success' });
    }
  })
})

/**
 * Equimement ouvert
 * - Onglet "Configuration Equipement"
 *   - Bouton "Valider IP"
 */
$('#bt_validateConfigByIp').off('click').on('click', function() {
  $.ajax({
    type: "POST",
    url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
    data: {
      action: "validateConfigByIp",
      id: $('.eqLogicAttr[data-l1key=id]').value(),
      ipAddress: $('.eqLogicAttr[data-l1key=configuration][data-l2key=updateConf_byIp_ip]').value(),
      httpUsername: $('.eqLogicAttr[data-l1key=configuration][data-l2key=updateConf_byIp_http_username]').value(),
      httpPassword: $('.eqLogicAttr[data-l1key=configuration][data-l2key=updateConf_byIp_http_password]').value()
    },
    dataType: 'json',
    error: function(error) {
      $.fn.showAlert({ message: error.message, level: 'danger' })
    },
    success: function(data) {
      if (data.state != 'ok') {
        $.fn.showAlert({ message: data.result, level: 'danger' })
        return
      }
      $.fn.showAlert({ message: '{{Configuration réalisée avec succès}}', level: 'success' })
    }
  })
})

/**
 * Equimement ouvert
 * - Onglet "Configuration Equipement"
 *   - Bouton "Valider MQTT"
 */
$('#bt_validateConfigByMqtt').off('click').on('click', function() {
  $.ajax({
    type: "POST",
    url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
    data: {
      action: "validateConfigByMqtt",
      id: $('.eqLogicAttr[data-l1key=id]').value(),
      rootTopic: $('.eqLogicAttr[data-l1key=configuration][data-l2key=updateConf_byMqtt_topic]').value(),
      rootName: $('.eqLogicAttr[data-l1key=configuration][data-l2key=updateConf_byMqtt_name]').value()
    },
    dataType: 'json',
    error: function(error) {
      $.fn.showAlert({ message: error.message, level: 'danger' })
    },
    success: function(data) {
      if (data.state != 'ok') {
        $.fn.showAlert({ message: data.result, level: 'danger' })
        return
      }
      $.fn.showAlert({ message: '{{Configuration réalisée avec succès}}', level: 'success' })
    }
  })
})

/**
 * Evènement reçu du serveur
 * - Recharger la page de l'équipement ouvert
 */
$('body').off('openhasp::equipment::reload').on('openhasp::equipment::reload', function (_event, _options) {
  if (_options != '') {
      jeeFrontEnd.modifyWithoutSave = false;
      modifyWithoutSave = false; // Mais pourquoi ?!
      window.location.href = 'index.php?v=d&m=openhasp&p=openhasp&id=' + _options;
  }
});

/**
 * Evènement reçu du serveur
 * - Recharger la page principale du plugin si elle est affichée
 */
$('body').off('openhasp::MainPage::reloadIfVisible').on('openhasp::MainPage::reloadIfVisible', function (_event, _options) {
  try {
    if (isVisible(bt_discoveryStop)) {
      window.location.href = 'index.php?v=d&m=openhasp&p=openhasp';
    }
  } catch(e)  {}
});

/**
 * Evènement reçu du serveur
 * - Changer les boutons pour activer/désactiver le mode inclusion automatique si affichés
 */
$('body').off('openhasp::MainPage::setDiscoveryButtonDisable').on('openhasp::MainPage::setDiscoveryButtonDisable', function (_event, _options) {
  if (_options != '') {
    try {
      if (isVisible(document.getElementById(_options))) {
        $('#bt_discoveryStart').removeClass('hidden');
        $('#bt_discoveryStop').addClass('hidden');
      }
    } catch(e)  {
      // console.log(e); --> 99% l'élément _options ne doit pas exister : sûrement page non affichée
    }
    $('#div_alert').showAlert({message: 'openHASP : {{Arrêt du mode inclusion automatique}}', level: 'success'});
  }  
});

/**
 * Page principale du plugin
 * - Bouton "Activer l'inclusion automatique"
 */
$('#bt_discoveryStart').off('click').on('click', function () {
  jeedom.openhasp.utils.promptMqttTopic("{{Topic racine MQTT}} ?", "{{Sélectionner le topic racine pour lancer l'inclusion automatique}}</br>{{Aller sur la page de configuration du plugin pour modifier la liste des topic racine disponibles.}}</br>", function (mqttRootTopic) {
    jeedom.openhasp.mqtt.discovery({
      mqttRootTopic : mqttRootTopic,
      mode : 1,
      error: function (error) {
        $('#div_alert').showAlert({message: error.message, level: 'danger'});
      },
      success: function () {
        $('#div_alert').showAlert({message: '{{Lancement du mode inclusion automatique}}', level: 'success'});
        $('#bt_discoveryStart').addClass('hidden');
        $('#bt_discoveryStop').removeClass('hidden');
      }
    });
  });
});

/**
 * Page principale du plugin
 * - Bouton "Désactiver l'inclusion automatique"
 */
$('#bt_discoveryStop').off('click').on('click', function () {
  jeedom.openhasp.mqtt.discovery({
    mode : 0,
    error: function (error) {
      $('#div_alert').showAlert({message: error.message, level: 'danger'});
    },
    success: function () {
      $('#div_alert').showAlert({message: '{{Arrêt du mode inclusion automatique}}', level: 'success'});
      $('#bt_discoveryStart').removeClass('hidden');
      $('#bt_discoveryStop').addClass('hidden');
    }
  });
});

/**
 * Equimement ouvert
 * - Onglet "Configuration Equipement"
 *   - Bouton "Ouvrir dans nouvel onglet"
 */
$('#bt_openInNewWindow').off('click').on('click', function () {
  var ip = $('.eqLogicAttr[data-l1key=configuration][data-l2key="conf::wifi::ip"]').value();
  if ('' != ip) {
    window.open('http://' + ip, '_blank', '' );
  }
});

/**
 * Equimement ouvert
 * - Onglet "Commandes générales"
 *   - Bouton "Ajouter une commande"
 */
$("#bt_addCommandGeneral").on('click', function(event) {
  addCmdToTable({ configuration: { type : 'general'} })
  modifyWithoutSave = true
})

/**
 * Equimement ouvert
 * - Onglet "Commandes spécifiques"
 *   - Bouton "Ajouter une commande"
 */
$("#bt_addCommandSpecific").on('click', function(event) {
  addCmdToTable({ configuration: { type : 'specific', page : 'all'} })
  modifyWithoutSave = true
})

  
/**
 * Vérifie si un élément est visible ou non
 * Crédit : https://stackoverflow.com/questions/19669786/check-if-element-is-visible-in-dom
 * @param {object} elem - Elément à vérifier
 * @returns {boolean} True : l'élément est visible - False : l'élément n'est pas visible
 */
// 
function isVisible(elem) {
  if (!(elem instanceof Element)) throw Error('DomUtil: elem is not an element.');
  const style = getComputedStyle(elem);
  if (style.display === 'none') return false;
  if (style.visibility !== 'visible') return false;
  if (style.opacity < 0.1) return false;
  if (elem.offsetWidth + elem.offsetHeight + elem.getBoundingClientRect().height +
      elem.getBoundingClientRect().width === 0) {
      return false;
  }
  const elemCenter   = {
      x: elem.getBoundingClientRect().left + elem.offsetWidth / 2,
      y: elem.getBoundingClientRect().top + elem.offsetHeight / 2
  };
  if (elemCenter.x < 0) return false;
  if (elemCenter.x > (document.documentElement.clientWidth || window.innerWidth)) return false;
  if (elemCenter.y < 0) return false;
  if (elemCenter.y > (document.documentElement.clientHeight || window.innerHeight)) return false;
  let pointContainer = document.elementFromPoint(elemCenter.x, elemCenter.y);
  do {
      if (pointContainer === elem) return true;
  } while (pointContainer = pointContainer.parentNode);
  return false;
}

/**
 * Equimement ouvert
 * - Onglet "Commandes spécifiques"
 *   - Bouton de filtre sur les pages
 */
$('#filter_page').on('click', 'a.commandPageFilter', function() {
  $('#filter_page').find("a.btn-success").removeClass('btn-success')
  $(this).addClass('btn-success')
  if ('all' != $(this).attr('page')) {
    $('#table_cmd_specific').find('tbody>tr:not(.page_' + $(this).attr('page') + ')').hide()
    $('#table_cmd_specific').find('tbody>tr.page_' + $(this).attr('page')).show()
    $('#table_cmd_specific').find('tbody>tr.page_all').show()
  } else {
    $('#table_cmd_specific').find('tbody>tr').show()
  }
})
