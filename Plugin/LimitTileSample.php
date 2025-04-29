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

namespace CravenDunnill\TileSamples\Plugin;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\Product;

class LimitTileSample
{
	/**
	 * @var ManagerInterface
	 */
	protected $messageManager;

	/**
	 * LimitTileSample constructor.
	 *
	 * @param ManagerInterface $messageManager
	 */
	public function __construct(
		ManagerInterface $messageManager
	) {
		$this->messageManager = $messageManager;
	}

	/**
	 * Check if a product is already in the cart before adding
	 *
	 * @param Cart $subject
	 * @param Product $productInfo
	 * @param array $requestInfo
	 * @return array
	 * @throws LocalizedException
	 */
	public function beforeAddProduct(
		Cart $subject,
		$productInfo,
		$requestInfo = null
	) {
		// Only check for tile sample products (those with SKUs that match the ones from tile_sku_sample_cut attribute)
		$productId = $productInfo->getId();
		$quote = $subject->getQuote();
		
		// Check if this product is already in the cart
		foreach ($quote->getAllItems() as $item) {
			if ($item->getProductId() == $productId) {
				throw new LocalizedException(__('You already have this tile sample in your cart.'));
			}
		}
		
		return [$productInfo, $requestInfo];
	}
}