<?php
session_start();
include('config/config.php');

// Fetch scheduled exams from database
function getScheduledExams($month, $year) {
    global $conn;
    $query = "SELECT e.exam_id, e.title, e.exam_type, e.scheduled_date, e.cover_image, 
                     (SELECT COUNT(*) FROM questions WHERE exam_id = e.exam_id) as question_count 
              FROM exams e 
              WHERE e.is_scheduled = 1 
              AND MONTH(e.scheduled_date) = ? 
              AND YEAR(e.scheduled_date) = ?
              ORDER BY e.scheduled_date ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = array();
    while($row = $result->fetch_assoc()) {
        $date = date('Y-m-d', strtotime($row['scheduled_date']));
        if(!isset($events[$date])) {
            $events[$date] = array();
        }
        $events[$date][] = $row;
    }
    return $events;
}

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Get the first day of the month
$firstDay = mktime(0,0,0,$month,1,$year);
$numberDays = date('t', $firstDay);
$dateComponents = getdate($firstDay);
$monthName = $dateComponents['month'];
$dayOfWeek = $dateComponents['wday'];

// Get all scheduled exams for this month
$scheduledExams = getScheduledExams($month, $year);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Calendar</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        .calendar-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 20px 0;
            overflow: hidden;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .calendar-nav button {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .calendar-nav button:hover {
            background-color: #e9ecef;
        }

        .current-month {
            font-size: 1.2rem;
            font-weight: 500;
            color: #333;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: #e0e0e0;
        }

        .calendar-day-header {
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            font-weight: 500;
            color: #666;
        }

        .calendar-day {
            background: white;
            min-height: 120px;
            padding: 10px;
            position: relative;
        }

        .calendar-day.today {
            background-color: #fff8e6;
        }

        .calendar-day.other-month {
            background-color: #f8f9fa;
        }

        .day-number {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #666;
        }

        .today .day-number {
            background-color: #75343A;
            color: white;
            border-radius: 50%;
        }

        .event {
            margin-top: 25px;
            margin-bottom: 5px;
            padding: 8px;
            border-radius: 4px;
            background-color: #e6f7ff;
            border-left: 3px solid rgba(212, 99, 99, 0.9);
            font-size: 0.85rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .event:hover {
            transform: translateY(-2px);
        }

        .event-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 3px;
        }

        .event-time {
            color: #666;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .event-type {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.75rem;
            background-color: rgba(212, 99, 99, 0.9);
            color: white;
            margin-top: 3px;
        }

        /* Event Modal Styles */
        .event-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .event-modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        /* Add Event Button */
        .add-event-btn {
            background: #75343A;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .add-event-btn:hover {
            background: #5c2a2f;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .add-event-btn .material-symbols-rounded {
            font-size: 20px;
        }

        /* Add these new styles */
        .calendar-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .title-section {
            flex: 1;
        }

        .page-title {
            font-size: 36px;
            color: #75343A;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }

        .page-title .material-symbols-rounded {
            font-size: 32px;
            color: #75343A;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
            margin: 0;
        }
    </style>
</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>
    <div class="main">
        <div class="calendar-page-header">
            <div class="title-section">
                <h2 class="page-title">
                    <span class="material-symbols-rounded">calendar_month</span>
                    Exam Calendar
                </h2>
                <p class="subtitle">Manage and view scheduled exams</p>
            </div>
            <button class="add-event-btn" onclick="window.location.href='quiz_editor.php?new=1'">
                <span class="material-symbols-rounded">add</span>
                Schedule New Exam
            </button>
        </div>

        <div class="calendar-container">
            <div class="calendar-header">
                <div class="calendar-nav">
                    <button onclick="changeMonth(-1)">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <h2 class="current-month"><?php echo $monthName . " " . $year; ?></h2>
                    <button onclick="changeMonth(1)">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>
            </div>

            <div class="calendar-grid">
                <!-- Day headers -->
                <?php
                $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach($days as $day) {
                    echo "<div class='calendar-day-header'>$day</div>";
                }

                // Blank days before start of month
                for($i = 0; $i < $dayOfWeek; $i++) {
                    echo "<div class='calendar-day other-month'></div>";
                }

                // Days of the month
                for($day = 1; $day <= $numberDays; $day++) {
                    $currentDate = date('Y-m-d', mktime(0,0,0,$month,$day,$year));
                    $isToday = $currentDate == date('Y-m-d');
                    
                    echo "<div class='calendar-day" . ($isToday ? ' today' : '') . "'>";
                    echo "<div class='day-number'>$day</div>";
                    
                    // Display events for this day
                    if(isset($scheduledExams[$currentDate])) {
                        foreach($scheduledExams[$currentDate] as $exam) {
                            echo "<div class='event' onclick='showEventDetails(" . json_encode($exam) . ")'>";
                            echo "<div class='event-title'>" . htmlspecialchars($exam['title']) . "</div>";
                            echo "<div class='event-time'>";
                            echo "<span class='material-symbols-rounded'>schedule</span>";
                            echo date('g:i A', strtotime($exam['scheduled_date']));
                            echo "</div>";
                            echo "<div class='event-type'>" . ucfirst($exam['exam_type']) . "</div>";
                            echo "</div>";
                        }
                    }
                    
                    echo "</div>";
                }

                // Blank days after end of month
                $totalDays = $dayOfWeek + $numberDays;
                $remainingDays = ceil($totalDays/7)*7 - $totalDays;
                for($i = 0; $i < $remainingDays; $i++) {
                    echo "<div class='calendar-day other-month'></div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div id="eventModal" class="event-modal">
    <div class="event-modal-content">
        <button class="close-modal" onclick="closeEventModal()">×</button>
        <div id="eventDetails"></div>
    </div>
</div>
<script src="assets/js/side.js"></script>
<script>
function changeMonth(offset) {
    const urlParams = new URLSearchParams(window.location.search);
    let month = parseInt(urlParams.get('month')) || <?php echo date('n'); ?>;
    let year = parseInt(urlParams.get('year')) || <?php echo date('Y'); ?>;
    
    month += offset;
    
    if(month > 12) {
        month = 1;
        year++;
    } else if(month < 1) {
        month = 12;
        year--;
    }
    
    window.location.href = `calendar.php?month=${month}&year=${year}`;
}

function showEventDetails(exam) {
    const modal = document.getElementById('eventModal');
    const details = document.getElementById('eventDetails');
    
    details.innerHTML = `
        <h3 style="margin-bottom: 15px; color: #333;">${exam.title}</h3>
        <div style="margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <span class="material-symbols-rounded">schedule</span>
                <span>${new Date(exam.scheduled_date).toLocaleString()}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <span class="material-symbols-rounded">quiz</span>
                <span>${exam.question_count} questions</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="material-symbols-rounded">label</span>
                <span>${exam.exam_type.charAt(0).toUpperCase() + exam.exam_type.slice(1)}</span>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.location.href='quiz_editor.php?exam_id=${exam.exam_id}'" 
                    style="background: #75343A; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                Edit Exam
            </button>
        </div>
    `;
    
    modal.style.display = 'flex';
}

function closeEventModal() {
    document.getElementById('eventModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('eventModal').addEventListener('click', function(e) {
    if(e.target === this) {
        closeEventModal();
    }
});
</script>

</body>
</html>
