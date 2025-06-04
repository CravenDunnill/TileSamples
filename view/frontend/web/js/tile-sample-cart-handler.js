define([
	'jquery',
	'Magento_Customer/js/customer-data',
	'mage/url'
], function($, customerData, url) {
	'use strict';
	
	return function(config) {
		console.log('[Tile Sample] Cart handler initialized');
		
		// Function to open minicart with proper positioning
		function openMinicart() {
			console.log('[Tile Sample] Opening minicart...');
			
			var isMobile = window.innerWidth <= 767;
			
			// Ensure minicart is positioned correctly
			var $minicart = $('#cd-minicart');
			if ($minicart.length) {
				$minicart.css({
					'position': 'fixed',
					'top': '0',
					'z-index': '9999',
					'height': '100vh'
				});
				
				if (isMobile) {
					$minicart.css({
						'width': '100%',
						'right': '-100%'
					});
				} else {
					$minicart.css({
						'width': '400px',
						'right': '-450px'
					});
				}
			}
			
			// Show minicart
			$('#cd-minicart').addClass('active');
			$('#cd-overlay').css('display', 'block');
			
			// Apply blur effect to content (desktop only)
			if (!isMobile) {
				$('.page-main, .page-footer, .nav-sections, .breadcrumbs').css('filter', 'blur(4px)');
			}
			
			// Prevent body scrolling
			var scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
			window.lastScrollPosition = scrollPosition;
			
			document.documentElement.classList.add('scroll-locked');
			document.body.classList.add('scroll-locked');
			document.body.style.setProperty('top', `-${scrollPosition}px`, 'important');
			
			// Refresh minicart content
			refreshMinicartContent();
		}
		
		// Function to refresh minicart content
		function refreshMinicartContent() {
			console.log('[Tile Sample] Refreshing minicart content');
			$('.cd-minicart-content').addClass('loading');
			
			$.ajax({
				url: url.build('cravendunnill_header/cart/minicart'),
				type: 'GET',
				cache: false,
				success: function(response) {
					console.log('[Tile Sample] Minicart content updated');
					$('.cd-minicart-items').html(response);
					updateSubtotal();
					$('.cd-minicart-content').removeClass('loading');
				},
				error: function(error) {
					console.error('[Tile Sample] Error updating minicart:', error);
					$('.cd-minicart-content').removeClass('loading');
				}
			});
		}
		
		// Function to update subtotal
		function updateSubtotal() {
			$.ajax({
				url: url.build('cravendunnill_header/cart/subtotal'),
				type: 'GET',
				cache: false,
				success: function(response) {
					$('#cd-minicart-subtotal').html(response);
				}
			});
		}
		
		// Function to update cart counter
		function updateCartCounter() {
			var cartData = customerData.get('cart');
			if (cartData() && typeof cartData().summary_count !== 'undefined') {
				var itemCount = parseInt(cartData().summary_count, 10);
				if (itemCount > 0) {
					$('.cd-cart-counter').removeClass('empty').text(itemCount);
					$('.cd-cart-counter').addClass('updated');
					setTimeout(function() {
						$('.cd-cart-counter').removeClass('updated');
					}, 500);
				} else {
					$('.cd-cart-counter').addClass('empty').text('');
				}
			}
		}
		
		$(document).ready(function() {
			console.log('[Tile Sample] Setting up tile sample form handlers');
			
			// Handle tile sample form submission
			$(document).on('submit', 'form[data-role="tile-sample-form"]', function(e) {
				console.log('[Tile Sample] Tile sample form submitted');
				
				var $form = $(this);
				var formData = new FormData(this);
				
				// Prevent default form submission
				e.preventDefault();
				
				// Show loading state
				var $button = $form.find('button[type="submit"]');
				var originalText = $button.find('span').text();
				$button.prop('disabled', true).find('span').text('Adding...');
				
				// Submit via AJAX
				$.ajax({
					url: $form.attr('action'),
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						console.log('[Tile Sample] Form submission successful');
						
						// Force reload cart data from server
						customerData.invalidate(['cart']);
						customerData.reload(['cart'], true).done(function() {
							console.log('[Tile Sample] Cart data reloaded, opening minicart');
							updateCartCounter();
							openMinicart();
						});
						
						// Update button state
						$button.find('span').html('<i class="tick-icon">✓</i> Free Cut Sample Already In Cart');
						$button.addClass('tile-sample-button-disabled').prop('disabled', true);
					},
					error: function(xhr, status, error) {
						console.error('[Tile Sample] Form submission failed:', error);
						
						// Reset button
						$button.prop('disabled', false).find('span').text(originalText);
						
						// Still try to reload cart data in case it actually worked
						customerData.reload(['cart'], true);
					}
				});
			});
			
			// Also handle direct button clicks as fallback
			$(document).on('click', '.tile-sample-button:not(.tile-sample-button-disabled)', function(e) {
				console.log('[Tile Sample] Tile sample button clicked');
				
				var $button = $(this);
				var $form = $button.closest('form');
				
				if ($form.length) {
					// Trigger form submission
					$form.submit();
				}
			});
			
			// Monitor for page redirects (in case AJAX fails and form submits normally)
			var originalOnBeforeUnload = window.onbeforeunload;
			window.onbeforeunload = function(e) {
				// Check if this might be a tile sample form submission redirect
				if (document.querySelector('form[data-role="tile-sample-form"]')) {
					console.log('[Tile Sample] Page unloading, might be form submission');
					
					// Set a flag in sessionStorage to check on next page load
					try {
						sessionStorage.setItem('tile_sample_added', 'true');
					} catch (e) {
						// sessionStorage not available, ignore
					}
				}
				
				// Call original handler if exists
				if (originalOnBeforeUnload) {
					return originalOnBeforeUnload(e);
				}
			};
			
			// Check if we returned from a tile sample addition
			try {
				if (sessionStorage.getItem('tile_sample_added') === 'true') {
					console.log('[Tile Sample] Detected return from tile sample addition');
					sessionStorage.removeItem('tile_sample_added');
					
					// Force cart refresh and open minicart
					setTimeout(function() {
						customerData.invalidate(['cart']);
						customerData.reload(['cart'], true).done(function() {
							updateCartCounter();
							openMinicart();
						});
					}, 500);
				}
			} catch (e) {
				// sessionStorage not available, ignore
			}
			
			// Subscribe to cart changes to update counter
			customerData.get('cart').subscribe(function(updatedCart) {
				updateCartCounter();
			});
			
			// Initial counter update
			updateCartCounter();
		});
	};
});