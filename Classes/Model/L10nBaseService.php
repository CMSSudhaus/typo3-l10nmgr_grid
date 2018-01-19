<?php

namespace WebKonInternetagentur\L10nmgrGrid\Model;

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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Localizationteam\L10nmgr\Model\L10nBaseService as OriginalL10nBaseService;


class L10nBaseService extends OriginalL10nBaseService
{
    /**
     * Copy of Original Funktion
     * Modifyed in line: 118-121
     */
    function _submitContentAsTranslatedLanguageAndGetFlexFormDiff($accum, $inputArray)
    {
        global $TCA;
        if (is_array($inputArray)) {
            // Initialize:
            /** @var FlexFormTools $flexToolObj */
            $flexToolObj = GeneralUtility::makeInstance(FlexFormTools::class);
            $gridElementsInstalled = ExtensionManagementUtility::isLoaded('gridelements');
            $fluxInstalled = ExtensionManagementUtility::isLoaded('flux');
            $element = array();
            $TCEmain_data = array();
            $this->TCEmain_cmd = array();
            $Tlang = '';
            $_flexFormDiffArray = array();
            // Traverse:
            foreach ($accum as $pId => $page) {
                foreach ($accum[$pId]['items'] as $table => $elements) {
                    foreach ($elements as $elementUid => $data) {
                        $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['beforeDataFieldsTranslated'];
                        if (is_array($hooks)) {
                            foreach ($hooks as $hookObj) {
                                $parameters = array(
                                    'data' => $data
                                );
                                $data = GeneralUtility::callUserFunction($hookObj, $parameters, $this);
                            }
                        }
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
                                    if ($Tuid === 'NEW') {
                                        if ($table === 'tt_content' && ($gridElementsInstalled === true || $fluxInstalled === true)) {
                                            $element = BackendUtility::getRecordRaw($table,
                                                'uid = ' . (int)$elementUid . ' AND deleted = 0');
                                            if (isset($this->TCEmain_cmd['tt_content'][$elementUid])) {
                                                unset($this->TCEmain_cmd['tt_content'][$elementUid]);
                                            }
                                            if ((int)$element['colPos'] > -1 && (int)$element['colPos'] !== 18181) {
                                                $this->TCEmain_cmd['tt_content'][$elementUid]['localize'] = $Tlang;
                                            } else {
                                                if ($element['tx_gridelements_container'] > 0) {
                                                    $this->depthCounter = 0;
                                                    $this->recursivelyCheckForRelationParents($element, $Tlang,
                                                        'tx_gridelements_container', 'tx_gridelements_children');
                                                }
                                                if ($element['tx_flux_parent'] > 0) {
                                                    $this->depthCounter = 0;
                                                    $this->recursivelyCheckForRelationParents($element, $Tlang,
                                                        'tx_flux_parent', 'tx_flux_children');
                                                }
                                            }
                                        } elseif ($table === 'sys_file_reference') {
                                            $element = BackendUtility::getRecordRaw($table,
                                                'uid = ' . (int)$elementUid . ' AND deleted = 0');
                                            if ($element['uid_foreign'] && $element['tablenames'] && $element['fieldname']) {
                                                if ($element['tablenames'] === 'pages') {
                                                    if (isset($this->TCEmain_cmd[$table][$elementUid])) {
                                                        unset($this->TCEmain_cmd[$table][$elementUid]);
                                                    }
                                                    $this->TCEmain_cmd[$table][$elementUid]['localize'] = $Tlang;
                                                } else {
                                                    $parent = BackendUtility::getRecordRaw($element['tablenames'],
                                                        $TCA[$element['tablenames']]['ctrl']['transOrigPointerField'] . ' = ' . (int)$element['uid_foreign'] .
                                                        ' AND deleted = 0 AND sys_language_uid = ' . (int)$Tlang);
                                                    if ($parent['uid'] > 0) {
                                                        if (isset($this->TCEmain_cmd[$element['tablenames']][$element['uid_foreign']])) {
                                                            unset($this->TCEmain_cmd[$element['tablenames']][$element['uid_foreign']]);
                                                        }
                                                        $this->TCEmain_cmd[$element['tablenames']][$element['uid_foreign']]['inlineLocalizeSynchronize'] = $element['fieldname'] . ',localize';
                                                    }
                                                }
                                            }
                                        } else {
                                            //print "\nNEW\n";
                                            if (isset($this->TCEmain_cmd[$table][$elementUid])) {
                                                unset($this->TCEmain_cmd[$table][$elementUid]);
                                            }
                                            $this->TCEmain_cmd[$table][$elementUid]['localize'] = $Tlang;
                                        }
                                        $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['importNewTceMainCmd'];
                                        if (is_array($hooks)) {
                                            foreach ($hooks as $hookObj) {
                                                $parameters = array(
                                                    'data' => $data,
                                                    'TCEmain_cmd' => $this->TCEmain_cmd
                                                );
                                                $this->TCEmain_cmd = GeneralUtility::callUserFunction($hookObj,
                                                    $parameters, $this);
                                            }
                                        }
                                    }
                                    // If FlexForm, we set value in special way:
                                    if ($Tpath) {
                                        if (!is_array($TCEmain_data[$Ttable][$TuidString][$Tfield])) {
                                            $TCEmain_data[$Ttable][$TuidString][$Tfield] = array();
                                        }
                                        //TCEMAINDATA is passed as reference here:
                                        $flexToolObj->setArrayValueByPath($Tpath,
                                            $TCEmain_data[$Ttable][$TuidString][$Tfield],
                                            $inputArray[$table][$elementUid][$key]);
                                        $_flexFormDiffArray[$key] = array(
                                            'translated' => $inputArray[$table][$elementUid][$key],
                                            'default' => $tData['defaultValue']
                                        );
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
                        $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['afterDataFieldsTranslated'];
                        if (is_array($hooks)) {
                            foreach ($hooks as $hookObj) {
                                $parameters = array(
                                    'TCEmain_data' => $TCEmain_data,
                                    'TCEmain_cmd' => $this->TCEmain_cmd
                                );
                                $this->TCEmain_cmd = GeneralUtility::callUserFunction($hookObj, $parameters, $this);
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
            /** @var DataHandler $tce */
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            if ($this->extensionConfiguration['enable_neverHideAtCopy'] == 1) {
                $tce->neverHideAtCopy = true;
            }
            $tce->isImporting = true;
            if (count($this->TCEmain_cmd)) {
                $tce->start(array(), $this->TCEmain_cmd);
                $tce->process_cmdmap();
                if (count($tce->errorLog)) {
                    debug($tce->errorLog, 'TCEmain localization errors:');
                }
            }
            // Before remapping
            if (TYPO3_DLOG) {
                GeneralUtility::sysLog(__FILE__ . ': ' . __LINE__ . ': TCEmain_data before remapping: ' . GeneralUtility::arrayToLogString($TCEmain_data),
                    'l10nmgr');
            }
            // Remapping those elements which are new:
            $this->lastTCEMAINCommandsCount = 0;
            foreach ($TCEmain_data as $table => $items) {
                foreach ($TCEmain_data[$table] as $TuidString => $fields) {
                    if ($table === 'sys_file_reference' && $fields['tablenames'] === 'pages') {
                        $parent = BackendUtility::getRecordRaw('pages_language_overlay',
                            'pid = ' . (int)$element['uid_foreign'] . ' AND deleted = 0 AND sys_language_uid = ' . (int)$Tlang);
                        if ($parent['uid']) {
                            $fields['tablenames'] = 'pages_language_overlay';
                            $fields['uid_foreign'] = $parent['uid'];
                        }
                    }
                    list($Tuid, $Tlang, $TdefRecord) = explode('/', $TuidString);
                    $this->lastTCEMAINCommandsCount++;
                    if ($Tuid === 'NEW') {
                        if ($tce->copyMappingArray_merged[$table][$TdefRecord]) {
                            $TCEmain_data[$table][BackendUtility::wsMapId($table,
                                $tce->copyMappingArray_merged[$table][$TdefRecord])] = $fields;
                        } else {
                            GeneralUtility::sysLog(__FILE__ . ': ' . __LINE__ . ': Record "' . $table . ':' . $TdefRecord . '" was NOT localized as it should have been!',
                                'l10nmgr');
                        }
                        unset($TCEmain_data[$table][$TuidString]);
                    }
                }
            }
            // After remapping
            if (TYPO3_DLOG) {
                GeneralUtility::sysLog(__FILE__ . ': ' . __LINE__ . ': TCEmain_data after remapping: ' . GeneralUtility::arrayToLogString($TCEmain_data),
                    'l10nmgr');
            }
            /**
             * Format Array For Gridelements fields
             * L10nmrg_grid
             */
            $this->_formatForGridelementsFlexForm($TCEmain_data);

            // Now, submitting translation data:
            /** @var DataHandler $tce */
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            if ($this->extensionConfiguration['enable_neverHideAtCopy'] == 1) {
                $tce->neverHideAtCopy = true;
            }
            $tce->dontProcessTransformations = true;
            $tce->isImporting = true;
            foreach (array_chunk($TCEmain_data, 100, true) as $dataPart) {
                $tce->start($dataPart,
                    array()); // check has been done previously that there is a backend user which is Admin and also in live workspace
                $tce->process_datamap();
            }
            self::$targetLanguageID = null;
            if (count($tce->errorLog)) {
                GeneralUtility::sysLog(__FILE__ . ': ' . __LINE__ . ': TCEmain update errors: ' . GeneralUtility::arrayToLogString($tce->errorLog),
                    'l10nmgr');
            }
            if (count($tce->autoVersionIdMap) && count($_flexFormDiffArray)) {
                if (TYPO3_DLOG) {
                    GeneralUtility::sysLog(__FILE__ . ': ' . __LINE__ . ': flexFormDiffArry: ' . GeneralUtility::arrayToLogString($this->flexFormDiffArray),
                        'l10nmgr');
                }
                foreach ($_flexFormDiffArray as $key => $value) {
                    list($Ttable, $Tuid, $Trest) = explode(':', $key, 3);
                    if ($tce->autoVersionIdMap[$Ttable][$Tuid]) {
                        $_flexFormDiffArray[$Ttable . ':' . $tce->autoVersionIdMap[$Ttable][$Tuid] . ':' . $Trest] = $_flexFormDiffArray[$key];
                        unset($_flexFormDiffArray[$key]);
                    }
                }
                if (TYPO3_DLOG) {
                    GeneralUtility::sysLog(__FILE__ . ': ' . __LINE__ . ': autoVersionIdMap: ' . $tce->autoVersionIdMap,
                        'l10nmgr');
                    GeneralUtility::sysLog(__FILE__ . ': ' . __LINE__ . ': _flexFormDiffArray: ' . GeneralUtility::arrayToLogString($_flexFormDiffArray),
                        'l10nmgr');
                }
            }
            // Should be empty now - or there were more information in the incoming array than there should be!
            if (count($inputArray)) {
                debug($inputArray, 'These fields were ignored since they were not in the configuration:');
            }
            return $_flexFormDiffArray;
        } else {
            return false;
        }
    }

    /**
     * The Funktion checks if the Content elemnt is an FlexForm and Update the Import Array
     *
     * @param array $TCEmain_data
     * @return $TCEmain_data
     */
    function _formatForGridelementsFlexForm(&$TCEmain_data)
    {
        foreach ($TCEmain_data['tt_content'] as $uid => $fields) {
            /**
             * Check if Gridelements exist
             */
            $isGridElemnts = array_filter(array_keys($fields), function ($key) {
                return strpos($key, 'gridelements-') === 0;
            });
            if (count($isGridElemnts)) {
                /**
                 * Format Gridelements Form Field Names
                 */
                $gridElemnts = array();
                foreach ($isGridElemnts as $key => $value) {
                    $gridElemnts[] = str_replace('gridelements-', '', $value);
                }
                /**
                 * Get Reccord for FlexForm merge
                 */
                $translationRecord = BackendUtility::getRecordWSOL('tt_content', $uid);
                /**
                 * Match Flexform XML for Translation
                 */
                $flexform = GeneralUtility::xml2array($translationRecord['pi_flexform']);
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
    function _mergeGridelementsFlexFormValues($flexform, $field, $gridElemnts)
    {
        /**
         * Merge Values to Strukture
         */
        foreach ($flexform['data'] as $_key => $option) {

            foreach ($option['lDEF'] as $key => $value) {

                if (in_array($key, $gridElemnts)) {
                    $flexform['data'][$_key]['lDEF'][$key]['vDEF'] = $field['gridelements-' . $key];
                    // Unset old Arrays
                    unset($field['gridelements-' . $key]);
                }

            }
        }
        // Pack FlexForm to XML
        $flexformTools = GeneralUtility::makeInstance('TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools');
        $returnXML = $flexformTools->flexArray2Xml($flexform, TRUE);
        // Set new Array to Updater
        $field['pi_flexform'] = $flexformTools->flexArray2Xml($flexform, TRUE);
        // Return new Field Array for SQL import
        return $field;
    }
}
