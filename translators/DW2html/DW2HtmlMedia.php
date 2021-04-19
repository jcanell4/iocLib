<?php
require_once "DW2HtmlParser.php";

if (!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) . "/../../") . '/');


class DW2HtmlMedia extends DW2HtmlImage {

    private static $counter = 0;


    public function open() {


        $token = $this->currentToken;

        // Descartem el segón paràmetre, la clau no es fa servir


        // remove {{ }}
        $command = substr($token['raw'], 2, -2);

        // title (no es fa servir)
        list($command, $title) = explode('|', $command);
        $command = trim($command);

        // get site and video
        list($type, $id) = explode('>', $command);

        // what size?
        list($id, $param) = explode('?', $id, 2);

        $size = '';
        $width = '';
        $height = '';

        foreach (SharedConstants::ONLINE_VIDEO_CONFIG['sizes'] as $key => $value) {

            if (strpos($param, $key) !== false) {
                $size = $key;
                list($width, $height) = explode('x', $value, 2);
                break;
            }

        }


        $template = SharedConstants::ONLINE_VIDEO_CONFIG['origins'][$type]['url_template'];
        $url = str_replace('${id}', $id, $template);


        // afegim un nombre aleatori al data-ioc-id per assegurar que no hi ha conflictes encara que es trobin 2 vídeos amb el mateix id real (el que s'envia al iframe)

        try {
            $random = rand(0, PHP_INT_MAX);
        } catch (Exception $e) {
            $random = (new DateTime())->getTimestamp() + self::$counter;
            self::$counter++; // Cal assegurar-nos que aquest nombre será diferent encara que es cridi múltiples vegades
        }


        $html = '<div data-dw-block="video" data-video-type="' . $type . '" data-video-id="' . $id . '" data-ioc-id="ioc_video_' . $id . $random . '" contenteditable="false" data-video-size="' . $size . '">' .
            '<iframe src="' . $url . '" width="' . $width . '" height="' . $height . '"></iframe>' .
            '</div>';

        $this->addRefId($html);

        return $html;


    }


}