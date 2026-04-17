# NexusEdu Gamification: Rewards, Avatars & Pixel Art System
## Cosmética + Gold + Logros Educativos

**Date:** 2026-04-16  
**Status:** Approved  
**Builds on:** 2026-04-14-student-gamification-design.md  
**Stack:** Vue 3 + Pinia + Tailwind + Motion.js + Laravel API  
**Visual Style:** Pixel Art Intermedio (estilo Habitica)

---

## Executive Summary

Expandir el sistema de gamificación existente (XP, niveles, avatar companion) con un **sistema completo de recompensas, cosmética y logros educativos** inspirado en Habitica. Los estudiantes preuniversitarios ganan Gold completando actividades educativas y lo gastan en cosmética para personalizar su avatar. Logros educativos reales desbloquean cosmética especial sin costo.

**Decisiones clave del brainstorming:**
- Estilo visual: Pixel art intermedio (Habitica) — suave, accesible, sofisticado
- Recompensas: Cosmética pura + desbloqueos por logros educativos
- Cosmética: Moderate (5 categorías: color, ropa, accesorios x2, mascota, fondo)
- Integración: Híbrida (Gold por actividades + cosmética por mastery real)
- UI: Distribuida (avatar en navbar, tienda separada, rewards en actividades)
- Arquitectura: Modular (4 stores Pinia separados)
- Prioridad: Tienda > Logros > Avatar Customizer > Animaciones

---

## 1. Arquitectura de Stores (Pinia)

### 1.1 Estructura de Carpetas

```
resources/js/Stores/gamification/
├── avatarStore.ts
├── currencyStore.ts
├── achievementsStore.ts
└── shopStore.ts
```

### 1.2 avatarStore

Gestiona el estado visual del avatar del usuario.

```javascript
{
  equipped: {
    color: 'purple',         // Color base del avatar
    outfit: 'student_robes', // Ropa equipada
    accessories: ['glasses', 'badge'], // 2 slots de accesorios
    pet: 'dragon_purple',    // Mascota companion
    background: 'library'    // Fondo del avatar
  },
  availableCosmetics: {
    colors: [...],
    outfits: [...],
    accessories: [...],
    pets: [...],
    backgrounds: [...]
  }
}
```

### 1.3 currencyStore

Gestiona Gold, XP y niveles.

```javascript
{
  gold: 1250,
  xp: 3400,
  currentLevel: 3,
  xpToNextLevel: 500
}
```

### 1.4 achievementsStore

Gestiona logros desbloqueados y cosmética especial ganada.

```javascript
{
  completed: [
    { id: 'mastery_math_8', reward: 'outfit_math_master', unlockedAt: '2026-04-10' },
    { id: 'streak_30_days', reward: 'pet_golden_dragon', unlockedAt: '2026-04-15' }
  ],
  unlockedCosmetics: ['outfit_math_master', 'pet_golden_dragon']
}
```

### 1.5 shopStore

Gestiona el catálogo de la tienda y el inventario del usuario.

```javascript
{
  catalog: [
    { id: 'outfit_warrior', cost: 250, category: 'outfit', name: 'Warrior Armor', rarity: 'rare' }
  ],
  inventory: ['outfit_warrior', 'glasses_academic'],
  transactionHistory: [
    { cosmeticId: 'outfit_warrior', cost: 250, purchasedAt: '2026-04-12' }
  ]
}
```

---

## 2. Componentes Nuevos

### 2.1 Estructura de Carpetas

```
resources/js/Components/Gamification/
├── AvatarCustomizer.vue       # Modal/página para cambiar cosmética equipada
├── Cosmetics/
│   ├── CosmeticSelector.vue   # Selector genérico (color, ropa, accesorios)
│   └── PetSelector.vue        # Selector de mascota
├── Shop/
│   ├── ShopCatalog.vue        # Grid de items disponibles para compra
│   ├── ShopItem.vue           # Card individual con precio y preview
│   └── PurchaseConfirm.vue    # Modal de confirmación de compra
├── Currency/
│   └── CurrencyDisplay.vue    # Widget Gold + XP para navbar/sidebar
└── Achievements/
    └── AchievementUnlock.vue  # Toast/modal cuando se desbloquea cosmética
```

### 2.2 Componentes Modificados

- `AvatarShowcase.vue` → agregar click para abrir AvatarCustomizer
- `Progress/Index.vue` → link a tienda, mostrar próximos desbloqueos
- `RewardFeedback.vue` → mostrar Gold ganado además de XP

### 2.3 Nuevas Páginas

```
resources/js/Pages/Gamification/
├── Shop.vue                   # Página principal de tienda de cosmética
└── Avatar.vue                 # Página de customización completa del avatar
```

---

## 3. Data Flows

### 3.1 Ganar Gold (al completar actividad)

```
Quiz/Simulacro completado
    ↓
Backend calcula reward: { gold: 50, xp: 100 }
    ↓
Frontend recibe respuesta
    ↓
RewardFeedback.vue muestra "+50 Gold, +100 XP" con animación
    ↓
currencyStore.addGold(50) + currencyStore.addXP(100)
    ↓
Si XP suficiente → levelUp → posible desbloqueo cosmética
    ↓
CurrencyDisplay en navbar se actualiza
```

### 3.2 Comprar cosmética en tienda

```
Usuario navega a /gamification/shop
    ↓
ShopCatalog muestra items comprables (filtrados por inventario)
    ↓
Click en ShopItem → PurchaseConfirm modal
    ↓
Validación: ¿tiene suficiente Gold?
    ↓
POST /api/gamification/purchase { cosmetic_id }
    ↓
currencyStore.spendGold(cost)
shopStore.addToInventory(cosmetic_id)
    ↓
Toast: "¡Compra exitosa!" + preview del item
```

### 3.3 Desbloquear cosmética por logro educativo

```
Actividad completada → backend evalúa achievements
    ↓
Backend retorna: { achievements_unlocked: ['mastery_math_8'] }
    ↓
achievementsStore.unlock('mastery_math_8')
    ↓
AchievementUnlock.vue muestra toast especial:
  "¡Maestro de Matemáticas! Outfit desbloqueado"
    ↓
Cosmética aparece en inventario (gratis, ya equipable)
```

### 3.4 Equipar cosmética

```
Usuario abre AvatarCustomizer (click en avatar o /gamification/avatar)
    ↓
CosmeticSelector muestra items del inventario por categoría
    ↓
Click en item → preview en avatar
    ↓
Confirmar → POST /api/gamification/equip { cosmetic_id, slot }
    ↓
avatarStore.equip(slot, cosmetic_id)
    ↓
Avatar en navbar/sidebar se actualiza globalmente
```

---

## 4. Backend API Endpoints

```
GET  /api/gamification/state
  → { gold, xp, level, equipped, inventory, achievements }

POST /api/gamification/purchase
  body: { cosmetic_id }
  → { success, gold_remaining, item }

POST /api/gamification/equip
  body: { cosmetic_id, slot }
  → { equipped }

POST /api/gamification/earn-reward
  body: { activity_type, activity_id, points }
  → { gold_earned, xp_earned, level_up, achievements_unlocked }
```

---

## 5. Catálogo de Cosmética (MVP)

### 5.1 Estructura de un item

```javascript
{
  id: 'outfit_math_master',
  category: 'outfit',
  name: 'Túnica del Maestro de Matemáticas',
  description: 'Desbloqueada al dominar Matemáticas (8+/10)',
  cost: 0,                    // 0 = desbloqueable, no comprable
  rarity: 'epic',             // common, uncommon, rare, epic
  unlockedBy: 'mastery_math_8', // null = comprable con Gold
  preview_color: '#4169e1'
}
```

### 5.2 Catálogo Inicial (15 items)

| Categoría | Item | Costo | Desbloqueo | Rareza |
|-----------|------|-------|------------|--------|
| **Color** | Purple | 0 | Default | common |
| | Blue | 100 | Shop | common |
| | Green | 150 | Shop | common |
| | Gold | 200 | Shop | uncommon |
| **Outfit** | Student Robes | 0 | Default | common |
| | Warrior Armor | 250 | Shop | rare |
| | Mage Robes | 300 | Shop | rare |
| | Math Master Outfit | 0 | mastery_math_8 | epic |
| **Accessory** | Glasses | 100 | Shop | common |
| | Academic Badge | 0 | first_quiz | common |
| | Crown of Knowledge | 500 | Shop | epic |
| **Pet** | Purple Dragon | 0 | Default | common |
| | Golden Dragon | 0 | streak_30_days | epic |
| | Phoenix | 400 | Shop | rare |
| **Background** | Library | 0 | Default | common |
| | Lab | 150 | Shop | uncommon |
| | Starfield | 200 | Shop | uncommon |

### 5.3 Tabla de Logros Educativos (8 logros)

| Logro | Condición | Cosmética Desbloqueada | Rareza |
|-------|-----------|----------------------|--------|
| Primera Pregunta | Completar primer quiz | Academic Badge (accessory) | common |
| Racha de 7 días | 7 días consecutivos | Blue Flame (accessory) | uncommon |
| Racha de 30 días | 30 días consecutivos | Golden Dragon (pet) | epic |
| Competente en X | mastery ≥ 5 en materia | Badge de materia (accessory) | uncommon |
| Maestro de X | mastery ≥ 8 en materia | Outfit temático (outfit) | epic |
| Simulacro Perfecto | Score máximo en simulacro | Crown of Knowledge (accessory) | epic |
| Explorador | Estudiar 5 materias | Phoenix (pet) | rare |
| Incansable | 500 preguntas respondidas | Starfield (background) | rare |

---

## 6. Tabla de Recompensas por Actividad

| Actividad | Gold | XP |
|-----------|------|-----|
| Completar quiz (por pregunta correcta) | +5 | +10 |
| Completar quiz completo | +30 | +50 |
| Completar simulacro | +100 | +150 |
| Sesión de estudio (≥15 min) | +20 | +30 |
| Login diario (streak) | +10 | +15 |

---

## 7. Integración con Progresión Existente

### 6.1 Conexión con progressCalculations.js

El sistema existente de niveles (Novato → Maestro) sigue funcionando. Gold es una moneda paralela:

- **XP** → determina nivel y journey stage (ya existe)
- **Gold** → moneda para comprar cosmética (nuevo)
- **Achievements** → basados en mastery_score y streaks (nuevo)

### 6.2 Conexión con avatarMessages.js

Los mensajes contextuales del avatar se enriquecen:

- Cuando el usuario desbloquea un logro, el avatar celebra
- Cuando el usuario tiene Gold suficiente para una compra nueva, el avatar sugiere visitar la tienda
- Sentimiento "success" incluye menciones de cosmética ganada

---

## 7. Estilo Visual

**Dirección:** Pixel Art Intermedio (estilo Habitica)
- Avatares suaves, redondeados, con proporciones naturales
- Paleta rica (16-32 colores por personaje)
- Formas redondeadas con bordes pixel visibles
- Mascotas expresivas y animadas
- Fondos temáticos educativos

**Consistencia con UI existente:**
- Gradientes púrpura → azul (ya en uso)
- Glassmorphism para modales y cards
- Motion.js para animaciones de reward y transiciones

---

## 8. Prioridad de Implementación

1. **Tienda de Gold y cosmética** (currencyStore + shopStore + Shop.vue)
2. **Desbloqueos por logros educativos** (achievementsStore + AchievementUnlock)
3. **Avatar customizable** (avatarStore + AvatarCustomizer + Avatar.vue)
4. **Animaciones y efectos visuales** (transiciones, confetti mejorado, auras)
