<?php

namespace Monta\MyModule\Magewire\Checkout\Shipping\Method;

use http\Exception;
use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Monta\MyModule\Model\Config\Provider\Carrier;
use Magewirephp\Magewire\Component;
use Monta\CheckoutApiWrapper\MontapackingShipping;
use Monta\CheckoutApiWrapper\MontapackingShipping as MontpackingApi;
use Monta\CheckoutApiWrapper\Objects\Settings;
use Monta\CheckoutApiWrapper\Objects\ShippingOption;

class DeliveryDates extends Component implements EvaluationInterface
{
    private CheckoutSession $checkoutSession;
    protected Carrier $carrierConfig;

    public const TYPE_DELIVERY = 'delivery';
    public const TYPE_PICKUP = 'pickup';
    public $type = null;

    public $shouldShowDatePicker = true;
    public $result;
    public $userSelectedShipperCode = null;
    public $userSelectedShipperOptions = [];
    private $montaApi = null;

    public $address = null;

    protected $listeners = [
        'refresh',
        'shipping_method_selected' => 'updateShouldShowDatePicker',
//        'shipping_method_selected' => 'refresh',
        'payment_method_selected' => 'refresh',
        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh',
        'shipping_address_saved' => 'refresh',
        'shipping_address_activated' => 'refresh',
        'billing_address_saved' => 'refresh',
        'billing_address_activated' => 'refresh',

        'monta_shipper_selected' => 'shipperSelected',
        'monta_pickup_selected' => 'PickupSelected',
    ];

    protected $loader = 'Loading....';

    public function __construct(CheckoutSession $checkoutSession, Carrier $carrierConfig)
    {
        $this->checkoutSession = $checkoutSession;
        $this->carrierConfig = $carrierConfig;
    }

    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        if($this->userSelectedShipperCode == null)
        {
            return $resultFactory->createErrorMessage((string) __('Please select a shipper before continuing'));
        }

        return $resultFactory->createSuccess();
    }

    public function deliveryButtonClicked()
    {
        $this->type = self::TYPE_DELIVERY;
        $this->userSelectedShipperCode = null;

        if(!isset($this->result) ||   !is_array($this->result['DeliveryOptions'])) {
            return;
        }

        $this->emitTypeEvent();
    }


    public function pickupButtonClicked()
    {
        $this->type = self::TYPE_PICKUP;
        $this->userSelectedShipperCode = null;

        if(!is_array($this->result['PickupOptions'])) {
            return;
        }

        $this->emitTypeEvent();
    }

    public function emitTypeEvent(): void
    {
        $deliveryArray[] = $this->result['DeliveryOptions'];
        $pickupArray[] = $this->result['PickupOptions'];

        if($this->type == null) {
            $this->type = self::TYPE_DELIVERY;
            $this->emit('monta_pickup_button_selected', $pickupArray);
            $this->emit('monta_delivery_button_selected', $deliveryArray);
        }

        if($this->type == self::TYPE_DELIVERY) {
            $this->emit('monta_delivery_button_selected', $deliveryArray);
            return;
        }

        if ($this->type == self::TYPE_PICKUP) {
            $this->emit('monta_pickup_button_selected', $pickupArray);
            return;
        }

        $this->emit('monta_delivery_button_selected', $deliveryArray);
    }


    public function getCarrierConfig()
    {
        $abc = $this->carrierConfig;
        return $this->carrierConfig;
    }

    public function boot(): void
    {
        $webshop                = $this->getCarrierConfig()->getWebshop();
        $username               = $this->getCarrierConfig()->getUserName();
        $password               = $this->getCarrierConfig()->getPassword();
        $pickupPointsEnabled    = !$this->getCarrierConfig()->getDisablePickupPoints();
        $maxPickupPoints        = $this->getCarrierConfig()->getMaxPickupPoints() ?? 3;
        $googleApiKey           = $this->getCarrierConfig()->getGoogleApiKey();
        $defaultShippingCost    = $this->getCarrierConfig()->getPrice();
        $isMontaLeading         = $this->getCarrierConfig()->getLeadingStockMontapacking() ?? true;

        $deliveryDaysEnabled    = !$this->getCarrierConfig()->getDisableDeliveryDays() ?? true;

        if($webshop == null) {
            throw new \Exception("Configuration error");
        }

        $this->updateShouldShowDatePicker();

        $this->checkoutSession->setSelectedShipperCode(null);

        $settings = new Settings(
            $webshop,
            $username,
            $password,
            $pickupPointsEnabled,
            $maxPickupPoints,
            $googleApiKey,
            $defaultShippingCost,
            'nl-NL'
        );

        $this->montaApi = new MontpackingApi($settings, 'nl-NL');


        $quote = $this->checkoutSession->getQuote();
        $firstname = $quote->getShippingAddress()['firstname'];
        $lastname = $quote->getShippingAddress()['lastname'];
        $street = $quote->getShippingAddress()['street'];
        $houseNumber = '';
        $city  = $quote->getShippingAddress()['city'];
        $region = $quote->getShippingAddress()['region'];
        $postal = $quote->getShippingAddress()['postcode'];
        $hmm = $quote->getShippingAddress();
        $streetArray = explode("\n", $street);

        if(!isset($streetArray[0])) {
            $street = '';
        }

        if(!isset($streetArray[1])) {
            $houseNumber = '';
        }

        if(!isset($street) || !isset($postal) || !isset($city) || !isset($region)) {
            return;
        }

        $this->montaApi->setAddress($street, $houseNumber, '', $postal, $city, $region, 'NL');

        /** begin price calculation */
        $cart = $this->checkoutSession->getQuote()->getAllVisibleItems();
        $priceIncl = $this->checkoutSession->getQuote()->getTotals()['subtotal']->getData()['value'];
        $priceExcl = $this->checkoutSession->getQuote()->getTotals()['subtotal']->getData()['value'];

        $this->montaApi->setOrder($priceIncl, $priceExcl);

        $bAllProductsAvailable = true;

        foreach($cart as $item) {
            if($isMontaLeading) {
                $this->montaApi->addProduct(sku: $item->getSku(), quantity: $item->getQty(), price: $item->getPrice());
            } else {
                $stockItem = $item->getProduct()->getExtensionAttributes()->getStockItem();
                if ($stockItem->getQty() <= 0 || $stockItem->getQty() < $item->getQty()) {
                    $bAllProductsAvailable = false;
                    break;
                }
            }

            if (false === $bAllProductsAvailable || $deliveryDaysEnabled) {
                $this->montaApi->setOnstock(false);
            }
        }
        /** End price calculation */




        $result = $this->montaApi->getShippingOptions(true);
        $this->result = $result;
        $this->checkoutSession->setAllDeliveryOptions($this->result['DeliveryOptions']);
        $this->checkoutSession->setAllPickupOptions($this->result['PickupOptions']);



        $this->emitTypeEvent();
    }

    public function mounted(): void
    {
        $this->emitTypeEvent();
    }

    /**
     * DatePicker should only be shown when Monta is selected as delivery option
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function shouldShowDatePicker(): bool
    {
        return $this->checkoutSession->getQuote()->getShippingAddress()->getShippingMethod() == 'montapacking_montapacking';
    }

    public function updateShouldShowDatePicker(): void
    {
        $this->shouldShowDatePicker = $this->shouldShowDatePicker();
    }

    public function shipperSelected($shipperCode, $deliveryOptions)
    {
        $this->userSelectedShipperCode = $shipperCode;
        $this->checkoutSession->setSelectedShipperCode($shipperCode);

        $shipperData = $this->getShipperByShipperCode($shipperCode, $this->result['DeliveryOptions']);
        $shipperDataDate = $this->getShipperDateByShipperCode($shipperCode, $this->result['DeliveryOptions']);

        $deliveryOptionsFlattened = [];
        foreach($deliveryOptions as $givenOptions) {
            foreach($shipperData->deliveryOptions as $code) {
                if($givenOptions == $code->code) {
                    $deliveryOptionsFlattened[] = $code->code;
                    $deliveryOptionsFlattened = array_unique($deliveryOptionsFlattened);
                }
            }
        }

        /** begin price calculation */
//        $cart = $this->checkoutSession->getQuote()->getAllVisibleItems();
//        $priceIncl = $this->checkoutSession->getQuote()->getTotals()['subtotal']->getData()['value'];
//        $priceExcl = $this->checkoutSession->getQuote()->getTotals()['subtotal']->getData()['value'];
//
//        $this->montaApi->setOrder($priceIncl, $priceExcl);
//
//        $bAllProductsAvailable = true;
//        $disabledeliverydays = false; // ToDO: Read from Magento Monta Settings
//
//        foreach($cart as $item) {
//            // ToDO: Read from Magento Monta Settings
////            if($leadingstockmontapacking) {
////
////            }
//            if(true) {
//                $this->montaApi->addProduct($item->getSku(), $item->getQty());
//            } else {
//                if ($stockItem->getQty() <= 0 || $stockItem->getQty() < $item->getQty()) {
//                    $bAllProductsAvailable = false;
//                    break;
//                }
//            }
//
//            if (false === $bAllProductsAvailable || $disabledeliverydays) {
//                $this->montaApi->setOnstock(false);
//            }
//
//           $test =  $item->getData()['price'];
//        }
        /** end price calculation */

        $magic['type'] = 'delivery';
        $magic['details'] = [
            [
                'short_code' => implode(",", $shipperData->shipperCodes),
                'options' => $deliveryOptionsFlattened
            ]
        ];
        $magic['additional_info'] = [
            [
                'code' => $shipperData->code,
                'name' => $shipperData->shipper,
                'date' => $shipperDataDate,
                'time' => $shipperData->from . '-' . $shipperData->to,
                'price' => $shipperData->price,
                'total_price' => '10.00',
            ]
        ];

        $address = $this->checkoutSession->getQuote()->getShippingAddress();
            $address->setMontapackingMontacheckoutData();
            $address->save();

        // ToDO: Check if item exists in the list of given shippers. If not, don't set the value
        // $this->userSelectedShipperCode = $shipperCode;

        $this->invalidateShippingRate();
        $this->emit('monta_delivery_option_selected', $shipperData);
        $this->emitToRefresh('price-summary.total-segments');
    }

    public function PickupSelected($pickupCode)
    {
        $this->userSelectedShipperCode = $pickupCode;
        $this->checkoutSession->setSelectedShipperCode($pickupCode);

        $pickup = null;
        foreach($this->result['PickupOptions'] as $item) {
            if($item->code == $pickupCode) {
                 $pickup = $item;
            }
        }

        if($pickup == null) {
            return;
        }

        $magic['type'] = 'pickup';
        $magic['details'] = [
            [
                'short_code' => $pickup->shipperCode,
                'options' => []
            ]
        ];
        $magic['additional_info'] = [
            [
                'code' => $pickup->code,
                'code_pickup' => $pickup->shipperOptionsWithValue,
                'shipper' => $pickup->shipperCode,
                'company' => $pickup->company,
                'street' => $pickup->street,
                'housenumber' => $pickup->houseNumber,
                'postal' => $pickup->postalCode,
                'city' => $pickup->city,
                'description' => $pickup->displayName . ' | ' . $pickup->distanceMeters / 1000 . 'km',
//                'price' => number_format($pickup->price, 2, '.', ''),
                'price' => $pickup->price,
                'country' => $pickup->countryCode,
//                'total_price' =>  number_format($pickup->price, 2, '.', ''),
                'total_price' =>  '10.00',
            ]
        ];

        $address = $this->checkoutSession->getQuote()->getShippingAddress();
        $address->setMontapackingMontacheckoutData(json_encode($magic));
        $address->save();

        $this->invalidateShippingRate();
        $this->emit('monta_pickup_option_selected', $pickup);
        $this->emitToRefresh('price-summary.total-segments');
    }

    /**
     * Invalidate shipping rate so new rates will be fetched and set
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function invalidateShippingRate(): void
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->collectTotals()->save();

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();

        $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
    }

    public function init()
    {
        $this->emitUp('dataToParent',['user_ids'=>[1,2,3]]);
    }

    /**
     * Get the shipper object by shipperCode
     *
     * @param string $searchShipperCode
     * @param $shippersArray
     * @return ShippingOption|null
     */
    private function getShipperByShipperCode(string $searchShipperCode, $shippersArray) : ?ShippingOption
    {
        foreach($shippersArray as $key => $shipperDay) {
            foreach ($shipperDay->options as $option) {
                if($option->code == $searchShipperCode) {
                    return $option;
                }
            }
        }

        return null;
    }

    /**
     * @param string $searchShipperCode
     * @param $shippersArray
     * @return null
     */
    private function getShipperDateByShipperCode(string $searchShipperCode, $shippersArray)
    {
        foreach($shippersArray as $key => $shipperDay) {
            foreach ($shipperDay->options as $option) {
                if($option->code == $searchShipperCode) {
                    return $shipperDay->date;
                }
            }
        }

        return null;
    }
}
