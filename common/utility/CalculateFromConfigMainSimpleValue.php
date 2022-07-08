<?php

/**
 * @class CalculateFromConfigMainValue
 *
 * Implementació simple que retorna un valor directament extret  del configMain.json associat al projecte
 *
 * @author xaviergaro.dev@gamail.com
 */
class CalculateFromConfigMainSimpleValue extends CalculateFromConfigMainValue
{

    public function calculate($data)
    {
        $field = $this->getConfigMainValue($data[self::KEY_PARAM],
            $data[self::SUBSET_PARAM], $data[self::PATH_PARAM]);

        return $field;
    }

}
