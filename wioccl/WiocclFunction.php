<?php
class WiocclFunction extends WiocclInstruction
{

    protected $functionName = '';
    protected $arguments = [];
    protected $rawArguments = '';

    public function __construct($value = null, array $arrays = array(), array $dataSource = array(), $resetables = NULL, $parentInstruction = NULL) {

        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        $this->pauseStructureGeneration();
    }

    protected function init($value, $tokenEnd)
    {
        if (preg_match('/(.*?)\((.*)\)/s', $value, $matches) === 0) {
            throw new Exception("Incorrect function structure");
        };

        $this->functionName = $matches[1];

//        $this->pauseStructureGeneration();

        $this->arguments = $this->extractArgs($matches[2]);

//        ALERTA! Aquests no son els rawArguments, son els arguments ja parsejats!!

        $this->generateRawValue($rawValue, $this->currentToken['tokenIndex']+1, $tokenEnd['tokenIndex']-1);

        // Els arguments son els valors que es troben entre el primer ( i l'últim )

        $paramStart = strpos($rawValue, '(') +1;
        $paramEnd = strrpos($rawValue, ')');

        $this->rawArguments = substr($rawValue, $paramStart, $paramEnd - $paramStart);

//        $this->resumeStructureGeneration();

        if($this->arguments==null){
            $this->arguments=[];
        }
    }


    protected function extractArgs($string)
    {
        $string = preg_replace('/(^|\\s)\'\'\'/', '$1"\'', $string);
        $string = preg_replace('/\'\'\'(\\s|$)/', '\'"$1', $string);
        $string = preg_replace("/''/", '"', $string);
//        $string = (new WiocclParser($string, $this->arrays, $this->dataSource))->getValue();
        $string = WiocclParser::getValue($string, $this->arrays, $this->dataSource, $this->resetables);
        $string = "[" . $string . "]";

        $jsonArgs = json_decode($string, true);
        //return $jsonArgs;

        //ALERTA: cal verificar quan es produeix una situació en la que $jsonArgs té un valor incorrecte
        return ($jsonArgs==NULL || !is_array($jsonArgs)) ? [] : $jsonArgs;
    }

    protected function resolveOnClose($result, $tokenEnd) {
        $this->init($result, $tokenEnd);

        $method = array("IocCommonFunctions", $this->functionName);
        if(is_callable($method)){
            try{
                $result = call_user_func_array($method, $this->arguments);
            } catch (Error $e){
                $result = $e->getMessage();
            }
        }else{
            $result = "[ERROR! No existeix la funció ${$method[1]}]";
        }

        // Normalitzem el resultat
        $result = WiocclParserData::normalizeValue($result);

        $this->resumeStructureGeneration();

        $this->close($result, $tokenEnd);


        // només s'ha de canviar el primer element del format
        if ($this->rawArguments) {
            $this->item->open = sprintf($this->currentToken['extra']['opening-format'], $this->functionName, "%s");
        } else {
            $this->item->open = sprintf($this->currentToken['extra']['opening-format'], $this->functionName, "");
        }

        $this->item->attrs = $this->rawArguments;
        $this->item->close = "";
        return $result;
    }

}

