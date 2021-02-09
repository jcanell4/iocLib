<?php
/**
 * ResultsVerificationError
 * @author rafael <rclaver@xtec.cat>
 */

class ResultsVerificationError {

    public function get_html_data_errors($param) {

        $ret = '<span id="dataerror" style="word-wrap: break-word;">';
        foreach ($param as $key => $error) {
            if ($key === "noerror") {
                $ret.= '<p>'.$error.'</p>';
            }else {
                foreach ($error as $item) {
                    $ret.= '<p><a class="interwiki" href="'.$item['field'].'">'.$item['field'].'</a> ';
                    $ret.= '<span>'.$item['message'].'</span></p>';
                }
            }
        }
        $ret.= '</span>';
        return $ret;
    }

}
