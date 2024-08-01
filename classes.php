<?php
session_start();

// Fonction pour lire les utilisateurs depuis le fichier JSON
function getUsers() {
    $json = file_get_contents('users.json');
    return json_decode($json, true);
}

// Fonction pour sauvegarder les utilisateurs dans le fichier JSON
function saveUsers($users) {
    $json = json_encode($users, JSON_PRETTY_PRINT);
    file_put_contents('users.json', $json);
}

// Fonction pour lire les classes depuis le fichier JSON
function getClasses() {
    $json = file_get_contents('classes.json');
    return json_decode($json, true);
}

// Lire les questions depuis le fichier JSON
function getQuestions() {
    $json = file_get_contents('questions.json');
    return json_decode($json, true);
}

// Lire les réponses depuis le fichier JSON
function getResponses() {
    $json = file_get_contents('responses.json');
    return json_decode($json, true);
}

// Fonction pour sauvegarder les classes dans le fichier JSON
function saveClasses($classes) {
    $json = json_encode($classes, JSON_PRETTY_PRINT);
    file_put_contents('classes.json', $json);
}

// Fonction pour vérifier les informations de connexion
function verifyLogin($username, $password) {
    $users = getUsers();
    foreach ($users as $user) {
        if ($user['username'] == $username && password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

// Fonction pour créer un nouvel utilisateur
function createUser($username, $password) {
    $users = getUsers();
    foreach ($users as $user) {
        if ($user['username'] == $username) {
            return false; // L'utilisateur existe déjà
        }
    }
    $newUser = [
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'classes' => []
    ];
    $users[] = $newUser;
    saveUsers($users);
    return $newUser;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['login'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];
            $user = verifyLogin($username, $password);
            if ($user) {
                $_SESSION['user'] = $user;
                header('Location: classes.php');
                exit;
            } else {
                $error = "Nom d'utilisateur ou mot de passe incorrect.";
            }
        } elseif (isset($_POST['register'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];
            $user = createUser($username, $password);
            if ($user) {
                $_SESSION['user'] = $user;
                header('Location: classes.php');
                exit;
            } else {
                $error = "Le nom d'utilisateur existe déjà.";
            }
        }
    }

    // Afficher le formulaire de connexion/inscription
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Connexion - Gestion des Classes</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                padding: 20px;
            }
            .container {
                max-width: 400px;
                margin: auto;
                background-color: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
            }
            .form-group input[type="text"],
            .form-group input[type="password"] {
                width: 100%;
                padding: 8px;
                box-sizing: border-box;
            }
            .form-group button {
                background-color: #4CAF50;
                color: white;
                padding: 10px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .form-group button:hover {
                background-color: #45a049;
            }
            .error {
                color: red;
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Connexion</h2>';
            if (isset($error)) {
                echo '<div class="error">' . htmlspecialchars($error) . '</div>';
            }
            echo '<form method="post">
                <div class="form-group">
                    <label for="username">Nom d\'utilisateur</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="login">Se connecter</button>
                </div>
            </form>
            <h2>Créer un compte</h2>
            <form method="post">
                <div class="form-group">
                    <label for="username">Nom d\'utilisateur</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="register">Créer un compte</button>
                </div>
            </form>
        </div>
    </body>
    </html>';
    exit;
}

// Utilisateur connecté
$user = $_SESSION['user'];
$classes = getClasses();
$questions = getQuestions();
$responses = getResponses();

// Filtrer les classes pour n'afficher que celles de l'utilisateur
$userClasses = array_filter($classes, function($class) use ($user) {
    return in_array($class['id'], $user['classes']);
});

// Ajouter une nouvelle classe
if (isset($_POST['addClass'])) {
    $newClass = [
        'id' => count($classes) + 1,
        'name' => $_POST['className'],
        'pin' => $_POST['classPin']
    ];
    $classes[] = $newClass;
    saveClasses($classes);

    // Ajouter la classe à l'utilisateur
    $user['classes'][] = $newClass['id'];
    $users = getUsers();
    foreach ($users as &$u) {
        if ($u['username'] == $user['username']) {
            $u['classes'] = $user['classes'];
            break;
        }
    }
    saveUsers($users);

    $_SESSION['user'] = $user;
}

// Supprimer une classe
if (isset($_GET['deleteClass'])) {
    $classId = $_GET['deleteClass'];
    $classes = array_filter($classes, function($class) use ($classId) {
        return $class['id'] != $classId;
    });
    saveClasses($classes);

    // Supprimer la classe de l'utilisateur
    $user['classes'] = array_filter($user['classes'], function($id) use ($classId) {
        return $id != $classId;
    });
    $users = getUsers();
    foreach ($users as &$u) {
        if ($u['username'] == $user['username']) {
            $u['classes'] = $user['classes'];
            break;
        }
    }
    saveUsers($users);

    $_SESSION['user'] = $user;
    header('Location: classes.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Classes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #4CAF50;
            color: white;
        }
        .header h2 {
            margin: 0;
        }
        .header .menu-button {
            background-color: white;
            color: #4CAF50;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header .menu-button:hover {
            background-color: #45a049;
        }
        .menu {
            display: none;
            position: absolute;
            top: 60px;
            right: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 10px;
            border-radius: 8px;
        }
        .menu a {
            display: block;
            padding: 10px;
            color: #4CAF50;
            text-decoration: none;
        }
        .menu a:hover {
            background-color: #f4f4f4;
        }
        .container {
            padding: 20px;
        }
        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .class-item {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: calc(33.333% - 20px);
            box-sizing: border-box;
        }
        .class-item h3 {
            margin-top: 0;
        }
        .class-item p {
            margin: 5px 0;
        }
        .class-item .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .class-item .actions button {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }
        .class-item .actions button:hover {
            background-color: #45a049;
        }
        .class-item .actions .delete-button {
            background-color: #f44336;
        }
        .class-item .actions .delete-button:hover {
            background-color: #e53935;
        }
        /* Styles pour la modale */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        .modal.show {
            display: block;
            opacity: 1;
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Classes</h2>
    <button class="menu-button" onclick="toggleMenu()">+</button>
    <div class="menu" id="menu">
        <a href="#" onclick="showAddClassModal()">Créer une classe</a>
    </div>
</div>
<div class="container">
    <div class="grid">
        <?php foreach ($userClasses as $class): 
            $classId = $class['id'];
            $numQuestions = isset($questions[$classId]) ? count($questions[$classId]) : 0;
            $numResponses = isset($responses[$classId]) ? count($responses[$classId]) : 0;
        ?>
            <div class="class-item">
                <h3><?php echo htmlspecialchars($class['name']); ?></h3>
                <p>Nombre de questions : <?php echo $numQuestions; ?></p>
                <p>Nombre de réponses : <?php echo $numResponses; ?></p>
                <div class="actions">
                    <button onclick="window.location.href='classe.php?id=<?php echo $class['id']; ?>'">Gérer</button>
                    <button class="delete-button" onclick="window.location.href='?deleteClass=<?php echo $class['id']; ?>'">Supprimer</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>



<!-- Modale pour créer une nouvelle classe -->
<div id="addClassModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddClassModal()">&times;</span>
        <form method="post">
            <div class="form-group">
                <label for="className">Nom de la classe</label>
                <input type="text" id="className" name="className" required>
            </div>
            <div class="form-group">
                <label for="classPin">Code PIN</label>
                <input type="text" id="classPin" name="classPin" required>
            </div>
            <div class="form-group">
                <button type="submit" name="addClass">Ajouter la classe</button>
            </div>
        </form>
    </div>
</div>


<script>
function toggleMenu() {
    var menu = document.getElementById('menu');
    if (menu.style.display === 'block') {
        menu.style.display = 'none';
    } else {
        menu.style.display = 'block';
    }
}

function showAddClassModal() {
    var modal = document.getElementById('addClassModal');
    modal.classList.add('show');
}

function closeAddClassModal() {
    var modal = document.getElementById('addClassModal');
    modal.classList.remove('show');
}

// Fermer la modale lorsqu'on clique à l'extérieur de celle-ci
window.onclick = function(event) {
    var modal = document.getElementById('addClassModal');
    if (event.target == modal) {
        modal.classList.remove('show');
    }
}
</script>

</body>
</html>