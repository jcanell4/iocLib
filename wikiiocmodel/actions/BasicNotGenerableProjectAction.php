<?php
if (!defined("DOKU_INC")) die();

/**
 * Extensió particular que afecta als projectes que no tenen 'Generació de Projecte'
 * Modifica els permisos i arxius de les 'persones' del projecte
 *  @deprecated deprecated desde la versió 3.1 (PHP 7 i WIKI 2018), per fer-ho 
 * generic i evitar haver de crea una nova classe sempre que els projectes no els calgui generar-se
 * 
 * A més, així, només es podia fer sevir pels SetProjectActions, Amb la variable es pot extendre a altres ProjectActions.
 */
class BasicNotGenerableProjectAction extends BasicSetProjectAction {

    /**
     * Envía los datos del proyecto al ProjectModel y obtiene la estructura y los valores del proyecto
     * @return array con la estructura y los valores del proyecto
     */
    protected function responseProcess() {
        $response = parent::responseProcess();

        $model = $this->getModel();
        if($model->getNeedGenerateAction()){
            //Malgrat que la variable indiqui que necessita Generació, pel fet de pertanyer a aquesta classe,
            //cal froçar l'actualització sense haver-se generat
            $params = $model->buildParamsToPersons($response[ProjectKeys::KEY_PROJECT_METADATA], $response['old_persons']);
            $model->modifyACLPageAndShortcutToPerson($params);
            $this->forceFileComponentRenderization();
        }

        return $response;
    }

}
