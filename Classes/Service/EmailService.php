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
 * Email service
 *
 * Contains quick-use emailing functions.
 *
 * @package Notify
 * @subpackage Service
 */
class Tx_Notify_Service_EmailService implements \TYPO3\CMS\Core\SingletonInterface, Tx_Notify_Message_DeliveryServiceInterface {

	/**
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Send an email. Supports any to-string-convertible parameter types
	 *
	 * @param mixed $subject
	 * @param mixed $body
	 * @param $recipient
	 * @param $sender
	 * @return integer the number of recipients who were accepted for delivery
	 * @api
	 */
	public function mail($subject, $body, $recipient, $sender) {
		list ($recipientName, $recipientEmail) = explode(' <', trim($this->formatRfcAddress($recipient), '>'));
		list ($senderName, $senderEmail) = explode(' <', trim($this->formatRfcAddress($sender), '>'));
		$mail = $this->getMailer();
		$mail->setTo($recipientEmail, $recipientName);
		$mail->setFrom($senderEmail, $senderName);
		$mail->setSubject($subject);
		$mail->setBody($body);
		return $mail->send();
	}

	/**
	 * Get a mailer (SwiftMailer) object instance
	 *
	 * @return t3lib_mail_Message;
	 * @api
	 */
	public function getMailer() {
		$mailer = new \TYPO3\CMS\Core\Mail\MailMessage();
		return $mailer;
	}

	/**
	 * Sends a Message-interface-implementing Message through Email routes
	 *
	 * @param Tx_Notify_Message_MessageInterface $message The message to send
	 * @throws Exception
	 * @return boolean
	 */
	public function send(Tx_Notify_Message_MessageInterface $message) {
		$settings = $this->getComponentConfiguration();
		if (!$message->getSender()) {
			$configuredSender = $settings['email']['from'];
			$message->setSender(isset($configuredSender['name']) ? array($configuredSender['email'] => $configuredSender['name']) : $configuredSender['email']);
		}
		if (!$message->getSubject()) {
			$message->setSubject($settings['email']['subject']);
		}
		if ($message->getPrepared() !== TRUE) {
			$copy = $message->prepare();
		} else {
			$copy = clone $message;
		}
		$recipient = $copy->getRfcFormattedRecipientNameAndAddress();
		$sender = $copy->getRfcFormattedSenderNameAndAddress();
		if (empty($recipient)) {
			throw new Exception('Unable to determine recipient type (data vas ' . var_export($recipient, TRUE) . ' - make sure the value is either a string, a valid $name=>$email array or an object that converts to a string using __toString(), getValue() or render() methods on the object which return an RFC valid email identity)', 1334864233);
		}
		if (empty($sender)) {
			throw new Exception('Unable to determine sender type (data vas ' . var_export($sender, TRUE) . ' - make sure the value is either a string, a valid $name=>$email array or an object that converts to a string using __toString(), getValue() or render() methods on the object which return an RFC valid email identity)', 1334864233);
		}
		$recipientParts = explode(' <', trim($recipient, '>'));
		$senderParts = explode(' <', trim($sender, '>'));
		list ($recipientName, $recipientEmail) = $recipientParts;
		list ($senderName, $senderEmail) = $senderParts;
		$mailer = $this->getMailer();
		$mailer->setSubject($copy->getSubject());
		$mailer->setFrom($senderEmail, $senderName);
		$mailer->setTo($recipientEmail, $recipientName);

		if ($message->getType() === Tx_Notify_Message_MessageInterface::TYPE_TEXT) {
			$mailer->setBody($copy->getBody());
		} elseif ($message->getType() === Tx_Notify_Message_MessageInterface::TYPE_HTML) {
			$mailer->setBody($copy->getBody(), 'text/html');
			$mailer->addPart($copy->getAlternative(), 'text/plain');
		}

		$attachments = (array) $message->getAttachments();
		foreach ($attachments as $attachment) {
			if ($attachment instanceof \Swift_Image || $attachment instanceof \Swift_EmbeddedFile) {
				$disposition = $attachment->getDisposition();
			} else {
				$disposition = 'attachment';
			}
			if ($disposition == 'inline') {
				$mailer->embed($attachment);
			} else {
				$mailer->attach($attachment);
			}
		}
		$sent = $mailer->send();
		return (boolean) ($sent > 0);
	}

	/**
	 * @param mixed $address
	 * @return string
	 */
	protected function formatRfcAddress($address) {
		if (is_array($address) === TRUE) {
			if (count($address) > 1) {
				return array_map(array($this, 'formatRfcAddress'), $address);
			}
			reset($address);
			$name = trim(current($address));
			$address = key($address);
			if (empty($name) === FALSE) {
				$address = $name . ' <' . $address . '>';
			} else {
				$address = $address . ' <' . $address . '>';
			}
		} elseif (is_object($address) === TRUE) {
			if (method_exists($address, '__toString') === TRUE) {
				$address = (string) $address;
			} elseif (method_exists($address, 'render') === TRUE) {
				$address = $address->render();
			} elseif (method_exists($address, 'getValue') === TRUE) {
				$address = $address->getValue();
			}
		} elseif (is_string($address) === TRUE) {
			if (strpos($address, '<') === FALSE) {
				$address = $address . ' <' . $address . '>';
			}
		}
		return $address;
	}

	/**
	 * @return array
	 */
	protected function getComponentConfiguration() {
		if (count($this->configuration) === 0) {
			$settings = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
			$this->configuration  = $this->typoScriptArrayToPlainArray($settings['plugin.']['tx_notify.']['settings.']);
		}
		return $this->configuration;
	}

	/**
	 * @param array $array
	 * @return array
	 */
	protected function typoScriptArrayToPlainArray(array $array) {
		$transformed = array();
		foreach ($array as $key => $member) {
			$key = trim($key, '.');
			if (is_array($member) === TRUE) {
				$member = $this->typoScriptArrayToPlainArray($member);
			}
			$transformed[$key] = $member;
		}
		return $transformed;
	}

}
