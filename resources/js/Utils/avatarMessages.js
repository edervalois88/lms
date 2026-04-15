// resources/js/Utils/avatarMessages.js
export const avatarMessages = {
  dashboard: {
    motivation: [
      '¡Vamos, estás en fuego! 🔥',
      '¡Casi ahí! 💪',
      'Tú puedes 🚀',
      'Eres un crack 🌟',
      'Vamos a lograrlo juntos 🎯',
    ],
    concern: [
      'Te echo de menos... 😢',
      '¿Todo está bien?',
      'Vuelve cuando puedas',
    ],
    success: [
      '¡Excelente! 🎉',
      '¡Lo hiciste! 🥳',
      'Brillante 💫',
    ],
  },
  quiz: {
    motivation: [
      'Confía en ti 💪',
      '¡Vamos! 🚀',
      'Tú puedes 🌟',
    ],
    correct: [
      '¡Excelente! 🎉',
      'Correcto 👏',
      'Muy bien 🔥',
    ],
    incorrect: [
      'Casi lo tienes',
      'Intenta de nuevo 💪',
      'La próxima seguro',
    ],
  },
  simulator: {
    motivation: [
      '¡Vamos, estás en fuego! 🔥',
      '¡Casi ahí! 💪',
      'Tú puedes 🚀',
    ],
    correct: [
      '¡Excelente! 🎉',
      'Correcto 👏',
      'Muy bien 🔥',
    ],
    incorrect: [
      'Casi lo tienes',
      'Intenta de nuevo 💪',
      'La próxima seguro',
    ],
    concern: [
      'No te desanimes 💪',
      'Sigue adelante 🚀',
      'Tú puedes 😊',
    ],
    success: [
      '¡Lo lograste! 🎉',
      'Excelente trabajo 👏',
      'Impresionante 🌟',
    ],
  },
  progress: {
    motivation: [
      '¡Mira cuánto has avanzado! 📈',
      '¡Sigue así, vas muy bien! 🚀',
      'Tu esfuerzo se nota 💪',
    ],
    success: [
      '¡Felicidades por tu racha! 🔥',
      '¡Dominaste esa materia! 📚',
      '¡Subiste de nivel! ⭐',
    ],
    concern: [
      '¿Qué materia quieres reforzar hoy?',
      'Cada sesión cuenta 💡',
      'Pequeños pasos, grandes resultados',
    ],
  },
};

export function getContextualMessage(context, sentiment) {
  const messages = avatarMessages[context]?.[sentiment] || [];
  return messages[Math.floor(Math.random() * messages.length)] || 'Tú puedes 🚀';
}
