# openHASP pour Jeedom

## Description

Le plugin openHASP permet de connecter Jeedom à un écran sous openHASP.

* openHASP est un projet open-source qui permet d'utiliser des ESP32 avec écran tactile et de contrôler Home Assistant par MQTT.
* Le matériel supporté par openHASP est disponible [ici](https://www.openhasp.com/latest/hardware/)

Ce plugin nécessite que openHASP soit installé (non détaillé dans cette documentation) et configuré (voir ci-dessous)

## Pré-requis
* Jeedom v4
* Plugin Jeedom mqtt2
* Ecran sous openHASP v0.7

## Configuration

### Plugin Jeedom mqtt2
Installé
user / mdp pas trop long

### Ecran sous openHASP
mqtt : broker

## Installation plugin

## Désinstallation du plugin

## Configuration du plugin

## Fonctionnalitées

### Discovery

### Equipement

#### Nouveau
#### Config par ip
#### Config par mqtt
#### Commandes par défaut
#### Commandes importées



------
| obj       | Type     | Description                                                                   | [Extra Parts](https://www.openhasp.com/0.7.0/design/styling/#parts) |
| --------- | -------- | ----------------------------------------------------------------------------- | ------------------------------------------------------------------- |
| btn       | Binary   | [Button](https://www.openhasp.com/0.7.0/design/objects/#button)               |                                                                     |
| switch    | Toggle   | [Switch](https://www.openhasp.com/0.7.0/design/objects/#switch)               | indicator, knob                                                     |
| checkbox  | Toggle   | [Checkbox](https://www.openhasp.com/0.7.0/design/objects/#checkbox)           | indicator                                                           |
| label     | Visual   | [Label](https://www.openhasp.com/0.7.0/design/objects/#text-label)            |                                                                     |
| led       | Visual   | [LED](https://www.openhasp.com/0.7.0/design/objects/#led-indicator)           |                                                                     |
| spinner   | Visual   | [Spinner](https://www.openhasp.com/0.7.0/design/objects/#spinner)             | indicator                                                           |
| obj       | Visual   | [Base Object](https://www.openhasp.com/0.7.0/design/objects/#base-object)     |                                                                     |
| line      | Visual   | [Line](https://www.openhasp.com/0.7.0/design/objects/#line)                   |                                                                     |
| img       | Visual   | [Image](https://www.openhasp.com/0.7.0/design/objects/#image)                 |                                                                     |
| cpicker   | Selector | [Color picker](https://www.openhasp.com/0.7.0/design/objects/#color-picker)   | knob                                                                |
| roller    | Selector | [Roller](https://www.openhasp.com/0.7.0/design/objects/#roller)               | selected                                                            |
| dropdown  | Selector | [Dropdown List](https://www.openhasp.com/0.7.0/design/objects/#dropdown-list) | selected, items, scrollbar                                          |
| btnmatrix | Selector | [Button Matrix](https://www.openhasp.com/0.7.0/design/objects/#button-matrix) | items                                                               |
| msgbox    | Selector | [Messagebox](https://www.openhasp.com/0.7.0/design/objects/#messagebox)       | items, items_bg                                                     |
| tabview   | Selector | [Tabview](https://www.openhasp.com/0.7.0/design/objects/#tabview)             | items, items_bg, indicator, selected                                |
| tab       | Selector | [Tab](https://www.openhasp.com/0.7.0/design/objects/#tab)                     |                                                                     |
| bar       | Range    | [Progress Bar](https://www.openhasp.com/0.7.0/design/objects/#progress-bar)   | indicator                                                           |
| slider    | Range    | [Slider](https://www.openhasp.com/0.7.0/design/objects/#slider)               | indicator, knob                                                     |
| arc       | Range    | [Arc](https://www.openhasp.com/0.7.0/design/objects/#arc)                     | indicator, knob                                                     |
| linemeter | Range    | [Line Meter](https://www.openhasp.com/0.7.0/design/objects/#line-meter)       |                                                                     |
| gauge     | Range    | [Gauge](https://www.openhasp.com/0.7.0/design/objects/#gauge)                 | indicator, ticks                                                    |
| qrcode    | Visual   | [Qrcode](https://www.openhasp.com/0.7.0/design/objects/#qrcode)               |                                                                     |



# Liens utiles
* [openHASP](https://www.openhasp.com/)