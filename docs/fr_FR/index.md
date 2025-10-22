# Plugin Jeedom : openHASP

## 1. Description

Le plugin openHASP permet de connecter Jeedom à un écran sous openHASP.

* openHASP est un projet open-source qui permet d'utiliser des ESP32 avec écran tactile et de contrôler Home Assistant par MQTT.
* Le matériel supporté par openHASP est disponible [ici](https://www.openhasp.com/latest/hardware/ (Hardware support))

Ce plugin nécessite que openHASP soit installé (non détaillé dans cette documentation) et configuré (voir ci-dessous)

## 2. Pré-requis
* Jeedom v4.4
* Ecran sous openHASP
  * $\geqslant$ v0.7 pour avoir l'inclusion automatique

## 3. Installation plugin
Plugin dépendant installé automatiquement si non présent : 
* MQTT Manager (mqtt2)

## 4. Désinstallation du plugin
Le plugin se désabonne de tous les sujets MQTT auxquels il est abonné.

## 5. Configuration

### 5.1 Plugin Jeedom MQTT Manager (mqtt2)
Le plugin Jeedom MQTT Manager (mqtt2) doit être installé et actif.
Le plugin openHASP l'utilise directement.
Il est nécessaire de configurer le broker MQTT dans le plugin Jeedom MQTT Manager (mqtt2).

### 5.2 Broker MQTT
Un broker MQTT est nécessaire : l'écran et Jeedom s'y connectent pour échanger des données MQTT.
Vous pouvez utiliser le broker MQTT local disponible avec le plugin Jeedom MQTT Manager (mqtt2) ou autre (broker local docker ou broker distant)
* Broker MQTT local avec le plugin Jeedom MQTT Manager (mqtt2)
  * Suivez les instructions du plugin Jeedom MQTT Manager (mqtt2) pour installer le broker en local.
  * Vous devez ajouter un nouvel utilisateur/mot de passe au broker local dans le champ "Authentification" disponible dans la fenêtre de configuration du plugin mqtt2.
* Autre (broker local docker ou broker distant)
  * Reportez-vous à la documentation du plugin Jeedom MQTT Manager (mqtt2) pour le configurer et à celle du broker distant utilisé.
  * Vous devez configurer un utilisateur/mot de passe

> ##### Attention à la longeur du mot de passe
> 
> openHASP ne supporte pas un mot de passe trop long
> Celui de 64 caractères généré en installant le broker local n'est pas supporté 

### 5.3 Ecran
MQTT doit être configuré dans l'écran :
Rendez-vous à la page http://xx.xx.xx.xx/config/mqtt pour y configurer les champs utiles.
Voir [§5.2 Broker MQTT](#52-broker-mqtt) pour les champs 2, 3, 4 et 5.

1. Hostname : Nom de l'écran utilisé pour s'identifier via MQTT
2. Broker : Nom ou adresse IP du broker MQTT
   * Exemple avec le plugin Jeedom MQTT Manager (mqtt2) configuré pour utiliser le broker local : mettre le nom ou l'adresse IP de Jeedom
3. Port : Port du broker MQTT
4. Username : Nom d'utilisateur pour le broker MQTT 
5. Password : Mot de passse de  l'utilisateur pour le broker MQTT
6. Node Topic : Attention si vous voulez le modifier.
 Garder la syntaxe "XXXXX/%hostname%/%topic%"
La valeur par défaut est "hasp/%hostname%/%topic%" : si vous avez un doute conservez cette valeur


## 6. Configuration du plugin

### 6.1 Dépendances
Permet de voir l'état d'installation des dépendances et de relancer leur installation.
Seule dépendance de ce plugin : le plugin Jeedom MQTT Manager (mqtt2)

### 6.2 Configuration
* **Liste des sujets racine MQTT** 
Cette liste est utilisée pour la fonction Discovery.
Mettre 1 sujet par ligne, sans caractère slash "/"
  > *Valeur par défaut*
  > 1 seul sujet
  >> hasp
* **Durée de l'inclusion automatique**
Durée maximale en minute de l'inclusion automatique
  > *Valeur par défaut*
  >> 12
   <details>
  <summary>Justification</summary>
    <ul>
      <li>Voir openHASP/src/hasp/hasp_dispatch.cpp ==> dispatchSecondsToNextDiscovery = dispatch_setings.teleperiod * 2 + HASP_RANDOM(10);</li></li>
      <li>Avec Plate config/debug/Tele Period = 300s par défaut</li>
    </ul>
  </details>

* **Gestion des caractères Unicode**
openHASP utilise par défaut une police de caractères incluant des caractères spéciaux sous forme d'icône.
La liste complète des icônes disponibles sous openHASP est disponible [ici](https://www.openhasp.com/latest/design/fonts/ (Fonts))
Ces caractères spéciaux ne sont pas directement supportées dans l'affichage Jeedom.
  * **Affichage des caractères unicode**
  Choix de l'affichage des caractères unicode reçus : 
    1. Option "Ne pas modifier"
    Les caractères unicode reçus seront affichés tel quel dans jeedom : par exemple "Volet ouvert " 
    2. Option "Utiliser le format \uXXXX (par défaut)"
    Les caractères unicode reçus seront affichés au format \uXXXX dans jeedom : par exemple "Volet ouvert \uF11E"
    3. Option "Remplacer par le texte correspondant"
    Les caractères unicode reçus seront par le texte correspondant dans jeedom (voir ci-dessouspour *la configuration du remplacement par le texte*"* et la liste des *correspondances des caractères unicodes affichés*): par exemple "Volet ouvert {{window-shutter-open}}"
  * **Configuration pour le remplacement par le texte correspondant**     
  Personalisation des caratères qui encadrent le texte correspondant au caractère unicode
  C'est utilisé pour
    * Texte reçu de l'écran si l'option "Remplacer par le texte correspondant" est sélectionnée
    * Texte envoyé à l'écran dans tous les cas
  
    Vous pouvez modifier les *Séparateur début* et *Séparateur fin*
    Ne pas laisser vide
    Les valeurs par défaut sont "{{" et "}}" --> exemple de texte reçu "Volet ouvert {{window-shutter-open}}" 
  * **Correspondance des caractères unicode affichés**
  Liste de tous les caractères unicodes supportés par openHASP
  Format utilisé : 1 caractère par ligne selon ce modèle \uXXXX:texte-de-remplacement

### 6.3 Fonctionnalités
Voici la liste des cron utilisés et ce qu'ils font :
* **cron** (toutes les minutes)
  * Faire un rafraîchissement automatique des équipements configurés
* **cron5** (toutes les 5 minutes)
  * Faire un rafraîchissement automatique des équipements configurés
* **cron10** (toutes les 10 minutes)
  * Se ré-abonnement aux sujets racines MQTT de chaque équipement
  * Supprimer le cron mqttDiscoveryCron, créé lors de l'inclusion automatique
  * Faire un rafraîchissement automatique des équipements configurés


### 7. Page Gestion du plugin

#### 7.1 Ajouter
Entrer le nom du nouvel équipement puis valider.
La page de gestion du nouvel équipement s'ouvre, voir [§8. Équipement](#8-équipement)

#### 7.2 Configuration
Ouvre la page de [configuration du plugin](#6-configuration-du-plugin)

#### 7.3 Activer/désactiver l'inclusion automatique
L'inclusion automatique permet d'ajouter automatiquement tout nouvel écran découvert par MQTT.
Cela correspond à la fonctionnalité "discovery" disponible sur les écrans openHASP avec une version $\geqslant$ v0.7.
Cette fonctionnalité se déroule en 2 étapes : 
1. Plublication de la commande [discovery](https://www.openhasp.com/0.7.0/commands/#discovery) sur un sujet racine MQTT
2. Chaque écran qui communique sur ce sujet racine MQTT va envoyer une réponse pour s'identifier
   * Chaque écran répond en moins de 10s la première fois qu'il reçoit la requête discovery
   * Il attend environ 10min par défaut avant d'y répondre à nouveau

**Activation de l'inclusion automatique**
1. Cliquer sur le bouton "Activer l'inclusion automatique"
2. Choisir le sujet racine MQTT sur lequel publier la demande de découverte
3. Valider
4. Attendre

Un nouvel équipement est créé pour chaque nouvel écran : le sujet racine MQTT, le nom de l'écran (hostname) et l'adresse IP sont vérifiés.

**Désactivation de l'inclusion automatique**
L'inclusion automatique s'arrête : 
* Manuellement en cliquant sur "Désactiver l'inclusion automatique"
* Automatiquement après expiration du délais de l'inclusion automatique, voir [§6.2 Configuration](#62-configuration)

> L'inclusion automatique créé un objet Cron qui est supprimé au plus tard 10 minutes après la désactivation de l'inclusion automatique (voir [§6.3 Fonctionnalités](#63-fonctionnalités))

#### 7.4 Mes équipements
Cette zone permet d'afficher tous les équipements openHASP existants.

### 8. Équipement

#### 8.1 Page Configuration Equipement

**Paramèters généraux**

Paramètres communs à tous les objets Jeedom :
* Nom de l'équipement
* Objet parent
* Catégorie
* Options

Paramètre spécifique à un équipement openHASP :
* Rafraîchir automatiquement
 Option pour envoyer périodiquement une demande de mise à jour de toutes les valeurs des commandes info disponibles
 Valeurs possibles :
   * Non - valeur par défaut
   * Toutes les minutes
   * Toutes les 5 minutes
   * Toutes les 10 minutes
 

**Configuration de l'écran**
L'écran communique via MQTT avec le broker (configuration, commande et information) mais est également accessible en direct via HTTP (configuration uniquement).

* ***Configuration par adresse IP***
  * Prérequis : adresse IP de l'écran
  * Fonctionnement :
    1. Entrer l'adresse IP de l'écran
    1. Cliquer sur "Valider IP"
    1. Le plugin va récupérer les informations MQTT, WIFI et Graphique via HTTP
    1. En cas de succès, la page est rechargée et les informations de l'équipement sont rafraîchies
  * Si vous avez restreint l'accès HTTP de votre écran en définissant un nom d'utilisateur et un mot de passe
    * Vous devez renseigner les 2 informations avant de cliquer sur "Valider IP"
    * La documentation est assez claire sur l'intérêt de faire cela dans votre écran : [All HTTP communication is unencrypted and setting credentials is only a simple security measure!](https://www.openhasp.com/latest/firmware/configuration/http/ (Voir la note en début de page))
  * Le plugin openHASP est capable de communiquer avec l'écran via MQTT : commandes générales et commandes spécifiques avec import automatique des objets de l'écran
* ***Configuration par MQTT***
  * Prérequis : Sujet racine MQTT configuré dans l'écran et nom de l'hôte (hostname) de l'écran
  * Fonctionnement :
    1. Entrer le sujet racine MQTT configuré dans l'écran
    1. Entrer le nom de l'hôte (hostname) de l'écran
    1. Cliquer sur "Valider MQTT"
    1. Le plugin va récupérer les informations WIFI via MQTT
    1. En cas de succès, la page est rechargée et les informations de l'équipement sont rafraîchies
  * Le plugin openHASP est capable de communiquer avec l'écran via MQTT : commandes générales et commandes à définir manuellement par l'utilisateur

> ***Conseil***
> Configurez l'écran avec son adresse IP pour profiter de toutes les fonctionnalitées

**Informations**
| Section   | Information      | Description                                      | Configuration par adresse IP | Configuration par MQTT |
| --------- | ---------------- | ------------------------------------------------ | ---------------------------- | ---------------------- |
|           | openHASP version | Version de openHAPS dans l'écran                 | oui :white_check_mark:       | non :x:                    |
| MQTT      | Addresse Broker  | Adresse IP du broker MQTT configuré dans l'écran | oui :white_check_mark:       | non :x:                    |
| MQTT      | Sujet racine     | Sujet racine utilisé par l'écran                 | oui :white_check_mark:       | oui        :white_check_mark:     |
| MQTT      | Nom d'hôte       | Nom de l'écran                                   | oui :white_check_mark:       | oui :white_check_mark:             |
| WIFI      | SSID             | Nom du point d'accès WIFI                        | oui :white_check_mark:       | non :x:                    |
| WIFI      | IP               | Adresse IP de l'écran sur le WIFI                | oui :white_check_mark:       | oui :white_check_mark:     |
| Graphique | Page JSONL       | Nom de la page utilisée par l'écran au démarrage  | oui :white_check_mark:       | non :x:                    |

#### 8.2 Page Commandes générales
2 commandes générales sont créées avec l'équipement : "Rafraîchir" et "Vu pour la dernière fois". Si une est supprimée, alors elle sera recréée au prochain enregistrement de l'équipement.

Vous pouvez :
* gérer les autres commandes générales en cliquant sur le bouton "Gérer les commandes générales"
* ajouter une commande personnalisée en cliquant sur le bouton "Ajouter une commande"

L'objectif est de n'avoir dans vos commandes générales que des commandes utiles.

**Fenêtre de gestion des commandes générales**
Cette fenêtre permet 
1. d'ajouter une nouvelle commande générale prédéfinie
  Cocher la case "Utiliser ?" et laisser la liste "Commandes associées" à la valeur "Nouvelle commande"
  Cliquer sur "Enregistrer"
2. de supprimer une commande générale prédéfinie existante
  Décocher la case "Utiliser ?"
  Cliquer sur "Enregistrer"
3. de modifier une comande générale prédéfinie existante
  Changer la valeur de la liste "Commandes associées"
  Cliquer sur "Enregistrer"

**Liste des commandes générales disponibles **

| Groupe  | Commande                       | Type   | Description                                                                     | Valeur possible /<br>Exemple valeur                                                                                                | Documentation openHASP                                                               |
|---------|--------------------------------|--------|---------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------|
| Aucun   | Rafraîchir                     | Action | Demander à l'écran une mise à jour pour toutes commandes info disponibles       |                                                                                                                                    | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Aucun   | Vu pour la dernière fois       | Info   | Horodatage de la dernière fois qu'une information de l'écran a été reçue        |                                                                                                                                    |                                                                                      |
| Général | Page courante                  | Info   | Numéro de la page affichée à l'écran                                            | 1                                                                                                                                  | [Command page](https://www.openhasp.com/0.7.0/commands/global/#page)                 |
| Général | Changer Page courante          | Action | Changer la page affichée à l'écran                                              | 1                                                                                                                                  | [Command page](https://www.openhasp.com/0.7.0/commands/global/#page)                 |
| Général | Mode Veille de l'écran         | Info   | Etat de veille de l'écran                                                       | <li>_off_ : écran allumé</li><li>_short_ : veille niveau 1, luminosité réduite</li><li>_long_ : veille niveau 2, écran éteint</li> | [Command idle](https://www.openhasp.com/0.7.0/commands/global/#idle)                 |
| Général | Changer Mode Veille de l'écran | Action | Changer l'état de veille de l'écran                                             | <li>_off_ : écran allumé</li><li>_short_ : veille niveau 1, luminosité réduite</li><li>_long_ : veille niveau 2, écran éteint</li> | [Command idle](https://www.openhasp.com/0.7.0/commands/global/#idle)                 |
| Général | Luminosité de l'écran          | Info   | Niveau de luminosité de l'écran                                                 | 1 à 255                                                                                                                            | [Command backlight](https://www.openhasp.com/0.7.0/commands/global/#backlight)       |
| Général | Etat de l'écran                | Info   | Etat de l'écran                                                                 | <li>_on_ : écran allumé</li><li>_off_ : écran éteint</li>                                                                          | [Command backlight](https://www.openhasp.com/0.7.0/commands/global/#backlight)       |
| Général | Changer Luminosité de l'écran  | Action | Changer le niveau de luminosité de l'écran                                      | 1 à 255                                                                                                                            | [Command backlight](https://www.openhasp.com/0.7.0/commands/global/#backlight)       |
| Réseau  | IP                             | Info   | Adresse IP de l'écran                                                           | 192.168.1.1                                                                                                                        | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Réseau  | SSID                           | Info   | Nom du réseau sans fil                                                          | Toto                                                                                                                               | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Réseau  | MAC                            | Info   | Adresse MAC de l'écran                                                          | AA:BB:CC:DD:EE:FF                                                                                                                  | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Réseau  | RSSI                           | Info   | Niveau de puissance WIFI reçu                                                   | -50                                                                                                                                | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Écran   | Largeur écran                  | Info   | Largeur de l'écran en pixel, unité px                                           | 300 px                                                                                                                             | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Écran   | Hauteur écran                  | Info   | Hauteur de l'écran en pixel, unité px                                           | 300 px                                                                                                                             | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Écran   | Activer Antiburn               | Action | Gérer la protection de l'écran LCD                                              | <li>_on_ : démarrer la protection </li><li>_off_ : arrête la protection</li>                                                       | [Command antiburn](https://www.openhasp.com/0.7.0/commands/system/#antiburn)         |
| Système | Version                        | Info   | Version de l'écran                                                              | 0.7.0-rc12                                                                                                                         | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Système | Durée de fonctionnement        | Info   | Durée de fonctionnement, en seconde                                             | 30 s                                                                                                                               | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Système | LWT                            | Info   | Last Will and Testament (LWT) : pour informer de l'état de connexion de l'écran |                                                                                                                                    | [LWT](https://www.hivemq.com/blog/mqtt-essentials-part-9-last-will-and-testament/)   |
| Système | Redémarrer                     | Action | Redémarrer l'écran                                                              |                                                                                                                                    | [Command reboot](https://www.openhasp.com/0.7.0/commands/system/#reboot-or-restart)  |


#### 8.3 Page Commandes spécifiques
La page des commandes spécifiques sert à afficher les commandes créées automatiquement à partir des objets de l'écran.
Cette fonction est disponible si l'écran a été configuré avec son adresse ip, voir [§8.1 Page Configuration Equipement](#81-page-configuration-equipement)
Vous pouvez également ajouter une commande personnalisée en cliquant sur le bouton "Ajouter une commande".

Pour créer automatiquement les commandes à partir des objets de l'écran, cliquer sur le bouton "Importer les objets de l'écran"
1. Le plugin openHASP va télécharger la page JSONL utilisée par l'écran au démarrage 
1. Tous les objets supportés sont listés et proposent
  2.1 Des commandes spécifiques, en bleu : spécifique au type d'objet
  2.2 Des commandes communes, en orange : commun à tous les objets

__Important__ : Toutes les commandes spécifiques ou communes ne sont pas décrites dans cette documentation. Un texte explication est disponible pour chaque commande disponible.

Liste des objets openHASP (version 0.7.0)
| Objet     | Supporté par le<br>plugin openHASP\* | Documentation                                                                 |
|-----------|--------------------------------------|-------------------------------------------------------------------------------|
| btn       | oui :white_check_mark:               | [Button](https://www.openhasp.com/0.7.0/design/objects/#button)               |
| switch    | oui :white_check_mark:               | [Switch](https://www.openhasp.com/0.7.0/design/objects/#switch)               |
| checkbox  | oui :white_check_mark:               | [Checkbox](https://www.openhasp.com/0.7.0/design/objects/#checkbox)           |
| label     | oui :white_check_mark:               | [Label](https://www.openhasp.com/0.7.0/design/objects/#text-label)            |
| led       | oui :white_check_mark:               | [LED](https://www.openhasp.com/0.7.0/design/objects/#led-indicator)           |
| spinner   | non :x:                              | [Spinner](https://www.openhasp.com/0.7.0/design/objects/#spinner)             |
| obj       | non :x:                              | [Base Object](https://www.openhasp.com/0.7.0/design/objects/#base-object)     |
| line      | non :x:                              | [Line](https://www.openhasp.com/0.7.0/design/objects/#line)                   |
| img       | non :x:                              | [Image](https://www.openhasp.com/0.7.0/design/objects/#image)                 |
| cpicker   | oui :white_check_mark:               | [Color picker](https://www.openhasp.com/0.7.0/design/objects/#color-picker)   |
| roller    | oui :white_check_mark:               | [Roller](https://www.openhasp.com/0.7.0/design/objects/#roller)               |
| dropdown  | oui :white_check_mark:               | [Dropdown List](https://www.openhasp.com/0.7.0/design/objects/#dropdown-list) |
| btnmatrix | oui :white_check_mark:                              | [Button Matrix](https://www.openhasp.com/0.7.0/design/objects/#button-matrix) |
| msgbox    | non :x:                              | [Messagebox](https://www.openhasp.com/0.7.0/design/objects/#messagebox)       |
| tabview   | non :x:                              | [Tabview](https://www.openhasp.com/0.7.0/design/objects/#tabview)             |
| tab       | non :x:                              | [Tab](https://www.openhasp.com/0.7.0/design/objects/#tab)                     |
| bar       | oui :white_check_mark:               | [Progress Bar](https://www.openhasp.com/0.7.0/design/objects/#progress-bar)   |
| slider    | oui :white_check_mark:               | [Slider](https://www.openhasp.com/0.7.0/design/objects/#slider)               |
| arc       | oui :white_check_mark:               | [Arc](https://www.openhasp.com/0.7.0/design/objects/#arc)                     |
| linemeter | oui :white_check_mark:               | [Line Meter](https://www.openhasp.com/0.7.0/design/objects/#line-meter)       |
| gauge     | oui :white_check_mark:               | [Gauge](https://www.openhasp.com/0.7.0/design/objects/#gauge)                 |
| qrcode    | oui :white_check_mark:               | [Qrcode](https://www.openhasp.com/0.7.0/design/objects/#qrcode)               |
|           |                                      |                                                                               |

#### 8.4 Commande Action

##### 8.4.1 Option Retain et Refresh
Les 2 options sont disponibles dans la colonne "Options MQTT" :
1. **Retain** → dire au serveur de retenir la valeur
   Penser à Enregistrer l’équipement avant de tester
   Si coché : la valeur sera conservée par le serveur MQTT
   Si décoché : la valeur ne sera pas conservée, le plugin gère la suppression du tag Retain auprès du serveur
2. **Refresh** → demander à l’écran d’envoyer la valeur de l’objet
   Penser à Enregistrer l’équipement avant de tester
   Si coché la commande est envoyée 2 fois : 1 première fois avec la valeur demandée et 1 seconde fois avec une valeur vide → l’écran va alors envoyer la valeur qu’il affiche via MQTT
   Par exemple avec ces 2 commandes : 1 action et 1 info, si vous voulez que l’info soit mise à jour alors il faut cocher cette option Refresh

##### 8.4.2 Ajouter un lien avec une commande info
Cette option est disponible dans la colonne "Info Jeedom liée" :
1. **Relier à info** : checkbox pour activer / désactiver le lien avec une commande info
2. **Champ texte de choix de la commande** : ouvre une popup Jeedom pour sélectionner une commande de type info. Une seule commande info possible, pas de combinaison

Le mécanisme de lien avec une commande info est actif lorsque la checkbox est cochée et qu'une commande info sélectionnée est valide.
Quand la valeur de la commande info liée change alors la commande action est exécutée avec la valeur de la commande info liée.

# 9. Liens utiles
* Site officiel de [openHASP](https://www.openhasp.com/)