require([
    "ioc/wiki30/dispatcherSingleton",
    "ioc/wiki30/UpdateViewHandler",
    "dojo/domReady!"
    ], function (getDispatcher, UpdateViewHandler) {
        
        var wikiIocDispatcher = getDispatcher();
        var updateHandler = new UpdateViewHandler();

        updateHandler.update = function () {
            var disp = wikiIocDispatcher;
            //%_changeWidgetPropertyFalse_%
            var id = disp.getGlobalState().getCurrentId();
            if (id) {
                var page = disp.getGlobalState().getContent(id);
                if (page && page.projectType === '%_projectType_%' && (!page.workflowState || page.workflowState === '%_workflowState_%')) {
                    //%_VarsIsButtonVisible_%
                    if (disp.getGlobalState().login) {
                        if (Object.keys(disp.getGlobalState().permissions).length > 0) {
                            //%_permissionButtonVisible_%
                        }
                        //%_rolesButtonVisible_%
                    }
                    //%_conditionsButtonVisible_%
                    //%_changeWidgetPropertyCondition_%
                }
            }
        };
        wikiIocDispatcher.addUpdateView(updateHandler);
});
