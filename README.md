# MediMinder

## Overview

MediMinder is a medication management system that helps patients manage their medication schedules, track adherence, and receive reminders. The system also supports caregiver monitoring to improve medication compliance and patient safety.

## Technologies Used

* Vue 3
* Vite
* Vue Router
* Pinia
* Tailwind CSS
* JavaScript

---

## Prerequisites

Before running the project, ensure the following software is installed:

* Node.js (v18 or later recommended)
* npm (comes with Node.js)
* Git

Check installation:

```bash
node -v
npm -v
git --version
```

---

## Clone the Repository

```bash
git clone https://github.com/nadd88/MediMinder
cd MediMinder
```

## Install Dependencies

Install all required packages:

```bash
npm install
```

---

## Running the Development Server

Start the Vite development server:

```bash
npm run dev
```

The application will be available at:

```text
http://localhost:5173
```

Open the URL in your browser.

---

## Build for Production

To create a production build:

```bash
npm run build
```

The compiled files will be generated in the `dist` folder.

---

## Preview Production Build

To preview the production build locally:

```bash
npm run preview
```


## Mock Data

The current prototype uses mock data located in:

```text
src/api/mockClient.json
```

Mock API functions are implemented in:

```text
src/api/mockClient.js
```

These files simulate backend responses while the actual backend is under development.

---

## Features Implemented

### Patient Dashboard

* View medication summary
* View adherence statistics
* View due and missed doses

### Dose Management

* Mark doses as taken
* Skip doses
* View medication schedule
* Track medication status

### Adherence Tracking

* Weekly adherence summary
* Missed dose monitoring

---

## Team

Developed as part of the Cross-Platform Application Development Course.

### Contributors

* Nada Mohammed Ibrahim Ali
* Fatima Yousra
* Zahra Aulia
* Lauza Amru

---

## License

This project is intended for academic and educational purposes only.
