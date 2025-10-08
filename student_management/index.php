<?php
// ====== Database Connection ======
$mysqli = new mysqli("localhost", "root", "", "student_db");
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// ====== Handle AJAX Requests ======
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Add Student
    if ($_POST['action'] === 'add') {
        $name = $_POST['name'];
        $roll = $_POST['roll'];
        $course = $_POST['course'];
        $age = $_POST['age'];

        $stmt = $mysqli->prepare("INSERT INTO students (name, roll, course, age) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $roll, $course, $age);
        if ($stmt->execute()) echo json_encode(["status" => "success"]);
        else echo json_encode(["status" => "error", "message" => $stmt->error]);
        exit;
    }

    // Edit Student
    if ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $roll = $_POST['roll'];
        $course = $_POST['course'];
        $age = $_POST['age'];

        $stmt = $mysqli->prepare("UPDATE students SET name=?, roll=?, course=?, age=? WHERE id=?");
        $stmt->bind_param("sssii", $name, $roll, $course, $age, $id);
        if ($stmt->execute()) echo json_encode(["status" => "success"]);
        else echo json_encode(["status" => "error", "message" => $stmt->error]);
        exit;
    }

    // Delete Student
    if ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $mysqli->prepare("DELETE FROM students WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) echo json_encode(["status" => "success"]);
        else echo json_encode(["status" => "error", "message" => $stmt->error]);
        exit;
    }

    // Fetch All Students
    if ($_POST['action'] === 'fetch') {
        $result = $mysqli->query("SELECT * FROM students ORDER BY id DESC");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            max-width: 900px;
            margin-top: 50px;
        }

        .card {
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card p-4">
            <h3 class="text-center mb-4">🎓 Student Management System</h3>

            <!-- Add/Edit Form -->
            <form id="studentForm" class="row g-3">
                <input type="hidden" id="student_id">
                <div class="col-md-6">
                    <input type="text" id="name" class="form-control" placeholder="Student Name" required>
                </div>
                <div class="col-md-6">
                    <input type="text" id="roll" class="form-control" placeholder="Roll No" required>
                </div>
                <div class="col-md-6">
                    <input type="text" id="course" class="form-control" placeholder="Course">
                </div>
                <div class="col-md-3">
                    <input type="number" id="age" class="form-control" placeholder="Age">
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary" id="saveBtn">Add Student</button>
                </div>
            </form>

            <hr>

            <!-- Table -->
            <table class="table table-striped mt-3 text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Roll</th>
                        <th>Course</th>
                        <th>Age</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="studentTable"></tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const form = document.getElementById("studentForm");
            const saveBtn = document.getElementById("saveBtn");
            const tableBody = document.getElementById("studentTable");
            const idField = document.getElementById("student_id");

            function fetchStudents() {
                fetch("", {
                        method: "POST",
                        body: new URLSearchParams({
                            action: "fetch"
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        tableBody.innerHTML = "";
                        data.forEach(s => {
                            tableBody.innerHTML += `
                        <tr>
                            <td>${s.id}</td>
                            <td>${s.name}</td>
                            <td>${s.roll}</td>
                            <td>${s.course}</td>
                            <td>${s.age}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editStudent(${s.id}, '${s.name}', '${s.roll}', '${s.course}', ${s.age})">Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteStudent(${s.id})">Delete</button>
                            </td>
                        </tr>`;
                        });
                    });
            }

            window.editStudent = function(id, name, roll, course, age) {
                idField.value = id;
                document.getElementById("name").value = name;
                document.getElementById("roll").value = roll;
                document.getElementById("course").value = course;
                document.getElementById("age").value = age;
                saveBtn.textContent = "Update Student";
                saveBtn.classList.replace("btn-primary", "btn-success");
            };

            window.deleteStudent = function(id) {
                if (confirm("Are you sure you want to delete this student?")) {
                    fetch("", {
                        method: "POST",
                        body: new URLSearchParams({
                            action: "delete",
                            id
                        })
                    }).then(res => res.json()).then(() => fetchStudents());
                }
            };

            form.addEventListener("submit", e => {
                e.preventDefault();
                const id = idField.value;
                const action = id ? "edit" : "add";
                const data = {
                    action,
                    id,
                    name: document.getElementById("name").value,
                    roll: document.getElementById("roll").value,
                    course: document.getElementById("course").value,
                    age: document.getElementById("age").value
                };

                fetch("", {
                        method: "POST",
                        body: new URLSearchParams(data)
                    })
                    .then(res => res.json())
                    .then(() => {
                        form.reset();
                        idField.value = "";
                        saveBtn.textContent = "Add Student";
                        saveBtn.classList.replace("btn-success", "btn-primary");
                        fetchStudents();
                    });
            });

            fetchStudents();
        });
    </script>
</body>

</html>