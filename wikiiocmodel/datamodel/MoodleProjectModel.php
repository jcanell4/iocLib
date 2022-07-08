<?php
/**
 * Description of MoodleProjectModel
 * @author josep
 */
if (!defined('DOKU_INC')) die();

abstract class MoodleProjectModel extends AbstractProjectModel{
    private $moodleToken= FALSE;
    
    public function init($params, $projectType=NULL, $rev=NULL, $viewConfigKey=ProjectKeys::KEY_VIEW_DEFAULTVIEW, $metaDataSubSet=Projectkeys::VAL_DEFAULTSUBSET, $actionCommand=NULL, $isOnView=FALSE) {
        parent::init($params, $projectType, $rev, $viewConfigKey, $metaDataSubSet, $actionCommand, $isOnView);
        if(isset($params["moodleToken"]) && $params["moodleToken"]){
            $this->moodleToken = $params["moodleToken"];
        }
    }
    
    public function getMoodleToken() {
        return $this->moodleToken;
    }
    
    protected function getMixDataLessons($courseId){
        $res = FALSE;
        if($courseId && $this->getMoodleToken()){
            $wsMix = new WsMixClient();
            $wsMix->setToken($this->getMoodleToken());
//            error_log("D0.1.- CourseId:-".$courseId."-");
            try{
                $res = $wsMix->getCourseLessons($courseId);
//                error_log("D0.2.- Num lessons:-". count($res)."-");
//                error_log("D0.3.- Num lessons:-". $res."-");
            }catch(WsMixException $ex){
//                error_log("E0.1.- Error: ".$ex->getMessage());
                $res = FALSE;
            }            
        }        
        return $res;
    }

    public function addExtraCalendar(&$ret) {
        $data = $this->getCurrentDataProject();
        // Comprovem si hi ha dades extre que afegir al calendari
        $dadesExtres = IocCommon::toArrayThroughArrayOrJson($data["dadesExtres"]);

        for ($i = 0; $i<count($dadesExtres); $i++) {
            $row = $dadesExtres[$i];
            if (strlen($row['parametres']) > 0 && strpos($row['parametres'], 'sendToCalendar') !== false) {

                // Separem els paràmetres
                $regex = "/\s*,\s*/";
                $parametres = preg_split($regex, $row['parametres']);

                for ($j = 0; $j < count($parametres); $j++) {

                    $pattern = "/^[\(\[]?(sendToCalendar(?::.*?)?)[\)\]]?$/m";
                    if (!preg_match($pattern, $parametres[$j], $match) ) {
                        continue;
                    }

                    $sanitized =$match[1];
                    $separadorPos = strpos($sanitized, ":");

                    if ($separadorPos !== false) {

                        $text = substr($sanitized, $separadorPos + 1);
                    } else {
                        $text = $row['nom'];
                    }

                    $ret[] = [
                        "title" => sprintf("%s - %s", $data["modulId"], $text),
                        "date" => $row['valor']
                    ];
                }
            }
        }

    }

    public abstract function getCourseId();

    /**
     * Llista de les dates a pujar al calendari amb el format següent:
     *  - title
     *  - date (en format yyyy-mm-dd)
     *  - description
     */
    public abstract function getCalendarDates();
}
