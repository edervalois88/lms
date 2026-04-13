<?php

namespace Database\Seeders;

use App\Models\VocationalQuestion;
use Illuminate\Database\Seeder;

class VocationalQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Expanded RIASEC Vocational Test: 60 questions (10 per type)
     * Provides more comprehensive assessment for career guidance
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        VocationalQuestion::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $questions = [
            // REALISTA (Manual, Herramientas, Maquinaria) - 10 preguntas
            ['text' => 'Reparar aparatos eléctricos o electrónicos.', 'ria_type' => 'R', 'order' => 1],
            ['text' => 'Trabajar con maquinaria pesada o herramientas agrícolas.', 'ria_type' => 'R', 'order' => 7],
            ['text' => 'Construir muebles o realizar reparaciones en el hogar.', 'ria_type' => 'R', 'order' => 13],
            ['text' => 'Trabajar al aire libre o en contacto con la naturaleza.', 'ria_type' => 'R', 'order' => 19],
            ['text' => 'Operar vehículos o equipo técnico especializado.', 'ria_type' => 'R', 'order' => 25],
            ['text' => 'Armar o desensamblar objetos mecánicos complejos.', 'ria_type' => 'R', 'order' => 31],
            ['text' => 'Trabajar en construcción o carpintería profesional.', 'ria_type' => 'R', 'order' => 37],
            ['text' => 'Mantener y reparar motores o maquinaria industrial.', 'ria_type' => 'R', 'order' => 43],
            ['text' => 'Instalar sistemas de tuberías, electricidad o plomería.', 'ria_type' => 'R', 'order' => 49],
            ['text' => 'Trabajar en talleres o laboratorios de producción.', 'ria_type' => 'R', 'order' => 55],

            // INVESTIGADOR (Ciencia, Análisis, Problemas) - 10 preguntas
            ['text' => 'Leer libros de ciencia o artículos científicos.', 'ria_type' => 'I', 'order' => 2],
            ['text' => 'Analizar datos estadísticos para encontrar conclusiones.', 'ria_type' => 'I', 'order' => 8],
            ['text' => 'Realizar experimentos de laboratorio.', 'ria_type' => 'I', 'order' => 14],
            ['text' => 'Resolver problemas matemáticos complejos.', 'ria_type' => 'I', 'order' => 20],
            ['text' => 'Investigar el origen de fenómenos naturales o sociales.', 'ria_type' => 'I', 'order' => 26],
            ['text' => 'Realizar búsqueda de información y análisis crítico.', 'ria_type' => 'I', 'order' => 32],
            ['text' => 'Desarrollar teorías o modelos para explicar problemas.', 'ria_type' => 'I', 'order' => 38],
            ['text' => 'Trabajar en investigación científica o académica.', 'ria_type' => 'I', 'order' => 44],
            ['text' => 'Analizar información para tomar decisiones basadas en hechos.', 'ria_type' => 'I', 'order' => 50],
            ['text' => 'Entender conceptos abstractos y teorías complejas.', 'ria_type' => 'I', 'order' => 56],

            // ARTÍSTICO (Creatividad, Expresión, Diseño) - 10 preguntas
            ['text' => 'Componer música, escribir o pintar.', 'ria_type' => 'A', 'order' => 3],
            ['text' => 'Actuar en obras de teatro o realizar presentaciones creativas.', 'ria_type' => 'A', 'order' => 9],
            ['text' => 'Diseñar gráficos, logotipos o piezas de arte.', 'ria_type' => 'A', 'order' => 15],
            ['text' => 'Expresar tus sentimientos a través de medios artísticos.', 'ria_type' => 'A', 'order' => 21],
            ['text' => 'Participar en talleres de fotografía o creación literaria.', 'ria_type' => 'A', 'order' => 27],
            ['text' => 'Crear contenido multimedia o diseño de moda.', 'ria_type' => 'A', 'order' => 33],
            ['text' => 'Producir arte digital o editar videos y películas.', 'ria_type' => 'A', 'order' => 39],
            ['text' => 'Organizar eventos culturales o exposiciones artísticas.', 'ria_type' => 'A', 'order' => 45],
            ['text' => 'Innovar en diseño o crear nuevas formas de expresión.', 'ria_type' => 'A', 'order' => 51],
            ['text' => 'Trabajar como artista independiente o freelancer creativo.', 'ria_type' => 'A', 'order' => 57],

            // SOCIAL (Ayuda, Enseñanza, Colaboración) - 10 preguntas
            ['text' => 'Enseñar o entrenar a otras personas.', 'ria_type' => 'S', 'order' => 4],
            ['text' => 'Ayudar a personas con problemas personales o sociales.', 'ria_type' => 'S', 'order' => 10],
            ['text' => 'Trabajar como voluntario en causas sociales o comunitarias.', 'ria_type' => 'S', 'order' => 16],
            ['text' => 'Escuchar los problemas de los demás y ofrecer consejos.', 'ria_type' => 'S', 'order' => 22],
            ['text' => 'Brindar apoyo educativo o tutorías a estudiantes.', 'ria_type' => 'S', 'order' => 28],
            ['text' => 'Trabajar en recursos humanos o gestión de talento.', 'ria_type' => 'S', 'order' => 34],
            ['text' => 'Mediar en conflictos y buscar soluciones cooperativas.', 'ria_type' => 'S', 'order' => 40],
            ['text' => 'Dirigir o coordinar equipos y proyectos comunitarios.', 'ria_type' => 'S', 'order' => 46],
            ['text' => 'Trabajar en psicología, trabajo social o counseling.', 'ria_type' => 'S', 'order' => 52],
            ['text' => 'Participar en programas de mentoría o acompañamiento.', 'ria_type' => 'S', 'order' => 58],

            // EMPRENDEDOR (Liderazgo, Persuasión, Negocios) - 10 preguntas
            ['text' => 'Organizar y liderar un grupo de trabajo.', 'ria_type' => 'E', 'order' => 5],
            ['text' => 'Vender productos o convencer a otros de una idea.', 'ria_type' => 'E', 'order' => 11],
            ['text' => 'Iniciar tu propio proyecto o empresa.', 'ria_type' => 'E', 'order' => 17],
            ['text' => 'Participar en debates o reuniones para tomar decisiones.', 'ria_type' => 'E', 'order' => 23],
            ['text' => 'Gestionar presupuestos o negociar acuerdos.', 'ria_type' => 'E', 'order' => 29],
            ['text' => 'Desarrollar estrategias de negocio y planes de crecimiento.', 'ria_type' => 'E', 'order' => 35],
            ['text' => 'Motivar y dirigir a otros hacia objetivos comunes.', 'ria_type' => 'E', 'order' => 41],
            ['text' => 'Tomar decisiones rápidas bajo presión o incertidumbre.', 'ria_type' => 'E', 'order' => 47],
            ['text' => 'Crear redes profesionales y establecer contactos estratégicos.', 'ria_type' => 'E', 'order' => 53],
            ['text' => 'Asumir riesgos calculados para obtener beneficios.', 'ria_type' => 'E', 'order' => 59],

            // CONVENCIONAL (Organización, Datos, Orden) - 10 preguntas
            ['text' => 'Archivar documentos o llevar un registro de gastos.', 'ria_type' => 'C', 'order' => 6],
            ['text' => 'Seguir instrucciones detalladas para una tarea administrativa.', 'ria_type' => 'C', 'order' => 12],
            ['text' => 'Organizar agendas y planificar tareas diarias de oficina.', 'ria_type' => 'C', 'order' => 18],
            ['text' => 'Clasificar archivos o mantener inventarios ordenados.', 'ria_type' => 'C', 'order' => 24],
            ['text' => 'Verificar datos para asegurar que no contengan errores.', 'ria_type' => 'C', 'order' => 30],
            ['text' => 'Trabajar en contabilidad o administración de sistemas.', 'ria_type' => 'C', 'order' => 36],
            ['text' => 'Cumplir con normas, políticas y procedimientos establecidos.', 'ria_type' => 'C', 'order' => 42],
            ['text' => 'Organizar información en bases de datos o sistemas.', 'ria_type' => 'C', 'order' => 48],
            ['text' => 'Realizar tareas repetitivas con precisión y detalle.', 'ria_type' => 'C', 'order' => 54],
            ['text' => 'Coordinar horarios, recursos y logística de operaciones.', 'ria_type' => 'C', 'order' => 60],
        ];

        foreach ($questions as $q) {
            VocationalQuestion::create($q);
        }
    }
}
