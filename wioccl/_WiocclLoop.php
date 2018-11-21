<?php
class _WiocclLoop
{

    protected $looperInstruction;
    
    public function __construct($looper)
    {
        $this->looperInstruction = $looper;
    }

    public function loop($tokens, &$tokenIndex)
    {
        
        $result = '';
        if($this->looperInstruction->getFrom() > $this->looperInstruction->getTo()){
            $this->index = -1;
            $this->looperInstruction->updateLoop();
            $this->parseTokensOfItem($tokens, $tokenIndex);
        }else{
            $startTokenIndex = $tokenIndex;
            $lastBlockIndex = null;
            $lastTokenIndex = 0;

            for ($this->index = $this->looperInstruction->getFrom(); $this->index <= $this->looperInstruction->getTo(); $this->index+= $this->looperInstruction->getStep()) {

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
        return $result;
    }
    
    public function getCounter(){
        return $this->index;
    }

    public function parseTokensOfItem($tokens, &$tokenIndex)
    {
        $result = '';
        while ($tokenIndex < count($tokens)) {

            $parsedValue = $this->looperInstruction->parseToken($tokens, $tokenIndex, $this->source);

            if ($parsedValue === null) { // tancament del foreach
                break;

            }
            $result .= $parsedValue;

            ++$tokenIndex;
        }
        return $result;
    }
}
