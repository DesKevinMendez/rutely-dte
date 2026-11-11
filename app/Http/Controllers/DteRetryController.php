<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Dte;
use App\Models\MhTransmission;
use App\Response\CommonResponse;
use App\Services\Dte\TransmissionRetryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DteRetryController extends Controller
{
    use AuthorizesRequests;

    /**
     * Resend one DTE whose latest transmission to MH failed.
     */
    public function __invoke(Request $request, Dte $dte, TransmissionRetryService $retryService): CommonResponse
    {
        $principal = $request->user();

        if ($principal instanceof Company) {
            throw_unless($principal->getKey() === $dte->company_id, AuthorizationException::class);
        } else {
            $this->authorize('retryTransmission', $dte);
        }

        $failedTransmission = MhTransmission::query()
            ->where('company_id', $dte->company_id)
            ->whereMorphedTo('transmittable', $dte)
            ->where('operation', 'dte')
            ->latestAttempts()
            ->where('status', 'failed')
            ->first();

        if ($failedTransmission === null) {
            return new CommonResponse(status: 409, message: __('validation.dte_retry_without_failed_transmission'));
        }

        $failedTransmission->setRelation('transmittable', $dte);
        $mhResult = $retryService->retry($failedTransmission);

        return new CommonResponse([
            'record' => $dte->refresh(),
            'mh_result' => $mhResult,
        ]);
    }
}
