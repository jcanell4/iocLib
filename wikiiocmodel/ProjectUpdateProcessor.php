<?php
/**
 * ProjectUpdateProcessor: clases para la actualización de los datos de proyecto
 *                         a partir de los parámetros de configuración del tipo de proyecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();
if (!defined('WIKI_IOC_MODEL')) define('WIKI_IOC_MODEL', DOKU_INC . "lib/plugins/wikiiocmodel/");

/**
 * Copia un único valor en los campos especificados del archivo de datos de un proyecto
 */
class FieldSingleSubstitutionProjectUpdateProcessor {
    /**
     * Modifica el conjunto de datos del archivo (meta.mdpr) de datos de un proyecto
     * @param string $value : valor que se utiliza en la substitución
     * @param array $params : conjunto de campos sobre los que se aplica la sustitución
     * @param array $projectMetaData : conjunto de datos del archivo meta.mdpr
     */
    public function runProcess($value, $params, &$projectMetaData) {
        foreach ($params as $field) {
            if (in_array($field, $projectMetaData)) {
                $projectMetaData[$field] = $value;
            }
        }
    }
}

/**
 * Incrementa el valor en los campos especificados del archivo de datos de un proyecto
 */
class FieldIncrementProjectUpdateProcessor {
    /**
     * Modifica el conjunto de datos del archivo (meta.mdpr) de datos de un proyecto
     * @param string $value : valor que se utiliza para incrementar el valor del campo
     * @param array $params : array de campos [key, type, value] sobre los que se aplica el incremento
     * @param array $projectMetaData : conjunto de datos del archivo meta.mdpr
     */
    public function runProcess($value, $params, &$projectMetaData) {
        foreach ($params['fields'] as $field) {
            if (in_array($field, $projectMetaData)) {
                switch ($params['type']) {
                    case 'module':
                        $projectMetaData[$field] = (($projectMetaData[$field] - 1 + $value) % $params['value']) + 1;
                        break;
                    case 'data':
                        $fecha = new DateTime($projectMetaData[$field]);
                        $fecha->add(new DateInterval('P'.$value.$params['value']));
                        $projectMetaData[$field] = $fecha->format('Y-m-d');
                        break;
                    default:
                        break;
                }
            }
        }
    }
}

