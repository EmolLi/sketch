<?php
namespace App\Sosadfun\Traits;

trait AdministrationTraits{
    public function findAdminRecords($id, $page=1, $include_private, $pagination=30)
    {
        return \App\Models\Administration::with('operator')
        ->withAdministratee($id)
        ->isPublic($include_private)
        ->latest()
        ->paginate($pagination);
    }
}
