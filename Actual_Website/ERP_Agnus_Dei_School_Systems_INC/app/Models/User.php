<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'contact_number',
        'status',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Block login if account is inactive
    public function getAuthPassword()
    {
        return $this->status === 'active' ? $this->password : null;
    }

    /**
     * Send the password reset notification to the student's personal email
     * if available, otherwise use the institutional email.
     */
    public function sendPasswordResetNotification($token): void
    {
        $email = $this->email;

        if ($this->student && $this->student->personal_email) {
            $email = $this->student->personal_email;
        }

        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }

    /**
     * Override notification routing to deliver to the correct email.
     */
    public function routeNotificationForMail($notification = null)
    {
        if ($this->student && $this->student->personal_email) {
            return $this->student->personal_email;
        }

        return $this->email;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
