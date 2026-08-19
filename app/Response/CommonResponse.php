<?php

namespace App\Response;

use Illuminate\Contracts\Support\Responsable;

class CommonResponse implements Responsable
{
    public function __construct(
        public mixed $data = null,
        public int $status = 200,
        public ?string $message = null,
    ) {
        //
    }

    public function toResponse($request)
    {
        $response = [];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->message !== null) {
            $response['message'] = $this->message;
        }

        return response()->json($response, $this->status);
    }
}
