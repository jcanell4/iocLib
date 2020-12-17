<?php
class _WiocclLoop
{

    protected $looperInstruction;

    protected $index;
    
    public function __construct($looper)
    {
        $this->looperInstruction = $looper;
    }


    public function loop($tokens, &$tokenIndex)
    {

        $previous = WiocclParser::$cloning;

        $result = '';

        if($this->looperInstruction->getFrom() > $this->looperInstruction->getTo()){


            if ($this->looperInstruction->getFrom() != $this->looperInstruction->getStep()) {
                WiocclParser::$cloning = true;
            }

            $this->index = -1;
            $this->looperInstruction->updateLoop();
            $this->parseTokensOfItem($tokens, $tokenIndex);


        }else{
            $startTokenIndex = $tokenIndex;
            $lastBlockIndex = null;
            $lastTokenIndex = 0;

            $first = true;

            for ($this->index = $this->looperInstruction->getFrom(); $this->index <= $this->looperInstruction->getTo(); $this->index+= $this->looperInstruction->getStep()) {

                if ($first) {
                    $first = false;
                    WiocclParser::$cloning = $previous;
                } else {
                    WiocclParser::$cloning = true;
                }

                $tokenIndex = $startTokenIndex;

                $this->looperInstruction->updateLoop();

                $process = $this->looperInstruction->validateLoop();

                if (!$process && $lastTokenIndex > 0) {
                    // Ja s'ha processat previament el token de tancament i no s'acompleix la condició, no cal continuar processant
                    continue;
                }

                $parseValue = $this->parseTokensOfItem($tokens, $tokenIndex);

                if($process){
                    $result .= $parseValue;
                }

                $lastTokenIndex = $tokenIndex;

            }

            $tokenIndex = $lastTokenIndex;
        }

        WiocclParser::$cloning = $previous;

        // ALERTA[Xavi] : pel cas del foreach s'ha de fer aqui el pop perquè el token de tancament es processa a cada
        // iteració
        $this->looperInstruction->popState();

        return $result;
    }
    
    public function getCounter(){
        return $this->index;
    }

    public function parseTokensOfItem($tokens, &$tokenIndex)
    {
        $result = '';

        // tots els elements excepte el primer es marcaran com a IsCloned=true a la estructura


        while ($tokenIndex < count($tokens)) {

            // Considerem que només el for i foreach son loopers, són els unics casos en que que es té en compte
            // que els elements després del primer son clons

            // versió original, el $this->source no és un paràmetre que accepti wiocclintruction
            //$parsedValue = $this->looperInstruction->parseToken($tokens, $tokenIndex, $this->source);

            $parsedValue = $this->looperInstruction->parseToken($tokens, $tokenIndex);

            if ($parsedValue === null) { // tancament del foreach
                break;

            }
            $result .= $parsedValue;

            ++$tokenIndex;

        }

        return $result;
    }
}
