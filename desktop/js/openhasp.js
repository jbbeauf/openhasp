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
$("#table_cmd").sortable({
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
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
  tr += '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '</td>'
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
  tr += '<td>'
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'
  tr += '<td >'
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="logicalId" placeholder="{{Topic}}" title="{{Topic}}"/> '
  tr += '<input class="cmdAttr form-control input-sm cmdType action" style="margin-top:3px" data-l1key="configuration" data-l2key="message" placeholder="{{Message}}" title="{{Message}}"/> '
  tr += '</td>'
  tr += '<td>'
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'
  tr += '</td>'
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
  tr += '<td>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
  }
  tr += ' <i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>'
  tr += '</tr>'
  $('#table_cmd tbody').append(tr)
  var tr = $('#table_cmd tbody tr').last()
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
}

$('#table_cmd').on('change', '.cmdAttr[data-l1key=type]', function() {
  let tr = $(this).closest('tr')
  tr.find('.cmdType').hide()
  if ($(this).value() != '') {
    tr.find('.cmdType.' + $(this).value()).show()
  }
})

// /* */
// $('#bt_statusUpdate').off('click').on('click', function() {
//   $.ajax({
//     type: "POST",
//     url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
//     data: {
//       action: "statusUpdate",
//       id: $('.eqLogicAttr[data-l1key=id]').value(),
//       mqttTopic: $('.eqLogicAttr[data-l1key=configuration][data-l2key=mqttTopic]').value(),
//       mqttHostname: $('.eqLogicAttr[data-l1key=configuration][data-l2key=mqttHostname]').value()
//     },
//     dataType: 'json',
//     error: function(error) {
//       $.fn.showAlert({ message: error.message, level: 'danger' })
//     },
//     success: function(data) {
//       if (data.state != 'ok') {
//         $.fn.showAlert({ message: data.result, level: 'danger' })
//         return
//       }
//       $.fn.showAlert({ message: '{{Opération réalisée avec succès}}', level: 'success' })
//     }
//   })
// })

/* */
$('#bt_importCommands').off('click').on('click', function() {
  $.ajax({
    type: "POST",
    url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
    data: {
      action: "importCommands",
      id: $('.eqLogicAttr[data-l1key=id]').value(),
      pageName: $('.eqLogicAttr[data-l1key=configuration][data-l2key=hasp_pages]').value()
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

/* */
$('#bt_validateConfigByIp').off('click').on('click', function() {
  $.ajax({
    type: "POST",
    url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
    data: {
      action: "validateConfigByIp",
      id: $('.eqLogicAttr[data-l1key=id]').value(),
      ipAddress: $('.eqLogicAttr[data-l1key=configuration][data-l2key=confIpAddress]').value(),
      httpUsername: $('.eqLogicAttr[data-l1key=configuration][data-l2key=confIpHttpUsername]').value(),
      httpPassword: $('.eqLogicAttr[data-l1key=configuration][data-l2key=confIphttpPassword]').value()
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
      $.fn.showAlert({ message: '{{Opération réalisée avec succès}}', level: 'success' })
    }
  })
})

/* */
$('#bt_validateConfigByMqtt').off('click').on('click', function() {
  $.ajax({
    type: "POST",
    url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
    data: {
      action: "validateConfigByMqtt",
      id: $('.eqLogicAttr[data-l1key=id]').value(),
      rootTopic: $('.eqLogicAttr[data-l1key=configuration][data-l2key=confMqttRootTopic]').value(),
      rootName: $('.eqLogicAttr[data-l1key=configuration][data-l2key=confMqttRootName]').value()
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
      $.fn.showAlert({ message: '{{Opération réalisée avec succès}}', level: 'success' })
    }
  })
})

$('body').off('openhasp::loadObjects').on('openhasp::loadObjects', function (_event, _options) {
  if (_options != '') {
      jeeFrontEnd.modifyWithoutSave = false;
      modifyWithoutSave = false; // Mais pourquoi ?!
      window.location.href = 'index.php?v=d&m=openhasp&p=openhasp&id=' + _options;
  }
  });