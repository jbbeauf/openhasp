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

* **Remplacement des caractères unicode affichés**
openHASP utilise des polices de caractères incluant des caractères spéciaux sous forme d'icône.
La liste complète des icônes disponibles sous openHASP est disponible [ici](https://www.openhasp.com/latest/design/fonts/ (Fonts))
Ces caractères spéciaux n'étant pas directement supportées par Jeedom, ils peuvent être convertis en texte.
Mettre 1 caractère par ligne selon ce modèle \uXXXX:texte
  > *Valeur par défaut*
  >> \uE141:PREV
  >> \uE2DC:HOME
  >> \uE142:NEXT
  >> \uE05D:UP
  >> \uE4DB:STOP
  >> \uE045:DOWN

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
Les commandes générales sont automatiquement créées à chaque enregistrement de l'équipement.
Si une commande générale est supprimée, alors elle sera recréée.
Vous pouvez également ajouter une commande personnalisée en cliquant sur le bouton "Ajouter une commande".

**Liste des commandes générales**
| Commande                   | Type   | Description                                                               | Valeur possible /<br>Exemple valeur                                                                       | Documentation openHASP                                                               |
| -------------------------- | ------ | ------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| Rafraîchir                 | Action | Demander à l'écran une mise à jour pour toutes commandes info disponibles |                                                                                                           | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Vu pour la dernière fois   | Info   | Horodatage de la dernière fois qu'une information de l'écran a été reçue  |                                                                                                           |                                                                                      |
| IP                         | Info   | Adresse IP de l'écran                                                     | 192.168.1.1                                                                                               | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Largeur écran              | Info   | Largeur de l'écran en pixel, unité px                                     | 300 px                                                                                                    | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Hauteur écran              | Info   | Hauteur de l'écran en pixel, unité px                                     | 300 px                                                                                                    | [Command statusupdate](https://www.openhasp.com/0.7.0/commands/system/#statusupdate) |
| Page courante              | info   | Numéro de la page affichée à l'écran                                      | 1                                                                                                         | [Command page](https://www.openhasp.com/0.7.0/commands/global/#page)                 |
| Page courante Commande     | Action | Changer la page affichée à l'écran                                        | 1                                                                                                         | [Command page](https://www.openhasp.com/0.7.0/commands/global/#page)                 |
| Veille de l'écran          | info   | Etat de veille de l'écran                                                 | <li>_off_ : écran allumé</li><li>_short_ : veille niveau 1, luminosité réduite</li><li>_long_ : veille niveau 2, écran éteint</li> | [Command idle](https://www.openhasp.com/0.7.0/commands/global/#idle)                 |
| Veille de l'écran Commande | Action | Changer l'état de veille de l'écran                                       | <li>_off_ : écran allumé</li><li>_short_ : veille niveau 1, luminosité réduite</li><li>_long_ : veille niveau 2, écran éteint</li> | [Command idle](https://www.openhasp.com/0.7.0/commands/global/#idle)                 |
| Etat de l'écran            | info   | Etat de l'écran                                                           | <li>_on_ : écran allumé</li><li>_off_ : écran éteint</li>                                                                   | [Command backlight](https://www.openhasp.com/0.7.0/commands/global/#backlight)       |
| Luminosité de l'écran      | Info   | Niveau de luminosité de l'écran                                           | 1 à 255                                                                                                   | [Command backlight](https://www.openhasp.com/0.7.0/commands/global/#backlight)       |

#### 8.3 Page Commandes spécifiques
La page des commandes spécifiques sert à afficher les commandes créées automatiquement à partir des objets de l'écran.
Cette fonction est disponible si l'écran a été configuré avec son adresse ip, voir [§8.1 Page Configuration Equipement](#81-page-configuration-equipement)
Vous pouvez également ajouter une commande personnalisée en cliquant sur le bouton "Ajouter une commande".

Pour créer automatiquement les commandes à partir des objets de l'écran, cliquer sur le bouton "Importer les objets de l'écran"
1. Le plugin openHASP va télécharger la page JSONL utilisée par l'écran au démarrage 
1. Des commandes info / actions vont être créées pour tous les objets supportés
    * Nom de la commande info au format : "Page X - TYPE_OBJET TEXT_OBJET"
     Exemple : "Page 1 - Checkbox Douche"
    * Nom de la commande info au format : "Page X - TYPE_OBJET TEXT_OBJET Commande"
     Exemple : "Page 1 - Checkbox Douche Commande"

> **TYPE_OBJET** : voir le tableau ci-dessous

> **TEXT_OBJET** :
> Utilisation de la propiété 'text' de l'objet
> Si la propriété 'text' n'est pas définie ou si elle est vide, alors la propriété 'id' sera utilisée à la place

Liste des objets openHASP (version 0.7.0)
| Objet     | Propriété      | Supporté par le<br>plugin openHASP\* | Documentation                                                                 | Commande info                              | Commande action                                     | Valeur possible                       |
| --------- | -------------- | ------------------------------------ | ----------------------------------------------------------------------------- | ------------------------------------------ | --------------------------------------------------- | ------------------------------------- |
| btn       | toggle = false | oui :white_check_mark:               | [Button](https://www.openhasp.com/0.7.0/design/objects/#button)               | Page x - Bouton TEXT                       | \-                                                  | down<br>up<br>long<br>hold<br>release |
| btn       | toggle = true  | oui :white_check_mark:               | [Button](https://www.openhasp.com/0.7.0/design/objects/#button)               | Page x - Bouton Toggle TEXT                | Page x - Bouton Toggle TEXT Commande                | 0<br>1                                |
| switch    |                | oui :white_check_mark:               | [Switch](https://www.openhasp.com/0.7.0/design/objects/#switch)               | Page x - Bouton Checkbox TEXT              | Page x - Bouton Checkbox TEXT Commande              | 0<br>1                                |
| checkbox  |                | oui :white_check_mark:               | [Checkbox](https://www.openhasp.com/0.7.0/design/objects/#checkbox)           | Page x - Bouton Switch TEXT                | Page x - Bouton Switch TEXT Commande                | 0<br>1                                |
| label     |                |                                      | [Label](https://www.openhasp.com/0.7.0/design/objects/#text-label)            |                                            |                                                     |                                       |
| led       |                | oui :white_check_mark:               | [LED](https://www.openhasp.com/0.7.0/design/objects/#led-indicator)           | Page x - Bouton LED TEXT                   | Page x - Bouton LED TEXT Commande                   | 0 à 255                               |
| spinner   |                | non :x:                              | [Spinner](https://www.openhasp.com/0.7.0/design/objects/#spinner)             |                                            |                                                     |                                       |
| obj       |                | non :x:                              | [Base Object](https://www.openhasp.com/0.7.0/design/objects/#base-object)     |                                            |                                                     |                                       |
| line      |                | non :x:                              | [Line](https://www.openhasp.com/0.7.0/design/objects/#line)                   |                                            |                                                     |                                       |
| img       |                | non :x:                              | [Image](https://www.openhasp.com/0.7.0/design/objects/#image)                 |                                            |                                                     |                                       |
| cpicker   |                | oui :white_check_mark:               | [Color picker](https://www.openhasp.com/0.7.0/design/objects/#color-picker)   | Page x - Bouton Sélecteur de couleurs TEXT | Page x - Bouton Sélecteur de couleurs TEXT Commande | #RRGGBB                               |
| roller    |                | non :x:                              | [Roller](https://www.openhasp.com/0.7.0/design/objects/#roller)               |                                            |                                                     |                                       |
| dropdown  |                | non :x:                              | [Dropdown List](https://www.openhasp.com/0.7.0/design/objects/#dropdown-list) |                                            |                                                     |                                       |
| btnmatrix |                | non :x:                              | [Button Matrix](https://www.openhasp.com/0.7.0/design/objects/#button-matrix) |                                            |                                                     |                                       |
| msgbox    |                | non :x:                              | [Messagebox](https://www.openhasp.com/0.7.0/design/objects/#messagebox)       |                                            |                                                     |                                       |
| tabview   |                | non :x:                              | [Tabview](https://www.openhasp.com/0.7.0/design/objects/#tabview)             |                                            |                                                     |                                       |
| tab       |                | non :x:                              | [Tab](https://www.openhasp.com/0.7.0/design/objects/#tab)                     |                                            |                                                     |                                       |
| bar       |                | oui :white_check_mark:               | [Progress Bar](https://www.openhasp.com/0.7.0/design/objects/#progress-bar)   | Page x - Bouton Barre de progression TEXT  | Page x - Bouton Barre de progression TEXT Commande  | 0 à 100                               |
| slider    |                | oui :white_check_mark:               | [Slider](https://www.openhasp.com/0.7.0/design/objects/#slider)               | Page x - Bouton Curseur TEXT               | Page x - Bouton Curseur TEXT Commande               | 0 à 100                               |
| arc       |                | oui :white_check_mark:               | [Arc](https://www.openhasp.com/0.7.0/design/objects/#arc)                     | Page x - Bouton Arc TEXT                   | Page x - Bouton Arc TEXT Commande                   | 0 à 100                               |
| linemeter |                | oui :white_check_mark:               | [Line Meter](https://www.openhasp.com/0.7.0/design/objects/#line-meter)       | Page x - Bouton Line meter TEXT            | Page x - Bouton Line meter TEXT Commande            | 0 à 100                               |
| gauge     |                | oui :white_check_mark:               | [Gauge](https://www.openhasp.com/0.7.0/design/objects/#gauge)                 | Page x - Bouton Jauge TEXT                 | Page x - Bouton Jauge TEXT Commande                 | 0 à 100                               |
| qrcode    |                | non :x:                              | [Qrcode](https://www.openhasp.com/0.7.0/design/objects/#qrcode)               |                                            |                                                     |                                       |

> &ast; _non supporté_ = l'objet sera ignoré par le plugin openHASP lors de l'import des objets de l'écran et aucune commande ne lui sera créé automatiquement
> L'objet reste utilisable avec une commande ajoutée manuellement



# 9. Liens utiles
* Site officiel de [openHASP](https://www.openhasp.com/)