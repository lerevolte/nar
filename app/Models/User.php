<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'avatar_url',
        'balance',
        'referrer_id',
        'last_activity',
        'is_blocked',
        'contact',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'balance' => 'integer',
        'is_blocked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_activity' => 'datetime',
    ];

    public function songs()
    {
        return $this->hasMany(Song::class, 'user_id', 'user_id');
    }

    public function draft()
    {
        return $this->hasOne(Draft::class, 'user_id', 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id', 'user_id');
    }

    public function getAuthIdentifierName()
    {
        return 'user_id';
    }

    public function getAuthIdentifier()
    {
        return $this->user_id;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Получить "логин" для отображения — email или user_id
     */
    public function getLoginIdentifier(): string
    {
        return $this->email ?? (string) $this->user_id;
    }

    /**
     * Отправить ссылку для сброса пароля своим брендированным письмом
     * (вместо стандартного англоязычного уведомления Laravel).
     */
    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));

        \Illuminate\Support\Facades\Mail::to($this->getEmailForPasswordReset())
            ->queue(new \App\Mail\ResetPasswordMail($resetUrl));
    }
}
