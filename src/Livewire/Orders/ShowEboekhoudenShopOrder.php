<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden\Livewire\Orders;

use Livewire\Component;

class ShowEboekhoudenShopOrder extends Component
{
    public $order;

    public function mount($order)
    {
        $this->order = $order;
    }

    public function render()
    {
        return view('qcommerce-ecommerce-eboekhouden::orders.components.show-eboekhouden-order');
    }

    public function submit()
    {
        if (! $this->order->eboekhoudenOrder) {
            $this->emit('notify', [
                'status' => 'error',
                'message' => 'De bestelling mag niet naar E-boekhouden gepushed worden.',
            ]);
        } elseif ($this->order->eboekhoudenOrder->pushed == 1) {
            $this->emit('notify', [
                'status' => 'error',
                'message' => 'De bestelling is al naar E-boekhouden gepushed.',
            ]);
        } elseif ($this->order->eboekhoudenOrder->pushed == 0) {
            $this->emit('notify', [
                'status' => 'error',
                'message' => 'De bestelling wordt al naar E-boekhouden gepushed.',
            ]);
        }

        $this->order->eboekhoudenOrder->pushed = 0;
        $this->order->eboekhoudenOrder->save();

        $this->emit('refreshPage');
        $this->emit('notify', [
            'status' => 'success',
            'message' => 'De bestelling wordt binnen enkele minuten opnieuw naar E-boekhouden gepushed.',
        ]);
    }
}
