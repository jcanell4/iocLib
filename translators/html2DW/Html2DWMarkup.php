<?php
require_once "Html2DWParser.php";

class Html2DWMarkup extends Html2DWInstruction {

//    protected function getContent($token) {
//
//        // Això no es crida
//        return $this->getReplacement(self::OPEN) . $token['value'];
//    }

    protected function resolveOnClose($result, $tokenEnd) {

        if (isset($this->extra['trim']) && $this->extra['trim']) {
            $result = trim($result);
        }

//        $post = $this->getReplacement(self::CLOSE);
//
//        // ALERTA! en el darrer token $tokenNext és null!
//        if ($tokenEnd['next'] && preg_match('/data-wioccl-ref="(.*?")/', $tokenEnd['next']['raw'], $matches)) {
//            $refId = intval($matches[1]);
//
//            $structure = Html2DWParser::$structure;
//            $wioccl = $structure[$refId];
//            if ($wioccl->type ==="readonly_close"){
//                $post = str_replace("\n", "", $post);
//            }
//        }
//
//        return $this->getReplacement(self::OPEN) . $result . $post;
        return $this->getReplacement(self::OPEN) . $result . $this->getReplacement(self::CLOSE);
    }
}