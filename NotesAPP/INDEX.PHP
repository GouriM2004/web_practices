<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notes App</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f8f9fa, #dbeafe);
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .notes-container {
      max-width: 700px;
      width: 100%;
      background: white;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      padding: 30px;
    }
    .note-card {
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      transition: transform 0.2s ease-in-out;
    }
    .note-card:hover {
      transform: translateY(-4px);
    }
    textarea {
      resize: none;
    }
    .btn-custom {
      background: linear-gradient(90deg, #007bff, #00c4ff);
      color: white;
      border: none;
    }
    .btn-custom:hover {
      background: linear-gradient(90deg, #0069d9, #00a8e8);
    }
  </style>
</head>
<body>

<div class="notes-container">
  <h3 class="text-center mb-4 fw-bold text-primary">📝 My Notes</h3>

  <div class="mb-3">
    <textarea id="noteInput" class="form-control shadow-sm" rows="3" placeholder="Write your note here..."></textarea>
  </div>
  <div class="text-center mb-4">
    <button class="btn btn-custom px-4" onclick="addNote()">Add Note</button>
  </div>

  <div id="notesList" class="row gy-3"></div>
</div>

<script>
  // Load existing notes
  document.addEventListener("DOMContentLoaded", showNotes);

  function addNote() {
    const noteInput = document.getElementById("noteInput");
    const noteText = noteInput.value.trim();

    if (noteText === "") {
      alert("Please write something before adding!");
      return;
    }

    let notes = JSON.parse(localStorage.getItem("notes")) || [];
    notes.push(noteText);
    localStorage.setItem("notes", JSON.stringify(notes));

    noteInput.value = "";
    showNotes();
  }

  function showNotes() {
    const notesList = document.getElementById("notesList");
    const notes = JSON.parse(localStorage.getItem("notes")) || [];

    if (notes.length === 0) {
      notesList.innerHTML = "<p class='text-center text-muted'>No notes yet. Add one above!</p>";
      return;
    }

    notesList.innerHTML = notes.map((note, index) => `
      <div class="col-md-6">
        <div class="card note-card p-3">
          <p>${note}</p>
          <button class="btn btn-outline-danger btn-sm mt-2" onclick="deleteNote(${index})">Delete</button>
        </div>
      </div>
    `).join('');
  }

  function deleteNote(index) {
    let notes = JSON.parse(localStorage.getItem("notes")) || [];
    notes.splice(index, 1);
    localStorage.setItem("notes", JSON.stringify(notes));
    showNotes();
  }
</script>

</body>
</html>
