<?php
require_once "DW2HtmlParser.php";

class DW2HtmlMedia extends DW2HtmlImage {


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
            // custom
            $width  = $m[1];
            $height = $m[2];
        }elseif(strpos($param,'small') !== false){
            // small
            $width  = 255;
            $height = 210;
        }elseif(strpos($param,'large') !== false){
            // large
            $width  = 520;
            $height = 406;
        }else{
            // medium
            $width  = 425;
            $height = 350;
        }

//        return array($site, $url, $title, $width, $height);


        if (preg_match($token['pattern'], $token['raw'], $match)) {
//            $type = $match[1];
            $id = $match[1];
        }


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



        $html = '<div data-dw-block="video" data-video-type="' . $type . '" data-video-id="' . $id . '" data-ioc-id="ioc_video_' . $id . '" contenteditable="false">' .
            '<iframe src="' . $url . '" width="' . $width .'" height="' . $height .'"></iframe>' .
            '</div>';

        return $html;


    }


}