
jeedom.openhasp = function() {};
jeedom.openhasp.utils = function() {};
jeedom.openhasp.mqtt = function() {};

jeedom.openhasp.utils.promptMqttTopic = function(_title, _message, _callback){
    var inputOptionsPrompt = [];
    for(let topic of openhasp_mqttRootTopics){
        inputOptionsPrompt.push({value : topic,text : topic});
    }
    bootbox.prompt({
      title: _title,
      value : inputOptionsPrompt[0].value,
      message : _message,
      inputType: 'select',
      inputOptions:inputOptionsPrompt,
      callback: function (result) {
        if(result === null){
          return;
        }
        _callback(result)
      }
    });
  }

 /* Function to refresh the page
 * Ask confirmation if the page has been modified
 * 99% repris du plugin jMQTT
 */
  jeedom.openhasp.utils.refreshEqLogicPage = function() {
    function refreshPage() {
        if ($('.eqLogicAttr[data-l1key=id]').value() != "") {
            tab = null
            if (document.location.toString().match('#')) {
                tab = '#' + document.location.toString().split('#')[1];
                if (tab != '#') {
                    tab = $('a[href="' + tab + '"]')
                } else {
                    tab = null
                }
            }
            $('.eqLogicDisplayCard[data-eqlogic_id="' + $('.eqLogicAttr[data-l1key=id]').value() + '"]').click();
            if (tab) tab.click();
        }
    }
    if (jeeFrontEnd.modifyWithoutSave || window.modifyWithoutSave) {
        bootbox.confirm("{{La page a été modifiée. Etes-vous sûr de vouloir la recharger sans sauver ?}}", function (result) {
            if (result)
                refreshPage();
        });
    }
    else
        refreshPage();
    }

  jeedom.openhasp.mqtt.discovery = function(_params){
    var paramsRequired = ['mode'];
    var paramsSpecifics = {};
    try {
      jeedom.private.checkParamsRequired(_params || {}, paramsRequired);
    } catch (e) {
      (_params.error || paramsSpecifics.error || jeedom.private.default_params.error)(e);
      return;
    }
  
    var params = $.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {});
    var paramsAJAX = jeedom.private.getParamsAJAX(params);
    paramsAJAX.url = "plugins/openhasp/core/ajax/openhasp.ajax.php";
    paramsAJAX.data = {
        action: "discovery",
        mode: _params.mode,
        mqttRootTopic: _params.mqttRootTopic
    };
    $.ajax(paramsAJAX);
  }