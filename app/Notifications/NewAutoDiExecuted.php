<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Model\Correctif\di_di;
use App\Model\Correctif\di_ot;

class NewAutoDiExecuted extends Notification
{
    use Queueable;
    protected $di;
    protected $ot;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(di_di $di,di_ot $ot)
    {
        $this->di=$di;
        $this->ot=$ot;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */

    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toDatabase($notifiable)
    {
        return [
            'DiID'=>$this->di->DiID,
            'user'=>'',
            'msg'=>"DI #".$this->di->DiID." est executé automatique ",
            'route'=>"/gmao/correctif/ot/ot/".$this->ot->OtID
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
