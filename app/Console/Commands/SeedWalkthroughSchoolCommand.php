<?php

namespace App\Console\Commands;

use App\Services\Provisioning\WalkthroughSchoolService;
use Illuminate\Console\Command;

/**
 * Local-only primary school with Baby–P7 filled (~100 learners) and named staff.
 */
class SeedWalkthroughSchoolCommand extends Command
{
    protected $signature = 'school:seed-walkthrough
        {--password= : Shared login password (min 10). Defaults to SEED_TEST_SCHOOL_PASSWORD}';

    protected $description = 'Create a local walkthrough primary school (Baby–P7, ~100 learners, named staff). Refuses production.';

    public function handle(WalkthroughSchoolService $walkthrough): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to seed a walkthrough school in production.');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: config('app.seed_test_school_password'));
        if (strlen($password) < 10) {
            $this->error('Password must be at least 10 characters. Pass --password=… or set SEED_TEST_SCHOOL_PASSWORD.');

            return self::FAILURE;
        }

        $result = $walkthrough->seed($password);
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

        return self::SUCCESS;
    }
}
