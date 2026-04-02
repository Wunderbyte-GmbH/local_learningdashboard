# Learning Dashboard (local_learningdashboard)

A Moodle local plugin that provides role-based dashboards for **students**, **trainers** and **reha-coaches**, showing course progress, competency checks, quiz results and activity metrics in filterable, sortable tables.

## Features

| Dashboard | Page | Capability |
|-----------|------|------------|
| **Student** | `student.php` | `local/learningdashboard:viewstudent` |
| **Trainer** | `trainer.php` | `local/learningdashboard:viewtrainer` |
| **Trainer Grading** | `trainer_grading.php` | `local/learningdashboard:viewtrainer` |
| **Reha Coach** | `rehacoach.php` | `local/learningdashboard:viewrehacoach` |

### Data columns

- **Course progress** – completion percentage based on course module completions.
- **Kompetenzchecks** – grades from assignments whose name starts with `Kompetenzcheck`. Displayed without decimal places. Only completed checks are shown.
- **Quiz** – grades from quizzes, HVP activities and H5P activities whose name starts with `Quiz`. Only completed attempts are shown, including the date of completion.
- **Weekly / Monthly activities** – number of course module completions in the last 7 / 30 days.

### Trainer features

- Trainers see all users that share their **city** value. Multiple comma-separated cities are supported (e.g. `Berlin, Hamburg`).
- **Trainer Grading** lists open assignment submissions with a direct "Bewerten" link to the Moodle grader.
- Full-text search and filters for name, course, city and department.

### Reha Coach features

- Toggle between **Show My** (own department only) and **Show All** views.
- Same data columns as the trainer dashboard.

## Requirements

| Requirement | Version |
|-------------|---------|
| Moodle | 4.3+ (2023100900) |
| [local_wunderbyte_table](https://github.com/Wunderbyte-GmbH/moodle-local_wunderbyte_table) | Required |
| mod_hvp *(optional)* | If installed, HVP quiz grades are included |

## Installation

1. Clone or copy the plugin into `local/learningdashboard/`:

   ```bash
   cd /path/to/moodle/local
   git clone https://github.com/Wunderbyte-GmbH/moodle-local_learningdashboard.git learningdashboard
   ```

2. Visit **Site administration → Notifications** to trigger the install/upgrade.

3. Assign the appropriate capabilities to your roles:
   - `local/learningdashboard:viewstudent` – students
   - `local/learningdashboard:viewtrainer` – trainers / editing teachers
   - `local/learningdashboard:viewrehacoach` – reha coaches / managers
   - `local/learningdashboard:manage` – administrators

## Configuration

Navigate to **Site administration → Plugins → Local plugins → Learning Dashboard Settings**.

| Setting | Description |
|---------|-------------|
| **Included Courses** | Restrict dashboards to specific courses. Leave empty to include all. |
| **Show Kompetenzchecks** | Toggle the competency checks column on/off. |
| **Show Quiz** | Toggle the quiz results column on/off. |

## How data is collected

### Kompetenzchecks (gpoints service)

Pulls grades from `{assign}` where assignment name starts with `Kompetenzcheck`. Only graded submissions (`finalgrade IS NOT NULL`) are included.

### Quiz (lzk service)

Pulls grades from three activity types via `UNION ALL`:

1. **mod_quiz** – `{quiz}` table, `itemmodule = 'quiz'`
2. **mod_hvp** *(conditional)* – `{hvp}` table, `itemmodule = 'hvp'` – only if the plugin is installed
3. **mod_h5pactivity** – `{h5pactivity}` table, `itemmodule = 'h5pactivity'`

All three filter on `name LIKE 'Quiz%'` and `finalgrade IS NOT NULL`.

### Activities (activities service)

Counts `{course_modules_completion}` records per user/course for the last 7 days (weekly) and 30 days (monthly).

## File structure

```
local/learningdashboard/
├── classes/
│   ├── output/
│   │   └── dashboard.php              # Renderable dashboard output
│   ├── service/
│   │   ├── activities.php             # Weekly/monthly activity service
│   │   ├── gpoints.php                # Kompetenzcheck grades service
│   │   └── lzk.php                    # Quiz grades service
│   ├── table/
│   │   ├── base_learningdashboard_table.php  # Base table with shared columns
│   │   ├── badge_table.php            # Badge display table
│   │   ├── grading_overview_table.php # Trainer grading table
│   │   ├── student_progress_table.php # Student progress table
│   │   └── trainer_progress_table.php # Trainer/coach progress table
│   └── observer.php                   # Event observers
├── db/
│   ├── access.php                     # Capability definitions
│   ├── caches.php                     # Cache definitions
│   └── events.php                     # Event observer registration
├── lang/
│   ├── de/local_learningdashboard.php # German strings
│   └── en/local_learningdashboard.php # English strings
├── templates/
│   └── dashboard.mustache             # Dashboard index template
├── index.php                          # Dashboard landing page
├── lib.php                            # Navbar hook & course filter helper
├── rehacoach.php                      # Reha coach dashboard page
├── settings.php                       # Admin settings
├── student.php                        # Student dashboard page
├── studentbadges.php                  # Student badges page
├── trainer.php                        # Trainer dashboard page
├── trainer_grading.php                # Trainer grading overview page
├── trainerbadges.php                  # Trainer badges page
└── version.php                        # Plugin version
```

## License

This plugin is licensed under the [GNU GPL v3 or later](https://www.gnu.org/copyleft/gpl.html).

© 2026 [Wunderbyte GmbH](https://www.wunderbyte.at)
