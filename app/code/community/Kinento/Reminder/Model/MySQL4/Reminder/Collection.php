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


class Kinento_Reminder_Model_Mysql4_Reminder_Collection extends Varien_Data_Collection_Db
{
	protected $_bookTable;

	public function __construct() {
		$resources = Mage::getSingleton( 'core/resource' );
		parent::__construct( $resources->getConnection( 'reminder_read' ) );
		$this->_reminderTable = $resources->getTableName( 'reminder/reminder' );

		$this->_select->from(
			array( 'reminder'=>$this->_reminderTable ),
			array( '*' )
		);
		$this->setItemObjectClass( Mage::getConfig()->getModelClassName( 'reminder/reminder' ) );
	}
}
