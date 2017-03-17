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


class Kinento_Reminder_Block_View_Main_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {
		parent::__construct();
		$this->setId( 'reminderGrid' );
		$this->setDefaultSort( 'entity_id' );
	}

	protected function _prepareCollection() {

		$history = new Zend_Date( Mage::getStoreConfig( 'reminder/generalsettings/startingdate' ) );
		$now = new Zend_Date( Mage::getModel( 'core/date' )->gmtTimestamp() );
		$statusfilter = explode( ',', Mage::getStoreConfig( 'reminder/generalsettings/orderstatuses' ) );
		$storesfilter = explode( ',', Mage::getStoreConfig( 'reminder/enablesettings/storesenabled' ) );

		// Get the data from the database
		$collection = Mage::getResourceModel( 'sales/order_grid_collection' )
		->addAttributeToSelect( '*' )
		->addFieldToFilter( 'store_id', array( 'in' => $storesfilter ) )
		->addAttributeToFilter( 'status', array( 'in' => $statusfilter ) )
		->addAttributeToFilter( 'created_at', array( 'from' => $history, 'to' => $now, 'datetime'=>true ) );
		$this->setCollection( $collection );

		// Create new entry for those that do not exist
		$remindermodel = Mage::getModel( 'reminder/reminder' );
		$orders = $collection->getItems();
		foreach ( $orders as $order ) {
			$reminderorders = $remindermodel->getCollection()->addFieldToFilter( 'increment_id', $order->getIncrementId() )->getItems();
			if ( empty( $reminderorders ) ) {
				$data = array(
					"increment_id"  => $order->getIncrementId(),
					"reminders"  => 0,
				);
				$remindermodel->setData( $data );
				$remindermodel->save();
			}
		}

		// Get the data from the database
		$collection = Mage::getResourceModel( 'sales/order_grid_collection' )
		->addAttributeToSelect( '*' )
		->addFieldToFilter( 'store_id', array( 'in' => $storesfilter ) )
		->addAttributeToFilter( 'status', array( 'in' => $statusfilter ) )
		->addAttributeToFilter( 'created_at', array( 'from' => $history, 'to' => $now, 'datetime'=>true ) );
		$this->setCollection( $collection );
		return parent::_prepareCollection();

	}

	protected function _prepareColumns() {
		$this->addColumn( 'real_order_id', array(
				'header'=> Mage::helper( 'reminder' )->__( 'Order id' ),
				'width' => '100px',
				'type'  => 'text',
				'index' => 'increment_id',
			) );

		if ( !Mage::app()->isSingleStoreMode() ) {
			$this->addColumn( 'store_id', array(
					'header'    => Mage::helper( 'reminder' )->__( 'Store' ),
					'index'     => 'store_id',
					'type'      => 'store',
					'store_view'=> true,
					'display_deleted' => true,
					'width' => '200px',
				) );
		}

		$this->addColumn( 'created_at', array(
				'header' => Mage::helper( 'reminder' )->__( 'Purchase date' ),
				'index' => 'created_at',
				'type' => 'datetime',
				'width' => '100px',
			) );

		$this->addColumn( 'shipping_name', array(
				'header' => Mage::helper( 'reminder' )->__( 'Shipping name' ),
				'index' => 'shipping_name',
				'width' => '200px',
			) );

		$this->addColumn( 'billing_name', array(
				'header' => Mage::helper( 'reminder' )->__( 'Billing name' ),
				'index' => 'billing_name',
				'width' => '200px',
			) );

		$this->addColumn( 'grand_total', array(
				'header' => Mage::helper( 'reminder' )->__( 'Amount' ),
				'index' => 'grand_total',
				'type'  => 'currency',
				'currency' => 'order_currency_code',
				'width' => '100px',
			) );

		$this->addColumn( 'orderstatus', array(
				'header' => Mage::helper( 'reminder' )->__( 'Order status' ),
				'index' => 'status',
				'type'  => 'options',
				'width' => '70px',
				'options' => Mage::getSingleton( 'sales/order_config' )->getStatuses(),
			) );

		// Start of widgets

		// Widget 1
		$this->addColumn( 'ordergroup', array(
				'header' => Mage::helper( 'reminder' )->__( 'Order type' ),
				'renderer' => 'Kinento_Reminder_Block_View_Main_Widget_Column_Renderer_Grouptype',
				'type'  => 'text',
				'width' => '70px',
				'filter' => false,
				'sortable' => false,
			) );

		// Widget 2
		$this->addColumn( 'reminders', array(
				'header' => Mage::helper( 'reminder' )->__( 'Reminders sent' ),
				'renderer' => 'Kinento_Reminder_Block_View_Main_Widget_Column_Renderer_Reminderssent',
				'type'  => 'text',
				'width' => '70px',
				'filter' => false,
				'sortable' => false,
			) );

		// Widget 3
		$this->addColumn( 'emailstatus', array(
				'header' => Mage::helper( 'reminder' )->__( 'Disable notifications' ),
				'renderer' => 'Kinento_Reminder_Block_View_Main_Widget_Column_Renderer_Status',
				'type'  => 'text',
				'width' => '70px',
				'filter' => false,
				'sortable' => false,
			) );

		// Widget 4
		$this->addColumn( 'paymentmethod', array(
				'header' => Mage::helper( 'reminder' )->__( 'Payment method' ),
				'renderer' => 'Kinento_Reminder_Block_View_Main_Widget_Column_Renderer_Payment',
				'type'  => 'text',
				'width' => '70px',
				'filter' => false,
				'sortable' => false,
			) );

		// Widget 5
		$this->addColumn( 'manualreminder', array(
				'header' => Mage::helper( 'reminder' )->__( 'Manual reminders' ),
				'renderer' => 'Kinento_Reminder_Block_View_Main_Widget_Column_Renderer_Manual',
				'type'  => 'text',
				'width' => '70px',
				'filter' => false,
				'sortable' => false,
			) );

		// End of widgets

		return parent::_prepareColumns();
	}

	protected function _prepareMassaction() {
		$this->setMassactionIdField( 'entity_id' );
		$this->getMassactionBlock()->setFormFieldName( 'reminder' );
		$this->getMassactionBlock()->setUseSelectAll( false );

		$this->getMassactionBlock()->addItem( 'send_reminder_now', array(
				'label'    => Mage::helper( 'reminder' )->__( 'Force send reminder(s) now' ),
				'url'      => $this->getUrl( '*/view/massSend' )
			) );

		$this->getMassactionBlock()->addItem( 'selected_enable', array(
				'label'    => Mage::helper( 'reminder' )->__( 'Enable selected' ),
				'url'      => $this->getUrl( '*/view/massChange/status/enabled' )
			) );

		$this->getMassactionBlock()->addItem( 'selected_disable', array(
				'label'    => Mage::helper( 'reminder' )->__( 'Disable selected' ),
				'url'      => $this->getUrl( '*/view/massChange/status/disabled' )
			) );

		return $this;
	}
}
?>
