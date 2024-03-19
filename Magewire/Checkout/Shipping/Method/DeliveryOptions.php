<?php

namespace Monta\MyModule\Magewire\Checkout\Shipping\Method;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magewirephp\Magewire\Component;

class DeliveryOptions extends Component
{
    public $deliveryOptions = [];
    public $deliveryOptionsForSelectedDate = [];
    public $userSelectedShipperOptions = [];

    protected $listeners = [
        'monta_delivery_button_selected' => 'init'
    ];

    protected $loader = 'Loading...';

    public function init($data)
    {
        if(!is_array($data)) {
            return;
        }

        $this->deliveryOptions = $data;

        if(count($this->deliveryOptionsForSelectedDate) == 0) {
            $this->setSelectedDate($this->deliveryOptions[0]['date']);
        }

    }

    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    public function boot(): void
    {

    }

    public function setSelectedDate($date)
    {
        foreach ($this->deliveryOptions as $item) {
            // Check if the attribute exists and has the desired value

            if($date == null) {
                $this->deliveryOptionsForSelectedDate = $item['options'];
            }

            if (isset($item['date']) && $item['date'] === $date) {
                $this->deliveryOptionsForSelectedDate = $item['options'];
                return $item;
            }
        }

        return [];
    }

    public function chooseShipperButtonClicked(string $shipperCode)
    {
        $this->emit('monta_shipper_selected', $shipperCode, $this->userSelectedShipperOptions);
    }

    public function shipperOptionClicked($optionCode)
    {
//        foreach($this->deliveryOptions as $deliveryOption) {
//            foreach($deliveryOption['options'] as $options) {
//                $test2 = $options['deliveryOptions'];
//                $test = $options;
//            }
//        }

        if(in_array($optionCode, $this->userSelectedShipperOptions)) {
            $to_remove = array($optionCode);

//            $whee = array_diff($this->userSelectedShipperOptions, $to_remove);

            $this->userSelectedShipperOptions =  array_diff($this->userSelectedShipperOptions, $to_remove);
            return;
        }

        $this->userSelectedShipperOptions[] = $optionCode;
        $this->userSelectedShipperOptions = array_unique($this->userSelectedShipperOptions);
    }
}
