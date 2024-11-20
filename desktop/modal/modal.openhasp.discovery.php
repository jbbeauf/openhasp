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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

openhasp::handleMqttPublish('hasp/discovery', '');
?>

<legend><i class="fas fa-exclamation"></i> {{Information importante}}</legend>
<div class="row row-overflow">
    <div class="form-group">
        <label class="col-lg control-label">{{La fontion discovery nécessite openHASP > 0.7.x}}</label>
    </div>
    <div class="form-group">
        <label class="col-lgs control-label">{{Chaque écran répond en moins de 10s la première fois qu'il reçoit la requête discovery et attend environ 10min par défaut avant d'y répondre à nouveau}}</label>
        <!-- voir openHASP/src/hasp/hasp_dispatch.cpp ==> dispatchSecondsToNextDiscovery = dispatch_setings.teleperiod * 2 + HASP_RANDOM(10); -->
        <!-- Plate config/debug/Tele Period = 300s par défaut -->
    </div>
</div>
<br/>
<div style="display: none;" id="md_cmdDiscoverAlert"></div>
<!-- <a class="btn btn-success pull-right" id="bt_saveDiscover"><i class="fa fa-check"></i> {{Sauvegarder}}</a> -->
<table class="table table-bordered table-condensed tablesorter" id="table_mqttDiscover">
    <thead>
        <tr>
            <th data-sorter="false" data-filter="false"></th>
            <th>{{Modèle}}</th>
            <th>{{Topic}}</th>
            <th>{{Nom}}</th>
            <th>{{IP}}</th>
            <th>{{Action}}</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<script>
    $('body').off('openhasp::discovery::add').on('openhasp::discovery::add', function (_event, plateFounded) {
        if (isset(plateFounded)) {
            if (isset(plateFounded.mf)) {
                if ('openHASP' == plateFounded.mf) {
                    addPlateToTable(plateFounded);
                }
            }
        }
    });

    function addPlateToTable(_plate) {
        var tr = '<tr class="plate" data-cmd_id="' + init(_plate.hwid) + '">'
        tr += '<td class="hidden-xs">'
        tr += '<span class="cmdAttr" data-l1key="id"></span>'
        tr += '</td>'
        tr += '<td>'
        tr += init(_plate.mdl)
        tr += '</td>'
        tr += '<td>'
        tr += init(_plate.node_t.split('/')[0])
        tr += '</td>'
        tr += '<td>'
        tr += init(_plate.node)
        tr += '</td>'
        tr += '<td>'
        tr += init(_plate.uri.split('/')[2])
        tr += '</td>'
        tr += '<td>'
        if (isset(_plate.equipment)) {
            tr += '{{Déjà configuré dans }} ' + _plate.equipment
        } else {
            tr += '<a class="btn btn-default btn-xs cmdAction" id="bt_addPlateAsNewEquipment" style="margin-top:5px;"><i class="fas fa-plus"></i>&nbsp;{{Créer en tant que nouvel équipement}}</a>'
        }
        tr += '</td>'

        tr += '</tr>'
        $('#table_mqttDiscover tbody').append(tr)
    }

    function emptyTable() {
        $('#table_mqttDiscover tbody').empty()
    }

    /* */
    $('#bt_addPlateAsNewEquipment').off('click').on('click', function() {
        
        // $.ajax({
        //     type: "POST",
        //     url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
        //     data: {
        //     action: "importCommands",
        //     id: $('.eqLogicAttr[data-l1key=id]').value(),
        //     pageName: $('.eqLogicAttr[data-l1key=configuration][data-l2key=conf::startLayout]').value()
        //     },
        //     dataType: 'json',
        //     error: function(error) {
        //     $.fn.showAlert({ message: error.message, level: 'danger' })
        //     },
        //     success: function(data) {
        //     if (data.state != 'ok') {
        //         $.fn.showAlert({ message: data.result, level: 'danger' })
        //         return
        //     }
        //     $.fn.showAlert({ message: '{{Opération réalisée avec succès}}', level: 'success' });
        //     }
        // })
    })

</script>

