<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Model\Achat\da;
use App\User;


class NewDa extends Notification
{
    use Queueable;
    
    protected $user;
    protected $da;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    
    public function __construct(da $da,User $user)
    {
        $this->user=$user;
        $this->da=$da;
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

     /*
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }
    */


    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    //public function toArray($notifiable)
    public function toDatabase($notifiable)
    {
        return [
            'DaID'=>$this->da->id,
            'delai'=>$this->da->delai,
            'user'=>$this->user->name,
            'msg'=>"Nouveau Demande d'achat ",
            'route'=>"/gmao/achat/da/da/".$this->da->id

        ];
    }
}
