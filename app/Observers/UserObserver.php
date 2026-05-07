<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Auto-disable pdf_access_active when access expires
        if ($user->wasChanged('pdf_access_expires_at') || $user->wasChanged('pdf_access_active')) {
            if ($user->pdf_access_expires_at && $user->pdf_access_expires_at->isPast()) {
                $user->pdf_access_active = false;
                $user->saveQuietly(); // Avoid infinite loop
            }
        }
    }

    /**
     * Handle the User "retrieved" event.
     */
    public function retrieved(User $user): void
    {
        // Ensure pdf_access_active reflects actual expiry status on every load
        if ($user->pdf_access_expires_at && $user->pdf_access_expires_at->isPast() && $user->pdf_access_active) {
            $user->pdf_access_active = false;
            $user->saveQuietly();
        }
    }
}
