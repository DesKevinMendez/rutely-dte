<?php

namespace App\Response;

use Illuminate\Contracts\Support\Responsable;

class CommonResponse implements Responsable
{
    public function __construct(
        public $data,
        public $status = 200,
    ) {
        //
    }

    public function toResponse($request)
    {
        return response()->json($this->data, $this->status);
    }
}
