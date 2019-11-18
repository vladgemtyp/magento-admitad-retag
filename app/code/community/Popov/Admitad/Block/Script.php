<?php

/**
 * Admitad ReTag Script
 *
 * @category Popov
 * @package Popov_Admitad
 * @author Popov Sergiy <popov@popov.com.ua>
 * @datetime: 25.04.2017 17:15
 */
class Popov_Admitad_Block_Script extends Mage_Page_Block_Html_Wrapper
{
    public function getCmsIndexIndexScript()
    {
        $reTagData = array(
            'level' => 0,
            'variables' => ''
        );

        return $reTagData;
    }

    public function getCatalogCategoryViewScript()
    {
        $category = Mage::registry('current_category');
        $reTagData = array(
            'level' => 1,
            'variables' => sprintf('window.ad_category = "%s";', $category->getId())
        );

        return $reTagData;
    }

	public function getCatalogProductViewScript()
    {
		$product = Mage::registry('current_product');
		$data = array(
            'id' => $product->getId(),
            'vendor' => '',
            'price' => $product->getFinalPrice(),
            'url' => Mage::helper('core/url')->getCurrentUrl(),
            'picture' => '', /*, Mage::helper('catalog/image')->init($product, 'image')*/
            'name' => Mage::helper('core')->escapeHtml($product->getName()),
            'category' => '',
        );
        $reTagData = array(
            'level' => 2,
            'variables' => sprintf('window.ad_product = %s;', Mage::helper('core')->jsonEncode($data))
        );

        return $reTagData;
	}

	public function getCheckoutCartIndexScript()
    {
        $items = Mage::getSingleton('checkout/cart')->getQuote()->getAllItems();
        $data = array();
        foreach ($items as $item) {
            //$productName = $item->getProduct()->getName();
            //$productPrice = $item->getProduct()->getPrice();
            $data[] = array('id' => $item->getProductId(), 'number' => $item->getQty());
        }
        $reTagData = array(
            //'code' => '9ce8887d92',
            'level' => 3,
            'variables' => sprintf('window.ad_products = %s;', Mage::helper('core')->jsonEncode($data))
        );

        return $reTagData;
	}

	public function getCheckoutOnepageSuccessScript()
    {
		$order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        $items = $order->getAllVisibleItems();
        $data = array();
        foreach ($items as $item) {
            //$productName = $item->getProduct()->getName();
            //$productPrice = $item->getProduct()->getPrice();
            $data[] = array('id' => $item->getProductId(), 'number' => $item->getQtyOrdered());
        }

        $reTagData = array(
            //'code' => '9ce8887d91',
            'level' => 4,
            'variables' => sprintf('window.ad_order = "%s"; window.ad_amount = "%d"; window.ad_products = %s;',
                $order->getId(), $order->getGrandTotal(), Mage::helper('core')->jsonEncode($data)
            )
        );

        return $reTagData;
	}

    protected function _toHtml()
    {
        if (!($method = 'get' . uc_words($this->getData('action'), '') . 'Script')) {
            return '';
        }

        $code = Mage::getStoreConfig('popov_admitad/settings/' . $this->getData('action') . '_code');

        $reTagData = $this->{$method}();
        $script = sprintf('<script type="text/javascript">
    %s
    window._retag = window._retag || [];
    window._retag.push({code: "%s", level: %d});
    (function () {
        var id = "admitad-retag";
        if (document.getElementById(id)) {return;}
        var s = document.createElement("script");
        s.async = true; s.id = id;
        var r = (new Date).getDate();
        s.src = (document.location.protocol == "https:" ? "https:" : "http:") + "//cdn.lenmit.com/static/js/retag.js?r="+r;
        var a = document.getElementsByTagName("script")[0]
        a.parentNode.insertBefore(s, a);
    })()
</script>', $reTagData['variables'], $code, $reTagData['level']);

        return $script;
    }
}