<?php
/**
 * interface ICalculateWithProjectId
 */
interface ICalculateWithProjectId {

    const WITH_PROJECT_ID_TYPE = "with_project_id";

    function init($value);

    function getProjectId();

}
