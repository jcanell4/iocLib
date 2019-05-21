<?php
/**
 * CalculateDateFromFile: retorna la fecha del fichero_continguts del proyecto
 * @culpable rafa
 */
class CalculateMaxPageDateFromNs extends CalculateWithValue {

    public function calculate($data) {
        $dir = WikiPageSystemManager::getRealDirFromPages($this->ns);
        $maxdate =$this->getMaxFileDateOf($dir);
        return date('d/m/Y', $maxdate);
    }
    
    private function getMaxFileDateOf($dir){
        $maxDate = 0;
        $arrayRet = array();
        $arrayDir = scandir($dir);
        if ( $arrayDir ) {
            unset( $arrayDir[0] );
            unset( $arrayDir[1] );
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
