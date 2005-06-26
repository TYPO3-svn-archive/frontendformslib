<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// Add our example plugin: 
t3lib_extMgm::addPlugin(Array('Frontend forms example plugin', $_EXTKEY.'_pi1'));

?>