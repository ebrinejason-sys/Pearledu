<?php

namespace App\Console\Commands;

use App\Services\Account\InvitationService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Activate an invited staff/parent/learner account without the emailed token.
 * Catalog sync is separate: `php artisan db:seed --class=RoleSeeder`.
 */
class ActivateInvitationCommand extends Command
{
    protected $signature = 'invite:activate
        {email : Account email that received the invitation}
        {--password= : Password to set (min 10 characters)}
        {--force : Allow on a live server}';

    protected $description = 'Set a password and activate an invited account that never opened the email link.';

    public function handle(InvitationService $invitations): int
    {
        $force = (bool) $this->option('force');
        if (app()->isProduction() && ! $force) {
            $this->error('Refusing to set a password on a live server without --force.');
            $this->comment('The person should open the invitation email and choose a password.');
            $this->comment('If mail never arrived: php artisan invite:activate email@school.test --password=\'…\' --force');

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');
        $password = (string) $this->option('password');
        if (strlen($password) < 10) {
            $this->error('Password must be at least 10 characters. Pass --password=…');

            return self::FAILURE;
        }

        try {
            $user = $invitations->activateInvitedAccount($email, $password);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Activated '.$user->full_name.' <'.$user->email.'>. They can sign in at /login.');
        $this->comment('If Secretary was missing from the invite list, sync the role catalog first:');
        $this->comment('php artisan db:seed --class=RoleSeeder');

        return self::SUCCESS;
    }
}
