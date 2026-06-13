<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Affiliate;
use App\Models\AffiliateReferral;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return \DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $refCode = request()->cookie('affiliate_ref');
            if ($refCode) {
                $affiliate = Affiliate::where('referral_code', $refCode)->where('status', 'active')->first();
                if ($affiliate) {
                    AffiliateReferral::create([
                        'affiliate_id' => $affiliate->id,
                        'referred_user_id' => $user->id,
                        'status' => 'registered',
                    ]);
                }
            }

            return $user;
        });
    }
}
