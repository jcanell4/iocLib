<?php
/**
 * Class abstract_rest_command_class
 * @author Josep Cañellas <jcanell4@ioc.cat>
 */
abstract class abstract_rest_command_class extends abstract_command_class {
    protected $supportedContentTypes;
    protected $supportedMethods;
    protected $defaultContentType = 'none';

    /**
     * El constructor defineix el content type per defecte, els content type suportats, el mètode ('GET') i els tipus
     */
    public function __construct() {
        parent::__construct();
        $this->defaultContentType    = "application/json";
        $this->supportedContentTypes = array("application/json");
        $this->supportedMethods      = array("GET");
        $this->types['method']       = self::T_STRING;
        $this->throwsEventResponse   = FALSE;
    }

    /** NO ES FA SERVIR
     * @param String[] $supportedFormats
     */
    protected function setSupportedContentTypes($supportedFormats) {
        $this->supportedContentTypes = $supportedFormats;
    }

    /** NO ES FA SERVIR
     * Estableix com a mètodes suportats els mètodes passats com argument. El valors d'aquest array poden ser GET, POST, etc.
     * @param String[] $supportedMethods
     */
    protected function setSupportedMethods($supportedMethods) {
        $this->supportedMethods = $supportedMethods;
    }

    /**
     * @return string
     */
    public function bestContentType() {
        $t = array();
        //$best = http_negotiate_content_type($this->supportedContentTypes, $t);
        return ((empty($t)) ? $this->defaultContentType : $best); // TODO[Xavi] això s'ha d'eliminar o modificar la declaració?
    }

    /**
     * Retorna cert si bestContentType (que retorna el valor de $this->defaultContentType) no es 'none' (el valor per
     * defecte).
     * @return bool true si defaultContentType es diferent a 'none'.
     */
    public function isContentTypeSupported() {
        return $this->bestContentType() !== 'none';
    }

    /**
     * Si el tipus de contingut de la petició es suportat es retorna el resultat de processar
     * la petició. No necessita mirar permissos.
     *
     * @param string        $method           mètode a través del qual s'ha rebut la petició
     * @param null|string[] $extra_url_params hash amb els paràmetres de la petició
     *
     * @return null|JSON
     * @throws Exception si es detaman un mètode no implementat.
     */
    public function dispatchRequest($method) {
        $ret = NULL;

        if($this->isContentTypeSupported()) {
            switch($method) {
                case 'GET':
                    $ret = $this->processGet();
                    break;
                case 'HEAD':
                    $ret = $this->processHead();
                    break;
                case 'POST':
                    $ret = $this->processPost();
                    break;
                case 'PUT':
                    $ret = $this->processPut();
                    break;
                case 'DELETE':
                    $ret = $this->processDelete();
                    break;
                default:
                    /* 501 (Not Implemented) for any unknown methods */
                    header('Allow: ' . implode($this->supportedMethods), TRUE, 501);
                    $this->error        = TRUE;
                    $this->errorMessage = "Error: " . $method . " does not implemented"; /*TODO internacionalitzaió (convertir missatges en variable) */
            }
        } else {
            /* 406 Not Acceptable */
            header('406 Not Acceptable');
            $this->error        = TRUE;
            $this->errorMessage = "Error: Content type is not accepted"; /*TODO internacionalitzaió (convertir missatges en variable) */
        }

        if($this->error && $this->throwsException) {
            throw new Exception($this->errorMessage);
        }
        return $ret;
    }

    /**
     * Extreu els paràmetres de la url passada com argument i els estableix com a paràmetres del objecte.
     * @param string[] $extra_url_params paràmetres per extreure
     */
    public function setParamValuesFromUrl($extra_url_params) {
        //trata los $extra_url_params como: name/value
        if (is_array($extra_url_params)) {
            for ($i=0; $i<count($extra_url_params); $i+=2) {
                if ($extra_url_params[$i] != NULL) {
                    $this->params[$extra_url_params[$i]] = $extra_url_params[$i+1];
                }
            }
        }
    }

    /**
     * Configura la capçalera amb l'error 405 (MethodNotAllowedResponse)
     */
    protected function methodNotAllowedResponse() {
        /* 405 (Method Not Allowed) */
        header('Allow: ' . implode($this->supportedMethods), TRUE, 405);
        return NULL;
    }

    public function processGet() {
        return $this->methodNotAllowedResponse();
    }

    public function processHead() {
        return $this->methodNotAllowedResponse();
    }

    public function processPost() {
        return $this->methodNotAllowedResponse();
    }

    public function processPut() {
        return $this->methodNotAllowedResponse();
    }

    public function processDelete() {
        return $this->methodNotAllowedResponse();
    }

    /**
     * Envia la petició perquè es processi segons el mètode empreat.
     */
    protected function process() {
        return $this->dispatchRequest($this->params['method']);
    }

    /**
     * @param mixed                    $response
     * @param AjaxCmdResponseGenerator $responseGenerator
     *
     * @return void
     */
    protected function getDefaultResponse($response, &$responseGenerator) {}

}