<?php

namespace Monta\MyModule\Magewire\Checkout\Shipping\Method;

use Magewirephp\Magewire\Component;

class DeliveryOptionSelectedOverview extends Component
{
    public $selectedDeliveryOption = null;

    protected $listeners = [
        'monta_delivery_option_selected' => 'init'
    ];

    public function init($data)
    {
        $this->selectedDeliveryOption = $data;
    }
}
