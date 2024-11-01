<?php
namespace TheCartPress;
/**
 * Plugin Name: TCP Cart Total Rounding
 * Plugin URI:
 * Description: Round off Woocommerce Cart Total to nearest 5 cents.
 * Version: 1.2.0
 * Stable tag: 1.2.0
 * Requires PHP: 5.6
 * Requires at least: 5.5
 * Tested up to: 6.0
 * Author: TCP Team
 * Author URI: https://www.thecartpress.com
 * WC tested up to: 6.3.1
 */
defined('ABSPATH') or exit;

class TCP_cart_total_rounding {

	public function __construct() {
		$tcp_f = __DIR__ . '/tcp.php';
		if (file_exists($tcp_f)) {
			require_once $tcp_f;
		}
		tcp_init_plugin($this, __FILE__);
		tcp_register_updater($this->plugin_id, 'https://app.thecartpress.com/api/?op=check_update&view=json&pid=' . $this->plugin_id);
		if (!tcp_is_plugin_available('woocommerce', 'WooCommerce', 'woocommerce/woocommerce.php', $this->plugin_name)) {
			return; // Check if WooCommerce is active
		}
		tcp_add_menu(
			'tcp-cart-total-rounding',
			__('TCP Cart Total Rounding'),
			__('Cart Total Rounding'),
			'tcp_cart_total_rounding'
		);
		add_filter('woocommerce_calculated_total', [$this, 'tcp_custom_roundoff']);
		add_action('woocommerce_order_status_changed', [$this, 'tcp_transition_post_status']);
		add_action('woocommerce_order_refunded', [$this, 'tcp_wc_order_refunded']);
	}

	// filter the woocommerce cart total with rounding off to nearest 5 cent
	function tcp_custom_roundoff($total) {
		$round_num = round($total / 0.05) * 0.05;
		$total = number_format($round_num, 2); // this is required for showing zero in the last decimal
		return $total;
	}

	function tcp_transition_post_status($order_id) {
		$order = wc_get_order($order_id);
		$total = $order->get_total() ?: 0.00;
		$round_off_total = $this->tcp_custom_roundoff(floatval($total));
		$order->legacy_set_total($round_off_total);
	}

	function tcp_wc_order_refunded($order_id) {
		$order = wc_get_order($order_id);
		$refunds = $order->get_refunds();
		foreach ($refunds as $refund) {
			$refund->set_amount($this->tcp_custom_roundoff(floatval($refund->get_amount())));
			$refund->save();
		}
	}

}
new TCP_cart_total_rounding();