<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerRead;
use Thinktomorrow\Trader\TraderConfig;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    private TraderConfig $traderConfig;

    private CustomerRead $customer;

    public function __construct(TraderConfig $traderConfig, CustomerRead $customer)
    {
        $this->traderConfig = $traderConfig;
        $this->customer = $customer;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return new MailMessage()
            ->subject(trans('trader-mails.verify.subject'))
            ->from($this->traderConfig->getWebmasterEmail(), $this->traderConfig->getWebmasterName())
            ->view('trader::customer.auth.verification-mail', [
                'customer' => $this->customer,
                'url' => $verificationUrl,
            ]);
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'customer.verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
