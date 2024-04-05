<?php

namespace Monta\MyModule\Magewire\Checkout\Shipping\Method;

use Magewirephp\Magewire\Component;

class PickupOptionSelectedOverview extends Component
{

    public $initialPickupChanged = false;
    public $selectedDeliveryOption = null;
    public $showOpeningsTijden = false;

    protected $listeners = [
        'monta_pickup_option_selected' => 'init',
        'monta_pickup_option_selected_initial_picked_change' => 'setInitialPickupChanged'
    ];

    public function init($data)
    {
        if (!is_array($data)) {
            return;
        }
        $this->selectedDeliveryOption = $data;
    }

    public function setInitialPickupChanged(){
        $this->initialPickupChanged = true;
    }
    public function choosePickupButtonClicked($pickupCode)
    {
        $this->initialPickupChanged = true;
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
