<?php
require_once "WiocclParser.php";

class WiocclInsert extends WiocclInstruction
{

    protected function getContent($token)
    {

        $ns = 'ERROR: NS not found';

        if (preg_match('/ns="(.*?)"/', $token['value'], $matches) === 1) {
            $ns = $matches[1];
        }

        if (page_exists($ns)) {
            $filename = wikiFN($ns);

            $ret= file_get_contents($filename); // Aquest es el parse de la wiki
            return $ret;

        }

    }
}