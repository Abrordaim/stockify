@props(['columns'=>['id']])

<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
    <tr>
        @if(count($columns)>0)
            @foreach ($columns as $column )
                <x-atoms.th>{{$column}}</x-atoms.th>
            @endforeach
        @else
        {{ $slot }}
        @endif
    </tr>
</thead>
