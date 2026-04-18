# Avatar SVG Isométrico Dinámico - Design Spec

> **For agentic workers:** Use superpowers:writing-plans to implement this spec task-by-task.

**Goal:** Replace emoji avatars with dynamic, parameterized SVG isometric 3D avatars that are fully scalable, animatable, and integrate seamlessly with the existing cosmetics/rewards system.

**Architecture:** One Vue component (`Avatar.vue`) renders SVG dynamically based on `equipped` cosmetics from the store. Cosmetic definitions (colors, styles, SVG attributes) are stored in `cosmetics.js` and indexed by cosmetic code. Four animation states (idle, happy, tired, thinking) apply via CSS keyframes based on a `state` prop. The component scales perfectly from 60px to 240px without quality loss.

**Tech Stack:** Vue 3, SVG native, CSS animations, Pinia (existing store pattern)

---

## Architecture

### High-Level Data Flow

```
User equips cosmetic (e.g., outfit: 'student_robes')
  ↓
avatarStore.setEquipped('outfit', 'student_robes')
  ↓
<Avatar :equipped="store.equipped" :state="state" size="lg" />
  ↓
Avatar component computes outfit properties from cosmetics.js
  ↓
Renders SVG with dynamic colors, paths, details
  ↓
Applies CSS animation class based on :state prop
  ↓
Result: Fully personalized, animated avatar
```

### Component Responsibility

**`Avatar.vue`** (single source of truth for rendering):
- Receives `equipped` object: `{ color, outfit, accessories[], pet, background }`
- Receives `state`: one of `['idle', 'happy', 'tired', 'thinking']`
- Receives `size`: one of `['sm', 'md', 'lg']` (scales SVG viewBox proportionally)
- Computes cosmetic properties from COSMETIC_DEFINITIONS
- Renders isometric SVG with layers: body → outfit → accessories → pet
- Applies animation class based on state

**`cosmetics.js`** (data layer):
- Centralized definitions for all cosmetics
- Each cosmetic has: name, visual properties (colors, SVG attributes, animation flags)
- Keyed by code (matches reward database codes)
- No rendering logic—pure data

**`avatarStore.js`** (state):
- Maintains `equipped` object with currently equipped cosmetics
- Already exists; will hydrate from server `RewardStoreController::state()`
- Methods: `setEquipped()`, `hydrateFromEquipped()`

---

## Isometric SVG Structure

Base avatar (body, head, limbs, eyes, mouth):
- Head (8x8 in isometric view): polygon with gradient fill
- Eyes: circles positioned isometrically
- Mouth: SVG path for expression
- Torso (12x12 isometric): polygon representing shirt/body
- Arms: two polygons
- Legs: two polygons for pants/shoes

Layers (applied conditionally):
1. **Color** (`equipped.color`) → skin tone fill
2. **Outfit** (`equipped.outfit`) → polygon overlays for clothing + detail paths
3. **Accessories** (`equipped.accessories` array, max 2) → small SVG overlays (hat, glasses, backpack)
4. **Pet** (`equipped.pet`) → small circle/shape positioned at shoulder
5. **Background** (`equipped.background`) → optional backdrop (not on avatar itself, maybe frame)

All coordinates scale proportionally based on size prop.

---

## Sizing Strategy

Three canonical sizes, all using same SVG viewBox `0 0 140 160`:

| Size | Width | Height | Use Case |
|------|-------|--------|----------|
| **sm** | 60px | 80px | Chat, comments, user lists |
| **md** | 120px | 140px | Card previews, secondary displays |
| **lg** | 200px | 240px | Profile, customizer, featured displays |

SVG scales losslessly via `viewBox` + responsive `width`/`height`. No pixelation.

---

## Animation States

Four states, each with distinct CSS animation:

### Idle (default)
- **Animation:** `idle-float` 3s ease-in-out infinite
- **Effect:** Gentle vertical bob (±8px), like floating/breathing
- **Use:** When nothing is happening, avatar is at rest

### Happy
- **Animation:** `happy-bounce` 0.6s ease-out (one-shot)
- **Effect:** Scale from 1.0 → 1.1 → 1.0, like jumping
- **Use:** After correct answer, achievement unlock, positive feedback
- **Timing:** Play once, then revert to idle after 1.5s

### Tired
- **Animation:** None (static)
- **Effect:** Opacity 0.7, slight grayscale filter (20%)
- **Use:** After extended study session or fatigue milestone
- **Visual:** Avatar looks less vibrant, more muted

### Thinking
- **Animation:** `thinking-sway` 2s ease-in-out infinite
- **Effect:** Small side-to-side rotation (±2°), like tilting head while thinking
- **Use:** During quiz, simulation, or when user is answering questions
- **Visual:** Contemplative pose

---

## File Structure

### Create
- `resources/js/Components/Gamification/Avatar.vue` — Main component
- `resources/js/Data/cosmetics.js` — Cosmetic definitions

### Modify
- `resources/js/Stores/gamification/avatarStore.js` — Ensure proper hydration
- `resources/js/Components/Gamification/AvatarCustomizer.vue` — Use new Avatar component
- `resources/js/Components/Progress/AvatarAnimated.vue` — Replace or wrap with Avatar
- `resources/js/Pages/Progress/Index.vue` — Use Avatar in dashboard

### Delete (optional)
- Old emoji-based avatar components if fully replaced

---

## Integration Points

### 1. AvatarCustomizer (existing)
Replace preview:
```vue
<!-- Before: <AvatarAnimated icon="🎓" state="idle" size="lg" /> -->
<!-- After: -->
<Avatar :equipped="equipped" :state="previewState" size="lg" />
```

### 2. Progress Dashboard
Show user's avatar:
```vue
<Avatar :equipped="user.gamification.equipped" state="idle" size="lg" />
```

### 3. Quiz/Simulator Pages
Avatar responds to quiz events:
```javascript
const avatarState = ref('idle');
const handleQuizStart = () => { avatarState.value = 'thinking'; };
const handleCorrectAnswer = () => {
  avatarState.value = 'happy';
  setTimeout(() => { avatarState.value = 'idle'; }, 1500);
};
```

### 4. DailyPractice Component
Animate avatar based on performance:
```vue
<Avatar :equipped="userEquipped" :state="answerState" size="md" />
```

---

## Cosmetic Definitions Format

`resources/js/Data/cosmetics.js` structure:

```javascript
export const COSMETIC_DEFINITIONS = {
  colors: {
    purple: { skinTone: '#d4a574', name: 'Púrpura Claro' },
    golden: { skinTone: '#f4d03f', name: 'Dorado' },
    // More colors...
  },
  
  outfits: {
    student_robes: {
      name: 'Túnica Estudiante',
      color: '#9333ea',
      details: { hasStripes: true, emblem: 'book' },
    },
    wizard_robes: {
      name: 'Robes Mágico',
      color: '#3b82f6',
      details: { hasStars: true, glowEffect: true },
    },
    // More outfits...
  },
  
  accessories: {
    glasses: { name: 'Gafas', type: 'glasses', color: '#1f2937' },
    backpack: { name: 'Mochila', type: 'backpack', color: '#7c3aed' },
    // More accessories...
  },
  
  pets: {
    dragon_purple: { name: 'Dragón Púrpura', color: '#a855f7', shape: 'circle' },
    owl_brown: { name: 'Búho Marrón', color: '#92400e', shape: 'circle' },
    // More pets...
  },
  
  backgrounds: {
    library: { name: 'Biblioteca', color: '#92400e', pattern: 'books' },
    forest: { name: 'Bosque', color: '#15803d', pattern: 'trees' },
    // More backgrounds...
  },
};
```

Keys match reward database `code` fields.

---

## Props & Events

### Avatar.vue Props

```typescript
interface Props {
  equipped: {
    color: string;        // code of equipped color
    outfit: string;       // code of equipped outfit
    accessories: string[]; // array of accessory codes (max 2)
    pet: string;          // code of equipped pet
    background?: string;  // code of background (optional)
  };
  state?: 'idle' | 'happy' | 'tired' | 'thinking'; // default: 'idle'
  size?: 'sm' | 'md' | 'lg';                        // default: 'md'
}
```

### No events
Avatar is presentational only. Parent component manages state changes.

---

## Error Handling

- **Missing cosmetic code:** Fall back to default cosmetic (e.g., default color if code not found)
- **Invalid state:** Default to 'idle'
- **Invalid size:** Default to 'md'
- **Empty equipped:** Render with all defaults (still valid avatar)

---

## Testing Strategy

Unit tests for Avatar.vue:
- Props are correctly applied
- SVG renders with correct cosmetic colors/details
- Animation classes are applied based on state
- Size prop scales viewBox correctly
- Missing cosmetics fall back gracefully

Integration tests:
- Avatar updates when store changes
- State changes trigger animation classes
- Cosmetics loaded from server are applied correctly

---

## Migration Plan

1. Create Avatar.vue + cosmetics.js
2. Update avatarStore.js to hydrate from server
3. Replace AvatarAnimated preview in AvatarCustomizer
4. Update Progress/Index to use Avatar
5. Update DailyPractice, Quiz, Simulator to wire avatar state
6. Remove old emoji avatar component (or keep as fallback)
7. Test across all sizes and states

---

## Success Criteria

✅ Avatar renders in sm/md/lg without quality loss
✅ All 4 animation states work (idle, happy, tired, thinking)
✅ Cosmetics from store are visually applied (color, outfit, pet, accessories)
✅ Avatar updates when store.equipped changes
✅ Component integrates with existing AvatarCustomizer, DailyPractice, Progress pages
✅ No console errors or warnings
✅ Performance: <16ms render time on all sizes
