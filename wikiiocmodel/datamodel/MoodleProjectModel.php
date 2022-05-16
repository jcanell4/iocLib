<?php
/**
 * Description of MoodleProjectModel
 * @author josep
 */
if (!defined('DOKU_INC')) die();

abstract class MoodleProjectModel extends AbstractProjectModel{

    function sendCalendarDates($token){
        $ws = new WsMoodleCalendar();
        $ws->setToken($token);

        $courseId = $this->getCourseId();
        $oldEvents = EventMoodle::getListFromJson($ws->getEvents(array($courseId)));
        //control d'errors

        $events  = [];
        foreach ($oldEvents->events as $item){
            if($item->getEventType()=="course" && $item->getCourseId()==$courseId){
                $events[] =$item;
            }
        }

        if(count($events)>0){
            $resp = $ws->deleteEventsFromEvents($events);
            //control d'errors
        }

        $dates = $this->getCalendarDates();

        // Afegim les dades extres marcades com a sendToCalendar
        $data = $this->getCurrentDataProject();
        $this->addExtraCalendar($dates, $data);

        $events = [];
        foreach ($dates as $item) {
            $events[] = [
                "name" => $item["title"],
                "timestart" => strptime($item["date"], "%Y-%m-%d"),
                "courseid" => $courseId,
                "eventtype" => "course",
                "description" => $item['description']
            ];
        }

        $resp = $ws->createEvents($events);
        //control d'errors

    }

    public function addExtraCalendar(&$ret) {
        $data = $this->getCurrentDataProject();
        // Comprovem si hi ha dades extre que afegir al calendari
        if (!is_string($data["dadesExtres"]) || strlen($data["dadesExtres"])===0) {
            return;
        }

        $dadesExtres = json_decode($data["dadesExtres"], true);

        for ($i = 0; $i<count($dadesExtres); $i++) {
            $row = $dadesExtres[$i];
            if (strlen($row['parametres']) > 0 && strpos($row['parametres'], 'sendToCalendar') !== false) {

                // Separem els paràmetres
                $regex = "/\s*,\s*/";
                $parametres = preg_split($regex, $row['parametres']);

                for ($j = 0; $j < count($parametres); $j++) {
                    if (!(strpos($parametres[$j], "sendToCalendar") === 0)) {
                        continue;
                    }

                    $separadorPos = strpos($parametres[$j], ":");

                    if ($separadorPos !== false) {
                        $text = substr($parametres[$j], $separadorPos + 1);
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
