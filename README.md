<div align="center">

# PulseERP

<!-- Dynamic Typing SVG Header -->
<a href="https://git.io/typing-svg">
  <img src="https://readme-typing-svg.demolab.com?font=Fira+Code&weight=700&size=26&duration=3500&pause=1000&color=A3E635&center=true&vCenter=true&width=520&lines=PulseERP+Enterprise+Core;Dual-Theme+Glassmorphic+Portal;Extreme+Simplicity+for+Non-Tech+Users;Zero-Trust+Telemetry+Architecture" alt="Typing SVG" />
</a>

<p align="center">
  <strong>Next-Generation Enterprise Resource Planning & Live Telemetry Intelligence Suite</strong>
</p>

<!-- Tech Stack Badges -->
<p align="center">
  <img src="https://img.shields.io/badge/Laravel_12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel" />
  <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS" />
  <img src="https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=black" alt="Alpine.js" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite" />
</p>

---

</div>

## 🌟 Overview

**PulseERP** is a modern, enterprise-grade resource planning portal built with **Laravel**, **Tailwind CSS**, and **Alpine.js**. Engineered with an absolute focus on **extreme simplicity for non-technical users**, PulseERP delivers real-time operations, security status, and telemetry data through an intuitive, clutter-free glassmorphic interface that anyone can understand instantly.

---

## ✨ Key Features

- **🌓 Dual-Theme Glassmorphism Engine**:
  - **Soft Light Theme**: Default soothing off-whites (`bg-slate-50`), soft ambient watercolor washes, and frosted glass panels designed for all-day eye comfort without harsh bright glares.
  - **Midnight Dark Theme**: Deep zinc backdrop (`dark:bg-zinc-950`) with ambient gold/lime glows and frosted translucency.
  - **Zero-Flash Persistence**: Immediate `localStorage` theme state evaluation before DOM paint eliminates unstyled flashing on navigation.
  - **Interactive Sun/Moon Toggle**: Accessible theme switcher with fluid icon rotation and glow effects.

- **🛡️ Mandatory Solid Black (#000000) Emblem**:
  - The signature PulseERP shield emblem remains housed in a strictly solid `#000000` container across desktop sidebars, mobile drawers, auth screens, and modal dialogs in both light and dark themes.

- **👤 Non-Technical Friendly Architecture**:
  - Plain-English status metrics (*100% Operational*, *Sub-second Latency*, *Zero-Trust Verified*).
  - Streamlined navigation eliminating complex menus in favor of a clean, responsive sidebar.
  - Self-service account profile and security credentials manager with confirmation modals.

- **🔒 Enterprise Security**:
  - Complete authentication workflows (Login, Registration, Password Update, Profile Deletion).
  - Built-in TLS 1.3 telemetry indicators and CSRF session protections.

---

## 🛠️ Tech Stack

| Technology | Purpose |
| :--- | :--- |
| **Laravel** | Backend framework, routing, Eloquent ORM, and authentication controllers |
| **Tailwind CSS** | Custom styling, glassmorphism utilities, and class-based dark mode (`darkMode: 'class'`) |
| **Alpine.js** | Lightweight client-side reactivity, mobile drawer toggles, and reactive theme persistence |
| **MySQL** | Relational data persistence for users, enterprise sessions, and telemetry bindings |
| **Vite** | Fast asset bundling and Hot Module Replacement (HMR) |

---

## 🚀 Getting Started

Follow these steps to set up and run PulseERP locally:

### 1. Clone the Repository
```bash
git clone git@github.com:ChandupaJay1/dairy-product-ms.git
cd dairy-product-ms
```

### 2. Install PHP & Node Dependencies
```bash
composer install
npm install
```

### 3. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Configure your database credentials inside `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pulseerp
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Run Migrations
```bash
php artisan migrate
```

### 5. Build Assets & Start Development Servers
```bash
# Terminal 1: Vite Asset Dev Server
npm run dev

# Terminal 2: Laravel Artisan Server
php artisan serve
```

Visit **`http://localhost:8000`** in your browser. Unauthenticated visits automatically redirect to the login gateway.

---

## 📂 Project Structure

```text
PulseERP/
├── app/                  # Application controllers, models, and providers
├── config/               # Application configuration
├── database/             # Migrations, seeders, and factories
├── resources/
│   ├── css/              # App styles and custom Tailwind utilities
│   ├── js/               # JavaScript and Alpine.js bootstrap
│   └── views/
│       ├── auth/         # Redesigned Split-Screen Login & Registration
│       ├── components/   # Glassmorphic UI & Form input components
│       ├── layouts/      # App layout (sidebar & header) and Guest layout
│       └── profile/      # User Profile & Security settings
├── routes/
│   ├── auth.php          # Breeze Authentication routes
│   └── web.php           # Web routes (redirect / to /login, /dashboard)
└── tailwind.config.js    # Tailwind configuration with darkMode: 'class'
```

---

## 📄 License

This project is open-sourced software licensed under the [MIT License](LICENSE).
