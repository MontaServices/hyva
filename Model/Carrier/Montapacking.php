<?php

namespace Monta\MyModule\Model\Carrier;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

class Montapacking extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'montapacking';

    /**
     * @var ResultFactory
     */
    protected $rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $rateMethodFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param array $data
     */

    protected $_customLogger;

    protected $_request;

    protected $checkoutSession;

    public function __construct(ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        LoggerInterface $customLogger,
        RequestInterface $request,
                                CheckoutSession $checkoutSession,
        array $data = []) {
        $this->_request = $request;
        $this->_customLogger = $customLogger;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['montapacking' => $this->getConfigData('name')];
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $items = $request->getAllItems();
        foreach ($items as $item) {
            $quote = $item->getQuote();
            break;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier('montapacking');
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('montapacking');
        $method->setMethodTitle($this->getConfigData('name'));

        $amount = $this->getConfigData('price');
//        $amount = $this->checkoutSession->getShippingCost() ?: $this->getConfigData('price');


//        if ($quote != null) {
//            $address = $quote->getShippingAddress();
//            $address->setMontapackingMontacheckoutData('setMontapackingMontacheckoutData test attribute');
//            $address->save();
//        }


        // Set the price at the right column of the webshop
        if($this->checkoutSession->getSelectedShipperCode() != null && $this->checkoutSession->getAllDeliveryOptions() != null) {
            $abc = $this->checkoutSession->getAllDeliveryOptions();
            // Todo: Change to map
            foreach ($this->checkoutSession->getAllDeliveryOptions() as $deliveryOptions) {
                foreach ($deliveryOptions->options as $options) {
                    if ($options->code == $this->checkoutSession->getSelectedShipperCode()) {
                        $amount = $options->price;
                    }
                }
            }
        }

        if($this->checkoutSession->getSelectedShipperCode() != null && $this->checkoutSession->getAllPickupOptions() != null) {
            $abc = $this->checkoutSession->getAllPickupOptions();
            // Todo: Change to map
            foreach ($this->checkoutSession->getAllPickupOptions() as $pickupOptions) {
                if($pickupOptions->code == $this->checkoutSession->getSelectedShipperCode()) {
                    $amount = $pickupOptions->price;
                }


//                foreach ($PickupOptions->options as $options) {
//                    if ($options->code == $this->checkoutSession->getSelectedShipperCode()) {
//                        $amount = $options->price;
//                    }
//                }
            }
        }

//        $formpostdata = json_decode(file_get_contents('php://input'), true);
//
//        // quickfix for onepagecheckout
//        if (isset($formpostdata["shippingAddress"]["extension_attributes"]["montapacking_montacheckout_data"])) {
//            $json = json_decode($formpostdata["shippingAddress"]["extension_attributes"]["montapacking_montacheckout_data"]);
//            $amount = $json->additional_info[0]->total_price;
//
//            if ($quote != null) {
//                $address = $quote->getShippingAddress();
//                $address->setMontapackingMontacheckoutData($formpostdata["shippingAddress"]["extension_attributes"]["montapacking_montacheckout_data"]);
//                $address->save();
//            }
//        }

        $method->setPrice($amount);
        $method->setCost($amount);

        $result->append($method);

        return $result;
    }
}
