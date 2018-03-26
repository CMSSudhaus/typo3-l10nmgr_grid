<?php
namespace WebKonInternetagentur\L10nmgrGrid\Model\Tools;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Web-Kon Internetagentur <technik@web-kon.de>
 *
 ***************************************************************/

/**
 * baseService class for offering common services like saving translation etc...
 *
 * @author     Dirk Persky <technik@web-kon.de>
 * @package    TYPO3
 * @subpackage tx_l10nmgr_grid
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Localizationteam\L10nmgr\Model\Tools\Tools as OriginalTools;

class Tools extends OriginalTools {
  /**
   * Extend Original Funktion
   */
  function _detectTranslationModes($tInfo, $table, $row) {
    $translationModes = parent::_detectTranslationModes($tInfo, $table, $row);
    /**
     * Check if element is an Gridelement
     */
    if ($row['CType'] == 'gridelements_pi1') {
        $this->detailsOutput['log'][] = 'Mode: "gridelements" detected because we Gridelements Record';
        $translationModes[] = 'gridelements';
    }

    return array_unique($translationModes);
  }
  /**
   * Copy of Original Funktion
   * Modifyed in line: 110-114
   */
    public function translationDetails($table, $row, $sysLang, $flexFormDiff = array(), $previewLanguage = 0){
      global $TCA;
      // Initialize:
      $tInfo = $this->translationInfo($table, $row['uid'], $sysLang, null, '', $previewLanguage);
      $this->detailsOutput = array();
      $this->flexFormDiff = $flexFormDiff;
      if (is_array($tInfo)) {
          // Initialize some more:
          $this->detailsOutput['translationInfo'] = $tInfo;
          $this->sysLanguages = $this->getSystemLanguages();
          $this->detailsOutput['ISOcode'] = $this->sysLanguages[$sysLang]['ISOcode'];
          // decide how translations are stored:
          // there are three ways: flexformInternalTranslation (for FCE with langChildren)
          // useOverlay (for elements with classic overlay record)
          // noTranslation
          $translationModes = $this->_detectTranslationModes($tInfo, $table, $row);
          foreach ($translationModes as $translationMode) {
              switch ($translationMode) {
                  case 'flexformInternalTranslation':
                      $this->detailsOutput['log'][] = 'Mode: flexFormTranslation with no translation set; looking for flexform fields';
                      $this->_lookForFlexFormFieldAndAddToInternalTranslationDetails($table, $row);
                      break;
                  case 'useOverlay':
                      if (count($tInfo['translations'])) {
                          $this->detailsOutput['log'][] = 'Mode: translate existing record';
                          $translationUID = $tInfo['translations'][$sysLang]['uid'];
                          $translationRecord = BackendUtility::getRecordWSOL($tInfo['translation_table'],
                              $tInfo['translations'][$sysLang]['uid']);
                      } else {
                          // Will also suggest to translate a default language record which are in a container block with Inheritance or Separate mode. This might not be something people wish, but there is no way we can prevent it because its a deprecated localization paradigm to use container blocks with localization. The way out might be setting the langauge to "All" for such elements.
                          $this->detailsOutput['log'][] = 'Mode: translate to new record';
                          $translationUID = 'NEW/' . $sysLang . '/' . $row['uid'];
                          $translationRecord = array();
                      }
                      if ($TCA[$tInfo['translation_table']]['ctrl']['transOrigDiffSourceField']) {
                          $diffArray = unserialize($translationRecord[$TCA[$tInfo['translation_table']]['ctrl']['transOrigDiffSourceField']]);
                          // debug($diffArray);
                      } else {
                          $diffArray = array();
                      }
                      $prevLangRec = array();
                      foreach ($this->previewLanguages as $prevSysUid) {
                          $prevLangInfo = $this->translationInfo($table, $row['uid'], $prevSysUid, null, '',
                              $previewLanguage);
                          if (!empty($prevLangInfo) && $prevLangInfo['translations'][$prevSysUid]) {
                              $prevLangRec[$prevSysUid] = BackendUtility::getRecordWSOL($prevLangInfo['translation_table'],
                                  $prevLangInfo['translations'][$prevSysUid]['uid']);
                          } else {
                              $prevLangRec[$prevSysUid] = BackendUtility::getRecordWSOL($prevLangInfo['translation_table'],
                                  $row['uid']);
                          }
                      }
                      foreach ($TCA[$tInfo['translation_table']]['columns'] as $field => $cfg) {
                          $cfg['labelField'] = trim($TCA[$tInfo['translation_table']]['ctrl']['label']);
                          if ($TCA[$tInfo['translation_table']]['ctrl']['languageField'] !== $field && $TCA[$tInfo['translation_table']]['ctrl']['transOrigPointerField'] !== $field && $TCA[$tInfo['translation_table']]['ctrl']['transOrigDiffSourceField'] !== $field) {
                              $key = $tInfo['translation_table'] . ':' . BackendUtility::wsMapId($tInfo['translation_table'],
                                      $translationUID) . ':' . $field;
                              if ($cfg['config']['type'] == 'flex') {
                                  $dataStructArray = $this->_getFlexFormMetaDataForContentElement($table, $field,
                                      $row);
                                  if ($dataStructArray['meta']['langDisable'] && $dataStructArray['meta']['langDatabaseOverlay'] == 1 || $table === 'tt_content' && $row['CType'] === 'fluidcontent_content') {
                                      // Create and call iterator object:
                                      /** @var FlexFormTools $flexObj */
                                      $flexObj = GeneralUtility::makeInstance(FlexFormTools::class);
                                      $this->_callBackParams_keyForTranslationDetails = $key;
                                      $this->_callBackParams_translationXMLArray = GeneralUtility::xml2array($translationRecord[$field]);
                                      if (is_array($translationRecord)) {
                                          $diffsource = unserialize($translationRecord['l18n_diffsource']);
                                          $this->_callBackParams_translationDiffsourceXMLArray = GeneralUtility::xml2array($diffsource[$field]);
                                      }
                                      foreach ($this->previewLanguages as $prevSysUid) {
                                          $this->_callBackParams_previewLanguageXMLArrays[$prevSysUid] = GeneralUtility::xml2array($prevLangRec[$prevSysUid][$field]);
                                      }
                                      $this->_callBackParams_currentRow = $row;
                                      $flexObj->traverseFlexFormXMLData($table, $field, $row, $this,
                                          'translationDetails_flexFormCallBackForOverlay');
                                  }
                                  $this->detailsOutput['log'][] = 'Mode: useOverlay looking for flexform fields!';
                              } else {
                                  // handle normal fields:
                                  $diffDefaultValue = $diffArray[$field];
                                  $previewLanguageValues = array();
                                  foreach ($this->previewLanguages as $prevSysUid) {
                                      $previewLanguageValues[$prevSysUid] = $prevLangRec[$prevSysUid][$field];
                                  }
                                  // debug($row[$field]);
                                  $this->translationDetails_addField($key, $cfg, $row[$field],
                                      $translationRecord[$field], $diffDefaultValue, $previewLanguageValues, $row);
                              }
                          }
                          // elseif ($cfg[
                      }
                      break;
                  case 'gridelements':
                      /**
                       * Existiert bereits eine übersetzung?
                       * L10nmrg_grid
                       */
                      $this->_lookForGridelementsFormFieldAndAddToInternalTranslationDetails($tInfo, $table, $row, $sysLang);

                      break;
              }
          } // foreach translationModes
      } else {
          $this->detailsOutput['log'][] = 'ERROR: ' . $tInfo;
      }
      return $this->detailsOutput;
  }
  /**
	 * The Funktion Build the Export Array for the Gridelements Translations
	 *
   * @param array  $tInfo
   * @param string $table
   * @param array  $row
	 * @param int    $sysLang
	 * @return
	 */
  function _lookForGridelementsFormFieldAndAddToInternalTranslationDetails($tInfo, $table, $row, $sysLang){
    // Get Plugin Configuration
    $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['l10nmgr_grid']);
    // Get Element Arrays
    $exclude = array_map('trim',explode(',', $conf['exclude']));
    $include = array_map('trim',explode(',', $conf['include']));

    // Element is in Exclude list, also not Import GridFields
    if(in_array($row['tx_gridelements_backend_layout'], $exclude)) return false;
    // Element is not in Include list, also not Import GridFields
    if( !empty($conf['include']) && !in_array($row['tx_gridelements_backend_layout'], $include)) return false;

    /**
     * Match Flexform XML
     */
    $flexform = GeneralUtility::xml2array($row['pi_flexform']);
    // Übersetzung Holen:
    $translationValue = false;
    if(count($tInfo['translations'])) {
        /**
         * Get Translation Reccord
         */
        $this->detailsOutput['log'][] = 'Mode: translate existing record';
        $translationUID = $tInfo['translations'][$sysLang]['uid'];
        $translationRecord = BackendUtility::getRecordWSOL($tInfo['translation_table'], $tInfo['translations'][$sysLang]['uid']);
        /**
         * Match Flexform XML for Translation
         */
        $translationValue = GeneralUtility::xml2array($translationRecord['pi_flexform']);

    }
    foreach ($flexform['data'] as $_key => $option) {

      foreach ($option['lDEF'] as $key => $value) {
        $addField = true;
        $flexFormInclude = array_map('trim',explode(',', $conf['flexformInclude']));
        $flexFormExclude = array_map('trim',explode(',', $conf['flexformExclude']));
        /**
         * Field is in Exclude list, also not Import Field
         */
        if( $this->_checkPatternCondition($flexFormExclude, $row['tx_gridelements_backend_layout'], $key) ) $addField = false;
        /**
         * Field is not in Include list, also not Import Field
         */
        if( !empty($conf['flexformInclude']) && !$this->_checkPatternCondition($flexFormInclude, $row['tx_gridelements_backend_layout'], $key) ) $addField = false;

        if($addField === true){
          /**
           * IF Translation Exist match to Array
           */
          $_translationValue = false;
          $_diff = false;

          if( isset($translationValue) && !empty($translationValue) && isset($translationValue['data'][$_key]['lDEF'][$key]['vDEF'])){
            $_translationValue = $translationValue['data'][$_key]['lDEF'][$key]['vDEF'];
            /**
             * Generate Array Key
             */
            $arrayKey = 'tt_content:'.$tInfo['translations'][$sysLang]['uid'].':gridelements-'.$key;

          } else {
            /**
             * Generate Array Key
             */
            $arrayKey = 'tt_content:NEW/' . $sysLang . '/'.$row['uid'].':gridelements-'.$key;

          }
          /**
           * Build Translation Array
           */
          $this->detailsOutput['fields'][$arrayKey] = array(
            "defaultValue"=> $value['vDEF'],
            "translationValue" => $_translationValue,
            "diffDefaultValue"=> $_diff,
            "previewLanguageValues" => '',
            "msg" => '',
            "readOnly" => false,
            "fieldType" => "input",
            "isRTE" => false,
          );
        }
      }
    }
  }

  function _checkPatternCondition($conditions, $flexform, $field){

    foreach ($conditions as $pattern) {
      $_pattern = explode(':', $pattern);

      // If Flexform Match Condition
      if(in_array( $_pattern[0],array('all' =>'*', 'form' => $flexform) ) ) {

        if(in_array( $_pattern[1],array('all' =>'*', 'form' => $field) ) ) {
          // Match Gefunden
          return true;
        }

      }
    }
    // No Match Found
    return false;
  }
}
