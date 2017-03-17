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


class Kinento_Reminder_Block_View_Main_Widget_Column_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render( Varien_Object $row ) {
		$html = '';
		$incrementid = $row->getIncrementId();
		$orderids = Mage::getModel( 'reminder/reminder' )->getCollection()->addFieldToFilter( 'increment_id', $incrementid )->getItems();
		foreach ( $orderids as $orderid ) {
			if ( $orderid->getStatus() == 'enabled' )
				$html .= Mage::helper( 'reminder' )->__( 'Enabled' ).'<br><a href="'.$this->getUrl( '*/*/change/status/disabled/id/'.$row->getIncrementId() ).'">'.Mage::helper( 'reminder' )->__( 'Disable notifications' ).'</a>';
			else
				$html .= '<b>'.Mage::helper( 'reminder' )->__( 'Disabled' ).'</b><br><a href="'.$this->getUrl( '*/*/change/status/enabled/id/'.$row->getIncrementId() ).'">'.Mage::helper( 'reminder' )->__( 'Enable notifications' ).'</a>';
		}
		return $html;
	}
}
?>
