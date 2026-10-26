<?php
/**
 * Description of MediaDataQuery
 * @author josep
 */
if (! defined('DOKU_INC')) die();

require_once (DOKU_INC . 'inc/auth.php');
require_once (DOKU_INC . 'inc/pageutils.php');
require_once (DOKU_INC . 'inc/io.php');
require_once (DOKU_INC . 'inc/confutils.php');
require_once (DOKU_INC . 'inc/media.php');

class MediaDataQuery extends DataQuery{

    public function getFileName($id, $sppar=NULL) {
        if (is_array($sppar)){
            $rev = $sppar["rev"];
        }else{
            $rev = $sppar;
        }
        return mediaFN( $id, $rev );
    }

    public function getNsTree($currentNode, $sortBy, $onlyDirs = FALSE, $expandProjects=FALSE, $hiddenProjects=FALSE, $root=FALSE) {
        $base = WikiGlobalConfig::getConf('mediadir');
        return $this->getNsTreeFromGenericSearch($base, $currentNode, $sortBy, $onlyDirs, 'search_index', $expandProjects, $hiddenProjects, $root);
    }

    public function save($id, $filePathSource, $overWrite = TRUE ) {
        $ns = $this->getNs($id);
        $imageId = $this->getIdWithoutNs($id);
        return $this->_saveImage($ns, $imageId, $filePathSource, $overWrite, "move_uploaded_file");
    }

    public function delete($id) {
        return $this->_deleteImage($id);
    }

    /**
     * És la crida pincipal de la comanda save_unlinked_image.
     * Guarda un fitxer de tipus media pujat des del client
     * @param string $nsTarget
     * @param string $idTarget
     * @param string $filePathSource
     * @param bool   $overWrite
     *
     * @return int
     */
    //[ALERTA Josep] Es trasllada a BasicPersistenceManager
    //[TODO Josep] Aquí cal crear una crida normalitzada que en processar
    //l'acció cridi a aquesta funció traslladada a la classe encarregada
    //de la persistencia.
    public function upload( $nsTarget, $idTarget, $filePathSource, $overWrite = FALSE ) {
        return $this->_saveImage($nsTarget, $idTarget, $filePathSource, $overWrite, "move_uploaded_file");
    }

    /**
     * És la crida principal de la comanda copy_image_to_project
     * @param string $nsTarget
     * @param string $idTarget
     * @param string $filePathSource
     * @param bool   $overWrite
     *
     * @return int
     */
    public function copy( $nsTarget, $idTarget, $filePathSource, $overWrite = FALSE ) {
        return $this->_saveImage($nsTarget, $idTarget, $filePathSource, $overWrite, "copy");
    }


  /**
    * Handles media file deletions
    *
    * If configured, checks for media references before deletion
    *
    * @return int One of: 0,
    *                     DOKU_MEDIA_DELETED,
    *                     DOKU_MEDIA_DELETED | DOKU_MEDIA_EMPTY_NS,
    *                     DOKU_MEDIA_INUSE
    */
    private function _deleteImage($idImge) {
        $auth = auth_quickaclcheck( getNS( $idImge ) . ":*" );
        $ret = media_delete($idImge, $auth);
        return $ret;
    }

    /**
     * @param string   $nsTarget
     * @param string   $idTarget
     * @param string   $filePathSource
     * @param boolean  $overWrite
     * @param callable $copyFunction funció que es cridarà per moure el fitxer de la ruta tempora a la ruta final.
     *                               Aquesta funciò ha de rebre com a paràmetres dos strings, el primer amb el nom del
     *                               fitxer temporal i el segon amb el nom del fitxer final
     *
     * @return int enter corresponent a un dels següents codis:
     *       0 = OK
     *      -1 = UNAUTHORIZED
     *      -2 = OVER_WRITING_NOT_ALLOWED
     *      -3 = OVER_WRITING_UNAUTHORIZED
     *      -5 = FAILS
     *      -4 = WRONG_PARAMS
     *      -6 = BAD_CONTENT
     *      -7 = SPAM_CONTENT
     *      -8 = XSS_CONTENT
     */
    private function _saveImage($nsTarget, $idTarget, $filePathSource, $overWrite, $copyFunction) {
        global $conf;
        $res = NULL; //(0=OK, -1=UNAUTHORIZED, -2=OVER_WRITING_NOT_ALLOWED,
        //-3=OVER_WRITING_UNAUTHORIZED, -5=FAILS, -4=WRONG_PARAMS
        //-6=BAD_CONTENT, -7=SPAM_CONTENT, -8=XSS_CONTENT)
        $auth = auth_quickaclcheck( $nsTarget . ":*" );

        if ( $auth >= AUTH_UPLOAD ) {
            io_createNamespace( "$nsTarget:xxx", 'media' ); //TODO [Josep] Canviar el literal media pel valor de la configuració (mediadir?)
            list( $ext, $mime, $dl ) = mimetype( $idTarget );
            $res_media = media_save(
                array(
                    'name' => $filePathSource,
                    'mime' => $mime,
                    'ext'  => $ext
                ),
                $nsTarget . ':' . $idTarget,
                $overWrite,
                $auth,
                $copyFunction
            );

            if ( is_array( $res_media ) ) {
                if ( $res_media[1] == 0 ) {
                    if ( $auth < ( ( $conf['mediarevisions'] ) ? AUTH_UPLOAD : AUTH_DELETE ) ) {
                        $res = - 3;
                    } else {
                        $res = - 2;
                    }
                } else if ( $res_media[1] == - 1 ) {
                    $res = - 5;
                    $res += media_contentcheck( $filePathSource, $mime );
                }
            } else if ( ! $res_media ) {
                $res = - 4;
            } else {
                $res = 0;
            }
        } else {
            $res = - 1; //NO AUTORITZAT
        }

        return $res;
    }
}
