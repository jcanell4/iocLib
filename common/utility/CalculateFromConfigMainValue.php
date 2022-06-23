<?php

/**
 * @class CalculateFromConfigMainValue
 *
 * Aquesta és la clase base per les classes que retornen un valor directament extret
 * del configMain.json associat al projecte
 *
 * @author xaviergaro.dev@gamail.com
 */
abstract class CalculateFromConfigMainValue extends AbstractCalculate implements ICalculateWithConfigMain
{

    const KEY_PARAM = "key";
    const SUBSET_PARAM = "subset";
    const PATH_PARAM = "path";

    protected $configMain;

    public function __construct()
    {
        $this->addCalculatorTypeData(self::WITH_CONFIG_MAIN_TYPE);
        $this->setCalculatorTypeToInitParam(self::WITH_CONFIG_MAIN_TYPE, self::PERSISTENCE_VAR);
    }

    public function calculate($data)
    {
        $field = $this->getConfigMainValue($data[self::KEY_PARAM],
            $data[self::SUBSET_PARAM], $data[self::PATH_PARAM]);
        return $field;
    }

    public function init($configMain, $calculatorType, $defaultValue = NULL)
    {
        $this->configMain = $configMain;
    }

    function getConfigMainValue($key, $subsetName, $path)
    {
        if (!$this->configMain[$key]) {
            // Llençar excepció?
            return 'ERROR: No es troba la clau';
        }

        $subset = $this->extractSubset($this->configMain[$key], $subsetName, $key === 'metaDataProjectStructure');

        if ($subset == null) {
            return 'ERROR: No es troba el subset';
        }

        $value = $this->extractValueFromPath($path, $subset);

        if ($value == null) {
            return 'ERROR: no es pot extreure el valor';
        }
        return $value;
    }

    private function extractSubset($data, $subset, $isMetaDataProjectStructure) {
        for ($i = 0; $i < count($data); $i++) {
            $firstPair = array_slice($data[$i], 0, 1, true);
            foreach ($firstPair as $subsetKey => $subsetValue) {
                if ($subsetKey == $subset) {
                    // Cas especial, el $subset es troba al primer element de cada objecte de l'array
                    if ($isMetaDataProjectStructure) {
                        return $data[$i];
                    } else {
                        return $subsetValue;
                    }
                }
            }
        }

        return false;
    }


    private function extractValueFromPath($path, $object)
    {
        if (count($path) == 1) {
            return $object[$path[0]];
        }

        $key = array_shift($path);
        return $this->extractValueFromPath($path, $object[$key]);
    }
}
