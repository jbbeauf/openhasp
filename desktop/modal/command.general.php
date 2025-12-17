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

/* Récupération de l'identifiant de l'équipement courant et vérification qu'il est bien valide */
if (init('id') == '') {
	throw new Exception(__('L\'id ne peut etre vide', __FILE__));
}
$eqLogic = openhasp::byId(init('id'));
if (!is_object($eqLogic)) {
	throw new Exception(__('L\'équipement est introuvable : ', __FILE__) . init('id'));
}
if ($eqLogic->getEqType_name() != 'openhasp') {
	throw new Exception(__('Cet équipement n\'est pas de type openhasp : ', __FILE__) . $eqLogic->getEqType_name());
}

/* Charger le fichier contenant la définition de toutes les commandes générales disponibles */
$file = __DIR__ . '/../../data/General.json';
if (file_exists($file)) {
    $json = file_get_contents($file);
    if ($json === false) {
        throw new Exception(__('Fonction non disponible', __FILE__) . ' - ' . __('Erreur en chargeant le fichier de définition des commandes générales', __FILE__) . ' : ' . __('Ré-installer le plugin', __FILE__));
    }
    
    /* Pour afficher correctement le caractère Apostrophe */
    $json = str_replace('\'', '&#39;', $json);
    
    $json_data = json_decode($json, true); 
    if ($json_data === null) {
        throw new Exception(__('Fonction non disponible', __FILE__) . ' - ' . __('Erreur en décodant le fichier de définition des commandes générales', __FILE__) . ' : ' . __('Ré-installer le plugin', __FILE__));
    }
} else {
    throw new Exception(__('Fonction non disponible', __FILE__) . ' - ' . __('Fichier de définition des commandes générales non trouvé', __FILE__) . ' : ' . __('Ré-installer le plugin', __FILE__));
}

/* Liste de toutes les commandes exitantes : commandes de type 'général' et différentes des 2 de mise à jour (mise à jour et dernière vu) */
$listAvailableCommands = array();
foreach ($eqLogic->getCmd() as $cmd) {
    if ( 'general' == $cmd->getConfiguration('type')) {
        if ('command/statusupdate' != $cmd->getLogicalId() && 'lastSeen' != $cmd->getLogicalId()) {
            $listAvailableCommands[$cmd->getId()]['name'] = $cmd->getName() . ' (' . $cmd->getId() . ')';
            $listAvailableCommands[$cmd->getId()]['id'] = $cmd->getId();
        }
    }
}

/* Lien entre les commandes générales disponibles et les commandes existantes */
foreach($json_data as $groupKey => $groupElement) {
    // log::add('openhasp', 'debug', 'Groupe Element : ' . print_r($groupElement, true));
    foreach($groupElement['command'] as $commandKey => $commandElement) {
        // log::add('openhasp', 'debug', 'Command Element : ' . print_r($commandElement, true));
        foreach ($eqLogic->getCmd() as $cmd) {
            if ( 'general' == $cmd->getConfiguration('type')) {
                if ($commandElement['topic'] == $cmd->getLogicalId()) {
                    $json_data[$groupKey]['command'][$commandKey]['checked'] = 'checked';
                    $json_data[$groupKey]['command'][$commandKey]['cmdLinked'] =  $cmd->getId();;
                    break;
                } else {
                    $json_data[$groupKey]['command'][$commandKey]['checked'] = 'unchecked';
                    $json_data[$groupKey]['command'][$commandKey]['cmdLinked'] = '';
                }
            }
        }
    }
}

sendVarToJS('tableElements', $json_data);
?>

<div class="modal-content">


    <div class="modal-header">
    <!-- <div style="position: sticky;top: 0;padding: 1em;"> -->
    <!-- <div> -->
        <!-- Champ de info / recherche  -->
        <ul class="nav nav-tabs" style="display:inline-flex;">
            <i>{{Filtrer les éléments affichés}}</i>&nbsp;&nbsp;&nbsp;<input id="filterElements" type="text" placeholder="{{Recherche}}...">&nbsp;&nbsp;<i class="fa fa-times cursor" id="emptyFilterElements"></i>
        </ul>
        <!-- Bouton de commandes pour fermer la fenêtre modale  -->
        <div class="input-group pull-right" style="display:inline-flex;">
            <div id='messageErrorInvalidStatus' style="display: none;"><b style='color:red;'>{{Problème détecté !}}</b></div>
            <div id='messageChangesDetected' style="display: none;"><i>{{Changement en cours}}</i></div>&nbsp;&nbsp;
            <span class="input-group-btn">
                <a class="btn btn-sm btn-success" id="modalSave"><i class="fas fa-check-circle"></i> {{Enregistrer}}</a>
                <a class="btn btn-sm btn-danger roundedRight" id="modalQuit"><i class="fas fa-minus-circle"></i> {{Annuler}}</a>
            </span>
        </div>
    </div>
    <div class="modal-body">
    <!-- <div class="overflow-auto vertical-scrollable;"> -->
            <table id="table_manage_cmd_general" class="table table-bordered">
                <thead>
                    <tr class="sectionTitle">
                        <th>{{Elements disponibles}}</th>
                        <th style="min-width:50px;width:70px;">{{Utiliser&nbsp;?}}</th>
                        <th style="min-width:400px;width:600px;">{{Commandes associées}}</th>
                    </tr>
                </thead>
                <?php
                    foreach($json_data as $groupKey => $groupElement) {
                        echo '<tbody>';
                        echo '<tr style="height: 10px;">';
                        echo '<td colspan="3">';
                        echo '<div class="cursor card logoPrimary" data-toggle="collapse" data-target="#group_' . $groupKey . '" aria-expanded="true">';
                        echo '<h3 class="display-3"><i class="fas fa-chevron-right"></i><i class="fas fa-chevron-down"></i>&nbsp;&nbsp;' . $groupElement['name'] . '</h3>';
                        echo '</div>';
                        echo '</td>';
                        echo '</tr>';
                        echo '</tbody>';
                        echo '<tbody id="group_' . $groupKey . '" class="collapse group" aria-expanded="true">';
                        foreach($groupElement['command'] as $commandKey => $commandElement) {
                            echo '<tr style="height: 10px;">';

                            echo '<td>';
                            echo '<div style="display: none;">' . $groupElement['name'] . ' </div>'; // Pour la recherche
                            echo '<div style="font-size: larger;font-weight: bold;display: inline;">' . $commandElement['name'] . '</div> (' . $commandElement['type'] .')<em>&nbsp;&nbsp;&nbsp;&nbsp;' . $commandElement['description'] . '</em>';
                            echo '</td>';
                            
                            echo '<td>';
                            echo '<input type="checkbox" unchecked id="checkbox_' . $groupKey . '_' . $commandKey . '"  key="' . $groupKey . '_' . $commandKey . '" class="chkSelectCommand">';
                            echo '</td>';
                            echo '<td>';
                            echo '<div class="divStatus" id="status_' . $groupKey . '_' . $commandKey . '">';
                            echo '<select class="cmdAttr form-control input-sm selectCommand invalid" style="margin-top:5px;" id="cmdLinked_' . $groupKey . '_' . $commandKey . '" key="' . $groupKey . '_' . $commandKey . '" title="{{Commande liée}}">';
                            if ('' == $commandElement['cmdLinked']) {
                              echo '<option value="new" selected>{{Nouvelle commande}}</option>'; 
                            }
                            foreach ($listAvailableCommands as $key => $availableCommand) {
                                echo '<option value="' . $availableCommand['id'] . '">' . $availableCommand['name'] . '</option>';
                            }
                            echo '</select>';
                            echo '</div>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                    }
                ?>
            </table>
        </div>
    </div>
</div>
<script>

    /* Clique sur un des checkbox "Utiliser ?" */
    $('.chkSelectCommand').change(function() {
        var key = $(this).attr('key');
        if(this.checked) {
            $("#cmdLinked_" + key).prop('disabled', false);
        } else {
            $("#cmdLinked_" + key).prop('disabled', true);
        }
    });

    /* Changement d'une valeur d'une des liste de choix de commandes disponibles : indique les valeurs sélectionnées plus d'une fois */
    $('.selectCommand, .chkSelectCommand').change(function (e) {
        var arrayAllValues = [];
        $('#table_manage_cmd_general').find("select").each(function() {
            if ($('#checkbox_' +  $(this).attr('key')).is(':checked')) {
                arrayAllValues.push(this.value);
            }
            $('#status_' +  $(this).attr('key')).removeClass('statusInvalid');
        });
        $("#messageErrorInvalidStatus").hide();
        $("#modalSave").addClass('btn-success');
        $("#modalSave").removeClass('btn-secondary');
        var duplicatedValues = getDuplicates(arrayAllValues);
        duplicatedValues = duplicatedValues.filter((value) => value !== 'new');
        duplicatedValues.forEach(item => {
            $('#table_manage_cmd_general').find("select").each(function() {
                if (item == this.value) {
                    $('#status_' +  $(this).attr('key')).addClass('statusInvalid');
                    $("#messageErrorInvalidStatus").show();
                    $("#modalSave").addClass('btn-secondary');
                    $("#modalSave").removeClass('btn-success');
                }
            });
        });

    });

    /* QUand la page est chargée */
    $( document ).ready(function() {
        /* Initialisation des éléments après le chargement de la page */
        $('.collapse', '#table_manage_cmd_general').collapse();
        for (var groupKey in tableElements){
            var groupElement = tableElements[groupKey];
            for (var commandKey in groupElement['command']){
                var commandElement = groupElement['command'][commandKey];
                if ('' == commandElement['cmdLinked']) {
                    /* Commande disponible non liée à une commande existante */
                    $('#checkbox_' + groupKey + '_' + commandKey).prop('checked', false);
                    $("#cmdLinked_"  + groupKey + '_' + commandKey).prop('disabled', true);
                } else {
                    /* Commande disponible liée à une commande existante */
                    $('#checkbox_'  + groupKey + '_' + commandKey).prop('checked', true);
                    $("#cmdLinked_"  + groupKey + '_' + commandKey).prop('disabled', false);
                    $("#cmdLinked_" + groupKey + '_' + commandKey).val(commandElement['cmdLinked']);
                }
            }
        }

        /* Fonction pour filtrer les éléments affichés */ 
        $("#filterElements").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#table_manage_cmd_general tr:not(.sectionTitle)").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        modalClosedWithChange = false;

    });

    /* Bouton pour effacer le champ de filtre des éléments affichés*/
    $("#emptyFilterElements").on("click", function() {
        $("#filterElements").val('');
        $("#table_manage_cmd_general tr").show();
    });

    /* Bouton Annuler */
    $("#modalQuit").on("click", function() {
        modalClosedWithChange = false;
        $("#md_modal").dialog('close');
    });

    /* Bouton Sauvegarder */
    $("#modalSave").on("click", function() {
        if ($("#messageErrorInvalidStatus").is(":visible")) return;
        for (var groupKey in tableElements){
            var groupElement = tableElements[groupKey];
            for (var commandKey in groupElement['command']){
                var commandElement = groupElement['command'][commandKey];
                if ($('#checkbox_' + groupKey + '_' + commandKey).is(':checked')) {
                    /* Checkbox coché */
                    if ('' != commandElement['cmdLinked']) {
                        /* Commande liée */
                        if (commandElement['cmdLinked'] != $("#cmdLinked_" + groupKey + '_' + commandKey).val()) {
                            /* Commande liée différente de la commande sélectionnée --> modifier */
                            $.ajax({
                                type: "POST",
                                url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
                                data: {
                                    action: "commandModify",
                                    id: $('.eqLogicAttr[data-l1key=id]').value(),
                                    idCommand : $("#cmdLinked_" + groupKey + '_' + commandKey).val(),
                                    typeCommand : 'general',
                                    newElement : commandElement
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
                                }
                            })
                            modalClosedWithChange = true;
                        } /* Pas de else */
                          /* Si commande liée identique à commande sélectionnée alors ne rien faire */
                    } else {
                        /* Pas de commande liée */
                        if ('new' == $("#cmdLinked_" + groupKey + '_' + commandKey).val()) {
                            /* Nouvelle commande sélectionnée --> créer  */
                            $.ajax({
                                type: "POST",
                                url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
                                data: {
                                    action: "commandCreateNew",
                                    id: $('.eqLogicAttr[data-l1key=id]').value(),
                                    typeCommand : 'general',
                                    newElement : commandElement
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
                                }
                            })
                            modalClosedWithChange = true;
                        } else {
                            /* commande sélectionnée est une commande existante --> modifier  */
                            $.ajax({
                                type: "POST",
                                url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
                                data: {
                                    action: "commandModify",
                                    id: $('.eqLogicAttr[data-l1key=id]').value(),
                                    idCommand : $("#cmdLinked_" + groupKey + '_' + commandKey).val(),
                                    typeCommand : 'general',
                                    newElement : commandElement
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
                                }
                            })
                            modalClosedWithChange = true;
                        }
                    }
                } else {
                    /* Checkbox décoché */
                    if ('' != commandElement['cmdLinked']) {
                        /* Commande liée --> on supprime donc la commande */
                        $.ajax({
                            type: "POST",
                            url: "plugins/openhasp/core/ajax/openhasp.ajax.php",
                            data: {
                                action: "commandDeleteExiting",
                                id: $('.eqLogicAttr[data-l1key=id]').value(),
                                idCommand : commandElement['cmdLinked']
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
                            }
                        })
                        modalClosedWithChange = true;
                    } /* Pas de else */
                      /* Si pas de commande liée alors ne rien faire */
                }
            }
        }
        $("#md_modal").dialog('close');
    });

    function getDuplicates(arr) {
        let duplicates = [];
        let seen = new Set();
        let seenOnce = new Set();

        arr.forEach(item => {
            if (seen.has(item)) {
                if (!seenOnce.has(item)) {
                    duplicates.push(item);
                    seenOnce.add(item);
                }
            } else {
            seen.add(item);
            }
        });

        return duplicates;
    }

</script>

<?php include_file('desktop', 'openhasp.modal', 'css', 'openhasp'); ?>