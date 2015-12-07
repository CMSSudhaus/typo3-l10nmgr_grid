<?php
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class ux_tx_l10nmgr_l10nBaseService extends tx_l10nmgr_l10nBaseService {
	/**
   * Copy of Original Funktion
   * Modifyed in line: 118-121
   */
	function _submitContentAsTranslatedLanguageAndGetFlexFormDiff($accum, $inputArray) {
		if (is_array($inputArray)) {
			// Initialize:
			/** @var $flexToolObj t3lib_flexformtools */
			$flexToolObj = t3lib_div::makeInstance('t3lib_flexformtools');
			$gridElementsInstalled = t3lib_extMgm::isLoaded('gridelements');
			$TCEmain_data = array();
			$TCEmain_cmd = array();

			$_flexFormDiffArray = array();
			// Traverse:
			foreach ($accum as $pId => $page) {
				foreach ($accum[$pId]['items'] as $table => $elements) {
					foreach ($elements as $elementUid => $data) {
						if (is_array($data['fields'])) {

							foreach ($data['fields'] as $key => $tData) {

								if (is_array($tData) && isset($inputArray[$table][$elementUid][$key])) {

									list($Ttable, $TuidString, $Tfield, $Tpath) = explode(':', $key);
									list($Tuid, $Tlang, $TdefRecord) = explode('/', $TuidString);

									if (!$this->createTranslationAlsoIfEmpty && $inputArray[$table][$elementUid][$key] == '' && $Tuid == 'NEW') {
										//if data is empty do not save it
										unset($inputArray[$table][$elementUid][$key]);
										continue;
									}

									// If new element is required, we prepare for localization
									if($table === 'tt_content' && $gridElementsInstalled === TRUE) {
										$element = t3lib_BEfunc::getRecordRaw($table, $where = 'uid = ' . $elementUid . ' AND colPos > -1');
									}
									if ($Tuid === 'NEW' && $element !== FALSE) {
										//print "\nNEW\n";
										$TCEmain_cmd[$table][$elementUid]['localize'] = $Tlang;
									}

									// If FlexForm, we set value in special way:
									if ($Tpath) {
										if (!is_array($TCEmain_data[$Ttable][$TuidString][$Tfield])) {
											$TCEmain_data[$Ttable][$TuidString][$Tfield] = array();
										}
										//TCEMAINDATA is passed as reference here:
										$flexToolObj->setArrayValueByPath($Tpath, $TCEmain_data[$Ttable][$TuidString][$Tfield], $inputArray[$table][$elementUid][$key]);
										$_flexFormDiffArray[$key] = array('translated' => $inputArray[$table][$elementUid][$key], 'default' => $tData['defaultValue']);
									} else {
										$TCEmain_data[$Ttable][$TuidString][$Tfield] = $inputArray[$table][$elementUid][$key];
									}
									unset($inputArray[$table][$elementUid][$key]); // Unsetting so in the end we can see if $inputArray was fully processed.
								} else {
									//debug($tData,'fields not set for: '.$elementUid.'-'.$key);
									//debug($inputArray[$table],'inputarray');
								}
							}
							if (is_array($inputArray[$table][$elementUid]) && !count($inputArray[$table][$elementUid])) {
								unset($inputArray[$table][$elementUid]); // Unsetting so in the end we can see if $inputArray was fully processed.
							}
						}
					}
					if (is_array($inputArray[$table]) && !count($inputArray[$table])) {
						unset($inputArray[$table]); // Unsetting so in the end we can see if $inputArray was fully processed.
					}
				}
			}

			self::$targetLanguageID = $Tlang;

			// Execute CMD array: Localizing records:
			/** @var $tce t3lib_TCEmain */
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			if ($this->extensionConfiguration['enable_neverHideAtCopy'] == 1) {
				$tce->neverHideAtCopy = TRUE;
			}
			$tce->stripslashes_values = FALSE;
			$tce->isImporting = TRUE;
			if (count($TCEmain_cmd)) {
				$tce->start(array(), $TCEmain_cmd);
				$tce->process_cmdmap();
				if (count($tce->errorLog)) {
					debug($tce->errorLog, 'TCEmain localization errors:');
				}
			}

			// Before remapping
			if (TYPO3_DLOG) {
				t3lib_div::sysLog(__FILE__ . ': ' . __LINE__ . ': TCEmain_data before remapping: ' . t3lib_div::arrayToLogString($TCEmain_data), 'l10nmgr');
			}
			// Remapping those elements which are new:
			$this->lastTCEMAINCommandsCount = 0;
			foreach ($TCEmain_data as $table => $items) {
				foreach ($TCEmain_data[$table] as $TuidString => $fields) {
					list($Tuid, $Tlang, $TdefRecord) = explode('/', $TuidString);
					$this->lastTCEMAINCommandsCount++;
					if ($Tuid === 'NEW') {
						if ($tce->copyMappingArray_merged[$table][$TdefRecord]) {
							$TCEmain_data[$table][t3lib_BEfunc::wsMapId($table, $tce->copyMappingArray_merged[$table][$TdefRecord])] = $fields;
						} else {
							t3lib_div::sysLog(__FILE__ . ': ' . __LINE__ . ': Record "' . $table . ':' . $TdefRecord . '" was NOT localized as it should have been!', 'l10nmgr');
						}
						unset($TCEmain_data[$table][$TuidString]);
					}
				}
			}
			// After remapping
			if (TYPO3_DLOG) {
				t3lib_div::sysLog(__FILE__ . ': ' . __LINE__ . ': TCEmain_data after remapping: ' . t3lib_div::arrayToLogString($TCEmain_data), 'l10nmgr');
			}

      /**
      * Format Array For Gridelements fields
       */
      $this->_formatForGridelementsFlexForm($TCEmain_data);

			// Now, submitting translation data:
			/** @var $tce t3lib_TCEmain */
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			if ($this->extensionConfiguration['enable_neverHideAtCopy'] == 1) {
				$tce->neverHideAtCopy = TRUE;
			}
			$tce->stripslashes_values = FALSE;
			$tce->dontProcessTransformations = TRUE;
			$tce->isImporting = TRUE;
			//print_r($TCEmain_data);
			$tce->start($TCEmain_data, array()); // check has been done previously that there is a backend user which is Admin and also in live workspace
			$tce->process_datamap();

			self::$targetLanguageID = NULL;

			if (count($tce->errorLog)) {
				t3lib_div::sysLog(__FILE__ . ': ' . __LINE__ . ': TCEmain update errors: ' . t3lib_div::arrayToLogString($tce->errorLog), 'l10nmgr');
			}

			if (count($tce->autoVersionIdMap) && count($_flexFormDiffArray)) {
				if (TYPO3_DLOG) {
					t3lib_div::sysLog(__FILE__ . ': ' . __LINE__ . ': flexFormDiffArry: ' . t3lib_div::arrayToLogString($this->flexFormDiffArray), 'l10nmgr');
				}
				foreach ($_flexFormDiffArray as $key => $value) {
					list($Ttable, $Tuid, $Trest) = explode(':', $key, 3);
					if ($tce->autoVersionIdMap[$Ttable][$Tuid]) {
						$_flexFormDiffArray[$Ttable . ':' . $tce->autoVersionIdMap[$Ttable][$Tuid] . ':' . $Trest] = $_flexFormDiffArray[$key];
						unset($_flexFormDiffArray[$key]);
					}
				}
				if (TYPO3_DLOG) {
					t3lib_div::sysLog(__FILE__ . ': ' . __LINE__ . ': autoVersionIdMap: ' . $tce->autoVersionIdMap, 'l10nmgr');
					t3lib_div::sysLog(__FILE__ . ': ' . __LINE__ . ': _flexFormDiffArray: ' . t3lib_div::arrayToLogString($_flexFormDiffArray), 'l10nmgr');
				}
			}

			// Should be empty now - or there were more information in the incoming array than there should be!
			if (count($inputArray)) {
				debug($inputArray, 'These fields were ignored since they were not in the configuration:');
			}

			return $_flexFormDiffArray;
		} else {
			return FALSE;
		}
	}
	/**
	 * The Funktion checks if the Content elemnt is an FlexForm and Update the Import Array
	 *
	 * @param array  $TCEmain_data
	 * @return $TCEmain_data
	 */
  function _formatForGridelementsFlexForm(&$TCEmain_data){
    foreach ($TCEmain_data['tt_content'] as $uid => $fields) {
      /**
       * Check if Gridelements exist
       */
      $isGridElemnts = array_filter(array_keys($fields), function($key) {
        return strpos($key, 'gridelements-') === 0;
      });
      if(count($isGridElemnts)){
        /**
         * Format Gridelements Form Field Names
         */
        $gridElemnts = array();
        foreach ($isGridElemnts as $key => $value) {
          $gridElemnts[] = str_replace('gridelements-','',$value);
        }
        /**
         * Get Reccord for FlexForm merge
         */
        $translationRecord = t3lib_BEfunc::getRecordWSOL('tt_content', $uid);
        /**
         * Match Flexform XML for Translation
         */
        $flexform = TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($translationRecord['pi_flexform']);
        /**
         * Merge Values to XML Strukture
         */
        $TCEmain_data['tt_content'][$uid] = $this->_mergeGridelementsFlexFormValues($flexform, $fields, $gridElemnts);

      }
    }
  }
	/**
	 * This Funktions Build the new Field Array for the Import with Gridelements
	 *
	 * @param array $flexform
	 * @param array $field
	 * @param array $gridElemnts
	 * @return array
	 */
	function _mergeGridelementsFlexFormValues($flexform, $field, $gridElemnts) {
    /**
     * Merge Values to Strukture
     */
    foreach ($flexform['data'] as $_key => $option) {

      foreach ($option['lDEF'] as $key => $value) {

        if(in_array($key, $gridElemnts)) {
          $flexform['data'][$_key]['lDEF'][$key]['vDEF'] = $field['gridelements-'.$key];
          // Unset old Arrays
          unset($field['gridelements-'.$key]);
        }

      }
    }
    // Pack FlexForm to XML
    $flexformTools = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
		$returnXML = $flexformTools->flexArray2Xml($flexform, TRUE);
    // Set new Array to Updater
    $field['pi_flexform'] = $flexformTools->flexArray2Xml($flexform, TRUE);
    // Return new Field Array for SQL import
    return $field;
  }
}
