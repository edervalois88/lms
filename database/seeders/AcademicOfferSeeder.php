<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Major;
use App\Models\MajorStatistic;
use App\Models\University;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AcademicOfferSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        University::truncate();
        Campus::truncate();
        Major::truncate();
        MajorStatistic::truncate();
        Schema::enableForeignKeyConstraints();

        $datasetPath = base_path('docs/academic-offer-3-institutions.json');
        if (!file_exists($datasetPath)) {
            $this->command?->warn('No se encontró docs/academic-offer-3-institutions.json; se usará MultiUniversitySeeder.');
            $this->call(MultiUniversitySeeder::class);
            return;
        }

        $raw = json_decode((string) file_get_contents($datasetPath), true);
        if (!is_array($raw)) {
            $this->command?->warn('Dataset inválido; se usará MultiUniversitySeeder.');
            $this->call(MultiUniversitySeeder::class);
            return;
        }

        $universities = [
            'unam' => University::create([
                'name' => 'Universidad Nacional Autónoma de México',
                'acronym' => 'UNAM',
                'slug' => 'unam',
                'logo_path' => 'images/institutions/unam.svg',
                'exam_config' => [
                    'total_questions' => 120,
                    'areas' => ['Área 1', 'Área 2', 'Área 3', 'Área 4'],
                    'sources' => [
                        'https://www.dgae.unam.mx/Licenciatura2025/oferta_lugares/oferta_licenciatura2025.html',
                        'https://www.dgae.unam.mx/Licenciatura2024/oferta_lugares/oferta_licenciatura2024.html',
                        'https://www.dgae.unam.mx/Licenciatura2023/oferta_lugares/oferta_licenciatura2023.html',
                    ],
                ],
            ]),
            'ipn' => University::create([
                'name' => 'Instituto Politécnico Nacional',
                'acronym' => 'IPN',
                'slug' => 'ipn',
                'logo_path' => 'images/institutions/ipn.png',
                'exam_config' => [
                    'total_questions' => 140,
                    'areas' => ['IyCFM', 'CSA', 'CMB'],
                    'sources' => [
                        'https://admision.ipn.dae.support/nse/convocatoria/ANEXO/escolarizado271.html',
                        'https://www.admision.ipn.mx/nse/convocatoria/ANEXO/escolarizado261.html',
                    ],
                ],
            ]),
            'uam' => University::create([
                'name' => 'Universidad Autónoma Metropolitana',
                'acronym' => 'UAM',
                'slug' => 'uam',
                'logo_path' => 'images/institutions/uam.svg',
                'exam_config' => [
                    'total_questions' => 80,
                    'divisions' => ['CBI', 'CBS', 'CSH', 'CAD', 'CNI', 'CCD'],
                    'score_calculation' => '70% examen, 30% promedio',
                    'sources' => [
                        'https://licenciaturas.uam.mx/',
                        'https://www.admision.uam.mx/adm_resultados.html',
                    ],
                ],
            ]),
        ];

        $campusMap = [];
        foreach ($raw['unam']['schools'] ?? [] as $school) {
            $campusMap['unam'][$school] = Campus::create([
                'university_id' => $universities['unam']->id,
                'name' => $school,
                'slug' => Str::slug($school),
                'location' => 'México',
                'logo_path' => null,
            ]);
        }

        foreach ($raw['ipn']['schools'] ?? [] as $school) {
            $campusMap['ipn'][$school] = Campus::create([
                'university_id' => $universities['ipn']->id,
                'name' => $school,
                'slug' => Str::slug($school),
                'location' => 'México',
                'logo_path' => null,
            ]);
        }

        foreach ($raw['uam']['schools'] ?? [] as $school) {
            $campusMap['uam'][$school] = Campus::create([
                'university_id' => $universities['uam']->id,
                'name' => $school,
                'slug' => Str::slug($school),
                'location' => 'México',
                'logo_path' => null,
            ]);
        }

        $fallbackCampus = [];
        foreach (['unam', 'ipn', 'uam'] as $key) {
            $fallbackCampus[$key] = Campus::create([
                'university_id' => $universities[$key]->id,
                'name' => 'Oferta General ' . strtoupper($key),
                'slug' => 'oferta-general-' . $key,
                'location' => 'México',
                'logo_path' => null,
            ]);
        }

        $benchmarks = $this->getBenchmarks();

        // UNAM careers
        foreach (($raw['unam']['careers'] ?? []) as $careerName) {
            $campus = $this->resolveCampus('unam', $careerName, $campusMap, $fallbackCampus);
            $major = $this->createMajor($campus, $careerName, 'unam', $benchmarks);
            $this->seedTrend($major, 'unam', $benchmarks);
        }

        // IPN careers
        foreach (($raw['ipn']['careers'] ?? []) as $careerName) {
            $campus = $this->resolveCampus('ipn', $careerName, $campusMap, $fallbackCampus);
            $major = $this->createMajor($campus, $careerName, 'ipn', $benchmarks);
            $this->seedTrend($major, 'ipn', $benchmarks);
        }

        // UAM careers already carry units
        foreach (($raw['uam']['careers'] ?? []) as $career) {
            if (!is_array($career) || empty($career['name'])) {
                continue;
            }
            $units = $career['units'] ?? [];
            if (is_string($units) && $units !== '') {
                $units = [$units];
            }
            if (!is_array($units)) {
                $units = [];
            }
            if (empty($units)) {
                $major = $this->createMajor($fallbackCampus['uam'], $career['name'], 'uam', $benchmarks);
                $this->seedTrend($major, 'uam', $benchmarks);
                continue;
            }
            foreach ($units as $unit) {
                $campus = $campusMap['uam'][$unit] ?? $fallbackCampus['uam'];
                $major = $this->createMajor($campus, $career['name'], 'uam', $benchmarks);
                $this->seedTrend($major, 'uam', $benchmarks);
            }
        }
    }

    private function createMajor(Campus $campus, string $name, string $institution, array $benchmarks): Major
    {
        $normalized = $this->normalize($name);
        $benchmark = $benchmarks[$institution][$normalized] ?? null;

        $applicants = $benchmark['applicants_2025'] ?? $this->estimateApplicants($institution, $name);
        $places = $benchmark['places_2025'] ?? $this->estimatePlaces($institution, $name);
        $score = $benchmark['score_2025'] ?? $this->estimateScore($institution, $name, $applicants, $places);

        return Major::firstOrCreate([
            'campus_id' => $campus->id,
            'name' => $name,
        ], [
            'division_name' => $this->divisionFromName($institution, $name),
            'min_score' => (int) $score,
            'applicants' => (int) max(1, $applicants),
            'places' => (int) max(1, $places),
            'holland_code' => $this->hollandFromName($name),
            'description' => 'Catálogo consolidado con base en oferta académica pública y tendencias históricas.',
            'extra_requirements' => [
                'source_mode' => 'online+estimated',
            ],
        ]);
    }

    private function seedTrend(Major $major, string $institution, array $benchmarks): void
    {
        $normalized = $this->normalize($major->name);
        $benchmark = $benchmarks[$institution][$normalized] ?? null;

        $series = [];
        if ($benchmark && isset($benchmark['trend']) && is_array($benchmark['trend'])) {
            $series = $benchmark['trend'];
        } else {
            $series = [
                2023 => [
                    'cutoff_score' => max(1, $major->min_score - 3),
                    'applicants' => (int) round($major->applicants * 1.08),
                    'places' => (int) max(1, round($major->places * 1.00)),
                ],
                2024 => [
                    'cutoff_score' => max(1, $major->min_score - 1),
                    'applicants' => (int) round($major->applicants * 1.03),
                    'places' => (int) max(1, round($major->places * 1.00)),
                ],
                2025 => [
                    'cutoff_score' => $major->min_score,
                    'applicants' => $major->applicants,
                    'places' => $major->places,
                ],
            ];
        }

        foreach ($series as $year => $row) {
            MajorStatistic::updateOrCreate([
                'major_id' => $major->id,
                'year' => (int) $year,
            ], [
                'cutoff_score' => $row['cutoff_score'] ?? null,
                'applicants' => $row['applicants'] ?? null,
                'places_offered' => $row['places'] ?? null,
            ]);
        }
    }

    private function resolveCampus(string $institution, string $careerName, array $campusMap, array $fallbackCampus): Campus
    {
        if ($institution === 'ipn') {
            $map = [
                'INTELIGENCIA ARTIFICIAL' => 'ESCOM',
                'CIENCIA DE DATOS' => 'ESCOM',
                'SISTEMAS COMPUTACIONALES' => 'ESCOM',
                'NEGOCIOS' => 'ESCA',
                'CONTADOR' => 'ESCA',
                'TURISMO' => 'EST',
                'ODONTOLOGIA' => 'CICS',
                'MEDICO' => 'ESM',
                'ENFERMERIA' => 'ESEO',
                'BIOLOGIA' => 'ENCB',
            ];
            $name = $this->normalize($careerName);
            foreach ($map as $needle => $school) {
                if (str_contains($name, $needle) && isset($campusMap['ipn'][$school])) {
                    return $campusMap['ipn'][$school];
                }
            }
        }

        if ($institution === 'unam') {
            $map = [
                'MEDICO CIRUJANO' => 'FACULTAD DE MEDICINA',
                'DERECHO' => 'FACULTAD DE DERECHO',
                'PSICOLOGIA' => 'FACULTAD DE PSICOLOGIA',
                'ARQUITECTURA' => 'FACULTAD DE ARQUITECTURA',
                'COMPUTACION' => 'FACULTAD DE INGENIERIA',
                'CIENCIAS DE LA COMPUTACION' => 'FACULTAD DE CIENCIAS',
                'QFB' => 'FACULTAD DE QUIMICA',
                'BIOLOGIA' => 'FACULTAD DE CIENCIAS',
                'ENFERMERIA' => 'FACULTAD DE ENFERMERIA Y OBSTETRICIA',
            ];
            $name = $this->normalize($careerName);
            foreach ($map as $needle => $school) {
                if (str_contains($name, $needle) && isset($campusMap['unam'][$school])) {
                    return $campusMap['unam'][$school];
                }
            }
        }

        return $fallbackCampus[$institution];
    }

    private function estimateApplicants(string $institution, string $name): int
    {
        $n = $this->normalize($name);
        $base = match ($institution) {
            'unam' => 1200,
            'ipn' => 900,
            default => 650,
        };

        if (str_contains($n, 'MEDICO') || str_contains($n, 'ODONTOLOGIA')) {
            return $base * 7;
        }
        if (str_contains($n, 'DERECHO') || str_contains($n, 'PSICOLOGIA')) {
            return $base * 4;
        }
        if (str_contains($n, 'INTELIGENCIA ARTIFICIAL') || str_contains($n, 'CIENCIA DE DATOS')) {
            return $base * 3;
        }
        if (str_contains($n, 'INGENIERIA')) {
            return (int) round($base * 2.2);
        }

        return $base;
    }

    private function estimatePlaces(string $institution, string $name): int
    {
        $n = $this->normalize($name);
        $base = match ($institution) {
            'unam' => 90,
            'ipn' => 120,
            default => 70,
        };

        if (str_contains($n, 'MEDICO')) {
            return (int) round($base * 1.8);
        }
        if (str_contains($n, 'DERECHO') || str_contains($n, 'CONTADOR') || str_contains($n, 'ADMINISTRACION')) {
            return (int) round($base * 2.5);
        }
        return $base;
    }

    private function estimateScore(string $institution, string $name, int $applicants, int $places): int
    {
        $examMax = match ($institution) {
            'unam' => 120,
            'ipn' => 140,
            default => 100,
        };
        $ratio = max(1.0, $applicants / max(1, $places));
        $pressure = min(0.92, 0.45 + (log10($ratio) * 0.25));
        $score = (int) round($examMax * $pressure);

        if (str_contains($this->normalize($name), 'MEDICO')) {
            $score = (int) min($examMax, $score + 8);
        }

        return max(35, $score);
    }

    private function divisionFromName(string $institution, string $name): string
    {
        $n = $this->normalize($name);
        if ($institution === 'unam') {
            if (str_contains($n, 'INGENIERIA') || str_contains($n, 'FISICA') || str_contains($n, 'MATEMATIC')) {
                return 'Área 1';
            }
            if (str_contains($n, 'MEDICO') || str_contains($n, 'ENFERMERIA') || str_contains($n, 'BIOLOGIA') || str_contains($n, 'QUIMICA')) {
                return 'Área 2';
            }
            if (str_contains($n, 'DERECHO') || str_contains($n, 'ECONOMIA') || str_contains($n, 'SOCIOLOG') || str_contains($n, 'ADMINISTR')) {
                return 'Área 3';
            }
            return 'Área 4';
        }

        if ($institution === 'ipn') {
            if (str_contains($n, 'MEDICO') || str_contains($n, 'BIOLOG') || str_contains($n, 'ODONTO') || str_contains($n, 'NUTRIC')) {
                return 'CMB';
            }
            if (str_contains($n, 'ADMINISTR') || str_contains($n, 'NEGOC') || str_contains($n, 'CONTADOR') || str_contains($n, 'TURISMO') || str_contains($n, 'COMERC')) {
                return 'CSA';
            }
            return 'IyCFM';
        }

        if (str_contains($n, 'DISEÑO') || str_contains($n, 'ARTE') || str_contains($n, 'COMUNICACION')) {
            return 'CAD';
        }
        if (str_contains($n, 'BIOLOG') || str_contains($n, 'NUTRIC') || str_contains($n, 'MEDIC')) {
            return 'CBS';
        }
        if (str_contains($n, 'SOCIO') || str_contains($n, 'DERECHO') || str_contains($n, 'ADMINIST')) {
            return 'CSH';
        }
        return 'CBI';
    }

    private function hollandFromName(string $name): string
    {
        $n = $this->normalize($name);
        if (str_contains($n, 'INGENIER') || str_contains($n, 'MATEMATIC') || str_contains($n, 'FISIC')) {
            return 'IRC';
        }
        if (str_contains($n, 'MEDICO') || str_contains($n, 'ENFERMERIA') || str_contains($n, 'BIOLOG')) {
            return 'SIR';
        }
        if (str_contains($n, 'DERECHO') || str_contains($n, 'COMUNIC') || str_contains($n, 'SOCIO')) {
            return 'ESA';
        }
        if (str_contains($n, 'ARTE') || str_contains($n, 'DISE')) {
            return 'AIR';
        }
        return 'ECS';
    }

    private function normalize(string $text): string
    {
        $txt = Str::upper(Str::ascii($text));
        return preg_replace('/\s+/', ' ', trim($txt)) ?? $txt;
    }

    private function getBenchmarks(): array
    {
        return [
            'unam' => [
                $this->normalize('Médico Cirujano') => [
                    'score_2025' => 114,
                    'applicants_2025' => 14954,
                    'places_2025' => 176,
                    'trend' => [
                        2023 => ['cutoff_score' => 113, 'applicants' => 16469, 'places' => 176],
                        2024 => ['cutoff_score' => 114, 'applicants' => 15962, 'places' => 176],
                        2025 => ['cutoff_score' => 114, 'applicants' => 14954, 'places' => 176],
                    ],
                ],
                $this->normalize('Ingeniería en Computación') => [
                    'score_2025' => 105,
                    'applicants_2025' => 3068,
                    'places_2025' => 110,
                    'trend' => [
                        2023 => ['cutoff_score' => 104, 'applicants' => 3423, 'places' => 120],
                        2024 => ['cutoff_score' => 105, 'applicants' => 3516, 'places' => 110],
                        2025 => ['cutoff_score' => 105, 'applicants' => 3068, 'places' => 110],
                    ],
                ],
                $this->normalize('Derecho') => [
                    'score_2025' => 96,
                    'applicants_2025' => 6199,
                    'places_2025' => 556,
                    'trend' => [
                        2023 => ['cutoff_score' => 95, 'applicants' => 6600, 'places' => 556],
                        2024 => ['cutoff_score' => 96, 'applicants' => 6525, 'places' => 556],
                        2025 => ['cutoff_score' => 96, 'applicants' => 6199, 'places' => 556],
                    ],
                ],
                $this->normalize('Psicología') => [
                    'score_2025' => 101,
                    'applicants_2025' => 4592,
                    'places_2025' => 80,
                    'trend' => [
                        2023 => ['cutoff_score' => 101, 'applicants' => 5085, 'places' => 80],
                        2024 => ['cutoff_score' => 101, 'applicants' => 5005, 'places' => 80],
                        2025 => ['cutoff_score' => 101, 'applicants' => 4592, 'places' => 80],
                    ],
                ],
                $this->normalize('Arquitectura') => [
                    'score_2025' => 102,
                    'applicants_2025' => 5157,
                    'places_2025' => 250,
                    'trend' => [
                        2023 => ['cutoff_score' => 101, 'applicants' => 5160, 'places' => 250],
                        2024 => ['cutoff_score' => 102, 'applicants' => 5069, 'places' => 250],
                        2025 => ['cutoff_score' => 102, 'applicants' => 5157, 'places' => 250],
                    ],
                ],
            ],
            'ipn' => [
                $this->normalize('Ingeniería en Sistemas Computacionales') => [
                    'score_2025' => 101,
                    'places_2025' => 350,
                    'applicants_2025' => 2800,
                ],
                $this->normalize('Ingeniería en Inteligencia Artificial') => [
                    'score_2025' => 108,
                    'places_2025' => 100,
                    'applicants_2025' => 2100,
                ],
                $this->normalize('Ingeniería Industrial') => [
                    'score_2025' => 95,
                    'places_2025' => 540,
                    'applicants_2025' => 2600,
                ],
            ],
            'uam' => [
                $this->normalize('Administración') => [
                    'score_2025' => 720,
                    'places_2025' => 180,
                    'applicants_2025' => 1200,
                ],
                $this->normalize('Medicina') => [
                    'score_2025' => 830,
                    'places_2025' => 110,
                    'applicants_2025' => 3200,
                ],
                $this->normalize('Ingeniería en Computación') => [
                    'score_2025' => 750,
                    'places_2025' => 150,
                    'applicants_2025' => 1800,
                ],
            ],
        ];
    }
}
