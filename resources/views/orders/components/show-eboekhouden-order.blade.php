<form wire:submit.prevent="submit">
    @if($order->eboekhoudenOrder)
        @if($order->eboekhoudenOrder->pushed == 1)
            <span
                class="bg-green-100 text-green-800 inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium">
                                Bestelling naar E-boekhouden gepushed
                                </span>
        @elseif($order->eboekhoudenOrder->pushed == 2)
            <span
                class="bg-red-100 text-red-800 inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium">
                                Bestelling niet naar E-boekhouden gepushed
                                </span>
            <button type="submit"
                    class="inline-flex items-center justify-center font-medium tracking-tight rounded-lg focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700 h-9 px-4 text-white shadow focus:ring-white w-full mt-2">
                Opnieuw naar E-boekhouden pushen
            </button>
        @else
            <span
                class="bg-yellow-100 text-yellow-800 inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium">
                                Bestelling wordt naar E-boekhouden gepushed
                                </span>
        @endif
    @endif
</form>
