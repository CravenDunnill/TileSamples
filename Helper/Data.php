<?php
/**
 * CravenDunnill_TileSamples extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @category   CravenDunnill
 * @package    CravenDunnill_TileSamples
 * @copyright  Copyright (c) 2025 CravenDunnill
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace CravenDunnill\TileSamples\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Model\Product;

class Data extends AbstractHelper
{
	/**
	 * @var CheckoutSession
	 */
	protected $checkoutSession;

	/**
	 * Data constructor.
	 * @param Context $context
	 * @param CheckoutSession $checkoutSession
	 */
	public function __construct(
		Context $context,
		CheckoutSession $checkoutSession
	) {
		$this->checkoutSession = $checkoutSession;
		parent::__construct($context);
	}

	/**
	 * Check if a product has the tile sample attribute
	 *
	 * @param Product $product
	 * @return string|null
	 */
	public function getTileSampleSku(Product $product)
	{
		return $product->getData('tile_sku_sample_cut');
	}

	/**
	 * Check if tile sample is already in cart
	 *
	 * @param string $sku
	 * @return bool
	 */
	public function isTileSampleInCart($sku)
	{
		$quote = $this->checkoutSession->getQuote();
		$items = $quote->getAllItems();
		
		foreach ($items as $item) {
			if ($item->getSku() == $sku) {
				return true;
			}
		}
		
		return false;
	}
}