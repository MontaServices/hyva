<?php

namespace Monta\MyModule\Magewire\Checkout\Shipping\Method;

use Magewirephp\Magewire\Component;

class PickupOptionSelectedOverview extends Component
{

    public $initSelectedDeliveryOption = null;
    public $selectedDeliveryOption = null;
    public $showOpeningsTijden = false;

    protected $listeners = [
        'monta_pickup_option_selected' => 'init'
    ];

    public function init($data)
    {
        if (!is_array($data)) {
            return;
        }
        $this->selectedDeliveryOption = $data;
//        if($this->initSelectedDeliveryOption == null){
//            $this->initSelectedDeliveryOption = $data;
//            $this->selectedDeliveryOption = $data;
//
//        }else{
//            $this->selectedDeliveryOption = $data;
//        }
    }


    public function choosePickupButtonClicked($pickupCode)
    {
        $this->emit('monta_pickup_selected', $pickupCode);
    }


    public function getOpeningsTijd($code){
        $this->showOpeningsTijden = !$this->showOpeningsTijden;
        $test = "";

//        foreach ($this->deliveryOptions as $item) {
//            // Check if the attribute exists and has the desired value
//
//            if($date == null) {
//                $this->deliveryOptionsForSelectedDate = $item['options'];
//            }
//
//            if (isset($item['date']) && $item['date'] === $date) {
//                $this->deliveryOptionsForSelectedDate = $item['options'];
//                return $item;
//            }
//        }

        return [];

    }
    public function getTimes()
    {
        $test = "";
//        foreach ($this->deliveryOptions as $item) {
//            // Check if the attribute exists and has the desired value
//
//            if($date == null) {
//                $this->deliveryOptionsForSelectedDate = $item['options'];
//            }
//
//            if (isset($item['date']) && $item['date'] === $date) {
//                $this->deliveryOptionsForSelectedDate = $item['options'];
//                return $item;
//            }
//        }

        return [];
    }
}
