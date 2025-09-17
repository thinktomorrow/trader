<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerRead;
use Thinktomorrow\Trader\TraderConfig;

class ResetCustomerPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public $token;
    private TraderConfig $traderConfig;
    private CustomerRead $customer;

    public function __construct($token, TraderConfig $traderConfig, CustomerRead $customer)
    {
        $this->token = $token;
        $this->traderConfig = $traderConfig;
        $this->customer = $customer;
    }

    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject(trans('customer.mails.reset_password.subject'))
            ->from($this->traderConfig->getWebmasterEmail(), $this->traderConfig->getWebmasterName())
            ->view('chief-trader-shop::customer.auth.password.reset-mail', [
                'reset_url' => route('customer.password.reset', $this->token),
                'customer' => $this->customer,
            ]);
    }
}
