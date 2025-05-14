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

namespace CravenDunnill\TileSamples\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use CravenDunnill\TileSamples\Helper\Data as TileSampleHelper;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class TileSample extends Template
{
	/**
	 * @var Registry
	 */
	protected $registry;

	/**
	 * @var TileSampleHelper
	 */
	protected $tileSampleHelper;

	/**
	 * @var ProductRepository
	 */
	protected $productRepository;
	
	/**
	 * @var StockRegistryInterface
	 */
	protected $stockRegistry;

	/**
	 * TileSample constructor.
	 *
	 * @param Context $context
	 * @param Registry $registry
	 * @param TileSampleHelper $tileSampleHelper
	 * @param ProductRepository $productRepository
	 * @param StockRegistryInterface $stockRegistry
	 * @param array $data
	 */
	public function __construct(
		Context $context,
		Registry $registry,
		TileSampleHelper $tileSampleHelper,
		ProductRepository $productRepository,
		StockRegistryInterface $stockRegistry,
		array $data = []
	) {
		$this->registry = $registry;
		$this->tileSampleHelper = $tileSampleHelper;
		$this->productRepository = $productRepository;
		$this->stockRegistry = $stockRegistry;
		parent::__construct($context, $data);
	}

	/**
	 * Get current product
	 *
	 * @return \Magento\Catalog\Model\Product|null
	 */
	public function getCurrentProduct()
	{
		return $this->registry->registry('current_product');
	}

	/**
	 * Check if tile sample is available
	 *
	 * @return bool
	 */
	public function isTileSampleAvailable()
	{
		$product = $this->getCurrentProduct();
		if (!$product) {
			return false;
		}

		$sampleSku = $this->tileSampleHelper->getTileSampleSku($product);
		if (empty($sampleSku)) {
			return false;
		}
		
		// Check if sample product is enabled and in stock
		return $this->isSampleProductAvailable($sampleSku);
	}

	/**
	 * Check if sample product is enabled and in stock
	 *
	 * @param string $sampleSku
	 * @return bool
	 */
	protected function isSampleProductAvailable($sampleSku)
	{
		try {
			// Load sample product
			$sampleProduct = $this->productRepository->get($sampleSku);
			
			// Check if product is enabled
			if (!$sampleProduct->getStatus() || $sampleProduct->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED) {
				return false;
			}
			
			// Check stock status
			$stockItem = $this->stockRegistry->getStockItemBySku($sampleSku);
			if (!$stockItem->getIsInStock() || ($stockItem->getQty() <= 0 && !$stockItem->getBackorders())) {
				return false;
			}
			
			return true;
		} catch (NoSuchEntityException $e) {
			return false;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Get tile sample SKU
	 *
	 * @return string|null
	 */
	public function getTileSampleSku()
	{
		$product = $this->getCurrentProduct();
		if (!$product) {
			return null;
		}

		return $this->tileSampleHelper->getTileSampleSku($product);
	}

	/**
	 * Check if sample is already in cart
	 *
	 * @return bool
	 */
	public function isSampleInCart()
	{
		$sampleSku = $this->getTileSampleSku();
		if (!$sampleSku) {
			return false;
		}

		return $this->tileSampleHelper->isTileSampleInCart($sampleSku);
	}

	/**
	 * Get add to cart URL
	 *
	 * @return string
	 */
	public function getAddToCartUrl()
	{
		$sampleSku = $this->getTileSampleSku();
		if (!$sampleSku) {
			return '';
		}

		return $this->getUrl('tilesample/cart/add', ['sku' => $sampleSku]);
	}
}