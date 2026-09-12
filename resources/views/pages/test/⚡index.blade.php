<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Test Pages')]  class extends Component
{
//    public $headers = ['ID', 'Nama', 'Email', 'Role', 'Status', 'Telepon', 'Kota', 'Tanggal Dibuat', 'Akses Terakhir', 'Total Transaksi', 'Aksi'];
};
?>

<div>
    <x-organism.table :thead="['id','name', 'date']">

    </x-organism.table>
</div>
