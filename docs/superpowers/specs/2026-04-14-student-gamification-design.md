# NexusEdu Student Gamification Design
## Progresión Visual Clara + Avatar Companion

**Date:** 2026-04-14  
**Status:** Design Review  
**Target:** Dashboard, Quiz, Simulator, Progress pages  
**Stack:** Vue 3 + Tailwind + Motion.js  
**Approach:** Hybrid (Modular Components + Coordinated Composables)

---

## Executive Summary

Refactorizar las páginas de estudiantes (Dashboard, Quiz, Simulator, Progress) con un **sistema de Progresión Visual Clara** que resuelva tres problemas críticos:

1. **Confusión** — Estudiantes no saben dónde están o qué hacer después
2. **Poco Engagement** — Las métricas no se sienten "ganables"
3. **Falta de Recompensa Inmediata** — El feedback no es satisfactorio

**Solución:** Componentes reutilizables de progresión (barras, gráficos radiales, roadmaps) + Avatar Companion interactivo que reacciona en tiempo real.

**Visual Direction:** Gradientes Suaves (purpura → azul), bordes redondeados, glassmorphism. Profesional pero divertido, ideal para preuniversitarios.

---

## 1. Arquitectura

### 1.1 Estructura de Carpetas

```
resources/js/
├── Components/Progress/
│   ├── ProgressBar.vue          # Barra horizontal animada
│   ├── ProgressRadial.vue       # Gráfico circular (anillos concéntricos)
│   ├── ProgressJourney.vue      # Roadmap visual de niveles
│   ├── StatCard.vue             # Card de métrica con mini-progreso
│   ├── RewardFeedback.vue       # Confeti + "+XP" animado
│   ├── AvatarAnimated.vue       # Avatar con estados (Idle/Happy/Tired)
│   ├── AvatarShowcase.vue       # Avatar grande (250px) para Progress
│   ├── AvatarCompanion.vue      # Avatar interactivo (clickeable, mensajes)
│   ├── AvatarTutor.vue          # Avatar como tutor IA en Quiz/Simulator
│   └── AvatarDialog.vue         # Panel de opciones al clickear avatar
│
├── Composables/
│   ├── useGameProgress.js       # Coordina datos + cálculos de progresión
│   ├── useProgressAnimation.js  # Motion.js triggers para animaciones
│   └── useRewardFeedback.js     # Feedback visual + audio + mensajes
│
└── Pages/
    ├── Dashboard.vue            # Avatar Companion + Radial + Journey + StatCards
    ├── Quiz/Session.vue         # Avatar Tutor + ProgressBar + Feedback
    ├── Simulator/Exam.vue       # Avatar Tutor (serio) + ProgressBar + Predicción
    └── Progress/Index.vue       # Avatar Showcase + Radial (ampliado) + Timeline
```

### 1.2 Data Flow

```
Backend (Laravel)
    ↓
Inertia Props:
  - user.gpa
  - user.gamification (nivel, xp, rank, streak)
  - stats.projection (gap_to_goal, projected_score)
  - stats.subject_mastery (progreso por materia)
  - cosmetics.equipped (avatar actual + ropa)
    ↓
Composable: useGameProgress.js
  - Calcula: progressPercentage, nextLevelXp, gapStatus, journeyStage
  - Método: addXP(), triggerReward(), getAvatarMessage()
    ↓
Componentes (Props Reactivas)
    ↓
useProgressAnimation.js dispara motion.js
    ↓
UI Animada + Feedback
```

---

## 2. Componentes de Progresión

### 2.1 ProgressBar.vue
- **Barra horizontal** con degradado purpura → azul
- **Label:** "45/100 puntos" o "75% completado"
- **Sub-label:** "20 puntos para siguiente nivel"
- **Animación:** Spring easing cuando cambia valor, se ve muy satisfactorio

### 2.2 ProgressRadial.vue
- **Gráfico circular** con 3 anillos concéntricos:
  - Exterior: GPA actual vs meta (rojo/naranja/verde según gap)
  - Medio: Materias dominadas (azul) vs débiles (naranja)
  - Interior: Streak (días consecutivos, verde vibrante)
- **Centro:** Nivel actual + rango (ej: "NIVEL 5 - Aprendiz")
- **Animación:** Los anillos se "dibujan" con arc drawing effect

### 2.3 ProgressJourney.vue
- **Roadmap visual tipo RPG:** Novato → Aprendiz → Adept → Experto → Maestro
- **Estrella animada** muestra posición actual del estudiante (glow effect)
- **Distancia:** "3 niveles falta para siguiente etapa"
- **Barra de progreso** conecta etapas
- **Animación:** Al subir nivel, estrella se desliza a siguiente etapa

### 2.4 StatCard.vue
- **Por cada materia:** Nombre, barra de progreso mini, porcentaje, trend (↑↓→)
- **Color borde:** Verde (fuerte) | Naranja (medio) | Rojo (crítico)
- **Animación:** Hover → levanta con shadow + escala ligeramente

### 2.5 RewardFeedback.vue
- **Overlay temporal** cuando ganan XP/logro
- **"+50 XP"** flota hacia arriba (fade out)
- **Confeti animado** cae con stagger
- **Glow en fondo** (pulse effect)
- **Duración:** 1.5 segundos, muy satisfactorio

---

## 3. Componentes de Avatar (El Corazón Emocional)

### 3.1 AvatarCompanion.vue
- **Avatar interactivo** que responde a clicks
- **Tono:** Casual/divertido (amigo gamer, no tutor formal)
- **Estados de interacción:**
  - Click 1: Avatar saluda/onda (animación custom)
  - Click 2: Dice frase motivacional (toast popup)
  - Click 3: Tip sobre tema actual
  - Click 4: Broma/comentario random
  - Click repetido: Reacciona cansado
- **Mensajes contextuales:**
  - 3 días sin estudiar: "Te echo de menos... 😢"
  - Buena racha: "¡Vamos, estás en fuego! 🔥"
  - En quiz: "Tú puedes, confía en ti 💪"
  - Acertó pregunta: "¡Excelente! 🎉"
- **Animaciones:** Wave, jump + glow, slow (cansado)

### 3.2 AvatarTutor.vue
- **Avatar grande** en lado izquierdo/superior durante Quiz/Simulator
- **Estados de reacción:**
  - Explicando: Gesticula mientras "habla"
  - Pensando: Se rasca cabeza (ellipsis animado)
  - Celebrando: Baila cuando aciertas
  - Animando: Movimientos motivacionales mientras espera respuesta
- **Interacción:** Click en avatar → "¿Puedo ayudarte?" → Abre tutor IA
- **Setting:** on/off, default ON, se puede ocultar
- **Tono:** Casual/divertido, igual que Companion

### 3.3 AvatarShowcase.vue
- **Avatar grande (250px)** mostrando cosmética equipada
- **Fondo:** Degradado que gira lentamente
- **Labels:** "Equip: [ropa], [accesorios], [color]"
- **Botón:** "Ir a Rewards" para cambiar cosmética
- **Dónde:** En página Progress/Index

### 3.4 AvatarDialog.vue
- **Panel cuando clickeas avatar**
- **4 opciones de diálogo:**
  - "💡 Dame un tip" → Sugerencia pedagógica contextual
  - "❓ Explícame esto" → Llama IA tutor con contexto
  - "🎯 ¿Qué sigue?" → Muestra roadmap de qué estudiar
  - "😂 Cuéntame un chiste" → Mensaje chistoso + motivación
- **Cierre:** Click o timeout (5 segundos)

---

## 4. Composables Coordinadores

### 4.1 useGameProgress.js
**Responsabilidad:** Sincronizar datos backend con UI

```javascript
const progressPercentage = computed(() => ...)  // 0-100% para barras
const nextLevelXp = computed(() => ...)         // XP falta
const gapStatus = computed(() => ...)           // "CRÍTICA" / "PRÓXIMA" / "LOGRADA"
const journeyStage = computed(() => ...)        // Novato/Aprendiz/Experto
const subjectRanking = computed(() => ...)      // Materias por urgencia
const avatarState = computed(() => ...)         // Idle/Happy/Tired

// Métodos
addXP(amount)                   // Actualiza, valida level-up
triggerReward(type)             // Ejecuta animación
getAvatarMessage(context)       // Retorna mensaje contextual
getMotionTrigger()              // Qué animación disparar
```

### 4.2 useProgressAnimation.js
**Responsabilidad:** Disparar animaciones motion.js

```javascript
onLevelUp()      // Avatar evoluciona, confeti, "+XP"
onXPGain()       // ProgressBar anima, "+XP" flota
onStrike(days)   // StatCard palpita (pulse)
onAvatarClick()  // Wave animation + toast
```

### 4.3 useRewardFeedback.js
**Responsabilidad:** Audio + mensajes contextuales

```javascript
showReward(xpAmount, type)       // Dispara RewardFeedback
getContextualMessage(progress)   // "Te echo de menos", "¡Fuego!", etc.
playSound(type)                  // Si audio está enabled
```

---

## 5. Animaciones con Motion.js

### 5.1 Catálogo de Animaciones

| Nombre | Trigger | Duración | Easing | Dónde |
|--------|---------|----------|--------|-------|
| Spring Jump | XP ganado | 0.8s | spring | RewardFeedback |
| Progress Bar Fill | Valor cambia | 0.8s | ease-out | ProgressBar |
| Arc Draw | Carga página | 1.2s | ease-in-out | ProgressRadial |
| Avatar Wave | Click | 0.6s | ease-in-out | AvatarCompanion |
| Confetti | Level up | 1.5s | stagger | RewardFeedback |
| Level Up Evolution | Level up | 1.5s | spring | AvatarAnimated |
| Journey Slide | Stage change | 1.2s | ease-out | ProgressJourney |
| Pulse Streak | Active streak | 0.5s (∞) | — | StatCard |

### 5.2 Ejemplos de Código

```javascript
// Level Up
animate(avatar, 
  { scale: [1, 1.3, 1], rotate: [0, 360, 0], filter: ["brightness(1)", "brightness(1.5)", "brightness(1)"] },
  { duration: 1.5, type: "spring" }
)

// Progress Bar
animate(progressBar, 
  { width: ["0%", "75%"] }, 
  { duration: 0.8, easing: "ease-out" }
)

// Avatar Wave
animate(avatar,
  { rotate: [0, 10, -10, 0], scale: [1, 1.1, 1] },
  { duration: 0.6, easing: "ease-in-out" }
)
```

---

## 6. Integración por Página

### 6.1 Dashboard.vue

**Layout:**
```
┌─────────────────────────────┐
│ Bienvenido, [nombre]        │
├─────────────────────────────┤
│ [AvatarCompanion] [RadialProgress]
├─────────────────────────────┤
│ ProgressJourney (roadmap)   │
├─────────────────────────────┤
│ StatCards (4-6 materias)    │
├─────────────────────────────┤
│ [Iniciar Quiz] [Simulator]  │
└─────────────────────────────┘
```

**Componentes:**
- `AvatarCompanion` (clickeable, saludar + mensajes)
- `ProgressRadial` (stats circulares)
- `ProgressJourney` (roadmap visual)
- `StatCard` (materias)

**Interactivo:**
- Click avatar: Saluda + mensaje motivacional
- Click materia: Va a estudiar esa materia

### 6.2 Quiz/Session.vue

**Layout:**
```
┌──────────────────────────┐
│ [AvatarTutor] Preg: 5/10 │
│  Burbuja de ayuda        │
│                          │
│ Pregunta...              │
│ [A] [B] [C] [D]         │
│                          │
│ ProgressBar: ▓░░░░░░░░░  │
│ +25 XP (flotando)        │
└──────────────────────────┘
```

**Componentes:**
- `AvatarTutor` (habla, gesticula)
- `ProgressBar` (preguntas respondidas)
- `RewardFeedback` (cuando acierta)
- `AvatarDialog` (si hace click)

**Interactivo:**
- Click avatar: Panel de ayuda
- Responder: Anima + confeti
- Setting: Ocultar/mostrar AvatarTutor

### 6.3 Simulator/Exam.vue

**Similar a Quiz pero:**
- Avatar es más "serio"
- ProgressBar muestra **predicción de calificación en vivo**
- Avatar gesticula según desempeño (nerviosismo si va mal, motivación si va bien)

### 6.4 Progress/Index.vue

**Layout:**
```
┌──────────────────────────┐
│ [AvatarShowcase] (250px) │
│ Cosmética equipada       │
│                          │
│ ProgressRadial (ampliado)│
│                          │
│ Timeline de Logros       │
│ ▲ Subiste a Aprendiz    │
│ ▲ Desbloqueaste skin    │
│                          │
│ [Ver Rewards Shop]       │
└──────────────────────────┘
```

---

## 7. Visual Direction

### 7.1 Color Palette
- **Primary Gradient:** Purpura (#667eea) → Azul (#764ba2)
- **Accent:** Naranja (#ff6b00) — se mantiene del sistema actual
- **Success:** Verde (#10b981)
- **Warning:** Naranja (#f59e0b)
- **Critical:** Rojo (#ef4444)
- **Background:** Degradados sutiles, glassmorphism (semi-transparente)

### 7.2 Typography
- **Heading:** Bold, uppercase tracking (ej: "NIVEL 5")
- **Body:** Regular, legible en todos los tamaños
- **Label:** Small, uppercase, subtle color

### 7.3 Spacing & Borders
- **Borders:** Redondeados (12-20px), no duros
- **Glassmorphism:** backdrop-filter blur, semi-transparent bg
- **Spacing:** Consistente con Tailwind spacing scale

---

## 8. Implementation Scope

### Phase 1 (Primera Semana)
1. Crear componentes de progresión base (ProgressBar, ProgressRadial, ProgressJourney)
2. Crear AvatarCompanion con mensajes contextuales
3. Integrar en Dashboard
4. Animar con motion.js

### Phase 2 (Segunda Semana)
1. Integrar en Quiz/Session
2. Integrar en Simulator
3. AvatarTutor con reacciones
4. RewardFeedback completo

### Phase 3 (Tercera Semana)
1. Progress/Index con AvatarShowcase
2. Sonidos y feedback audio
3. Testing y refinamientos
4. Performance optimization

---

## 9. Success Criteria

- ✅ Estudiantes entienden claramente dónde están (confusión resuelta)
- ✅ Las métricas se sienten "ganables" (engagement sube)
- ✅ Animaciones son satisfactorias (recompensa inmediata)
- ✅ Avatar Companion es divertido y no intrusivo
- ✅ Dashboard carga en < 1s (performance)
- ✅ Funciona en mobile (responsive)

---

## 10. Technical Notes

- **Motion.js Version:** 12.38.0 (ya instalado)
- **Vue Version:** 3.4+
- **Tailwind:** 4.0.0
- **Inertia:** Usar props reactivos, no estado local
- **Audio:** Usar Web Audio API con fallback graceful
- **Accessibility:** ARIA labels en avatares, alt text en cosmética

---

## Preguntas Resueltas en Brainstorm

✅ Visual Direction: Gradientes Suaves  
✅ Enfoque Arquitectónico: Hybrid (Modular + Composables)  
✅ Avatar Tone: Casual/Divertido  
✅ Avatar Messages: Contextuales  
✅ Avatar Tutor: Setting on/off, default ON, ocultable  
✅ Integración: Dashboard + Quiz + Simulator + Progress (todo)  

---

**Next Step:** Implementation Plan (writing-plans skill)
