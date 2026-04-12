<?php

use App\Services\GroqService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('ai:groq-test', function (GroqService $groq) {
    $result = $groq->generateFeedback([
        'pregunta' => '¿Cuál es la derivada de x^2?',
        'correcta' => '2x',
        'usuario' => 'x',
        'resultado' => 'ERROR',
        'duda_usuario' => 'No entiendo por qué no es x',
        'contexto_tecnico' => 'La regla de la potencia establece que d/dx(x^n)=n*x^(n-1). Para x^2, la derivada es 2x.',
    ]);

    if (! is_array($result)) {
        $this->error('No se obtuvo respuesta valida de Groq. Revisa GROQ_API_KEY/GROQ_MODEL y conectividad.');
        return self::FAILURE;
    }

    $feedback = (string) data_get($result, 'evaluacion.feedback_especifico', '');
    $semblanza = (string) data_get($result, 'evaluacion.semblanza_tema', '');

    $this->info('Conexion Groq OK');
    $this->line('feedback_especifico: ' . $feedback);
    $this->line('semblanza_tema: ' . $semblanza);
    $this->line('JSON completo:');
    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
})->purpose('Prueba de conexion con Groq y validacion del contrato JSON adaptativo.');
