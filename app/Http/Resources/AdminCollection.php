<?php

namespace App\Http\Resources;

class AdminCollection extends BaseCollection
{
    public function toArray($request)
    {
        return array_merge(
            parent::toArray($request),
            [
                'data' => $this->collection,
                'links' => [
                    'self' => 'link-value',
                ],
            ]
        );
    }
}
