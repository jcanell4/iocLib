<?php
/**
 * Description of DraftDataQuery
 * @author Josep Cañellas i Xavier Garcia
 */
if (!defined('DOKU_INC')) die();

class DraftDataQuery extends DataQuery
{
    public function getFileName($id, $extra=NULL) {
        return $this->getFullFileName($id);
    }

    public function getFullFileName($id) {
        return getCacheName(WikiIocInfoManager::getInfo("client") . $id, '.draft');
    }

    public function getStructuredFilename($id) {
        return $this->getFilename($id) . '.structured';
    }

    public function getNsTree($currentNode, $sortBy, $onlyDirs=FALSE, $expandProject=FALSE, $hiddenProjects=FALSE, $root=FALSE) {
        throw new UnavailableMethodExecutionException("DraftDataQuery#getNsTree");
    }

    public function getFull($id)    {
        $draftFile = $this->getFilename($id);
        $cleanedDraft = NULL;
        $draft = [];

        // Si el draft es més antic que el document actual esborrem el draft
        if ($this->hasFull($id)) {
            $draft = unserialize(io_readFile($draftFile, FALSE));
            $cleanedDraft = self::cleanDraft(con($draft['prefix'], $draft['text'], $draft['suffix']));
        }

        return ['content' => $cleanedDraft, 'date' => $draft['date']];
    }

    public function removeStructured($id) {
        $draftFile = $this->getStructuredFilename($id);
        if (@file_exists($draftFile)) {
            @unlink($draftFile);
        }
    }

    public function removeChunk($id, $chunkId) {
        $draftFile = $this->getStructuredFilename($id);

        if (@file_exists($draftFile)) {
            $oldDraft = $this->getStructured($id);

            if (isset($oldDraft['content']) && array_key_exists($chunkId, $oldDraft['content'])) {
                unset($oldDraft['content'][$chunkId]);
            }

            if (isset($oldDraft['content']) && count($oldDraft['content']) > 0) {
                io_saveFile($draftFile, serialize($oldDraft));

            } else {
                // No hi ha res, l'esborrem
                @unlink($draftFile);
            }
        }
    }

    public function getStructured($id) {
        $draftFile = self::getStructuredFilename($id);
        $draft = [];

        if (@file_exists($draftFile)) {
            $draft = unserialize(io_readFile($draftFile, FALSE));
        }

        return $draft;
    }

    public function hasFull($id) {
        $draftFile = $this->getFullFileName($id);
        return self::existsDraft($draftFile, $id);
    }

    public function hasStructured($id) {
        $draftFile = $this->getStructuredFilename($id);
        return self::existsDraft($draftFile, $id);
    }

    /**
     * Retorna cert si existeix un esborrany del propi usuari.
     * En cas de que es trobi un esborrany més antic que el document, és esborrat.
     * @param $id - id del document
     * @return bool - cert si hi ha un esborrany vàlid o fals en cas contrari.
     */
    public function hasAny($id) {
        return $this->hasFull($id) || $this->hasStructured($id);
    }

    public function getChunk($id, $header) {
        $ret = NULL;
        $draftFile = $this->getStructuredFilename($id);

        if ($this->hasStructured($id)) {
            $draft = unserialize(io_readFile($draftFile, FALSE));

            if ($draft['content'][$header]) {
                $ret = ['content' => $draft['content'][$header],
                        'date' => $draft['date']
                       ];
            }
        }

        return $ret;
    }


    public function generateStructured($draft, $id, $date) {
        $newDraft = [];
        $newDraft['date'] = $date;

        $draftFile = $this->getStructuredFilename($id);

        if (@file_exists($draftFile)) {
            // Obrim el draft actual si existeix
            $oldDraft = $this->getStructured($id)['content'];
        } else {
            $oldDraft = [];
        }

        if (!empty($oldDraft)) {
            // Recorrem la llista de headers de old drafts
            foreach ($oldDraft as $header => $chunk) {

                if (array_key_exists($header, $draft) && $chunk != $draft[$header]) {
                    $newDraft['content'][$header] = $draft[$header];
                    unset($draft[$header]);

                } else {
                    $newDraft['content'][$header] = $chunk;
                }
            }
        }

        foreach ($draft as $header => $content) {
            $newDraft['content'][$header] = $content;
        }

        foreach ($draft as $header => $content) {
            $newDraft['content'][$header] = $content;
        }

        // Guardem el draft si hi ha cap chunk
        if (count($newDraft) > 0) {
            io_saveFile($draftFile, serialize($newDraft));
            $this->removeFull($id);
        } else {
            // No hi ha res, l'esborrem
            @unlink($draftFile);
        }

    }

    /**
     * Guarda l'esborrany complet del document i s'eliminen els esborranys parcials
     * @param $draft
     * @param $id
     */
    public function saveFullDraft($draft, $id, $date) {
        global $INFO;
        $aux = ['id' => $id,
                'prefix' => '',
                'text' => $draft,
                'suffix' => '',
                'date' => $date,
                'client' => WikiIocInfoManager::getInfo('client')
               ];
        $filename = $this->getFilename($id);

        if (io_saveFile($filename, serialize($aux))) {
            $INFO['draft'] = $filename;
        }

        $this->removeStructured($id);
    }

    /**
     * Guarda l'esborrany del projecte (dades del formulari) que s'està modificant
     * @return boolean Indica si el draft s'ha desat correctamment
     */
    public function saveProjectDraft($draft, $subSet) {
        $aux = ['id' => $draft['id'],
                'prefix' => '',
                'text' => $draft['content'],
                'suffix' => '',
                'date' => $draft['date'],
                'client' => WikiIocInfoManager::getInfo('client')
               ];
        $filename = $this->getFilename($draft['id'].$subSet);
        return io_saveFile($filename, serialize($aux));
    }

    public function removeProjectDraft($id) {
        $this->removeFull($id);
    }

    public function getStructuredDraft($id) {
        $draft = [];
        $draftFile = $this->getStructuredFilename($id);

        if (@file_exists($draftFile)) {
            $draft = unserialize(io_readFile($draftFile, FALSE));
        }

        return $draft;
    }

    /**
     * Retorna cert si existeix un draft o fals en cas contrari. Si es troba un draft però es més antic que el document
     * corresponent aquest draft s'esborra.
     *
     * @param {string} $id id del document a comprovar
     * @return bool
     */
    private static function existsDraft($draftFile, $id) {
        $exists = false;

        // Si el draft es més antic que el document actual esborrem el draft
        if (@file_exists($draftFile)) {
            if (@filemtime($draftFile) < @filemtime(wikiFN($id))) {
                @unlink($draftFile);
                $exists = false;
            } else {
                $exists = true;
            }
        }
        return $exists;
    }

    /**
     * Neteja el contingut del esborrany per poder fer-lo servir directament.
     *
     * @param string $text - contingut original del fitxer de esborrany.
     * @return mixed
     */
    private static function cleanDraft($text) {
        $pattern = '/^(wikitext\s*=\s*)|(date=[0-9]*)$/i';
        $content = preg_replace($pattern, '', $text);
        return $content;
    }

    public function removeFull($id) {
        $draftFile = $this->getFileName($id);
        if (@file_exists($draftFile)) {
            @unlink($draftFile);
        }
    }

    public function getFullDraftDate($id) {
        $draftFile = $this->getFullFileName($id);
        if (@file_exists($draftFile)) {
            $draft = unserialize(io_readFile($draftFile, FALSE));
            return $draft['date'];
        } else {
            return -1;
        }
    }

    public function getStructuredDraftDate($id)   {
        $draft = $this->getStructured($id);
        return $draft['date'];
    }

}
