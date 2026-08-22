<?php

namespace App\Console\Commands;

use App\Services\Provisioning\WalkthroughSchoolService;
use Illuminate\Console\Command;

/**
 * Primary school with Baby–P7 filled (~100 learners) and named staff.
 */
class SeedWalkthroughSchoolCommand extends Command
{
    protected $signature = 'school:seed-walkthrough
        {--password= : Shared login password (min 10). Defaults to SEED_TEST_SCHOOL_PASSWORD}
        {--force : Allow seeding on a live server for online walkthrough testing}';

    protected $description = 'Create a walkthrough primary school (Baby–P7, ~100 learners, named staff). Production requires --force.';

    public function handle(WalkthroughSchoolService $walkthrough): int
    {
        $force = (bool) $this->option('force');

        if (app()->isProduction() && ! $force) {
            $this->error('Refusing to seed a walkthrough school in production without --force.');
            $this->comment('To test online: php artisan school:seed-walkthrough --password=\'…\' --force');
            $this->comment('Do not set SEED_TEST_SCHOOL_PASSWORD in the live .env. Purge the school from the platform console when finished.');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: config('app.seed_test_school_password'));
        if (strlen($password) < 10) {
            $this->error('Password must be at least 10 characters. Pass --password=… or set SEED_TEST_SCHOOL_PASSWORD.');

            return self::FAILURE;
        }

        $result = $walkthrough->seed($password, app()->isProduction() && $force);
        $school = $result['school'];

        $this->info(($result['created'] ? 'Created' : 'Updated').' '.$school->name.' (EMIS '.$school->emis_number.').');
        $this->info('Learners: '.$result['students'].' across Baby–P7.');
        $this->newLine();
        $this->info('Sign in at /login. Every walkthrough account uses the password you just supplied.');
        $this->newLine();

        $rows = [];
        foreach ($result['accounts'] as $account) {
            $rows[] = [
                $account['role'],
                $account['name'],
                $account['email'],
            ];
        }
        $this->table(['Role', 'Name', 'Email'], $rows);

        $this->newLine();
        $this->comment('Class teachers are homeroom-only (no mark entry). Use english@ / maths@ for assessment.');
        $this->comment('Parent portal: parent@stkizito.test (P1 + P4 children). Learner portal: learner.p4@stkizito.test.');

        if (app()->isProduction()) {
            $this->newLine();
            $this->warn('This demonstration school is on a live server. Purge it from the platform console when testing is done.');
            $this->warn('Leave SEED_TEST_SCHOOL_PASSWORD unset in .env — pass --password only on this command.');
        }

        return self::SUCCESS;
    }
}
