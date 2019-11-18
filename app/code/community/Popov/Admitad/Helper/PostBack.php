<?php

/**
 * Enter description here...
 *
 * @category Popov
 * @package Popov_<package>
 * @author Popov Sergiy <popow.serhii@gmail.com>
 * @datetime: 07.06.2017 17:48
 */
class Popov_Admitad_Helper_PostBack extends Mage_Core_Helper_Abstract implements Popov_Retag_Helper_PostBackInterface
{
    public function getUrl()
    {
        return Mage::getStoreConfig('popov_admitad/settings/postback_url');
    }

    public function getParams()
    {
        $cookie = Mage::getSingleton('core/cookie');
        $order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        $items = $order->getAllVisibleItems();

        $post = [];
        foreach ($items as $key => $item) {
            $post[] = [
                'postback_key' => Mage::getStoreConfig('popov_admitad/settings/postback_key'),
                'campaign_code' => Mage::getStoreConfig('popov_admitad/settings/campaign_code'),
                'postback' => 1,
                'action_code' => 1,
                'uid' => $cookie->get('ADMITAD_UID'),
                'order_id' => $order->getIncrementId(),
                'tariff_code' => 1,
                'price' => $item->getPrice(),
                'quantity' => (int) $item->getQtyOrdered(),
                'position_id' => $key + 1,
                'position_count' => $order->getTotalItemCount(),
                'product_id' => $item->getProductId(),
                'payment_type' => 'sale',

                'coupon' => (int) (bool) $order->getCouponCode(),
                'old_consumer' => $this->hasCustomerPreviousOrders(),
                'currency_code' => Mage::app()->getStore()->getCurrentCurrencyCode(),
                'country_code' => $this->getCountryCode()
            ];
            if ($customerId = Mage::getSingleton('customer/session')->getCustomer()->getId()) {
                $post['client_id'] = $customerId;
            }
        }

        return $post;
    }

    public function sendOld()
    {
        $cookie = Mage::getSingleton('core/cookie');
		if (!$cookie->get('ADMITAD_UID')) {
			return;
		}
        $order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());

        $backUrl = Mage::getStoreConfig('popov_admitad/settings/postback_url');

        $items = $order->getAllVisibleItems();
        foreach ($items as $key => $item) {
            $post = [
                'postback_key' => Mage::getStoreConfig('popov_admitad/settings/postback_key'),
                'campaign_code' => Mage::getStoreConfig('popov_admitad/settings/campaign_code'),
                'postback' => 1,
                'action_code' => 1,
                'uid' => $cookie->get('ADMITAD_UID'),
                'order_id' => $order->getIncrementId(),
                'tariff_code' => 1,
                'price' => $item->getPrice(),
                'quantity' => (int) $item->getQtyOrdered(),
                'position_id' => $key + 1,
                'position_count' => $order->getTotalItemCount(),
                'product_id' => $item->getProductId(),
                'payment_type' => 'sale',

                'coupon' => (int) (bool) $order->getCouponCode(),
                'old_consumer' => $this->hasCustomerPreviousOrders(),
                'currency_code' => Mage::app()->getStore()->getCurrentCurrencyCode(),
                'country_code' => $this->getCountryCode()
            ];
			if ($customerId = Mage::getSingleton('customer/session')->getCustomer()->getId()) {
				$post['client_id'] = $customerId;
			}

            parent::send($backUrl, $post);
        }
    }

    /**
     * @link https://magento.stackexchange.com/a/70096
     * @link https://stackoverflow.com/a/9586889/1335142
     */
    public function hasCustomerPreviousOrders()
    {
        $order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        #$customer = Mage::getSingleton('customer/session')->getCustomer();
        #$email = $customer->getEmail();
        $email = $order->getCustomerEmail();


        $orderCollection = Mage::getModel('sales/order')->getCollection();
        $orderCollection->addFieldToFilter('customer_email', $email);

        return (int) (bool) count($orderCollection);
    }

    /**
     * @see https://stackoverflow.com/a/6989826/1335142
     * @return string
     */
    public function getCountryCode()
    {
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$billingAddress = $customer->getDefaultBillingAddress();
		if ($billingAddress) {
			$countryCode = $billingAddress->getCountry();
		} else {
			$countryCode = Mage::getStoreConfig('general/country/default');
		}

        return $countryCode;
    }
}