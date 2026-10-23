<?php

namespace App\Jobs;

use App\Mail\CompanyAdminCredentialsMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

final class SendCompanyAdminCredentials implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Company $company,
        public readonly string $password,
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new CompanyAdminCredentialsMail($this->user, $this->company, $this->password),
        );
    }
}
