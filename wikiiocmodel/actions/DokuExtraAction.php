<?php
/**
 * DokuExtraAction: Abstract de las acciones no comunes
 * @author culpable Rafa
 */
if (!defined("DOKU_INC")) die();

abstract class DokuExtraAction extends DokuAction {

    const KEY_IS_INTERNAL_ACTION = 0;
    const KEY_NEED_USER_INTERVENTION = 1;
    const KEY_LOW_USUAL_INTERVENTION = 0;
    const KEY_USUAL_INTERVENTION = 1;
    const KEY_HIGH_IMPORTANT_INTERVENTION = 2;
    const KEY_LOW_DURATION = 0;
    const KEY_LONG_DURATION = 1;

    abstract public static function getActionParams();

}
