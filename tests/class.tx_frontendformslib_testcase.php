<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Robert Lemke (robert@typo3.org)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * @author	Robert Lemke <robert@typo3.org>
 */

require_once (t3lib_extMgm::extPath ('frontendformslib').'class.tx_frontendformslib.php');
require_once 'PHPUnit2/Framework/TestCase.php';

class tx_frontendformslib_testcase extends PHPUnit2_Framework_TestCase {

	private	$frontendFormsObj;

	public function __construct ($name) {
		parent::__construct ($name);
	}
	
	/**
	 * Sets up fixtures etc.
	 * 
	 * @return		void
	 * @access		public 
	 */
	
	public function setUp() {
		$this->frontendFormsObj = new tx_frontendformslib();
		$this->frontendFormsObj->steps = array (
			1 => array (
				'label' => 'Normal step',
				'type' => 'fields',
				'infotext' => 'Some dummy text',
				'fields' => array (
					'tt_content|imagewidth' => array (
						'name' => 'imagewidth',
						'mode' => 'tca',
						'table' => 'tt_content',
					),
				),
			),
			2 => array (
				'label' => 'Step with user method',
				'type' => 'user',
				'infotext' => 'Some dummy text',
				'fields' => array (
					'tx_someextensionkey_virtual|firstfield' => array (
						'name' => 'firstfield',
						'mode' => 'manual',
						'table' => 'tx_someextensionkey_virtual',
						'config' => array (
							'type' => 'input',
							'eval' => 'firstfieldeval',
							'userevalobject' => &$this,
							'userevalmethod' => 'userEvalMethod',
						),
					),
					'tx_someextensionkey_virtual|secondfield' => array (
						'name' => 'secondfield',
						'mode' => 'manual',
						'table' => 'tx_someextensionkey_virtual',
						'config' => array (
							'type' => 'input',
							'eval' => 'secondfieldeval',
							'userevalobject' => &$this,
							'userevalmethod' => 'userEvalMethod',
						),
					),
				),
				'userobject' => &$this,
				'usermethod' => 'userFormRenderMethod',
			)
		);
	}

	/**
	 * Checks evaluation of a field defined via TCA by calling evaluate_stepFields 
	 * 
	 * @return		void
	 * @access		public 
	 */
	public function test_evaluateStepFields_tcaFields() {
		$fieldConfWrongValueType = array ('tt_content' => array ('imagewidth' => 'abb'));				
		$fieldConfRightValueType = array ('tt_content' => array ('imagewidth' => 150));				

		$result = $this->frontendFormsObj->evaluate_stepFields (1, $fieldConfWrongValueType);
		self::AssertTrue (is_array ($result) && $result['tt_content|imagewidth'] == 1, 'evaluate_stepFields did not return the correct result while checking a TCA defined field "imagewidth" with wrong type');	

		$result = $this->frontendFormsObj->evaluate_stepFields (1, $fieldConf);
		self::AssertTrue ($result === TRUE, 'evaluate_stepFields did not return the correct result while checking a TCA defined field "imagewidth" with correct type');	
	}
	
	/**
	 * Checks evaluation of virtual fields, not defined via TCA, by calling evaluate_stepFields 
	 * 
	 * @return		void
	 * @access		public 
	 */
	public function test_evaluateStepFields_virtualFields() {
		$fieldConfWrongValueTypes = array (
			'tx_someextensionkey_virtual' => array (
				'firstfield' => 'firstvalue',
				'secondfield' => 'wrongsecondvalue',
			),
		);				
		$fieldConfRightValueTypes = array (
			'tx_someextensionkey_virtual' => array (
				'firstfield' => 'firstvalue',
				'secondfield' => 'secondvalue',
			),
		);				

		$result = $this->frontendFormsObj->evaluate_stepFields (2, $fieldConfRightValueTypes);
		self::AssertTrue ($result === TRUE, 'evaluate_stepFields did not return the correct result while checking a virtual field which should be okay');	

		$result = $this->frontendFormsObj->evaluate_stepFields (2, $fieldConfWrongValueTypes);
		self::AssertTrue ((is_array ($result) && isset ($result['tx_someextensionkey_virtual|secondfield']) && count ($result) == 1), 'evaluate_stepFields did not return the correct result while checking a virtual field which is not okay');
	}

	public function test_evaluateSingleField () {
		
	}
	
	public function userFormRenderMethod() {
		
	}

	/**
	 * User method which is called from the frontendforms library (evaluate_singleField) and provides a user defined evaluation
	 * for our tests. 
	 * 
	 * @param		array		$fieldConf: The field's configuration
	 * @param		mixed		$value: The value to check against
	 * @param		object		&$pObj: The parent object 
	 * @return		array		The key 'value' contains $value if evaluation was successful
	 * @access		public 
	 */
	public function userEvalMethod($fieldConf, $value, &$pObj) {
		
		switch ($fieldConf['config']['eval']) {
			case 'firstfieldeval' :
				$compareValue = 'firstvalue';
			break;
			case 'secondfieldeval' :
				$compareValue = 'secondvalue';
			break;
			default:
				self::Fail ('[config][eval] should be firstfieldeval or secondfieldeval!');
		}	

		$returnValue = ($value == $compareValue) ? $value : null;		
		return array (
			'value' => $returnValue
		);
	}
			
}

?>