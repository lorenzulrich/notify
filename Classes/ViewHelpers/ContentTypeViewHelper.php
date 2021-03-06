<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Claus Due <claus@wildside.dk>, Wildside A/S
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * @package Notify
 * @subpackage ViewHelpers
 */
class Tx_Notify_ViewHelpers_ContentTypeViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @param array $record
	 * @return string
	 */
	public function render($record) {
		return $this->getContentTypeRepresentation($record);
	}

	/**
	 * @param array $record
	 * @return string
	 */
	protected function getContentTypeRepresentation($record) {
		$cType = $record['CType'];
		switch ($cType) {
			case 'list': return $this->getPluginListTypeLabel($record['list_type']);
			default: return $this->getTcaLabel($cType);
		}
	}

	/**
	 * @param string $listType
	 * @return string
	 */
	protected function getPluginListTypeLabel($listType) {
		foreach ($GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] as $selectionValueSet) {
			if ($selectionValueSet[1] === $listType) {
				return $selectionValueSet[0];
			}
		}
		return $listType;
	}

	/**
	 * @param string $cType
	 * @return string
	 */
	protected function getTcaLabel($cType) {
		foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $selectionValueSet) {
			if ($selectionValueSet[1] === $cType) {
				return $selectionValueSet[0];
			}
		}
		return $cType;
	}

}