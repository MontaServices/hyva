<?php

namespace Monta\MyModule\Magewire\Checkout\Shipping\Method;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magewirephp\Magewire\Component;

class DeliveryOptions extends Component
{
    public $deliveryOptions = [];
    public $standardOptions = null;
    public $deliveryOptionsForSelectedDate = [];
    public $userSelectedShipperOptions = [];

    public $selectedDate = null;
    public $showSelectedDate = true;

    public $allDays = [];

    protected $listeners = [
        'monta_delivery_button_selected' => 'init',
        'monta_delivery_button_selected_alldays' => 'allDays'
    ];

    protected $loader = 'Loading...';

    public function init($data)
    {
        if (!is_array($data)) {
            return;
        }

        $this->deliveryOptions = $data;

        if (count($this->deliveryOptions) == 1) {
            $this->setSelectedDate($this->deliveryOptions[0]['date']);
        }

        $this->allDays = array_map(function ($deliveryOption) {
            if ($deliveryOption['dateOnlyFormatted'] != "") {
                return array('day' => substr($deliveryOption['day'], 0, 2), 'date' => $deliveryOption['date']);
            } else {
                return [];
            }
        }, $this->deliveryOptions);

        $this->allDays = array_filter($this->allDays, function ($k) {
            return count($k) != 0;
        });

        $this->allDays = array_slice($this->allDays, 0, 6);

    }

    function testfunction($deliveryOption): array
    {
        return array('day' => substr($deliveryOption['day'], 0, 2), 'date' => $deliveryOption['date']);
    }

    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    public
    function allDays($data)
    {
        $this->allDays = json_decode($data);
//        $testje = json_decode($data);
//        $test = $$testje->0->day;
    }

    public function boot(): void
    {

    }

    public function setSelectedDate($date)
    {
        if ($this->selectedDate == $date) {
            $this->showSelectedDate = !$this->showSelectedDate;
            $this->deliveryOptionsForSelectedDate = [];
        } else {
            $this->showSelectedDate = true;
        }
        if (!$this->showSelectedDate) {
            return [];
        }

        foreach ($this->deliveryOptions as $item) {
            // Check if the attribute exists and has the desired value

            if ($date == null) {
                $this->deliveryOptionsForSelectedDate = $item['options'];
            }

            if (isset($item['date']) && $item['date'] === $date) {
                $this->deliveryOptionsForSelectedDate = $item['options'];
                $this->selectedDate = $date;
                $this->showSelectedDate = true;
                return $item;
            }
        }

        return [];
    }

    public
    function chooseShipperButtonClicked(string $shipperCode)
    {
        $this->emit('monta_shipper_selected', $shipperCode, $this->userSelectedShipperOptions);
    }

    public
    function shipperOptionClicked($optionCode)
    {
//        foreach($this->deliveryOptions as $deliveryOption) {
//            foreach($deliveryOption['options'] as $options) {
//                $test2 = $options['deliveryOptions'];
//                $test = $options;
//            }
//        }

        if (in_array($optionCode, $this->userSelectedShipperOptions)) {
            $to_remove = array($optionCode);

//            $whee = array_diff($this->userSelectedShipperOptions, $to_remove);

            $this->userSelectedShipperOptions = array_diff($this->userSelectedShipperOptions, $to_remove);
            return;
        }

        $this->userSelectedShipperOptions[] = $optionCode;
        $this->userSelectedShipperOptions = array_unique($this->userSelectedShipperOptions);
    }
}
