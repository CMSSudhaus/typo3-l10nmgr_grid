<?php
if (!defined ('TYPO3_MODE')) {
  die ('Access denied.');
}

/**
 * Export Override
 */
// XClass Extend class Load
$TYPO3_CONF_VARS['BE']['XCLASS']['ext/l10nmgr/models/tools/class.tx_l10nmgr_tools.php'] = t3lib_extMgm::extPath($_EXTKEY).'models/class.ux_tx_l10nmgr_tools.php';
// Set New Class to Autoload
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['tx_l10nmgr_tools'] = array(
 'className' => 'ux_tx_l10nmgr_tools'
);
/**
 * Import Override
 */
 // XClass Extend class Load
$TYPO3_CONF_VARS['BE']['XCLASS']['ext/l10nmgr/models/class.tx_l10nmgr_l10nBaseService.php'] = t3lib_extMgm::extPath($_EXTKEY).'models/class.ux_tx_l10nmgr_l10nBaseService.php';
// Set New Class to Autoload
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['tx_l10nmgr_l10nBaseService'] = array(
 'className' => 'ux_tx_l10nmgr_l10nBaseService'
);
?>
