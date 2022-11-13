<?php

namespace App\Http\Resources;

use App\Providers\ResponseServiceProvider;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return ResponseServiceProvider::DEFAULT_RESPONSE_STRUCTURE;
    }
}
