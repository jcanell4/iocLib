<?php
/**
 * CalculateDateFromFile: retorna la fecha del fichero_continguts del proyecto
 * @culpable rafa
 */
class CalculateMaxPageDateFromNs extends CalculateWithValue {

    public function calculate($data) {
        $dir = WikiPageSystemManager::getRealDirFromPages($this->ns);
        $maxdate = $this->getMaxFileDateOf($dir);
        return date('d/m/Y', $maxdate);
    }

    private function getMaxFileDateOf($dir){
        $maxDate = 0;
        if ($dir)
            $arrayDir = @scandir($dir);
        if ($arrayDir)
            $arrayDir = array_diff($arrayDir, [".", ".."]);
        if ( $arrayDir ) {
            foreach ($arrayDir as $item){
                $fn = "$dir$item";
                if (is_dir($fn)){
                    $date = $this->getMaxFileDateOf("$fn/");
                }else{
                    $date= filemtime($fn);
                }
                if($maxDate<$date){
                    $maxDate=$date;
                }
            }
        }

        return $maxDate;
    }

}
