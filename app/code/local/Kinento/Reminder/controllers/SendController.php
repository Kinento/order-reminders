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


class Kinento_Reminder_SendController extends Mage_Adminhtml_Controller_Action
{

	public function indexAction() {

		// Calculate the total of send items before sending
		$count1 = 0;
		$orders = Mage::getModel( 'reminder/reminder' )->getCollection()->getItems();
		foreach ( $orders as $order ) {
			$count1 = $count1 + $order->getReminders();
		}

		// Send the reminders
		Mage::getModel( 'reminder/sender' )->prepareMail();

		// Calculate the total of send items after sending
		$count2 = 0;
		$orders = Mage::getModel( 'reminder/reminder' )->getCollection()->getItems();
		foreach ( $orders as $order ) {
			$count2 = $count2 + $order->getReminders();
		}

		// Output the total number of sent reminders
		Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'reminder' )->__( '%d reminder(s) sent', ( $count2 - $count1 ) ) );
		$this->getResponse()->setRedirect( $this->getUrl( '*/view/' ) );

	}
}
?>
