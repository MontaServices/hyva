<?php

namespace Monta\MyModule\Magewire\Checkout\Shipping\Method;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magewirephp\Magewire\Component;

class PickupOptions extends Component
{
    public $pickupOptions = [];

    protected $listeners = [
        'monta_pickup_button_selected' => 'init',
        'pickupSelectedFromMaps' => 'pickupSelectedFromMaps'
    ];

    protected $loader = 'Loading...';

    public function pickupSelectedFromMaps($pickupPointCode)
    {
        $this->emit('monta_pickup_selected', $pickupPointCode);
    }

    public function init($data)
    {
        if(!is_array($data)) {
            return;
        }

        $this->pickupOptions = $data;
    }

    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    public function boot(): void
    {

    }

    public function choosePickupButtonClicked($pickupCode)
    {
        $this->emit('monta_pickup_selected', $pickupCode);
    }

//    public function setSelectedDate($date)
//    {
//        foreach ($this->deliveryOptions as $item) {
//            // Check if the attribute exists and has the desired value
//            if (isset($item['date']) && $item['date'] === $date) {
//                $this->deliveryOptionsForSelectedDate = $item['options'];
//                return $item;
//            }
//        }
//
//        return [];
//    }

//    public function chooseShipperButtonClicked(string $shipperCode)
//    {
//        $this->emit('monta_shipper_selected', $shipperCode);
//    }
}
