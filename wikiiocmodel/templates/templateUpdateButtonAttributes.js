require([
    "ioc/wiki30/dispatcherSingleton",
    "ioc/wiki30/UpdateViewHandler",
     "dijit/registry",
    "dojo/domReady!"
    ], function (getDispatcher, UpdateViewHandler, registry) {
        var wikiIocDispatcher = getDispatcher();
        var updateHandler = new UpdateViewHandler();
        updateHandler.update = function () {
            var disp = wikiIocDispatcher;
            var id = disp.getGlobalState().getCurrentId();
            if (id) {
                var page = disp.getGlobalState().getContent(id);

                if (page && page.projectType === '%_projectType_%'  && (!page.workflowState || page.workflowState === '%_workflowState_%')) {
                    var buttons = ___JSON_BUTTON_ATTRIBUTES_DATA___;
                    buttons.forEach(function(buttonAttributes){
                        var buttonId = buttonAttributes.id;
                        var button = registry.byId(buttonId);
                        var condition = page.projectType + ((page.workflowState) ? page.workflowState : "");
                        if (buttonAttributes.toDelete && buttonAttributes.toDelete.length > 0) {
                            buttonAttributes.toDelete.forEach(function(key){
                                if (button[key] !== undefined){
                                    if(wikiIocDispatcher.originalButtonAttributes[buttonId]===undefined){
                                        wikiIocDispatcher.originalButtonAttributes[buttonId]={"toSet":{}};
                                    }else if(wikiIocDispatcher.originalButtonAttributes[buttonId]["toSet"] == undefined){
                                        wikiIocDispatcher.originalButtonAttributes[buttonId]["toSet"] = {};
                                    }
                                    wikiIocDispatcher.originalButtonAttributes[buttonId]["toSet"][key] = button[key];
                                }
                            });
                        }
                        if (buttonAttributes.toSet){
                            for (const [key, value] of Object.entries(buttonAttributes.toSet)) {
                                if (button[key] === undefined){
                                    if(wikiIocDispatcher.originalButtonAttributes[buttonId]===undefined){
                                        wikiIocDispatcher.originalButtonAttributes[buttonId]={"toDelete":{}};
                                    }else if(wikiIocDispatcher.originalButtonAttributes[buttonId]["toDelete"] == undefined){
                                        wikiIocDispatcher.originalButtonAttributes[buttonId]["toDelete"] = [];
                                    }
                                    wikiIocDispatcher.originalButtonAttributes[buttonId]["toDelete"].push(key);
                                }else{
                                    if(wikiIocDispatcher.originalButtonAttributes[buttonId]===undefined){
                                        wikiIocDispatcher.originalButtonAttributes[buttonId]={"toSet":{}};
                                    }else if(wikiIocDispatcher.originalButtonAttributes[buttonId]["toSet"] == undefined){
                                        wikiIocDispatcher.originalButtonAttributes[buttonId]["toSet"] = {};
                                    }
                                    wikiIocDispatcher.originalButtonAttributes[buttonId]["toSet"][key] = button[key];
                                }
                            }
                        }
                        if (buttonAttributes.toDelete && buttonAttributes.toDelete.length > 0) {
                            buttonAttributes.toDelete.forEach(function(key){
                                if (button[key] !== undefined){
                                    button[key] = undefined;
                                }
                            });
                        }
                        if (buttonAttributes.toSet){
                           for (const [key, value] of Object.entries(buttonAttributes.toSet)) {
                               button.set(key, value);
                           }
                        }
                    });
                }
            }
        };
        wikiIocDispatcher.addUpdateView(updateHandler);
});