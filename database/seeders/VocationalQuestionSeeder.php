<?php

namespace Database\Seeders;

use App\Models\VocationalQuestion;
use Illuminate\Database\Seeder;

class VocationalQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        VocationalQuestion::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $questions = [
            // REALISTA (Manual, Herramientas, Maquinaria)
            ['text' => 'Reparar aparatos eléctricos o electrónicos.', 'ria_type' => 'R', 'order' => 1],
            ['text' => 'Trabajar con maquinaria pesada o herramientas agrícolas.', 'ria_type' => 'R', 'order' => 7],
            ['text' => 'Construir muebles o realizar reparaciones en el hogar.', 'ria_type' => 'R', 'order' => 13],
            ['text' => 'Trabajar al aire libre o en contacto con la naturaleza.', 'ria_type' => 'R', 'order' => 19],
            ['text' => 'Operar vehículos o equipo técnico especializado.', 'ria_type' => 'R', 'order' => 25],

            // INVESTIGADOR (Ciencia, Análisis, Problemas)
            ['text' => 'Leer libros de ciencia o artículos científicos.', 'ria_type' => 'I', 'order' => 2],
            ['text' => 'Analizar datos estadísticos para encontrar conclusiones.', 'ria_type' => 'I', 'order' => 8],
            ['text' => 'Realizar experimentos de laboratorio.', 'ria_type' => 'I', 'order' => 14],
            ['text' => 'Resolver problemas matemáticos complejos.', 'ria_type' => 'I', 'order' => 20],
            ['text' => 'Investigar el origen de fenómenos naturales o sociales.', 'ria_type' => 'I', 'order' => 26],

            // ARTÍSTICO (Creatividad, Expresión, Diseño)
            ['text' => 'Componer música, escribir o pintar.', 'ria_type' => 'A', 'order' => 3],
            ['text' => 'Actuar en obras de teatro o realizar presentaciones creativas.', 'ria_type' => 'A', 'order' => 9],
            ['text' => 'Diseñar gráficos, logotipos o piezas de arte.', 'ria_type' => 'A', 'order' => 15],
            ['text' => 'Expresar tus sentimientos a través de medios artísticos.', 'ria_type' => 'A', 'order' => 21],
            ['text' => 'Participar en talleres de fotografía o creación literaria.', 'ria_type' => 'A', 'order' => 27],

            // SOCIAL (Ayuda, Enseñanza, Colaboración)
            ['text' => 'Enseñar o entrenar a otras personas.', 'ria_type' => 'S', 'order' => 4],
            ['text' => 'Ayudar a personas con problemas personales o sociales.', 'ria_type' => 'S', 'order' => 10],
            ['text' => 'Trabajar como voluntario en causas sociales o comunitarias.', 'ria_type' => 'S', 'order' => 16],
            ['text' => 'Escuchar los problemas de los demás y ofrecer consejos.', 'ria_type' => 'S', 'order' => 22],
            ['text' => 'Brindar apoyo educativo o tutorías a estudiantes.', 'ria_type' => 'S', 'order' => 28],

            // EMPRENDEDOR (Liderazgo, Persuasión, Negocios)
            ['text' => 'Organizar y liderar un grupo de trabajo.', 'ria_type' => 'E', 'order' => 5],
            ['text' => 'Vender productos o convencer a otros de una idea.', 'ria_type' => 'E', 'order' => 11],
            ['text' => 'Iniciar tu propio proyecto o empresa.', 'ria_type' => 'E', 'order' => 17],
            ['text' => 'Participar en debates o reuniones para tomar decisiones.', 'ria_type' => 'E', 'order' => 23],
            ['text' => 'Gestionar presupuestos o negociar acuerdos.', 'ria_type' => 'E', 'order' => 29],

            // CONVENCIONAL (Organización, Datos, Orden)
            ['text' => 'Archivar documentos o llevar un registro de gastos.', 'ria_type' => 'C', 'order' => 6],
            ['text' => 'Seguir instrucciones detalladas para una tarea administrativa.', 'ria_type' => 'C', 'order' => 12],
            ['text' => 'Organizar agendas y planificar tareas diarias de oficina.', 'ria_type' => 'C', 'order' => 18],
            ['text' => 'Clasificar archivos o mantener inventarios ordenados.', 'ria_type' => 'C', 'order' => 24],
            ['text' => 'Verificar datos para asegurar que no contengan errores.', 'ria_type' => 'C', 'order' => 30],
        ];

        foreach ($questions as $q) {
            VocationalQuestion::create($q);
        }
    }
}
