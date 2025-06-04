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

namespace CravenDunnill\TileSamples\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductRepository;
use CravenDunnill\TileSamples\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class Add extends Action implements HttpPostActionInterface
{
	/**
	 * @var RedirectFactory
	 */
	protected $resultRedirectFactory;

	/**
	 * @var JsonFactory
	 */
	protected $resultJsonFactory;

	/**
	 * @var ManagerInterface
	 */
	protected $messageManager;

	/**
	 * @var Cart
	 */
	protected $cart;

	/**
	 * @var ProductRepository
	 */
	protected $productRepository;

	/**
	 * @var Data
	 */
	protected $helper;
	
	/**
	 * @var StockRegistryInterface
	 */
	protected $stockRegistry;

	/**
	 * Add constructor.
	 *
	 * @param Context $context
	 * @param RedirectFactory $resultRedirectFactory
	 * @param ManagerInterface $messageManager
	 * @param Cart $cart
	 * @param ProductRepository $productRepository
	 * @param Data $helper
	 * @param StockRegistryInterface $stockRegistry
	 * @param JsonFactory $resultJsonFactory
	 */
	public function __construct(
		Context $context,
		RedirectFactory $resultRedirectFactory,
		ManagerInterface $messageManager,
		Cart $cart,
		ProductRepository $productRepository,
		Data $helper,
		StockRegistryInterface $stockRegistry,
		JsonFactory $resultJsonFactory
	) {
		parent::__construct($context);
		$this->resultRedirectFactory = $resultRedirectFactory;
		$this->messageManager = $messageManager;
		$this->cart = $cart;
		$this->productRepository = $productRepository;
		$this->helper = $helper;
		$this->stockRegistry = $stockRegistry;
		$this->resultJsonFactory = $resultJsonFactory;
	}

	/**
	 * Execute action
	 *
	 * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\Result\Json
	 */
	public function execute()
	{
		$sku = $this->getRequest()->getParam('sku');
		$isAjax = $this->getRequest()->isAjax();

		if (!$sku) {
			$message = __('Invalid tile sample SKU.');
			$this->messageManager->addErrorMessage($message);
			
			if ($isAjax) {
				$result = $this->resultJsonFactory->create();
				return $result->setData([
					'success' => false,
					'message' => $message->render()
				]);
			}
			
			$resultRedirect = $this->resultRedirectFactory->create();
			return $resultRedirect->setRefererUrl();
		}

		try {
			// Check if sample is already in cart
			if ($this->helper->isTileSampleInCart($sku)) {
				$message = __('You already have this tile sample in your cart.');
				$this->messageManager->addErrorMessage($message);
				
				if ($isAjax) {
					$result = $this->resultJsonFactory->create();
					return $result->setData([
						'success' => false,
						'message' => $message->render(),
						'already_in_cart' => true
					]);
				}
				
				$resultRedirect = $this->resultRedirectFactory->create();
				return $resultRedirect->setRefererUrl();
			}

			// Load the product by SKU
			$product = $this->productRepository->get($sku);
			
			// Check if product is enabled
			if (!$product->getStatus() || $product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED) {
				$message = __('This tile sample is not available.');
				$this->messageManager->addErrorMessage($message);
				
				if ($isAjax) {
					$result = $this->resultJsonFactory->create();
					return $result->setData([
						'success' => false,
						'message' => $message->render()
					]);
				}
				
				$resultRedirect = $this->resultRedirectFactory->create();
				return $resultRedirect->setRefererUrl();
			}
			
			// Check stock status
			$stockItem = $this->stockRegistry->getStockItemBySku($sku);
			if (!$stockItem->getIsInStock() || ($stockItem->getQty() <= 0 && !$stockItem->getBackorders())) {
				$message = __('This tile sample is out of stock.');
				$this->messageManager->addErrorMessage($message);
				
				if ($isAjax) {
					$result = $this->resultJsonFactory->create();
					return $result->setData([
						'success' => false,
						'message' => $message->render()
					]);
				}
				
				$resultRedirect = $this->resultRedirectFactory->create();
				return $resultRedirect->setRefererUrl();
			}
			
			// Add product to cart
			$this->cart->addProduct($product, ['qty' => 1]);
			$this->cart->save();
			
			$message = __('Free cut sample has been added to your cart.');
			$this->messageManager->addSuccessMessage($message);
			
			if ($isAjax) {
				$result = $this->resultJsonFactory->create();
				return $result->setData([
					'success' => true,
					'message' => $message->render(),
					'product_name' => $product->getName(),
					'product_sku' => $sku
				]);
			}
			
		} catch (NoSuchEntityException $e) {
			$message = __('The requested sample product does not exist.');
			$this->messageManager->addErrorMessage($message);
			
			if ($isAjax) {
				$result = $this->resultJsonFactory->create();
				return $result->setData([
					'success' => false,
					'message' => $message->render()
				]);
			}
		} catch (LocalizedException $e) {
			$this->messageManager->addErrorMessage($e->getMessage());
			
			if ($isAjax) {
				$result = $this->resultJsonFactory->create();
				return $result->setData([
					'success' => false,
					'message' => $e->getMessage()
				]);
			}
		} catch (\Exception $e) {
			$message = __('We can\'t add the tile sample to your cart right now.');
			$this->messageManager->addExceptionMessage($e, $message);
			
			if ($isAjax) {
				$result = $this->resultJsonFactory->create();
				return $result->setData([
					'success' => false,
					'message' => $message->render()
				]);
			}
		}

		$resultRedirect = $this->resultRedirectFactory->create();
		return $resultRedirect->setRefererUrl();
	}
}