<?php
/**
 * interface ICalculateWithProjectId
 */
interface ICalculateWithProjectId extends ICalculate{

    const WITH_PROJECT_ID_TYPE = "with_project_id";
    
    function getProjectId();

}
