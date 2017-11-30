<?php
/**
 * AbstractPermission: define la clase abstracta de permisos
 *
 * @author Rafael Claver
 */
if (!defined('DOKU_INC') ) die();

abstract class AbstractPermission {
    
    protected $authenticatedUsersOnly;  //bool (de command_class)
    protected $isSecurityTokenVerified;
    protected $isUserAuthenticated;     // bool
    protected $idPage;
    protected $hasPermissionFor;
    protected $info_writable;
    protected $info_isadmin;
    protected $info_ismanager;
    protected $permissionLoaded = FALSE;
    protected $userGroups;
    
    public function getPermissionLoaded() {
        return $this->permissionLoaded;
    }

    public function getAuthenticatedUsersOnly() {
        return $this->authenticatedUsersOnly;
    }

    public function getSecurityTokenVerified() {
        return $this->isSecurityTokenVerified;
    }

    public function getUserAuthenticated() {
        return $this->isUserAuthenticated;
    }

    public function getPermissionFor() {
        return $this->hasPermissionFor;
    }
  
    public function setPermissionLoaded($permissionLoaded) {
        $this->permissionLoaded = $permissionLoaded;
    }

    public function setAuthenticatedUsersOnly($authenticatedUsersOnly) {
        $this->authenticatedUsersOnly = $authenticatedUsersOnly;
    }

    public function setSecurityTokenVerified($isSecurityTokenVerified) {
        $this->isSecurityTokenVerified = $isSecurityTokenVerified;
    }

    public function setUserAuthenticated($isUserAuthenticated) {
        $this->isUserAuthenticated = $isUserAuthenticated;
    }

    public function setIdPage($idPage) {
        $this->idPage = $idPage;
    }

    public function getIdPage() {
        return $this->idPage;
    }

    public function setPermissionFor($permissionFor) {
        $this->hasPermissionFor = $permissionFor;
    }
  
    public function getInfoWritable() {
        return $this->info_writable;
    }
  
    public function setInfoWritable($info_writable) {
        $this->info_writable = $info_writable;
    }
  
    public function getInfoIsadmin() {
        return $this->info_isadmin;
    }
  
    public function setInfoIsadmin($info_isadmin) {
        $this->info_isadmin = $info_isadmin;
    }
  
    public function getInfoIsmanager() {
        return $this->info_ismanager;
    }
  
    public function setInfoIsmanager($info_ismanager) {
        $this->info_ismanager = $info_ismanager;
    }
  
    public function isAdminOrManager( $checkIsmanager = TRUE ) {
	return $this->getInfoIsadmin() || $checkIsmanager && $this->getInfoIsmanager();
    }

    public function setUserGroups($userGroups) {
        $this->userGroups = $userGroups;
    }

    public function getUserGroups() {
        return $this->userGroups;
    }

}
