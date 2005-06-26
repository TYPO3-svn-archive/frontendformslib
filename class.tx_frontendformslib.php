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
 * Base class for the 'frontendformslib' extension
 *
 * @author	Robert Lemke <robert@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   79: class tx_frontendformslib
 *  113:     function tx_frontendformslib(&$pObj)
 *  123:     function __construct(&$pObj)
 *  143:     function init()
 *
 *              SECTION: Main render functions
 *  163:     function renderWholeForm()
 *  175:     function renderCurrentStep()
 *  220:     function renderStepDisplay()
 *  245:     function renderStepsOverview()
 *
 *              SECTION: Sub-Rendering functions
 *  309:     function wrapWithForm($content)
 *  326:     function renderSubmitButtons()
 *
 *              SECTION: Field rendering functions
 *  366:     function getSingleLabel($fieldConf, $rawLabelOnly = FALSE)
 *  423:     function getSingleField($fieldConf)
 *  459:     function getSingleField_typeInput($fieldConf, $tcaFieldConf)
 *  485:     function getSingleField_typeText($fieldConf, $tcaFieldConf)
 *
 *              SECTION: Evaluation functions
 *  536:     function evaluate_allFields($fieldsValuesArr)
 *  547:     function evaluate_stepFields($step, &$fieldsValuesArr)
 *  573:     function evaluate_singleField($fieldConf, $value)
 *
 *              SECTION: Helper functions for internal use
 *  609:     function processIncomingData()
 *  675:     function implode_assoc_r ($inner_glue = "=", $outer_glue = "\n", $array = null, $keepOuterKey = false)
 *
 *              SECTION: Helper functions for external use
 *  709:     function createStepConf($fieldNames, $table='', $stepLabel='', $infoText='')
 *  765:     function getSessionData()
 *  775:     function destroySessionData()
 *
 * TOTAL FUNCTIONS: 21
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(t3lib_extMgm::extPath('lang','lang.php'));
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');


class tx_frontendformslib {
	var $extKey = 'frontendformslib';								// The extension key.
	var	$prefixId = 'tx_frontendformslib';

	var $localLangLabels = array (									// Locallang labels - you may change these references from outside
		'renderSubmitButtons_proceed' => 'LLL:EXT:frontendformslib/locallang.php:renderSubmitButtons_proceed',
		'renderSubmitButtons_back' => 'LLL:EXT:frontendformslib/locallang.php:renderSubmitButtons_back',
		'renderSubmitButtons_submit' => 'LLL:EXT:frontendformslib/locallang.php:renderSubmitButtons_submit',
		'renderSubmitButtons_cancel' => 'LLL:EXT:frontendformslib/locallang.php:renderSubmitButtons_cancel',
	);

	var $labelWraps = array (										// stdWraps for field labels, used in certain situations. The wraps will be applied in the order you see below:
		'fieldRequired' => '|&nbsp;<span style="color:red;">*</span> ',
		'fieldEvalError' => '',
	);

	var $conf = array();											// The general configuration. Set from outside via setConf() !
	var $steps = array();											// Steps (forms) configuration
	var $maxSubmitsPerSession = 1000;								// Maximum submits per frontend session (security measure)
	var $formAction = '';											// action of the form rendered with wrapWithForm()
	var $formMethod = 'POST';										// method of the form rendered with wrapWithForm()
	var $showNextButton = TRUE;										// By default the submit button for the next step (if exists) appears. You can set this to FALSE if certain conditions for proceeding are not met yet.
	var $showPreviousButton = TRUE;									// By default the submit button for the previous step (if exists) appears. You can set this to FALSE, eg. if you want to render that button yourself
	var $showCancelButton = TRUE;									// By default the submit button for cancellation  appears. You can set this to FALSE, eg. if you want to render that button yourself

	var $additionalHiddenFields= '';								// This (HTML) code will be included just before the closing </form> tag in all forms rendered by wrapWithForm(). Use this for defining additional hidden fields.
	var $sessionData;												// Current session data
	
		// Private variables:
	var $pObj;														// Reference to the parent object, a tslib_pibase, Set by constructor of this class.
	var $currentStep = 1;											// Current step number
	var $currentEvalErrors = array();								// An array of table/field identifiers (tablename|fieldname) which caused an evaluation error after the last submission.
	var $fieldTabIndex = 500;										// Current tabindex property for form fields. Will be incremented by 10 for each field.
	
	/**
	 * Constructor for PHP4 compability
	 *
	 * @param	object		$pObj: Reference to the parent object (a tslib_pibase)
	 * @return	mixed		result from the real constructor (if any)
	 */
	function tx_frontendformslib(&$pObj) {
		return $this->__construct($pObj);
	}

	/**
	 * Constructor, initializes basics
	 *
	 * @param	object		$pObj: Reference to the parent object (a tslib_pibase)
	 * @return	void
	 */
	function __construct(&$pObj) {
		global $TSFE;
		
		$this->pObj =& $pObj;

		$this->LANG = t3lib_div::makeInstance('language');
		$this->LANG->init($this->pObj->LLkey);

		if (is_object ($TSFE)) {
			$TSFE->includeTCA();
			if (is_object ($TSFE->fe_user)) {	// Only check this so it works for the unit test. Later on T3Unit should support real FE enviroment ..
				$this->sessionData = $TSFE->fe_user->getKey('ses', 'tx_frontendformslib');
			}
		}		

			// Add an icon to the labelWraps:
		$this->labelWraps['fieldEvalError'] = '<img '.t3lib_iconWorks::skinImg($pObj->backPath, 't3lib/gfx/required_h.gif').' style="margin: 2px;" alt="" title="" />|';
	}

	/**
	 * Init function. Always call this function after you have configured the formObj
	 * and before you use any data or render the form!
	 *
	 * @return	void
	 */
	function init() {
		$this->processIncomingData();
	}





	/****************************************
	 *
	 * Main render functions
	 *
	 ******************************************/

	/**
	 * Renders the whole frontend form containing the labels and fields of the current step and
	 * various submit buttons depending on the current situation.
	 *
	 * @return	string		HTML form containing a table of labels and fields
	 */
	function renderWholeForm() {
		$output = $this->renderCurrentStep();
		$output .= $this->renderSubmitButtons();

		return $this->wrapWithForm ($output);
	}

	/**
	 * Renders the current step containing the labels and fields of the current step
	 *
	 * @return	string		HTML output: labels and fields
	 */
	function renderCurrentStep() {
		$rows = array();

		switch ($this->steps[$this->currentStep]['type']) {
			case 'fields':
				$this->fieldTabIndex = 500;

				$rows[] = '<fieldset>';

				if (strlen ($this->steps[$this->currentStep]['label'])) {
					$rows[] = '<legend>'.$this->steps[$this->currentStep]['label'].'</legend>';
				}

				if (strlen ($this->steps[$this->currentStep]['infotext'])) {
					$rows[] = $this->steps[$this->currentStep]['infotext'];
				}

				foreach ($this->steps[$this->currentStep]['fields'] as $fieldConf) {
					$field = $this->getSingleField ($fieldConf);
					$label = $this->getSingleLabel ($fieldConf);
					if (intval($fieldConf['config']['tx_frontendformslib_swaphorizontally'])) {
						$rows[] = $field.$label.'<br />';
					} else {
						$rows[] = $label.$field.'<br />';
					}
				}

				$rows[] = '</fieldset>';

			break;
			case 'user':
				if (method_exists ($this->steps[$this->currentStep]['userobject'], $this->steps[$this->currentStep]['usermethod'])) {
					$rows[] = call_user_method ($this->steps[$this->currentStep]['usermethod'], $this->steps[$this->currentStep]['userobject'], $this->steps[$this->currentStep]['userconfig'], $this->steps[$this->currentStep]['userobject'], $this);
				}
			break;
		}
		return implode (chr(10), $rows);
	}

	/**
	 * Renders an HTML div container with number and label of the current step and how
	 * many steps are following
	 *
	 * @return	string		HTML output.
	 */
	function renderStepDisplay() {
		$stepsSpans = '';
		if (is_array ($this->steps)) {
			$stepTitle = $this->steps[$this->currentStep]['label'];
			$counter = 1;
			foreach ($this->steps as $stepNr => $stepConf) {
				$stepsSpans .= '<span class="tx-frontendformslib-steps-'.($this->currentStep == $stepNr ? 'active' : 'inactive').'">'.$this->steps[$stepNr]['label'].'</span>';
				$counter ++;
			}

			$output = '
				<div class="tx-frontendformslib-steps">
					'.$stepsSpans.'
				</div>
			';
		}
		return $output;
	}

	/**
	 * Renders an HTML table containing all information from the different steps.
	 *
	 * @return	string		HTML output.
	 */
	function renderStepsOverview() {

		$rows = array();

			// Generate a summary of all data from the different steps:
		foreach ($this->steps as $stepNr => $stepConf) {
				// Show all steps except the overview of course:
			if ($stepNr < $this->currentStep) {
					// If a userfunction was defined for rendering the overview part for this step, call the user function:
				if (is_array($stepConf['userfunctions']['renderstepsoverview'])) {
					if (method_exists ($stepConf['userfunctions']['renderstepsoverview']['userobject'], $stepConf['userfunctions']['renderstepsoverview']['usermethod'])) {
						$userConfig = array(
							'stepNr' => $stepNr,
							'stepConf' => $stepConf
						);
						$rows[] = call_user_method ($stepConf['userfunctions']['renderstepsoverview']['usermethod'], $stepConf['userfunctions']['renderstepsoverview']['userobject'], $userConfig, $stepConf['userfunctions']['renderstepsoverview']['userobject'], $this);
					}
				} else {
					$rows[] = '
						<fieldset>
							<legend>'.htmlspecialchars($stepConf['label']).'</legend>';
					$counter = 1;
					if (is_array ($stepConf['fields'])) {
						foreach ($stepConf['fields'] as $fieldConf) {
							$rows[] = '
								<div class="tx-frontendformslib-stepsoverview-row tx-frontendformslib-stepsoverview-row-'.($counter%2 ? 'even':'odd').'">
									'.$this->getSingleLabel($fieldConf).
									 htmlspecialchars($this->sessionData['data'][$fieldConf['table']][$fieldConf['name']]).'
								</div>
							';
							$counter ++;
						}
					}
					$rows[] = '
						</fieldset>
					';
				}
			}
		}

		$output .= '
			<div class="tx-frontendformslib-stepsoverview">
				'.implode (chr(10), $rows).'
			</div>
		';

		return $output;
	}





	/****************************************
	 *
	 * Sub-Rendering functions
	 *
	 ******************************************/

	/**
	 * Wraps the given content with an HTML form tag
	 *
	 * @param	string		$content: Content to be wrapped
	 * @return	string		The given string wrapped by a configured form tag
	 */
	function wrapWithForm($content) {
		$output = '
			<form action="'.$this->formAction.'" method="'.$this->formMethod.'" enctype="multipart/form-data" class="tx-frontendformslib-form">
				'.$content.'
				'.$this->additionalHiddenFields.'
			</form>
		';

		return $output;
	}

	/**
	 * Renders one or more buttons (previous, next, cancel) depending on the
	 * current settings and situation.
	 *
	 * @return	string		HTML button(s)
	 */
	function renderSubmitButtons() {
		$output = '';

		if ($this->currentStep < count($this->steps) && count($this->steps) > 1 && $this->showNextButton) {
			$output .= '<input style="display:none;" type="submit" name="'.$this->prefixId.'[submitproceed]" value="'.htmlspecialchars($this->LANG->sL($this->localLangLabels['renderSubmitButtons_proceed'])).'" tabindex="1000" />';
			$output .= '<input type="hidden" name="'.$this->prefixId.'[currentstep]" value="'.$this->currentStep.'" />';
		}
		if ($this->showCancelButton) {
			$output .= '<input style="float:left;" type="submit" name="'.$this->prefixId.'[submitcancel]" value="'.htmlspecialchars($this->LANG->sL($this->localLangLabels['renderSubmitButtons_cancel'])).'" tabindex="1030" />';
		}
		if ($this->currentStep > 1 && $this->showPreviousButton) {
			$output .= '<input style="float:left;" type="submit" name="'.$this->prefixId.'[submitback]" value="'.htmlspecialchars($this->LANG->sL($this->localLangLabels['renderSubmitButtons_back'])).'" tabindex="1020" />';
			$output .= '<input type="hidden" name="'.$this->prefixId.'[currentstep]" value="'.$this->currentStep.'" />';
		}
		if ($this->currentStep == count($this->steps) && $this->showNextButton) {
			$output .= '<input style="float:left;" type="submit" name="'.$this->prefixId.'[submitsubmit]" value="'.htmlspecialchars($this->LANG->sL($this->localLangLabels['renderSubmitButtons_submit'])).'" tabindex="1000" />';
		}
		if ($this->currentStep < count($this->steps) && count($this->steps) > 1 && $this->showNextButton) {
			$output .= '<input style="float:left;" type="submit" name="'.$this->prefixId.'[submitproceed]" value="'.htmlspecialchars($this->LANG->sL($this->localLangLabels['renderSubmitButtons_proceed'])).'" tabindex="1010" />';
		}
		return '<div class="tx-frontendformslib-submitbuttons">'.$output.'</div>';
	}





	/****************************************
	 *
	 * Field rendering functions
	 *
	 ******************************************/

	/**
	 * Returns the label of a single field wrapped into label tags. In 'tca' mode the label will be taken from
	 * the TCA configuration of that field. It may override this label by specifiying a property 'label' in
	 * the field configuration.
	 *
	 * @param	array		$fieldconf: The configuration for that field
	 * @param	boolean		$rawLabelOnly: If set to TRUE, the label won't be wrapped with <label> tags
	 * @return	string		Label for the specified field ready for insertion into an HTML form
	 */
	function getSingleLabel($fieldConf, $rawLabelOnly = FALSE) {
		global $TCA;

		$output = '';
		$icons = '';

		switch ($fieldConf['mode']) {
			case 'tca':
				if (isset($fieldConf['label'])) {
					$label = $this->LANG->sL ($fieldConf['label']);
				} else {
						// Load TCA configuration for the given field:
					$table = isset ($fieldConf['table']) ? $fieldConf['table'] : $this->conf['defaultTable'];
					t3lib_div::loadTCA($table);
					$tcaFieldConf = $TCA[$table]['columns'][$fieldConf['name']]['config'];
					$label =  $this->LANG->sL($TCA[$table]['columns'][$fieldConf['name']]['label']);
				}
			break;
			case 'manual':
				$table = $fieldConf['table'];
				$tcaFieldConf = $fieldConf['config'];
				$label = $this->LANG->sL ($fieldConf['label']);
			break;
			default:
				$output = $fieldConf['name'];
		}

		$id = isset ($fieldConf['overridename']) ? $fieldConf['overridename'] : $this->prefixId.'[data]['.$fieldConf['table'].']['.$fieldConf['name'].']';
		if (!$rawLabelOnly) {
			$label = htmlspecialchars($label);

				// Render required icon if neccesary:
			$label = stristr($tcaFieldConf['eval'], 'required') ? $this->pObj->cObj->wrap($label, $this->labelWraps['fieldRequired']) : $label;

				// Render evaluation error wrap if neccessary:
			$label = isset($this->currentEvalErrors[$table.'|'.$fieldConf['name']]) ? $this->pObj->cObj->wrap($label, $this->labelWraps['fieldEvalError']) : $label;

				// Render the label:
			if (intval($fieldConf['config']['tx_frontendformslib_swaphorizontally'])) {
				$output .= '<label for="'.$id.'" class="tx-frontendformslib-label tx-frontendformslib-label-switched">'.$label.'</label>';
			} else {
				$output .= '<label for="'.$id.'" class="tx-frontendformslib-label tx-frontendformslib-label-normal">'.$label.'</label>';
			}
		} else {
			$output = $label;
		}

		return $output;
	}

	/**
	 * Returns a single field
	 *
	 * @param	array		$fieldconf: The configuration for that field
	 * @return	string		HTML representation of the given field
	 */
	function getSingleField($fieldConf) {
		global $TCA;

		$output = '';

			// Determine the configuration mode: Is the field defined in the TCA or does it use a mockup TCA config?
		switch ($fieldConf['mode']) {
			case 'tca':
					// Load TCA configuration for the given field:
				$table = isset ($fieldConf['table']) ? $fieldConf['table'] : $this->conf['defaultTable'];
				t3lib_div::loadTCA($table);
				$tcaFieldConf = $TCA[$table]['columns'][$fieldConf['name']]['config'];
			break;
			case 'manual':
					// Load configuration from fieldConf:
				$table = $fieldConf['table'];
				$tcaFieldConf = $fieldConf['config'];
			break;
		}

			// Render the field according to its type:
		switch ($tcaFieldConf['type']) {
			case 'input': $output .= $this->getSingleField_typeInput ($fieldConf, $tcaFieldConf); break;
			case 'text': $output .= $this->getSingleField_typeText ($fieldConf, $tcaFieldConf); break;
			case 'select': $output .= $this->getSingleField_typeSelect ($fieldConf, $tcaFieldConf); break;
		}

		return $output;
	}

	/**
	 * Renders a single field of the type "input"
	 *
	 * @param	array		$fieldConf: The formslib specific field configuration array
	 * @param	array		$tcaFieldConf: The TCA configuration
	 * @return	string		HTML representation of the field
	 */
	function getSingleField_typeInput($fieldConf, $tcaFieldConf) {

			// Add the various parameters according to TCA configuration:
		$params = array(
			'type' => stristr($tcaFieldConf['eval'], 'password') ? 'password' : 'text',
			'name' => isset ($fieldConf['overridename']) ? $fieldConf['overridename'] : $this->prefixId.'[data]['.$fieldConf['table'].']['.$fieldConf['name'].']',
			'id' => isset ($fieldConf['overrideid']) ? $fieldConf['overrideid'] : $this->prefixId.'[data]['.$fieldConf['table'].']['.$fieldConf['name'].']',
			'value' => htmlspecialchars($this->sessionData['data'][$fieldConf['table']][$fieldConf['name']]),
			'size' => ($tcaFieldConf['size'] ? $tcaFieldConf['size'] : 30),
			'maxlength' => ($fieldTCACfon['max'] ? $tcaFieldConf['max'] : 255),
			'tabindex' => ($this->fieldTabIndex += 10),
			'class' => 'tx-frontendformslib-field tx-frontendformslib-field-' . (intval($fieldConf['config']['tx_frontendformslib_swaphorizontally']) ? 'switched': 'normal'),
		);

		$output = '<input '.$this->implode_assoc_r('="', '" ', $params).'" />';

		return $output;
	}

	/**
	 * Renders a single field of the type "text"
	 *
	 * @param	array		$fieldConf: The formslib specific field configuration array
	 * @param	array		$tcaFieldConf: The TCA configuration
	 * @return	string		HTML representation of the field
	 */
	function getSingleField_typeText($fieldConf, $tcaFieldConf) {

			// Add the various parameters according to TCA configuration:
		$params = array(
			'name' => isset ($fieldConf['overridename']) ? $fieldConf['overridename'] : $this->prefixId.'[data]['.$fieldConf['table'].']['.$fieldConf['name'].']',
			'id' => isset ($fieldConf['overrideid']) ? $fieldConf['overrideid'] : $this->prefixId.'[data]['.$fieldConf['table'].']['.$fieldConf['name'].']',
			'rows' => ($tcaFieldConf['rows'] ? $tcaFieldConf['rows'] : 5),
			'cols' => ($tcaFieldConf['cols'] ? $tcaFieldConf['cols'] : 30),
			'maxlength' => ($fieldTCAConf['max'] ? $tcaFieldConf['max'] : 255),
			'tabindex' => ($this->fieldTabIndex += 10),
			'class' => 'tx-frontendformslib-field tx-frontendformslib-field-' . (intval($fieldConf['config']['tx_frontendformslib_swaphorizontally']) ? 'switched': 'normal'),
		);

		$output = '<textarea '.$this->implode_assoc_r('="', '" ', $params).'" />'.htmlspecialchars($this->sessionData['data'][$fieldConf['table']][$fieldConf['name']]).'</textarea>';

		return $output;
	}

	/**
	 * Renders a single field of the type "select"
	 *
	 * @param	array		$fieldConf: The formslib specific field configuration array
	 * @param	array		$tcaFieldConf: The TCA configuration
	 * @return	string		HTML representation of the field
	 * @todo	currently this only renders a very basic selector box! This should be rendered by TCEforms in TYPO3 5.0 anyways, so no motivation for developing this further ...
	 */
	function getSingleField_typeSelect($fieldConf, $tcaFieldConf) {

		$itemsArr = is_array ($tcaFieldConf['items']) ? $tcaFieldConf['items'] : array();
		if (isset ($tcaFieldConf['itemsProcFunc'])) {
			$params=array();
			$params['items'] = &$itemsArr;
				// NOTE: Some important parameters are missing (table, fieldname etc.), so this won't work with every userfunction like expected. 
			t3lib_div::callUserFunction($tcaFieldConf['itemsProcFunc'], $params, $this);
		}

			// Create list of options:
		$optionsArr = array();
		foreach ($itemsArr as $keyValueArr) {
			if (is_array ($keyValueArr)) {
				$selected = ($this->sessionData['data'][$fieldConf['table']][$fieldConf['name']] == $keyValueArr[1]) ? ' selected="selected"' : '';
				$optionsArr[] = '<option value="'.htmlspecialchars($keyValueArr[1]).'"'.$selected.'>'.htmlspecialchars($this->LANG->sL($keyValueArr[0])).'</option>';	
			}
		}	

			// Add the various parameters according to TCA configuration:
		$params = array(
			'name' => isset ($fieldConf['overridename']) ? $fieldConf['overridename'] : $this->prefixId.'[data]['.$fieldConf['table'].']['.$fieldConf['name'].']',
			'id' => isset ($fieldConf['overrideid']) ? $fieldConf['overrideid'] : $this->prefixId.'[data]['.$fieldConf['table'].']['.$fieldConf['name'].']',
			'tabindex' => ($this->fieldTabIndex += 10),
			'class' => 'tx-frontendformslib-field tx-frontendformslib-field-' . (intval($fieldConf['config']['tx_frontendformslib_swaphorizontally']) ? 'switched': 'normal'),
		);

		$output = '
			<select '.$this->implode_assoc_r('="', '" ', $params).'">
				'.implode(chr(10), $optionsArr).'
			</select>
		';

		return $output;
	}

	/**
	 * Renders a single field of the type "select", subtype "single"
	 *
	 * @param	array		$fieldConf: The formslib specific field configuration array
	 * @param	array		$tcaFieldConf: The TCA configuration
	 * @return	string		HTML representation of the field
	 */
	function getSingleField_typeSelect_single ($fieldConf, $tcaFieldConf) {

			// Add the various parameters according to TCA configuration:
		$params = array(
			'name' => isset ($fieldConf['overridename']) ? $fieldConf['overridename'] : $this->prefixId.'[data]['.$fieldConf['table'].']['.$fieldConf['name'].']',
			'id' => isset ($fieldConf['overrideid']) ? $fieldConf['overrideid'] : $this->prefixId.'[data]['.$fieldConf['table'].']['.$fieldConf['name'].']',
			'rows' => ($tcaFieldConf['rows'] ? $tcaFieldConf['rows'] : 5),
			'cols' => ($tcaFieldConf['cols'] ? $tcaFieldConf['cols'] : 30),
			'maxlength' => ($fieldTCACfon['max'] ? $tcaFieldConf['max'] : 255),
			'tabindex' => ($this->fieldTabIndex += 10),
			'class' => 'tx-frontendformslib-field tx-frontendformslib-field-' . (intval($fieldConf['config']['tx_frontendformslib_swaphorizontally']) ? 'switched': 'normal'),
		);

		$output = '<textarea '.$this->implode_assoc_r('="', '" ', $params).'" />'.htmlspecialchars($this->sessionData['data'][$fieldConf['table']][$fieldConf['name']]).'</textarea>';

		return $output;
	}

	// 	function getSingleField_typeCheck($table,$field,$row,&$PA)
	// 	function getSingleField_typeRadio($table,$field,$row,&$PA)
	// 	function getSingleField_typeSelect($table,$field,$row,&$PA)
	// 	function getSingleField_typeSelect_single($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
	// 	function getSingleField_typeSelect_checkbox($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
	//	function getSingleField_typeSelect_singlebox($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
	// 	function getSingleField_typeSelect_multiple($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
	// 	function getSingleField_typeGroup($table,$field,$row,&$PA)
	// 	function getSingleField_typeNone($table,$field,$row,&$PA)
	// 	function getSingleField_typeNone_render($config,$itemValue)
	//	function getSingleField_typeFlex($table,$field,$row,&$PA)
	// 	function getSingleField_typeFlex_langMenu($languages,$elName,$selectedLanguage,$multi=1)
	//	function getSingleField_typeFlex_sheetMenu($sArr,$elName,$sheetKey)
	//	function getSingleField_typeFlex_draw($dataStruct,$editData,$cmdData,$table,$field,$row,&$PA,$formPrefix='',$level=0,$tRows=array())
	//	function getSingleField_typeUnknown($table,$field,$row,&$PA)
	//	function getSingleField_typeUser($table,$field,$row,&$PA)





	/****************************************
	 *
	 * Evaluation functions
	 *
	 ******************************************/

	/**
	 * Evaluates all fields
	 *
	 * @param	array		&$fieldsValuesArr: An array of tables with an array of field names and values array ('table' => array('field' => 'value', 'table2' => ...). Will contain the evaluated, possibly modified values.
	 * @return	mixed		TRUE if all fields contain allowed values, array of tables/field identifiers which caused errors (identifier: tablename|fieldname)
	 */
	function evaluate_allFields(&$fieldsValuesArr) {
		$result = array();

			// Evaluate all fields:
		foreach ($this->steps as $stepNr => $stepConf) {
			$evalRes = $this->evaluate_stepFields ($stepNr, $fieldsValuesArr);
			if (is_array($evalRes)) {
				$result = array_merge ($result, $evalRes);	
			}
		}

			// Store the list of tables/fields which didn't validate:
		$this->currentEvalErrors = $result;

		return (count($result) ? $result : TRUE);
	}

	/**
	 * Evaluates all fields of a certain step
	 *
	 * @param	integer		$step: Step number
	 * @param	array		&$fieldsValuesArr: An array of tables with an array of field names and values array ('table' => array('field' => 'value', 'table2' => ...). Will contain the evaluated, possibly modified values.
	 * @return	mixed		TRUE if all fields contain allowed values, array of tables/field identifiers which caused errors (identifier: tablename|fieldname)
	 */
	function evaluate_stepFields($step, &$fieldsValuesArr) {
		$result = array();
			// Evaluate each field of the given step number:
		if (is_array ($this->steps[$step]['fields'])) {			
			foreach ($this->steps[$step]['fields'] as $fieldKey => $fieldConf) {
				$evalRes = $this->evaluate_singleField ($fieldConf, $fieldsValuesArr[$fieldConf['table']][$fieldConf['name']]);
				if (isset ($evalRes['value']) && $evalRes['value'] == $fieldsValuesArr[$fieldConf['table']][$fieldConf['name']]) {
					$fieldsValuesArr[$fieldConf['table']][$fieldConf['name']] = $evalRes['value'];
				} else {
					$result[$fieldKey] = 1;
				}
			}
	
				// Store the list of tables/fields which didn't validate:
			$this->currentEvalErrors = $result;
		}
		return (count($result) ? $result : TRUE);
	}

	/**
	 * Evaluates a single field
	 *
	 * @param	array		$fieldConf: The configuration of the field to be checked
	 * @param	mixed		$value: The value to check
	 * @return	array		If $value was valid it will be returned (possibly modified) as the value of key 'value'. If $value was not valid, the value of key 'value' will be NULL.
	 */
	function evaluate_singleField($fieldConf, $value) {
		global $TCA;

		$table = isset ($fieldConf['table']) ? $fieldConf['table'] : $this->conf['defaultTable'];
		switch ($fieldConf['mode']) {
			case 'tca':
					// Load TCA configuration for the given field:
				t3lib_div::loadTCA($table);
				$tcaFieldConf = $TCA[$table]['columns'][$fieldConf['name']]['config'];
			break;
			case 'manual':
					// Load configuration from fieldConf:
				$tcaFieldConf = $fieldConf['config'];
			break;
		}
		if (isset ($tcaFieldConf['userevalmethod']) && is_object($tcaFieldConf['userevalobject'])) {
			$result = call_user_method ($tcaFieldConf['userevalmethod'], $tcaFieldConf['userevalobject'], $fieldConf, $value, $this);
		} else {
				// Create an instance of TCEmain and check the value:
			$TCEmain = t3lib_div::makeInstance ('t3lib_tcemain');
			$result = $TCEmain->checkValue_SW ($res, $value, $tcaFieldConf, $table, -1, null, null, -1, null, null, null, null);
		}
		return $result;
	}





	/****************************************
	 *
	 * Helper functions for internal use
	 *
	 ******************************************/

	/**
	 * Processes incoming POST data and stores it into the current FE session
	 *
	 * @return	void
	 */
	 function processIncomingData() {
	 	global $TSFE;

	 		// fetch incoming POST data and restore current session data:
	 	$incomingData = t3lib_div::_GP($this->prefixId);
	 	
	 	if (!is_array ($this->sessionData)) {
	 		$this->sessionData = array (
	 			'data' => array(),
	 			'submitCount' => 0,
	 			'currentStep' => 1,
				'submissionId' => md5(t3lib_div::getIndpEnv('REMOTE_ADDR').microtime()),
	 		);
	 	}
	 	if (!is_array ($incomingData['data'])) {
	 		$incomingData['data'] = array();
	 	}

	 		// To avoid submit spamming attacks we restrict each session to a certain amount of submits:
		$this->sessionData['submitCount']++;
		if ($this->sessionData['submitCount'] < $this->maxSubmitsPerSession) {

				// Now proceed according to the type of submission:
			if (isset ($incomingData['submitcancel'])) {
				$this->submitType = 'cancel';
				$this->sessionData['data'] = array();
				$this->sessionData['currentStep'] = 1;
					// Reset submission id. This is used for making sure that a submission is only processed once. It must be handled by the extension
					// which processes the form:
				$this->sessionData['submissionId'] = md5(t3lib_div::getIndpEnv('REMOTE_ADDR').microtime());
			} elseif (isset ($incomingData['submitproceed'])) {
				$this->submitType = 'proceed';
				$this->sessionData['data'] = (t3lib_div::array_merge_recursive_overrule ($this->sessionData['data'], $incomingData['data']));

				$evalResult = $this->evaluate_stepFields ($incomingData['currentstep'], $this->sessionData['data']);
				if ($evalResult === TRUE) {
					$this->sessionData['currentStep']= $incomingData['currentstep'] + 1;
				} else {
					$this->submitType = 'evalerror';
				}
			} elseif (isset ($incomingData['submitback'])) {
				$this->submitType = 'back';
				$this->sessionData['data'] = (t3lib_div::array_merge_recursive_overrule ($this->sessionData['data'], $incomingData['data']));
				$this->sessionData['currentStep']= $incomingData['currentstep'] - 1;
			} elseif (isset ($incomingData['submitsubmit'])) {
				$this->submitType = 'submit';
				$this->sessionData['data'] = (t3lib_div::array_merge_recursive_overrule ($this->sessionData['data'], $incomingData['data']));
				$evalResult = $this->evaluate_allFields ($this->sessionData['data']);
				if ($evalResult !== TRUE) {
					$this->submitType = 'evalerror';
				}
			}
		}

		$TSFE->fe_user->setKey ('ses', $this->prefixId, $this->sessionData);
		$this->currentStep = $this->sessionData['currentStep'];
	 }

	/**
	 * Implodes an associative array. Similar script found on php.net
	 *
	 * @param	string		$inner_glue: Glue between key and value
	 * @param	string		$outer_glue: Glue between key-value sets
	 * @param	array		$array: The array to implode
	 * @param	boolean		$keepOuterKey: If set to TRUE, keys which have a value of type array will also be added.
	 * @return	string		Imploded array
	 */
	function implode_assoc_r ($inner_glue = "=", $outer_glue = "\n", $array = null, $keepOuterKey = false) {
		$output = array();
		foreach($array as $key => $item ) {
			if (is_array ($item)) {
				if ($keepOuterKey) {
					$output[] = $key;
				}
				$output[] = implode_assoc_r ($inner_glue, $outer_glue, $item, $keepOuterKey);
			} else {
				$output[] = $key . $inner_glue . $item;
			}
		}
		return implode($outer_glue, $output);
	}





	/****************************************
	 *
	 * Helper functions for external use
	 *
	 ******************************************/

	/**
	 * Creates a configuration array for one step.
	 *
	 * @param	string		$fieldNames: A comma separated list of field names
	 * @param	string		$table: The table name for all fields. Leaving the table empty means the default table (if configured) will be used later on
	 * @param	string		$stepLabel: A label (header) for the step. Optional. You may use language splits (LLL:EXT:yourext/locallang.php etc.)
	 * @param	string		$infoText: Some text to be shown just before the form. Won't be htmlspecialchared, make sure that it doesn't contain bad HTML code!
	 * @return	array		step configuration array
	 */
	function createStepConf($fieldNames, $table='', $stepLabel='', $infoText='') {
		global $TCA;

		$fieldNamesArr = t3lib_div::trimExplode (',', $fieldNames);
		$table = strlen ($table) ? $table : $this->conf['defaultTable'];
		$stepConf = array();

		if (is_array ($fieldNamesArr)) {
			$stepConf['label'] = $this->LANG->sL ($stepLabel);
			$stepConf['type'] = 'fields';
			$stepConf['infotext'] = $infoText;
			foreach ($fieldNamesArr as $fieldName) {

					// If the fieldName begins with an asterisk, it is a required field, regardless of the original TCA configuration. We make copy the TCA configuration and make a manual field of it:
				if (substr ($fieldName, 0, 1) == '*') {
					$fieldName = substr ($fieldName, 1);

						// Load TCA configuration for the given field:
					t3lib_div::loadTCA($table);

						// Create a manual setup and copy the original TCA configuration:
					$stepConf['fields'][$table.'|'.$fieldName] = array (
						'name' => $fieldName,
						'mode' => 'manual',
						'table' => $table,
						'label' => $TCA[$table]['columns'][$fieldName]['label'],
						'config' => $TCA[$table]['columns'][$fieldName]['config'],
					);

						// Add "required" to the eval flags:
					$evalArr = t3lib_div::trimExplode(',', $TCA[$table]['columns'][$fieldName]['config']['eval'], 1);
					$evalArr[] = 'required';
					$eval = implode (',', t3lib_div::uniqueArray ($evalArr));
					$stepConf['fields'][$table.'|'.$fieldName]['config']['eval'] = $eval;

				} else {
						// Create a normal TCA field setup:
					$stepConf['fields'][$table.'|'.$fieldName] = array (
						'name' => $fieldName,
						'mode' => 'tca',
						'table' => $table,
					);
				}
			}
		}

		return $stepConf;
	}

	/**
	 * Destroys the session data for the current form. Call this method if you'd like to discard the
	 * form data after a successful submission of the form or if submission should be cancelled.
	 *
	 * @return	void;
	 */
	function destroySessionData() {
		global $TSFE;

		$TSFE->fe_user->setKey ('ses', 'tx_frontendformslib', '');
		unset ($this->sessionData);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/frontendformslib/class.tx_frontendformslib.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/frontendformslib/class.tx_frontendformslib.php']);
}

?>