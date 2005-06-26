<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Robert Lemke (robert@typo3.org)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Example plugin for the frontendformslib
 *
 * @author	Robert Lemke <robert@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   51: class tx_frontendformslib_pi1 extends tslib_pibase
 *   64:     function main($content,$conf)
 *
 *              SECTION: Main render funtions for different examples
 *  107:     function renderExample_SimpleFormWithTCAFields()
 *  144:     function renderExample_TwoStepsFormWithTCAAndEval()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('frontendformslib').'class.tx_frontendformslib.php');

t3lib_extMgm::isLoaded ('tt_address');

class tx_frontendformslib_pi1 extends tslib_pibase {

	var $prefixId = 'tx_frontendformslib_pi1';						// Same as class name
	var $scriptRelPath = 'pi1/class.tx_frontendformslib_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'frontendformslib';								// The extension key.

	/**
	 * The mandatory main function. Calls the different sub functions for rendering a certain example
	 *
	 * @param	string		$content: Pre-rendered content (not used)
	 * @param	array		$conf: The plugin configuration (TypoScript)
	 * @return	string		HTML output
	 */
	function main($content,$conf)	{
		$this->conf = $conf;													// Make configuration available for all methods in this class
		$this->pi_setPiVarDefaults(); 											// Set default piVars from TS

		$content = '
			<form action="" method="get">
				Select example:
				<select name="tx_frontendformslib_pi1[example]">
					<option value="1"'.($this->piVars['example'] == 1 ? ' selected="selected"' : '').'>Simple form with $TCA fields</option>
					<option value="2"'.($this->piVars['example'] == 2 ? ' selected="selected"' : '').'>Two-steps form with $TCA fields and evaluation</option>
				</select>
				<input type="submit" value="go!" />
			</form>
		';

			// Jump to the different mode rendering functions:
		switch ($this->piVars['example']) {
			case '1' :
				$content .= $this->renderExample_SimpleFormWithTCAFields();
			break;
			case '2':
				$content .= $this->renderExample_TwoStepsFormWithTCAAndEval();
			break;
		}

		return $content;
	}





	/*******************************************
	 *
	 * Main render funtions for different examples
	 *
	 *******************************************/

	/**
	 * Simple form with $TCA fields
	 *
	 * @return	void
	 */
	function renderExample_SimpleFormWithTCAFields() {

				// Create the frontend form object. We don't use t3lib_div::makeInstance because we have to
				// pass a parameter to the constructor.
		$className = t3lib_div::makeInstanceClassName('tx_frontendformslib');
		$formObj = new $className ($this);

			// Generate configuration for a single step displaying certain fields of tt_address:
		$formObj->steps[1] = $formObj->createStepConf('name,address,zip,city', 'tt_address', 'Your address', 'Please enter your personal address:<br /><br />');

			// Initialize the form object. This will process and evaluate incoming data and must
			// be done between configuring and rendering the form:
		$formObj->init();

			// Check if the form has been submitted:
		if ($formObj->submitType == 'submit') {

				// Output submitted data:
			$output .= 'This is the data you submitted:<br /><br />' . t3lib_div::view_array ($formObj->sessionData['data']);

				// Destroy session data for our submitted form:
			$formObj->destroySessionData();

		} else {

				// Form has not yet been submitted, so render the form:
			$output = $formObj->renderWholeForm();
		}

		return $output;
	}

	/**
	 * Two-steps form with $TCA fields and evaluation
	 *
	 * @return	void
	 */
	function renderExample_TwoStepsFormWithTCAAndEval() {

				// Create the frontend form object. We don't use t3lib_div::makeInstance because we have to
				// pass a parameter to the constructor.
		$className = t3lib_div::makeInstanceClassName('tx_frontendformslib');
		$formObj = new $className ($this);

			// Generate configuration for a single step displaying certain fields of tt_address:
		$formObj->steps[1] = $formObj->createStepConf('username,password,email', 'fe_users', 'Basic data', 'Please enter some basic data:<br /><br />');
		$formObj->steps[2] = $formObj->createStepConf('*name,address,telephone,fax', 'fe_users', 'Your address', 'Please enter some more information, entering a name is required:<br /><br />');

			// Initialize the form object. This will process and evaluate incoming data and must
			// be done between configuring and rendering the form:
		$formObj->init();

			// Check if the form has been submitted:
		if ($formObj->submitType == 'submit') {

				// Output submitted data:
			$output .= 'This is the data you submitted:<br /><br />' . t3lib_div::view_array ($formObj->sessionData['data']);

				// Destroy session data for our submitted form:
			$formObj->destroySessionData();

		} else {

				// Form has not yet been submitted, so render the form:
			$output = $formObj->renderStepDisplay();
			$output .= $formObj->renderWholeForm();
		}

		return $output;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/frontendformslib/pi1/class.tx_frontendformslib_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/frontendformslib/pi1/class.tx_frontendformslib_pi1.php']);
}

?>