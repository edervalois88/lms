<?php

namespace Database\Seeders;

use App\Models\Major;
use Illuminate\Database\Seeder;

class MajorSeeder extends Seeder
{
    public function run(): void
    {
        $majors = [
            // ÁREA 1: Ciencias Físico-Matemáticas y de las Ingenierías
            ['name' => 'Ingeniería Aeroespacial', 'area_id' => 1, 'school_name' => 'Facultad de Ingeniería (CU)', 'min_score' => 114, 'demand_level' => 'Muy Alta'],
            ['name' => 'Ingeniería en Computación', 'area_id' => 1, 'school_name' => 'Facultad de Ingeniería (CU)', 'min_score' => 105, 'demand_level' => 'Alta'],
            ['name' => 'Física', 'area_id' => 1, 'school_name' => 'Facultad de Ciencias (CU)', 'min_score' => 108, 'demand_level' => 'Muy Alta'],
            ['name' => 'Actuaría', 'area_id' => 1, 'school_name' => 'Facultad de Ciencias (CU)', 'min_score' => 106, 'demand_level' => 'Alta'],
            ['name' => 'Matemáticas Aplicadas y Computación', 'area_id' => 1, 'school_name' => 'FES Acatlán', 'min_score' => 85, 'demand_level' => 'Media'],
            ['name' => 'Arquitectura', 'area_id' => 1, 'school_name' => 'Facultad de Arquitectura (CU)', 'min_score' => 102, 'demand_level' => 'Alta'],
            ['name' => 'Ingeniería Mecatrónica', 'area_id' => 1, 'school_name' => 'Facultad de Ingeniería (CU)', 'min_score' => 110, 'demand_level' => 'Muy Alta'],
            ['name' => 'Ingeniería Civil', 'area_id' => 1, 'school_name' => 'Facultad de Ingeniería (CU)', 'min_score' => 92, 'demand_level' => 'Media'],
            ['name' => 'Ingeniería Química', 'area_id' => 1, 'school_name' => 'Facultad de Química (CU)', 'min_score' => 98, 'demand_level' => 'Media-Alta'],
            ['name' => 'Ciencia de Datos', 'area_id' => 1, 'school_name' => 'IIMAS (CU)', 'min_score' => 105, 'demand_level' => 'Alta'],

            // ÁREA 2: Ciencias Biológicas, Químicas y de la Salud
            ['name' => 'Médico Cirujano', 'area_id' => 2, 'school_name' => 'Facultad de Medicina (CU)', 'min_score' => 115, 'demand_level' => 'Máxima'],
            ['name' => 'Médico Cirujano', 'area_id' => 2, 'school_name' => 'FES Iztacala', 'min_score' => 108, 'demand_level' => 'Muy Alta'],
            ['name' => 'Médico Cirujano', 'area_id' => 2, 'school_name' => 'FES Zaragoza', 'min_score' => 107, 'demand_level' => 'Muy Alta'],
            ['name' => 'Psicología', 'area_id' => 2, 'school_name' => 'Facultad de Psicología (CU)', 'min_score' => 104, 'demand_level' => 'Alta'],
            ['name' => 'Cirujano Dentista', 'area_id' => 2, 'school_name' => 'Facultad de Odontología (CU)', 'min_score' => 95, 'demand_level' => 'Alta'],
            ['name' => 'Biología', 'area_id' => 2, 'school_name' => 'Facultad de Ciencias (CU)', 'min_score' => 100, 'demand_level' => 'Alta'],
            ['name' => 'Medicina Veterinaria y Zootecnia', 'area_id' => 2, 'school_name' => 'Facultad de Medicina Veterinaria (CU)', 'min_score' => 103, 'demand_level' => 'Alta'],
            ['name' => 'Enfermería', 'area_id' => 2, 'school_name' => 'FENO (CU)', 'min_score' => 85, 'demand_level' => 'Media'],
            ['name' => 'Química Farmacéutico Biológica', 'area_id' => 2, 'school_name' => 'Facultad de Química (CU)', 'min_score' => 105, 'demand_level' => 'Alta'],
            ['name' => 'Nutriología', 'area_id' => 2, 'school_name' => 'FES Zaragoza', 'min_score' => 88, 'demand_level' => 'Media'],

            // ÁREA 3: Ciencias Sociales
            ['name' => 'Derecho', 'area_id' => 3, 'school_name' => 'Facultad de Derecho (CU)', 'min_score' => 85, 'demand_level' => 'Alta'],
            ['name' => 'Relaciones Internacionales', 'area_id' => 3, 'school_name' => 'Facultad de Ciencias Políticas (CU)', 'min_score' => 101, 'demand_level' => 'Muy Alta'],
            ['name' => 'Administración', 'area_id' => 3, 'school_name' => 'Facultad de Contaduría (CU)', 'min_score' => 88, 'demand_level' => 'Alta'],
            ['name' => 'Contaduría', 'area_id' => 3, 'school_name' => 'Facultad de Contaduría (CU)', 'min_score' => 82, 'demand_level' => 'Media-Alta'],
            ['name' => 'Ciencias de la Comunicación', 'area_id' => 3, 'school_name' => 'Facultad de Ciencias Políticas (CU)', 'min_score' => 103, 'demand_level' => 'Muy Alta'],
            ['name' => 'Economía', 'area_id' => 3, 'school_name' => 'Facultad de Economía (CU)', 'min_score' => 90, 'demand_level' => 'Alta'],
            ['name' => 'Sociología', 'area_id' => 3, 'school_name' => 'Facultad de Ciencias Políticas (CU)', 'min_score' => 75, 'demand_level' => 'Baja-Media'],
            ['name' => 'Geografía', 'area_id' => 3, 'school_name' => 'Facultad de Filosofía (CU)', 'min_score' => 80, 'demand_level' => 'Baja'],
            ['name' => 'Turismo y Desarrollo Sostenible', 'area_id' => 3, 'school_name' => 'ENES Juriquilla', 'min_score' => 95, 'demand_level' => 'Media'],
            ['name' => 'Negocios Internacionales', 'area_id' => 3, 'school_name' => 'FCA (CU)', 'min_score' => 105, 'demand_level' => 'Alta'],

            // ÁREA 4: Humanidades y de las Artes
            ['name' => 'Artes Visuales', 'area_id' => 4, 'school_name' => 'FAD', 'min_score' => 102, 'demand_level' => 'Alta'],
            ['name' => 'Diseño y Comunicación Visual', 'area_id' => 4, 'school_name' => 'FAD', 'min_score' => 105, 'demand_level' => 'Muy Alta'],
            ['name' => 'Filosofía', 'area_id' => 4, 'school_name' => 'Facultad de Filosofía (CU)', 'min_score' => 95, 'demand_level' => 'Media'],
            ['name' => 'Letras Modernas (Inglesas)', 'area_id' => 4, 'school_name' => 'Facultad de Filosofía (CU)', 'min_score' => 100, 'demand_level' => 'Alta'],
            ['name' => 'Historia', 'area_id' => 4, 'school_name' => 'Facultad de Filosofía (CU)', 'min_score' => 90, 'demand_level' => 'Media'],
            ['name' => 'Pedagogía', 'area_id' => 4, 'school_name' => 'Facultad de Filosofía (CU)', 'min_score' => 75, 'demand_level' => 'Media'],
            ['name' => 'Música y Tecnología Artística', 'area_id' => 4, 'school_name' => 'ENES Morelia', 'min_score' => 105, 'demand_level' => 'Alta'],
            ['name' => 'Teatro y Actuación', 'area_id' => 4, 'school_name' => 'Facultad de Filosofía (CU)', 'min_score' => 102, 'demand_level' => 'Muy Alta'],
            ['name' => 'Literatura Dramática y Teatro', 'area_id' => 4, 'school_name' => 'Facultad de Filosofía (CU)', 'min_score' => 98, 'demand_level' => 'Media-Alta'],
            ['name' => 'Lengua y Literaturas Hispánicas', 'area_id' => 4, 'school_name' => 'Facultad de Filosofía (CU)', 'min_score' => 95, 'demand_level' => 'Media'],

            // Adicionales Alta Demanda
            ['name' => 'Criminología', 'area_id' => 3, 'school_name' => 'FES Acatlán', 'min_score' => 105, 'demand_level' => 'Muy Alta'],
            ['name' => 'Neurociencias', 'area_id' => 2, 'school_name' => 'Facultad de Medicina (CU)', 'min_score' => 110, 'demand_level' => 'Muy Alta'],
            ['name' => 'Fisioterapia', 'area_id' => 2, 'school_name' => 'Facultad de Medicina (CU)', 'min_score' => 108, 'demand_level' => 'Alta'],
            ['name' => 'Nanotecnología', 'area_id' => 1, 'school_name' => 'CNyN Ensenada', 'min_score' => 110, 'demand_level' => 'Alta'],
            ['name' => 'Inteligencia Artificial', 'area_id' => 1, 'school_name' => 'FES Aragón', 'min_score' => 102, 'demand_level' => 'Alta'],
            ['name' => 'Cine', 'area_id' => 4, 'school_name' => 'ENAC', 'min_score' => 112, 'demand_level' => 'Máxima'],
            ['name' => 'Gastronomía', 'area_id' => 3, 'school_name' => 'Claustro de Sor Juana (Convenio)', 'min_score' => 90, 'demand_level' => 'Media'],
            ['name' => 'Comunicación y Periodismo', 'area_id' => 3, 'school_name' => 'FES Aragón', 'min_score' => 95, 'demand_level' => 'Alta'],
            ['name' => 'Desarrollo Comunitario para el Envejecimiento', 'area_id' => 2, 'school_name' => 'FES Zaragoza', 'min_score' => 60, 'demand_level' => 'Baja'],
            ['name' => 'Bibliotecología y Estudios de la Información', 'area_id' => 3, 'school_name' => 'Facultad de Filosofía (CU)', 'min_score' => 50, 'demand_level' => 'Baja'],
        ];

        foreach ($majors as $major) {
            Major::updateOrCreate(['name' => $major['name'], 'school_name' => $major['school_name']], $major);
        }
    }
}
