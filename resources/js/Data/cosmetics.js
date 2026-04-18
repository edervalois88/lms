export const COSMETIC_DEFINITIONS = {
  colors: {
    purple: {
      name: 'Púrpura Claro',
      color: '#d4a574',
      code: 'purple',
    },
    golden: {
      name: 'Dorado',
      color: '#f4d03f',
      code: 'golden',
    },
    fair: {
      name: 'Piel Clara',
      color: '#f5deb3',
      code: 'fair',
    },
    tan: {
      name: 'Bronceado',
      color: '#d2b48c',
      code: 'tan',
    },
  },

  outfits: {
    student_robes: {
      name: 'Túnica Estudiante',
      code: 'student_robes',
      color: '#9333ea',
      details: {
        hasStripes: false,
        hasStars: false,
        glowEffect: false,
        emblem: 'book',
      },
    },
    wizard_robes: {
      name: 'Túnica Mágica',
      code: 'wizard_robes',
      color: '#3b82f6',
      details: {
        hasStripes: false,
        hasStars: true,
        glowEffect: true,
        emblem: null,
      },
    },
    casual_shirt: {
      name: 'Camisa Casual',
      code: 'casual_shirt',
      color: '#ef4444',
      details: {
        hasStripes: true,
        hasStars: false,
        glowEffect: false,
        emblem: null,
      },
    },
    lab_coat: {
      name: 'Bata de Laboratorio',
      code: 'lab_coat',
      color: '#ffffff',
      details: {
        hasStripes: false,
        hasStars: false,
        glowEffect: false,
        emblem: 'flask',
      },
    },
  },

  accessories: {
    glasses: {
      name: 'Gafas',
      code: 'glasses',
      type: 'glasses',
      color: '#1f2937',
      size: 'small',
    },
    backpack: {
      name: 'Mochila',
      code: 'backpack',
      type: 'backpack',
      color: '#7c3aed',
      size: 'large',
    },
    crown: {
      name: 'Corona',
      code: 'crown',
      type: 'crown',
      color: '#ffd700',
      size: 'small',
    },
    scarf: {
      name: 'Bufanda',
      code: 'scarf',
      type: 'scarf',
      color: '#dc2626',
      size: 'medium',
    },
  },

  pets: {
    dragon_purple: {
      name: 'Dragón Púrpura',
      code: 'dragon_purple',
      type: 'dragon',
      color: '#a855f7',
      shape: 'circle',
    },
    owl_brown: {
      name: 'Búho Marrón',
      code: 'owl_brown',
      type: 'owl',
      color: '#92400e',
      shape: 'circle',
    },
    phoenix_gold: {
      name: 'Fénix Dorado',
      code: 'phoenix_gold',
      type: 'phoenix',
      color: '#fbbf24',
      shape: 'circle',
    },
    fox_red: {
      name: 'Zorro Rojo',
      code: 'fox_red',
      type: 'fox',
      color: '#dc2626',
      shape: 'circle',
    },
  },

  backgrounds: {
    library: {
      name: 'Biblioteca',
      code: 'library',
      color: '#92400e',
      pattern: 'books',
    },
    forest: {
      name: 'Bosque',
      code: 'forest',
      color: '#15803d',
      pattern: 'trees',
    },
    starfield: {
      name: 'Campo de Estrellas',
      code: 'starfield',
      color: '#1e1b4b',
      pattern: 'stars',
    },
  },
};

// Helper function to get default cosmetic
export function getDefaultCosmetic(type) {
  const defaults = {
    color: 'purple',
    outfit: 'student_robes',
    pet: 'dragon_purple',
    background: 'library',
  };
  return defaults[type] || null;
}
