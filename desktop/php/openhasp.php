<?php

if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('openhasp');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

$rootTopics = preg_split ('/\r?\n/', config::byKey('mqtt::topic::roots', 'openhasp'));
sendVarToJS('openhasp_mqttRootTopics', $rootTopics);

?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
					<br>
				<span>{{Configuration}}</span>
			</div>
			<div class="cursor card logoPrimary
				<?php 
					if (1 == config::byKey('mqtt::discovery::running', 'openhasp')) {
						echo 'hidden';
					}
				?>
				" id="bt_discoveryStart">
				<i class="fas fa-eye"></i>
				<br />
				<span>{{Activer l'inclusion automatique}}</span>
			</div>
			<div class="cursor card logoPrimary
				<?php 
					if (0 == config::byKey('mqtt::discovery::running', 'openhasp')) {
						echo 'hidden';
					}
				?>
				" id="bt_discoveryStop">
				<i class="fas fa-eye-slash"></i>
				<br />
				<span>{{Désactiver l'inclusion automatique}}</span>
			</div>
		</div>
		<legend><i class="fas fa-table"></i> {{Mes équipements}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement openHASP trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $eqLogic->getImage() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Configuration Equipement}}</a></li>
			<li role="presentation"><a href="#commandtabgeneral" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes générales}}</a></li>
			<li role="presentation"><a href="#commandtabspecific" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes spécifiques}}</a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux et spécifiques de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Rafraîchir automatiquement}}</label>
								<div class="col-sm-6">
									<select id="sel_autoRefresh" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="conf::autoRefresh" >
										<option value="0">{{Non}}</option>
										<option value="1">{{Toutes les minutes}}</option>
										<option value="2">{{Toutes les 5 minutes}}</option>
										<option value="3">{{Toutes les 10 minutes}}</option>
									</select>
								</div>
							</div>

							<legend><i class="fas fa-cogs"></i> {{Configuration de l'écran}}</legend>
							<div class="form-group">
								<label class="col-sm control-label"> {{Vous pouvez configurer l'écran avec avec son adresse IP ou avec ses paramèters MQTT}}<label>
							</div>
							<legend>1. {{Configuration par Adresse IP}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label"> {{Adresse IP de l'écran}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Renseignez l'adresse IP de l'écran}}"></i></sup>
								</label>
								<div class="col-sm-5">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="updateConf_byIp_ip" placeholder="{{Adresse IP de l'écran}}">
								</div>
								<div class="col-sm-1">
									<a class="btn btn-sm btn-success roundedLeft" id="bt_validateConfigByIp"><i class="fas"></i><span class="hidden-xs"> {{Valider IP}}</span></a>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label"> {{Configuration HTTP}}<label>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label"> {{Nom d'utilisateur}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le nom d'utilisateur}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="updateConf_byIp_http_username" placeholder="{{Nom d'utilisateur}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label"> {{Mot de passe}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le mot de passe}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control inputPassword" data-l1key="configuration" data-l2key="updateConf_byIp_http_password">
								</div>
							</div>
							<legend>2. {{Configuration par MQTT}}</legend>
							<span class="eqLogicAttr tooltips label label-default hidden" data-l1key="configuration" data-l2key="validateConfigByMqttRequested"></span>
							<div class="form-group">
								<label class="col-sm-4 control-label"> {{Sujet racine}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le sujet racine MQTT}}"></i></sup>
								</label>
								<div class="col-sm-5">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="updateConf_byMqtt_topic" placeholder="{{Sujet racine}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label"> {{Nom d'hôte}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le nom de l'hôte}}"></i></sup>
								</label>
								<div class="col-sm-5">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="updateConf_byMqtt_name" placeholder="{{Nom d'hôte}}">
								</div>
								<div class="col-sm-1">
									<a class="btn btn-sm btn-success roundedLeft" id="bt_validateConfigByMqtt"><i class="fas"></i><span class="hidden-xs"> {{Valider MQTT}}</span></a>
								</div>
							</div>
						</div>

						<!-- Partie droite de l'onglet "Équipement" -->
						<div class="col-lg-6">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group">
								<label class="col-sm-2 control-label">{{Description}}</label>
								<div class="col-sm-7">
									<textarea class="form-control eqLogicAttr autogrow" data-l1key="comment"></textarea>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{openHASP version}}</label>
								<div class="col-sm-7">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="conf::version"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm control-label">{{MQTT}}</label>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Addresse Broker}}</label>
								<div class="col-sm-7">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="conf::mqtt:brokerIp"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Sujet racine}}</label>
								<div class="col-sm-7">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="conf::mqtt::rootTopic"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Nom d'hôte}}</label>
								<div class="col-sm-7">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="conf::mqtt::name"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm control-label">{{WIFI}}</label>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{SSID}}</label>
								<div class="col-sm-7">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="conf::wifi::ssid"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{IP}}</label>
								<div class="col-sm-2">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="conf::wifi::ip"></span>
								</div>
								<a class="btn btn-sm eqLogicAction" id="bt_openInNewWindow"><i class="fa fa-external-link-alt" aria-hidden="true"></i> {{Ouvrir dans nouvel onglet}}</a>
							</div>
							<div class="form-group">
								<label class="col-sm control-label">{{Graphique}}</label>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Page JSONL}}</label>
								<div class="col-sm-7">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="conf::startLayout"></span>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- Onglet des commandes générales de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtabgeneral">
				<div class="pull-right">
					<a class="btn btn-default btn-sm cmdAction" id="bt_addCommandGeneral" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
				</div>
				<br><br>
				<div class="table-responsive">
					<table id="table_cmd_general" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;"> ID</th>
								<th style="min-width:150px;width:300px;">{{Nom}}</th>
								<th style="width:130px;">{{Type}}</th>
								<th>{{Paramètres}}</th>
								<th>{{Etat}}</th>
								<th style="min-width:260px;width:400px;">{{Options}}</th>
								<th style="min-width:80px;width:180px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

			<!-- Onglet des commandes spécifiques de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtabspecific">
				<div class="form-group">
					<div class="pull-left" id="filter_page">
						<label class="control-label" style="margin-top:5px;"><i>&nbsp&nbsp;{{Filtre sur les pages}}</i></label>
						<a class="btn btn-success btn-sm commandPageFilter" style="margin-top:5px;" page="all" id="btn_commandPageFilterAllPages">{{Toutes les pages}}</a>
					</div>
					<div class="pull-right">
						<a class="btn btn-default btn-sm cmdAction"id="bt_importCommands" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Importer les objets de l'écran}}
						</a> <a class="btn btn-default btn-sm cmdAction" id="bt_addCommandSpecific" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
					</div>
				</div>
				<br><br>
				<div class="table-responsive">
					<table id="table_cmd_specific" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;"> ID</th>
								<th style="width:50px;">{{Page}}</th>
								<th style="min-width:150px;width:300px;">{{Nom}}</th>
								<th style="width:130px;">{{Type}}</th>
								<th>{{Paramètres}}</th>
								<th>{{Etat}}</th>
								<th style="min-width:260px;width:400px;">{{Options}}</th>
								<th style="min-width:80px;width:180px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'openhasp', 'js', 'openhasp'); ?>
<?php include_file('core', 'openhasp', 'class.js', 'openhasp'); ?>
<?php include_file('desktop', 'openhasp', 'css', 'openhasp'); ?>

<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js'); ?>
