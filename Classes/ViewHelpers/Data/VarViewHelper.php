<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Claus Due <claus@wildside.dk>, Wildside A/S
*
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
 *
 * @author Claus Due, Wildside A/S
 * @package Notify
 * @subpackage ViewHelpers\Data
 */
class Tx_Notify_ViewHelpers_Data_VarViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface {

	/**
	 * @var array<\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode>
	 */
	protected $childNodes;

	/**
	 * Initialize arguments
	 */
	public function initializeArguments() {
		$this->registerArgument('name', 'string', 'Name of the variable to get or set', TRUE, NULL, TRUE);
		$this->registerArgument('value', 'mixed', 'If specified, takes value from content of this argument', FALSE, NULL, TRUE);
		$this->registerArgument('type', 'string', 'Data-type for this variable. Casts the value if set.', FALSE, NULL, TRUE);
		$this->registerArgument('scope', 'string', 'Scope in which to get the variable - switch this to "php" to read PHP variables by path', FALSE, 'fluid');
	}

	/**
	 * Get or set a variable
	 * @return mixed
	 */
	public function render() {
		$value = NULL;
		$name = $this->arguments['name'];
		$value = $this->arguments['value'];
		$type = $this->arguments['type'];
		$parts = array();
		if (count($this->childNodes) > 0 && isset($this->arguments['value']) === FALSE) {
			$value = $this->renderChildren();
		}
		if ($value !== NULL || isset($this->arguments['value']) === TRUE) {
				// we are setting a variable
			if ($type !== NULL) {
				$value = $this->typeCast($value, $type);
			}
			if ($this->templateVariableContainer->exists($name)) {
				$this->templateVariableContainer->remove($name);
			}
			$this->templateVariableContainer->add($name, $value);
			return NULL;
		} else {
				// we are echoing a variable
			if (strpos($name, '.')) {
				$parts = explode('.', $name);
				$name = array_shift($parts);
			}
			if ($this->arguments['scope'] === 'php') {
				global $$name;
				$allVariables = get_defined_vars();
				if (isset($allVariables[$name])) {
					$rootVariable = $allVariables[$name];
					if (count($parts) > 0) {
						return \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($rootVariable, implode('.', $parts));
					} else {
						return $rootVariable;
					}
				}
			}
			if ($this->templateVariableContainer->exists($name)) {
				$value = $this->templateVariableContainer->get($name);
				if (is_array($parts) && count($parts) > 0) {
					$value = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($value, implode('.', $parts));
				}
				return $value;
			} else {
				return NULL;
			}
		}
	}

	/**
	 * Type-cast a value with type $type
	 *
	 * @param mixed $value
	 * @param string $type
	 * @throws Exception
	 * @return mixed
	 */
	private function typeCast($value, $type) {
		switch ($type) {
			case 'integer':
				$value = intval($value);
				break;
			case 'float':
				$value = floatval($value);
				break;
			case 'object':
				$value = (object) $value;
				break;
			case 'array':
				// cheat a bit; assume CSV
				if (is_array($value) === FALSE) {
					$value = explode(',', $value);
				}
				break;
			case 'DateTime':
				// pretty easy assumption: integer = Unix timestamp
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($value)) {
					// Convert to interpretable string to respect the local timezone
					$value = date(DateTime::W3C, $value);
				}
				$converter = new \TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter();
				$value = $converter->convertFrom($value, 'DateTime');
				break;
			case 'string':
				$value = (string) $value;
		}
		return $value;
	}

	/**
	 * Sets the direct child nodes of the current syntax tree node.
	 *
	 * @param array<\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode> $childNodes
	 * @return void
	 */
	public function setChildNodes(array $childNodes) {
		$this->childNodes = $childNodes;
	}
}
