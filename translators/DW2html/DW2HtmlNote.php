<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlNote extends DW2HtmlInstruction {

    const NOTE = 1;
    const SIGNATURE = 2;

//    protected $newLinefound = '';

//    protected function getReplacement($position) {
//
//        $ret = parent::getReplacement($position);
//
//        if ($position === IocInstruction::CLOSE) {
//            $ret .= $this->newLinefound;
//        }
//
//        return $ret;
//    }

    public function close() {
        return '';
    }

    public function open() {

//        var_dump($this->currentToken);
//        die('open code');
        $token = $this->currentToken;
        // El contingut dintre d'aquest block no parseja, es deixa tal qual


        if (preg_match($token['pattern'], $token['raw'], $match)) {
            $value = $match[1];
        } else {
            $value = "ERROR: No s'ha trobat coincidencia amb el patró";
        }


        $pattern = "/(.*?) ?(--- \/\/.*?\/\/)/m";


        // TODO: separar en múliples comentaris:
        // <note>Comentari --- //signatura// Resposta 1 --- //signatura// Resposta 2 // Signatura//</note>

        $contents = [];
        $signatures = [];

        $notes = 0;

        if (preg_match_all($pattern, $value, $matches)) {

            $notes = count($matches[0]);

            for ($i = 0; $i < $notes; $i++) {
                $matches[self::SIGNATURE][$i]; //

                $contents[] = $matches[self::NOTE][$i];
                $signatures[] = $matches[self::SIGNATURE][$i];

                // TODO: obtenir el nom d'usuari a partir del correu de les signatures
//                $users[] = ???

            }

        }

        // TODO: a afegir en els handlers: id, data-reference i contingut del span amb el códi de referència
        $users = ['admin', 'user2(unimplemented)', 'user2(unimplemented)'];


        $value =
            '<div class="ioc-comment-block" data-ioc-comment="">
                <span class="ioc-comment ioc-comment-reference" data-reference="">* ()</span>
            
                <div data-type="ioc-comment" class="ioc-comment ioc-comment-body">
                    <div class="triangle-outer"> </div>
                    <div class="triangle-inner"> </div>
                    <button data-action="resolve" title="Elimina el comentari">
                        Resol
                    </button>
                    <div class="ioc-comment-main">
                        <b>Ref. </b>
                    </div>
            
                    <div>
                        <div class="ioc-reply-list">';

        for ($i = 0; $i < $notes; $i++) {
            $value .= '<div class="ioc-comment-reply" data-ioc-reply="" data-user="' . $users[$i] . '">
                            <div class="viewComment">
                                <span class="ioc-comment-toolbar">
                                    <span class="ioc-comment-toolbar-button" title="" data-button="edit">Editar</span>
                                    |
                                    <span class="ioc-comment-toolbar-button" title="" data-button="remove">Esborrar</span>
                                </span>
                                <span class="replyContent">' . $contents[$i] . '</span>
                                <span class="ioc-signature">' . $signatures[$i] . '</span>
                            </div>
                            <div class="editComment">
                                <textarea rows="2"></textarea>
                                <button data-action-reply="save">Desar</button>
                                <button data-action-reply="cancel">Cancel·lar</button>
                            </div>
                        </div>';
        }


        $value .= '</div>                
                    <textarea class="reply" rows="2" placeholder="Escriu un comentari..."></textarea>
                </div>
                <button data-action="reply" title="Afegir un comentari">Respon</button>
            </div>
        </div data-ioc-comment="">';


        // Estructua que s'ha de generar:
        //
//        <div id="ioc-comment-1584525589618" class="ioc-comment-block" data-ioc-comment="">
//            <span class="ioc-comment ioc-comment-reference" data-reference="101296789">* (101296789)</span>
//
//            <div data-type="ioc-comment" class="ioc-comment ioc-comment-body">
//                <div class="triangle-outer"> </div>
//                <div class="triangle-inner"> </div>
//                <button data-action="resolve" title="Elimina el comentari">
//                    Resol
//                </button>
//                <div class="ioc-comment-main">
//                    <b>Ref. 101296789</b>
//                </div>
//
//                <div>
//                    <div class="ioc-reply-list">
//                    <div class="ioc-comment-reply" data-ioc-reply="" data-user="admin">
//            <div class="viewComment">
//                <span class="ioc-comment-toolbar">
//                    <span class="ioc-comment-toolbar-button" title="" data-button="edit">Editar</span>
//                    |
//                    <span class="ioc-comment-toolbar-button" title="" data-button="remove">Esborrar</span>
//                </span>
//                <span class="replyContent">asdf<br></span>
//                <span class="ioc-signature"> --- //[[jcanell4@ioc.cats|Admin]] 2020/03/18 11:49//</span>
//            </div>
//            <div class="editComment">
//                <textarea rows="2"></textarea>
//                <button data-action-reply="save">Desar</button>
//                <button data-action-reply="cancel">Cancel·lar</button>
//            </div>
//        </div><div class="ioc-comment-reply" data-ioc-reply="" data-user="admin">
//            <div class="viewComment">
//                <span class="ioc-comment-toolbar">
//                    <span class="ioc-comment-toolbar-button" title="" data-button="edit">Editar</span>
//                    |
//                    <span class="ioc-comment-toolbar-button" title="" data-button="remove">Esborrar</span>
//                </span>
//                <span class="replyContent">dasdf</span>
//                <span class="ioc-signature"> --- //[[jcanell4@ioc.cats|Admin]] 2020/03/18 11:49//</span>
//            </div>
//            <div class="editComment">
//                <textarea rows="2"></textarea>
//                <button data-action-reply="save">Desar</button>
//                <button data-action-reply="cancel">Cancel·lar</button>
//            </div>
//        </div></div>
//                    <textarea class="reply" rows="2" placeholder="Escriu un comentari..."></textarea>
//                </div>
//                <button data-action="reply" title="Afegir un comentari">Respon</button>
//            </div>
//        </div>


        // Si l'últim caràcters és un salt de línia l'eliminem, això es necessari perque el salt de línia no
        // ha de forma part del contingut
//
//        if (substr($value, -1) == "\n") {
//            $value = substr($value, 0, strlen($value) - 1);
//        }


        // Si hi ha un llenguatge ho posem com atribut

//        $pattern = "/<.*? (.*?)>/";
//
//        $openReplacement = $this->getReplacement(self::OPEN);
//
//        if (preg_match($pattern, $token['raw'], $match)) {
//
////            var_dump(end(static::$stack));
////            die();
//
//            $lang = $match[1];
//
////            var_dump($lang);
////            die();
//
//            $openReplacement = static::AddAttributeToTag($openReplacement, 'data-dw-lang', $lang);
//        }
//
//
//        $value = $openReplacement . $value . $this->getReplacement(self::CLOSE);
////        var_dump($value);
////        die();
//
////        $this->getPreAndPost($pre, $post);
//

        return $value;
//        return $pre . $value . $post;
    }


}
