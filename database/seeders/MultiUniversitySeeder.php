<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Major;
use App\Models\MajorStatistic;
use App\Models\University;
use Illuminate\Database\Seeder;

class MultiUniversitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        University::truncate();
        Campus::truncate();
        Major::truncate();
        MajorStatistic::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // 1. UNIVERSIDADES
        $unam = University::create([
            'name' => 'Universidad Nacional Autónoma de México',
            'acronym' => 'UNAM',
            'slug' => 'unam',
            'logo_path' => 'images/logos/universities/unam.svg',
            'exam_config' => [
                'total_questions' => 120,
                'areas' => ['Área 1', 'Área 2', 'Área 3', 'Área 4']
            ]
        ]);

        $ipn = University::create([
            'name' => 'Instituto Politécnico Nacional',
            'acronym' => 'IPN',
            'slug' => 'ipn',
            'logo_path' => 'images/logos/universities/ipn.svg',
            'exam_config' => [
                'total_questions' => 140,
                'areas' => ['IyCFM', 'CMB', 'CSA']
            ]
        ]);

        $uam = University::create([
            'name' => 'Universidad Autónoma Metropolitana',
            'acronym' => 'UAM',
            'slug' => 'uam',
            'logo_path' => 'images/logos/universities/uam.svg',
            'exam_config' => [
                'total_questions' => 80,
                'divisions' => ['CBI', 'CBS', 'CSH', 'CAD', 'CNI', 'CCD'],
                'score_calculation' => '70% examen, 30% promedio'
            ]
        ]);

        // 2. PLANTELES + 3. CARRERAS (catálogo ampliado)
        $this->seedUnam($unam);
        $this->seedIpn($ipn);
        $this->seedUam($uam);
    }

    private function seedUnam(University $unam): void
    {
        $catalog = [
            ['name' => 'Ciudad Universitaria', 'slug' => 'cu', 'location' => 'Coyoacán, CDMX', 'logo_path' => 'images/logos/campuses/cu.svg', 'majors' => [
                ['name' => 'Ingeniería en Computación', 'division' => 'Área 1', 'score' => 105, 'apps' => 12500, 'places' => 350, 'holland' => 'IRC'],
                ['name' => 'Medico Cirujano', 'division' => 'Área 2', 'score' => 114, 'apps' => 25000, 'places' => 180, 'holland' => 'ISR'],
                ['name' => 'Derecho', 'division' => 'Área 3', 'score' => 96, 'apps' => 21000, 'places' => 900, 'holland' => 'ESA'],
            ]],
            ['name' => 'FES Acatlan', 'slug' => 'fes-acatlan', 'location' => 'Naucalpan, EdoMex', 'logo_path' => 'images/logos/campuses/fes-acatlan.svg', 'majors' => [
                ['name' => 'Arquitectura', 'division' => 'Área 1', 'score' => 95, 'apps' => 7400, 'places' => 400, 'holland' => 'AIR'],
                ['name' => 'Relaciones Internacionales', 'division' => 'Área 3', 'score' => 99, 'apps' => 6300, 'places' => 220, 'holland' => 'SEA'],
            ]],
            ['name' => 'FES Aragon', 'slug' => 'fes-aragon', 'location' => 'Nezahualcóyotl, EdoMex', 'logo_path' => 'images/logos/campuses/fes-aragon.svg', 'majors' => [
                ['name' => 'Ingeniería Civil', 'division' => 'Área 1', 'score' => 92, 'apps' => 5100, 'places' => 260, 'holland' => 'IRC'],
                ['name' => 'Economía', 'division' => 'Área 3', 'score' => 89, 'apps' => 4700, 'places' => 280, 'holland' => 'ECS'],
            ]],
            ['name' => 'FES Iztacala', 'slug' => 'fes-iztacala', 'location' => 'Tlalnepantla, EdoMex', 'logo_path' => 'images/logos/campuses/fes-iztacala.svg', 'majors' => [
                ['name' => 'Cirujano Dentista', 'division' => 'Área 2', 'score' => 100, 'apps' => 6800, 'places' => 280, 'holland' => 'SIR'],
                ['name' => 'Psicología', 'division' => 'Área 2', 'score' => 101, 'apps' => 9200, 'places' => 330, 'holland' => 'SIA'],
            ]],
            ['name' => 'FES Zaragoza', 'slug' => 'fes-zaragoza', 'location' => 'Iztapalapa, CDMX', 'logo_path' => 'images/logos/campuses/fes-zaragoza.svg', 'majors' => [
                ['name' => 'QFB', 'division' => 'Área 2', 'score' => 104, 'apps' => 5800, 'places' => 250, 'holland' => 'IRC'],
                ['name' => 'Enfermería', 'division' => 'Área 2', 'score' => 90, 'apps' => 3900, 'places' => 300, 'holland' => 'SCE'],
            ]],
            ['name' => 'ENES León', 'slug' => 'enes-leon', 'location' => 'León, Guanajuato', 'logo_path' => 'images/logos/campuses/enes-leon.svg', 'majors' => [
                ['name' => 'Fisioterapia', 'division' => 'Área 2', 'score' => 87, 'apps' => 1800, 'places' => 140, 'holland' => 'SIR'],
                ['name' => 'Desarrollo Territorial', 'division' => 'Área 3', 'score' => 76, 'apps' => 1200, 'places' => 130, 'holland' => 'EAS'],
            ]],
        ];

        $this->seedUniversityCatalog($unam, $catalog);
    }

    private function seedIpn(University $ipn): void
    {
        $catalog = [
            ['name' => 'ESCOM', 'slug' => 'escom', 'location' => 'Zacatenco, CDMX', 'logo_path' => 'images/logos/campuses/escom.svg', 'majors' => [
                ['name' => 'Ingeniería en Sistemas Computacionales', 'division' => 'IyCFM', 'score' => 102, 'apps' => 8900, 'places' => 260, 'holland' => 'IRC'],
                ['name' => 'Ingeniería en Inteligencia Artificial', 'division' => 'IyCFM', 'score' => 108, 'apps' => 5000, 'places' => 120, 'holland' => 'IRC'],
            ]],
            ['name' => 'ESIME Zacatenco', 'slug' => 'esime-zac', 'location' => 'Gustavo A. Madero, CDMX', 'logo_path' => 'images/logos/campuses/esime-zac.svg', 'majors' => [
                ['name' => 'Ingeniería Mecánica', 'division' => 'IyCFM', 'score' => 93, 'apps' => 6200, 'places' => 350, 'holland' => 'IRC'],
                ['name' => 'Ingeniería Eléctrica', 'division' => 'IyCFM', 'score' => 95, 'apps' => 5400, 'places' => 280, 'holland' => 'IRC'],
            ]],
            ['name' => 'UPIICSA', 'slug' => 'upiicsa', 'location' => 'Iztacalco, CDMX', 'logo_path' => 'images/logos/campuses/upiicsa.svg', 'majors' => [
                ['name' => 'Ingeniería Industrial', 'division' => 'CSA', 'score' => 92, 'apps' => 7400, 'places' => 420, 'holland' => 'ECR'],
                ['name' => 'Ingeniería en Informática', 'division' => 'CSA', 'score' => 95, 'apps' => 6000, 'places' => 300, 'holland' => 'IRC'],
            ]],
            ['name' => 'ESCA Santo Tomás', 'slug' => 'esca-st', 'location' => 'Miguel Hidalgo, CDMX', 'logo_path' => 'images/logos/campuses/esca-st.svg', 'majors' => [
                ['name' => 'Contaduría Pública', 'division' => 'CSA', 'score' => 87, 'apps' => 5200, 'places' => 340, 'holland' => 'ECS'],
                ['name' => 'Negocios Internacionales', 'division' => 'CSA', 'score' => 90, 'apps' => 4600, 'places' => 260, 'holland' => 'ESA'],
            ]],
        ];

        $this->seedUniversityCatalog($ipn, $catalog);
    }

    private function seedUam(University $uam): void
    {
        $catalog = [
            ['name' => 'Unidad Azcapotzalco', 'slug' => 'azc', 'location' => 'Azcapotzalco, CDMX', 'logo_path' => 'images/logos/campuses/uam-azc.svg', 'majors' => [
                ['name' => 'Ingeniería en Computación', 'division' => 'CBI', 'score' => 750, 'apps' => 3000, 'places' => 150, 'holland' => 'IRC'],
                ['name' => 'Diseño Industrial', 'division' => 'CAD', 'score' => 730, 'apps' => 2200, 'places' => 120, 'holland' => 'AIR'],
            ]],
            ['name' => 'Unidad Iztapalapa', 'slug' => 'izt', 'location' => 'Iztapalapa, CDMX', 'logo_path' => 'images/logos/campuses/uam-izt.svg', 'majors' => [
                ['name' => 'Matemáticas', 'division' => 'CBI', 'score' => 690, 'apps' => 1400, 'places' => 120, 'holland' => 'IRC'],
                ['name' => 'Biología Experimental', 'division' => 'CBS', 'score' => 705, 'apps' => 1900, 'places' => 140, 'holland' => 'IRS'],
            ]],
            ['name' => 'Unidad Xochimilco', 'slug' => 'xoc', 'location' => 'Xochimilco, CDMX', 'logo_path' => 'images/logos/campuses/uam-xoc.svg', 'majors' => [
                ['name' => 'Medicina', 'division' => 'CBS', 'score' => 830, 'apps' => 5200, 'places' => 110, 'holland' => 'SIR'],
                ['name' => 'Psicología', 'division' => 'CSH', 'score' => 740, 'apps' => 4200, 'places' => 190, 'holland' => 'SIA'],
            ]],
            ['name' => 'Unidad Cuajimalpa', 'slug' => 'cua', 'location' => 'Cuajimalpa, CDMX', 'logo_path' => 'images/logos/campuses/uam-cua.svg', 'majors' => [
                ['name' => 'Tecnologías y Sistemas de Información', 'division' => 'CNI', 'score' => 710, 'apps' => 1800, 'places' => 130, 'holland' => 'IRC'],
                ['name' => 'Derecho', 'division' => 'CSH', 'score' => 700, 'apps' => 2500, 'places' => 170, 'holland' => 'ESA'],
            ]],
            ['name' => 'Unidad Lerma', 'slug' => 'ler', 'location' => 'Lerma, EdoMex', 'logo_path' => 'images/logos/campuses/uam-ler.svg', 'majors' => [
                ['name' => 'Ingeniería en Recursos Hídricos', 'division' => 'CBI', 'score' => 660, 'apps' => 1200, 'places' => 120, 'holland' => 'IRC'],
                ['name' => 'Arte y Comunicación Digital', 'division' => 'CCD', 'score' => 690, 'apps' => 1600, 'places' => 140, 'holland' => 'AES'],
            ]],
        ];

        $this->seedUniversityCatalog($uam, $catalog);
    }

    private function seedUniversityCatalog(University $university, array $catalog): void
    {
        foreach ($catalog as $campusData) {
            $campus = Campus::create([
                'university_id' => $university->id,
                'name' => $campusData['name'],
                'slug' => $campusData['slug'],
                'location' => $campusData['location'],
                'logo_path' => $campusData['logo_path'] ?? null,
            ]);

            foreach ($campusData['majors'] as $majorData) {
                $major = Major::create([
                    'campus_id' => $campus->id,
                    'name' => $majorData['name'],
                    'division_name' => $majorData['division'],
                    'min_score' => $majorData['score'],
                    'applicants' => $majorData['apps'],
                    'places' => $majorData['places'],
                    'holland_code' => $majorData['holland'],
                ]);

                $this->seedStats($major, $majorData['score']);
            }
        }
    }

    private function seedStats(Major $major, int $baseScore): void
    {
        $trend = [
            2021 => max(0, $baseScore - 3),
            2022 => max(0, $baseScore - 1),
            2023 => $baseScore,
        ];

        foreach ($trend as $year => $score) {
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
