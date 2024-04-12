<?php

namespace Monta\MyModule\Magewire\Checkout\Shipping\Method;

use Magewirephp\Magewire\Component;

class DeliveryOptionSelectedOverview extends Component
{
    public $selectedDeliveryOption = null;

    protected $listeners = [
        'monta_delivery_option_selected' => 'init'
    ];

    public $selectedOptions =[];

    public function init($data, $deliveryOptionsOptions)
    {
        $this->selectedDeliveryOption = $data;
        $this->selectedOptions = $deliveryOptionsOptions;
    }

    public function resetShipperOption(){
        $this->emit("resetShipperOption");
    }
}
