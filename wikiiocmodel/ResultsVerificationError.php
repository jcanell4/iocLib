<?php
/**
 * ResultsVerificationError
 * @author rafael <rclaver@xtec.cat>
 */

class ResultsVerificationError {

    public function get_html_data_errors($param) {

        $ret = '<span id="dataerror" style="word-wrap: break-word;">';
        foreach ($param as $key => $errors) {
            if ($key === "NOERROR") {
                $ret.= '<p>'.$errors.'</p>';
            }else if($key == "ERROR"){
                foreach ($errors as $error) {
                    $ret.= '<p class="resultatErroni"><a class="interwiki" href="#'.$error['field'].'">'.$error['field'].'</a> ';
                    $ret.= '<span>'.$error['message'].'</span></p>';
                    
                }
            }else if($key == "WARNING"){
                foreach ($errors as $error) {
                    $ret.= '<p class="resultatAlerta"><a class="interwiki" href="#'.$error['field'].'">'.$error['field'].'</a> ';
                    $ret.= '<span>'.$error['message'].'</span></p>';
                    
                }
            }
        }
        $ret.= '</span>';
        return $ret;
    }

}
