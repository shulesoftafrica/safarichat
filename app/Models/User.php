<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail{

    use HasFactory,
        Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at', 'phone', 'user_type_id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function business() {
        return $this->hasOne('App\Models\Business', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages() {
        return $this->hasMany('App\Models\Message');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usersEvents() {
        return $this->hasMany('App\Models\UsersEvent');
    }

    /**
     * Get the active event associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough|null
     */
    public function event() {
        return $this->hasOneThrough(
            'App\Models\Event',
            'App\Models\UsersEvent',
            'user_id',      // Foreign key on UsersEvent table...
            'id',           // Foreign key on Event table...
            'id',           // Local key on User table...
            'event_id'      // Local key on UsersEvent table...
        )->where('users_events.status', 1);
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userKey() {
        return $this->hasMany('App\Models\UserKey');
    }

      /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fileAlbums() {
        return $this->hasMany('App\Models\FileAlbum');
    }
    
    public function discountRequests() {
          return $this->hasMany('App\Models\DiscountRequest');
    }
    /**
     * @return message left from the database
     */

     public function messagesLeft($channel = 'bulksms') {   
        return \DB::table('users_sms_status')
            ->where('user_id', $this->id)
            ->where('channel', $channel)
            ->value('message_left') ?? 0;
    }

    /** 
     * @return messageinstances for this user
     */
    public function messageInstances() {
        return $this->hasMany('App\Models\MessageInstance');
    }

    /**
     * Get the user's WhatsApp instance
     * @return \App\Models\MessageInstance|null
     */
    public function whatsappInstance() {
        // First check new WhatsappInstance model
        $newInstance = \App\Models\WhatsappInstance::where('user_id', $this->id)
            ->where('connect_status', 'ready')
            ->first();
            
        if ($newInstance) {
            return $newInstance;
        }
        
        // Get most recent new instance
        $newInstance = \App\Models\WhatsappInstance::where('user_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($newInstance) {
            return $newInstance;
        }
        
        // Fallback to old MessageInstance model
        return $this->messageInstances()
            ->where('type', 'whatsapp')
            ->where('connect_status', 'ready')
            ->first() ?: $this->messageInstances()
            ->where('type', 'whatsapp')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get all WhatsApp instances for this user
     */
    public function whatsappInstances()
    {
        return $this->hasMany(\App\Models\WhatsappInstance::class);
    }

    /**
     * Get incoming messages for user's instances
     */
    public function incomingMessages()
    {
        return $this->hasMany(\App\Models\IncomingMessage::class);
    }

    /**
     * Get outgoing messages for user's instances
     */
    public function outgoingMessages()
    {
        return $this->hasMany(\App\Models\OutgoingMessage::class);
    }
}
