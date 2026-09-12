@props(['thead'=> [], 'items'=>[], 'codes' => []])


<table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
    <x-molecules.thead :columns="$thead"></x-molecules.thead>
    <x-molecules.tbody >{{$slot}}</x-molecules.tbody>
</table>
