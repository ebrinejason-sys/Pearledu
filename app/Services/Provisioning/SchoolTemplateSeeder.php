<?php
namespace App\Services\Provisioning;
use App\Models\School;
use App\Models\SchoolClass;
class SchoolTemplateSeeder {
    private const SCAFFOLD = [
        'preprimary'      => ['Kindergarten'=>'KG','Baby Class'=>'BABY','Middle Class'=>'MID','Top Class'=>'TOP'],
        'primary'         => ['P1'=>'P1','P2'=>'P2','P3'=>'P3','P4'=>'P4','P5'=>'P5','P6'=>'P6','P7'=>'P7'],
        'lower_secondary' => ['S1'=>'S1','S2'=>'S2','S3'=>'S3','S4'=>'S4'],
        'upper_secondary' => ['S5'=>'S5','S6'=>'S6'],
    ];
    public function seed(School $school, array $levels): void {
        foreach ($levels as $lvl) {
            foreach (self::SCAFFOLD[$lvl] ?? [] as $name => $code) {
                SchoolClass::firstOrCreate(
                    ['school_id'=>$school->id,'code'=>$code],
                    ['level'=>$lvl,'name'=>$name]
                );
            }
        }
    }
}
