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


class Kinento_Reminder_Model_Statuses {
	protected $_options;

	public function toOptionArray() {
		if ( !$this->_options ) {
			$this->getAllOptions();
		}
		return $this->_options;
	}

	public function getAllOptions() {
		if ( !$this->_options ) {
			$this->_options = array();
			$this->_options[] = array( 'value' => 'canceled' , 'label' => 'Canceled' );
			$this->_options[] = array( 'value' => 'closed' , 'label' => 'Closed' );
			$this->_options[] = array( 'value' => 'complete' , 'label' => 'Complete' );
			$this->_options[] = array( 'value' => 'fraud' , 'label' => 'Suspected Fraud' );
			$this->_options[] = array( 'value' => 'holded' , 'label' => 'On Hold' );
			$this->_options[] = array( 'value' => 'payment_review' , 'label' => 'Payment Review' );
			$this->_options[] = array( 'value' => 'pending' , 'label' => 'Pending' );
			$this->_options[] = array( 'value' => 'pending_payment' , 'label' => 'Pending Payment' );
			$this->_options[] = array( 'value' => 'pending_paypal' , 'label' => 'Pending PayPal' );
			$this->_options[] = array( 'value' => 'processing' , 'label' => 'Processing' );
		}
		return $this->_options;
	}
	
}

/*
foreach( Mage::getSingleton( 'sales/order_config' )->getStatuses() as $status) {
	$label = Mage::getSingleton( 'sales/order_config' )->getStatusLabel($status);
	//$value = Mage::getSingleton( 'sales/order_config' )->getStateDefaultStatus($state);
	$value = $status;
	$this->_options[] = array( 'value' => $value , 'label' => $label );
}
print_r( $this->_options );
*/

?>
