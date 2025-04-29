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
use Magento\Framework\Message\ManagerInterface;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductRepository;
use CravenDunnill\TileSamples\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

class Add extends Action implements HttpPostActionInterface
{
	/**
	 * @var RedirectFactory
	 */
	protected $resultRedirectFactory;

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
	 * Add constructor.
	 *
	 * @param Context $context
	 * @param RedirectFactory $resultRedirectFactory
	 * @param ManagerInterface $messageManager
	 * @param Cart $cart
	 * @param ProductRepository $productRepository
	 * @param Data $helper
	 */
	public function __construct(
		Context $context,
		RedirectFactory $resultRedirectFactory,
		ManagerInterface $messageManager,
		Cart $cart,
		ProductRepository $productRepository,
		Data $helper
	) {
		parent::__construct($context);
		$this->resultRedirectFactory = $resultRedirectFactory;
		$this->messageManager = $messageManager;
		$this->cart = $cart;
		$this->productRepository = $productRepository;
		$this->helper = $helper;
	}

	/**
	 * Execute action
	 *
	 * @return \Magento\Framework\Controller\Result\Redirect
	 */
	public function execute()
	{
		$resultRedirect = $this->resultRedirectFactory->create();
		$sku = $this->getRequest()->getParam('sku');

		if (!$sku) {
			$this->messageManager->addErrorMessage(__('Invalid tile sample SKU.'));
			return $resultRedirect->setRefererUrl();
		}

		try {
			// Check if sample is already in cart
			if ($this->helper->isTileSampleInCart($sku)) {
				$this->messageManager->addErrorMessage(__('You already have this tile sample in your cart.'));
				return $resultRedirect->setRefererUrl();
			}

			// Load the product by SKU
			$product = $this->productRepository->get($sku);
			
			// Add product to cart
			$this->cart->addProduct($product, ['qty' => 1]);
			$this->cart->save();
			
			$this->messageManager->addSuccessMessage(__('Free cut sample has been added to your cart.'));
		} catch (NoSuchEntityException $e) {
			$this->messageManager->addErrorMessage(__('The requested sample product does not exist.'));
		} catch (LocalizedException $e) {
			$this->messageManager->addErrorMessage($e->getMessage());
		} catch (\Exception $e) {
			$this->messageManager->addExceptionMessage($e, __('We can\'t add the tile sample to your cart right now.'));
		}

		return $resultRedirect->setRefererUrl();
	}
}