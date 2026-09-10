
@props([
    'title' => 'title card',
    'description' => 'description card',
    'total' => '0' ,
    'color' => 'blue',
])

@php
    $theme = [
        'blue' => [
            'text' => 'text-blue-500',
            'bg' => 'bg-blue-50',
        ],
        'red' => [
            'text' => 'text-red-600',
            'bg' => 'bg-red-50',
        ],
        'emerald' => [
            'text' => 'text-emerald-500',
            'bg' => 'bg-emerald-50',
        ],
        'amber' => [
            'text' => 'text-amber-500',
            'bg' => 'bg-amber-50',
        ],
        'gray' => [
            'text' => 'text-gray-500',
            'bg' => 'bg-gray-50',
        ],
        'purple' => [
            'text' => 'text-purple-500',
            'bg' => 'bg-purple-50',
        ],
    ][$color] ?? [
        'text' => 'text-black',
        'bg' => 'bg-white',
    ];


@endphp

<div {{ $attributes }} class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 flex items-center justify-between">
    <div>
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{$title}}</p>
        <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ $total }}</h3>
        <p class='text-xs font-medium {{ $theme['text'] }} mt-1' >{{$description}}</p>
    </div>
    <div class="w-12 h-12 rounded-xl {{ $theme['bg']}} {{ $theme['text'] }}  flex items-center justify-center">
        {{ $slot }}
    </div>
</div>
