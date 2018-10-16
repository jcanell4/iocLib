<?php
require_once "WiocclInstruction.php";

class WiocclField extends WiocclInstruction {

    public function getContent ($token) {

        // és un array? el value tindrà el format xxx['yyy'] llavors el valor serà $this->arrays[xxx][yyy]

        if (preg_match ('/(.*?)\[(.*?)\]/', $token['value'], $matches)===1) {
            // es un array
            $varName = $matches[1];
            $key = $matches[2];
            if (!isset($this->parser->arrays[$varName])) {
                print_r($token['value']);
            }
            return $this->parser->arrays[$varName][$key];
        } else {
            $fieldName = $token['value'];

            // Primer comprovem als arrays i si no es troba comprovem el datasource
            if (isset($this->parser->arrays[$fieldName])) {
                return json_encode($this->parser->arrays[$fieldName]);
            } else if (isset($this->parser->dataSource[$fieldName])) {
                return $this->parser->dataSource[$fieldName];
            }

        }

        return '[ERROR: undefined field]';

    }

    public function parseTokens($tokens, &$tokenIndex)
    {

        $result = '';


        while ($tokenIndex<count($tokens)) {

            $parsedValue = $this->parser->parseToken($tokens, $tokenIndex, $this);

            if ($parsedValue === null) { // tancament del field
                break;

            } else {
                $result .= $parsedValue;
            }

            ++$tokenIndex;
        }

        return $result;
    }

}