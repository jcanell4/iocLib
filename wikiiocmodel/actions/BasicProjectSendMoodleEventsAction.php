<?php
/**
 * Description of BasicFtpSendAction
 */
if (!defined("DOKU_INC")) die();

class BasicProjectSendMoodleEventsAction extends ProjectAction{

    protected function responseProcess() {
        
        $id = $this->params[ProjectKeys::KEY_ID];

        $dates = $this->projectModel->getCalendarDates();
        // Afegim les dades extres marcades com a sendToCalendar
        $this->projectModel->addExtraCalendar($dates);

        if(isset($this->params["moodleToken"])){
            $events = [];
            $courseId = $this->projectModel->getCourseId();
            if($courseId){
                try{
                    //$dates = $this->projectModel->getCalendarDates();
                    $ws = new WsMoodleCalendar();
                    //$ws->init(parametres de configuració);
                    $ws->setToken($this->params["moodleToken"]);
                    $ws->deleteAllCourseEvents($courseId);
                    foreach ($dates as $date){
                        $events[] = EventMoodle::newInstanceFromAssociative(array(
                            "name" => $date['title'],
                            "timestart" => strtotime(str_replace("/", "-", $date["date"])),
                            "eventtype" => "course",
                            "description" => isset($date['description'])?$date['description']:" "
                        ));
                    }
                    $ws->createEventsForCourseId($courseId, $events);
                    $response['info'] = self::generateInfo("success", WikiIocLangManager::getLang('MOODLE_EVENTS_HAS_BEEN_UPDATED'), $id);
                }catch(WikiIocModelException $e){                    
                    $response['info'] = self::generateInfo("error", $e.message, $id);
                    $response['info'] = self::addInfoToInfo($response['info'], 
                                        self::generateInfo("info", WikiIocLangManager::getLang('MOODLE_EVENTS_NOT_UPDATED'), $id));            
                }
            }else{
                $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('MOODLE_EVENTS_NOT_UPDATED'), $id);
            }
        }else{
            $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('USER_IS_NOT_MOODLE_USER'), $id);
            $response['info'] = self::addInfoToInfo($response['info'], 
                                        self::generateInfo("info", WikiIocLangManager::getLang('MOODLE_EVENTS_NOT_UPDATED'), $id));            
        }
        return $response;
    }
}
