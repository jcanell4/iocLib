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

                // tant el [next][next] com el [next][next][bext] s'estableix a l'IocInstruction
                $stateNext = $tokenEnd['next']['next'];
                $stateNextNext = $tokenEnd['next']['next']['next'];
//                if ($tokenEnd['next'] && $tokenEnd['next']['next'] && ($tokenEnd['next']['next']['state'] === "content")) {

                // ALERTA! només comprovem el type, però no mirem si el següent es tancament perquè no ho tenim
                // estandaritzat (els states per open/close es van ficar pel wioccl i no ens serveixen tal com estant)
                if ($tokenEnd['next'] && $tokenEnd['next']['next'] && ($stateNext['state'] === "content"
                        || ($stateNext['mode'] === "block" && $stateNext['type'] !== $stateNextNext['type']))) {
//                if ($tokenEnd['next'] && $tokenEnd['next']['next'] && $tokenEnd['next']['next']['state'] === "content" || $tokenEnd['next']['next']['state'] === "paragraph") {
                    $wioccl->open .= "\n"; // reafegim el salt de línia després del readonly
                }

            }
        }

        return $this->getReplacement(self::OPEN) . $result . $post;
    }
}