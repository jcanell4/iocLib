<?php
require_once "Html2DWParser.php";

class Html2DWHeader extends Html2DWInstruction {


    protected function resolveOnClose($result, $tokenEnd) {

        if (isset($this->extra['trim']) && $this->extra['trim']) {
            $result = trim($result);
        }

        $post = $this->getReplacement(self::CLOSE);

        // ALERTA! en el darrer token $tokenNext és null!
        if ($tokenEnd['next'] && preg_match('/data-wioccl-ref="(.*?")/', $tokenEnd['next']['raw'], $matches)) {
            $refId = intval($matches[1]);

            $structure = Html2DWParser::$structure;
            $wioccl = $structure[$refId];

            if ($wioccl->type ==="readonly_close"){
                $post = str_replace("\n", "", $post);

                $state = $tokenEnd['next']['next']['state'];
                if ($tokenEnd['next'] && $tokenEnd['next']['next'] && $tokenEnd['next']['next']['state'] === "content") {
                    $wioccl->open .= "\n"; // reafegim el salt de línia després del readonly
                }

            }
        }

        return $this->getReplacement(self::OPEN) . $result . $post;
    }
}