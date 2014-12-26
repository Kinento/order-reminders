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


class Kinento_Reminder_Block_View_Main extends Mage_Adminhtml_Block_Widget_Grid_Container {

	public function __construct() {
		parent::__construct();

		$this->_blockGroup = 'reminder';
		$this->_controller = 'view_main';
		$this->_headerText = Mage::helper( 'reminder' )->__( 'Pending orders overview' );
		$this->_removeButton( 'add' );
		$this->_addButton(
			'sendreminders',
			array(
				'label'     => Mage::helper( 'reminder' )->__( 'Send reminders now' ),
				'onclick'   => 'setLocation(\'' . $this->getUrl( '*/send' ) . '\')',
			)
		);

	}

}
?>
