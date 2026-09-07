

@props(['for', 'label'])

<label for="{{ $for }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
    {{ $label ?? $for }}
</label>
