<?php
/**
 * Description of WiocclLooperInstruction
 *
 * @author josep
 */
interface WiocclLooperInstruction {
    public function getFrom();
    
    public function getTo();
    
    public function getStep();
    
    public function updateLoop();
    
    public function validateLoop();    

    public function parseToken($tokens, &$tokenIndex);    
}
