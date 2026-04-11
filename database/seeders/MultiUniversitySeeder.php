<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Major;
use App\Models\MajorStatistic;
use App\Models\University;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MultiUniversitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. UNIVERSIDADES
        $unam = University::create([
            'name' => 'Universidad Nacional Autónoma de México',
            'acronym' => 'UNAM',
            'slug' => 'unam',
            'exam_config' => [
                'total_questions' => 120,
                'areas' => ['Área 1', 'Área 2', 'Área 3', 'Área 4']
            ]
        ]);

        $ipn = University::create([
            'name' => 'Instituto Politécnico Nacional',
            'acronym' => 'IPN',
            'slug' => 'ipn',
            'exam_config' => [
                'total_questions' => 140,
                'areas' => ['IyCFM', 'CMB', 'CSA']
            ]
        ]);

        $uam = University::create([
            'name' => 'Universidad Autónoma Metropolitana',
            'acronym' => 'UAM',
            'slug' => 'uam',
            'exam_config' => [
                'total_questions' => 80, // Simplificado para el simulador
                'divisions' => ['CBI', 'CBS', 'CSH', 'CAD', 'CNI', 'CCD'],
                'score_calculation' => '70% examen, 30% promedio'
            ]
        ]);

        // 2. PLANTELES (CAMPUSES)
        $cu = Campus::create(['university_id' => $unam->id, 'name' => 'Ciudad Universitaria', 'slug' => 'cu']);
        $iztacala = Campus::create(['university_id' => $unam->id, 'name' => 'FES Iztacala', 'slug' => 'iztacala']);
        $escom = Campus::create(['university_id' => $ipn->id, 'name' => 'ESCOM', 'slug' => 'escom']);
        $esca = Campus::create(['university_id' => $ipn->id, 'name' => 'ESCA Santo Tomás', 'slug' => 'esca-st']);
        $azcapotzalco = Campus::create(['university_id' => $uam->id, 'name' => 'Unidad Azcapotzalco', 'slug' => 'azc']);
        $xochimilco = Campus::create(['university_id' => $uam->id, 'name' => 'Unidad Xochimilco', 'slug' => 'xoc']);

        // 3. CARRERAS (MAJORS)
        
        // UNAM
        $ingCompUnam = Major::create([
            'campus_id' => $cu->id,
            'name' => 'Ingeniería en Computación',
            'division_name' => 'Área 1',
            'min_score' => 105,
            'applicants' => 12500,
            'places' => 350,
            'holland_code' => 'IRC'
        ]);

        $medicinaUnam = Major::create([
            'campus_id' => $cu->id,
            'name' => 'Médico Cirujano',
            'division_name' => 'Área 2',
            'min_score' => 114,
            'applicants' => 25000,
            'places' => 180,
            'holland_code' => 'ISR'
        ]);

        // IPN
        $iaIpn = Major::create([
            'campus_id' => $escom->id,
            'name' => 'Ingeniería en Inteligencia Artificial',
            'division_name' => 'IyCFM',
            'min_score' => 108,
            'applicants' => 5000,
            'places' => 120,
            'holland_code' => 'IRC'
        ]);

        // UAM
        $compUam = Major::create([
            'campus_id' => $azcapotzalco->id,
            'name' => 'Ingeniería en Computación',
            'division_name' => 'CBI',
            'min_score' => 750, // Puntos ponderados
            'applicants' => 3000,
            'places' => 150,
            'holland_code' => 'IRC'
        ]);

        // 4. ESTADÍSTICAS (STATISTICS) - Para el popup de tendencia
        $this->seedStats($ingCompUnam, [
            2021 => 102,
            2022 => 104,
            2023 => 105
        ]);

        $this->seedStats($medicinaUnam, [
            2021 => 112,
            2022 => 113,
            2023 => 114
        ]);
    }

    private function seedStats($major, $years)
    {
        foreach ($years as $year => $score) {
            MajorStatistic::create([
                'major_id' => $major->id,
                'year' => $year,
                'cutoff_score' => $score,
                'applicants' => $major->applicants - rand(100, 500),
                'places_offered' => $major->places
            ]);
        }
    }
}
