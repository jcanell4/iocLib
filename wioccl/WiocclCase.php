<?php

class WiocclCase extends WiocclInstruction {
//    const COND_ATTR = 'condition';
    const COND_ATTR = 'relation';
    const FORCHOOSE_ATTR = 'forchoose';
    const LEXPRESSION = 'lExpression';
    const REXPRESSION = 'rExpression';
    const RELATION = 'relation';

    protected $chooseId;
    protected $index;

    public function __construct($value = null, $arrays = [], $dataSource = [], &$resetables=NULL, &$parentInstruction=NULL, $mandatoryCondition = true) {
        $aux = new WiocclResetableData($resetables);
        parent::__construct($value, $arrays, $dataSource, $aux, $parentInstruction);

        $this->chooseId = WiocclChoose::PREFIX . $this->extractVarName($value, self::FORCHOOSE_ATTR, true);

        $value = str_replace("\\", "", $value);

        $this->index = empty($this->arrays[$this->chooseId]) ? 0 : count($this->arrays[$this->chooseId]);

        if ($mandatoryCondition) {
            $this->arrays[$this->chooseId][] = [
                'condition' => [
                    'lvalue' => $this->extractVarName($value, self::LEXPRESSION, false),
                    'rvalue' => $this->extractVarName($value, self::REXPRESSION, false),
                    'operator' => $this->extractVarName($value, self::RELATION, false)
                ]
            ];
        }

    }

    protected function resolveOnClose($result, $tokenEnd) {
        // ALERTA! és aquí on s'ha d'afegir el ref perquè el result és el que es fa servir al choose

        $this->arrays[$this->chooseId][$this->index]['value'] = &$result;
        $this->arrays[$this->chooseId][$this->index]['resetables'] = &$this->resetables;
        $this->updateParentArray(self::FROM_CASE, $this->chooseId);

        // Codi per afegir la estructura?
        $this->close($result, $tokenEnd);

        return "";
    }
}
