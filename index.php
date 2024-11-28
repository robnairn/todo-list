<?php
// Database connection
$servername = "localhost";
$username = "todo-admin";
$password = "vgLXE,g}BgC#";
$dbname = "todo";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create new list
if (isset($_POST['add_list'])) {
    $list_name = $_POST['list_name'];
    $sql = "INSERT INTO lists (name) VALUES ('$list_name')";
    $conn->query($sql);
}

// Delete list
if (isset($_POST['delete_list'])) {
    $list_id = $_POST['list_id'];
    $sql = "DELETE FROM lists WHERE id = $list_id";
    $conn->query($sql);
    header("Location: index.php");
    exit();
}

// Create todo item
if (isset($_POST['add'])) {
    $task = $_POST['task'];
    $list_id = $_POST['list_id'];
    $sql = "INSERT INTO todos (list_id, task, position) SELECT $list_id, '$task', COALESCE(MAX(position), 0) + 1 FROM todos WHERE list_id = $list_id";
    $conn->query($sql);
}

// Delete todo item
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM todos WHERE id = $id";
    $conn->query($sql);
}

// Toggle task completion
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $sql = "UPDATE todos SET completed = NOT completed WHERE id = $id";
    $conn->query($sql);
}

// Edit todo item
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $task = $_POST['task'];
    $sql = "UPDATE todos SET task = '$task' WHERE id = $id";
    $conn->query($sql);
}

// Reorder items
if (isset($_POST['order'])) {
    $order = explode(',', $_POST['order']);
    foreach ($order as $index => $id) {
        $sql = "UPDATE todos SET position = $index WHERE id = $id";
        $conn->query($sql);
    }
    exit();
}

// Fetch lists
$lists_sql = "SELECT * FROM lists";
$lists_result = $conn->query($lists_sql);

// Determine current list
$current_list_id = isset($_POST['list_id']) ? $_POST['list_id'] : (isset($_GET['list_id']) ? $_GET['list_id'] : null);

// If no list is selected, select the first available list
if ($current_list_id === null && $lists_result->num_rows > 0) {
    $first_list = $lists_result->fetch_assoc();
    $current_list_id = $first_list['id'];
    $lists_result->data_seek(0);  // Reset the result pointer
}

// Fetch todos for current list
$todos_result = null;
if ($current_list_id !== null) {
    $todos_sql = "SELECT * FROM todos WHERE list_id = $current_list_id ORDER BY position";
    $todos_result = $conn->query($todos_sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDo Lists</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        h1, h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            background-color: #fff;
            margin: 10px 0;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }
        .delete, .edit {
            cursor: pointer;
            margin-left: 10px;
            font-weight: bold;
        }
        .delete { color: #e74c3c; }
        .edit { color: #3498db; }
        .completed { text-decoration: line-through; color: #7f8c8d; }
        .toggle {
            cursor: pointer;
            margin-right: 15px;
            font-size: 1.2em;
        }
        select, input[type="text"], input[type="submit"] {
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            font-size: 16px;
        }
        select, input[type="text"] {
            width: 100%;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #3498db;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #2980b9;
        }
        #listSelection {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        #deleteListBtn {
            margin-left: 10px;
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        #deleteListBtn:hover {
            background-color: #c0392b;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .task-text {
            flex-grow: 1;
            margin: 0 15px;
        }
        .edit-form {
            display: flex;
            width: 100%;
        }
        .edit-input {
            flex-grow: 1;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <h1>ToDo Lists</h1>

    <?php if ($lists_result->num_rows > 0): ?>
        <div id="listSelection">
            <form method="post" id="listForm" style="flex-grow: 1;">
                <select name="list_id" onchange="this.form.submit()">
                    <?php while($list = $lists_result->fetch_assoc()): ?>
                        <option value="<?php echo $list['id']; ?>" <?php echo $list['id'] == $current_list_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($list['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
            <form method="post" id="deleteListForm" onsubmit="return confirm('Are you sure you want to delete this list and all its tasks?');">
                <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
                <input type="submit" name="delete_list" value="Delete List" id="deleteListBtn">
            </form>
        </div>

        <h2>Tasks</h2>
        <form method="post" class="form-group">
            <input type="text" name="task" required placeholder="New Task">
            <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
            <input type="submit" name="add" value="Add Task">
        </form>
        <?php if ($todos_result && $todos_result->num_rows > 0): ?>
            <ul id="todoList">
                <?php while($row = $todos_result->fetch_assoc()): ?>
                    <li data-id="<?php echo $row['id']; ?>" class="<?php echo $row['completed'] ? 'completed' : ''; ?>">
                        <span class="toggle" onclick="toggleTask(<?php echo $row['id']; ?>)"><?php echo $row['completed'] ? '☑' : '☐'; ?></span>
                        <span class="task-text"><?php echo htmlspecialchars($row['task']); ?></span>
                        <span class="edit" onclick="editTask(<?php echo $row['id']; ?>)">✎</span>
                        <span class="delete" onclick="deleteTask(<?php echo $row['id']; ?>)">✖</span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No tasks in this list yet.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>No lists available. Create a new list to get started.</p>
    <?php endif; ?>

    <h2>Create New List</h2>
    <form method="post" class="form-group">
        <input type="text" name="list_name" required placeholder="New List Name">
        <input type="submit" name="add_list" value="Add New List">
    </form>

    <script>
        $(function() {
            $("#todoList").sortable({
                update: function(event, ui) {
                    var order = $(this).sortable('toArray', {attribute: 'data-id'});
                    $.post('', {order: order.join(',')});
                }
            });
        });

        function deleteTask(id) {
            if (confirm('Are you sure you want to delete this task?')) {
                window.location.href = '?list_id=<?php echo $current_list_id; ?>&delete=' + id;
            }
        }

        function toggleTask(id) {
            window.location.href = '?list_id=<?php echo $current_list_id; ?>&toggle=' + id;
        }

        function editTask(id) {
            var listItem = $('li[data-id="' + id + '"]');
            var taskText = listItem.find('.task-text').text();
            
            listItem.html(`
                <form method="post" class="edit-form">
                    <input type="hidden" name="id" value="${id}">
                    <input type="text" name="task" value="${taskText}" required class="edit-input">
                    <input type="submit" name="edit" value="Save">
                </form>
            `);
        }
    </script>
</body>
</html>
