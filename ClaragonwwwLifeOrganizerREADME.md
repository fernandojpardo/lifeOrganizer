# LifeOrganizer - Gestor de Finanzas Personales

Una aplicación web moderna para organizar tus finanzas personales. Gestiona ingresos, gastos, objetivos de ahorro y deudas con proyecciones inteligentes.

## Características

✅ **Dashboard** - Resumen financiero en tiempo real
✅ **Gestión de Ingresos** - Registra tus ingresos semanales, quincenales o mensuales
✅ **Registro de Gastos** - Categoriza tus gastos automáticamente
✅ **Objetivos de Ahorro** - Define metas de ahorro con porcentajes automáticos
✅ **Gestión de Deudas** - Controla el pago de tus deudas
✅ **Proyecciones** - Visualiza proyecciones de 12 meses de ahorro y pago de deudas

## Stack Tecnológico

- **Backend**: Laravel 10
- **Frontend**: Vue.js 3
- **Build Tool**: Vite
- **Styling**: Tailwind CSS
- **Database**: MySQL (Laragon)

## Usuario de Prueba

```
Email: demo@example.com
Contraseña: password
```

## Ejecución

Terminal 1 - Servidor Vite:
```
npm run dev
```

Terminal 2 - Servidor Laravel (si es necesario):
```
php artisan serve
```

Accede a: http://lifeorganizer.test (en Laragon)

## Funcionalidades

### Dashboard
- Resumen financiero en tiempo real
- Balance total
- Ingresos, gastos, ahorros y deudas

### Ingresos
- Registra ingresos (semanal, quincenal, mensual)
- Define próxima fecha de pago
- Descripción personalizada

### Gastos
- Registra gastos diarios
- Categoriza (Alimentación, Transporte, etc.)
- Histórico de gastos

### Objetivos de Ahorro
- Define metas de ahorro
- Asigna % del ingreso automáticamente
- Seguimiento visual del progreso

### Deudas
- Registra deudas
- Define % del ingreso para pagar
- Visualiza progreso y estimaciones

### Proyecciones
- Proyecciones de 12 meses
- Ahorros y pagos de deudas por mes
- Estado final de deudas

## API Endpoints

Todos requieren autenticación:

```
GET    /api/finance/summary      - Resumen financiero
GET    /api/finance/projection   - Proyecciones

GET    /api/incomes              - Listar ingresos
POST   /api/incomes              - Crear
PUT    /api/incomes/{id}         - Actualizar
DELETE /api/incomes/{id}         - Eliminar

GET    /api/expenses             - Listar gastos
POST   /api/expenses             - Crear
PUT    /api/expenses/{id}        - Actualizar
DELETE /api/expenses/{id}        - Eliminar

GET    /api/saving-goals         - Listar objetivos
POST   /api/saving-goals         - Crear
PUT    /api/saving-goals/{id}    - Actualizar
DELETE /api/saving-goals/{id}    - Eliminar

GET    /api/debts                - Listar deudas
POST   /api/debts                - Crear
PUT    /api/debts/{id}           - Actualizar
DELETE /api/debts/{id}           - Eliminar
```

## Estructura

```
resources/js/
├── App.vue                  - Componente principal con navegación
└── components/
    ├── Dashboard.vue        - Resumen financiero
    ├── Income.vue           - Gestión de ingresos
    ├── Expenses.vue         - Gestión de gastos
    ├── SavingGoals.vue      - Objetivos de ahorro
    ├── Debts.vue            - Gestión de deudas
    └── Projection.vue       - Proyecciones de 12 meses
```

¡A organizar tus finanzas!
