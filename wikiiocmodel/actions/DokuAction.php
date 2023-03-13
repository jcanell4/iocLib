<?php
/**
 * Description of DokuAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

abstract class DokuAction extends AbstractWikiAction{
    protected $defaultDo;
    protected $response;

    protected $noMessage = FALSE;

    private $preResponseTmp = array(); //EL format d'aquestes dades és un hashArray on la clau indica el tipus i el valor el contingut. La clau
                                       //pot ser qualsevol de les que es processaràn després com a resposta en el responseHandler. Per exemple
                                       //title, content, info, meta, etc. A més hi ha la possibilitat d'afegir contingut html a la resposta
    private $postResponseTmp= array(); //EL format d'aquestes dades és un hashArray on la clau indica el tipus i el valor el contingut. La clau
                                       //pot ser qualsevol de les que es processaràn després com a resposta en el responseHandler. Per exemple
                                       //title, content, info, meta, etc.
    private $renderer = FALSE;
    private $addContentIsAllowed=FALSE; //Per defecte no deixem afegir contingut HTML a la renderització

    /**
     * @param Array $paramsArr
     */
    public function get($paramsArr=array()){
        $this->response = array();

        $this->start($paramsArr);
        $this->run();
        $response = $this->getResponse();

        if ($this->isDenied()) {
            throw new HttpErrorCodeException('accessdenied', 403);
        }

        if(!$this->noMessage){
            $response = $this->_responseGet($response);
        }
        return $response;
    }

    private function _responseGet($response) {
        global $MSG;

        foreach ($this->preResponseTmp as $preResponseTmp) {
            if(is_string($preResponseTmp) && $this->addContentIsAllowed){
                if(!$response["before.content"])
                    $response["before.content"]="";
                $response["before.content"] .= $preResponseTmp;
            }else{
                foreach ($preResponseTmp as $key => $value ){
                    if($key==="before.content"  && $this->addContentIsAllowed){
                        if(!$response["before.content"])
                            $response["before.content"]="";
                         $response["before.content"] .= $value;
                    }else if($key==="after.content"  && $this->addContentIsAllowed){
                        if(!$response["after.content"])
                            $response["after.content"]="";
                         $response["after.content"] .= $value;
                    }else if($key==="info"){
                        if(isset($response["info"])){
                            if(is_string($value)){
                                $response["info"] = self::addInfoToInfo($response["info"], $value);
                            }else{
                                $response["info"] = self::addInfoToInfo($response["info"], self::generateInfo($value["type"], $value["message"], $value["id"], $value["duration"]));
                            }
                        }else{
                            $response["info"] = self::generateInfo($MSG['lvl'], $MSG['msg']);
                        }
                    }else{
                        if(isset($response[$key])){
                            if(is_string($response[$key])){
                                $response[$key] .= $value;
                            }else if(is_array($response[$key])){
                                $response[$key][] = $value;
                            }else{
                                $response[$key] = $value;
                            }
                        }else{
                            $response[$key] = $value;
                        }
                    }
                }
            }
        }

        foreach ($this->postResponseTmp as $postResponseTmp) {
            if(is_string($postResponseTmp) && $this->addContentIsAllowed){
                if(!$response["after.content"])
                    $response["after.content"]="";
                $response["after.content"] = $postResponseTmp;
            }elseif (is_array($postResponseTmp)){
                foreach ($postResponseTmp as $key => $value ){
                    if($key==="before.content"  && $this->addContentIsAllowed){
                        if(!$response["before.content"])
                            $response["before.content"]="";
                         $response["before.content"] .= $value;
                    }else if($key==="after.content"  && $this->addContentIsAllowed){
                        if(!$response["after.content"])
                            $response["after.content"]="";
                         $response["after.content"] .= $value;
                    }else if($key==="info"){
                        if(isset($response["info"])){
                            if(is_string($value)){
                                $response["info"] = self::addInfoToInfo($response["info"], $value);
                            }else{
                                $response["info"] = self::addInfoToInfo($response["info"], self::generateInfo($value["type"], $value["message"], $value["id"], $value["duration"]));
                            }
                        }else{
                            $response["info"] = self::generateInfo($MSG['lvl'], $MSG['msg']);
                        }
                    }else{
                        if(isset($response[$key])){
                            if(is_string($response[$key])){
                                $response[$key] .= $value;
                            }else if(is_array($response[$key])){
                                $response[$key][] = $value;
                            }else{
                                $response[$key] = $value;
                            }
                        }else{
                            $response[$key] = $value;
                        }
                    }
                }
            }
        }
        if(isset($MSG)){
            $shown = array();
            foreach($MSG as $missatge){
                $hash = md5($missatge['msg']);
                if (isset($shown[$hash]))
                    continue; // skip double messages
                if (isset($response["info"])){
                    $response["info"] = self::addInfoToInfo($response["info"], self::generateInfo($missatge['lvl'], $missatge['msg']));
                }else{
                    $response["info"] = self::generateInfo($missatge['lvl'], $missatge['msg']);
                }
                $shown[$hash] = 1;
            }
            unset($GLOBALS['MSG']);
        }

        $this->triggerEndEvents();

        return $response;
    }

    /**
     * És un mètode per sobrescriure. Per defecte no fa res, però la
     * sobrescriptura permet fer assignacions a les variables globals de la
     * wiki a partir dels valors de DokuAction#params.
     */
    protected abstract function startProcess();

    /**
     * És un mètode per sobrescriure. Per defecte no fa res, però la
     * sobrescriptura permet processar l'acció i emmagatzemar totes aquelles
     * dades intermèdies que siguin necessàries per generar la resposta final:
     * DokuAction#responseProcess.
     */
    protected abstract function runProcess();

    private function start($paramsArr){
        $this->params = $paramsArr;
	$this->startProcess();
        WikiIocInfoManager::loadInfo();
        WikiIocLangManager::load();
        $this->triggerStartEvents();
    }

    private function run() {
        if ( $this->runBeforePreprocess() ) {
            $this->runProcess();
        }
        $this->runAfterPreprocess();
    }

    private function runBeforePreprocess() {
        global $ACT;
        $content = "";

        $brun = FALSE;
        // give plugins an opportunity to process the action
        $this->ppEvt = new Doku_Event( 'ACTION_ACT_PREPROCESS', $ACT );
        ob_start();
        $brun    = ( $this->ppEvt->advise_before() );
        $content = ob_get_clean();

        if(!empty($content)){
            $this->preResponseTmp[] = $content;
        }

        return $brun;
    }

    private function runAfterPreprocess() {
        $content = "";
        ob_start();
        $this->ppEvt->advise_after();
        $content .= ob_get_clean();
        unset( $this->ppEvt );

        if(!empty($content)){
            $this->preResponseTmp[] = $content;
        }
    }

    /**
     * És un mètode per sobrescriure. La sobrescriptura permet generar la resposta a enviar al client.
     * Aquest mètode ha de retornar la resposa o bé emmagatzemar-la a l'atribut
     * DokuAction#response.
     */
    private function getResponse(){
        if($this->isRenderer()){
            $evt = new Doku_Event('TPL_ACT_RENDER', $this->params[PageKeys::KEY_DO]);
            ob_start();
            if($evt->advise_before()){
                $pre_output = ob_get_clean();
                $response = $this->responseProcess();
            }else{
                $pre_output = ob_get_clean();
            }
            ob_start();
            $evt->advise_after();
            $post_output = ob_get_clean();
            ob_start();
            trigger_event('TPL_CONTENT_DISPLAY', $post_output, 'ptln');
            $post_output = ob_get_clean();
            if(!empty($pre_output))
                $this->preResponseTmp[] = $pre_output;
            if(!empty($post_output))
                $this->postResponseTmp[] = $post_output;
        }else{
            $response = $this->responseProcess();
        }

        if (!empty($this->response) && !$response){
            $response = $this->response;
        }

        return $response;
    }

    protected function getCommonPage( $id, $title, $content=NULL ) {
        $contentData['id'] = $id;
        $contentData['title'] = $title;
        if($content){
            $contentData["content"] = $content;
        }
        return $contentData;
    }

    private function isDenied() {
	global $ACT;
	return $ACT == PageKeys::DW_ACT_DENIED;
    }

    protected function setRenderer($val){
        $this->renderer=$val;
    }

    protected function isRenderer(){
        return $this->renderer;
    }
}
