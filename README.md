# NexusEdu — AI Powered Education Platform 🎓🚀

![NexusEdu Banner](https://images.unsplash.com/photo-1501504905252-473c47e087f8?q=80&w=1200&auto=format&fit=crop)

**NexusEdu** is a cutting-edge Learning Management System (LMS) specifically architected for high-performance exam preparation (focused on UNAM Mexico). It leverages Artificial Intelligence to personalize the learning experience through adaptive difficulty and spaced repetition algorithms.

## 🌟 Key Features

- **🤖 AI-Driven Question Generation**: Integration with Anthropic's Claude API to generate context-aware, high-quality academic questions.
- **📈 Adaptive Difficulty Service**: Real-time adjustment of question complexity based on student performance (Mastery Level).
- **🧠 Spaced Repetition System (SRS)**: Implementation of the **SuperMemo-2 (SM-2)** algorithm to optimize long-term memory retention.
- **⚖️ UNAM Simulator**: Full-length mock exams (120 questions) with area-specific distributions and real-time grading.
- **📊 Advanced Analytics**: Detailed progress tracking per subject, accuracy trends, and UNAM score projections.
- **🔥 Study Streaks**: Gamification engine to maintain student engagement through study streaks and daily goals.

## 🛠️ Technical Stack

- **Backend**: [Laravel 11](https://laravel.com/) (PHP 8.3+)
- **Frontend**: [Vue 3](https://vuejs.org/) with [Inertia.js](https://inertiajs.com/) (The Monolith replacement)
- **Styling**: [Tailwind CSS 4](https://tailwindcss.com/)
- **State Management**: [Pinia](https://pinia.vuejs.org/)
- **Database**: 
  - **MySQL**: Persistent storage for users, exams, and academic content.
  - **Redis**: High-speed caching for adaptive difficulty metrics and real-time state.
- **Intelligence**: [Anthropic Claude API](https://www.anthropic.com/api)
- **Tooling**: Vite, Composer, NPM.

## 🚀 Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL & Redis

### Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/edervalois88/lms.git
   cd nexusedu
   ```

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Configure your database and `ANTHROPIC_API_KEY` in the `.env` file.*

4. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate --seed
   ```

5. **Start the application**:
   ```bash
   # Terminal 1
   php artisan serve
   
   # Terminal 2
   npm run dev
   ```

## 🏗️ Architecture Design

NexusEdu follows a service-oriented architecture within the Laravel framework:
- **`App\Services\AI`**: Handles Claude API integration and prompt engineering.
- **`App\Services\Learning`**: Contains logic for SRS (SM-2) and Adaptive Difficulty algorithms.
- **`App\Http\Middleware`**: Custom logic for onboarding flow and streak tracking.

---
Developed by [Eder Valois](https://github.com/edervalois88).
