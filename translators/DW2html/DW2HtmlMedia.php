<?php
require_once "DW2HtmlParser.php";

class DW2HtmlMedia extends DW2HtmlImage {

    private static $counter = 0;

    protected $urlPattern = "/{{(vimeo|youtube|dailymotion|altamarVideos)>(.*?)\|.*}}/";

    static $vimeo = 'https://player.vimeo.com/video/@VIDEO@';
    static $youtube = 'https://www.youtube.com/embed/@VIDEO@?controls=1';
    static $dailymotion = 'https://www.dailymotion.com/embed/video/@VIDEO@';

    public function open() {


        $token = $this->currentToken;

        // Descartem el segón paràmetre, la clau no es fa servir


        // remove {{ }}
        $command = substr($token['raw'],2,-2);

        // title
        list($command, $title) = explode('|',$command);
        $title = trim($title);
        $command = trim($command);

        // get site and video
        list($type, $id) = explode('>',$command);

        // what size?
        list($id, $param) = explode('?',$id,2);
        if(preg_match('/(\d+)x(\d+)/i',$param,$m)){
            // TODO: No implementat al client
            // custom
//            $width  = $m[1];
//            $height = $m[2];
//            $size = "custom";

        }elseif(strpos($param,'small') !== false){
            // small
            $size = 'small';
            $width  = 255;
//            $height = 210; // format 4:3, obsolet
            $height = 255 / 16 * 9; // format 16:9
        }elseif(strpos($param,'large') !== false){
            // large
            $size = 'large';
            $width  = 520;
//            $height = 406; // format 4:3, obsolet
            $height = 520 / 16 * 9; // format 16:9;
        }else{
            $size = 'medium';
            // medium
            $width  = 425;
//            $height = 350; // format 4:3, obsolet
            $height = 425 / 16 * 9; // format 16:9;
        }

//        return array($site, $url, $title, $width, $height);


//        if (preg_match($token['pattern'], $token['raw'], $match)) {
////            $type = $match[1];
//            $id = $match[1];
//        }


        // TODO[Xavi] Cal incloure les etiquetes de altamarVideos
        switch ($type) {

            case 'vimeo':
                $url = self::$vimeo;
                break;

            case 'youtube':
                $url = self::$youtube;
                break;

            case 'dailymotion':
                $url = self::$dailymotion;
                break;

            default:
                $url = 'undefined';
        }

        $url = str_replace('@VIDEO@', $id, $url);



        // afegim un nombre aleatori al data-ioc-id per assegurar que no hi ha conflictes encara que es trobin 2 vídeos amb el mateix id real (el que s'envia al iframe)

        try {
            $random = rand(0, PHP_INT_MAX);
        } catch (Exception $e) {
            $random = (new DateTime())->getTimestamp() + self::$counter;
            self::$counter++; // Cal assegurar-nos que aquest nombre será diferent encara que es cridi múltiples vegades
        }


        $html = '<div data-dw-block="video" data-video-type="' . $type . '" data-video-id="' . $id . '" data-ioc-id="ioc_video_' . $id . $random . '" contenteditable="false" data-video-title="'. $title .'" data-video-size="'. $size .'">' .
            '<iframe src="' . $url . '" width="' . $width .'" height="' . $height .' title="' . $title . '"></iframe>' .
            '</div>';

        return $html;


    }


}