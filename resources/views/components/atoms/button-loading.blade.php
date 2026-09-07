@props(['target', 'title'])

<button {{$attributes }} >
    <span wire:loading.remove wire:target="{{ $target }}">{{ $title }}</span>
                <span wire:loading wire:target="{{ $target }}" class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                 <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>

    </span>
</button>
