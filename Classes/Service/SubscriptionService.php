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
 * @subpackage Service
 */
class Tx_Notify_Service_SubscriptionService extends Tx_Notify_Service_AbstractService implements t3lib_Singleton {

	/**
	 * @var Tx_Notify_Service_ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var \Tx_Notify_Service_Json
	 */
	protected $jsonService;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @param Tx_Notify_Service_ConfigurationService $configurationService
	 */
	public function injectConfigurationService(Tx_Notify_Service_ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param \Tx_Notify_Service_Json $jsonService
	 */
	public function injectJsonService(\Tx_Notify_Service_Json $jsonService) {
		$this->jsonService = $jsonService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
	 */
	public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * @return Tx_Notify_Subscription_SourceProviderInterface[]
	 */
	public function getUniqueSourceProvidersUsedByAllSubscriptions() {
		$subscriptions = $this->getAllActiveSubscriptions();
		/** @var $providers Tx_Notify_Subscription_SourceProviderInterface[] */
		$providers = array();
		$names = array();
		foreach ($subscriptions as $subscription) {
			$provider = $subscription->getSource();
			if (is_object($provider)) {
				$provider = serialize($provider);
			}
			array_push($names, $provider);
		}
		$names = array_unique($names);
		foreach ($names as $provider) {
			if (substr($provider, 0, 2) === 'O:') {
				array_push($providers, unserialize($provider));
			}
		}
		return $providers;
	}

	/**
	 * @param array $configuration
	 * @return Tx_Notify_Subscription_SourceProviderInterface
	 */
	public function getSourceProviderInstance($configuration=NULL) {
		$settings = $this->configurationService->getConfiguration();
		$providerClassName = $configuration['source']['provider'] ? $configuration['source']['provider'] : $settings['source']['provider'];
		if (class_exists($providerClassName) === FALSE) {
			throw new Exception('Invalid or missing notification source provider class name', 1332618106);
		}
		/** @var Tx_Notify_Subscription_SourceProviderInterface $instance */
		$instance = $this->objectManager->get($providerClassName);
		$instance->setConfiguration($configuration ? $configuration : $settings);
		return $instance;
	}

	/**
	 * Subscribes a Subscriber to $source
	 *
	 * @param Tx_Notify_Subscription_SourceProviderInterface $source The source of Subscription. This instance defines how subscription happens.
	 * @param mixed $subscriber Any value that can be converted to a string and reanimated by a Tx_Notify_Subscription_SubscriberProvider
	 * @param string $url Optional URL to use for this subscription whenever links are created
	 * @return Tx_Notify_Domain_Model_Subscription
	 */
	public function subscribe(Tx_Notify_Subscription_SourceProviderInterface $source, $subscriber, $url=NULL) {
		$this->refreshSubscriberCookie($subscriber);
		if ($subscription = $source->getSubscription($subscriber)) {
			$subscription->setActive(TRUE);
			$subscription->setUrl($url);
			$subscription->setSource($source);
			$this->subscriptionRepository->update($subscription);
		} else {
			$subscription = $source->createSubscription($subscriber);
			$subscription->setUrl($url);
			$subscription->setSource($source);
			$this->subscriptionRepository->add($subscription);
		}
		$this->persistenceManager->persistAll();
		return $subscription;
	}

	/**
	 * Unsubscribes a Subscriber to $source
	 *
	 * @param Tx_Notify_Subscription_SourceProviderInterface $source The source of Subscription. This instance defines how subscription happens.
	 * @param mixed $subscriber Any value that can be converted to a string and reanimated by a Tx_Notify_Subscription_SubscriberProvider
	 * @return boolean
	 */
	public function unsubscribe(Tx_Notify_Subscription_SourceProviderInterface $source, $subscriber) {
		$this->refreshSubscriberCookie($subscriber);
		$subscription = $source->getSubscription($subscriber);
		if ($subscription) {
			$subscription->setActive(FALSE);
			$this->subscriptionRepository->update($subscription);
			$this->persistenceManager->persistAll();
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns TRUE if $subscriber already subscribes to $source
	 *
	 * @param Tx_Notify_Subscription_SourceProviderInterface $source The source of Subscription. This instance defines how subscription happens.
	 * @param mixed $subscriber Any value that can be converted to a string and reanimated by a Tx_Notify_Subscription_SubscriberProvider
	 */
	public function isSubscribed(Tx_Notify_Subscription_SourceProviderInterface $source, $subscriber) {
		$subscription = $source->getSubscription($subscriber);
		if ($subscription) {
			return $subscription->getActive();
		}
		return FALSE;
	}

	/**
	 * Gets all active subscriptions from Repository
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function getAllActiveSubscriptions() {
		$query = $this->subscriptionRepository->createQuery();
		$query->getQuerySettings()->setRespectStoragePage(FALSE);
		$query->matching($query->equals('active', 1));
		$subscriptions = $query->execute();
		$subscriptions->getFirst(); // pre-load all results by firing the query now
		return $subscriptions;
	}

	/**
	 * @param Iterator $subscriptions
	 * @param boolean $rewriteChecksums
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<Tx_Notify_Domain_Model_Subscription>
	 */
	public function buildUpdatedContentObjects(Iterator $subscriptions, $rewriteChecksums=FALSE) {
		/** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $objectStorage */
		$objectStorage = $this->objectManager->get('TYPO3\CMS\Extbase\Persistence\ObjectStorage');
		foreach ($subscriptions as $subscription) {
			/** @var Tx_Notify_Domain_Model_Subscription $subscription */
			/** @var Tx_Notify_Poller_PollerInterface $poller  */
			$poller = $this->resolvePollerForSubscription($subscription);
			$updates = $poller->getUpdatedObjects($subscription, $rewriteChecksums);
			if ($updates->count() > 0) {
				$subscription->setUpdates($updates);
				$objectStorage->attach($subscription);
			}
		}
		return $objectStorage;
	}

	/**
	 * @param Tx_Notify_Domain_Model_Subscription $subscription
	 * @return Tx_Notify_Poller_PollerInterface|NULL
	 */
	public function resolvePollerForSubscription(Tx_Notify_Domain_Model_Subscription $subscription) {
		switch ($subscription->getMode()) {
			case Tx_Notify_Subscription_StandardSourceProvider::MODE_FILE:
				return $this->objectManager->get('Tx_Notify_Poller_FilePoller');
			case Tx_Notify_Subscription_StandardSourceProvider::MODE_RECORD:
				return $this->objectManager->get('Tx_Notify_Poller_RecordPoller');
			case Tx_Notify_Subscription_StandardSourceProvider::MODE_PAGE:
				return $this->objectManager->get('Tx_Notify_Poller_PageContentPoller');
			default:
				return NULL;
		}
	}

	/**
	 * @param Tx_Notify_Domain_Model_Subscription $subscription
	 */
	public function update(Tx_Notify_Domain_Model_Subscription $subscription) {
		$this->subscriptionRepository->update($subscription);
		$this->persistenceManager->persistAll();
	}

	/**
	 * @param string $subscriber
	 */
	protected function refreshSubscriberCookie($subscriber) {
		setcookie('tx_notify_subscriber', $subscriber, time() + 8640000);
	}

}
