<?php
/**
 * Kinento Order Reminders
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @category   Kinento
 * @package    Kinento_Reminder
 * @copyright  Copyright (c) 2009-2015 Kinento
 * @license    MIT license
 *
 */

class Kinento_Reminder_Model_Sender extends Mage_Core_Model_Abstract {

	// This section contains the different email addresses. If you want to use another email address,
	// simply comment the default 'default contact' line and comment out the wanted contact (1 line).

	// Default contact 
	const KINENTO_CONTACT = 'general';
	
	// Sales Representative 
	# const KINENTO_CONTACT = 'sales';
	
	// Customer Support 
	# const KINENTO_CONTACT = 'support';
	
	// Custom email 1 
	# const KINENTO_CONTACT = 'custom1';
	
	// Custom email 2
	# const KINENTO_CONTACT = 'custom2';

	// Function called from the cronjob
	public function cronMail() {
		$this->prepareMail();
	}

	// Main function to decide whether or not to send mail
	public function prepareMail() {

		// Get the current date
		$now = new Zend_Date( Mage::getModel( 'core/date' )->gmtTimestamp() );

		// Get the setting for the start of history
		$history = new Zend_Date( Mage::getStoreConfig( 'reminder/generalsettings/startingdate' ) );

		// Get the settings whether to use the order creation or update date
		$orderusecreation = Mage::getStoreConfig( 'reminder/timesettings/orderusecreation' );

		// Get the active stores
		$storesfilter = explode( ',', Mage::getStoreConfig( 'reminder/enablesettings/storesenabled' ) );

		// Get all orders from the Magento database
		$collection = Mage::getResourceModel( 'sales/order_grid_collection' )
		->addAttributeToSelect( '*' )
		->addFieldToFilter( 'store_id', array( 'in' => $storesfilter ) )
		->addAttributeToFilter( 'created_at', array( 'from' => $history, 'to' => $now, 'datetime'=>true ) );
		#->addAttributeToFilter( 'status', array( 'in' => $statusfilter ) )

		// Iterate over all the orders
		$remindermodel = Mage::getModel( 'reminder/reminder' );
		$orders = $collection->getItems();
		$count_all = count($orders);
		$count_considered1 = 0;
		$count_considered2 = 0;
		$count_match = 0;
		foreach ( $orders as $order ) {

			// Get settings for the status filter
			$statusfilter = explode( ',', Mage::getStoreConfig( 'reminder/generalsettings/orderstatuses', $order->getStoreId() ) );

			// Only consider orders with the right status
			$statusok = in_array( $order->getStatus(), $statusfilter );
			if ($statusok) {
				$count_considered1 += 1;

				// Get settings for weekend days
				$weekendsexclude = Mage::getStoreConfig( 'reminder/timesettings/weekendsexclude', $order->getStoreId() );
			
				// Get settings for the payment filters
				$paymentfilter = explode( ',', Mage::getStoreConfig( 'reminder/generalsettings/orderpayments', $order->getStoreId() ) );

				// Get settings for onaccount customers
				$firstnotificationonaccount = Mage::getStoreConfig( 'reminder/timesettings/firstnotificationonaccount', $order->getStoreId() );
				$firstonaccount = new Zend_Date( Mage::getModel( 'core/date' )->gmtTimestamp() );
				$firstonaccount = $firstonaccount->subDay( $firstnotificationonaccount );
				$secondnotificationonaccount = Mage::getStoreConfig( 'reminder/timesettings/secondnotificationonaccount', $order->getStoreId() );
				$secondonaccount = new Zend_Date( Mage::getModel( 'core/date' )->gmtTimestamp() );
				$secondonaccount = $secondonaccount->subDay( $secondnotificationonaccount );
				$nthonaccount = Mage::getStoreConfig( 'reminder/timesettings/nthonaccount', $order->getStoreId() );
				$cancelonaccountsettings = Mage::getStoreConfig( 'reminder/timesettings/cancelonaccount', $order->getStoreId() );
				$cancelonaccount = new Zend_Date( Mage::getModel( 'core/date' )->gmtTimestamp() );
				$cancelonaccount = $cancelonaccount->subDay( $cancelonaccountsettings );

				// Get settings for prepaid customers
				$firstnotificationprepaid = Mage::getStoreConfig( 'reminder/timesettings/firstnotificationprepaid', $order->getStoreId() );
				$firstprepaid = new Zend_Date( Mage::getModel( 'core/date' )->gmtTimestamp() );
				$firstprepaid = $firstprepaid->subDay( $firstnotificationprepaid );
				$secondnotificationprepaid = Mage::getStoreConfig( 'reminder/timesettings/secondnotificationprepaid', $order->getStoreId() );
				$secondprepaid = new Zend_Date( Mage::getModel( 'core/date' )->gmtTimestamp() );
				$secondprepaid = $secondprepaid->subDay( $secondnotificationprepaid );
				$nthprepaid = Mage::getStoreConfig( 'reminder/timesettings/nthprepaid', $order->getStoreId() );
				$cancelprepaidsettings = Mage::getStoreConfig( 'reminder/timesettings/cancelprepaid', $order->getStoreId() );
				$cancelprepaid = new Zend_Date( Mage::getModel( 'core/date' )->gmtTimestamp() );
				$cancelprepaid = $cancelprepaid->subDay( $cancelprepaidsettings );

				// Get the order from the 'reminder' database
				$reminderorders = $remindermodel->getCollection()->addFieldToFilter( 'increment_id', $order->getIncrementId() )->getItems();

				// If it doesn't exist yet, create it
				if ( empty( $reminderorders ) ) {
					$this->createReminderEntry( $order, $remindermodel );
					$reminderorders = $remindermodel->getCollection()->addFieldToFilter( 'increment_id', $order->getIncrementId() )->getItems();
				}

				// Process the order as present in the 'reminder' database
				foreach ( $reminderorders as $reminderorder ) {

					// Make sure that the order is not filtered out - otherwise don't do any processing
					if ( $reminderorder->getStatus() == 'enabled' &&
					     $order->getBillingAddress() != null &&
					     in_array( $order->getPayment()->getMethod(), $paymentfilter ) ) {
						$count_considered2 += 1;

						// Obtain the age (weekends excluded)
						if ( $weekendsexclude == 'enabled' ) {

							// Obtain the age
							$now_bis = new Zend_Date( Mage::getModel( 'core/date' )->gmtTimestamp() );
							if ( $orderusecreation == 'enabled' ) {
								$start_date = new Zend_Date( $order->getCreatedAt(), 'yyyy-MM-dd' );
							}
							else {
								$start_date = new Zend_Date( $order->getUpdatedAt(), 'yyyy-MM-dd' );
							}
							$age = $now_bis->sub($start_date)->toValue();
							$days = ceil($age/60/60/24);

							// Calculate the weekenddays
							$end_date = new Zend_Date( $start_date );
							$dayscounter = $days;
							$weekenddays = 0;
							while ($dayscounter > 0) {
								$weekdaydigit = $end_date->toValue( Zend_Date::WEEKDAY_DIGIT );
								if ( $weekdaydigit == 0 || $weekdaydigit == 6 ) {
									$weekenddays++;
								}
								$end_date->addDay( 1 );
								$dayscounter--;
							}
						}
						else {
							$weekenddays = 0;
						}

						// Get the date of the order
						if ( $orderusecreation == 'enabled' ) {
							$orderdate = new Zend_Date( $order->getCreatedAt(), 'yyyy-MM-dd' );
						}
						else {
							$orderdate = new Zend_Date( $order->getUpdatedAt(), 'yyyy-MM-dd' );
						}
						$orderdate = $orderdate->addDay( $weekenddays );
						$orderdate = $orderdate->getTimestamp();

						//  Find out if the order is part of the on-account customers
						$onaccount = Mage::getStoreConfig( 'reminder/generalsettings/groupsonaccount', $order->getStoreId() );
						if ( in_array( $order->getCustomerGroupId(), explode( ',', $onaccount ) ) ) {

							// First reminder for on-account customers
							if ( $reminderorder->getReminders() == 0 ) {
								if ( $firstonaccount->getTimestamp() > $orderdate ) {
									$this->prepareReminder( $order, $remindermodel );
									$count_match += 1;
								}
							}

							// Second reminder for on-account customers
							elseif ( $reminderorder->getReminders() == 1 ) {
								if ( $secondonaccount->getTimestamp() > $orderdate ) {
									$this->prepareReminder( $order, $remindermodel );
									$count_match += 1;
								}
							}

							// Third (or forth, fifth, etc.) reminder for on-account customers
							else {
								$newdate = new Zend_Date( $secondonaccount );
								$limit = $newdate->subDay( ( $reminderorder->getReminders()-1 )*$nthonaccount );
								if ( $limit->getTimestamp() > $orderdate ) {
									$this->prepareReminder( $order, $remindermodel );
									$count_match += 1;
								}
							}

							// Auto-cancel an order if it is too old (on-account)
							if ( $cancelonaccount->getTimestamp() > $orderdate ) {
								$order_bis = Mage::getModel( 'sales/order' )->loadByIncrementId( $order->getIncrementId() );
								if ($order_bis->canCancel()) {
									Mage::log( '[kinento-reminder] Canceling order '.$order_bis->getIncrementId().' ('.$cancelonaccount->getTimestamp().' > '.$orderdate.')', null, 'kinento.log', true );
									$order_bis->cancel();
									$order_bis->addStatusToHistory($order_bis->getStatus(), 'Canceled by Order Reminders', false);
									$order_bis->save();
									Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'reminder' )->__( 'Order %d canceled', $order_bis->getIncrementId() ) );
								}
								else {
									Mage::log( '[kinento-reminder] Could not cancel order '.$order_bis->getIncrementId().' ('.$cancelonaccount->getTimestamp().' > '.$orderdate.')', null, 'kinento.log', true );
									Mage::getSingleton( 'adminhtml/session' )->addNotice( Mage::helper( 'reminder' )->__( 'Unable to cancel order %d', $order_bis->getIncrementId() ) );
								}
							}
						}

						// The order was not on-account, it must be in the prepaid customers group
						else {

							// First reminder for prepaid customers
							if ( $reminderorder->getReminders() == 0 ) {
								if ( $firstprepaid->getTimestamp() > $orderdate ) {
									$this->prepareReminder( $order, $remindermodel );
									$count_match += 1;
								}
							}

							// Second reminder for prepaid customers
							elseif ( $reminderorder->getReminders() == 1 ) {
								if ( $secondprepaid->getTimestamp() > $orderdate ) {
									$this->prepareReminder( $order, $remindermodel );
									$count_match += 1;
								}
							}

							// Third (or forth, fifth, etc.) reminder for prepaid customers
							else {
								$newdate = new Zend_Date( $secondprepaid );
								$limit = $newdate->subDay( ( $reminderorder->getReminders()-1 )*$nthprepaid );
								if ( $limit->getTimestamp() > $orderdate ) {
									$this->prepareReminder( $order, $remindermodel );
									$count_match += 1;
								}
							}

							// Auto-cancel an order if it is too old (prepaid)
							if ( $cancelprepaid->getTimestamp() > $orderdate ) {
								$order_bis = Mage::getModel( 'sales/order' )->loadByIncrementId( $order->getIncrementId() );
								if ($order_bis->canCancel()) {
									Mage::log( '[kinento-reminder] Canceling order '.$order_bis->getIncrementId().' ('.$cancelprepaid->getTimestamp().' > '.$orderdate.')', null, 'kinento.log', true );
									$order_bis->cancel();
									$order_bis->addStatusToHistory($order_bis->getStatus(), 'Canceled by Order Reminders', false);
									$order_bis->save();
									Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'reminder' )->__( 'Order %d canceled', $order_bis->getIncrementId() ) );
								}
								else {
									Mage::log( '[kinento-reminder] Could not cancel order '.$order_bis->getIncrementId().' ('.$cancelprepaid->getTimestamp().' > '.$orderdate.')', null, 'kinento.log', true );
									Mage::getSingleton( 'adminhtml/session' )->addNotice( Mage::helper( 'reminder' )->__( 'Unable to cancel order %d', $order_bis->getIncrementId() ) );
								}
							}
						}
					}
				}
			}
		}
		Mage::log( '', null, 'kinento.log', true );
		Mage::log( '[kinento-reminder] Orders '.$count_all.' (total), '.$count_considered1.' (after status filter), '.$count_considered2.' (after other filters), '.$count_match.' (after time filter)', null, 'kinento.log', true );
		Mage::getSingleton( 'adminhtml/session' )->addNotice( Mage::helper( 'reminder' )->__( '%d order(s) searched, %d match the criteria', $count_all, $count_match ) );

	}

	// Function to send an email independent of any settings or filters
	public function manualMail( $id ) {

		// Get all orders from the Magento database
		$collection = Mage::getResourceModel( 'sales/order_grid_collection' )
		->addAttributeToSelect( '*' )
		->addAttributeToFilter( 'increment_id', $id );

		// Iterate over all the orders
		$remindermodel = Mage::getModel( 'reminder/reminder' );
		$orders = $collection->getItems();
		foreach ( $orders as $order ) {

			// Get the order from the 'reminder' database
			$reminderorders = $remindermodel->getCollection()->addFieldToFilter( 'increment_id', $id )->getItems();

			// If it doesn't exist yet, create it
			if ( empty( $reminderorders ) ) {
				$this->createReminderEntry( $order, $remindermodel );
				$reminderorders = $remindermodel->getCollection()->addFieldToFilter( 'increment_id', $id )->getItems();
			}

			//  Find out if the order is part of the on-account customers
			$onaccount = Mage::getStoreConfig( 'reminder/generalsettings/groupsonaccount', $order->getStoreId() );

			// Send out reminder for on-account customers
			if ( in_array( $order->getCustomerGroupId(), explode( ',', $onaccount ) ) ) {
				$this->prepareReminder( $order, $remindermodel );
			}

			// Send out reminder for prepaid customers
			else {
				$this->prepareReminder( $order, $remindermodel );
			}
		}
	}

	// Function to create an entry in the 'reminder' database
	public function createReminderEntry( $order, $remindermodel ) {
		$data = array(
			"increment_id" => $order->getIncrementId(),
			"reminders"    => 0,
		);
		$remindermodel->setData( $data );
		$remindermodel->save();
	}

	// Function to prepare a reminder email
	public function prepareReminder( $order, $remindermodel ) {

		// Gather all necessary data
		$order_alt1 = Mage::getModel( 'sales/order' )->loadByIncrementId( $order->getIncrementId() );
		$order_alt2 = Mage::getModel( 'sales/order' )->load( $order->getId() );
		$customer = Mage::getModel( 'customer/customer' )->load( $order->getCustomerId() );
		$data = array(
			"order"            => $order_alt1,
			"order_alt1"       => $order,
			"order_alt2"       => $order_alt2,
			"payment"          => $order->getPayment(),
			"shippingname"     => $order->getShippingName(),
			"customername"     => $customer->getFirstname().' '.$customer->getLastname(),
			"customeremail"    => $order_alt1->getCustomerEmail(),
			"orderid"          => $order->getIncrementId(),
			"orderincrementid" => $order->getIncrementId(),
			"orderdate"        => $order->getCreatedAt(),
			"orderamount"      => money_format( "%n", $order->getGrandTotal() ),
			"invoices"         => $order->getInvoiceCollection()->getItems(),
			"storeid"          => $order->getStoreId(),
			"paymentmethod"    => $order->getPayment()->getMethod(),
			"shippingdescription" => $order_alt1->getShippingDescription(),
		);

		// Add the invoice ID to the emails
		if ( !empty( $data["invoices"] ) ) {
			$data["invoiceid"] = reset( $data["invoices"] )->getIncrementId();
		}

		// Set additional data depending on on-account or prepaid
		$onaccount = Mage::getStoreConfig( 'reminder/generalsettings/groupsonaccount', $order->getStoreId() );
		if ( in_array( $order->getCustomerGroupId(), explode( ',', $onaccount ) ) ) {
			$data["customergroup"] = 'On account';
			$data["customergroupdata"] = Mage::getStoreConfig( 'reminder/emailsettings/paytypeone', $order->getStoreId() );
			$data["attachment"] = Mage::getStoreConfig( 'reminder/emailsettings/attachonaccount', $order->getStoreId() );
		}
		else {
			$data["customergroup"] = 'Prepaid';
			$data["customergroupdata"] = Mage::getStoreConfig( 'reminder/emailsettings/paytypetwo', $order->getStoreId() );
			$data["attachment"] = Mage::getStoreConfig( 'reminder/emailsettings/attachprepaid', $order->getStoreId() );
		}

		// Set additional data depending on normal or alternative payment method
		$normalpayments = Mage::getStoreConfig( 'reminder/emailsettings/altpayments', $order->getStoreId() );
		if ( in_array( $order->getPayment()->getMethod(), explode( ',', $normalpayments ) ) ) {
			$data["paymenttype"] = 'Normal';
			$data["paymenttypedata"] = Mage::getStoreConfig( 'reminder/emailsettings/normaltext', $order->getStoreId() );
		}
		else {
			$data["paymenttype"] = 'Alternative';
			$data["paymenttypedata"] = Mage::getStoreConfig( 'reminder/emailsettings/alttext', $order->getStoreId() );
		}

		// Update the data in the 'reminder' database and send out the reminder
		$reminderorders = $remindermodel->getCollection()->addFieldToFilter( 'increment_id', $data["orderincrementid"] )->getItems();
		foreach ( $reminderorders as $reminderorder ) {
			$data["remindercount"] = $reminderorder->getReminders()+1;
			$this->preSendReminder( $data, $order );
			$reminderorder->setReminders( $data["remindercount"] );
			$reminderorder->save();
		}
	}

	// Function to send either just the reminder or also a copy
	public function preSendReminder( $data, $order ) {

		// Find out if we need to send a copy
		$copy = Mage::getStoreConfig( 'reminder/emailsettings/emailcopy', $order->getStoreId() );

		// Send the copy to the specified email address
		if ( $copy != "" ) {
			$this->sendReminder( $data, $order, $copy );
		}

		// Send the original reminder
		$this->sendReminder( $data, $order, $data["customeremail"] );
	}

	// Function to send the actual email reminder
	public function sendReminder( $data, $order, $emailaddress ) {

		// Set-up the email environment
		$translate = Mage::getSingleton( 'core/translate' );
		$translate->setTranslateInline( false );
		$mail = Mage::getModel( 'core/email_template' );

		// Get the reminder email templates
		if ( $data["remindercount"] == 1 ) {
			$template = Mage::getStoreConfig( 'reminder/emailsettings/templateone', $order->getStoreId() );
		}
		elseif ( $data["remindercount"] == 2 ) {
			$template = Mage::getStoreConfig( 'reminder/emailsettings/templatetwo', $order->getStoreId() );
		}
		else {
			$template = Mage::getStoreConfig( 'reminder/emailsettings/templatethree', $order->getStoreId() );
		}

		// Get the attachement if it is enabled in the settings
		if ( $data["attachment"] == 'enabled' ) {
			if ( !empty( $data["invoices"] ) ) {
				$pdf = Mage::getModel( 'sales/order_pdf_invoice' )->getPdf( $data["invoices"] );
				$pdfname = 'invoice';
			}
			else {
				$pdf = Mage::getModel( 'reminder/pdf_proforma' )->getPdf( $order );
				$pdfname = 'proforma';
			}
			$pdffile = $pdf->render();
			$mail->getMail()->createAttachment( $pdffile,
				'application/pdf',
				Zend_Mime::DISPOSITION_ATTACHMENT,
				Zend_Mime::ENCODING_BASE64,
				$pdfname.'.pdf'
			);
		}
		else {
			$mail->getMail();
		}

		// Set the sender data
		$senderdata = self::KINENTO_CONTACT;

		// Write the log
		Mage::log( '' , null, 'kinento.log', true );
		Mage::log( '[kinento-reminder] Sending a reminder', null, 'kinento.log', true );
		Mage::log( '[kinento-reminder] To: '.$emailaddress, null, 'kinento.log', true );
		Mage::log( '[kinento-reminder] Contact: '.$senderdata, null, 'kinento.log', true );
		Mage::log( '[kinento-reminder] Template: '.$template, null, 'kinento.log', true );
		Mage::log( '[kinento-reminder] StoreID: '.$data["storeid"], null, 'kinento.log', true );
		Mage::log( '[kinento-reminder] Description: '.$data["shippingdescription"], null, 'kinento.log', true );

		// Send out the actual email
		$mail
			->setDesignConfig( array( 'area' => 'frontend', 'store' => $order->getStoreId() ) )
			->sendTransactional(
				$template,
				$senderdata,
				$emailaddress,
				null,
				$data
			);

		// Debug logging (after sending the email)
		Mage::log( '[kinento-reminder] Emailing enabled: '.var_export( !Mage::getStoreConfigFlag( 'system/smtp/disable' ), true ), null, 'kinento.log', true );
		Mage::log( '[kinento-reminder] From (email): '.var_export( $mail->getSenderName(), true ), null, 'kinento.log', true );
		Mage::log( '[kinento-reminder] From (name): '.var_export( $mail->getSenderEmail(), true ), null, 'kinento.log', true );
		Mage::log( '[kinento-reminder] Subject: '.var_export( $mail->getTemplateSubject(), true ), null, 'kinento.log', true );

		// Give feedback to the user
		Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'reminder' )->__( 'Send email to %s for order %d', $emailaddress, $order->getIncrementId() ) );

		// Finalize
		$translate->setTranslateInline( true );
	}
}
?>
