<?php
require_once "Html2DWParser.php";

class Html2DWBox extends Html2DWMarkup {

    protected function getContent($token) {
        preg_match_all($token['pattern'], $token['raw'], $matches);


        // El primer grup és el tipus
        // El segon grup és la informació
        // El tercer grup és el contingut que s'ha de parsejar


        $data = $this->getBoxInfo($matches[2][0]);

        $type = $this->extractVarName($token['raw'], 'data-dw-type', false);

        if ($type !== null) {
            $data['type'] = $type;
        }


        $pre = '::' . $matches[1][0] . ':' . $data['id'] . "\n";

        foreach ($data as $key => $value) {
            if ($key === 'id') {
                continue;
            }
            $pre .= '  :' . $key . ':' . $value . "\n";
        }

        $post = ':::';


        ++static::$instancesCounter;

        // TODO: incloure el parse de la taula
        $class = static::$parserClass;
        $isInnerPrevious = $class::isInner();
        $class::setInner(true);

        $content = $class::getValue($matches[3][0]);

        $class::setInner($isInnerPrevious);

        --static::$instancesCounter;

        return $pre . $content . $post;
    }


    protected function getBoxInfo($text) {

        $pattern = "/<strong.*?data-dw-field=\"(.*?)\".*?<\/strong> (.*?)<\/?br>/ms";

        preg_match_all($pattern, $text, $matches);

        $data = [];

        for ($i = 0; $i<count($matches[0]); $i++) {
            $data[$matches[1][$i]] = $matches[2][$i];
        }

        return $data;
    }
}
