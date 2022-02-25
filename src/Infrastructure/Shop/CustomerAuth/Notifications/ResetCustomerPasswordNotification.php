<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetCustomerPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('Herstel jouw wachtwoord.')
            ->from('webmaster-email','webmaster-name')
            ->view('chief::mails.password-reset', [
                'reset_url' => route('chief.back.password.reset', $this->token),
            ]);
    }
}
