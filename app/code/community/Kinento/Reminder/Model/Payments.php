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


class Kinento_Reminder_Model_Payments {
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
			$payments = Mage::getSingleton( 'payment/config' )->getActiveMethods();
			foreach ( $payments as $paymentCode=>$paymentModel ) {
				$paymentTitle = Mage::getStoreConfig( 'payment/'.$paymentCode.'/title' );
				$this->_options[$paymentCode] = array( 'label' => $paymentTitle, 'value' => $paymentCode );
			}
		}
		return $this->_options;
	}
}

?>
