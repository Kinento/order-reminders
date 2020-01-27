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


class Kinento_Reminder_ViewController extends Mage_Adminhtml_Controller_Action
{

	public function indexAction() {

		$this->loadLayout();
		$this->_setActiveMenu( 'reminder/view' );

		$this->getLayout()
		->getBlock( 'content' )->append(
			$this->getLayout()->createBlock( 'reminder/view_main' )
		);
		$this->renderLayout();
	}

	public function changeAction() {
		$id = $this->getRequest()->getParam( 'id', false );
		$status = $this->getRequest()->getParam( 'status', false );

		try {
			$orders = Mage::getModel( 'reminder/reminder' )->getCollection()->addFieldToFilter( 'increment_id', $id )->getItems();
			foreach ( $orders as $order ) {
				$order->setStatus( $status );
				$order->save();
				Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'reminder' )->__( 'Status changed.' ) );
			}
		} catch ( Exception $e ) {
			Mage::getSingleton( 'adminhtml/session' )->addError( $e->getMessage() );
		}

		$this->_redirectReferer();
	}

	public function manualAction() {
		$id = $this->getRequest()->getParam( 'id', false );

		try {
			Mage::getModel( 'reminder/sender' )->manualMail( $id );
			Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'reminder' )->__( '1 reminder sent manually' ) );

		} catch ( Exception $e ) {
			Mage::getSingleton( 'adminhtml/session' )->addError( $e->getMessage() );
		}

		$this->_redirectReferer();
	}

	public function manipulateAction() {
		$id = $this->getRequest()->getParam( 'id', false );
		$option = $this->getRequest()->getParam( 'option', false );

		try {
			$orders = Mage::getModel( 'reminder/reminder' )->getCollection()->addFieldToFilter( 'increment_id', $id )->getItems();
			foreach ( $orders as $order ) {
				if ( $option == 'add' ) {
					$order->setReminders( $order->getReminders()+1 );
				}
				if ( $option == 'sub' ) {
					$order->setReminders( $order->getReminders()-1 );
				}
				if ( $option == 'reset' ) {
					$order->setReminders( 0 );
				}
				$order->save();
			}
		} catch ( Exception $e ) {
			Mage::getSingleton( 'adminhtml/session' )->addError( $e->getMessage() );
		}

		$this->_redirectReferer();
	}

	public function massChangeAction() {
		$ids = $this->getRequest()->getParam( 'reminder' );
		$status = $this->getRequest()->getParam( 'status', false );

		if ( !is_array( $ids ) ) {
			Mage::getSingleton( 'adminhtml/session' )->addError( Mage::helper( 'reminder' )->__( 'Please select one or more orders' ) );
		} else {
			try {
				foreach ( $ids as $id ) {
					$orderid = Mage::getModel( 'sales/order' )->load( $id )->getIncrementId();
					$orders = Mage::getModel( 'reminder/reminder' )->getCollection()->addFieldToFilter( 'increment_id', $orderid )->getItems();
					foreach ( $orders as $order ) {
						$order->setStatus( $status );
						$order->save();
					}
				}
				Mage::getSingleton( 'adminhtml/session' )->addSuccess(
					Mage::helper( 'reminder' )->__( 'Total of %d order(s) were successfully updated', count( $ids ) )
				);
			} catch ( Exception $e ) {
				Mage::getSingleton( 'adminhtml/session' )->addError( $e->getMessage() );
			}
		}
		$this->_redirect( '*/*/index' );
	}

	// Function to send out reminders now, independent of the settings. Sends out multiple emails (massAction)
	public function massSendAction() {
		$ids = $this->getRequest()->getParam( 'reminder' );
		if ( !is_array( $ids ) ) {
			Mage::getSingleton( 'adminhtml/session' )->addError( Mage::helper( 'reminder' )->__( 'Please select one or more orders' ) );
		} else {
			try {
				foreach ( $ids as $id ) {
					$orderid = Mage::getModel( 'sales/order' )->load( $id )->getIncrementId();
					Mage::getModel( 'reminder/sender' )->manualMail( $orderid );
				}
				Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'reminder' )->__( '%d reminder(s) sent manually', count( $ids ) ) );

			} catch ( Exception $e ) {
				Mage::getSingleton( 'adminhtml/session' )->addError( $e->getMessage() );
			}
		}
		$this->_redirect( '*/*/index' );
	}
}
?>
