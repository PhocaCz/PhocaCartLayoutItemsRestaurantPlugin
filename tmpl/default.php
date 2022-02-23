<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Router\Route;

$layoutI	= new FileLayout('product_image', null, array('component' => 'com_phocacart'));
$layoutAI	= new FileLayout('button_add_to_cart_icon', null, array('component' => 'com_phocacart'));
$layoutP	= new FileLayout('product_price', null, array('component' => 'com_phocacart'));
$layoutAB	= new FileLayout('attribute_options_box', null, array('component' => 'com_phocacart'));
$layoutV	= new FileLayout('button_product_view', null, array('component' => 'com_phocacart'));
$layoutPFS	= new FileLayout('form_part_start_add_to_cart_list', null, array('component' => 'com_phocacart'));
$layoutPFE	= new FileLayout('form_part_end', null, array('component' => 'com_phocacart'));
$layoutA	= new FileLayout('button_add_to_cart_list', null, array('component' => 'com_phocacart'));
$layoutA2	= new FileLayout('button_buy_now_paddle', null, array('component' => 'com_phocacart'));
$layoutA3	= new FileLayout('button_external_link', null, array('component' => 'com_phocacart'));
$layoutA4 	= new FileLayout('button_quickview', null, array('component' => 'com_phocacart'));
$layoutBSH	= new FileLayout('button_submit_hidden', null, array('component' => 'com_phocacart'));
$layoutQ	= new FileLayout('button_ask_question', null, array('component' => 'com_phocacart'));

$d 		= $displayData;
$t		= $d['t'];
$s      = $d['s'];



if (!empty($items)) {

    $pluginName = strip_tags(htmlspecialchars($this->name));
    $id = 'ph' . str_replace('_', '', ucwords($pluginName, '_'));

	HTMLHelper::_('stylesheet', 'media/plg_pcl_'.$pluginName.'/css/default.css', array('version' => 'auto'));

	$price	= new PhocacartPrice;
	$lt		= $t['layouttype'];
	$categoryTitle = '';
	$headerStarted = 0;

	echo '<div id="'.$id.'" class="'.PhocacartRenderFront::completeClass(array($s['c']['row'], $t['class_row_flex'], $pluginName, $t['class_lazyload'], $lt)).'">';


	$i = 1;
	$count = count($items);


	foreach($items as $v) {

		// DIFF CATEGORY / ITEMS
		$t['categoryid'] = (int)$v->catid;

		$label 		= PhocacartRenderFront::getLabel($v->date, $v->sales, $v->featured);
		$link 		= Route::_(PhocacartRoute::getItemRoute($v->id, $v->catid, $v->alias, $v->catalias));


		// Image data
		$attributesOptions 	= $t['hide_attributes_category'] == 0 ? PhocacartAttribute::getAttributesAndOptions((int)$v->id) : array();
		if (!isset($v->additional_image)) { $v->additional_image = '';}
		$image = PhocacartImage::getImageDisplay($v->image, $v->additional_image, $t['pathitem'], $t['switch_image_category_items'], $t['image_width_cat'], $t['image_height_cat'], '', $lt, $attributesOptions);

		if ($categoryTitle != $v->cattitle) {

		    if ($headerStarted == 1) {

		        echo '</div>';// close the header group
            }

			echo '<h3>'.$v->cattitle.'</h3>';
			$categoryTitle = $v->cattitle;

			$headerStarted = 1;
			echo '<div class="row phItemGroup'.str_replace('-', '', ucwords($v->catalias, '-')).'">';// start the header group

		}


		// Image data
		$attributesOptions 	= $t['hide_attributes_category'] == 0 ? PhocacartAttribute::getAttributesAndOptions((int)$v->id) : array();
		if (!isset($v->additional_image)) { $v->additional_image = '';}
		$image = PhocacartImage::getImageDisplay($v->image, $v->additional_image, $t['pathitem'], $t['switch_image_category_items'], $t['image_width_cat'], $t['image_height_cat'], 'small', $lt, $attributesOptions);


		// :L: IMAGE
		$dI	= array();
		$imageO = '';
		if (isset($image['image']->rel) && $image['image']->rel != '') {
			$dI['t']				= $t;
			$dI['s']				= $s;
			$dI['product_id']		= (int)$v->id;
			$dI['layouttype']		= $lt;
            $dI['title']			= $v->title;
			$dI['image']			= $image;
			$dI['typeview']			= 'Items';
			$imageO = $layoutI->render($dI);
		}


		// :L: PRICE
		$dP = array();
		$dP['type'] = $v->type;// PRODUCTTYPE
		$priceO = '';
		if ($t['can_display_price']) {

			$dP['priceitems']	= $price->getPriceItems($v->price, $v->taxid, $v->taxrate, $v->taxcalculationtype, $v->taxtitle, $v->unit_amount, $v->unit_unit, 1, 1, $v->group_price);

			$price->getPriceItemsChangedByAttributes($dP['priceitems'], $attributesOptions, $price, $v);
			$dP['priceitemsorig']= array();
			if ($v->price_original != '' && $v->price_original > 0) {
				$dP['priceitemsorig'] = $price->getPriceItems($v->price_original, $v->taxid, $v->taxrate, $v->taxcalculationtype);
			}
			//$dP['class']		= 'ph-category-price-box '.$lt;
			$dP['class']		= 'ph-category-price-box';// Cannot be dynamic as can change per ajax - this can cause jumping of boxes
			$dP['product_id']	= (int)$v->id;
			$dP['typeview']		= 'Items';

			// Display discount price
			// Move standard prices to new variable (product price -> product discount)
			$dP['priceitemsdiscount']		= $dP['priceitems'];
			$dP['discount'] 				= PhocacartDiscountProduct::getProductDiscountPrice($v->id, $dP['priceitemsdiscount']);

			// Display cart discount (global discount) in product views - under specific conditions only
			// Move product discount prices to new variable (product price -> product discount -> product discount cart)
			$dP['priceitemsdiscountcart']	= $dP['priceitemsdiscount'];
			$dP['discountcart']				= PhocacartDiscountCart::getCartDiscountPriceForProduct($v->id, $v->catid, $dP['priceitemsdiscountcart']);

			$dP['zero_price']		= 1;// Apply zero price if possible
			$priceO = $layoutP->render($dP);
		}


		// :L: LINK TO PRODUCT VIEW
		$dV = array();
		$dV['s'] = $s;
		$dV['display_view_product_button'] 	= $t['display_view_product_button'];
		if ((int)$t['display_view_product_button'] > 0) {
			$dV['link']							= $link;
			//$dV['display_view_product_button'] 	= $t['display_view_product_button'];
		}


		// :L: ADD TO CART
		$dA = $dA2 = $dA3 = $dA4 = $dAb = $dF = array();
		$icon['addtocart'] = '';

		// STOCK ===================================================
		// Set stock: product, variations, or advanced stock status
		$dSO 				= '';
		$dA['class_btn']	= '';
		$dA['class_icon']	= '';
		$dA['s']	        = $s;
		if ($t['display_stock_status'] == 2 || $t['display_stock_status'] == 3) {

			$stockStatus 				= array();
			$stock 						= PhocacartStock::getStockItemsChangedByAttributes($stockStatus, $attributesOptions, $v);

			if ($t['hide_add_to_cart_stock'] == 1 && (int)$stock < 1) {
				$dA['class_btn'] 		= 'ph-visibility-hidden';// hide button
				$dA['class_icon']		= 'ph-display-none';// hide icon
			}

			if($stockStatus['stock_status'] || $stockStatus['stock_count'] !== false) {
				$dS							= array();
				$dS['s']	                = $s;
				$dS['class']				= 'ph-category-stock-box';
				$dS['product_id']			= (int)$v->id;
				$dS['typeview']				= 'Category';
				$dS['stock_status_output'] 	= PhocacartStock::getStockStatusOutput($stockStatus);
				$dSO = $layoutS->render($dS);
			}

			if($stockStatus['min_quantity']) {
				$dPOQ						= array();
				$dPOQ['s']	                = $s;
				$dPOQ['text']				= JText::_('COM_PHOCACART_MINIMUM_ORDER_QUANTITY');
				$dPOQ['status']				= $stockStatus['min_quantity'];
				$dSO .= $layoutPOQ->render($dPOQ);
			}

			if($stockStatus['min_multiple_quantity']) {
				$dPOQ						= array();
				$dPOQ['s']	                = $s;
				$dPOQ['text']				= JText::_('COM_PHOCACART_MINIMUM_MULTIPLE_ORDER_QUANTITY');
				$dPOQ['status']				= $stockStatus['min_multiple_quantity'];
				$dSO .= $layoutPOQ->render($dPOQ);
			}
		}
		// END STOCK ================================================


		// ------------------------------------
		// BUTTONS + ICONS
		// ------------------------------------
		// Prepare data for Add to cart button
		// - Add To Cart Standard Button
		// - Add to Cart Icon Button
		// - Add to Cart Icon Only
		if ((int)$t['category_addtocart'] == 1 || (int)$t['category_addtocart'] == 4 || $t['display_addtocart_icon'] == 1) {

			// FORM DATA
            $dF['s']	                = $s;
			$dF['linkch']				= $t['linkcheckout'];// link to checkout (add to cart)
			$dF['id']					= (int)$v->id;
			$dF['catid']				= $t['categoryid'];
			$dF['return']				= $t['actionbase64'];
			$dF['typeview']				= 'Items';
			$dA['addtocart']			= $t['category_addtocart'];
			$dA['addtocart_icon']		= $t['display_addtocart_icon'];

			// Both buttons + icon
			$dA['s']					= $s;
			$dA['id']					= (int)$v->id;
			$dA['link']					= $link;// link to item (product) view e.g. when there are required attributes - we cannot add it to cart
			$dA['addtocart']			= $t['category_addtocart'];
			$dA['method']				= $t['add_cart_method'];
			$dA['typeview']				= 'Items';

			// ATTRIBUTES, OPTIONS
			$dAb['s']						= $s;
			$dAb['attr_options']			= $attributesOptions;
			$dAb['hide_attributes']			= $t['hide_attributes_category'];
			$dAb['dynamic_change_image'] 	= $t['dynamic_change_image'];
			$dAb['remove_select_option_attribute']	= $t['remove_select_option_attribute'];
			$dAb['zero_attribute_price']	= $t['zero_attribute_price'];
			$dAb['pathitem']				= $t['pathitem'];
			$dAb['product_id']				= (int)$v->id;
			$dAb['image_size']				= $image['size'];
			$dAb['typeview']				= 'Items';
			$dAb['price']					= $price;

			// Attribute is required and we don't display it in category/items view, se we need to redirect to detail view
			$dA['selectoptions']	= 0;
			if (isset($v->attribute_required) && $v->attribute_required == 1 && $t['hide_attributes_category'] == 1) {
				$dA['selectoptions']	= 1;
			}

			// Add To Cart as Icon
			if ($t['display_addtocart_icon'] == 1) {
				$icon['addtocart'] 	= $layoutAI->render($dA);

			}
		}

		// Different button or icons
		$addToCartHidden = 0;// Design parameter - if there is no button (add to cart, paddle link, external link), used e.g. for displaying ask a question button
		// Type 3 is Product Price on Demand - there is no add to cart button except Quick View Button
		if ($v->type == 3 && (int)$t['category_addtocart'] != 104) {
			// PRODUCTTYPE - price on demand price cannot be added to cart
			$dA = array(); // Skip Standard Add to cart button
			$icon['addtocart'] = '';// Skip Add to cart icon except Quick View Button
			$dF = array();// Skip form
			$addToCartHidden = 1;
		} else if ($t['hide_add_to_cart_zero_price'] == 1 && $v->price == 0) {
			// Don't display Add to Cart in case the price is zero
			$dA = array(); // Skip Standard Add to cart button
			$icon['addtocart'] = '';// Skip Add to cart icon
			$dF = array();// Skip form
			$addToCartHidden = 1;
		} else if ((int)$t['category_addtocart'] == 1 || (int)$t['category_addtocart'] == 4) {
			// ADD TO CART BUTTONS - we have data yet
		} else if ((int)$t['category_addtocart'] == 102 && (int)$v->external_id != '') {
			// EXTERNAL LINK PADDLE
			$dA2['t']				= $t;
			$dA2['s']				= $s;
			$dA2['external_id']		= (int)$v->external_id;
			$dA2['return']			= $t['actionbase64'];

			$dA = array(); // Skip Standard Add to cart button
			$icon['addtocart'] = '';// Skip Add to cart icon
			$dF = array();// Skip form

		} else if ((int)$t['category_addtocart'] == 103 && $v->external_link != '') {
			// EXTERNAL LINK
			$dA3['t']				= $t;
			$dA3['s']				= $s;
			$dA3['external_link']	= $v->external_link;
			$dA3['external_text']	= $v->external_text;
			$dA3['return']			= $t['actionbase64'];

			$dA = array(); // Skip Standard Add to cart button
			$icon['addtocart'] = '';// Skip Add to cart icon
			$dF = array();// Skip form

		} else if ((int)$t['category_addtocart'] == 104) {
			// QUICK VIEW
			$dA4				= array();
			$dA4['s']			= $s;
			$dA4['linkqvb']		= Route::_(PhocacartRoute::getItemRoute($v->id, $v->catid, $v->alias, $v->catalias));
			$dA4['id']			= (int)$v->id;
			$dA4['catid']		= $t['categoryid'];
			$dA4['return']		= $t['actionbase64'];
			$dA4['button'] 		= 1;

			$dA = array(); // Skip Standard Add to cart button
			$icon['addtocart'] = '';// Skip Add to cart icon
			$dF = array();// Skip form

		} else {
			// ADD TO CART ICON ONLY (NO BUTTONS)
			$dA = array(); // Skip Standard Add to cart button
			// We remove the $dA completely, even for the icon, but the icon has the data already stored in $icon['addtocart']
			// so no problem with removing the data completely
			// $dA for button will be rendered
			// $dA for icon was rendered already
			// Do not skip the form here
			$addToCartHidden = 1;
		}
		// ---------------------------- END BUTTONS


		// Image
		echo '<div class="'.$s['c']['col.xs12.sm1.md1']	.' ph-item-image">'.$imageO.'</div>';

		// Weight, Volume, Unit
		echo '<div class="'.$s['c']['col.xs12.sm1.md1']	.' ph-item-wvu">';


		if (isset($v->weight) && $v->weight != 0) {
			echo PhocacartPrice::cleanPrice($v->weight);
		} else if(isset($v->volume) && $v->volume != 0) {
			echo PhocacartPrice::cleanPrice($v->volume);
		}

		if (isset($v->unit_unit) && $v->unit_unit != '') {
			echo ' '.$v->unit_unit;
		}
		echo '</div>';

		// Title
		echo '<div class="'.$s['c']['col.xs12.sm6.md6']	.'">';

		echo '<div class="ph-item-title">' . $v->title. '</div>';

		if ($t['cv_display_description'] == 1 && $v->description != '') {
			echo '<div class="ph-item-desc">' . HTMLHelper::_('content.prepare', $v->description) . '</div>';
		}

		echo '</div>';
		// Price
		echo '<div class="'.$s['c']['col.xs12.sm1.md1']	.' ph-item-price">'.$priceO.'</div>';


		// Add to cart
		echo '<div class="'.$s['c']['col.xs12.sm3.md3']	.'">';


		// VIEW PRODUCT BUTTON
		echo '<div class="ph-item-action-box ph-caption ph-category-action-box-buttons '.$lt.'">';
		echo '<div class="ph-category-action-buttons '.$lt.'">';

		// :L: Stock status
		if (!empty($dSO)) { echo $dSO;}

		// Start Form
		if (!empty($dF)) { echo $layoutPFS->render($dF);}

		// :L: ATTRIBUTES AND OPTIONS
		if (!empty($dAb)) { echo $layoutAB->render($dAb);}

		// :L: LINK TO PRODUCT VIEW
		if (!empty($dV)) { echo $layoutV->render($dV);}

		// :L: ADD TO CART
		if (!empty($dA)) { echo $layoutA->render($dA);} else if ($d['icon']['addtocart'] != '') { echo $layoutBSH->render();}

		// :L: ASK A QUESTION
		if (!empty($dQ)) { echo $layoutQ->render($dQ);}

		// End Form
		if (!empty($dF)) { echo $layoutPFE->render();}

		if (!empty($dA2)) { echo $layoutA2->render($dA2);}
		if (!empty($dA3)) { echo $layoutA3->render($dA3);}
		if (!empty($dA4)) { echo $layoutA4->render($dA4);}

		echo '</div>';// end category_action_buttons


		echo '<div class="ph-cb"></div>';

		echo '</div>';// end action box



		echo '</div>'; // end row


		echo '<div class="ph-cb '.$lt.'"></div>';

		if ($i == $count) {
		    echo '</div>';// close the last header group
		}

		$i++;
	}

	echo '</div>';// end row (row-flex)
	echo '<div class="ph-cb '.$lt.'"></div>';
}
?>
