<?php
class Tx_Notify_Poller_FilePoller extends Tx_Notify_Poller_AbstractPoller implements Tx_Notify_Poller_PollerInterface {

	/**
	 * @param Tx_Notify_Domain_Model_Subscription $subscription
	 * @param boolean $rewriteChecksums
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<Tx_Notify_Domain_Model_UpdatedObject>
	 */
	public function getUpdatedObjects(Tx_Notify_Domain_Model_Subscription &$subscription, $rewriteChecksums=FALSE) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Persistence\ObjectStorage');
	}

	/**
	 * @param Tx_Notify_Domain_Model_Subscription $subscription
	 * @param string $file
	 * @return string
	 */
	public function calculateChecksum(Tx_Notify_Domain_Model_Subscription &$subscription, $file) {
		return md5(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file));
	}

}
