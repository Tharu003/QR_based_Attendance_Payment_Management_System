# 🎓 QR-Based Attendance, Payment & LMS Management System

An all-in-one Education Institute Management System designed for tuition institutes and schools. This platform combines **QR Code Attendance**, **Monthly Fee Payment Management**, **LMS (Learning Management System)** with **AI-generated Quizzes**, and **Exam Results Tracking**.

---

## 🚀 Key Features

### 📌 1. QR-Based Attendance System
- Unique `qr_token` generation for each registered student.
- Quick QR scanning for real-time daily attendance.
- Attendance status tracking (Present / Absent) per class and time.

### 💳 2. Payment & Expense Management
- Monthly class fee tracking per student and class.
- Fee adjustment and month-to-month payment transfer management (`payment_transfers`).
- Institute operational expense logging.

### 📚 3. LMS & Material Sharing
- Class material management (Tutes, Notes, PDF files, and Video URLs).
- Organized by grade levels and weekly schedules.
- **AI-Powered Quiz Generation**: Create quiz questions manually or automatically using AI.
- Interactive online quiz attempts, student submissions, and automated scoring.

### 📝 4. Exams & Results Portal
- Schedule physical or online examinations with hall allocations.
- Record and display student marks and academic performance.

### 📅 5. Timetable & Announcements
- Weekly class schedules with physical hall numbers and Zoom links.
- Target notices (Class cancellations, exam updates, tute uploads).
- Direct student notification feed.

### 👥 6. Role-Based Access Control (RBAC)
- **Super Admin & Admin:** Full system control, expense logs, and database administration.
- **Teacher:** Content upload, quiz creation, and exam marks entry.
- **Assistant:** Attendance scanning and cash payment collecting.
- **Student:** View class materials, attempt quizzes, check results, and receive notifications.

---

## 🛠️ Tech Stack

- **Backend:** PHP 8.x
- **Database:** MySQL / MariaDB (`attendence`)
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap / Tailwind CSS
- **Server:** Apache (XAMPP / WAMP)

---

## 🗄️ Database Architecture

The system operates on a relational database design containing key modules:

| Table | Function |
| :--- | :--- |
| `students` | Stores student registration data and unique QR tokens |
| `attendance` | Records timestamped attendance via QR scanning |
| `payments` & `payment_transfers` | Fee records, monthly payments, and fee transfer logs |
| `classes` & `class_grades` | Class details, grade levels, teachers, and monthly fees |
| `class_materials` | Learning resources (PDFs, tutes, videos) |
| `quizzes` & `quiz_questions` | Manual and AI-generated quiz questions |
| `exams` & `exam_results` | Examination timetables and student performance marks |
| `notices` & `student_notifications` | System-wide and grade-specific announcements |
| `users` | Role authentication (`admin`, `superadmin`, `teacher`, `assistant`, `student`) |

---

## ⚙️ Installation & Setup

1. **Clone the Repository**
   ```bash
   git clone [https://github.com/your-username/your-repo-name.git](https://github.com/your-username/your-repo-name.git)
   cd your-repo-name
