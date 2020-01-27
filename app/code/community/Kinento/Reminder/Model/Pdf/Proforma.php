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


class Kinento_Reminder_Model_Pdf_Proforma extends Kinento_Reminder_Model_Pdf_Abstract
{

	public function getPdf( $order ) {
		$this->_beforeGetPdf();
		$this->_initRenderer( 'order' );

		$pdf = new Zend_Pdf();
		$this->_pdf = $pdf;
		$style = new Zend_Pdf_Style();
		$this->_setFontBold( $style, 10 );

		$order = Mage::getModel( 'sales/order' )->loadByIncrementId( $order['increment_id'] );

		if ( $this->getStoreId() ) {
			Mage::app()->getLocale()->emulate( $this->getStoreId() );
		}
		$page = $pdf->newPage( Zend_Pdf_Page::SIZE_A4 );
		$pdf->pages[] = $page;

		// Add image
		$this->insertLogo( $page, $this->getStoreId() );

		// Add address
		$this->insertAddress( $page, $this->getStoreId() );

		// Add head
		$this->insertOrder( $page, $order, false );


		$page->setFillColor( new Zend_Pdf_Color_GrayScale( 1 ) );
		$this->_setFontRegular( $page );
		$page->drawText( Mage::helper( 'sales' )->__( 'Order # ' ) . $order->getIncrementId(), 35, 780, 'UTF-8' );

		// Add table
		$page->setFillColor( new Zend_Pdf_Color_RGB( 0.93, 0.92, 0.92 ) );
		$page->setLineColor( new Zend_Pdf_Color_GrayScale( 0.5 ) );
		$page->setLineWidth( 0.5 );

		$page->drawRectangle( 25, $this->y, 570, $this->y -15 );
		$this->y -=10;

		// Add table head
		$page->setFillColor( new Zend_Pdf_Color_RGB( 0.4, 0.4, 0.4 ) );
		$page->drawText( Mage::helper( 'sales' )->__( 'Product' ), 35, $this->y, 'UTF-8' );
		$page->drawText( Mage::helper( 'sales' )->__( 'SKU' ), 240, $this->y, 'UTF-8' );
		$page->drawText( Mage::helper( 'sales' )->__( 'Price' ), 380, $this->y, 'UTF-8' );
		$page->drawText( Mage::helper( 'sales' )->__( 'QTY' ), 430, $this->y, 'UTF-8' );
		$page->drawText( Mage::helper( 'sales' )->__( 'Tax' ), 480, $this->y, 'UTF-8' );
		$page->drawText( Mage::helper( 'sales' )->__( 'Subtotal' ), 535, $this->y, 'UTF-8' );

		$this->y -=15;

		$page->setFillColor( new Zend_Pdf_Color_GrayScale( 0 ) );

		// Add body
		foreach ( $order->getAllItems() as $item ) {
			if ( $item->getParentItem() ) {
				continue;
			}

			$shift = array();
			if ( $this->y<15 ) {
				// Add new table head
				$page = $pdf->newPage( Zend_Pdf_Page::SIZE_A4 );
				$pdf->pages[] = $page;
				$this->y = 800;

				$this->_setFontRegular( $page );
				$page->setFillColor( new Zend_Pdf_Color_RGB( 0.93, 0.92, 0.92 ) );
				$page->setLineColor( new Zend_Pdf_Color_GrayScale( 0.5 ) );
				$page->setLineWidth( 0.5 );
				$page->drawRectangle( 25, $this->y, 570, $this->y-15 );
				$this->y -=10;

				$page->setFillColor( new Zend_Pdf_Color_RGB( 0.4, 0.4, 0.4 ) );
				$page->drawText( Mage::helper( 'sales' )->__( 'Product' ), 35, $this->y, 'UTF-8' );
				$page->drawText( Mage::helper( 'sales' )->__( 'SKU' ), 240, $this->y, 'UTF-8' );
				$page->drawText( Mage::helper( 'sales' )->__( 'Price' ), 380, $this->y, 'UTF-8' );
				$page->drawText( Mage::helper( 'sales' )->__( 'QTY' ), 430, $this->y, 'UTF-8' );
				$page->drawText( Mage::helper( 'sales' )->__( 'Tax' ), 480, $this->y, 'UTF-8' );
				$page->drawText( Mage::helper( 'sales' )->__( 'Subtotal' ), 535, $this->y, 'UTF-8' );

				$page->setFillColor( new Zend_Pdf_Color_GrayScale( 0 ) );
				$this->y -=20;
			}

			// Draw item
			$this->_drawItem( $item, $page, $order );
		}

		// Add totals
		$page = $this->insertTotals( $page, $order );

		if ( $this->getStoreId() ) {
			Mage::app()->getLocale()->revert();
		}

		$this->_afterGetPdf();

		return $pdf;
	}

	protected function insertTotals( $page, $source ) {
		$order = $source;

		$totals = $this->_getTotalsList( $source );

		$lineBlock = array(
			'lines'  => array(),
			'height' => 15
		);
		foreach ( $totals as $total ) {
			$amount = $source->getDataUsingMethod( $total['source_field'] );
			$displayZero = ( isset( $total['display_zero'] ) ? $total['display_zero'] : 0 );

			if ( $amount != 0 || $displayZero ) {
				$amount = $order->formatPriceTxt( $amount );

				if ( isset( $total['amount_prefix'] ) && $total['amount_prefix'] ) {
					$amount = "{$total['amount_prefix']}{$amount}";
				}

				$fontSize = ( isset( $total['font_size'] ) ? $total['font_size'] : 7 );

				$label = Mage::helper( 'sales' )->__( $total['title'] ) . ':';

				$lineBlock['lines'][] = array(
					array(
						'text'      => $label,
						'feed'      => 475,
						'align'     => 'right',
						'font_size' => $fontSize,
						'font'      => 'bold'
					),
					array(
						'text'      => $amount,
						'feed'      => 565,
						'align'     => 'right',
						'font_size' => $fontSize,
						'font'      => 'bold'
					),
				);
			}
		}

		$page = $this->drawLineBlocks( $page, array( $lineBlock ) );
		return $page;
	}

	protected function insertOrder( &$page, $source, $putOrderId = true ) {
		$order = $source;

		$page->setFillColor( new Zend_Pdf_Color_GrayScale( 0.5 ) );

		$page->drawRectangle( 25, 790, 570, 755 );

		$page->setFillColor( new Zend_Pdf_Color_GrayScale( 1 ) );
		$this->_setFontRegular( $page );


		if ( $putOrderId ) {
			$page->drawText( Mage::helper( 'sales' )->__( 'Order # ' ).$order->getRealOrderId(), 35, 770, 'UTF-8' );
		}
		$page->drawText( Mage::helper( 'sales' )->__( 'Order Date: ' ) . Mage::helper( 'core' )->formatDate( $order->getCreatedAt(), 'medium', false ), 35, 760, 'UTF-8' );

		$page->setFillColor( new Zend_Pdf_Color_Rgb( 0.93, 0.92, 0.92 ) );
		$page->setLineColor( new Zend_Pdf_Color_GrayScale( 0.5 ) );
		$page->setLineWidth( 0.5 );
		$page->drawRectangle( 25, 755, 275, 730 );
		$page->drawRectangle( 275, 755, 570, 730 );

		$billingAddress = $this->_formatAddress( $order->getBillingAddress()->format( 'pdf' ) );

		if ( !$order->getIsVirtual() ) {
			$shippingAddress = $this->_formatAddress( $order->getShippingAddress()->format( 'pdf' ) );
			$shippingMethod  = $order->getShippingDescription();
		}

		$page->setFillColor( new Zend_Pdf_Color_GrayScale( 0 ) );
		$this->_setFontRegular( $page );
		$page->drawText( Mage::helper( 'sales' )->__( 'SOLD TO:' ), 35, 740 , 'UTF-8' );

		if ( !$order->getIsVirtual() ) {
			$page->drawText( Mage::helper( 'sales' )->__( 'SHIP TO:' ), 285, 740 , 'UTF-8' );
		}
		else {
			$page->drawText( Mage::helper( 'sales' )->__( 'Payment Method:' ), 285, 740 , 'UTF-8' );
		}

		if ( !$order->getIsVirtual() ) {
			$y = 730 - ( max( count( $billingAddress ), count( $shippingAddress ) ) * 10 + 5 );
		}
		else {
			$y = 730 - ( count( $billingAddress ) * 10 + 5 );
		}

		$page->setFillColor( new Zend_Pdf_Color_GrayScale( 1 ) );
		$page->drawRectangle( 25, 730, 570, $y );
		$page->setFillColor( new Zend_Pdf_Color_GrayScale( 0 ) );
		$this->_setFontRegular( $page );
		$this->y = 720;

		foreach ( $billingAddress as $value ) {
			if ( $value!=='' ) {
				$page->drawText( strip_tags( ltrim( $value ) ), 35, $this->y, 'UTF-8' );
				$this->y -=10;
			}
		}

		if ( !$order->getIsVirtual() ) {
			$this->y = 720;
			foreach ( $shippingAddress as $value ) {
				if ( $value!=='' ) {
					$page->drawText( strip_tags( ltrim( $value ) ), 285, $this->y, 'UTF-8' );
					$this->y -=10;
				}

			}

			$page->setFillColor( new Zend_Pdf_Color_Rgb( 0.93, 0.92, 0.92 ) );
			$page->setLineWidth( 0.5 );
			$page->drawRectangle( 25, $this->y, 275, $this->y-25 );
			$page->drawRectangle( 275, $this->y, 570, $this->y-25 );

			$this->y -=15;
			$this->_setFontBold( $page );
			$page->setFillColor( new Zend_Pdf_Color_GrayScale( 0 ) );
			$page->drawText( Mage::helper( 'sales' )->__( 'Payment Method' ), 35, $this->y, 'UTF-8' );
			$page->drawText( Mage::helper( 'sales' )->__( 'Shipping Method:' ), 285, $this->y , 'UTF-8' );

			$this->y -=10;
			$page->setFillColor( new Zend_Pdf_Color_GrayScale( 1 ) );

			$this->_setFontRegular( $page );
			$page->setFillColor( new Zend_Pdf_Color_GrayScale( 0 ) );

			$paymentLeft = 35;
			$yPayments   = $this->y - 15;
		}
		else {
			$yPayments   = 720;
			$paymentLeft = 285;
		}

		if ( !$order->getIsVirtual() ) {
			$this->y -=15;

			$page->drawText( $shippingMethod, 285, $this->y, 'UTF-8' );

			$yShipments = $this->y;


			$totalShippingChargesText = "(" . Mage::helper( 'sales' )->__( 'Total Shipping Charges' ) . " " . $order->formatPriceTxt( $order->getShippingAmount() ) . ")";

			$page->drawText( $totalShippingChargesText, 285, $yShipments-7, 'UTF-8' );
			$yShipments -=10;
			$tracks = $order->getTracksCollection();
			if ( count( $tracks ) ) {
				$page->setFillColor( new Zend_Pdf_Color_Rgb( 0.93, 0.92, 0.92 ) );
				$page->setLineWidth( 0.5 );
				$page->drawRectangle( 285, $yShipments, 510, $yShipments - 10 );
				$page->drawLine( 380, $yShipments, 380, $yShipments - 10 );

				$this->_setFontRegular( $page );
				$page->setFillColor( new Zend_Pdf_Color_GrayScale( 0 ) );
				$page->drawText( Mage::helper( 'sales' )->__( 'Title' ), 290, $yShipments - 7, 'UTF-8' );
				$page->drawText( Mage::helper( 'sales' )->__( 'Number' ), 385, $yShipments - 7, 'UTF-8' );

				$yShipments -=17;
				$this->_setFontRegular( $page, 6 );
				foreach ( $order->getTracksCollection() as $track ) {

					$CarrierCode = $track->getCarrierCode();
					if ( $CarrierCode!='custom' ) {
						$carrier = Mage::getSingleton( 'shipping/config' )->getCarrierInstance( $CarrierCode );
						$carrierTitle = $carrier->getConfigData( 'title' );
					}
					else {
						$carrierTitle = Mage::helper( 'sales' )->__( 'Custom Value' );
					}

					$truncatedCarrierTitle = substr( $carrierTitle, 0, 35 ) . ( strlen( $carrierTitle ) > 35 ? '...' : '' );
					$truncatedTitle = substr( $track->getTitle(), 0, 45 ) . ( strlen( $track->getTitle() ) > 45 ? '...' : '' );
					$page->drawText( $truncatedTitle, 300, $yShipments , 'UTF-8' );
					$page->drawText( $track->getNumber(), 395, $yShipments , 'UTF-8' );
					$yShipments -=7;
				}
			} else {
				$yShipments -= 7;
			}

			$currentY = min( $yPayments, $yShipments );

			// replacement of Shipments-Payments rectangle block
			$page->drawLine( 25, $this->y + 15, 25, $currentY );
			$page->drawLine( 25, $currentY, 570, $currentY );
			$page->drawLine( 570, $currentY, 570, $this->y + 15 );

			$this->y = $currentY;
			$this->y -= 15;
		}

	}

}
?>
