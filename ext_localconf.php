<?php
if (!defined ('TYPO3_MODE')) {
  die ('Access denied.');
}

/**
 * Export Override
 */
// Set New Class to Autoload
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['tx_l10nmgr_tools'] = array(
 'className' => 'WebKonInternetagentur\L10nmgrGrid\Model\Tools\Tools'
);

/**
 * Import Override
 */
// Set New Class to Autoload
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['tx_l10nmgr_l10nBaseService'] = array(
 'className' => 'WebKonInternetagentur\L10nmgrGrid\Model\L10nBaseService'
);
?>
