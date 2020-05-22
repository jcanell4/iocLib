<?php
/**
 * CommonUpgrader: Colección de funciones de transformación, para los datos de proyecto,
 *                 entre estructuras de distintas versiones
 * @culpable rafael
 */
if (!defined("DOKU_INC")) die();

class CommonUpgrader {
    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    //                    Actualización de nombres de campo del formulario
    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    /**
     * Modifica el nombre de un campo perteneciente a una tabla (multirregistro)
     * (modifica el nombre de una clave del array de datos del proyecto)
     * @param array $data : array de datos del proyecto (del archivo mdprojects/.../.../*.mdpr)
     * @param array $u0 : ruta completa (del array multinivel) de la clave original (versión 0)
     * @param array $u1 : ruta completa (del array multinivel) de la nueva clave (versión 1)
     * @return array de datos con los nombres de las claves $u0 cambiados a $u1
     */
    public function changeFieldNameInArray($data, $u0, $u1) {
        $data = $this->changeFieldName($data, $u0[0], $u1[0]);
        for ($i=0; $i<count($u0)-1; $i++) {
            $data[$u1[$i]] = $this->changeFieldNameInMultiRow($data[$u1[$i]], $u0[$i+1], $u1[$i+1]);
        }
        return $data ;
    }

    /**
     * Modifica el nombre de una clave de un array y retorna el array con la clave renombrada
     * @param array $data : array de datos
     * @param string $u0 : nombre de clave original
     * @param string $u1 : nuevo nombre de clave
     * @return array de datos con el nombre de la clave $u0 cambiado a $u1
     */
    public function changeFieldName($data, $u0, $u1) {
        $dataChanged = array();
        foreach ($data as $key => $value) {
            if ($key === $u0) {
                $dataChanged[$u1] = $value;
            }else {
                $dataChanged[$key] = $value;
            }
        }
        return $dataChanged;
    }

    public function changeFieldNameInMultiRow($rama, $u0, $u1) {
        $rama = (is_array($rama)) ? $rama : json_decode($rama, TRUE);
        if (is_array($rama[0])) { //es un conjunto de filas de una tabla
            for ($j=0; $j<count($rama); $j++) {
                $ret = $this->changeFieldName($rama[$j], $u0, $u1);
                $rama[$j] = json_encode($ret);
            }
        }else {
            $rama = $this->changeFieldName($rama, $u0, $u1);
        }
        return json_encode($rama);
    }

    /**
     * Añade un nuevo campo (y su valor)
     * @param array $data : array de datos original
     * @param string $newkey : clave a añadir
     * @param mixed $newvalue : valor de la clave añadida
     * @return array de datos con la nueva clave añadida
     */
    public function addNewField($data, $newkey, $newvalue) {
        $data[$newkey] = $newvalue;
        return $data;
    }

    /**
     * Añade un nuevo campo (y su valor) a todos los registros de un multirregistro
     * @param array $data : array de datos original
     * @param string $row : clave del multirregistro
     * @param string $newkey : clave a añadir
     * @param mixed $newvalue : valor de la clave añadida
     * @return array de datos con la nueva clave añadida
     */
    public function addFieldInMultiRow($data, $row, $newkey, $newvalue) {
        $rama = (is_array($data[$row])) ? $data[$row] : json_decode($data[$row], TRUE);
        if($rama==NULL){
            $rama=[];
        }
        foreach ($rama as $k => $v) {
            $rama[$k][$newkey] = $newvalue;
        }
        $data[$row] = $rama;
        return $data;
    }

    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    //                              Actualización de plantillas
    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    /**
     * Sean $plant_0 i $plant_1, plantillas diferentes de texto donde se encuantran
     * insertadas etiquetas TODO, en la misma cantida i conetenido, en ambas plantillas.
     * La función extrae cada uno de los bloques de texto contenidos entre las
     * etiquetas TODO i entre el inicio i la primera etiqueta TODO así como la
     * últilma etiqueta i el final de la plantilla. Una vez extraidos los boques
     * se buscan en el documento del usuario ($doc), cada uno de los bloques de la plantilla %plant_0
     * i se sustituyen por cada uno los bloques correspondientes (segun el orden encontrado) de $plant_1. Esto
     * mantiene intacto, en el documento del usuario ($doc), el texto situado entre
     * los bloques definidos en las plantillas.
     *
     * @param string $plant_0 : texto del documento que sirve para la comparación base (plantilla versión anterior)
     * @param string $plant_1 : texto de la plantilla final (plantilla nueva versión)
     * @param string $doc : texto del documento a modificar
     */
    public function updateFromTemplatesWithTodoTags($plant_0, $plant_1, $doc) {
        $offset_0 = 0;
        $offset_1 = 0;
        $offset_doc = 0;
        $errmsg = "Error en l'actualització. Canvis incontrolats al document de l'usuari.";

        while (true) {
            //busca el tag ##TODO en la plantilla $plant_0, a partir de la última aparición
            if (preg_match("/\[##TODO:.*##\]/m", $plant_0, $match, PREG_OFFSET_CAPTURE, $offset_0) === 1) {
                $bloque_0 = substr($plant_0, $offset_0, $match[0][1]-$offset_0);
                $bloque_TODO = $match[0][0];
                $offset_0 = $match[0][1] + strlen($match[0][0]);

                //busca el tag ##TODO en la plantilla $plant_1, a partir de la última aparición
                preg_match("/\[##TODO:.*##\]/m", $plant_1, $match, PREG_OFFSET_CAPTURE, $offset_1);
                $bloque_1 = substr($plant_1, $offset_1, $match[0][1]-$offset_1);
                $offset_1 = $match[0][1] + strlen($match[0][0]);

                if ($bloque_0 === "") continue;

                //busca el $bloque_0 en $doc y, si lo encuentra, lo sustituye por $bloque_1
                $bloque_0 = "/".preg_quote($bloque_0,"/")."/m";
                if (preg_match($bloque_0, $doc, $match) === 1) {
                    $doc = preg_replace($bloque_0, $bloque_1, $doc);
                    if (preg_match("/".preg_quote($bloque_TODO,"/")."/m", $doc, $match, PREG_OFFSET_CAPTURE, $offset_doc) === 1) {
                        $offset_doc = $match[0][1] + strlen($match[0][0]);
                    }else {
                        throw new Exception($errmsg);
                    }
                }else {
                    if (preg_match("/".preg_quote($bloque_TODO,"/")."/m", $doc, $match, PREG_OFFSET_CAPTURE, $offset_doc) === 1) {
                        $doc = substr($doc, 0, $offset_doc).$bloque_1.substr($doc, $match[0][1]);
                        //$doc = preg_replace("/".preg_quote($match[0][0],"/")."/m", $bloque_1, $doc);
                        $offset_doc += strlen($bloque_1) + strlen($match[0][0]);
                    }else {
                        throw new Exception($errmsg);
                    }
                }
            }else {
                //Tratamiento del resto del documento después del último ##TODO
                $resto_0 = "/".preg_quote(substr($plant_0, $offset_0),"/")."/m";
                $resto_1 = substr($plant_1, $offset_1);
                if (preg_match($resto_0, $doc, $match) === 1) {
                    $doc = preg_replace($resto_0, $resto_1, $doc);
                }else {
                    if (preg_match("/".preg_quote($bloque_TODO,"/")."/m", $doc, $match, PREG_OFFSET_CAPTURE, $offset_doc) === 1) {
                        $doc = substr($doc, 0, $match[0][1] + strlen($match[0][0])) . $resto_1;
                    }else {
                        throw new Exception($errmsg);
                    }
                }
                break;
            }
        }
        return $doc;
    }

//[JOSEP]: Alerta! això només substitueix el paragrag anterior i posterior al TODO. Fa falta? Si no fa falta, eliminar!
//    /**
//     * Reemplaza en el documeto de usuario ($doc_1) el contenido que está fuera de los
//     * tags ##TODO, contenido tomado de la plantilla ($doc_0)
//     * @param string $doc_0 : texto del documento en el que se busca (plantilla)
//     * @param string $doc_1 : texto del documento a modificar
//     */
//    public function updateTemplateInsertTags_2($doc_0, $doc_1) {
//        $ret = "";
//        $offset_0 = 0;
//        $offset_1 = 0;
//
//        while (true) {
//            //busca el tag ##TODO en la plantilla ($doc_0), a partir de la última aparición
//            if (preg_match("/\n.*\[##TODO:.*##\].*\n/m", $doc_0, $match, PREG_OFFSET_CAPTURE, $offset_0) === 1) {
//                $bloque = substr($doc_0, $offset_0, $match[0][1]);
//                $offset_0 = $match[0][1] + strlen($match[0][0]);
//                //busca el tag ##TODO en $doc_1, a partir de la última aparición
//                if (preg_match("/\n.*\[##TODO:.*##\].*\n/m", $doc_1, $match, PREG_OFFSET_CAPTURE, $offset_1) === 1) {
//                    $ret .= $bloque . $match[0][0];
//                    $offset_1 = $match[0][1] + strpos($match[0][0], "[##TODO") + 7;
//                }
//            }else {
//                break;
//            }
//        }
//        return $ret;
//    }

// [JOSEP]: No acabo d'entrendre per a què serveix aquesta funció. No queda clar què
// és tag_ini, tag_fin, ni la seva relació amb TODO (literal)
//    /**
//     * Inserta en el documeto de usuario ($doc1) los tags ($tag_ini, $tag_fin) tomando como referencia
//     * la disposición de esos tags en la plantilla ($doc0)
//     * @param string $doc0 : texto del documento en el que se busca (plantilla)
//     * @param string $doc1 : texto del documento a modificar
//     * @param string $tag_ini : tag de inicio a buscar
//     * @param string $tag_fin : tag de finalización a buscar
//     */
//    public function updateTemplateInsertTags_1($doc0, $doc1, $tag_ini, $tag_fin) {
//        $ret = "";
//        $nl = "\n";
//        $offset = 0;
//        //desmenuza la plantilla ($doc0) en bloques de tags
//        $bloques = explode($tag_fin, $doc0);
//
//        foreach ($bloques as $bloque) {
//            $abloq = explode($tag_ini, $bloque);
//            $bloque = ($abloq[1]) ? $abloq[1] : $abloq[0];
//            $ret .= $tag_ini.$bloque.$nl.$tag_fin;
//            //busca el tag ##TODO en el documento del usuario ($doc1), a partir de la última aparición
//            if (preg_match("/\n.*\[##TODO:.*##\].*\n/m", $doc1, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
//                $offset = $match[0][1] + strpos($match[0][0], "[##TODO") + 7;
//                $ret .= $match[0][0];
//            }
//        }
//        return $ret;
//    }

    /**
     * Busca en el documento origen un trozo de texto mediante una expresión regular y lo guarda en una variable,
     * a continuación hace una búsqueda (con regex) y sustitución (con texto guardado) en el documento destino
     * @param string $doc0 : texto del documento en el que se busca (plantilla)
     * @param string $doc1 : texto del documento a modificar
     * @param array $aTokens : matriz de expresiones regulares [regexp_search] que establecen los fragmentos de texto a buscar
     */
    public function updateTemplateBySubstitute($doc0, $doc1, $aTokens) {
        foreach ($aTokens as $tok) {
            $t0 = "/$tok/m";
            if (preg_match($t0, $doc0, $stmp0) === 1) {
                $stmp = addcslashes($stmp0[0], '\\');
                $doc1 = preg_replace($t0, $stmp, $doc1);
            }
        }
        return $doc1;
    }

    /**
     * Reemplaza en el documento los fragmentos de texto que cumplen con cada una de las expresiones regulares
     * @param string $doc : texto del documento a modificar
     * @param array $aTokens : matriz [regexp_search, text_substitute, flags] que establecen los fragmentos de texto a
     * reemplazar y flags a utilizar
     */
    public function updateTemplateByReplace($doc, $aTokens) {
        foreach ($aTokens as $tok) {
            $extraFlags = isset($tok[2]) ? $tok[2] : '';
            $pattern = "/$tok[0]/m" . $extraFlags;
            $doc = preg_replace($pattern , $tok[1], $doc);
        }
        return $doc;
    }

    /**
     * Mueve un trozo de texto de una posición a otra del documento
     * @param string $doc : texto del documento a modificar
     * @param array $aTokens : matriz de elementos [regexp0, (expresión regular que determina el texto que se desea mover)
     *                                              regexp1, (expresión regular que determina el lugar al que hay que mover el texto)
     *                                              pos, (posición el la que se inserta el texto: 0:antes, 1:después de regexp1)
     *                                              modif (optativo. modificadores de PCRE: m:multiline i:ignorecase)]
     */
    public function updateTemplateByMove($doc, $aTokens) {
        foreach ($aTokens as $tok) {
            $m = ($tok['modif']) ? $tok['modif'] : "";
            $t0 = "/".$tok['regexp0']."/$m";
            if (preg_match($t0, $doc, $stmp0) === 1) {
                $t1 = "/".$tok['regexp1']."/$m";
                if (preg_match($t1, $doc, $stmp1) === 1) {
                    $doc = preg_replace($t0, "", $doc);  //Delete
                    $s = ($tok['pos']===0) ? $stmp1[0].$stmp0[0] : $stmp0[0].$stmp1[0];
                    $doc = preg_replace($t1, $s, $doc);   //Insert
                }
            }
        }
        return $doc;
    }

    /**
     * Inserta en el documento trozos de texto en las posiciones indicadas por cada una de las expresiones regulares
     * @param string $doc : texto del documento a modificar
     * @param array $aTokens : matriz de elementos [regexp, (expresión regular para buscar el lugar en el que se insertará el nuevo texto)
     *                                              text, (texto a insertar)
     *                                              pos, (posición el la que se inserta el texto: 0:antes, 1:después)
     *                                              modif (optativo. modificadores de PCRE: m:multiline i:ignorecase)]
     *                         que indican el lugar en el que hay que insertar un nuevo texto
     */
    public function updateTemplateByInsert($doc, $aTokens) {
        $ret = $doc;
        foreach ($aTokens as $tok) {
            $m = ($tok['modif']) ? $tok['modif'] : "";
            $t = "/".$tok['regexp']."/$m";
            if (preg_match($t, $ret, $stmp) === 1) {
                $s = ($tok['pos']===0) ? $tok['text'].$stmp[0] : $stmp[0].$tok['text'];
                $ret = preg_replace($t, $s, $ret);
            }
        }
        return $ret;
    }

    /**
     * Elimina del documento los fragmentos de texto que cumplen con cada una de las expresiones regulares
     * @param string $doc : texto del documento a modificar
     * @param array $aTokens : matriz de expresiones regulares que establecen los fragmentos de texto a eliminar
     */
    public function updateTemplateByDelete($doc, $aTokens) {
        foreach ($aTokens as $tok) {
            $doc = preg_replace("/$tok/m", "", $doc);
        }
        return $doc;
    }

    /**
     * Aplica una nueva plantilla a un documento que fue creado con una plantilla antigua
     * @param string $t0 : texto de la plantilla original
     * @param string $t1 : texto de la nueva plantilla
     * @param string $doc : texto del documento de usuario (basado en la plantilla orginal)
     * @param string $token0 : expresión regular mediante la cual se dividirán la plantilla original y el documento
     * @param array $aTokens : matriz de expresiones regulares mediante las cuales se dividirá la nueva plantilla
     */
    public function updateTemplateReplacingTokens($t0, $t1, $doc, $token0, $aTokens) {
        //dividimos en fragmentos la plantilla original mediante la expresión de $token0
        $st0 = preg_split("/($token0)/", $t0, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (count($st0) !== count($aTokens)) {
            throw new Exception("El nombre d'elements de la matriu d'expressions regulars és incorrecte.");
        }

        //convierte cada elemento, generado por la expresión regular en la plantilla original, en un patrón de búsqueda
        for ($i=0; $i<count($st0); $i++) {
            $st0[$i] = "/".preg_quote($st0[$i], '/')."/";
        }

        //construye un array de strings de sustitución buscando expresiones regulares en la nueva plantilla
        foreach ($aTokens as $tok) {
            if (preg_match($tok, $t1, $stmp) === 1)
                $st1[] = $stmp[0];
        }
        if (count($st0) !== count($st1)) {
            throw new Exception("No hi ha prou correspondències a la llista d'expressions regulars.");
        }

        $ret = preg_replace($st0, $st1, $doc);
        return $ret;
    }

    /**
     * Aplica los cambios especificados en la nueva plantilla a un documento
     * NOTA: los 2 documentos deben tener el mismo número de fragmentos 'protected'
     *       Se equiparan los fragmentos por su número de orden de aparición, independientemente de su contenido
     * @param string $tpl : texto de la nueva plantilla
     * @param string $doc : texto del documento de usuario
     * @param string $token : expresión regular mediante la cual se dividirán la plantilla y el documento
     * @return string transformado
     */
    public function updateDocFromTemplateUsingProtectecTags($tpl, $doc, $token=NULL) {
        if (!$token) {
            $token="/:###.*?###:/ims";
        }
        //Recorremos todo el documento de usuario buscando bloques definidos en el $token (por defecto, bloques 'protected')
        //que serán sustituidos por bloques $token de la plantilla
        $offset = 0;
        while (preg_match($token, $doc, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $match_doc[] = "/".preg_quote($match[0][0], '/')."/ims";
            $offset = $match[0][1] + strlen($match[0][0]);
        }
        //Buscamos bloques 'protected' en la plantilla
        $offset = 0;
        while (preg_match($token, $tpl, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $match_tpl[] = $match[0][0];
            $offset = $match[0][1] + strlen($match[0][0]);
        }

        $ret = preg_replace($match_doc, $match_tpl, $doc);
        return $ret;
    }

    //[JOSEP]: Diria que aquesta funció fa quelcom semblant a updateFromTemplatesWithTodoTags. Revisar!
    /**
     * Aplica una nueva plantilla a un documento creado con una plantilla antigua
     * NOTA: las 2 plantillas y el documento deben tener el mismo número de fragmentos [##TODO
     *       Se equiparan los fragmentos por su número de orden de aparición, independientemente de su contenido
     * @param string $t0 : texto de la plantilla original
     * @param string $t1 : texto de la nueva plantilla
     * @param string $doc : texto del documento de usuario (basado en la plantilla orginal)
     * @param string $token : expresión regular mediante la cual se dividirán las plantillas y el documento
     * @return string transformado
     */
    public function updateFromOriginalTemplateToNewTemplateWithTodoTags($t0, $t1, $doc, $token=NULL) {
        if (!$token) $token = "/\[##TODO.*##\]/m";
        $st0 = preg_split("$token", $t0);
        $st1 = preg_split("$token", $t1);

        for ($i=0; $i<count($st0); $i++) {
            $st0[$i] = "/".preg_quote($st0[$i], '/')."/";
        }

        $ret = preg_replace($st0, $st1, $doc);
        return $ret;
    }

    /**
     * Aplica una nueva plantilla, con token numerado, a un documento creado con una plantilla antigua
     * NOTA: las 2 plantillas pueden tener distinto número de fragmentos [##TODO_n:
     *       Se equiparan los frangmentos por su número de [##TODO_n:
     * @param string $t0 : texto de la plantilla original
     * @param string $t1 : texto de la nueva plantilla
     * @param string $doc : texto del documento de usuario (basado en la plantilla orginal)
     * @return string transformado
     */
    public function updateFromOriginalTemplateToNewTemplateWithNumberedTodoTags($t0, $t1, $doc) {

//        $ret = $this->updateFromOriginalTemplateToNewTemplateWithNumberedTodoTags(_1($t0, $t1, $doc);
//        $ret = $this->updateFromOriginalTemplateToNewTemplateWithNumberedTodoTags(_2($t0, $t1, $doc);
        $ret = $this->updateFromOriginalTemplateToNewTemplateWithNumberedTodoTags_3($t0, $t1, $doc);

        return $ret;
    }

    // Este modelo reemplaza los tokens en el mismo orden en que los encuentra
    private function updateFromOriginalTemplateToNewTemplateWithNumberedTodoTags_1($t0, $t1, $doc) {
        $st0 = preg_split('/(\[##TODO.*##\])/', $t0, -1, PREG_SPLIT_DELIM_CAPTURE);

        //busca los tokens del template 0 en el template 1 y los marca con [##TTODO
        for ($i=0; $i<count($st0); $i++) {
            preg_match("/^\[##TODO_(\d+):/", $st0[$i], $p);
            if ($p[0]) {
                $t1 = preg_replace("/\\{$p[0]}/", "[##TTODO_{$p[1]}:", $t1);
            }
            //aprovecha el bucle para convertir cada elemento en un patrón de búsqueda
            $st0[$i] = "/".preg_quote($st0[$i], '/')."/";
        }
        $st1 = preg_split("/(\[##TTODO_.*##\])/", $t1, -1, PREG_SPLIT_DELIM_CAPTURE);

        $ret = preg_replace($st0, $st1, $doc);
        $ret = preg_replace("/\[##TTODO_(\d+):/", "[##TODO_$1:", $ret);
        return $ret;
    }

    // Este modelo reemplaza los tokens según su número token
    private function updateFromOriginalTemplateToNewTemplateWithNumberedTodoTags_2($t0, $t1, $doc) {
        $st0 = preg_split('/(\[##TODO.*##\])/', $t0, -1, PREG_SPLIT_DELIM_CAPTURE);

        //busca los tokens del template 0 en el template 1 y los marca con [##TTODO
        for ($i=0; $i<count($st0); $i++) {
            preg_match("/^\[##TODO_(\d+):/", $st0[$i], $p);
            if ($p[0]) {
                $t1 = preg_replace("/\\{$p[0]}/", "[##TTODO_{$p[1]}:", $t1);
            }
        }
        $st1 = preg_split("/(\[##TTODO_.*##\])/", $t1, -1, PREG_SPLIT_DELIM_CAPTURE);

        //Reemplaza en el $doc los tokens numerados correspondientes al template 0
        //por los tokens numerados equivalentes del template 1
        for ($i=0; $i<count($st0); $i++) {
            preg_match("/^\[##TODO_(\d+):/", $st0[$i], $p);
            $st0[$i] = "/".preg_quote($st0[$i], '/')."/";  //convierte el elemento en patrón de búsqueda
            if ($p[0]) {
                for ($j=0; $j<count($st1); $j++) {
                    preg_match("/^\[##TTODO_{$p[1]}:.*##]/", $st1[$j], $p1);
                    if ($p1[0]) {
                        $doc = preg_replace($st0[$i], $p1[0], $doc);
                        break;
                    }
                }
            }else {
                $doc = preg_replace($st0[$i], $st1[$i], $doc);
            }
        }
        $ret = preg_replace("/\[##TTODO_(\d+):/", "[##TODO_$1:", $doc);
        return $ret;
    }

    // Este modelo reemplaza los tokens, emparejados con su predecesor, según su número token
    private function updateFromOriginalTemplateToNewTemplateWithNumberedTodoTags_3($t0, $t1, $doc) {
        $st0 = preg_split('/(\[##TODO.*##\])/', $t0, -1, PREG_SPLIT_DELIM_CAPTURE);

        //busca los tokens del template 0 en el template 1 y los marca con [##TTODO
        for ($i=0; $i<count($st0); $i++) {
            preg_match("/^\[##TODO_(\d+):/", $st0[$i], $p);
            if ($p[0]) {
                $t1 = preg_replace("/\\{$p[0]}/", "[##TTODO_{$p[1]}:", $t1);
            }
        }
        $st1 = preg_split("/(\[##TTODO_.*##\])/", $t1, -1, PREG_SPLIT_DELIM_CAPTURE);

        //Reemplaza en el $doc los tokens numerados, emparejados con su predecesor,
        //correspondientes al template 0, por los tokens equivalentes del template 1
        for ($i=0; $i<count($st0); $i++) {
            preg_match("/^\[##TODO_(\d+):/", $st0[$i], $p);
            $st0[$i] = "/".preg_quote($st0[$i], '/')."/";  //convierte el elemento en patrón de búsqueda
            if ($p[0]) {
                for ($j=0; $j<count($st1); $j++) {
                    preg_match("/^\[##TTODO_{$p[1]}:.*##]/", $st1[$j], $p1);
                    if ($p1[0]) {
                        $doc = preg_replace($st0[$i-1], $st1[$j-1], $doc);
                        $doc = preg_replace($st0[$i], $p1[0], $doc);
                        break;
                    }
                }
            }
        }
        $ret = preg_replace("/\[##TTODO_(\d+):/", "[##TODO_$1:", $doc);
        return $ret;
    }

    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    //                                  PROVES
    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    private function bajaSube($rama, $u0, $i) {
        $dato = json_decode($rama, TRUE);
        if ($i < count($u0)-1) {
            $dato[$u0[$i]] = $this->bajaSube($dato[$u0[$i]], $u0, $i+1);
            $ret = json_encode($dato);
        }else {
            $ret = json_encode($dato);
        }
        return $ret;
    }
    /**
     * Lee el contenido de una rama de un array
     * @param array $rama : array de datos
     * @param array $u0 : ruta completa (del array multinivel) de la clave a leer
     * @param integer $i : nivel
     */
    private function readArrayBranch(&$rama, $u0, $u1, $i) {
        $rama = (is_array($rama)) ? $rama : json_decode($rama, TRUE);
        if (is_array($rama[0])) { //es un conjunto de filas de una tabla
            $rama = $rama[0];
        }
        $tmp = $rama[$u0[$i]];
        if ($i < count($u0) - 1) {
            $ret = $this->readArrayBranch($tmp, $u0, $u1, $i+1);
        }else {
            unset($rama[$u0[$i]]);
            $rama[$u1[$i]] = $tmp;
            $ret = json_encode($rama);
         }
        return $ret;
    }

    /**
     * Lee el contenido de una rama de un array
     * @param array $rama : array de datos
     * @param array $u0 : ruta completa (del array multinivel) de la clave a leer
     * @param integer $i : nivel
     */
    private function readArrayBranchMultiRow(&$rama, $u0, $u1, $i) {
        $rama = (is_array($rama)) ? $rama : json_decode($rama, TRUE);
        if (is_array($rama[0])) { //es un conjunto de filas de una tabla
            for ($j=0; $j<count($rama); $j++) {
                $ret = $this->readArrayBranchMultiRow($rama[$j], $u0, $u1, $i);
            }
        }else {
            $tmp = $rama[$u0[$i]];
            unset($rama[$u0[$i]]);
            $rama[$u1[$i]] = $tmp;
            if ($i < count($u0)-1) {
                $ret = $this->readArrayBranchMultiRow($tmp, $u0, $u1, $i+1);
            }else {
                $ret = $rama;
            }
        }
        return $ret;
    }

    //Transforma el objeto en un array puro
    public function JsonToArray($obj) {
        $arr = array();
        foreach ($obj as $k => $v) {
            $a = json_decode($v, TRUE);
            $arr[$k] = ($a) ? $a : $v;
        }
        return $arr;
    }

    public function buildArrayFromStringTokenized(&$data, $path) {
        $temp = &$data;
        foreach(explode(":", $path) as $key) {
            $temp = &$temp[$key];
        }
        $temp = "";
    }

    //Obtiene el valor de una subclave del array pasada como una ruta: un string partido en tokens
    public function getValueArrayFromIndexString($data, $path) {
        $temp = $data;
        foreach(explode(":", $path) as $ndx) {
            $temp = isset($temp[$ndx]) ? $temp[$ndx] : null;
        }
        return $temp;
    }

    //Obtiene un array (de 1 dimensión) en el que el conjunto de sus claves especifican, en orden inverso,
    //la localización de la clave buscada
    public function getKeyPathArray($arr, $lookup) {
        if (array_key_exists($lookup, $arr)) {
            return array($lookup);
        }else {
            foreach ($arr as $key => $subarr){
                if (is_array($subarr)) {
                    $ret = $this->getKeyPathArray($subarr, $lookup);
                    if ($ret) {
                        $ret[] = $key;
                        return $ret;
                    }
                }
            }
        }
        return null;
    }

}
