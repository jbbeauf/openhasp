# openHASP pour Jeedom

## Description

Le plugin openHASP permet de connecter Jeedom à un écran sous openHASP.

* openHASP est un projet open-source qui permet d'utiliser des ESP32 avec écran tactile et de contrôler Home Assistant par MQTT.
* Le matériel supporté par openHASP est disponible [ici](https://www.openhasp.com/latest/hardware/)

Ce plugin nécessite que openHASP soit installé (non détaillé dans cette documentation) et configuré (voir ci-dessous)

## Pré-requis
* Jeedom v4
* Plugin Jeedom mqtt2
* Ecran sous openHASP
  * $\geqslant$ v0.7 pour avoir l'inclusion automatique

## Configuration

### Plugin Jeedom mqtt2
Installé
user / mdp pas trop long

### Ecran sous openHASP
http://xx.xx.xx.xx/config/mqtt : Hostname, broker

## Installation plugin

## Désinstallation du plugin

## Configuration du plugin
* Liste des topics racine 
Pour l'inclusion automatique
* Durée de l'inclusion automatique
En minute
* Remplacement des caractères unicode affichés 
Remplacement des caractères unicode, comme \uE2DC qui est affichés comme une icône Maison sur l'écran, par un texte dans Jeedom, par exemple HOME.

## Fonctionnalitées
### Discovery
Publication de la demande discovery
Chaque écran répond en moins de 10s la première fois qu'il reçoit la requête discovery et attend environ 10min par défaut avant d'y répondre à nouveau 

### Equipement

#### Nouveau
Créer équipement, rien de plus
#### Configuration
Il faut lier l'écran qui communique en mqtt à Jeedom
On a 2 moyens d'accéder à l'écran : 
* Par IP
* Par MQTT
#### Config par ip
Choix conseillé : permet de récupérer toutes les informations MQTT et les objets graphiques
#### Config par mqtt
Choix alternatif, ça permet aussi de récupérer l'adresse IP
#### Commandes par défaut
Refresh, vu la dernière fois, taille écran, page courante
#### Commandes importées
Pré-requis : config par IP
Récupère tous les objets graphiques et créé des commandes GET/SET pour ceux supportés


| Objet     | Supporté | Description                                                                   |
| --------- | -------- | ----------------------------------------------------------------------------- |
| btn       |   oui    | [Button](https://www.openhasp.com/0.7.0/design/objects/#button)               |
| switch    |   oui    | [Switch](https://www.openhasp.com/0.7.0/design/objects/#switch)               |
| checkbox  |   oui    | [Checkbox](https://www.openhasp.com/0.7.0/design/objects/#checkbox)           |
| label     |   non    | [Label](https://www.openhasp.com/0.7.0/design/objects/#text-label)            |
| led       |   oui    | [LED](https://www.openhasp.com/0.7.0/design/objects/#led-indicator)           |
| spinner   |   non    | [Spinner](https://www.openhasp.com/0.7.0/design/objects/#spinner)             |
| obj       |   non    | [Base Object](https://www.openhasp.com/0.7.0/design/objects/#base-object)     |
| line      |   non    | [Line](https://www.openhasp.com/0.7.0/design/objects/#line)                   |
| img       |   non    | [Image](https://www.openhasp.com/0.7.0/design/objects/#image)                 |
| cpicker   |   oui    | [Color picker](https://www.openhasp.com/0.7.0/design/objects/#color-picker)   |
| roller    |   non    | [Roller](https://www.openhasp.com/0.7.0/design/objects/#roller)               |
| dropdown  |   non    | [Dropdown List](https://www.openhasp.com/0.7.0/design/objects/#dropdown-list) |
| btnmatrix |   non    | [Button Matrix](https://www.openhasp.com/0.7.0/design/objects/#button-matrix) |
| msgbox    |   non    | [Messagebox](https://www.openhasp.com/0.7.0/design/objects/#messagebox)       |
| tabview   |   non    | [Tabview](https://www.openhasp.com/0.7.0/design/objects/#tabview)             |
| tab       |   non    | [Tab](https://www.openhasp.com/0.7.0/design/objects/#tab)                     |
| bar       |   oui    | [Progress Bar](https://www.openhasp.com/0.7.0/design/objects/#progress-bar)   |
| slider    |   oui    | [Slider](https://www.openhasp.com/0.7.0/design/objects/#slider)               |
| arc       |   oui    | [Arc](https://www.openhasp.com/0.7.0/design/objects/#arc)                     |
| linemeter |   oui    | [Line Meter](https://www.openhasp.com/0.7.0/design/objects/#line-meter)       |
| gauge     |   oui    | [Gauge](https://www.openhasp.com/0.7.0/design/objects/#gauge)                 |
| qrcode    |   non    | [Qrcode](https://www.openhasp.com/0.7.0/design/objects/#qrcode)               |


# Liens utiles
* [openHASP](https://www.openhasp.com/)