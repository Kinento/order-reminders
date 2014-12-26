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


class Kinento_Reminder_Block_View_Main_Widget_Column_Renderer_Reminderssent extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render( Varien_Object $row ) {
		$html = '';
		$weekendsexclude = Mage::getStoreConfig( 'reminder/timesettings/weekendsexclude' );

		// Obtain the order/reminder details
		$incrementid = $row->getIncrementId();
		$reminderorders = Mage::getModel( 'reminder/reminder' )->getCollection()->addFieldToFilter( 'increment_id', $incrementid )->getItems();
		$reminderorder = reset( $reminderorders );
		
		// Obtain the age
		$now = new Zend_Date( Mage::getModel( 'core/date' )->gmtTimestamp() );
		$orderdate = new Zend_Date( $row->getCreatedAt(), 'yyyy-MM-dd' );
		$age = $now->sub($orderdate)->toValue();
		$days = ceil($age/60/60/24);

		// Obtain the age (weekend excluded)
		if ( $weekendsexclude == 'enabled' ) {
			$start_date = new Zend_Date( $row->getCreatedAt(), 'yyyy-MM-dd' );
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
			$days = $days - $weekenddays;
		}

		// Display the HTML
		$html .= $reminderorder->getReminders();
		$html .= ' (age: '.$days.')';
		$html .= '<br/><a href="'.$this->getUrl( '*/*/manipulate/option/add/id/'.$row->getIncrementId() ).'">'.Mage::helper( 'reminder' )->__( 'add' ).' 1</a>';
		$html .= '<br/><a href="'.$this->getUrl( '*/*/manipulate/option/sub/id/'.$row->getIncrementId() ).'">'.Mage::helper( 'reminder' )->__( 'remove' ).' 1</a>';
		$html .= '<br/><a href="'.$this->getUrl( '*/*/manipulate/option/reset/id/'.$row->getIncrementId() ).'">'.Mage::helper( 'reminder' )->__( 'reset to' ).' 0</a>';
		return $html;
	}
}
?>
