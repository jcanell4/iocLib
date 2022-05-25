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

//    function sendCalendarDates($token){
//        $ws = new WsMoodleCalendar();
//        $ws->setToken($token);
//
//        $courseId = $this->getCourseId();
//        $oldEvents = EventMoodle::getListFromJson($ws->getEvents(array($courseId)));
//        //control d'errors
//
//        $events  = [];
//        foreach ($oldEvents->events as $item){
//            if($item->getEventType()=="course" && $item->getCourseId()==$courseId){
//                $events[] =$item;
//            }
//        }
//
//        if(count($events)>0){
//            $resp = $ws->deleteEventsFromEvents($events);
//            //control d'errors
//        }
//
//        $dates = $this->getCalendarDates();
//        $events = [];
//        foreach ($dates as $item) {
//            $events[] = [
//                "name" => $item["title"],
//                "timestart" => strptime($item["date"], "%Y-%m-%d"),
//                "courseid" => $courseId,
//                "eventtype" => "course",
//                "description" => $item['description']
//            ];
//        }
//
//        $resp = $ws->createEvents($events);
//        //control d'errors
//
//    }

    public abstract function getCourseId();

    /**
     * Llista de les dates a pujar al calendari amb el format següent:
     *  - title
     *  - date (en format yyyy-mm-dd)
     *  - description
     */
    public abstract function getCalendarDates();
}
